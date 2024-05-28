<?php 
	
if (! defined('ABSPATH')) {
    exit();
}

class PL_WCPT_Products {
	
	public $wallet_updated = false;
	private $plugin_instance = null;
	private $product_stock_updated = array();
	
	public function __construct() {
		
		// Add Product Fields
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_product_fields' ) );
		
		// Save Product Fields
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_fields' ), 99 );
		
		// Change stock status based on vouchers data
	    add_filter( 'woocommerce_product_is_in_stock', array( $this, 'set_product_voucher_stock_status' ), 10, 2 );
	    
	    // Change stock quantities based on vouchers data
	    add_action( 'woocommerce_product_get_stock_quantity', array( $this, 'set_product_voucher_stock' ), 10, 2 );
	    
	    // Check for price updates
		add_action( 'pl_wcpt_check_price_updates', array( $this, 'check_pt_price_updates' ), 1 );
	    	    
	    // Initialize plugin instance
	    $this->plugin_instance = pl_wcpt_extention_free::get_instance();
	}	
	
	public function check_pt_price_updates() {
		
		global $wpdb;
		$wcpt_products = $wpdb->prefix . 'wcpt_products';
		$products = $wpdb->get_results( "SELECT pm1.post_id as post_id, pm2.meta_value as product_id, pm1.meta_value as price, sell_price from {$wpdb->postmeta} as pm1 INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id AND pm2.meta_key='_pt_product' INNER JOIN $wcpt_products as ptp ON pm2.meta_value=ptp.product_id WHERE pm1.meta_key='_pt_price'" );
				
		foreach ( $products as $product ) {
			if ( $product->price != $product->sell_price && $product->post_id ) {
				update_post_meta( $product->post_id, '_pt_price', $product->sell_price );
			}
		}							
	}
		
	public function set_product_voucher_stock( $stock, $product ) {
					
		$product_id = $product->get_id();
		$pt_product = get_post_meta( $product_id, '_pt_product', TRUE );
		if ( $pt_product ) {
			$stock_db = new PL_WCPT_Vouchers_DB();
			$vouchers = $stock_db->get_active_vouchers_by_product_id_count( $pt_product );
			if ( isset( $vouchers->count ) ) {
				$new_stock = $vouchers->count;
														
				update_post_meta( $product_id, 'pl_pt_base_stock', $new_stock );	
				
				// Remove from stock reserved stock
				$reserved_db    = new PL_WCPT_Products_Reserved();
				$reserved_stock = $reserved_db->calculate_reserved_stock( $product_id );
				$new_stock     -= $reserved_stock;
									
				$stock_management = $this->get_product_stock_management( $product_id );
		
				if ( $stock_management == 'no' ) {
					
					$base_stock = $new_stock;
					$new_stock  = $this->get_on_demand_stock( $new_stock, $pt_product, $product_id );
									
					if ( !is_admin() ) {
						$max_items = get_option( 'pl_wcpt_api_purchase_limit' ) ? get_option( 'pl_wcpt_api_purchase_limit' ) : 20;						
						if ( $new_stock > $max_items ) {
							$new_stock = $max_items;
						}
					}
				}
																			
				update_post_meta( $product_id, '_stock', $new_stock );
				
				return $new_stock;
			}
		}
		
		return $stock;
	}
	
	public function set_product_voucher_stock_status( $in_stock, $product ) {
							
		$product_id = $product->get_id();
		$pt_product = get_post_meta( $product_id, '_pt_product', TRUE );
		if ( $pt_product ) {
			$stock_db = new PL_WCPT_Vouchers_DB();
			$vouchers = $stock_db->get_active_vouchers_by_product_id_count( $pt_product );
			if ( isset( $vouchers->count ) ) {
				$new_stock  = $vouchers->count;
													
				// Remove from stock reserved stock
				$reserved_db    = new PL_WCPT_Products_Reserved();
				$reserved_stock = $reserved_db->calculate_reserved_stock( $product_id );
				$new_stock     -= $reserved_stock;
									
				$stock_management = $this->get_product_stock_management( $product_id );
		
				if ( $stock_management == 'no' ) {
					
					$base_stock = $new_stock;
					$new_stock  = $this->get_on_demand_stock( $new_stock, $pt_product, $product_id );					
					if ( !is_admin() ) {
						
						$max_items = get_option( 'pl_wcpt_api_purchase_limit' ) ? get_option( 'pl_wcpt_api_purchase_limit' ) : 20;						
						if ( $new_stock > $max_items ) {
							$new_stock = $max_items;
						}
					}
				}
													
				$new_status = ( $new_stock > 0 ) ? 1 : 0;		
				if ( $new_status ) {
					$this->plugin_instance->update_product_stock_status( $product_id, 'instock' );
				} else {
					$this->plugin_instance->update_product_stock_status( $product_id, 'outofstock' );
				}
				
				$this->maybe_update_visibility( $product, $new_status );
				
				return $new_status;
			}
		}
		
		return $in_stock;
	}
	
