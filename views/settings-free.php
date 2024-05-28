<?php
	
if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

$pl_wcpt_field_errors = '';
$pl_wcpt_field_success = '';

$max_items 						 	   = get_option( 'pl_wcpt_api_purchase_limit', 20 );
$pl_wcpt_api_per_product_limit		   = $max_items;
$pl_wcpt_api_username    			   = get_option( 'pl_wcpt_api_username' );
$pl_wcpt_api_oldusername 			   = $pl_wcpt_api_username;
$pl_wcpt_api_last_currency		       = get_option( 'pl_wcpt_api_last_currency' );
$pl_wcpt_api_password    			   = get_option( 'pl_wcpt_api_password' );
$pl_wcpt_api_public_key  			   = get_option( 'pl_wcpt_api_public_key' );
$pl_wcpt_api_private_key 			   = get_option( 'pl_wcpt_api_private_key' );
$pl_wcpt_api_environment 			   = get_option( 'pl_wcpt_api_environment' );
$pl_wcpt_api_oldenvironment			   = $pl_wcpt_api_environment;
$pl_wcpt_api_currency_conversion_value = ( get_option( 'pl_wcpt_api_currency_conversion_value' ) ) ? get_option( 'pl_wcpt_api_currency_conversion_value' ) : 1;
$pl_wcpt_api_code_release	           = ( get_option( 'pl_wcpt_api_code_release' ) ) ? get_option( 'pl_wcpt_api_code_release' ) : 'processing';
$pl_wcpt_api_code_release_regular 	   = get_option( 'pl_wcpt_api_code_release_regular_email', 'yes' );
$pl_wcpt_api_currency_conversion	   = ( get_option( 'pl_wcpt_api_currency_conversion' ) ) ? get_option( 'pl_wcpt_api_currency_conversion' ) : 'manual';

$request_method = isset( $_SERVER["REQUEST_METHOD"] ) ? sanitize_text_field( $_SERVER["REQUEST_METHOD"] ) : '';
if ( $request_method == "POST" ) {
	
   	$nonce = isset( $_REQUEST['_wcpt_nonce'] ) ? sanitize_text_field( $_REQUEST['_wcpt_nonce'] ) : '';
   	if ( wp_verify_nonce( $nonce, 'pl-wcpt-settings-edit' . get_current_user_id() ) ) {
	  if ( isset( $_POST['pl_wcpt_api_username'] ) ) {
		$pl_wcpt_api_username = sanitize_text_field( $_POST['pl_wcpt_api_username'] );
	    update_option( 'pl_wcpt_api_username', $pl_wcpt_api_username );
	    $pl_wcpt_field_success = __( 'Settings updated', 'gift-cards-on-demand-free' );
	  }
	  
	  if ( isset( $_POST['pl_wcpt_api_password'] ) ) {
		$pl_wcpt_api_password = sanitize_text_field( $_POST['pl_wcpt_api_password'] );
	    update_option( 'pl_wcpt_api_password', $pl_wcpt_api_password );
	    $pl_wcpt_field_success = __( 'Settings updated', 'gift-cards-on-demand-free' );
	  }
	  
	  if ( isset( $_POST['pl_wcpt_api_public_key'] ) ) {
		$pl_wcpt_api_public_key = sanitize_text_field( $_POST['pl_wcpt_api_public_key'] );
	    update_option( 'pl_wcpt_api_public_key', $pl_wcpt_api_public_key );
	    $pl_wcpt_field_success = __( 'Settings updated', 'gift-cards-on-demand-free' );
	  }
	  
	  if ( isset( $_POST['pl_wcpt_api_private_key'] ) ) {
		$pl_wcpt_api_private_key = sanitize_text_field( $_POST['pl_wcpt_api_private_key'] );
	    update_option( 'pl_wcpt_api_private_key', $pl_wcpt_api_private_key );
	    $pl_wcpt_field_success = __( 'Settings updated', 'gift-cards-on-demand-free' );
	  }
	  
	  if ( isset( $_POST['pl_wcpt_api_environment'] ) ) {
		$pl_wcpt_api_environment = sanitize_text_field( $_POST['pl_wcpt_api_environment'] );
	    update_option( 'pl_wcpt_api_environment', $pl_wcpt_api_environment );
	    $pl_wcpt_field_success = __( 'Settings updated', 'gift-cards-on-demand-free' );
	  }
	  
	  if ( isset( $_POST['pl_wcpt_api_currency_conversion_value'] ) ) {
		$new_conversion_value = sanitize_text_field( $_POST['pl_wcpt_api_currency_conversion_value'] );
		$old_conversion_value = $pl_wcpt_api_currency_conversion_value;
	    update_option( 'pl_wcpt_api_currency_conversion_value', $new_conversion_value );
	    $pl_wcpt_api_currency_conversion_value = $new_conversion_value;
	    $pl_wcpt_field_success = __( 'Settings updated', 'gift-cards-on-demand-free' );
	    
	    if ( $new_conversion_value !== $old_conversion_value ) {
		    do_action( 'pl_pt_updated_conversion_value' );
	    }
	  }
	                  
	  if ( isset( $_POST['pl_wcpt_api_code_release'] ) ) {
		$pl_wcpt_api_code_release = sanitize_text_field( $_POST['pl_wcpt_api_code_release'] );
	    update_option( 'pl_wcpt_api_code_release', $pl_wcpt_api_code_release );
	    $pl_wcpt_field_success = __( 'Settings updated', 'gift-cards-on-demand-free' );
	  }
	  
	  if ( isset( $_POST['pl_wcpt_api_code_release_regular_email'] ) ) {
		$pl_wcpt_api_code_release_regular = sanitize_text_field( $_POST['pl_wcpt_api_code_release_regular_email'] );
	    update_option( 'pl_wcpt_api_code_release_regular_email', $pl_wcpt_api_code_release_regular );
	    $pl_wcpt_field_success = __( 'Settings updated', 'gift-cards-on-demand-free' );
	  }
	            
	  if ( isset( $_POST['pl_wcpt_api_currency_conversion'] ) ) {
		$old_api_currency_conversion = $pl_wcpt_api_currency_conversion;
		$pl_wcpt_api_currency_conversion = sanitize_text_field( $_POST['pl_wcpt_api_currency_conversion'] );
	    update_option( 'pl_wcpt_api_currency_conversion', $pl_wcpt_api_currency_conversion );
	    $pl_wcpt_field_success = __( 'Settings updated', 'gift-cards-on-demand-free' );    
	  }
  }
}

