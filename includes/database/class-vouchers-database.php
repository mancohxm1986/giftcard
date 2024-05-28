<?php
	
if (! defined('ABSPATH')) {
    exit();
}

/**
* This class handles Vouchers database queries
**/

class PL_WCPT_Vouchers_DB extends PL_WCPT_Database {

    public function __construct() {
	    
	    global $wpdb;
        $this->tableName = $wpdb->prefix . 'wcpt_vouchers';
    }
    
    public function get_vouchers() {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . 'wcpt_vouchers' . " ORDER BY voucher_id DESC" );
	    return $products;
    }
    
    public function delete_vouchers() {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_results( "TRUNCATE TABLE " . $wpdb->prefix . 'wcpt_vouchers' );
	    return $products;
    }
    
    public function get_voucher( $voucher_id ) {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . 'wcpt_vouchers' . " WHERE voucher_id=%d ORDER BY voucher_id DESC", $voucher_id ) );
	    return $products;
    }
    
    public function get_active_vouchers_count_by_product_id( $product_id ) {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_col( $wpdb->prepare( "SELECT count(voucher_id) as count FROM " . $wpdb->prefix . 'wcpt_vouchers' . " WHERE product_id=%d AND sold IS NULL ORDER BY voucher_id DESC", $product_id ) );
	    $result = isset( $products[0] ) ? $products[0] : 0;
	    return $result;
    }
    
    public function get_active_vouchers_by_product_id( $product_id ) {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . 'wcpt_vouchers' . " WHERE product_id=%d AND sold IS NULL ORDER BY voucher_id DESC", $product_id ) );
	    return $products;
    }
    
    public function get_vouchers_by_order_product( $order_id, $product_id ) {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . 'wcpt_vouchers' . " WHERE product_id=%d AND order_id=%d AND sold=1 ORDER BY voucher_id DESC", $product_id, $order_id ) );
	    return $products;
    }
    
    public function get_vouchers_by_order( $order_id ) {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . 'wcpt_vouchers' . " WHERE order_id=%d AND sold=1 ORDER BY voucher_id DESC", $order_id ) );
	    return $products;
    }
    
    public function delete_vouchers_from_order( $order_id ) {
	    
	     global $wpdb;
	     $wpdb->delete( $this->tableName, array( 'sold' => 1, 'order_id' => $order_id ) );
    }
    
    public function get_active_vouchers_by_product_id_count( $product_id ) {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_row( $wpdb->prepare( "SELECT count(voucher_id) as count FROM " . $wpdb->prefix . 'wcpt_vouchers' . " WHERE product_id=%d AND sold IS NULL ORDER BY voucher_id DESC", $product_id ) );
	    
	    return $products;
    }
    
    public function set_vouchers_as_sold( $pt_product, $quantity, $order_id ) {
	    
	    global $wpdb;
	    $query = $wpdb->prepare( "UPDATE " . $wpdb->prefix . 'wcpt_vouchers' . " SET order_id=%d, sold=1
					WHERE voucher_id IN (
					    SELECT voucher_id FROM (
					        SELECT voucher_id FROM " . $wpdb->prefix . 'wcpt_vouchers' . " 
					        WHERE sold IS NULL AND product_id=%d
					        ORDER BY expires ASC  
					        LIMIT 0, %d
					    ) tmp
					)", $order_id, $pt_product, $quantity );
										
	    $result = $wpdb->query( $query );
	    return $result;
    }
    
    public function add_vouchers( $transactions ) {
	    
	    global $wpdb;
	    	    	    
	    $paging = array();
	    $base   = 0;
	      
	    foreach ( $transactions as $k => $transaction ) {
		    
		    if ( $k % 20 == 0 ) {
			    $base++;
		    }
		    
		    $paging[ $base ][]  = $transaction;
	    }
	    
	    foreach ( $paging as $v_manu ) {
		    if ( $v_manu ) {
			    $p_args = array();
			    $insert_vals = 'INSERT INTO ' . $wpdb->prefix . 'wcpt_vouchers' . ' (
							    voucher_id,
							    voucher_pin,
							    voucher_serial,
							    product_id,
							    product_name,
							    sell_price,
							    sales_id,
							    transaction_status,
							    purchase_date,
							    expires,
							    transaction_id
							)
							VALUES';
				foreach ( $v_manu as $k => $m_insert ) {
											
				    $voucher_id     = is_numeric( $m_insert['OEM_VOUCHER_ID'] ) ? $m_insert['OEM_VOUCHER_ID'] : 0;
				    $voucher_pin    = sanitize_text_field( $m_insert['OEM_VOUCHER_PIN'] );
				    $voucher_serial = sanitize_text_field( $m_insert['OEM_VOUCHER_SERIAL'] );
				    $product_id 	= is_numeric( $m_insert['OEM_PRODUCT_ID'] ) ? $m_insert['OEM_PRODUCT_ID'] : 0;
				    $product_name   = sanitize_text_field( $m_insert['OEM_PRODUCT_Name'] );
				    $sell_price 	= is_numeric( $m_insert['OEM_PRODUCT_SellPrice'] ) ? $m_insert['OEM_PRODUCT_SellPrice'] : 0;
				    $sales_id 	    = is_numeric( $m_insert['OEM_VOUCHER_SALES_ID'] ) ? $m_insert['OEM_VOUCHER_SALES_ID'] : 0;
				    $status 		= sanitize_text_field( $m_insert['OEM_VOUCHER_TransactionStatus'] );
				    $purchase_date  = sanitize_text_field( $m_insert['TRANSACTION_DATE'] );
				    $expires  	    = isset( $m_insert['OEM_VOUCHER_EXPIRATION_DATE'] ) ?sanitize_text_field( $m_insert['OEM_VOUCHER_EXPIRATION_DATE'] ) : '0000-00-00';
				    $transaction_id = is_numeric( $m_insert['TRANSACTION_ID'] ) ? $m_insert['TRANSACTION_ID'] : 0;
				    					
					if ( $k ) {
						$insert_vals .= ",";
					}
					
					$insert_vals .= "( %d, %s, %s, %d, %s, %f, %d, %s, %s, %s, %d )";
					
					$p_args[] = $voucher_id;
					$p_args[] = $voucher_pin;
					$p_args[] = $voucher_serial;
					$p_args[] = $product_id;
					$p_args[] = $product_name;
					$p_args[] = $sell_price;
					$p_args[] = $sales_id;
					$p_args[] = $status;
					$p_args[] = $purchase_date;
					$p_args[] = $expires;
					$p_args[] = $transaction_id;
				}
				
				$insert_vals .= ' ON DUPLICATE KEY UPDATE voucher_id=VALUES(voucher_id)';
								
				$wpdb->query( $wpdb->prepare( $insert_vals, $p_args ) );
			}
	    }
    }
}