	public function maybe_update_visibility( $product, $in_stock ) {
		
		$terms   = wp_get_post_terms( $product->get_id(), 'product_visibility' );
		$updated = false;
		$has_oos = false;
		
		foreach ( $terms as $term_k => $term ) {
			if ( $term->slug == 'outofstock' ) {
				$has_oos = true;
				if ( $in_stock ) {
					unset( $terms[ $term_k ] );
					$updated = true;
				}
			}
		}
				
		if ( !$in_stock && !$has_oos ) {
			$oos_term = get_term_by( 'slug', 'outofstock', 'product_visibility' );
			if ( $oos_term ) {
				$terms[] = $oos_term;
				$updated = true;
			}
		}
				
		if ( $updated ) {
			$term_ids = array();
			foreach ( $terms as $term ) {
				$term_ids[] = $term->term_id;
			}

			if ( ! is_wp_error( wp_set_post_terms( $product->get_id(), $term_ids, 'product_visibility', false ) ) ) {
				do_action( 'woocommerce_product_set_visibility', $product->get_id(), $product->get_catalog_visibility() );
			}
		}
	}
		
	public function get_product_stock_management( $product_id ) {
		
		$stock_management = '';
		$stock_level 	  = get_post_meta( $product_id, '_pt_stock_level', TRUE );		
		if ( $stock_level == 0 ) {
			$stock_management = 'no';
		}
					
		return $stock_management;
	}
	
	public function get_on_demand_stock( $new_stock, $pt_product, $product_id ) {
									
		$force = ( is_cart() || is_checkout() ) ? true : false;
		if ( isset( $_POST['woocommerce-process-checkout-nonce'] ) ) {
			$force = false;
		}
				
		$stock_management = $this->get_product_stock_management( $product_id );
		
		if ( $stock_management == 'no' ) {
			
			if ( !$this->wallet_updated ) {
				$wallet = $this->plugin_instance->get_wallet_amount( $force );
			} else {
				$wallet = $this->plugin_instance->get_wallet_amount( false );
			}
			if ( $force ) {
				$this->wallet_updated        = true;
			}

			if ( $wallet ) {
				
				if ( $force ) {
					
					if ( !in_array( $product_id, $this->product_stock_updated ) ) {
						
						$this->product_stock_updated[] = $product_id;						
						$this->plugin_instance->update_product_stock( $product_id );					
					}	
				}
				
				$product_db = new PL_WCPT_Products_DB();
				$product    = $product_db->get_product( $pt_product );
				
				if ( !is_admin() ) {				
					// Bail on descontinued products
					if ( $product && $product->discontinued ) {
						return $new_stock;
					}
				}
				
				$sell_price = isset( $product->sell_price )      ? $product->sell_price : 0;
				$quantity   = isset( $product->stock_available ) ? $product->stock_available : 0;
				
				if ( $sell_price && $quantity ) {
					
					$ondemand_stock = ( floor( $wallet / $sell_price ) <= $quantity ) ? floor( $wallet / $sell_price ) : $quantity;
					update_post_meta( $product_id, 'pl_pt_on_demand_stock', $ondemand_stock );
										
					if ( !is_admin() && $ondemand_stock > 0 ) {
						$new_stock += $ondemand_stock;
						return $new_stock;
					}
					
					if ( $ondemand_stock > 0 ) {
						$new_stock += $ondemand_stock;
					}
				} else if ( !$quantity ) {
					if ( !is_admin() ) {
						return 0;
					}
				}
			}
		}
								
		return $new_stock;
	}
	
