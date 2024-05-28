<?php

if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

?>

<p><?php esc_html_e( 'Please use a text editor such as Notepad++ or Windows Notepad or Apple TextEdit.app to open CSV files.', 'gift-cards-on-demand-free' ); ?></p>
<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
	<thead>
		<tr>
			<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr">Order</span></th>
			<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr">Date</span></th>
			<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-status"><span class="nobr">Status</span></th>
			<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><span class="nobr">Download</span></th>
		</tr>
	</thead>

	<tbody>
	
		<?php
		foreach ( $orders as $order ) : 
			$download = get_site_url() . '/download_vouchers?order_id=';
		?>
			<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
				<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Order">
					<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
						<?php echo esc_html( _x( '#', 'hash before order number', 'gift-cards-on-demand-free' ) . $order->get_order_number() ); ?>
					</a>
				</td>
				<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Date">
					<time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></time>
				</td>
				<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status" data-title="Status">
					<?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
				</td>
				<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions" data-title="Actions">
					<a target="_blank" href="<?php echo esc_url( $download . $order->get_id() ) ?>" class="woocommerce-button wp-element-button button view">Download</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>