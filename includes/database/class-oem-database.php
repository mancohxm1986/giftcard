<?php
	
if (! defined('ABSPATH')) {
    exit();
}

/**
* This class handles OEM database queries
**/

class PL_WCPT_OEM_DB extends PL_WCPT_Database {

    public function __construct() {
	    
	    global $wpdb;
        $this->tableName = $wpdb->prefix . 'wcpt_oem';
    }
    
    public function add_oems( $manufacturers ) {
	    
	    global $wpdb;
	    	    
	    $paging  = array();
	    $base    = 0;
	    foreach ( $manufacturers as $k => $manufacturer ) {
		    
		    if ( $k % 20 == 0 ) {
			    $base++;
		    }
		    
		    $paging[ $base ][] = $manufacturer;
	    }
	    
	    foreach ( $paging as $v_manu ) {
		    if ( $v_manu ) {
			    $p_args = array();
			    $insert_vals = 'INSERT INTO ' . $wpdb->prefix . 'wcpt_oem' . ' (
							    oem_id,
							    name,
							    website,
							    product_count
							)
							VALUES';
				foreach ( $v_manu as $k => $m_insert ) {
					
					$oem_id 	   = is_numeric( $m_insert['OEM_ID'] ) ? $m_insert['OEM_ID'] : 0;
					$name   	   = sanitize_text_field( $m_insert['OEM_Name'] );
					$website       = sanitize_text_field( $m_insert['OEM_Website'] );
					$product_count = is_numeric( $m_insert['OEM_ProductCount'] ) ? $m_insert['OEM_ProductCount'] : 0;
					
					if ( $k ) {
						$insert_vals .= ",";
					}
					$insert_vals .= "( %d, %s, %s, %d )";
					
					$p_args[] = $oem_id;
					$p_args[] = $name;
					$p_args[] = $website;
					$p_args[] = $product_count;
				}
				
				$insert_vals .= ' ON DUPLICATE KEY UPDATE oem_id=VALUES(oem_id)';				
				$wpdb->query( $wpdb->prepare( $insert_vals, $p_args ) );
			}
	    }
    }
}