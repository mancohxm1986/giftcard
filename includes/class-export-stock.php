<?php

if ( !defined( 'ABSPATH' ) ) {
    exit(); // Exit if accessed directly
}

class PL_WCPT_Export_Stock_Controller {

    public function __construct() {}

    public function exportCSV() {
	    		
		ob_clean();
		
		global $wpdb;
	    
		header( 'Content-Type: application/csv' );
	    header( 'Content-Disposition: attachment; filename="stock.csv";' );

	    $delimiter = ',';
        
        /**
         * open raw memory as file, no need for temp files, be careful not to run out of memory thought
         */
        $f = tmpfile();

        $line = array(
          __( 'PIN', 'gift-cards-on-demand-free' ),
	      __( 'Serial', 'gift-cards-on-demand-free' ),
	      __( 'Product', 'gift-cards-on-demand-free' ),
	      __( 'Purchase Date', 'gift-cards-on-demand-free' ),
	      __( 'Expire Date', 'gift-cards-on-demand-free' ),
	      __( 'Status', 'gift-cards-on-demand-free' ),
		  __( 'Order ID', 'gift-cards-on-demand-free' )
        );
        
        fputcsv( $f, $line, $delimiter );
        
        $search = '';
        $status = '';
		        
        if ( isset( $_GET['search'] ) ) {
          $search = sanitize_text_field( $_GET['search'] );
        }
        if ( isset( $_GET['status'] ) ) {
          $status = sanitize_text_field( $_GET['status'] );
        }
        
        $term_array = $search ? explode( ',', $search ) : array();
                
        $query  = "SELECT * FROM {$wpdb->prefix}wcpt_vouchers";
        $p_args = array();
        
        if ( $term_array || $status ) {
	        $query .= " WHERE ";
	        $first  = true;
        }
        
        $view = isset( $_GET['pl_view'] ) ? sanitize_text_field( $_GET['pl_view'] ) : '';
        
        if ( $term_array ) {
	        foreach ( $term_array as $term_val ) {
		        
		        $term_clean = trim( $term_val );
		        if ( $first ) {
			        if ( $view == 'orders' ) {
				        $query .= "(  order_id LIKE %s";
				        $p_args[] = "%$term_clean%";
			        }
			        else {
	        			$query .= "( voucher_serial LIKE %s OR voucher_pin LIKE %s OR order_id LIKE %s OR product_name LIKE %s";
	        			$p_args[] = "%$term_clean%";
	        			$p_args[] = "%$term_clean%";
	        			$p_args[] = "%$term_clean%";
	        			$p_args[] = "%$term_clean%";
	        		}
	        		$first = false;
		        }
		        else {
			        if ( $view == 'orders' ) {
				        $query .= " OR order_id LIKE %s";
				        $p_args[] = "%$term_clean%";
			        }
			        else {
		        		$query .= " OR order_id LIKE %s";
		        		$p_args[] = "%$term_clean%";
			        }
		        }
	        }
	        
	        $query .= ' )';
        }
        
        if ( $status ) {
	        $sold = ( $status == 'sold' ) ? 1 : 0;
	        if ( $first ) {
		        if ( $sold ) {
	        		$query .= "sold=%d";
		        	$p_args[] = $sold;
		        }
		        else {
			        $query .= "sold IS NULL";
		        }
	        }
	        else {
		        if ( $sold ) {
			        $query .= " AND sold=%d";
			        $p_args[] = $sold;
		        }
		        else {
			        $query .= " AND sold IS NULL";
		        }
	        }
        }
                   
        if ( $p_args ) {
	        $stocks = $wpdb->get_results( $wpdb->prepare( $query, $p_args ), OBJECT );
        } else {                 
        	$stocks = $wpdb->get_results( $query, OBJECT );
        }
         
        foreach ( $stocks as $stock ) {
	        
		    $status = $stock->sold     ? 'sold'         : 'instock';
            $order  = $stock->order_id ? $stock->order_id : 'N/A';
                    
	        $line  = array(
	            esc_attr( $stock->voucher_pin ),
	            esc_attr( $stock->voucher_serial ),
	            esc_attr( $stock->product_name ),
	            esc_attr( $stock->purchase_date ),
	            esc_attr( $stock->expires ),
	            esc_attr( $status ),
	            esc_attr( $order ),
			);
			
	        fputcsv( $f, $line, $delimiter );
        }
        
	    /**
	     * rewrind the 'file' with the csv lines *
	     */
	    fseek( $f, 0 );

	    fpassthru( $f );
	    die();
    }
}
