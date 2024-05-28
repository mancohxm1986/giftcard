<?php
	
if (! defined('ABSPATH')) {
    exit();
}

/**
* This class handles financial transaction database queries
**/

class PL_WCPT_Financial_DB extends PL_WCPT_Database {

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
			    $paging[ $base ][]  = $transaction;
		    }
	    }
	    
	    foreach ( $paging as $v_manu ) {
		    if ( $v_manu ) {
			    $p_args = array();
			    $insert_vals = 'INSERT INTO ' . $wpdb->prefix . 'wcpt_transactions' . ' (
							    transaction_id,
							    product_id,
							    quantity,
							    total,
							    date,
							    status,
							    balance
							)
							VALUES';
				foreach ( $v_manu as $k => $m_insert ) {
															
				    $transaction_id = is_numeric( $m_insert['TRANSACTION_ID'] ) ? $m_insert['TRANSACTION_ID'] : 0;
				    $product_id	   = 0;
				    $quantity	   = 1;
				    $type 		   = sanitize_text_field( $m_insert['TRANSACTION_Type'] );
				    $total   	   = is_numeric( $m_insert['TRANSACTION_Value'] ) ? $m_insert['TRANSACTION_Value'] : 0;
				    $date 		   = sanitize_text_field( $m_insert['TRANSACTION_DateCaptured'] );
				    $balance 	   = is_numeric( $m_insert['TRANSACTION_AccountBalance'] ) ? $m_insert['TRANSACTION_AccountBalance'] : null;
				    
					if ( $k ) {
						$insert_vals .= ",";
					}
					
					$insert_vals .= "( %d, %d, %d, %f, %s, %s, %f )";
					$p_args[] = $transaction_id;
					$p_args[] = $product_id;
					$p_args[] = $quantity;
					$p_args[] = $total;
					$p_args[] = $date;
					$p_args[] = $type;
					$p_args[] = $balance;
				}
				
				$insert_vals .= ' ON DUPLICATE KEY UPDATE 
				                    transaction_id=VALUES(transaction_id),
				                    product_id=VALUES(product_id),
				                    quantity=VALUES(quantity),
				                    total=VALUES(total),
				                    date=VALUES(date),
				                    status=VALUES(status),
									balance=VALUES(balance)';
				
				$result = $wpdb->query( $wpdb->prepare( $insert_vals, $p_args ) );				
			}
	    }
    }
}