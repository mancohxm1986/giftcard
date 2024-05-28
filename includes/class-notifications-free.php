<?php 
	
if (! defined('ABSPATH')) {
    exit();
}

class PL_WCPT_Notifications {
	
	public function __construct() {
			    
	    // Send voucher data on email
		add_action( 'woocommerce_order_item_meta_start', array( $this, 'send_voucher_data_email' ), 10, 4 );
	}
	
	public function send_voucher_data_email( $item_id, $item, $order, $plain_text ) {
		
		if ( is_order_received_page() ) {
			return false;
		}
					
		$already_sent = wc_get_order_item_meta( $item_id, '_pl_voucher_to_user', true );
		if ( $already_sent ) {
			$vouchers_db  = new PL_WCPT_Vouchers_DB();
			$product_db   = new PL_WCPT_Products_DB();
			$order_id 	  = $order->get_id();
			$customer_id  = $order->get_customer_id();
			$product_id   = $item->get_product_id();
			$pt_product   = get_post_meta( $product_id, '_pt_product', TRUE );
							
			if ( $pt_product && $order_id ) {
				
				$vouchers       = $vouchers_db->get_vouchers_by_order_product( $order_id, $pt_product );
				$total_vouchers = $vouchers_db->get_vouchers_by_order( $order_id );
				$pt_product_obj = $product_db->get_product( $pt_product );
				$instructions   = $pt_product_obj->instructions;
				
				if ( $vouchers && is_array( $vouchers ) ) {
					
					foreach ( $vouchers as $voucher ) {
						$pin     = $voucher->voucher_pin;
						$serial  = $voucher->voucher_serial;
						$expires = $voucher->expires;
						echo "<br><br><b>PIN: " . esc_attr( $pin ) . "</b>";
						echo "<br>Serial: " . esc_attr( $serial ) . "";
						if ( $expires && $expires != '0000-00-00' ) {
							echo "<br>Expires: " . esc_attr( $expires ) . "";
						}
					}
					
					if ( $instructions ) {
						echo "<br><br>" . esc_attr( $instructions );
					}
				}
			}
		}
	}	
}