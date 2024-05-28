<?php
	
if ( !defined( 'ABSPATH' ) ) {
    exit(); // Exit if accessed directly
}
	
/**
 * Add Custom Product Taxonomy.
*/
 
class PL_WCPT_Scheduled_Actions {
	
	private $plugin_instance = null;
		
	public function __construct() {		
				
		// Add cronjobs
		add_action( 'admin_init', array( $this, 'add_scheduled_actions' ) );
		
		// Run cronjobs
		add_action( 'pl_paythem_release_vouchers', array( $this, 'release_vouchers_action' ) );
		
		// Sync products
		add_action( 'pl_paythem_sync_products', array( $this, 'sync_products' ) );
		
		// Sync trasactions
		add_action( 'pl_paythem_sync_transactions', array( $this, 'sync_transactions' ) );
		
		// Sync auto purchases
		add_action( 'pl_paythem_sync_auto_purchases', array( $this, 'auto_purchases' ) );
				
		// Sync purchase limit
		add_action( 'pl_paythem_update_purchase_limit', array( $this, 'update_purchase_limit' ) );
		
		$this->plugin_instance = pl_wcpt_extention_free::get_instance();
	}
	
	public function add_scheduled_actions() {
		
		// Run export orders every 5 minutes
		if ( function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( 'pl_paythem_release_vouchers' ) ) {
			as_schedule_recurring_action( strtotime( 'now' ), ( HOUR_IN_SECONDS / 12 ), 'pl_paythem_release_vouchers' );
		}
		
		if ( function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( 'pl_paythem_sync_products' ) ) {
			as_schedule_recurring_action( strtotime( 'now' ), ( HOUR_IN_SECONDS ), 'pl_paythem_sync_products' );
		}
		
		if ( function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( 'pl_paythem_sync_transactions' ) ) {
			as_schedule_recurring_action( strtotime( 'now' ), ( HOUR_IN_SECONDS ), 'pl_paythem_sync_transactions' );
		}
		
		if ( function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( 'pl_paythem_sync_auto_purchases' ) ) {
			as_schedule_recurring_action( strtotime( 'now' ), ( HOUR_IN_SECONDS ), 'pl_paythem_sync_auto_purchases' );
		}
				
		if ( function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( 'pl_paythem_update_purchase_limit' ) ) {
			as_schedule_recurring_action( strtotime( 'now' ), ( HOUR_IN_SECONDS * 24 ), 'pl_paythem_update_purchase_limit' );
		}		
	}
	
	public function update_purchase_limit() {
		
		$pt_api = new PL_WCPT_API();
		$pt_api->get_purchase_limit();
	}
		
	public function auto_purchases() {
		$this->plugin_instance->auto_purchases();		
	}
	
	public function sync_transactions() {
		$this->plugin_instance->sync_transactions();
	}
		
	public function sync_products() {
		$this->plugin_instance->sync_products();
	}
		
	public function release_vouchers_action() {
		
		$relase_status = ( get_option( 'pl_wcpt_api_code_release' ) ) ? get_option( 'pl_wcpt_api_code_release' ) : 'processing';	
		$statuses      = $relase_status == 'completed' ? array( 'wc-completed' ) : array( 'wc-completed', 'wc-processing' );
		$order_ids     = get_posts( array(
			'numberposts' => -1, 
			'post_status' => $statuses,
			'post_type'   => 'shop_order',
			'fields'      => 'ids',
			'meta_query' => array(
		        'relation' => 'AND',
		        array(
		            'key'     => 'pl_fraudlabs_blocked',
		            'compare' => 'EXISTS'
		        ),
		        array(
		            'key'     => 'pl_run_release_cron',
		            'compare' => 'EXISTS'
		        ),
		        array(
		            'key'     => 'pl_released_vouchers',
		            'compare' => 'NOT EXISTS'
		        )
			)
			)
		);
		
		foreach ( $order_ids as $order_id ) {
						
			$order = wc_get_order( $order_id );
			if ( $order ) {
				do_action( 'woocommerce_order_action_pl_release_voucher', $order );
			}
		}				
	}	
}
