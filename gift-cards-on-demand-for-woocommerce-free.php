<?php
/*
Plugin Name: Gift Cards On Demand - Free
Description: This plugin adds a connection between WooCommerce and PayThem.
Version: 3.0
Author: PayThem.net
Text Domain: gift-cards-on-demand-free
Author URI: https://paythem.net
License: GPLv2
*/

if ( ! class_exists( 'pl_wcpt_extention_free' ) ) {

	class pl_wcpt_extention_free {

		public $product_stock_updated = array();
		public static $instance;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			 return self::$instance;
		}

		public function __construct() {

			// Add needed files
			add_action( 'plugins_loaded', array( $this, 'includes' ) );
		}

		public function settings_notice() {

			$notice = __( 'PayThem requires initial configuration. You can set it up through Paythem>Settings.', 'gift-cards-on-demand-free' );
			echo "<div class='updated'><p><strong>" . esc_attr( $notice ) . "</strong></p></div>";
		}

		public function paythem_already_active_notice() {

			$notice = __( 'There are multiple version of Paythem active. Please deactivate the older version or the free version if you are using PRO.', 'gift-cards-on-demand-free' );
			echo "<div class='error'><p><strong>" . esc_attr( $notice ) . "</strong></p></div>";
		}
		
		public function update_product_stock_status( $product_id, $stock_status ) {
			update_post_meta( $product_id, '_stock_status', $stock_status );

			global $wpdb;
			$result = $wpdb->update( $wpdb->wc_product_meta_lookup, array( 'stock_status' => $stock_status ), array( 'product_id' => $product_id ) );
		}

		public function get_wallet_amount( $force ) {

			$wallet = ! $force ? get_transient( 'pl_wcpt_seller_wallet' ) : false;
			if ( ! $wallet ) {
				$pt_api           = new PL_WCPT_API();
				$account_ballance = $pt_api->get_account_ballance();

				$wallet = isset( $account_ballance['RESELLER_Balance'] ) ? $account_ballance['RESELLER_Balance'] : 0;
				if ( $wallet ) {

					// Save wallet value for 1 hour
					set_transient( 'pl_wcpt_seller_wallet', $wallet, 3600 );
				}
			}
			return $wallet;
		}

		public function add_vouchers_list_to_inventory( $product_id ) {

			global $post;
			$product_id = $post->ID;
			$pt_product = get_post_meta( $product_id, '_pt_product', true );
			if ( $pt_product ) {
				$stock_db       = new PL_WCPT_Vouchers_DB();
				$vouchers       = $stock_db->get_active_vouchers_by_product_id( $pt_product );
				$expires_exists = true;

				foreach ( $vouchers as $voucher ) {
					if ( isset( $voucher->expires ) && $voucher->expires ) {
						$expires_exists = true;
					}
				}

				if ( $vouchers ) {
					$vouchers_table  = '<table class="widefat pl_voucher_table">';
					$vouchers_table .= '<tr>
									    <th>PIN</th>
									    <th>Serial</th>';

					if ( $expires_exists ) {
						$vouchers_table .= '<th>Expires</th>';
					}

					$vouchers_table .= '</tr>';

					foreach ( $vouchers as $voucher ) {
						$vouchers_table .= "<tr>
									    <td>$voucher->voucher_pin</td>
									    <td>$voucher->voucher_serial</td> 
									    <td>$voucher->expires</td>
									  </tr>";
					}

					$vouchers_table .= '</table>';
					echo '<p class="form-field"><label>Available Vouchers:</label></p>' . wp_kses_post( $vouchers_table );
				} else {
					echo '<p class="form-field"><label>Available Vouchers:</label>No vouchers available.</p>';
				}
			}
		}

		public function update_product_data() {

			global $pagenow;

			if ( is_admin() && $pagenow == 'post.php' && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {

				$post_id = isset( $_GET['post'] ) ? sanitize_text_field( $_GET['post'] ) : 0;
				if ( $post_id ) {

					$post = get_post( $post_id );
					if ( $post->post_type == 'product' ) {
						$this->update_product_stock( $post_id );
					}
				}
			}
		}

		public function update_product_stock( $post_id ) {

			$pt_product = get_post_meta( $post_id, '_pt_product', true );
			if ( $pt_product ) {

				$pt_api = new PL_WCPT_API();
				$stock  = $pt_api->get_product_stock( $pt_product );
				if ( $stock >= 0 ) {

					$products_db = new PL_WCPT_Products_DB();
					$products_db->update_stock( $pt_product, $stock );
				}
			}
		}

		public function auto_purchase_product( $product_id ) {

			$product_obj = wc_get_product( $product_id );
			if ( $product_obj ) {

				$stock_management = get_post_meta( $product_id, '_pt_stock_management', true );
				$products_db      = new PL_WCPT_Products_DB();
				$stock            = $product_obj->get_stock_quantity();
				$min_stock        = get_post_meta( $product_id, '_pt_stock_level', true );

				if ( ! $stock_management && $min_stock && $min_stock > $stock ) {

					$difference = $min_stock - $stock;
					$this->update_product_stock( $product_id );
					$pt_product = get_post_meta( $product_id, '_pt_product', true );

					if ( ! $products_db->is_product_discontinued( $pt_product ) ) {

						$available_stock = $products_db->get_product_stock( $pt_product );
						if ( $available_stock ) {
							$quantity = ( $difference <= $available_stock ) ? $difference : $available_stock;

							$result = $this->purchase_paythem_product( $pt_product, $quantity, $product_id );
							do_action( 'pl_wcpt_low_wallet_email' );
						}
					}
				}
			}
		}

		public function purchase_paythem_product( $pt_product_id, $quantity, $product_id ) {

			$result  = false;
			$product = wc_get_product( $product_id );
			if ( $product ) {

				$status = $product->get_status();
				if ( $status == 'publish' ) {
					$pt_api             = new PL_WCPT_API();
					$result             = $pt_api->purchase_products( $pt_product_id, $quantity );
					$highest_sell_price = 0;

					if ( $result && is_array( $result ) ) {

						$this->get_wallet_amount( true );
						$balance = get_transient( 'pl_wcpt_seller_wallet' ) ? get_transient( 'pl_wcpt_seller_wallet' ) : null;

						$transactions_db = new PL_WCPT_Transactions_DB();
						$vouchers_db     = new PL_WCPT_Vouchers_DB();
						$transactions    = array();
						$vouchers        = isset( $result['VOUCHERS'] ) ? $result['VOUCHERS'] : array();
						foreach ( $vouchers as $voucher ) {
							$transactions[] = array(
								'TRANSACTION_ID'        => $result['TRANSACTION_ID'],
								'TRANSACTION_VOUCHER_QUANTITY' => $result['TRANSACTION_VOUCHER_QUANTITY'],
								'TRANSACTION_DATE'      => $result['TRANSACTION_DATE'],
								'TRANSACTION_USD_VALUE' => $result['TRANSACTION_VALUE'],
								'TRANSACTION_CurrentStatus' => 'Voucher purchase',
								'TRANSACTION_AccountBalance' => $balance,
								'OEM_ID'                => $result['OEM_ID'],
								'OEM_Name'              => $result['OEM_Name'],
								'OEM_BRAND_ID'          => $result['OEM_BRAND_ID'],
								'OEM_BRAND_Name'        => $result['OEM_BRAND_Name'],
								'OEM_VOUCHER_TransactionStatus' => 'Sold',
								'OEM_VOUCHER_ID'        => $voucher['OEM_VOUCHER_ID'],
								'OEM_VOUCHER_PIN'       => $voucher['OEM_VOUCHER_PIN'],
								'OEM_VOUCHER_SERIAL'    => $voucher['OEM_VOUCHER_SERIAL'],
								'OEM_VOUCHER_EXPIRATION_DATE' => $voucher['OEM_VOUCHER_EXPIRATION_DATE'],
								'OEM_PRODUCT_ID'        => $result['OEM_PRODUCT_ID'],
								'OEM_PRODUCT_Name'      => $result['OEM_PRODUCT_Name'],
								'OEM_PRODUCT_SellPrice' => $result['OEM_PRODUCT_SellPrice'],
								'OEM_VOUCHER_SALES_ID'  => 0,
							);

							if ( $result['OEM_PRODUCT_SellPrice'] > $highest_sell_price ) {
								$highest_sell_price = $result['OEM_PRODUCT_SellPrice'];
							}
						}

						if ( $transactions ) {
							$transactions_db->add_transactions( $transactions );
							$vouchers_db->add_vouchers( $transactions );
						}

						do_action( 'pl_wcpt_mismatch_email', $product, $highest_sell_price );
					}
				}
			}

			return $result;
		}

		public function purchase_paythem_product_ajax() {

			if ( isset( $_REQUEST ) ) {

				$pt_product_id = isset( $_REQUEST['pt_product_id'] ) ? intval( sanitize_text_field( $_REQUEST['pt_product_id'] ) ) : '';
				$quantity      = isset( $_REQUEST['quantity'] ) ? intval( sanitize_text_field( $_REQUEST['quantity'] ) ) : '';
				$product_id    = isset( $_REQUEST['product_id'] ) ? intval( sanitize_text_field( $_REQUEST['product_id'] ) ) : '';

				$result = $this->purchase_paythem_product( $pt_product_id, $quantity, $product_id );

				do_action( 'pl_wcpt_low_wallet_email' );

				$result_bool = $result ? true : false;
				$message     = $result_bool ? __( 'Products purchased with success', 'gift-cards-on-demand-free' ) : __( 'Error purchasing product', 'gift-cards-on-demand-free' );
				echo wp_json_encode(
					array(
						'result'  => $result_bool,
						'message' => $message,
					)
				);
			}
			die();
		}

		public function add_buy_product_metabox() {

			$type = 'product';
			add_meta_box( 'pl-paythem-product-metabox', __( 'Purchase on Paythem', 'gift-cards-on-demand-free' ), array( $this, 'buy_product_metabox' ), $type, 'side', 'low' );
		}

		public function buy_product_metabox( $post ) {
			
			$allowed_html 	= array(
			    'input' => array(
			        'type'      => array(),
			        'name'      => array(),
			        'id'		=> array(),
			        'value'     => array(),
			        'checked'   => array(),
			        'min'		=> array(),
			        'max'		=> array(),
			        'step'		=> array(),
			    ),
		    );
			$product_id     = $post->ID;
			$product_db     = new PL_WCPT_Products_DB();
			$pt_product     = get_post_meta( $product_id, '_pt_product', true );
			$pt_product_obj = $product_db->get_product( $pt_product );
			if ( $pt_product && $pt_product_obj ) {
				$sell_price      = $pt_product_obj->sell_price;
				$stock_available = $pt_product_obj->stock_available;
				$limit           = get_option( 'pl_wcpt_api_purchase_limit' ) ? get_option( 'pl_wcpt_api_purchase_limit' ) : 20;
				$max             = ( $stock_available <= $limit ) ? $stock_available : $limit;
				$max             = ( $max >= 1 ) ? $max : 1;
				$stock_input     = '<input type="number" min="1" step="1" value="1" max="' . esc_attr( $max ) . '" id="pl_paythem_purchase_stock_qtt" name="pl_paythem_purchase_stock_qtt">';

				echo "<p><b>Stock available</b>: " . esc_attr( $stock_available ) . "</p>";
				echo "<p><b>Quantity</b>: " . wp_kses( $stock_input, $allowed_html ) . "</p>";
				echo "<p class='pl_purchase_button'><span class='button' data-pt-product_id='" . esc_attr( $pt_product ) . "' data-product_id='" . esc_attr( $product_id ) . "' id='pl_paythem_purchase_stock'>Purchase</span></p>";
			} else {
				echo 'Associate product with PayThem before purchasing.';
			}
		}

		public function db_install() {

			include_once PL_WCPT_DIR_PATH . 'includes/class.install.php';
			new PL_WCPT_Dabatase_Install();

			$currency = get_option( 'pl_wcpt_api_account_currency' );
			if ( ! $currency ) {
				$pl_wcpt_api_username = get_option( 'pl_wcpt_api_username' );
				$pl_wcpt_api_password = get_option( 'pl_wcpt_api_password' );

				if ( $pl_wcpt_api_username && $pl_wcpt_api_password ) {
					$pt_api           = new PL_WCPT_API();
					$account_ballance = $pt_api->get_account_ballance();
				}
			}
		}

		public function woocommerce_error_activation_notice() {
			$notice = __( 'You need WooCommerce active in order to use WooCommerce PayThem extention - Free.', 'gift-cards-on-demand-free' );
			echo "<div class='error'><p><strong>" . esc_attr( $notice ) . "</strong></p></div>";
		}

		public function setup_constants() {

			global $wpdb;

			if ( ! defined( 'PL_WCPT_DIR_PATH' ) ) {
				define( 'PL_WCPT_DIR_PATH', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'PL_WCPT_DIR_URL' ) ) {
				define( 'PL_WCPT_DIR_URL', plugin_dir_url( __FILE__ ) );
			}

			if ( ! defined( 'PL_WCPT_PLUGIN_FILE' ) ) {
				define( 'PL_WCPT_PLUGIN_FILE', __FILE__ );
			}

			if ( ! defined( 'PL_WCPT_PLUGIN_VERSION' ) ) {
				define( 'PL_WCPT_PLUGIN_VERSION', '3.0' );
			}
		}

		public function includes() {

			if ( class_exists( 'pl_wcpt_extention_pro' ) || class_exists( 'pl_wcpt_extention' ) ) {
				add_action( 'admin_notices', array( $this, 'paythem_already_active_notice' ) );
				return;
			} elseif ( ! class_exists( 'WooCommerce' ) ) {
				add_action( 'admin_notices', array( $this, 'woocommerce_error_activation_notice' ) );
				return;
			}

			$this->setup_constants();

			include_once PL_WCPT_DIR_PATH . 'vendor/autoload.php';
			include_once PL_WCPT_DIR_PATH . 'includes/class.pt_api.php';
			include_once PL_WCPT_DIR_PATH . 'includes/class-list-table.php';
			include_once PL_WCPT_DIR_PATH . 'includes/class-reports-list-table.php';
			include_once PL_WCPT_DIR_PATH . 'includes/class-stock-list-table.php';
			include_once PL_WCPT_DIR_PATH . 'includes/class-reserved-stock-list-table.php';
			include_once PL_WCPT_DIR_PATH . 'includes/class-export-stock.php';
			include_once PL_WCPT_DIR_PATH . 'includes/class-export-reports.php';
			include_once PL_WCPT_DIR_PATH . 'includes/class-products-reserved.php';

			include_once PL_WCPT_DIR_PATH . 'includes/database/class-database.php';
			include_once PL_WCPT_DIR_PATH . 'includes/database/class-oem-database.php';
			include_once PL_WCPT_DIR_PATH . 'includes/database/class-products-database.php';
			include_once PL_WCPT_DIR_PATH . 'includes/database/class-brands-database.php';
			include_once PL_WCPT_DIR_PATH . 'includes/database/class-vouchers-database.php';
			include_once PL_WCPT_DIR_PATH . 'includes/database/class-transactions-database.php';
			include_once PL_WCPT_DIR_PATH . 'includes/database/class-financial-database.php';

			// Show warning when settings are not setup
			if ( ! get_option( 'pl_wcpt_api_username' ) ) {
				add_action( 'admin_notices', array( $this, 'settings_notice' ) );
			}
			
			// Database install
			add_action( 'init', array( $this, 'db_install' ) );

			// Enqueue backend scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

			// Add buy product metabox
			add_action( 'admin_menu', array( $this, 'add_buy_product_metabox' ) );

			// Purchase PayThem products via ajax
			add_action( 'wp_ajax_pl_pt_purchase_product', array( $this, 'purchase_paythem_product_ajax' ) );

			// Update product when entering product page
			add_action( 'init', array( $this, 'update_product_data' ) );

			// Add vouchers to inventory page
			add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_vouchers_list_to_inventory' ) );

			// Add site title to email footers
			add_filter( 'woocommerce_email_footer_text', array( $this, 'set_site_title' ) );

			// to block stock reduce & restore
			add_filter( 'woocommerce_payment_complete_reduce_order_stock', array( $this, 'block_stock_for_pt_orders' ), 999, 2 );
			add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'block_stock_for_pt_orders' ), 999, 2 );
			add_filter( 'woocommerce_restore_order_stock_quantity', array( $this, 'block_stock_for_pt_orders' ), 999, 2 );

			add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( $this, 'hide_vouchers_data_order_edit' ), 10, 2 );

			// Remove woocommerce stock functions
			add_filter( 'woocommerce_payment_complete_reduce_order_stock', array( $this, 'remove_woocommerce_functions' ), 1, 1 );
			add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'remove_woocommerce_functions' ), 1, 1 );
			add_filter( 'woocommerce_restore_order_stock_quantity', array( $this, 'remove_woocommerce_functions' ), 1, 1 );
			add_filter( 'woocommerce_can_restore_order_stock', array( $this, 'remove_woocommerce_functions' ), 1, 1 );

			// Remove stock order notes
			add_action( 'woocommerce_order_note_added', array( $this, 'remove_stock_order_notes' ), 10, 2 );

			// Block emails from orders that failed to release voucher
			add_filter( 'woocommerce_email_enabled_customer_completed_order', array( $this, 'disable_order_emails' ), 10, 2 );
			add_filter( 'woocommerce_email_enabled_customer_processing_order', array( $this, 'disable_order_emails' ), 10, 2 );

			// Process stock reserved features
			new PL_WCPT_Products_Reserved();

			// Add account data to order in woocommerce
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_account_info_to_order' ), 10, 1 );

			// Get OER Rate via ajax
			add_action( 'wp_ajax_pl_pt_get_oer_rate', array( $this, 'get_oer_rate_ajax' ) );

			// Filter on demand stock for discontinued products
			add_filter( 'get_post_metadata', array( $this, 'filter_demand_stock_discontinued' ), 100, 4 );

			// Update database if needed
			add_action( 'pl_database_updated', array( $this, 'maybe_update_database' ) );

			// Delete used vouchers on deleted orders
			add_action( 'delete_post', array( $this, 'maybe_delete_vouchers' ), 10, 2 );
			add_action( 'wp_trash_post', array( $this, 'maybe_trash_vouchers' ), 10, 1 );

			// Remove file after email was sent
			add_action( 'woocommerce_email_sent', array( $this, 'maybe_delete_file' ), 10, 3 );

			// Include plugin features
			$this->include_features();
		}

		public function include_features() {
			include_once PL_WCPT_DIR_PATH . 'includes/class-features-free.php';
			new PL_WCPT_Features();
		}

		public function maybe_delete_file( $return, $email_id, $email ) {

			$release_status = ( get_option( 'pl_wcpt_api_code_release' ) ) ? get_option( 'pl_wcpt_api_code_release' ) : 'processing';

			if ( ( $release_status == 'processing' && 'customer_processing_order' !== $email_id ) || ( $release_status == 'completed' && 'customer_completed_order' !== $email_id ) ) {
				return false;
			}

			if ( $return ) {
				$order = $email->object;
				if ( $order ) {
					$order_id = $order->get_id();
					// $file_url = get_post_meta( $order_id, '_pl_file_url', TRUE );
					$file_url = $order->get_meta( '_pl_file_url' );

					if ( $file_url && file_exists( $file_url ) ) {
						unlink( $file_url );
					}
				}
			}
		}

		public function maybe_trash_vouchers( $post_id ) {

			$post = get_post( $post_id );
			$this->maybe_delete_vouchers( $post_id, $post );
		}

		public function maybe_delete_vouchers( $post_id, $post ) {

			if ( $post && $post->post_type == 'shop_order' ) {

				$stock_db = new PL_WCPT_Vouchers_DB();
				$vouchers = $stock_db->get_vouchers_by_order( $post_id );
				if ( $vouchers ) {
					$stock_db->delete_vouchers_from_order( $post_id );
				}
			}
		}

		public function maybe_update_database( $db_version ) {

			if ( $db_version == 1.3 ) {

				delete_option( 'pl_wcpt_transaction_last' );
			} else if ( $db_version == 2.0 ) {

				global $wpdb;
				$table_products = $wpdb->prefix . 'wcpt_products';

				// Add discontinued column if not exists
				$query  = $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->prefix}wcpt_products LIKE %s", 'discontinued' );
				$exists = $wpdb->query( $query );
				if ( ! $exists ) {
					$query_discontinued = $wpdb->prepare( "ALTER TABLE {$wpdb->prefix}wcpt_product ADD COLUMN discontinued BIT DEFAULT 0 AFTER image_url;" );
					$wpdb->query( $query_discontinued );
				}
			}
			
			$this->maybe_migrate_settings();
		}
		
		public function maybe_migrate_settings() {
			
			if ( !get_option( 'pl_wcpt_api_purchase_limit' ) ) {
				$per_product_limit = get_option( 'pl_api_purchase_limit' );
				if ( $per_product_limit ) {
					update_option( 'pl_wcpt_api_purchase_limit', sanitize_text_field( $per_product_limit ) );
				}
			}
			
			if ( !get_option( 'pl_wcpt_api_username' ) ) {
				$username = get_option( 'pl_api_username' );
				if ( $username ) {
					update_option( 'pl_wcpt_api_username', sanitize_text_field( $username ) );
				}
			}
			
			if ( !get_option( 'pl_wcpt_api_last_currency' ) ) {
				$api_last_currency = get_option( 'pl_api_last_currency' );
				if ( $api_last_currency ) {
					update_option( 'pl_wcpt_api_last_currency', sanitize_text_field( $api_last_currency ) );
				}
			}
			
			if ( !get_option( 'pl_wcpt_api_password' ) ) {
				$api_password = get_option( 'pl_api_password' );
				if ( $api_password ) {
					update_option( 'pl_wcpt_api_password', sanitize_text_field( $api_password ) );
				}
			}
			
			if ( !get_option( 'pl_wcpt_api_public_key' ) ) {
				$api_public_key = get_option( 'pl_api_public_key' );
				if ( $api_public_key ) {
					update_option( 'pl_wcpt_api_public_key', sanitize_text_field( $api_public_key ) );
				}
			}
			
			if ( !get_option( 'pl_wcpt_api_private_key' ) ) {
				$api_private_key = get_option( 'pl_api_private_key' );
				if ( $api_private_key ) {
					update_option( 'pl_wcpt_api_private_key', sanitize_text_field( $api_private_key ) );
				}
			}
			
			if ( !get_option( 'pl_wcpt_api_environment' ) ) {
				$api_environment = get_option( 'pl_api_environment' );
				if ( $api_environment ) {
					update_option( 'pl_wcpt_api_environment', sanitize_text_field( $api_environment ) );
				}
			}
			
			if ( !get_option( 'pl_wcpt_api_currency_conversion_value' ) ) {
				$api_currency_conversion_value = get_option( 'pl_api_currency_conversion_value' );
				if ( $api_currency_conversion_value ) {
					update_option( 'pl_wcpt_api_currency_conversion_value', sanitize_text_field( $api_currency_conversion_value ) );
				}
			}
			
			if ( !get_option( 'pl_wcpt_api_code_release' ) ) {
				$api_code_release = get_option( 'pl_api_code_release' );
				if ( $api_code_release ) {
					update_option( 'pl_wcpt_api_code_release', sanitize_text_field( $api_code_release ) );
				}
			}
			
			if ( !get_option( 'pl_wcpt_api_code_release_regular_email' ) ) {
				$api_code_release_regular = get_option( 'pl_api_code_release_regular_email' );
				if ( $api_code_release_regular ) {
					update_option( 'pl_wcpt_api_code_release_regular_email', sanitize_text_field( $api_code_release_regular ) );
				}
			}
			
			if ( !get_option( 'pl_wcpt_api_currency_conversion' ) ) {
				$api_currency_conversion = get_option( 'pl_api_currency_conversion' );
				if ( $api_currency_conversion ) {
					update_option( 'pl_wcpt_api_currency_conversion', sanitize_text_field( $api_currency_conversion ) );
				}
			}
		}

		public function filter_demand_stock_discontinued( $metadata, $object_id, $meta_key, $single ) {

			if ( $meta_key == '_pt_stock_management' ) {

				remove_filter( 'get_post_metadata', array( $this, 'filter_demand_stock_discontinued' ), 100 );
				$current_meta  = get_post_meta( $object_id, '_pt_stock_management', true );
				$pt_product_id = get_post_meta( $object_id, '_pt_product', true );

				if ( $current_meta ) {

					$product_db = new PL_WCPT_Products_DB();
					if ( $product_db->is_product_discontinued( $pt_product_id ) ) {

						update_post_meta( $object_id, '_pt_stock_management', '' );
						return '';
					}
				}

				add_filter( 'get_post_metadata', array( $this, 'filter_demand_stock_discontinued' ), 100, 4 );
			}

			return $metadata;
		}

		public function get_oer_rate_ajax() {

			if ( isset( $_REQUEST ) ) {
				echo wp_json_encode();
			}
			die();
		}

		public function add_account_info_to_order( $order_id ) {

			$order       = wc_get_order( $order_id );
			$fingerprint = PL_WCPT_Products_Reserved::get_user_account_fingerprint();
			if ( $fingerprint && $order ) {
				// update_post_meta( $order_id, '_pt_account_fingerprint', $fingerprint );
				$order->update_meta_data( '_pt_account_fingerprint', $fingerprint );
				$order->save();
			}
		}

		public function disable_order_emails( $enabled, $order ) {

			if ( $order && is_object( $order ) ) {
				/*
				if ( get_post_meta( $order->get_id(), 'pl_pt_block_emails', TRUE ) ) {
					return false;
				}*/

				if ( $order->get_meta( 'pl_pt_block_emails' ) ) {
					return false;
				}
			}

			return $enabled;
		}

		public function needs_stock_on_demand_orders( $order_id ) {

			$order = wc_get_order( $order_id );
			$items = $order->get_items();

			$purchased = array();
			$failed    = array();

			foreach ( $items as $item ) {

				$product_id = $item->get_product_id();
				$quantity   = $item->get_quantity();
				$pt_product = get_post_meta( $product_id, '_pt_product', true );
				if ( $pt_product ) {
					$stock_management = get_post_meta( $product_id, '_pt_stock_management', true );
					if ( $stock_management == 'no' ) {

						$reserved_db    = new PL_WCPT_Products_Reserved();
						$reserved_stock = $reserved_db->calculate_reserved_stock( $product_id, $order_id );

						$product_obj     = wc_get_product( $product_id );
						$stock           = $product_obj->get_stock_quantity();
						$on_demand_stock = get_post_meta( $product_id, 'pl_pt_on_demand_stock', true );
						$base_stock      = get_post_meta( $product_id, 'pl_pt_base_stock', true );
						$at_hand_stock   = $base_stock - $reserved_stock;

						if ( $at_hand_stock < $quantity ) {

							wp_schedule_single_event( time() + 15, 'pl_get_order_on_demand_stock', array( $order_id ) );
							return true;
						}
					}
				}
			}

			return false;
		}

		public function remove_woocommerce_functions( $can ) {

			return false;
		}

		public function hide_vouchers_data_order_edit( $formatted_meta, $item ) {

			if ( is_admin() ) {
				foreach ( $formatted_meta as $key => $meta ) {

					if ( in_array( $meta->key, array( '_pl_voucher_pins', '_pl_voucher_serials', '_pl_voucher_expires', '_pl_voucher_to_user' ) ) ) {
						unset( $formatted_meta[ $key ] );
					} elseif ( $meta->key == '_pl_vouchers_url' ) {
						$formatted_meta[ $key ]->display_key = __( 'License codes', 'gift-cards-on-demand-free' );
					}
				}
			}

			return $formatted_meta;
		}

		public function remove_stock_order_notes( $comment_id, $order ) {

			$comment = get_comment( $comment_id );
			if ( $comment && $comment->comment_content && ( strpos( $comment->comment_content, __( 'Stock levels', 'gift-cards-on-demand-free' ) ) !== false || strpos( $comment->comment_content, __( 'stock reduced', 'gift-cards-on-demand-free' ) ) !== false ) ) {
				wp_delete_comment( $comment_id );
			}
		}

		public function block_stock_for_pt_orders( $allow, $order ) {

			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
			}

			$items = $order->get_items();

			foreach ( $items as $key => $item ) {

				$product_id = $item->get_product_id();
				$quantity   = $item->get_quantity();
				$pt_product = get_post_meta( $product_id, '_pt_product', true );
				if ( ! $pt_product ) {

					return true;
				}
			}

			return false;
		}

		public function auto_purchases() {

			$args = array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_pt_product',
						'compare' => 'EXISTS',
					),

					array(
						'key'     => '_pt_product',
						'value'   => '',
						'compare' => '!=',
					),
				),
			);

			$products = get_posts( $args );
			$purchase = false;

			$products_db = new PL_WCPT_Products_DB();

			foreach ( $products as $product ) {

				$product_obj = wc_get_product( $product );
				$stock       = $product_obj->get_stock_quantity();
				$min_stock   = get_post_meta( $product, '_pt_stock_level', true );
				if ( $min_stock && $min_stock > $stock ) {

					$difference = $min_stock - $stock;
					$this->update_product_stock( $product );
					$pt_product      = get_post_meta( $product, '_pt_product', true );
					$available_stock = $products_db->get_product_stock( $pt_product );
					if ( $available_stock ) {
						$quantity = ( $difference <= $available_stock ) ? $difference : $available_stock;

						$purchase = true;
						$result   = $this->purchase_paythem_product( $pt_product, $quantity, $product );
					}
				}
			}

			if ( $purchase ) {
				do_action( 'pl_wcpt_low_wallet_email' );
			}
		}

		public function set_site_title( $text ) {

			$text = str_replace( '{site_title}', get_bloginfo(), $text );
			return $text;
		}

		public function send_paythem_email( $message, $heading, $subject, $to ) {

			if ( $message && $heading && $subject && $to ) {

				$email_heading = $heading;

				// Email Subject
				$email_subject = $subject;

				// Email Headers
				$headers[] = 'From: ' . get_option( 'woocommerce_email_from_name' ) . ' <' . get_option( 'woocommerce_email_from_address' ) . '>';
				add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

				$content = $message;

				// Email Message
				$email_message = nl2br( $content );

				// Email - Start
				ob_start();
				if ( function_exists( 'wc_get_template' ) ) {
					 wc_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
				} else {
					 woocommerce_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
				}

				echo esc_attr( $email_message );

				if ( function_exists( 'wc_get_template' ) ) {
					 wc_get_template( 'emails/email-footer.php', array() );
				} else {
					 woocommerce_get_template( 'emails/email-footer.php', array() );
				}

				// Email Message - End
				$email_message = ob_get_clean();

				// Send Email
				$status = wc_mail( $to, $email_subject, $email_message, $headers );
				remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
			}
		}

		public function set_html_content_type( $content_type ) {

			return 'text/html';
		}

		public function sync_only_products() {

			$products_db = new PL_WCPT_Products_DB();
			$pt_api      = new PL_WCPT_API();
			$products    = $pt_api->get_products();

			if ( $products ) {
				$products_db->add_products( $products );
			}
		}

		public function sync_products() {

			$oem_db      = new PL_WCPT_OEM_DB();
			$brands_db   = new PL_WCPT_Brands_DB();
			$products_db = new PL_WCPT_Products_DB();

			$pt_api = new PL_WCPT_API();

			$manufacturers = $pt_api->get_manufacturers();
			if ( $manufacturers ) {
				$oem_db->add_oems( $manufacturers );
			}

			$brands = $pt_api->get_brands();
			if ( $brands ) {
				$brands_db->add_brands( $brands );
			}

			$products = $pt_api->get_products();

			if ( $products ) {
				$products_db->add_products( $products );
			}

			do_action( 'pl_wcpt_check_price_updates' );
		}

		public function sync_transactions() {

			$now_date         = date( 'Y-m-d H:i:s', strtotime( '-12 hours' ) );
			$transaction_last = get_option( 'pl_wcpt_transaction_last' ) ? get_option( 'pl_wcpt_transaction_last' ) : $now_date;
			$fifteen_days_ago = date( 'Y-m-d H:i:s', strtotime( '-15 days' ) );
			$sixty_days_ago   = date( 'Y-m-d H:i:s', strtotime( '-60 days' ) );

			$now_gmt = gmdate( 'Y-m-d H:i:s' );

			$financial_last = $transaction_last;
			$sales_last     = $transaction_last;

			if ( $sales_last < $fifteen_days_ago ) {
				$sales_last = $fifteen_days_ago;
			}

			if ( $financial_last < $sixty_days_ago ) {
				$financial_last = $sixty_days_ago;
			}

			$transactions_db = new PL_WCPT_Transactions_DB();
			$vouchers_db     = new PL_WCPT_Vouchers_DB();
			$financial_db    = new PL_WCPT_Financial_DB();

			$pt_api = new PL_WCPT_API();

			$transactions = $pt_api->get_sales_history( $sales_last, $now_gmt );
			if ( $transactions ) {
				$transactions_db->add_transactions( $transactions );
				$vouchers_db->add_vouchers( $transactions );
			}

			$financial = $pt_api->get_financial_history( $financial_last, $now_gmt );

			if ( $financial ) {
				$financial_db->add_transactions( $financial );
			}

			$this->get_wallet_amount( true );
			$pt_api->get_purchase_limit();

			update_option( 'pl_wcpt_transaction_last', $now_date );
		}

		public function admin_scripts() {

			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'pl-wcpt-jquery-ui-smoothness', plugins_url( 'assets/css/smoothness/jquery-ui.css', PL_WCPT_PLUGIN_FILE ), array(), PL_WCPT_PLUGIN_VERSION, false );
			wp_enqueue_style( 'pl-wcpt-fa', plugins_url( 'assets/css/fontawesome.css', PL_WCPT_PLUGIN_FILE ), array(), PL_WCPT_PLUGIN_VERSION, false );
			wp_enqueue_script( 'pl-wcpt-admin', plugins_url( 'assets/js/admin.js', PL_WCPT_PLUGIN_FILE ), array( 'jquery' ), PL_WCPT_PLUGIN_VERSION, true );
			wp_enqueue_style( 'pl-wcpt-admin', plugins_url( 'assets/css/admin.css', PL_WCPT_PLUGIN_FILE ), array(), PL_WCPT_PLUGIN_VERSION, false );

			$products_db = new PL_WCPT_Products_DB();
			$products    = $products_db->get_products();

			$conversion_value  = ( get_option( 'pl_wcpt_api_currency_conversion_value' ) ) ? get_option( 'pl_wcpt_api_currency_conversion_value' ) : 1;
			$markup_val        = ( get_option( 'pl_wcpt_api_import_markup_val' ) ) ? get_option( 'pl_wcpt_api_import_markup_val' ) : 0;
			$products_formated = array();

			foreach ( $products as $product ) {

				$sell_price   = $product->sell_price;
				$unit_price   = $product->unit_price;
				$instructions = $product->instructions;

				$price         = round( $sell_price * $conversion_value, 2 );
				$product_price = $price;

				$product_price += $product_price * $markup_val / 100;

				$product_price = round( $product_price, 2 );

				$products_formated[ $product->product_id ] = array(
					'pt_price'      => $price,
					'product_price' => $product_price,
					'instructions'  => $instructions,
				);
			}

			$discontinued_all = $products_db->get_discontinued_product_ids();
			$discontinued     = array();
			if ( $discontinued_all ) {
				foreach ( $discontinued_all as $discontinued_item ) {
					$discontinued[ $discontinued_item ] = 1;
				}
			}

			$has_oer   = false;
			$max_items = get_option( 'pl_wcpt_api_purchase_limit' ) ? get_option( 'pl_wcpt_api_purchase_limit' ) : 20;

			wp_localize_script( 'pl-wcpt-admin', 'pl_pt_products', $products_formated );
			wp_localize_script( 'pl-wcpt-admin', 'pl_pt_options', array( 'has_oer'   => $has_oer, 'max_items' => $max_items ) );
			wp_localize_script( 'pl-wcpt-admin', 'pl_pt_discontinued_products', $discontinued );
		}
	}
}

if ( class_exists( 'pl_wcpt_extention_free' ) ) {

	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}

	pl_wcpt_extention_free::get_instance();
}
