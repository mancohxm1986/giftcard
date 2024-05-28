<?php
	
if (! defined('ABSPATH')) {
    exit();
}

/**
* This class handles Brands database queries
**/

class PL_WCPT_Brands_DB extends PL_WCPT_Database {

    public function __construct() {
	    global $wpdb;
        $this->tableName = $wpdb->prefix . 'wcpt_brand';
    }
    
    public function add_brands( $brands ) {
	    
	    global $wpdb;
	    	    
	    $paging  = array();
	    $base    = 0;
	    foreach ( $brands as $k => $brand ) {
		    
		    if ( $k % 20 == 0 ) {
			    $base++;
		    }
		    
		    $paging[ $base ][] = $brand;
	    }
	    
	    foreach ( $paging as $v_manu ) {
		    if ( $v_manu ) {
			    $p_args  = array();
			    $insert_vals = 'INSERT INTO ' . $wpdb->prefix . 'wcpt_brand' . ' (
							    oem_id,
							    brand_id,
							    name,
							    product_count,
							    instructions,
							    pin_wording,
							    serial_wording
							)
							VALUES';
				foreach ( $v_manu as $k => $m_insert ) {
					
					$oem_id 	    = is_numeric( $m_insert['OEM_ID'] ) ? $m_insert['OEM_ID'] : 0;
					$brand_id 	    = is_numeric( $m_insert['OEM_BRAND_ID'] ) ? $m_insert['OEM_BRAND_ID'] : 0;					
					$name   	    = sanitize_text_field( $m_insert['OEM_BRAND_Name'] );
					$product_count  = is_numeric( $m_insert['OEM_BRAND_ProductCount'] ) ? $m_insert['OEM_BRAND_ProductCount'] : 0;
					$instructions   = $wpdb->_real_escape( $m_insert['OEM_BRAND_RedeemInstructions'] );
					$pin_wording    = sanitize_text_field( $m_insert['OEM_BRAND_PINWording'] );
					$serial_wording = sanitize_text_field( $m_insert['OEM_BRAND_SerialWording'] );
					
					if ( $k ) {
						$insert_vals .= ",";
					}
					$insert_vals .= "( %d, %d, %s, %d, %s, %s, %s )";
					$p_args[] = $oem_id;
					$p_args[] = $brand_id;
					$p_args[] = $name;
					$p_args[] = $product_count;
					$p_args[] = $instructions;
					$p_args[] = $pin_wording;
					$p_args[] = $serial_wording;
				}
				
				$insert_vals .= ' ON DUPLICATE KEY UPDATE brand_id=VALUES(brand_id)';								
				$wpdb->query( $wpdb->prepare( $insert_vals, $p_args ) );
			}
	    }
    }
}