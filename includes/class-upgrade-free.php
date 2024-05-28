<?php 
	
if (! defined('ABSPATH')) {
    exit();
}

class PL_WCPT_Upgrade {
	
	public function __construct() {		
		
		// Maybe cleanup pro settings
		add_action( 'init', array( $this, 'maybe_cleanup_pro_settings' ) );
	}	
	
	public function maybe_cleanup_pro_settings() {
		
		$pl_wcpt_api_currency_conversion	= ( get_option( 'pl_wcpt_api_currency_conversion' ) ) ? get_option( 'pl_wcpt_api_currency_conversion' ) : 'manual';
		if ( $pl_wcpt_api_currency_conversion !== 'manual' ) {
			update_option( 'pl_wcpt_api_currency_conversion', 'manual' );
		}
		
		$api_openexchangerates = ( get_option( 'pl_wcpt_api_openexchangerates' ) ) ? get_option( 'pl_wcpt_api_openexchangerates' ) : '';
		if ( $api_openexchangerates ) {
			delete_option( 'pl_wcpt_api_openexchangerates' );
		}
		
		$api_import_markup_val = ( get_option( 'pl_wcpt_api_import_markup_val' ) ) ? get_option( 'pl_wcpt_api_import_markup_val' ) : '';
		if ( $api_import_markup_val ) {
			delete_option( 'pl_wcpt_api_import_markup_val' );
		}
		
		$max_items = get_option( 'pl_wcpt_api_purchase_limit', 20 );
		$api_per_product_limit = get_option( 'pl_wcpt_api_per_product_limit', $max_items );
		
		if ( $api_per_product_limit > $max_items ) {
			update_option( 'pl_wcpt_api_per_product_limit', $max_items );
		}
		
		$api_code_purchase = ( get_option( 'pl_wcpt_api_code_purchase' ) ) ? get_option( 'pl_wcpt_api_code_purchase' ) : '';
		if ( $api_code_purchase ) {
			delete_option( 'pl_wcpt_api_code_purchase' );
		}
		
		$api_code_release_method_limit = get_option( 'pl_wcpt_api_code_release_method_limit' );
		if ( $api_code_release_method_limit ) {
			delete_option( 'pl_wcpt_api_code_release_method_limit' );
		}
		
		$api_code_release_regular   = get_option( 'pl_wcpt_api_code_release_regular_email' );
		$api_code_release_csv_email = get_option( 'pl_wcpt_api_code_release_csv_email' );
		
		if ( $api_code_release_regular !== 'yes' ) {
			get_option( 'pl_wcpt_api_code_release_regular_email', 'yes' );
		}
		
		if ( $api_code_release_csv_email !== 'yes' ) {
			get_option( 'pl_wcpt_api_code_release_csv_email', 'yes' );
		}
		
		$api_low_wallet_email	 = get_option( 'pl_wcpt_api_low_wallet_email' );
		$api_price_mismatch_email = get_option( 'pl_wcpt_api_price_mismatch_email' );
		
		if ( $api_low_wallet_email ) {
			delete_option( 'pl_wcpt_api_low_wallet_email' );
		}
		
		if ( $api_price_mismatch_email ) {
			delete_option( 'pl_wcpt_api_price_mismatch_email' );
		}
	}
}