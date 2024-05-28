<?php
	
if (! defined('ABSPATH')) {
    exit();
}

/**
* This class handles Products database queries
**/

class PL_WCPT_Products_DB extends PL_WCPT_Database {

    public function __construct() {
	    
	    global $wpdb;
        $this->tableName = $wpdb->prefix . 'wcpt_products';
    }
    
    public function update_stock( $product_id, $stock ) {
	    
	    global $wpdb;
	    	    
	    $wpdb->update( 
			$this->tableName, 
			array( 
				'stock_available' => $stock,
			), 
			array( 'product_id' => $product_id ), 
			array( 
				'%d'
			) 
		);
    }
    
    public function get_product_stock( $product_id ) {
	    
	    global $wpdb;
	    
	    $stock = $wpdb->get_row( $wpdb->prepare( "SELECT stock_available FROM " . $wpdb->prefix . 'wcpt_products' . " WHERE product_id=%d", $product_id ) );
	    
	    $stock_available = isset( $stock->stock_available ) ? $stock->stock_available : 0;
	    return $stock_available;
    }
    
    public function get_products() {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . 'wcpt_products' . " ORDER BY product_name ASC" );
	    return $products;
    }
    
    public function get_continued_product_ids() {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_col( "SELECT product_id FROM " . $wpdb->prefix . 'wcpt_products' . " WHERE discontinued = 0 ORDER BY product_name ASC" );
	    return $products;
    }
    
    public function get_discontinued_product_ids() {
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_col( "SELECT product_id FROM " . $wpdb->prefix . 'wcpt_products' . " WHERE discontinued = 1 ORDER BY product_name ASC" );
	    return $products;
    }
    
    public function is_product_discontinued( $product_id ) {
	    
	    if ( !$product_id ) {
		    return false;
	    }
	    
	    global $wpdb;
	    
	    $product      = $wpdb->get_col( $wpdb->prepare( "SELECT discontinued FROM " . $wpdb->prefix . 'wcpt_products' . " WHERE product_id=%d ORDER BY product_name ASC", $product_id ) );
	    $discontinued = ( isset( $product[0] ) && $product[0] ) ? true : false;
	    return $discontinued;
    }
    
    public function get_product( $product_id ) {
	    
	    if ( !$product_id ) {
		    return array();
	    }
	    
	    global $wpdb;
	    
	    $products = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . 'wcpt_products' . " WHERE product_id=%d ORDER BY product_name ASC", $product_id ) );
	    return $products;
    }
    
    public function get_product_by_id( $product_id ) {
	    
	    global $wpdb;	    
	    $product = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . 'wcpt_products' . " WHERE product_id=%d", $product_id ) );	    
	    return $product;
    }
    
    public function get_products_by_ids( $product_ids ) {
	    
	    if ( !$product_ids ) {
		    return array();
	    }
	    
	    global $wpdb;
	    $product_ids_placeholder = substr( str_repeat( ',%s', count( $product_ids ) ), 1 );
	    
	    $products_raw = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . 'wcpt_products' . " WHERE product_id IN ($product_ids_placeholder)", $product_ids ) );
	    $products	  = array();
	    
	    foreach( $products_raw as $product_raw ) {
		    $products[ $product_raw->product_id ] = $product_raw;
	    }
	    
	    return $products;
    }
    
    public function discontinue_product_list( $product_ids ) {
	    
	    global $wpdb;
	    $product_ids_placeholder = substr( str_repeat( ',%s', count( $product_ids ) ), 1 );
	    $wpdb->query( $wpdb->prepare( "UPDATE " . $wpdb->prefix . 'wcpt_products' . " SET discontinued = 1 WHERE product_id IN ($product_ids_placeholder)", $product_ids ) );
    }
    
    public function continue_product_list( $product_ids ) {
	    
	    global $wpdb;
	    $product_ids_placeholder = substr( str_repeat( ',%s', count( $product_ids ) ), 1 );
	    $wpdb->query( $wpdb->prepare( "UPDATE " . $wpdb->prefix . 'wcpt_products' . " SET discontinued = 0 WHERE product_id IN ($product_ids_placeholder)", $product_ids ) );
    }
    
    public function check_discontinued_products( $new_prods ) {
	    
	    $c_continued    = $this->get_continued_product_ids();
	    $c_discontinued = $this->get_discontinued_product_ids();
	    
	    $n_discontinued = array_diff( $c_continued, $new_prods );
	    if ( $n_discontinued ) {
	    	$this->discontinue_product_list( $n_discontinued );
	    }
	    
	    $n_continued    = array_intersect( $new_prods, $c_discontinued );
	    if ( $n_continued ) {
		    $this->continue_product_list( $n_continued );
	    }
    }
    
    public function add_products( $products ) {
	    
	    global $wpdb;
	   
		$new_prods = array();
	    $paging    = array();
	    $base      = 0;
	    
	    foreach ( $products as $k => $product ) {
		    
		    if ( $k % 20 == 0 ) {
			    $base++;
		    }
		    
		    $paging[ $base ][] = $product;
	    }
	    
	    foreach ( $paging as $v_manu ) {
		    if ( $v_manu ) {
			    $p_args = array();
			    $insert_vals = 'INSERT INTO ' . $this->tableName . ' (
							    oem_id,
							    brand_id,
							    brand_name,
							    product_id,
							    product_name,
							    vvsku,
							    base_currency,
							    base_currency_symbol,
							    unit_price,
							    sell_price,
							    instructions,
							    image_url,
							    stock_available
							)
							VALUES';
				foreach ( $v_manu as $k => $m_insert ) {
										
					$oem_id 	          = is_numeric( $m_insert['OEM_ID'] ) ? $m_insert['OEM_ID'] : 0;
				    $brand_id	          = is_numeric( $m_insert['OEM_BRAND_ID'] ) ? $m_insert['OEM_BRAND_ID'] : 0;
				    $brand_name   		  = sanitize_text_field( $m_insert['OEM_BRAND_Name'] );
				    $product_id   		  = is_numeric( $m_insert['OEM_PRODUCT_ID'] ) ? $m_insert['OEM_PRODUCT_ID'] : 0;
				    $product_name         = sanitize_text_field( $m_insert['OEM_PRODUCT_Name'] );
				    $vvsku		          = sanitize_text_field( $m_insert['OEM_PRODUCT_VVSSKU'] );
				    $base_currency		  = sanitize_text_field( $m_insert['OEM_PRODUCT_BaseCurrency'] );
				    $base_currency_symbol = sanitize_text_field( $m_insert['OEM_PRODUCT_BaseCurrencySymbol'] );
				    $unit_price           = is_numeric( $m_insert['OEM_PRODUCT_UnitPrice'] ) ? $m_insert['OEM_PRODUCT_UnitPrice'] : 0;
				    $sell_price	          = is_numeric( $m_insert['OEM_PRODUCT_SellPrice'] ) ? $m_insert['OEM_PRODUCT_SellPrice'] : 0;
				    $instructions		  = sanitize_text_field( $m_insert['OEM_PRODUCT_RedemptionInstructions'] );
				    $image_url			  = sanitize_text_field( $m_insert['OEM_PRODUCT_ImageURL'] );
				    $stock_available	  = is_numeric( $m_insert['OEM_PRODUCT_Available'] ) ? $m_insert['OEM_PRODUCT_Available'] : 0;
					
					if ( $k ) {
						$insert_vals .= ",";
					}
					$insert_vals .= "( %d, %d, %s, %d, %s, %s, %s, %s, %f, %f, %s, %s, %d )";
					
					$p_args[] = $oem_id;
					$p_args[] = $brand_id;
					$p_args[] = $brand_name;
					$p_args[] = $product_id;
					$p_args[] = $product_name;
					$p_args[] = $vvsku;
					$p_args[] = $base_currency;
					$p_args[] = $base_currency_symbol;
					$p_args[] = $unit_price;
					$p_args[] = $sell_price;
					$p_args[] = $instructions;
					$p_args[] = $image_url;
					$p_args[] = $stock_available;
					
					if ( !in_array( $product_id, $new_prods ) ) {
						$new_prods[] = $product_id;
					}
				}
				
				$insert_vals .= ' ON DUPLICATE KEY UPDATE product_id=VALUES(product_id), 
								oem_id=VALUES(oem_id),
							    brand_id=VALUES(brand_id),
							    brand_name=VALUES(brand_name),
							    product_name=VALUES(product_name),
							    vvsku=VALUES(vvsku),
							    base_currency=VALUES(base_currency),
							    base_currency_symbol=VALUES(base_currency_symbol),
							    unit_price=VALUES(unit_price),
							    sell_price=VALUES(sell_price),
							    instructions=VALUES(instructions),
							    image_url=VALUES(image_url),
							    stock_available=VALUES(stock_available)';
				
				$wpdb->query( $wpdb->prepare( $insert_vals, $p_args ) );
			}
	    }
	    
	    $this->check_discontinued_products( $new_prods );
    }
}