$selected_tab  = isset( $_POST['pl_tab'] ) ? sanitize_text_field( $_POST['pl_tab'] ) : 'pl_nav_tab_1';
$tab_1_display = ( $selected_tab == 'pl_nav_tab_1' ) ? '' : 'style="display:none"';
$tab_2_display = ( $selected_tab == 'pl_nav_tab_2' ) ? '' : 'style="display:none"';
$tab_3_display = ( $selected_tab == 'pl_nav_tab_3' ) ? '' : 'style="display:none"';
$tab_4_display = ( $selected_tab == 'pl_nav_tab_4' ) ? '' : 'style="display:none"';
$tab_5_display = ( $selected_tab == 'pl_nav_tab_5' ) ? '' : 'style="display:none"';
$tab_6_display = ( $selected_tab == 'pl_nav_tab_6' ) ? '' : 'style="display:none"';

$tab_1_active = ( $selected_tab == 'pl_nav_tab_1' ) ? 'nav-tab-active' : '';
$tab_2_active = ( $selected_tab == 'pl_nav_tab_2' ) ? 'nav-tab-active' : '';
$tab_3_active = ( $selected_tab == 'pl_nav_tab_3' ) ? 'nav-tab-active' : '';
$tab_4_active = ( $selected_tab == 'pl_nav_tab_4' ) ? 'nav-tab-active' : '';
$tab_5_active = ( $selected_tab == 'pl_nav_tab_5' ) ? 'nav-tab-active' : '';
$tab_6_active = ( $selected_tab == 'pl_nav_tab_6' ) ? 'nav-tab-active' : '';

// Get account ballance data
$pt_api   		  = new PL_WCPT_API();
$account_ballance = $pt_api->get_account_ballance();
$pt_api->get_purchase_limit();

