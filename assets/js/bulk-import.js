jQuery( document ).ready(function($) {
	jQuery( '.pl_product_import_search' ).select2( {
      width: 'resolve',
      selectOnClose: true,
	  ajax: {
	    url: pl_wcpt_data.ajax_url,
		dataType: 'json',
		delay: 250,
		data: function (search) {
			return {
				search : search,
				action : 'pl_wcpt_product_search'
			};
		},
		processResults: function( data, page ) {
			var options = [];
			
			if ( data ) {					 
				// data is the array of arrays, and each of them contains ID and the Label of the option
				jQuery.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
					options.push( { id: text[0], text: text[1] } );
				});

			}
			return {
				results: options
			};
		  },
		cache: true
	  },
		  minimumInputLength: 3 // the minimum of symbols to input before perform a search
	} );
	
	jQuery( '.pl_product_category_search' ).select2( {
      width: 'resolve',
      selectOnClose: true,
	  ajax: {
	    url: pl_wcpt_data.ajax_url,
		dataType: 'json',
		delay: 250,
		data: function (search) {
			return {
				search : search,
				action : 'pl_wcpt_category_search'
			};
		},
		processResults: function( data, page ) {
			var options = [];
			
			if ( data ) {					 
				// data is the array of arrays, and each of them contains ID and the Label of the option
				jQuery.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
					options.push( { id: text[0], text: text[1] } );
				});

			}
			return {
				results: options
			};
		  },
		cache: true
	  },
		  minimumInputLength: 3 // the minimum of symbols to input before perform a search
	} );
	
	jQuery( '.pl_product_import_search' ).on( 'select2:select', function (e) {
		var data = e.params.data;
		if ( data.id != undefined && data.id > 0 ) {
			jQuery( this ).parent().find( '.pl_product_import_detatch' ).show();
						
			// Hide
			if ( data?.text ) {
				jQuery( this ).parents( 'tr:first' ).find( '.pl_product_name_input' ).val( data.text );
			} else {
				jQuery( this ).parents( 'tr:first' ).find( '.pl_product_name_input' ).attr( 'readonly', true );
			}
			jQuery( this ).parents( 'tr:first' ).find( '.pl_product_category_search' ).attr( 'disabled', true );
	    }
	} );
	
	jQuery( '.pl_product_import_detatch' ).click( function( event ) {
		jQuery( this ).parent().find( '.pl_product_import_search' ).val( '' ).change();
		jQuery( this ).hide();
		
		// Show
		jQuery( this ).parents( 'tr:first' ).find( '.pl_product_name_input' ).attr( 'readonly', false );
		jQuery( this ).parents( 'tr:first' ).find( '.pl_product_name_input' ).val( '' );
		jQuery( this ).parents( 'tr:first' ).find( '.pl_product_category_search' ).attr( 'disabled', false );
	} );
	
	jQuery( ".pl_product_pricing_model" ).on( "change", function() {
		const defaultMargin = jQuery( this ).data( 'default_margin' );
		const basePrice     = jQuery( this ).data( 'base_price' );
		const model 		= jQuery( this ).val();
		
		if ( model == 'no' || model == 'yes' ) {
			jQuery( this ).parents( 'tr:first' ).find( '.pl_product_margin_input' ).val( defaultMargin );
			jQuery( this ).parents( 'tr:first' ).find( '.pl_product_margin_input' ).attr( 'readonly', true );
		} else {
			jQuery( this ).parents( 'tr:first' ).find( '.pl_product_margin_input' ).attr( 'readonly', false );
		}
		
		if ( model == 'no' || model == 'yes_p' ) {
			jQuery( this ).parents( 'tr:first' ).find( '.pl_product_price_input' ).attr( 'readonly', true );
		} else {
			jQuery( this ).parents( 'tr:first' ).find( '.pl_product_price_input' ).attr( 'readonly', false );
		}
		
		if ( model == 'no' ) {
			var price = basePrice + ( basePrice * defaultMargin / 100 );
			jQuery( this ).parents( 'tr:first' ).find( ".pl_product_price_input" ).val( price.toFixed(2) );
		} else if ( model == 'yes_p' ) {
			const currentMargin = jQuery( this ).parents( 'tr:first' ).find( '.pl_product_margin_input' ).val();
			var price = basePrice + ( basePrice * currentMargin / 100 );
			jQuery( this ).parents( 'tr:first' ).find( ".pl_product_price_input" ).val( price.toFixed(2) );
		}
	} );
	
	jQuery( ".pl_product_margin_input" ).on( "change", function() {
		
		const margin = jQuery( this ).val();
		const basePrice = jQuery( this ).data( 'base_price' );
		var price = basePrice + ( basePrice * margin / 100 );
		jQuery( this ).parents( 'tr:first' ).find( ".pl_product_price_input" ).val( price.toFixed(2) );
	} );
	
	jQuery( ".pl_product_price_input" ).on( "change", function() {
		
		const price     = jQuery( this ).val();
		const basePrice = jQuery( this ).data( 'base_price' );
		const margin    = ( ( price - basePrice ) / basePrice ) * 100;
		jQuery( this ).parents( 'tr:first' ).find( ".pl_product_margin_input" ).val( margin.toFixed(2) );
	} );
	
} );