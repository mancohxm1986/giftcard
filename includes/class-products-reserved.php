<?php 
	
if (! defined('ABSPATH')) {
    exit();
}

class PL_WCPT_Products_Reserved {
	
	public function __construct() {
		
		add_action( "woocommerce_order_status_wc-failed", array ( $this, 'handle_reserved_stock' ), 999 );
		add_action( "woocommerce_order_status_wc-refunded", array ( $this, 'handle_reserved_stock' ), 999 );
		add_action( "woocommerce_order_status_wc-cancelled", array ( $this, 'handle_reserved_stock' ), 999 );
		add_action( "woocommerce_order_status_wc-pending", array ( $this, 'handle_reserved_stock' ), 999 );
		add_action( "woocommerce_order_status_on-hold", array ( $this, 'handle_reserved_stock' ), 999 );
		add_action( "woocommerce_order_status_processing", array ( $this, 'handle_reserved_stock' ), 999 );
		add_action( "woocommerce_order_status_completed", array ( $this, 'handle_reserved_stock' ), 999 );
	}
	
	public function handle_reserved_stock( $order_id ) {
		
		$order = wc_get_order( $order_id );	
		foreach ( $order->get_items() as $key => $item ) {
			
			$product_id = $item->get_product_id();
			$pt_product = get_post_meta( $product_id, '_pt_product', TRUE );
			if ( $pt_product ) {
				$this->calculate_reserved_stock( $product_id );
			}
		}
	}
	
	public static function get_user_account_fingerprint() {
		
		$pl_wcpt_api_username    = get_option( 'pl_wcpt_api_username' );
		$pl_wcpt_api_environment = get_option( 'pl_wcpt_api_environment' );
		if ( $pl_wcpt_api_username && $pl_wcpt_api_environment ) {
			$pl_environment = $pl_wcpt_api_username . '_' . $pl_wcpt_api_environment;
			return $pl_environment;
		}
		
		return '';
	}
	
	public function high_performance_order_storage() {
	    if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() && get_option( 'woocommerce_custom_orders_table_data_sync_enabled' ) != 'yes' ) {
		    return true;
		}
		
		return false;
    }
	
	public function calculate_reserved_stock( $product_id, $order_id = 0 ) {
		
		global $wpdb;
		
		$order_itemmeta = "{$wpdb->prefix}woocommerce_order_itemmeta";
		$order_items    = "{$wpdb->prefix}woocommerce_order_items";
		$posts		    = $wpdb->posts;		
		$statuses       = array( 'wc-processing', 'wc-completed' );
		$p_args 		= array();
		
		$cutoff_date = get_post_meta( $product_id, '_pt_product_start', TRUE );
		if ( !$cutoff_date ) {
			return 0;
		}
		
		$code_purchase = ( get_option( 'pl_wcpt_api_code_purchase' ) ) ? get_option( 'pl_wcpt_api_code_purchase' ) : 'processing';
		if ( $code_purchase == 'completed' ) {
			$statuses = array( 'wc-completed' );
		}
		
		$statuses_placeholder = substr( str_repeat( ',%s', count( $statuses ) ), 1 );
		
		$is_hpos = $this->high_performance_order_storage();
		
		if ( $is_hpos ) {
			$orders_db = Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore::get_orders_table_name();
			$query = "SELECT order_id, im3.meta_value as quantity, pt.status as status from $order_itemmeta im LEFT JOIN $order_itemmeta im2 ON im.order_item_id = im2.order_item_id AND im2.meta_key = '_pl_voucher_to_user' INNER JOIN $order_items oi ON im.order_item_id = oi.order_item_id INNER JOIN $order_itemmeta im3 ON im.order_item_id = im3.order_item_id AND im3.meta_key = '_qty' INNER JOIN $orders_db as pt ON oi.order_id = pt.ID WHERE im.meta_key = '_product_id' AND im.meta_value = %d AND ( im2.meta_key IS NULL OR im2.meta_value = '' ) AND pt.status IN ( $statuses_placeholder ) AND pt.date_created_gmt >= %s";
		} else {
			$query = "SELECT order_id, im3.meta_value as quantity, pt.post_status as status from $order_itemmeta im LEFT JOIN $order_itemmeta im2 ON im.order_item_id = im2.order_item_id AND im2.meta_key = '_pl_voucher_to_user' INNER JOIN $order_items oi ON im.order_item_id = oi.order_item_id INNER JOIN $order_itemmeta im3 ON im.order_item_id = im3.order_item_id AND im3.meta_key = '_qty' INNER JOIN $posts as pt ON oi.order_id = pt.ID WHERE im.meta_key = '_product_id' AND im.meta_value = %d AND ( im2.meta_key IS NULL OR im2.meta_value = '' ) AND pt.post_status IN ( $statuses_placeholder ) AND pt.post_date_gmt >= %s";
		}
		
		$p_args[] = $product_id;
		$p_args   = array_merge( $p_args, $statuses );
		$p_args[] = $cutoff_date;
										
		$results      = $wpdb->get_results( $wpdb->prepare( $query, $p_args ) );				
		$quantity     = 0;
		$all_quantity = 0;
				
		$fingerprint = self::get_user_account_fingerprint();
				
		foreach ( $results as $result ) {
			
			$r_order_id        = $result->order_id;
			$order			   = wc_get_order( $r_order_id );
			$order_fingerprint = $order->get_meta( '_pt_account_fingerprint' );
			if ( $order_fingerprint && $fingerprint && $fingerprint == $order_fingerprint ) {
				if ( !$order_id || $order_id != $result->order_id ) {
					if ( $result->quantity ) {
						$quantity += $result->quantity;
					}
				}
				if ( $result->quantity ) {
					$all_quantity += $result->quantity;
				}
			}
		}
										
		update_post_meta( $product_id, '_pt_product_reserved', $all_quantity );
		return $quantity;
	}
}