$status  = ( $account_ballance && isset( $account_ballance['RESELLER_Balance'] ) && isset( $account_ballance['RESELLER_Currency'] ) ) ? 'Online' : 'Offline';
$balance = ( $account_ballance && isset( $account_ballance['RESELLER_Balance'] ) && isset( $account_ballance['RESELLER_Currency'] ) ) ? $account_ballance['RESELLER_Balance'] . ' ' . $account_ballance['RESELLER_Currency'] : '';

if ( $status == 'Online' ) {
	
	if ( $pl_wcpt_api_oldusername && $pl_wcpt_api_oldenvironment ) {
		if ( $pl_wcpt_api_oldusername !== $pl_wcpt_api_username || $pl_wcpt_api_oldenvironment !== $pl_wcpt_api_environment ) {
						
			$financial_db    = new PL_WCPT_Financial_DB();
			$transactions_db = new PL_WCPT_Transactions_DB();
			$vouchers_db     = new PL_WCPT_Vouchers_DB();
			
			$financial_db->delete_transactions();
			$transactions_db->delete_transactions();
			$vouchers_db->delete_vouchers();
			
			if ( wp_next_scheduled ( 'pl_sync_products' ) ) {
				wp_clear_scheduled_hook( 'pl_sync_products' );
			}
			if ( wp_next_scheduled ( 'pl_sync_transactions' ) ) {
				wp_clear_scheduled_hook( 'pl_sync_transactions' );
			}
			
			delete_option( 'pl_wcpt_force_transaction_last' );
			delete_option( 'pl_wcpt_transaction_last' );			
		}
	}
}

$qa_chk 	 		 = ( !$pl_wcpt_api_environment || $pl_wcpt_api_environment == 'qa' ) ? 'checked' : '';
$live_chk 		 	 = ( $pl_wcpt_api_environment == 'live' ) 							? 'checked' : '';
$cc_man_chk 	     = 'checked';
$cr_pr_chk 	     	 = ( !$pl_wcpt_api_code_release || $pl_wcpt_api_code_release == 'processing' )   ? 'checked' : '';
$cr_co_chk           = ( $pl_wcpt_api_code_release == 'completed' )   					       ? 'checked' : '';
if ( !$pl_wcpt_field_errors ) {
	$account_currency = get_option( 'pl_wcpt_api_account_currency' );
	$store_currency   = get_option( 'woocommerce_currency' );
}

$external_url = 'https://paythem.net/gift-card-plugin-woocommerce/pro/';
?>

<style>
.input-add-field {
	width: 20em !important;
}
</style>

