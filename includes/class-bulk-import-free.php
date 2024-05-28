<?php

if ( !defined( 'ABSPATH' ) ) {
    exit(); // Exit if accessed directly
}

class PL_WCPT_Bulk_Import_Controller {

    public function __construct() {}
            
    public function display() {	
	    $this->display_page_1();
    }
        
    public function display_page_1() { ?>
	    <div class="wrap">
	
			<h2><?php esc_attr_e( 'Bulk Import', 'gift-cards-on-demand-free'); ?></h2>
			<p><?php esc_attr_e( 'This feature is only available on the pro version.', 'gift-cards-on-demand-free' ); ?></p><?php			    	    
    }
}