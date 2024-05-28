<?php
	
if (! defined('ABSPATH')) {
    exit();
}

/**
* This class handles transaction database queries
**/

class PL_WCPT_Transactions_DB extends PL_WCPT_Database {

    public function __construct() {
	    
	    global $wpdb;
        $this->tableName = $wpdb->prefix . 'wcpt_transactions';
    }
    
    public function get_transactions() {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . 'wcpt_transactions' . " ORDER BY date DESC" );
	    return $products;
    }
    
    public function delete_transactions() {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_results( "TRUNCATE TABLE " . $wpdb->prefix . 'wcpt_transactions' );
	    return $products;
    }
    
    public function get_transaction( $transaction_id ) {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . 'wcpt_transactions' . " WHERE transaction_id=%d ORDER BY date DESC", $transaction_id ) );
	    return $products;
    }
    
    public function add_transactions( $transactions ) {
	    	    
	    global $wpdb;
	    	    	    
	    $paging  		    = array();
	    $transactions_ids    = array();
	    $base    		    = 0;
	      
	    foreach ( $transactions as $k => $transaction ) {
		    
		    if ( $k % 20 == 0 ) {
			    $base++;
		    }
		    
		    $transaction_id = $transaction['TRANSACTION_ID'];
		    if ( !in_array( $transaction_id, $transactions_ids ) ) {
		    
			    $transactions_ids[] = $transaction_id;
			    $paging[ $base ][ $transaction_id ]  = $transaction;
		    }
		    else {
			    if ( isset( $transaction['OEM_VOUCHER_SERIAL'] ) ) {
				    if ( isset( $paging[ $base ][ $transaction_id ]['OEM_VOUCHER_SERIAL'] ) ) {
					    $paging[ $base ][ $transaction_id ]['OEM_VOUCHER_SERIAL'] .= ', ' . $transaction['OEM_VOUCHER_SERIAL'];
				    }
				    else {
					    $paging[ $base ][ $transaction_id ]['OEM_VOUCHER_SERIAL'] = $transaction['OEM_VOUCHER_SERIAL'];
				    }
			    }
		    }
	    }
	    	    
	    foreach ( $paging as $v_manu ) {
		    
		    global $wpdb;
		    if ( $v_manu ) {
			    
			    $p_args = array();
			    $insert_vals = 'INSERT INTO ' . $wpdb->prefix . 'wcpt_transactions' . ' (
							    transaction_id,
							    product_id,
							    quantity,
							    total,
							    date,
							    status,
							    serial,
							    balance
							)
							VALUES';
							
				$count = 0;
				foreach ( $v_manu as $k => $m_insert ) {
					
					if ( !isset( $m_insert['TRANSACTION_ID'] ) ) {
						continue;
					}
															
				    $transaction_id = is_numeric( $m_insert['TRANSACTION_ID'] ) ? $m_insert['TRANSACTION_ID'] : 0;
				    $quantity 	    = is_numeric( $m_insert['TRANSACTION_VOUCHER_QUANTITY'] ) ? $m_insert['TRANSACTION_VOUCHER_QUANTITY'] : 0;
				    $product_id     = is_numeric( $m_insert['OEM_PRODUCT_ID'] ) ? $m_insert['OEM_PRODUCT_ID'] : 0;
				    $total 		    = is_numeric( $m_insert['TRANSACTION_USD_VALUE'] ) ? $m_insert['TRANSACTION_USD_VALUE'] : 0;
				    $date 		    = sanitize_text_field( $m_insert['TRANSACTION_DATE'] );
				    $status 	    = sanitize_text_field( $m_insert['TRANSACTION_CurrentStatus'] );
				    $serial 	    = isset( $m_insert['OEM_VOUCHER_SERIAL'] ) ? sanitize_text_field( $m_insert['OEM_VOUCHER_SERIAL'] ) : '';
				    $balance 	    = ( isset( $m_insert['TRANSACTION_AccountBalance'] ) && is_numeric( $m_insert['TRANSACTION_AccountBalance'] ) ) ? $m_insert['TRANSACTION_AccountBalance'] : 0;
				    				    					
					if ( $count ) {
						$insert_vals .= ",";
					}
					
					$insert_vals .= "( %d, %d, %d, %f, %s, %s, %s, %f )";
					$p_args[] = $transaction_id;
					$p_args[] = $product_id;
					$p_args[] = $quantity;
					$p_args[] = $total;
					$p_args[] = $date;
					$p_args[] = $status;
					$p_args[] = $serial;
					$p_args[] = $balance;
					$count++;
				}
				
				$insert_vals .= ' ON DUPLICATE KEY UPDATE transaction_id=VALUES(transaction_id)';
												
				$wpdb->query( $wpdb->prepare( $insert_vals, $p_args ) );
			}
	    }
    }
}