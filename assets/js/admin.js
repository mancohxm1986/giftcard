jQuery(document).ready(function($) {
	
	/* Settings page */
	jQuery( '#pl_wcpt_api_per_product_limit_check' ).change(function() {
		if ( this.checked ) {
			jQuery( '#pl_product_limit_row' ).show();
		}
		else {
			jQuery( '#pl_product_limit_row' ).hide();
		}
	} );
		
	jQuery( 'input[type=radio][name=pl_wcpt_api_currency_conversion]' ).change(function() {
		if ( this.value == 'manual' ) {
			jQuery( '#pl_wcpt_api_currency_conversion_value' ).prop( 'readonly', false );
			jQuery( 'tr.pl_currency_conversion_auto_row' ).hide();
		}
		else if ( this.value == 'auto' ) {
			jQuery( '#pl_wcpt_api_currency_conversion_value' ).prop( 'readonly', true );
			jQuery( 'tr.pl_currency_conversion_auto_row' ).show();
		}
	} );
	
	var show_redemption_init = jQuery( 'input[type=radio][name=_pt_show_redemption_instructions]:checked' ).val();
	
	if ( show_redemption_init == 'show_default' ) {
		jQuery( '#_pt_redemption_instructions' ).attr( 'readonly', 'readonly' );
	}
	else if ( show_redemption_init == 'hide' ) {
		jQuery( '#_pt_redemption_instructions' ).attr( 'readonly', 'readonly' );
	}
	else {
		jQuery( '#_pt_redemption_instructions' ).removeAttr( 'readonly' );
		
	}
	
	var code_purchase = jQuery( 'input[type=radio][name=pl_wcpt_api_code_purchase]:checked' ).val();
	if ( code_purchase == 'completed' ) {
		jQuery( 'input[type=radio][name=pl_wcpt_api_code_release][value="processing"]' ).attr( 'disabled', 'disabled' );
		jQuery( 'input[type=radio][name=pl_wcpt_api_code_release][value="completed"]' ).click();
	}
	
	jQuery( 'input[type=radio][name=pl_wcpt_api_code_purchase]' ).change(function() {
		if ( this.value == 'processing' ) {
			jQuery( 'input[type=radio][name=pl_wcpt_api_code_release][value="processing"]' ).removeAttr( 'disabled', 'disabled' );
		}
		else {
			jQuery( 'input[type=radio][name=pl_wcpt_api_code_release][value="processing"]' ).attr( 'disabled', 'disabled' );
			jQuery( 'input[type=radio][name=pl_wcpt_api_code_release][value="completed"]' ).click();
		}
	} );
			
	jQuery( 'input[type=radio][name=_pt_show_redemption_instructions]' ).change(function() {
		
		if ( this.value == 'show_default' ) {
			
			jQuery( '#_pt_redemption_instructions' ).removeAttr( 'readonly' );
			
			var current_product  = jQuery( '#_pt_product' ).val();
			if ( pl_pt_products[current_product].instructions !== undefined ) {
				var default_instructions = pl_pt_products[current_product].instructions;
				jQuery( '#_pt_redemption_instructions' ).val( default_instructions );
			}
			
			jQuery( '#_pt_redemption_instructions' ).attr( 'readonly', 'readonly' );
		}
		else if ( this.value == 'hide' ) {
			jQuery( '#_pt_redemption_instructions' ).attr( 'readonly', 'readonly' );
		}
		else {
			jQuery( '#_pt_redemption_instructions' ).removeAttr( 'readonly' );
		}
	} );
	
	jQuery( '#pl_wcpt_api_low_wallet_email' ).change(function() {
		
		if ( this.checked ) {
			jQuery( '#pl_wcpt_api_low_wallet_value_tr' ).show();
		}
		else {
			jQuery( '#pl_wcpt_api_low_wallet_value_tr' ).hide();
		}
	} );
	
	jQuery( '#pl_wcpt_api_price_mismatch_email' ).change(function() {
		
		if ( this.checked ) {
			jQuery( '#pl_wcpt_api_price_mismatch_value_tr' ).show();
		}
		else {
			jQuery( '#pl_wcpt_api_price_mismatch_value_tr' ).hide();
		}
	} );
	
	/* Products Page */
	
	if ( jQuery( ".pl-search-date" ).length ) {
		jQuery( ".pl-search-date" ).datepicker({
	        dateFormat : "yy-mm-dd"
	    } );
    }
	
	jQuery( ".hd_popup" ).hover(
	  function() {
	    var children = jQuery( this ).children('.hd_popuptext');
	    setTimeout(function(){
		  children.addClass( "show" );
		}, 200 );
	  }, function() {
	    var children = jQuery( this ).children('.hd_popuptext');
	    setTimeout(function(){	
		  children.removeClass( "show" );
		}, 200 );	
	  }
	);
		
	jQuery( '#_pt_product' ).change(function() {
		
		var product_id = this.value;
		if ( product_id ) {
			jQuery( '#pl_pt_product_div' ).show();
			var pt_price  = pl_pt_products[product_id].pt_price;
			var prd_price = pl_pt_products[product_id].product_price;
			var prd_instructions = pl_pt_products[product_id].instructions;
			jQuery( '#_pt_price' ).val( pt_price );
			jQuery( '#_regular_price' ).val( prd_price );
			
			var pl_auto = jQuery( 'input[type=radio][name=_pt_autoselling_price]:checked' ).val();
			
			if ( pl_auto == 'yes' ) {
				jQuery( '#_regular_price' ).prop( 'readonly', '' );
			}
			else {
				jQuery( '#_regular_price' ).prop( 'readonly', 'readonly' );
			}
			
			var instructions_span = prd_instructions;
			if ( instructions_span == '' ) {
				instructions_span = 'N/A';
			}
			
			jQuery( 'input[type=radio][name=_pt_show_redemption_instructions]' ).filter( '[value="show_default"]' ).attr('checked', true);
			jQuery( '#_pt_redemption_instructions' ).val( prd_instructions );
			jQuery( '#_pt_default_redemption_instructions' ).text( instructions_span );
			jQuery( '#_pt_redemption_instructions' ).attr( 'readonly', 'readonly' );
			
			jQuery( '.pl_wcp_discontinued_row' ).hide();
						
			if ( pl_pt_discontinued_products[product_id] !== undefined ) {
				
				jQuery( '.pl_wcp_discontinued_row' ).show();
			} else {
				
				jQuery( '.pl_wcp_discontinued_row' ).hide();
			}
			
		}
		else {
			jQuery( '#pl_pt_product_div' ).hide();
			jQuery( '#_regular_price' ).prop( 'readonly', '' );
			
			jQuery( '.pl_wcp_discontinued_row' ).hide();
		}
	} );
	
	var pl_auto = jQuery( 'input[type=radio][name=_pt_autoselling_price]:checked' ).val();
	var pl_prod = jQuery( '#_pt_product' ).val();
	if ( pl_auto == 'yes' || pl_prod == '' ) {
		jQuery( '#_regular_price' ).prop( 'readonly', '' );
	}
	else {
		jQuery( '#_regular_price' ).prop( 'readonly', 'readonly' );
	}
		
	jQuery( '#pl_pt_autoselling_set_button' ).click( function( event ) {
		
		get_oer_rate();
	} );
	
	jQuery( 'input[type=radio][name=_pt_autoselling_price]' ).on('change', function() {
		
		pl_auto = jQuery( this ).val();
		product_id = jQuery( '#_pt_product' ).val();
		if ( product_id ) {
			jQuery( '#pl_pt_product_div' ).show();
			var pt_price         = pl_pt_products[product_id].pt_price;
			var prd_price        = pl_pt_products[product_id].product_price;
			jQuery( '#_pt_price' ).val( pt_price );
			jQuery( '#_regular_price' ).val( prd_price );
			
			if ( pl_auto == 'yes' || pl_prod == '' ) {
			jQuery( '#_regular_price' ).prop( 'readonly', '' );
			}
			else {
				jQuery( '#_regular_price' ).prop( 'readonly', 'readonly' );
			}
			
			if ( pl_auto == 'yes_p' ) {
				jQuery( '#pl_pt_autoselling_div' ).show();
				jQuery( '#pl_pt_autoselling_fixed_div' ).hide();
				var markup = jQuery( "#_pt_autoselling_price_percentage" ).val();
				if ( markup >= 0 ) {
					var base_price  = jQuery( '#_pt_price' ).val();
					var markup_val  = ( base_price * markup / 100 ).toFixed( 2 );
					var final_price = ( parseFloat( base_price ) + parseFloat( markup_val ) ).toFixed( 2 );
					jQuery( '#_regular_price' ).val( final_price );
				}
			}
			else if ( pl_auto == 'yes' && pl_pt_options.has_oer ) {
				
				jQuery( '#pl_pt_autoselling_div' ).hide();
				jQuery( '#pl_pt_autoselling_fixed_div' ).show();
			} else {
				
				jQuery( '#pl_pt_autoselling_div' ).hide();
				jQuery( '#pl_pt_autoselling_fixed_div' ).hide();
			}
		}
		else {
			jQuery( '#pl_pt_product_div' ).hide();
			jQuery( '#_regular_price' ).prop( 'readonly', '' );
		}
	} );
	
	jQuery( '#_pt_autoselling_price_percentage' ).on('input', function() {
		
		var markup      = jQuery( this ).val();
		var base_price  = jQuery( '#_pt_price' ).val();
		var markup_val  = ( base_price * markup / 100 ).toFixed( 2 );
		var final_price = ( parseFloat( base_price ) + parseFloat( markup_val ) ).toFixed( 2 );
		
		jQuery( '#_regular_price' ).val( final_price );
	} );
	
	/**
		Tab Navigation in Settings
	**/
	jQuery( '.pl_nav_tab .nav-tab' ).click( function( event ) {
		jQuery( '.pl_nav_tab > a' ).removeClass( 'nav-tab-active' );
		jQuery( this ).addClass( 'nav-tab-active' );
		jQuery( '.pl_nav_tab_table' ).hide();
		var activetab = jQuery( this ).data('tab');
		jQuery( '#pl_tab' ).val( activetab );
		jQuery( '.' + activetab ).show();
		event.preventDefault();
	});
	
	// Purchase PayThem Product Manually
	jQuery( '#pl_paythem_purchase_stock' ).click( function( event ) {
		
		var pt_product_id = jQuery( this ).data( 'pt-product_id' );
		var product_id    = jQuery( this ).data( 'product_id' );
		var qtt 	      = jQuery( '#pl_paythem_purchase_stock_qtt' ).val() * 1;
		var max           = pl_pt_options.max_items * 1;
						
		if ( pl_pt_discontinued_products[pt_product_id] !== undefined ) {
			alert( 'This product has been discontinued and cannot be purchased.' );
		} else {
						
			if ( qtt <= max ) {
				if ( pt_product_id > 0 && product_id > 0 ) {
					purchase_paythem_product( pt_product_id, qtt, product_id );
				}
				else {
					alert( 'An error occurred while purchasing this product. Try again later.' );
				}
			}
			else {
				alert( 'You can only purchase up to ' + max + ' items at a time.' );
			}
		}
	} );
	
	function get_oer_rate() {
				
		var currency = jQuery( "#_pt_autoselling_fixed_currency" ).val();
		var value    = jQuery( "#_pt_autoselling_fixed_currency_value" ).val();
		
		if ( currency && value ) {
		
			var ajaxFunction = 'pl_pt_get_oer_rate';
			jQuery.ajax({
		      url: ajaxurl,
		      dataType: 'JSON',
		      data: {
		          'action'    : ajaxFunction,
		          'currency'  : currency,
		          'value'     : value,
		      },
		      success:function( response ) {
		        if ( response ) {
			        if ( response.result ) {
				        jQuery( "#_regular_price" ).val( response.value );
			        } else {
				        alert( "Could not retrieve values from open exchange rate. Either the API credentials are wrong or you have surpassed your quota." );
			        }
		        }
		      },
		      error: function(errorThrown){
		      }
		    });		
	    }
	}
	
	function purchase_paythem_product( pt_product_id, qtt, product_id ) {
		
		var ajaxFunction = 'pl_pt_purchase_product';
		jQuery.ajax({
	      url: ajaxurl,
	      dataType: 'JSON',
	      data: {
	          'action'         : ajaxFunction,
	          'pt_product_id'  : pt_product_id,
	          'product_id'     : product_id,
	          'quantity'       : qtt
	      },
	      success:function( response ) {
	        if ( response ) {
		        var result  = response.result;
		        var message = response.message;
		        
		        alert( message );
		        if ( result ) {
			        window.location.reload();
		        }
	        }
		    	else{
			    	alert( 'An error occurred while purchasing this product.' );
		    	}
	      },
	      error: function(errorThrown){
		      alert( 'An error occurred while purchasing this product.' );
	      }
	    });
	}
} );