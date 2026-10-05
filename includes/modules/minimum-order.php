<?php
/**
 * Minimum order amount.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'minimum-order',
	'name'        => 'Minimum order',
	'description' => 'Blocks checkout until the cart subtotal reaches an amount. Shows a notice on the cart.',
	'category'    => 'checkout',
	'demand'      => 'medium',
	'frontend'    => false,
	'settings'    => array(
		array(
			'key'     => 'amount',
			'label'   => 'Minimum subtotal',
			'type'    => 'number',
			'default' => 25,
			'step'    => '0.01',
		),
		array(
			'key'     => 'message',
			'label'   => 'Notice ({amount} is replaced)',
			'type'    => 'text',
			'default' => 'Minimum order is {amount}',
		),
	),
	'boot'        => function ( $settings ) {
		$check = function () use ( $settings ) {
			if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
				return;
			}
			$min = isset( $settings['amount'] ) ? (float) $settings['amount'] : 0;
			if ( $min <= 0 ) {
				return;
			}
			$subtotal = (float) WC()->cart->get_subtotal();
			if ( $subtotal >= $min ) {
				return;
			}
			$tpl = ! empty( $settings['message'] ) ? $settings['message'] : 'Minimum order is {amount}';
			$msg = str_replace( '{amount}', wp_strip_all_tags( wc_price( $min ) ), $tpl );
			wc_add_notice( $msg, 'error' );
		};
		add_action( 'woocommerce_check_cart_items', $check );
	},
);
