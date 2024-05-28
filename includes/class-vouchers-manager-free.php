<?php 
	
if (! defined('ABSPATH')) {
    exit();
}

class PL_WCPT_Vouchers_Manager {
	
	private $plugin_instance = null;
	
	public function __construct() {		
		
		$release_status  = ( get_option( 'pl_wcpt_api_code_release' ) )  ? get_option( 'pl_wcpt_api_code_release' )  : 'processing';
		if ( $release_status == 'processing' ) {
			add_action( "woocommerce_order_status_completed", array ( $this, 'reduce_voucher_stock' ), 9 );
		}
		else {
			add_action( "woocommerce_order_status_processing", array ( $this, 'auto_purchase_stock_if_needed' ), 9 );
			add_action( "woocommerce_order_status_processing", array ( $this, 'buy_stock_on_demand_orders' ), 10 );
		}
		
		add_action( "woocommerce_order_status_on-hold", array ( $this, 'auto_purchase_stock_if_needed' ), 9 );
		add_action( "woocommerce_order_status_wc-failed", array ( $this, 'auto_purchase_stock_if_needed' ), 999 );
		add_action( "woocommerce_order_status_wc-refunded", array ( $this, 'auto_purchase_stock_if_needed' ), 999 );
		add_action( "woocommerce_order_status_wc-cancelled", array ( $this, 'auto_purchase_stock_if_needed' ), 999 );
		add_action( "woocommerce_order_status_wc-pending", array ( $this, 'auto_purchase_stock_if_needed' ), 999 );
	
		add_action( "woocommerce_order_status_$release_status", array ( $this, 'reduce_voucher_stock' ), 9 );
		
		// Handle on demand stock purchases
		add_action( 'pl_get_order_on_demand_stock', array( $this, 'handle_on_demand_order_purchase' ), 10, 1 );
		
		// Add release vouchers order action
		add_action( 'woocommerce_order_actions', array( $this, 'add_voucher_order_actions' ) );
		add_action( 'woocommerce_order_action_pl_release_voucher', array( $this, 'release_voucher_order_action' ) );
		
		// Auto purchase stock after order
		add_action( 'pl_purchase_product_after_order', array( $this, 'auto_purchase_after_order' ) );
		
		// Initialize plugin instance
	    $this->plugin_instance = pl_wcpt_extention_free::get_instance();
	}	
	
	public function auto_purchase_after_order( $product_id ) {
		$this->plugin_instance->auto_purchase_product( $product_id );
	} 
		
	public function auto_purchase_stock_if_needed( $order_id ) {
			
		$order = wc_get_order( $order_id );	
		foreach ( $order->get_items() as $key => $item ) {
			
			$product_id = $item->get_product_id();
			$pt_product = get_post_meta( $product_id, '_pt_product', TRUE );
			if ( $pt_product ) {
				$this->plugin_instance->auto_purchase_product( $product_id );
			}
		}
	}
	
	public function release_voucher_order_action( $order ) {
		$this->reduce_voucher_stock( $order->get_id(), true );
	}
	
	public function add_voucher_order_actions( $actions ) {
		$actions['pl_release_voucher'] = __( 'Release paythem vouchers', 'gift-cards-on-demand-free' );
		return $actions;
	}
	
	public function handle_on_demand_order_purchase( $order_id ) {
		$this->reduce_voucher_stock( $order_id, true );
	}
	
	public function buy_stock_on_demand_orders( $order_id ) {
					
		$order = wc_get_order( $order_id );
		$items = $order->get_items();
									
		$purchased = array();
		$failed    = array();
		
		foreach ( $items as $key => $item ) {
			
			$product_id = $item->get_product_id();
			$quantity   = $item->get_quantity();
			$pt_product = get_post_meta( $product_id, '_pt_product', TRUE );
			if ( $pt_product ) {
				
				$stock_management = get_post_meta( $product_id, '_pt_stock_management', TRUE );
				$already_sent     = wc_get_order_item_meta( $key, '_pl_voucher_to_user', true );
								
				if ( $stock_management == 'no' && !$already_sent ) {					
					$reserved_db     = new PL_WCPT_Products_Reserved();
					$reserved_stock  = $reserved_db->calculate_reserved_stock( $product_id, $order_id );
					
					$product_obj     = wc_get_product( $product_id );
					$stock           = $product_obj->get_stock_quantity();
					$on_demand_stock = get_post_meta( $product_id, 'pl_pt_on_demand_stock', TRUE );	
					$base_stock      = get_post_meta( $product_id, 'pl_pt_base_stock', TRUE );	
					$at_hand_stock   = $base_stock - $reserved_stock;
																	
					if ( $at_hand_stock < $quantity ) {
													
						$stock_needed = $quantity - $at_hand_stock;
						$max_items    = get_option( 'pl_wcpt_api_purchase_limit' ) ? get_option( 'pl_wcpt_api_purchase_limit' ) : 20;
						if ( $stock_needed > $max_items ) {
							$stock_needed = $max_items;
						}
													
						$result = $this->plugin_instance->purchase_paythem_product( $pt_product, $stock_needed, $product_id );
						
						if ( $result ) {
							$purchased[] = $product_obj;
						}
						else {
							$failed[] = $product_obj;
						}
					}						
				}
			}
		}
		
		if ( $purchased ) {
			
			//update_post_meta( $order_id, 'pl_purchased_on_demand', TRUE );
			$order->update_meta_data( 'pl_purchased_on_demand', TRUE );
			
			$titles = array();
			foreach ( $purchased as $purch_p ) {
				$titles[] = $purch_p->get_title();
			}
			
			$note = "Successfully purchased on demand product(s): " . implode( ',', $titles ) . '.';
			$order->add_order_note( $note );
			$order->save();
			
			do_action( 'pl_wcpt_low_wallet_email' );
		} 
		if ( $failed ) {
			$titles = array();
			foreach ( $failed as $failed_p ) {
				$titles[] = $failed_p->get_title();
			}
			
			$note = "Failed to purchase on demand product(s): " . implode( ',', $titles ) . '.';
			$order->add_order_note( $note );
			$order->save();
			
			$this->send_on_demand_failed_email( $titles, $order_id );
		}			
	}
	