	public function add_product_fields() {
		
		global $post;
		$product_id       	= get_post_meta( $post->ID, '_pt_product', TRUE );
		$stock_level     	= ( metadata_exists( 'post', $post->ID, '_pt_stock_level' ) ) ? get_post_meta( $post->ID, '_pt_stock_level', TRUE ) : 0;
		$product_show       = ( !$product_id ) ? 'style="display:none"' : '';							
		$fixed_price_show   = 'style="display:none"';
			
		$options = array();
		
		$products_db        = new PL_WCPT_Products_DB();
		$products           = $products_db->get_products();
		$discontinued       = $products_db->is_product_discontinued( $product_id );
		$options['']        = __( 'No', 'gift-cards-on-demand-free' );
		$discontinued_style = $discontinued ? '' : 'style="display:none;"';
					
		$selected_product     = $products_db->get_product( $product_id );
		$default_instructions = ( isset( $selected_product->instructions ) && $selected_product->instructions ) ? $selected_product->instructions : 'N/A';
		
		foreach ( $products as $product ) {
			$options[ $product->product_id ] = $product->product_name;
		}
		
		$conversion_value = ( get_option( 'pl_wcpt_api_currency_conversion_value' ) ) ? get_option( 'pl_wcpt_api_currency_conversion_value' ) : 1;
		$pt_price 		  = $selected_product ? round( $selected_product->sell_price * $conversion_value, 2 ) : '';
										
		echo '<div class="options_group">';
					
		woocommerce_wp_select( array(
	        'id'      => '_pt_product',
	        'label'   => __( 'PayThem Product', 'gift-cards-on-demand-free' ),
	        'options' =>  $options,
	        'value'   => $product_id,
	    ) );
	    
	    
	    echo "<p class='pl_wcp_discontinued_row' " . wp_kses_post( $discontinued_style ) . ">" . esc_html__( 'This product is discontinued and can no longer be purchased through Paythem.', 'gift-cards-on-demand-free' ) . "</p>";
	    
	    echo "<div " . esc_attr( $product_show ) . " id='pl_pt_product_div'>";
								
		// Stock Level
		woocommerce_wp_text_input(
			array( 
				'id'                => '_pt_stock_level', 
				'label'             => __( 'Buy & Carry Stock', 'gift-cards-on-demand-free' ), 
				'placeholder'       => '', 
				'type'              => 'number', 
				'value'   		   => $stock_level,
				'custom_attributes' => array(
					'step' 	=> '1',
					'max'	=> '20',
					'min'	=> '0'
				) 
			)
		);
					
		// Paythem Price
		woocommerce_wp_text_input( 
			array( 
				'id'          => '_pt_price', 
				'label'       => __( 'PayThem Price', 'gift-cards-on-demand-free' ), 
				'placeholder' => 'N/A',
				'custom_attributes' => array(
					'readonly' => 'readonly'
				),
				'value' => $pt_price
			)
		);
					
		$auto_selling_options = array( 'yes' => __( 'Yes, Fixed Price', 'gift-cards-on-demand-free' ) );
		woocommerce_wp_radio( array(
			  'label'       => __( 'Override Auto Selling Price', 'gift-cards-on-demand-free' ),
			  'id'          => '_pt_autoselling_price',
			  'name'        => '_pt_autoselling_price',
			  'options'     => $auto_selling_options,
			  'desc_tip'    => '', 
			  'description' => '',
			  'value'		=> 'yes'
			) 
		);
										
		echo '<p class="form-field">
		<label for="_pt_redemption_message">' . esc_html__( 'PayThem Redemption Information', 'gift-cards-on-demand-free' ) . '</label><span id="_pt_default_redemption_instructions">' . esc_attr( $default_instructions ) . '</span>';
	
		$show_redemption = array( '' => 'Show PayThem Redemption Information' );
		woocommerce_wp_radio( array(
			  'label'       => __( 'Show Redemption Information', 'gift-cards-on-demand-free' ),
			  'id'          => '_pt_show_redemption_instructions',
			  'name'        => '_pt_show_redemption_instructions',
			  'options'     => $show_redemption,
			  'desc_tip'    => '', 
			  'description' => '',
			  'value'		=> ''
			) 
		);
						
		echo '</div>';
		echo '</div>';
	}
	
	public function save_product_fields( $post_id ) {
				
		$pt_stock_level = isset( $_POST['_pt_stock_level'] ) ? sanitize_text_field( $_POST['_pt_stock_level'] ) : null;
		if ( !is_null( $pt_stock_level ) ) {
			update_post_meta( $post_id, '_pt_stock_level', $pt_stock_level );
		}
		
		$pt_stock_management = '';
		if ( $pt_stock_level == 0 ) {
			$pt_stock_management = 'no';
		}
					
		update_post_meta( $post_id, '_pt_stock_management', $pt_stock_management );
																				
		$pt_product = isset( $_POST['_pt_product'] ) ? sanitize_text_field( $_POST['_pt_product'] ) : null;
		
		$old_product = get_post_meta( $post_id, '_pt_product', TRUE );
		
		if ( $pt_product ) {
			
			$product_db  = new PL_WCPT_Products_DB();
			$product     = $product_db->get_product( $pt_product );
			$wc_product  = wc_get_product( $post_id );
			
			$sell_price = $product->sell_price;
			$unit_price = $product->unit_price;
			
			$conversion_value = ( get_option( 'pl_wcpt_api_currency_conversion_value' ) ) ? get_option( 'pl_wcpt_api_currency_conversion_value' ) : 1;
			$price = round( $sell_price * $conversion_value, 2 );

			$base_currency = $product->base_currency_symbol;
						
			$wc_product->set_manage_stock( true );
			$wc_product->set_virtual( true );
			$wc_product->save();
			
			update_post_meta( $post_id, '_pt_price', $price );
			update_post_meta( $post_id, '_pt_sell_price', $sell_price );
			update_post_meta( $post_id, '_pt_product', $pt_product );
			
			if ( $pt_product && $pt_product !== $old_product ) {					
				update_post_meta( $post_id, '_pt_product_start', gmdate( 'Y-m-d H:i:s' ) );
			}
			
			
			if ( $pt_product ) {
				$stock_db = new PL_WCPT_Vouchers_DB();
				$vouchers = $stock_db->get_active_vouchers_by_product_id_count( $pt_product );
				if ( isset( $vouchers->count ) ) {
					$new_stock  = $vouchers->count;
					update_post_meta( $post_id, '_stock', $new_stock );
				}
				
				$this->plugin_instance->auto_purchase_product( $post_id );
			}
		} else {
			if ( $old_product ) {
				delete_post_meta( $post_id, '_pt_price' );
				delete_post_meta( $post_id, '_pt_sell_price' );
				delete_post_meta( $post_id, '_pt_product' );
			}
		}
	}
}