<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly
}

class PL_WCPT_Features {

	private $plugin_instance = null;

	public function __construct() {

		// Add PayThem Menu
		add_action( 'admin_menu', array( $this, 'paythem_menu' ), 12 );

		// Include feature files
		$this->include_files();

		// Initialize plugin instance
		$this->plugin_instance = pl_wcpt_extention_free::get_instance();
		
		// Maybe update product prices
		add_action( 'pl_pt_updated_conversion_value', array( $this, 'check_needed_price_updates' ) );
		
		// Add action link for pro version
		add_filter( 'plugin_action_links_' . plugin_basename( PL_WCPT_PLUGIN_FILE ), array( $this, 'add_plugin_pro_link' ) );
		
		// Add pro version notice on settings
		add_action( 'admin_notices', array( $this, 'pro_settings_notice' ) );
		
		// Maybe fetch products if paythem products do not exist and user goes to product page
		add_action( 'init', array( $this, 'maybe_fetch_products' ) );	
	}
	
	public function maybe_fetch_products() {		
		if ( $this->is_product_edit_page() ) {
			$products_db = new PL_WCPT_Products_DB();
			if ( !$products_db->get_products() ) {
				$this->sync_products();
			}
		}
	}
	
	public function is_product_edit_page() {
		global $pagenow;
		$post_id 	= isset( $_GET['post'] ) 	  ? sanitize_text_field( $_GET['post'] )   	  : 0;
		$action  	= isset( $_GET['action'] ) 	  ? sanitize_text_field( $_GET['action'] ) 	  : '';
		$post_type  = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : '';
		
		if ( is_admin() && $post_id && $action == 'edit' ) {
			$post = get_post( $post_id );
			if ( $post && $post->post_type == 'product' ) {
				return true;
			}
		} else if ( is_admin() && $pagenow == 'post-new.php' && $post_type == 'product' ) {
			return true;
		}
			
		return false;
	}
	
	public function pro_settings_notice() {
		
		global $current_screen;
		if ( $current_screen && isset( $current_screen->parent_base ) && $current_screen->parent_base == 'pl-paythem-menu' ) {
			$url 		  = PL_WCPT_DIR_URL . '/assets/img/paythem_banner.png';
			$external_url = 'https://paythem.net/gift-card-plugin-woocommerce/pro/';
	  		echo '<div><a href="' . esc_url( $external_url ) . '" target="_blank"><img class="pl-pt-promo-banner" src="' . esc_url( $url ) . '" alt="PayThem Banner" width="600px"></a></div>';
  		}
	}
	
	public function add_plugin_pro_link( $actions ) {
		
		$actions[] = '<a target="_blank" href="https://paythem.net/gift-card-plugin-woocommerce/pro/">' . __( 'GO PRO', 'gift-cards-on-demand-free' ) . '</a>';
		return $actions;
	}

	public function include_files() {

		include_once PL_WCPT_DIR_PATH . 'includes/class-bulk-import-free.php';
		include_once PL_WCPT_DIR_PATH . 'includes/class-scheduled-actions-free.php';
		include_once PL_WCPT_DIR_PATH . 'includes/class-products-free.php';
		include_once PL_WCPT_DIR_PATH . 'includes/class-cart-free.php';
		include_once PL_WCPT_DIR_PATH . 'includes/class-notifications-free.php';
		include_once PL_WCPT_DIR_PATH . 'includes/class-vouchers-manager-free.php';
		include_once PL_WCPT_DIR_PATH . 'includes/class-upgrade-free.php';

		// Run scheduled actions
		new PL_WCPT_Scheduled_Actions();

		// Run product features
		new PL_WCPT_Products();

		// Run cart features
		new PL_WCPT_Cart();

		// Run notification features
		new PL_WCPT_Notifications();

		// Add voucher managers features
		new PL_WCPT_Vouchers_Manager();
		
		// Add upgrade features
		new PL_WCPT_Upgrade();
	}

	public function paythem_menu() {

		global $submenu;

		$current_user = wp_get_current_user();
		$roles        = isset( $current_user->roles ) ? $current_user->roles : array();

		if ( in_array( 'administrator', $roles ) ) {

			$icon = PL_WCPT_DIR_URL . 'assets/img/paythem_icon.png';

			add_menu_page( __( 'PayThem - Products', 'gift-cards-on-demand-free' ), __( 'PayThem', 'gift-cards-on-demand-free' ), 'manage_options', 'pl-paythem-menu', array( $this, 'pl_paythem_products' ), $icon, '50' );

			add_submenu_page( 'pl-paythem-menu', __( 'PayThem - Settings', 'gift-cards-on-demand-free' ), __( 'Settings', 'gift-cards-on-demand-free' ), 'manage_options', 'pl-paythem-menu-view-settings', array( $this, 'pl_paythem_settings' ) );

			add_submenu_page( 'pl-paythem-menu', __( 'PayThem - Stock', 'gift-cards-on-demand-free' ), __( 'Stock', 'gift-cards-on-demand-free' ), 'manage_options', 'pl-paythem-menu-view-stock', array( $this, 'pl_paythem_stock' ) );

			add_submenu_page( 'pl-paythem-menu', __( 'PayThem - Reserved Stock', 'gift-cards-on-demand-free' ), __( 'Reserved Stock', 'gift-cards-on-demand-free' ), 'manage_options', 'pl-paythem-menu-view-reserved-stock', array( $this, 'pl_paythem_reserved_stock' ) );

			add_submenu_page( 'pl-paythem-menu', __( 'PayThem - Transactions', 'gift-cards-on-demand-free' ), __( 'Transactions', 'gift-cards-on-demand-free' ), 'manage_options', 'pl-paythem-menu-view-reports', array( $this, 'pl_paythem_reports' ) );

			add_submenu_page( 'pl-paythem-menu', __( 'PayThem - Import', 'gift-cards-on-demand-free' ), __( 'Bulk Import', 'gift-cards-on-demand-free' ), 'manage_options', 'pl-paythem-menu-bulk-import', array( $this, 'pl_paythem_bulk_import' ) );

			add_submenu_page( null, __( 'Stock Export', 'gift-cards-on-demand-free' ), __( 'Stock Export', 'gift-cards-on-demand-free' ), 'manage_options', 'pl-paythem-stock-export', array( $this, 'pl_export_stock_page' ) );

			add_submenu_page( null, __( 'Reports Export', 'gift-cards-on-demand-free' ), __( 'Reports Export', 'gift-cards-on-demand-free' ), 'manage_options', 'pl-paythem-report-export', array( $this, 'pl_export_reports_page' ) );

			$submenu['pl-paythem-menu'][0][0] = __( 'Products', 'gift-cards-on-demand-free' );
		}
	}

