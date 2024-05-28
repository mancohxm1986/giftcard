<?php

if ( !defined( 'ABSPATH' ) ) {
    exit(); // Exit if accessed directly
}

class PL_WCPT_Export_Reports_Controller {

    public function __construct() {}

    public function exportCSV() {
	    		
		ob_clean();
		
		global $wpdb;
	    
		header( 'Content-Type: application/csv' );
	    header( 'Content-Disposition: attachment; filename="reports.csv";' );

	    $delimiter = ',';
        
        /**
         * open raw memory as file, no need for temp files, be careful not to run out of memory thought
         */
        $f = tmpfile();

        $line = array(
          __( 'Date', 'gift-cards-on-demand-free' ),
	      __( 'Product', 'gift-cards-on-demand-free' ),
	      __( 'Quantity', 'gift-cards-on-demand-free' ),
	      __( 'Total', 'gift-cards-on-demand-free' ),
	      __( 'Status', 'gift-cards-on-demand-free' ),
	      __( 'Transaction ID', 'gift-cards-on-demand-free' ),
	      __( 'Serials', 'gift-cards-on-demand-free' ),
	      __( 'Balance', 'gift-cards-on-demand-free' )
        );
        
        fputcsv( $f, $line, $delimiter );
        
        $date_1  = ( isset( $_GET['date_1'] ) && $_GET['date_1'] ) ? sanitize_text_field( $_GET['date_1'] ) : date( 'Y-m-d', strtotime( '-20 years' ) );
        $date_2  = ( isset( $_GET['date_2'] ) && $_GET['date_2'] ) ? sanitize_text_field( $_GET['date_2'] ) : date( 'Y-m-d', strtotime( '+1 month' ) );
        $orderby = ( isset( $_GET['orderby'] ) && $_GET['orderby'] ) ? ( sanitize_text_field( $_GET["orderby"] ) ) : 'date';
        $order 	 = ( isset( $_GET['order'] ) && $_GET['order'] ) ? ( sanitize_text_field( $_GET["order"] ) ) : 'DESC';

        $status = 'Voucher sale';
		$date_2 = $date_2 . ' 23:59:59';                        
        $query  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wcpt_transactions WHERE status != %s AND date > %s && date < %s ORDER BY $orderby $order", $status, $date_1, $date_2 );
        $reports  = $wpdb->get_results( $query, OBJECT );
        $balances = PL_WCPT_Reports_List_Table::get_initial_balance();
                 
        foreach ( $reports as $report ) {
	        
	        $title 		 = '-';
	        if ( $report->product_id ) {
			    $args = array(
			        'post_type'      => 'product',
			        'post_status'	    => 'publish',
			        'posts_per_page' => 1,
			        'fields'		    => 'ids',
			        'meta_query' => array(
			            array(
			                'key' => '_pt_product',
			                'value' => $report->product_id
			            )
			        )
			   );
			   
	            $products = get_posts( $args );
	            
	            if ( $products ) {
	                
	                $product = wc_get_product( $products[0] ); 
	                $title	 = $product->get_title();
	            }
            }
            
            $serial  = $report->serial;
            $serial  = str_replace( ', ', ' | ', $serial );
            $balance = isset( $balances[ $report->transaction_id ] ) ? $balances[ $report->transaction_id ] : '';
            
            $total   = number_format( (float) $report->total, 2, '.', '' );
            $balance = number_format( (float) $balance, 2, '.', '' );
                                
	        $line  = array(
	            esc_attr( $report->date ),
	            esc_attr( $title ),
	            esc_attr( $report->quantity ),
	            esc_attr( $total ),
	            esc_attr( $report->status ),
	            esc_attr( $report->transaction_id ),
	            esc_attr( $serial ),
	            esc_attr( $balance ),
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
