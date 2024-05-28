<?php

if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

?>

<style>

	@media screen and (max-width: 600px) {
	table {
    	border: 0;
  	}
  	
  	table caption {
    	font-size: 1.3em;
  	}
  	
  	table thead {
	    border: none;
	    clip: rect(0 0 0 0);
	    height: 1px;
	    margin: -1px;
	    overflow: hidden;
	    padding: 0;
	    position: absolute;
	    width: 1px;
  	}
  	
  	table tr {
	    border-bottom: 3px solid #ddd;
	    display: block;
	    margin-bottom: .625em;
	}
	
	table td {
	    border-bottom: 1px solid #ddd;
	    display: block;
	    font-size: .8em;
	    text-align: right;
	}
	
	table td:before {
	    content: attr(data-label);
	    float: left;
	    font-weight: bold;
	    text-transform: uppercase;
	}
	
	table td:last-child {
    	border-bottom: 0;
  	}
	
	.tablenav.top {
	    display: block;
	}
	
	tfoot {
		display: none;
	}
	
	#it-rst-filter-stocklog-warehouse-top {
		width: 100% !important;
		margin-bottom: 5px;
	}
	
	.search_buttons {
		margin-left: 0 !important;
	}
	
	.inside.settings .row {
		margin-left: 0 !important;
		margin-right: 0 !important;
	}
	
	.content_settings {
		padding: 30px 20px !important;
	}
}

.error {
    border-left-color: #F15C46 !important;
    border-radius: 5px !important;
    background: #fafafa !important;
    border-right: 1px solid #eee !important;
    border-top: 1px solid #eee !important;
    border-bottom: 1px solid #eee !important;
    box-shadow: none !important;
}	

.updated {
    border-left-color: #43A546 !important;
    border-radius: 5px !important;
    background: #fafafa !important;
    border-right: 1px solid #eee !important;
    border-top: 1px solid #eee !important;
    border-bottom: 1px solid #eee !important;
    box-shadow: none !important;
}
	
</style>

<div class="wrap">
	
	<h2><?php esc_html_e( 'Products', 'gift-cards-on-demand-free'); ?></h2>
  
    <form id="it-rooster-list-warehouses-filter" method="get">
    	<input type="hidden" name="page"
    		value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['page'] ) ) ?>" />
    <?php
	    	    
    $list_table = new PL_WCPT_Products_List_Table();
    $action = $list_table->process_action();
    
    switch( $action ) {
      default:
        $list_table->show_message();
        $list_table->prepare_items();
        $list_table->display();
      break;
    }
    ?>
    </form>
</div>						