<div class="wrap">

  <?php if( $pl_wcpt_field_errors !== '' ) { ?>
  	<div class="error">
		<p><?php echo esc_attr( $pl_wcpt_field_errors ); ?></p>
	</div>
  <?php
  } if( $pl_wcpt_field_success !== '' ) { ?>
  	<div class="updated">
		<p><?php echo esc_attr( $pl_wcpt_field_success ); ?></p>
	 </div>
  <?php } ?>
  <h2><?php esc_html_e( 'PayThem Settings', 'gift-cards-on-demand-free' ); ?></h2>
  <form method="post" action="" id="pl_wcpt_settings_form" name="pl_wcpt_settings_form">
		  <nav class="nav-tab-wrapper woo-nav-tab-wrapper pl_nav_tab">
		   <a href="#tab1" class="nav-tab <?php echo esc_attr( $tab_1_active ); ?>" data-tab="pl_nav_tab_1">Accounts</a>
		   <a href="#tab2" class="nav-tab <?php echo esc_attr( $tab_2_active ); ?>" data-tab="pl_nav_tab_2">API</a>
		   <a href="#tab3" class="nav-tab <?php echo esc_attr( $tab_3_active ); ?>" data-tab="pl_nav_tab_3">Pricing</a>
		   <a href="#tab4" class="nav-tab <?php echo esc_attr( $tab_4_active ); ?>" data-tab="pl_nav_tab_4">Logic</a>
		   <a href="#tab5" class="nav-tab <?php echo esc_attr( $tab_5_active ); ?>" data-tab="pl_nav_tab_5">Notifications</a>
		   <a href="#tab6" class="nav-tab <?php echo esc_attr( $tab_6_active ); ?>" data-tab="pl_nav_tab_6">PRO</a>
		  </nav>
		<table <?php echo wp_kses_post( $tab_1_display ); ?> class="form-table pl_nav_tab_table pl_nav_tab_1">
			<tbody>
				<tr class="form-field"><td>PayThem API – If you do not already have your PayThem API credentials, <a target="_blank" href="https://paythem.net/gift-card-plugin-woocommerce/">apply here</a>.</td></tr>
				<tr class="form-field"><td>FREE Open Exchange Rates Account – To automate PayThem pricing into your Store’s currency, signup for your free Open Exchange Rates account <a target="_blank" href="https://openexchangerates.org/signup?r=paythem">here</a>.</td></tr>
				<tr class="form-field"><td>FREE FraudLabs Pro Account – To automate the release of orders to trusted customers, <a target="_blank" href="http://www.fraudlabspro.com/?ref=10475">signup for a free FraudLabs Pro Account here</a>.</td></tr>
				<tr><td></td></tr>
			</tbody>
		</table>
		<table <?php echo wp_kses_post( $tab_2_display ); ?> class="form-table pl_nav_tab_table pl_nav_tab_2">
			<tbody>
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_username"><?php esc_html_e( "Username", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Insert API Login Username.", "gift-cards-on-demand-free" ); ?></span></div><input autocomplete="off" type="text" name="pl_wcpt_api_username" id="pl_wcpt_api_username" class="input-add-field" value="<?php echo esc_attr( $pl_wcpt_api_username ); ?>"/></td>
				</tr>
				
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_password"><?php esc_html_e( "Password", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Insert API Login Password.", "gift-cards-on-demand-free" ); ?></span></div><input autocomplete="off" type="password" name="pl_wcpt_api_password" id="pl_wcpt_api_password" class="input-add-field" value="<?php echo esc_attr( $pl_wcpt_api_password ); ?>"/></td>
				</tr>
				
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_public_key"><?php esc_html_e( "Public Key", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Insert API Public Key.", "gift-cards-on-demand-free" ); ?></span></div><input autocomplete="off" type="text" name="pl_wcpt_api_public_key" id="pl_wcpt_api_public_key" class="input-add-field" value="<?php echo esc_attr( $pl_wcpt_api_public_key ); ?>"/></td>
				</tr>
				
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_private_key"><?php esc_html_e( "Private Key", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Insert API Private Key.", "gift-cards-on-demand-free" ); ?></span></div><input autocomplete="off" type="text" name="pl_wcpt_api_private_key" id="pl_wcpt_api_private_key" class="input-add-field" value="<?php echo esc_attr( $pl_wcpt_api_private_key ); ?>"/></td>
				</tr>
				
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_environment"><?php esc_html_e( "Environment", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Use Test enviroment for testing.", "gift-cards-on-demand-free" ); ?></span></div>
<input <?php echo esc_attr( $qa_chk ); ?> type="radio" name="pl_wcpt_api_environment" value="qa"> <?php esc_html_e( 'Test', 'gift-cards-on-demand-free' ); ?>&nbsp;&nbsp;<input <?php echo esc_attr( $live_chk ); ?> type="radio" name="pl_wcpt_api_environment" value="live"> <?php esc_html_e( 'Live', 'gift-cards-on-demand-free' ); ?></td>
				</tr>
				
				<tr class="form-field">
					<th scope="row"></th>
					<td><div class="pl_wcpt_settings_status_balance">
				      	<p><?php esc_html_e( 'Status', 'gift-cards-on-demand-free' ); ?>: <?php echo esc_attr( $status );?></p>
				    	<?php if ( $balance ) { ?>
					    	<p><?php esc_html_e( 'Balance', 'gift-cards-on-demand-free' ); ?>: <?php echo esc_attr( $balance );?></p>
				    	<?php } ?>
					</div></td>
				</tr>
				<tr><th><input type="submit" name="submit" class="button-primary" id="pl_wcpt_settings_button" name="pl_wcpt_settings_buwcpton"
    value="<?php esc_html_e( 'Save', 'gift-cards-on-demand-free' ); ?>" /></th><td></td></tr>
      		</tbody>
    	</table>
    	
    
    	<table <?php echo wp_kses_post( $tab_3_display ); ?> class="form-table pl_nav_tab_table pl_nav_tab_3">
			<tbody>
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_currency_conversion"><?php esc_html_e( "Currency Conversion", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Select manual to manually input currency conversion between USD and your store currency. Or use OpenExchangeRates API automatically.", "gift-cards-on-demand-free" ); ?></span></div><input type="radio" <?php echo esc_attr( $cc_man_chk ); ?> name="pl_wcpt_api_currency_conversion" value="manual"><?php esc_html_e( 'Manual', 'gift-cards-on-demand-free' ); ?> &nbsp;&nbsp;<input disabled type="radio"><?php esc_html_e( 'Automatic', 'gift-cards-on-demand-free' ); ?></td>
				</tr>
				<tr class="pl_currency_conversion_auto_row" class="form-field">
					<th scope="row"><label for="pl_wcpt_api_openexchangerates"><?php esc_html_e( "OpenExchangeRates APP ID", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Insert OpenExchangeRates API Key here.", "gift-cards-on-demand-free" ); ?></span></div><input autocomplete="off" type="text" disabled id="pl_wcpt_api_openexchangerates" class="input-add-field" value=""/></td>
				</tr>
				
				<tr class="pl_currency_conversion_auto_row" class="form-field">
					<th scope="row"><label for="pl_wcpt_api_openexchangerates_cron"><?php esc_html_e( "Hour between rates sync", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Number of hours between rates sync.", "gift-cards-on-demand-free" ); ?></span></div><input autocomplete="off" type="number" disabled id="pl_wcpt_api_openexchangerates_cron" class="input-add-field" value="0"/></td>
				</tr>
				
				<tr id="pl_currency_conversion_val_row" class="form-field">
					<th scope="row"><label for="pl_wcpt_api_currency_conversion_val"><?php esc_html_e( "Exchange Rate", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "USD to Store currency exchange rate.", "gift-cards-on-demand-free" ); ?></span></div><input autocomplete="off" type="text" id="pl_wcpt_api_currency_conversion_value" name="pl_wcpt_api_currency_conversion_value" class="input-add-field" value="<?php echo esc_attr( $pl_wcpt_api_currency_conversion_value );?>"/></td>
				</tr>
								
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_import_markup_val"><?php esc_html_e( "Markup Percentage", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Set default product mark-up (%). This can be overridden per product.", "gift-cards-on-demand-free" ); ?></span></div><input autocomplete="off" disabled type="number" id="pl_wcpt_api_import_markup_val" class="input-add-field" value="0"/></td>
				</tr>
				<tr><th><input type="submit" name="submit" class="button-primary" id="pl_wcpt_settings_button" name="pl_wcpt_settings_buwcpton"
    value="<?php esc_html_e( 'Save', 'gift-cards-on-demand-free' ); ?>" /></th><td></td></tr>
      		</tbody>
    	</table>
    	<table <?php echo wp_kses_post( $tab_4_display ); ?>  class="form-table pl_nav_tab_table pl_nav_tab_4">
			<tbody>
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_per_product_limit"><?php esc_html_e( "Limit Stock Visibility", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Limit stock shown to customers. Leave empty to show 
maximum.", "gift-cards-on-demand-free" ); ?></span></div><input autocomplete="off" type="number" disabled id="pl_wcpt_api_per_product_limit" class="input-add-field" value="<?php echo esc_attr( $pl_wcpt_api_per_product_limit ); ?>"/></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_code_purchase"><?php esc_html_e( "Purchase Card", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "In which order status the code is purchased from PayThem. \"Processing\" is recommended to reserve the code as soon as possible after the customer has paid.", "gift-cards-on-demand-free" ); ?></span></div><input type="radio" checked name="pl_wcpt_api_code_purchase" value="processing"> <?php esc_html_e( 'Processing', 'gift-cards-on-demand-free' ); ?> &nbsp;&nbsp;<input disabled type="radio"> <?php esc_html_e( 'Completed', 'gift-cards-on-demand-free' ); ?></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_code_release"><?php esc_html_e( "Release Card", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "In which order status the code is released to the customer.", "gift-cards-on-demand-free" ); ?></span></div><input type="radio" <?php echo esc_attr( $cr_pr_chk ); ?> name="pl_wcpt_api_code_release" value="processing"> <?php esc_html_e( 'Processing', 'gift-cards-on-demand-free' ); ?> &nbsp;&nbsp;<input <?php echo esc_attr( $cr_co_chk ); ?> type="radio" name="pl_wcpt_api_code_release" value="completed"> <?php esc_html_e( 'Completed', 'gift-cards-on-demand-free' ); ?></td>
				</tr>
				
				<tr id="pl_code_release_method_limit_row" class="form-field">
					<th scope="row"><label for="pl_wcpt_api_code_release_method_limit"><?php esc_html_e( "CSV Threshold", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Orders containing less items than this value will not be converted into a CSV file.", "gift-cards-on-demand-free" ); ?></span></div><input disabled autocomplete="off" type="number" min="0" max="100" step="1" id="pl_wcpt_api_code_release_method_limit" class="input-add-field" value=""/></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_code_release_regular_email"><?php esc_html_e( "Release Regular Orders Via Email", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Select Yes to insert items into order emails. Select No if Registered Customers must login to view items. Guest orders will always be sent via email.", "gift-cards-on-demand-free" ); ?></span></div><input type="radio" checked name="pl_wcpt_api_code_release_regular_email" value="yes"> <?php esc_html_e( 'Yes', 'gift-cards-on-demand-free' ); ?> &nbsp;&nbsp;<input disabled type="radio"> <?php esc_html_e( 'No', 'gift-cards-on-demand-free' ); ?></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_code_release_csv_email"><?php esc_html_e( "Release CSV Orders Via Email", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Select Yes to attach the CSV file to order emails. Select No if Registered Customers must login to download the CSV file. Guest orders will always be sent via email.", "gift-cards-on-demand-free" ); ?></span></div><input type="radio" checked name="pl_wcpt_api_code_release_csv_email" value="yes"> <?php esc_html_e( 'Yes', 'gift-cards-on-demand-free' ); ?> &nbsp;&nbsp;<input disabled type="radio"> <?php esc_html_e( 'No', 'gift-cards-on-demand-free' ); ?></td>
				</tr>
				<tr><th><input type="submit" name="submit" class="button-primary" id="pl_wcpt_settings_button" name="pl_wcpt_settings_buwcpton"
    value="<?php esc_html_e( 'Save', 'gift-cards-on-demand-free' ); ?>" /></th><td></td></tr>
      		</tbody>
    	</table>
    	<table <?php echo wp_kses_post( $tab_5_display ); ?>  class="form-table pl_nav_tab_table pl_nav_tab_5">
			<tbody>
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_low_wallet_email"><?php esc_html_e( "Low Wallet Balance Email", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "Allow notifications when your wallet is bellow a given threshold value.", "gift-cards-on-demand-free" ); ?></span></div><input disabled type="checkbox" id="pl_wcpt_api_low_wallet_email" value="1"></td>
				</tr>				
				<tr class="form-field">
					<th scope="row"><label for="pl_wcpt_api_price_mismatch_email"><?php esc_html_e( "Price Mismatch Email", "gift-cards-on-demand-free" ); ?></label></th>
					<td><div class="hd_popup"><i class="fa fa-question-circle"></i><span class="hd_popuptext"><?php esc_html_e( "If the profit margin on a sale (Selling Price / Purchase Price) is less than the percentage you set below, you will receive a warning email.", "gift-cards-on-demand-free" ); ?></span></div><input type="checkbox" disabled id="pl_wcpt_api_price_mismatch_email" value="1"></td>
				</tr>
				<tr><th><input type="submit" name="submit" class="button-primary" id="pl_wcpt_settings_button" name="pl_wcpt_settings_buwcpton"
    value="<?php esc_html_e( 'Save', 'gift-cards-on-demand-free' ); ?>" /></th><td></td></tr>
      		</tbody>
    	</table>
    	<div <?php echo wp_kses_post( $tab_6_display ); ?>  class="form-table pl_nav_tab_table pl_nav_tab_6">
	    	<h3><?php esc_html_e( 'List of pro features:', 'gift-cards-on-demand-free' ) ?></h3>
			<ul class="pl-pt-pro-features">
				<li><strong>Global Default Markup</strong><br>
					This global setting stores a default markup percentage. When new products 
					are created, this default % is used to calculate and set the regular price that 
					customers pay. Default markup prices will also automatically adjust whenever
					cost prices increase or decrease. Default markup prices may be replaced with
					custom fixed or percentage-based prices at product level.</li>
				<li><strong>Automatic Exchange Rates</strong><br>
If supplier cost prices are USD based, but the default currency of the 
WooCommerce Store is EUR, all pricing will be converted to EUR using a free 
API key from Open Exchange Rates.</li>
				<li><strong>Limit Stock Availability</strong><br>
This optional global setting hides actual stock levels from customers and only
shows stock up to the maximum limit set by the Store Owner.</li>
				<li><strong>Low Wallet Balance Notifications</strong><br>
Store Owners have the option to set a minimum wallet level and receive a 
notification email when their supplier wallet balance drops below the set 
amount.</li>
				<li><strong>Set Price Mismatch Notifications</strong><br>
This option allows Store Owners to set a minimum profit percentage. If 
supplier price changes result in a sale making less profit than the value set, a
warning email is sent to the Store Owner.</li>
				<li><strong>Bulk Import of Products</strong><br>
Select up to 100 supplier products at a time to create WooCommerce 
products. Category selection, custom product names and pricing can all be 
adjusted on one page before importing. Newly created WooCommerce 
products are saved to draft status, so that final adjustments can be made 
before publishing.</li>
				<li><strong>Set Percentage Selling Price</strong><br>
This option sets a custom percentage markup to calculate and set customer 
prices for a product. The customer pricing will be automatically adjusted 
whenever supplier prices change so that the Store Owner does not need to 
adjust prices when supplier prices change.</li>
				<li><strong>Set Fixed Foreign Currency Price</strong><br>
This option automatically adjusts store prices according to a set price in a 
foreign currency. For example, a store with a EUR default currency might 
want to sell a USD 100 product for exactly USD 100 irrespective of the 
exchange rate. This option allows the EUR price to be adjusted so that 
customers always pay the EUR equivalent of USD 100 for this product. Store 
Owners can select any currency and set a fixed price for any product. This 
option relies on the Automatic Exchange Rates feature being enabled.</li>
				<li><strong>Hide Or Customize Redemption Instructions</strong><br>
Standard redemption instructions are provided by many suppliers. These 
default instructions are included in delivery emails to customers. This option 
allows Store Owners to remove redemption instructions or replace the default
instructions with their own.</li>
				<li><strong>Purchase At Completed Status</strong><br>
By default, the plugin will reserve a gift card as soon as the customer has 
paid, and the order status changes from “Pending payment” to “Processing”. 
This option allows the purchase of gift cards to be delayed until the order 
status is changed to “Completed”.</li>
				<li><strong>Send Bulk Order CSV Files</strong><br>
This option allows bulk orders to be delivered in CSV files. If a Store Owner 
sets a value of 10 items for example, orders for 10 or more gift cards will be 
provided in CSV files for the customers convenience.</li>
				<li><strong>Restrict Email Delivery</strong><br>
Email delivery is not always reliable or secure. This option removes gift cards 
from delivery emails if the customer has an account. Instead, the customer 
receives a notification email when the gift cards can be downloaded. The 
customer then needs to login to their account to access/download their gift 
cards.</li>
			</ul>
			<div>
				<a target="_blank" href="<?php echo esc_url( $external_url ); ?>"><span class="button"><?php esc_html_e( 'LEARN MORE', 'gift-cards-on-demand-free' ); ?></span></a>
				<a target="_blank" href="<?php echo esc_url( $external_url ); ?>"><span class="button-primary"><?php esc_html_e( 'BUY PRO NOW', 'gift-cards-on-demand-free' ); ?></span></a>
			</div>
    	</div>
	<hr>
	<input type="hidden" name="pl_tab" id="pl_tab" value="<?php echo esc_attr( $selected_tab ); ?>"/>    
	<?php wp_nonce_field( 'pl-wcpt-settings-edit' . get_current_user_id(), '_wcpt_nonce' ); ?>
  </form>
  </br>
</div>