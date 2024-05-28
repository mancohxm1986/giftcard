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

<?php 
	$date_1       	 = isset( $_REQUEST['pl-search-date1-top'] ) ? sanitize_text_field( $_REQUEST['pl-search-date1-top'] ) : date( 'Y-m-d', strtotime( '- 1 month' ) );
	$date_2       	 = isset( $_REQUEST['pl-search-date2-top'] ) ? sanitize_text_field( $_REQUEST['pl-search-date2-top'] ) : date( 'Y-m-d' );
	$url 		 	 = menu_page_url( "pl-paythem-report-export", false ) . "&date_1=$date_1&date_2=$date_2";
	$current_url      = menu_page_url( "pl-paythem-menu-view-reports", false );
	$transactions_url = $current_url . '&report=transactions';
?>
<div class="wrap">
		
	<h2><?php esc_html_e( 'Transactions', 'gift-cards-on-demand-free'); ?>
	  <a class="add-new-h2"
	    href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( "Export Transactions", "gift-cards-on-demand-free" ); ?></a>
	  </h2>
	  
	<ul class="subsubsub">
		<li class="transactions"><a href="<?php echo esc_url( $transactions_url ); ?>" class="current" aria-current="page">Transaction Report</a></li>
	</ul><br><br>
  
    <form id="it-rooster-list-warehouses-filter" method="get">
    	<input type="hidden" name="page"
    		value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['page'] ) ) ?>" />
    	<?php if ( isset( $_REQUEST['report'] ) ) { ?>
 	<input type="hidden" name="report"
    		value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['report'] ) ) ?>" />
    <?php }
	    	    
    $list_table = new PL_WCPT_Reports_List_Table();
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