	public function reduce_voucher_stock( $order_id, $force = false ) {
					
		$this->buy_stock_on_demand_orders( $order_id );
		$order          = wc_get_order( $order_id );			
		//$fraudlabs_raw  = get_post_meta( $order_id, '_fraudlabspro', TRUE ) ? get_post_meta( $order_id, '_fraudlabspro', TRUE ) : array();
		$fraudlabs_raw  = $order->get_meta( '_fraudlabspro' ) ? $order->get_meta( '_fraudlabspro' ) : array();
		$fraudlabs_data = array();
		
		// Fraudlabs 2.13.4+ and backwards compatibility
		if ( is_array( $fraudlabs_raw ) ) {
			$fraudlabs_data = $fraudlabs_raw;
		} else {
			$fraudlabs_data = json_decode( $fraudlabs_raw );
		}
		
		$items          = $order->get_items();
		$stock_db       = new PL_WCPT_Vouchers_DB();
		$block          = false;
		
		if ( defined( 'WC_FLP_DIR' ) && ( get_option( 'wc_settings_woocommerce-fraudlabs-pro_enabled' ) == 'yes' ) && !$fraudlabs_data ) {
			$block = true;
		}
		
		if( $fraudlabs_data && isset( $fraudlabs_data->fraudlabspro_status ) && $fraudlabs_data->fraudlabspro_status == 'APPROVE' ) {
			$block = false;
		}
		
		//$already_blocked 	 = get_post_meta( $order_id, 'pl_fraudlabs_blocked', TRUE );
		$already_blocked     = $order->get_meta( 'pl_fraudlabs_blocked' );
		$products_lack_stock = array();
												
		if ( !$block || $already_blocked ) {
			
			//delete_post_meta( $order_id, 'pl_run_release_cron' );
			$order->delete_meta_data( 'pl_run_release_cron' );
			$order->save();
			
			$product_db  = new PL_WCPT_Products_DB();
			$reserved_db = new PL_WCPT_Products_Reserved();
			
			foreach ( $items as $key => $item ) {
				$product_id   = $item->get_product_id();
				$quantity     = $item->get_quantity();
				$already_sent = wc_get_order_item_meta( $key, '_pl_voucher_to_user', true );
				$pt_product   = get_post_meta( $product_id, '_pt_product', TRUE );
				if ( $pt_product && !$already_sent ) {
					
					$base_stock  = $this->get_available_base_stock( $product_id );
					$reserved    = $reserved_db->calculate_reserved_stock( $product_id, $order_id ); 
					$local_stock = $base_stock - $reserved;
					
					if ( $local_stock < $quantity ) {
						$products_lack_stock[] = $product_id;
					}
				}
			}
			
			if ( $products_lack_stock ) {
				
				$this->send_release_vouchers_failed_email( $products_lack_stock, $order_id );
						
				$titles = array();
				foreach ( $products_lack_stock as $product_lack ) {
					$product_obj = wc_get_product( $product_lack );
					$titles[] = $product_obj->get_title();
				}
				
				$note = "There is not enough stock to release codes for this product(s): " . implode( ',', $titles ) . '.';
				$order->add_order_note( $note );
				
				$release_status = ( get_option( 'pl_wcpt_api_code_release' ) ) ? get_option( 'pl_wcpt_api_code_release' ) : 'processing';
				if ( $release_status == 'processing' ) {
					$order->set_status( 'on-hold' );
				}
				else {
					$order->set_status( 'processing' );
				}
				
				$order->save();
				
				// Prevent order emails
				//update_post_meta( $order_id, 'pl_pt_block_emails', true );
				$order->update_meta_data( 'pl_pt_block_emails', TRUE );
				$order->save();
			}
			else {
				
				$released = false;
										
				//delete_post_meta( $order_id, 'pl_fraudlabs_blocked' );
				$order->delete_meta_data( 'pl_fraudlabs_blocked' );
				$order->save();
				
				foreach ( $items as $key => $item ) {
					
					$product_id = $item->get_product_id();
					$quantity   = $item->get_quantity();
					$pt_product = get_post_meta( $product_id, '_pt_product', TRUE );
					if ( $pt_product ) {
													
						$product_start = get_post_meta( $product_id, '_pt_product_start', TRUE );
						if ( $product_start ) {
							$date_created = $order->get_date_created();
							$utc_offset   = $date_created->getOffset();
							$order_date   = $date_created->date( 'Y-m-d H:i:s' );
							$product_date = date( 'Y-m-d H:i:s', strtotime( $product_start ) + $utc_offset );
															
							// Prevent voucher releases if order os older than the pt product
							if ( $product_date && $product_date > $order_date ) {
								continue;
							}
						}
						
						$already_sent = wc_get_order_item_meta( $key, '_pl_voucher_to_user', true );
						if ( !$already_sent ) {
							$result = $stock_db->set_vouchers_as_sold( $pt_product, $quantity, $order_id );
							wc_update_order_item_meta( $key, '_pl_voucher_to_user', true );
							
							$vouchers = $stock_db->get_vouchers_by_order_product( $order_id, $pt_product );
							$pins    = array();
							$serials = array();
							$expires = array();
							if ( $vouchers && is_array( $vouchers ) ) {
								foreach ( $vouchers as $voucher ) {
									$pins[]     = $voucher->voucher_pin;
									$serials[]  = $voucher->voucher_serial;
									$expires[]  = $voucher->expires;
								}
							}
							if ( $pins ) {
								wc_update_order_item_meta( $key, '_pl_voucher_pins', implode( ',', $pins ) );
							}
							if ( $serials ) {
								wc_update_order_item_meta( $key, '_pl_voucher_serials', implode( ',', $serials ) );
							}
							if ( $expires ) {
								wc_update_order_item_meta( $key, '_pl_voucher_expires', implode( ',', $expires ) );
							}
				
							$pt_product_obj = $product_db->get_product( $pt_product );
							$product_name   = ( $pt_product_obj && $pt_product_obj->product_name ) ? $pt_product_obj->product_name : '';
							
							if ( !is_checkout() ) {	
								$this->plugin_instance->auto_purchase_product( $product_id );
							} else {
								
								wp_schedule_single_event( time() + 5, 'pl_purchase_product_after_order', array( $product_id ) );
							}
							
							$url  = get_site_url() . "/wp-admin/admin.php?page=pl-paythem-menu-view-stock&pl-search-term-top=$product_name&pl-order-id=$order_id";
							$here = "<a target='_blank' href='$url'>View</a>";
							wc_update_order_item_meta( $key, '_pl_vouchers_url', $here );
							$released = true;
						} else {
							$released = true;
						}
					}
				}
				
				if ( $released ) {
					//update_post_meta( $order_id, 'pl_released_vouchers', 1 );
					$order->update_meta_data( 'pl_released_vouchers', 1 );
					$order->save();
				}
			}
		} else {
			
			//update_post_meta( $order_id, 'pl_fraudlabs_blocked', 1 );
			//update_post_meta( $order_id, 'pl_run_release_cron', 1 );
			
			$order->update_meta_data( 'pl_fraudlabs_blocked', 1 );						
			$order->update_meta_data( 'pl_run_release_cron', 1 );
			$order->save();
		}
	}
	