	public function pl_paythem_products() {

		if ( ! isset( $_GET['paged'] ) && ! isset( $_GET['action'] ) && ! isset( $_GET['pl-search-term-top'] ) ) {
			$this->sync_products();
		}

		$pt_api           = new PL_WCPT_API();
		$account_ballance = $pt_api->get_account_ballance();
		
		include_once PL_WCPT_DIR_PATH . 'includes/class-products-list-table-free.php';
		include_once PL_WCPT_DIR_PATH . 'views/list-products.php';
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
		
		do_action( 'pl_pt_updated_conversion_value' );
	}
	
	public function check_needed_price_updates() {
								
		$args = array(
			'post_type'      => 'product',
	        'posts_per_page' => -1,
		    'meta_query' => array(
		        array(
		            'key' => '_pt_product',
		            'compare' => 'EXISTS'
		        )
		    ),
		    'fields' => 'ids'
		);
		
		$products         = get_posts( $args );
		$products_db      = new PL_WCPT_Products_DB();
		$conversion_value = ( get_option( 'pl_wcpt_api_currency_conversion_value' ) ) ? get_option( 'pl_wcpt_api_currency_conversion_value' ) : 1;
					
		foreach ( $products as $product ) {
			
			$pt_product_id = get_post_meta( $product, '_pt_product', TRUE );
			if ( $pt_product_id ) {
				$pt_product = $products_db->get_product( $pt_product_id );
				if ( $pt_product ) {
					
					$markup_value  = get_post_meta( $product, '_pt_autoselling_price_percentage', TRUE );
					$sell_price    = $pt_product->sell_price;
					$price         = round( $sell_price * $conversion_value, 2 );					
					$old_price = get_post_meta( $product, '_pt_price', TRUE );

					if ( $old_price != $price ) {						
						update_post_meta( $product, '_pt_price', $price );
					}
				}
			}
		}
	}

	public function pl_paythem_reserved_stock() {

		include_once PL_WCPT_DIR_PATH . 'views/list-reserved-stock.php';
	}

	public function pl_paythem_stock() {

		include_once PL_WCPT_DIR_PATH . 'views/list-stock.php';
	}

	public function pl_paythem_reports() {

		if ( ! isset( $_GET['paged'] ) && ! isset( $_GET['action'] ) && ! isset( $_GET['pl-search-term-top'] ) ) {
			$this->force_sync_transactions();
		}

		include_once PL_WCPT_DIR_PATH . 'views/list-reports.php';
	}

	public function force_sync_transactions() {

		$now_date         = date( 'Y-m-d H:i:s', strtotime( '-1 day' ) );
		$fifteen_days_ago = date( 'Y-m-d H:i:s', strtotime( '-15 days' ) );
		$sixty_days_ago   = date( 'Y-m-d H:i:s', strtotime( '-60 days' ) );

		$transaction_last = get_option( 'pl_wcpt_force_transaction_last' ) ? get_option( 'pl_wcpt_force_transaction_last' ) : $sixty_days_ago;
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
		$financial_db    = new PL_WCPT_Financial_DB();

		$pt_api = new PL_WCPT_API();

		$transactions = $pt_api->get_sales_history( $sales_last, $now_gmt );
		if ( $transactions ) {
			$transactions_db->add_transactions( $transactions );
		}

		$financial = $pt_api->get_financial_history( $financial_last, $now_gmt );

		if ( $financial ) {
			$financial_db->add_transactions( $financial );
		}

		$this->plugin_instance->get_wallet_amount( true );

		update_option( 'pl_wcpt_force_transaction_last', $now_date );
	}

	public function pl_paythem_bulk_import() {

		$import_controller = new PL_WCPT_Bulk_Import_Controller();
		$import_controller->display();
	}

	public function pl_paythem_settings() {

		include_once PL_WCPT_DIR_PATH . 'views/settings-free.php';
	}

	public function pl_export_stock_page() {

		$controller = new PL_WCPT_Export_Stock_Controller();
		$controller->exportCSV();
	}

	public function pl_export_reports_page() {

		$controller = new PL_WCPT_Export_Reports_Controller();
		$controller->exportCSV();
	}
}
