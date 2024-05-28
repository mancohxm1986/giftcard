<?php 
	
if (! defined('ABSPATH')) {
    exit();
}

class PL_WCPT_Cart {
	
	public function __construct() {	
		
		// Set on-demand product maximum to 20 per order
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart_on_demand' ), 1, 5 );
		add_filter( 'woocommerce_update_cart_validation', array( $this, 'validate_update_cart_on_demand' ), 1, 4 );	
	}
	
	public function validate_update_cart_on_demand( $passed, $cart_item_key, $values, $quantity ) {
		
		$product_id       = $values['product_id'];
		$stock_management = get_post_meta( $product_id, '_pt_stock_management', TRUE );
		$pt_product       = get_post_meta( $product_id, '_pt_product', TRUE );
		if ( $pt_product && $stock_management == 'no' ) {
			
			$product 		= wc_get_product( $product_id );
			$product_title 	= $product->get_title();
			
			$already_in_cart = $this->get_product_cart_qty( $product_id, $cart_item_key );
			$current_qty     = $already_in_cart + $quantity;
			
			$max_items = get_option( 'pl_wcpt_api_purchase_limit' ) ? get_option( 'pl_wcpt_api_purchase_limit' ) : 20;							
			if ( $current_qty > $max_items ) {
				// oops. too much.
				$passed = false;			
				wc_add_notice( sprintf( esc_html__( 'You can add a maximum of %1$s %2$s\'s to %3$s.', 'gift-cards-on-demand-free' ), 
							$u_max_items,
							$product_title,
							'<a href="' . esc_url( wc_get_cart_url() ) . '">' . esc_html__( 'your cart', 'gift-cards-on-demand-free' ) . '</a>' ), 'error' );
			}
		}
		
		return $passed;
	}
	
	public function validate_add_to_cart_on_demand( $passed, $product_id, $quantity, $variation_id = '', $variations = '' ) {
		
		$stock_management = get_post_meta( $product_id, '_pt_stock_management', TRUE );
		$pt_product       = get_post_meta( $product_id, '_pt_product', TRUE );
		if ( $pt_product && $stock_management == 'no' ) {
			$already_in_cart 	= $this->get_product_cart_qty( $product_id );
			$product 			= wc_get_product( $product_id );
			$product_title 		= $product->get_title();
			
			$current_qty = $already_in_cart + $quantity;
			$max_items   = get_option( 'pl_wcpt_api_purchase_limit' ) ? get_option( 'pl_wcpt_api_purchase_limit' ) : 20;
			if ( $current_qty > $max_items ) {
									
				// oops. too much.
				$passed = false;			
				wc_add_notice( sprintf( esc_html__( 'You can add a maximum of %1$s %2$s\'s to %3$s. You already have %4$s.', 'gift-cards-on-demand-free' ), 
							$u_max_items,
							$product_title,
							'<a href="' . esc_url( wc_get_cart_url() ) . '">' . esc_html__( 'your cart', 'gift-cards-on-demand-free' ) . '</a>',
							$already_in_cart ), 'error' );
			}
		}
		
		return $passed;
	}
	
	public function get_product_cart_qty( $product_id, $cart_item_key = '' ) {
		
		global $woocommerce;
		$running_qty = 0; // iniializing quantity to 0
		// search the cart for the product in and calculate quantity.
		foreach($woocommerce->cart->get_cart() as $other_cart_item_keys => $values ) {
			if ( $product_id == $values['product_id'] ) {
				if ( $cart_item_key == $other_cart_item_keys ) {
					continue;
				}
				$running_qty += (int) $values['quantity'];
			}
		}
			
		return $running_qty;
	}
}