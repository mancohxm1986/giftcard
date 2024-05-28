<?php
	
if ( !defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly
}

/**
* This class handles module database instalation 
**/

class PL_WCPT_Dabatase_Install {
	
	public $db_version = 2.1;
	
	public function __construct() {
		
    	$this->update_db_check();
	}

	public function install() {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		try {
			global $wpdb;
			
			// Database names
			$table_oem 	   	    = $wpdb->prefix . 'wcpt_oem';
			$table_brands   	= $wpdb->prefix . 'wcpt_brand';
			$table_products 	= $wpdb->prefix . 'wcpt_products';
			$table_vouchers 	= $wpdb->prefix . 'wcpt_vouchers';
			$table_transactions = $wpdb->prefix . 'wcpt_transactions';
			
			// Use wpdb Charset collation
			$charset_collate = $wpdb->get_charset_collate();
							        	        
	        // Create oem table
	        $sql_oem = "CREATE TABLE IF NOT EXISTS $table_oem (
		    	    oem_id INT NOT NULL,
		    	    name VARCHAR(255),
			        website VARCHAR(255),
					product_count INT NOT NULL,
					PRIMARY KEY oem_id (oem_id)
			) $charset_collate;";
	
	        dbDelta( $sql_oem );
	        
	        // Create brands table
	        $sql_brands = "CREATE TABLE IF NOT EXISTS $table_brands (
		    	    brand_id INT NOT NULL,
		    	    oem_id INT NOT NULL,
			        name VARCHAR(255),
					product_count INT NOT NULL,
					instructions TEXT NOT NULL,
					pin_wording TEXT NOT NULL,
					serial_wording TEXT NOT NULL,
					PRIMARY KEY brand_id (brand_id)
			) $charset_collate;";
			
			dbDelta( $sql_brands );
			
			// Create products table
	        $sql_products = "CREATE TABLE IF NOT EXISTS $table_products (
		    	    product_id INT NOT NULL,
		    	    oem_id INT NOT NULL,
		    	    brand_id INT NOT NULL,
			        brand_name VARCHAR(255),
			        product_name VARCHAR(255),
			        vvsku VARCHAR(255),
			        base_currency VARCHAR(255),
			        base_currency_symbol VARCHAR(5),
			        unit_price FLOAT,
			        sell_price FLOAT,
			        stock_available INT,
				    instructions TEXT NOT NULL,
				    image_url TEXT NOT NULL,
				    discontinued BIT DEFAULT 0,
					PRIMARY KEY product_id (product_id)
			) $charset_collate;";
	
	        dbDelta( $sql_products );
	        
	        // Create vouchers table
	        $sql_vouchers = "CREATE TABLE IF NOT EXISTS $table_vouchers (
		    	    voucher_id INT NOT NULL,
		    	    voucher_pin VARCHAR(255) NOT NULL,
			        voucher_serial VARCHAR(255),
			        product_id INT NOT NULL,
			        product_name VARCHAR(255),
			        sell_price FLOAT,
			        sales_id INT NOT NULL,
			        order_id INT,
			        transaction_status VARCHAR(255),
			        transaction_id INT,
			        purchase_date DATETIME,
			        expires DATE,
			        sold VARCHAR(15),		        
					PRIMARY KEY voucher_id (voucher_id)
			) $charset_collate;";
	
	        dbDelta( $sql_vouchers );
	        
	        // Create transactions table
	        $sql_transactions = "CREATE TABLE IF NOT EXISTS $table_transactions (
		        transaction_id INT,
		        product_id INT NOT NULL,
		        quantity	INT NOT NULL,
		        total FLOAT,
		        date DATETIME,
		        status VARCHAR(255),
		        serial TEXT,
		        balance DECIMAL(20,2),
			    PRIMARY KEY transaction_id (transaction_id)
			) $charset_collate;";
	
	        dbDelta( $sql_transactions );
	        
	        do_action( 'pl_database_updated', $this->db_version );
	        		        	        	        	        
	        update_option( 'pl_wcpt_paythem_db_version', $this->db_version );
	        
        } catch ( Exception $e ) {}
	}

	// Check if database needs update
	public function update_db_check() {
		
		// If database version is different from the current one
	    if ( get_option( 'pl_wcpt_paythem_db_version' ) != $this->db_version ) {
	        $this->install();
	    }		
	}
}