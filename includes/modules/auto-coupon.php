<?php
/**
 * Auto-apply a coupon when the cart is not empty.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'auto-coupon',
	'name'        => 'Auto-apply coupon',
	'description' => 'Applies one coupon code as soon as the cart has items. The coupon still has to exist and be valid in WooCommerce.',
	'category'    => 'checkout',
	'demand'      => 'high',
	'frontend'    => false,
	'settings'    => array(
		array(
			'key'     => 'code',
			'label'   => 'Coupon code',
			'type'    => 'text',
			'default' => '',
			'help'    => 'Must match a published WooCommerce coupon.',
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'woocommerce_before_calculate_totals',
			function ( $cart ) use ( $settings ) {
				if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
					return;
				}
				$code = isset( $settings['code'] ) ? wc_format_coupon_code( $settings['code'] ) : '';
				if ( '' === $code || ! $cart || $cart->is_empty() ) {
					return;
				}
				if ( $cart->has_discount( $code ) ) {
					return;
				}
				$cart->apply_coupon( $code );
			},
			20
		);
	},
);
