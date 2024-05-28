<?php
	
if ( !defined( 'ABSPATH' ) ) {
    exit(); // Exit if accessed directly
}

class PL_WCPT_API {
	
	public $api_id;
	public $api_username;
	public $api_password;
	public $api_public_key;
	public $api_private_key;
	public $api_environment;
	
	public function __construct() {
		  
		$this->api_id 		  = 2824;
				
		$environment 		  = get_option( 'pl_wcpt_api_environment' );
		if ( $environment == 'demo' ) {
			$url = 'https://vvsdemo.paythem.net/API/2824';
		}
		if ( !$environment || $environment == 'qa' ) {
			$url = 'https://vvsdemo.paythem.net/API/2824';
			//$url = 'https://vvsqa.paythem.net/API/2824';
		}
		else {
			$url = 'https://vvs.paythem.net/API/2824';
		}

		$this->api_url 		  = $url;
		$this->api_username    = get_option( 'pl_wcpt_api_username' );
		$this->api_password    = get_option( 'pl_wcpt_api_password' );
		$this->api_public_key  = get_option( 'pl_wcpt_api_public_key' );
		$this->api_private_key = get_option( 'pl_wcpt_api_private_key' );
		$this->api_environment = $environment;		
	}
	
	public function get_manufacturers() {
		
		if ( $this->api_environment && $this->api_id && $this->api_public_key && $this->api_private_key && $this->api_username && $this->api_password ) {
			
			$api = new PTN_API_v2( $this->api_environment, $this->api_id );
			$api->PUBLIC_KEY   = $this->api_public_key;
			$api->PRIVATE_KEY  = $this->api_private_key;
			$api->USERNAME 	   = $this->api_username;
			$api->PASSWORD 	   = $this->api_password;
			$api->SERVER_URI   = $this->api_url;
			$api->FUNCTION 	   = 'get_OEMList';

			$res = $api->callAPI( false ); 
			
			return $res;
		}
		
		return false;
	}
	
	public function get_brands() {
		
		if ( $this->api_environment && $this->api_id && $this->api_public_key && $this->api_private_key && $this->api_username && $this->api_password ) {
			
			$api = new PTN_API_v2( $this->api_environment, $this->api_id );
			$api->PUBLIC_KEY  = $this->api_public_key;
			$api->PRIVATE_KEY = $this->api_private_key;
			$api->USERNAME 	 = $this->api_username;
			$api->PASSWORD 	 = $this->api_password;
			$api->SERVER_URI	 = $this->api_url;
			$api->FUNCTION 	 = 'get_BrandList';

			$res = $api->callAPI( false ); 
			
			return $res;
		}
		
		return false;
	}
	
	public function get_products() {
		
		if ( $this->api_environment && $this->api_id && $this->api_public_key && $this->api_private_key && $this->api_username && $this->api_password ) {
			
			$api = new PTN_API_v2( $this->api_environment, $this->api_id );
			$api->PUBLIC_KEY  = $this->api_public_key;
			$api->PRIVATE_KEY = $this->api_private_key;
			$api->USERNAME 	 = $this->api_username;
			$api->PASSWORD 	 = $this->api_password;
			$api->SERVER_URI	 = $this->api_url;
			$api->FUNCTION 	 = 'get_ProductList';
			$api->PARAMETERS  = array(
			 "PRODUCT_ID" => 27,
			); 

			$res = $api->callAPI( false ); 
			
			return $res;
		}
		
		return false;
	}
	
	public function purchase_products( $product_id, $amount ) {
		
		if ( $this->api_environment && $this->api_id && $this->api_public_key && $this->api_private_key && $this->api_username && $this->api_password ) {
						
			$api = new PTN_API_v2( $this->api_environment, $this->api_id );
			$api->PUBLIC_KEY  = $this->api_public_key;
			$api->PRIVATE_KEY = $this->api_private_key;
			$api->USERNAME 	 = $this->api_username;
			$api->PASSWORD 	 = $this->api_password;
			$api->SERVER_URI	 = $this->api_url;
			$api->FUNCTION    = 'get_Vouchers';
			$api->PARAMETERS  = array(
			 "PRODUCT_ID" => $product_id,
			 "QUANTITY"   => $amount
			); 

			$res = $api->callAPI( false ); 
			$this->get_product_stock( $product_id );
									
			return $res;
		}
		
		return false;
	}
	