	public function send_on_demand_failed_email( $product_titles, $order_id ) {
			
		$order_link = get_edit_post_link( $order_id );
		$order_url  = "<a href='$order_link'>#$order_id</a>";
		$message  = __( 'There was a problem while purchasing on-demand products for order' ) . ' ' . $order_url . ':';		
		if ( $product_titles ) {
			$message .= '<ul>';
			foreach ( $product_titles as $product_title ) {
				$message .= "<li>$product_title</li>";
			}
			$message .= '</ul>';
		}
		$heading = __( 'Failed to Purchase on demand product' );
		$subject = __( 'Failed to Purchase on demand product' );
		$to		 = get_option('admin_email');;
		$this->plugin_instance->send_paythem_email( $message, $heading, $subject, $to );
	}
	
	public function get_available_base_stock( $product_id ) {
			
		$product 		 = wc_get_product( $product_id );
		$total_stock     = $product->get_stock_quantity();		
		$base_stock      = get_post_meta( $product_id, 'pl_pt_base_stock', TRUE );
		return $base_stock;
	}
	
	public function send_release_vouchers_failed_email( $product_ids, $order_id ) {
		
		$message = __( 'There is not enough stock to release vouchers for order' ) . ' ' . "#$order_id" . '.';
		$heading = __( 'Failed to release vouchers' );
		$subject = __( 'Failed to release vouchers' );
		$to		 = get_option('admin_email');;
		$this->plugin_instance->send_paythem_email( $message, $heading, $subject, $to );
	}
}