	public function get_product_stock( $product_id ) {
		
		if ( $this->api_environment && $this->api_id && $this->api_public_key && $this->api_private_key && $this->api_username && $this->api_password ) {
									
			$api = new PTN_API_v2( $this->api_environment, $this->api_id );
			$api->PUBLIC_KEY  = $this->api_public_key;
			$api->PRIVATE_KEY = $this->api_private_key;
			$api->USERNAME 	 = $this->api_username;
			$api->PASSWORD 	 = $this->api_password;
			$api->SERVER_URI	 = $this->api_url;
			$api->FUNCTION 	 = 'get_ProductAvailability';
			$api->PARAMETERS  = array(
				 'PRODUCT_ID' => $product_id,
			); 
			
			$res = $api->callAPI( false );
																		
			return $res;
		}
		
		return false;
	}
	
	public function get_purchase_limit() {
		
		if ( $this->api_environment && $this->api_id && $this->api_public_key && $this->api_private_key && $this->api_username && $this->api_password ) {
						
			$api = new PTN_API_v2( $this->api_environment, $this->api_id );
			$api->PUBLIC_KEY  = $this->api_public_key;
			$api->PRIVATE_KEY = $this->api_private_key;
			$api->USERNAME 	  = $this->api_username;
			$api->PASSWORD 	  = $this->api_password;
			$api->SERVER_URI  = $this->api_url;
			$api->FUNCTION 	  = 'get_MaxAllowedVouchersPerCall';
			
			$res = $api->callAPI( false );
						
			if ( $res && is_numeric( $res ) ) {
				update_option( 'pl_wcpt_api_purchase_limit', $res );
			}
		}
		
		return false;
	}
	
	public function get_account_ballance() {
		
		if ( $this->api_environment && $this->api_id && $this->api_public_key && $this->api_private_key && $this->api_username && $this->api_password ) {
						
			$api = new PTN_API_v2( $this->api_environment, $this->api_id );
			$api->PUBLIC_KEY  = $this->api_public_key;
			$api->PRIVATE_KEY = $this->api_private_key;
			$api->USERNAME 	  = $this->api_username;
			$api->PASSWORD 	  = $this->api_password;
			$api->SERVER_URI  = $this->api_url;
			$api->FUNCTION 	  = 'get_AccountBalance';
			
			$res = $api->callAPI( false );
						
			$current_currency       = isset( $res['RESELLER_Currency'] ) ? $res['RESELLER_Currency'] : 'USD';
			$store_currency         = get_option( 'woocommerce_currency' );
			$pl_wcpt_api_last_currency   = get_option( 'pl_wcpt_api_last_currency' );
			$pl_wcpt_store_last_currency = get_option( 'pl_wcpt_store_last_currency' );
			$run_conversion         = false;
			
			if ( $current_currency && $current_currency != $pl_wcpt_api_last_currency ) {								
				update_option( 'pl_wcpt_api_last_currency', $current_currency );
				update_option( 'pl_wcpt_api_account_currency', $current_currency );
				$run_conversion = true;
			}
			
			if ( $store_currency && $store_currency != $pl_wcpt_store_last_currency ) {
				update_option( 'pl_wcpt_store_last_currency', $store_currency );
				$run_conversion = true;
			}
									
			if ( $run_conversion ) {
				do_action( 'pl_auto_currency_conversion' );
			}
									
			return $res;
		}
		return false;
	}
	
	public function get_sales_history( $from_date, $to_date ) {
		
		if ( $this->api_environment && $this->api_id && $this->api_public_key && $this->api_private_key && $this->api_username && $this->api_password ) {
			
			$api = new PTN_API_v2( $this->api_environment, $this->api_id );
			$api->PUBLIC_KEY  = $this->api_public_key;
			$api->PRIVATE_KEY = $this->api_private_key;
			$api->USERNAME 	 = $this->api_username;
			$api->PASSWORD 	 = $this->api_password;
			$api->SERVER_URI	 = $this->api_url;
			$api->FUNCTION 	 = 'Get_SalesTransaction_ByDateRange';
			$api->PARAMETERS  = array(
				 'FROM_DATE' => $from_date,
				 'TO_DATE'   => $to_date,
			); 
			
			$res = $api->callAPI( false ); 
						
			return $res;
		}
		return false;
	}
	
	public function get_financial_history( $from_date, $to_date ) {
		
		if ( $this->api_environment && $this->api_id && $this->api_public_key && $this->api_private_key && $this->api_username && $this->api_password ) {
			
			$api = new PTN_API_v2( $this->api_environment, $this->api_id );
			$api->PUBLIC_KEY  = $this->api_public_key;
			$api->PRIVATE_KEY = $this->api_private_key;
			$api->USERNAME 	 = $this->api_username;
			$api->PASSWORD 	 = $this->api_password;
			$api->SERVER_URI	 = $this->api_url;
			$api->FUNCTION 	 = 'get_FinancialTransaction_ByDateRange';
			$api->PARAMETERS  = array(
				 'FROM_DATE' => $from_date,
				 'TO_DATE'   => $to_date,
			); 
			
			$res = $api->callAPI( false ); 
						
			return $res;
		}
		return false;
	}
}