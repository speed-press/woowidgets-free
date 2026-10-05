<?php
/**
 * Block orders from selected countries.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'block-country',
	'name'        => 'Block Country For Order',
	'description' => 'Manage which country user can purchase your products.',
	'category'    => 'general',
	'tier'        => 'free',
	'demand'      => 'high',
	'frontend'    => false,
	'settings'    => array(
		array(
			'key'     => 'countries',
			'label'   => 'Blocked country codes',
			'type'    => 'text',
			'default' => '',
			'help'    => 'Comma-separated ISO codes, for example BD, CA, IN.',
		),
		array(
			'key'     => 'message',
			'label'   => 'Checkout message',
			'type'    => 'text',
			'default' => 'We do not accept orders from your country.',
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'woocommerce_after_checkout_validation',
			function () use ( $settings ) {
				$raw = isset( $settings['countries'] ) ? strtoupper( (string) $settings['countries'] ) : '';
				$blocked = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
				if ( ! $blocked || ! function_exists( 'WC' ) || ! WC()->customer ) {
					return;
				}
				$billing  = strtoupper( (string) WC()->customer->get_billing_country() );
				$shipping = strtoupper( (string) WC()->customer->get_shipping_country() );
				if ( in_array( $billing, $blocked, true ) || in_array( $shipping, $blocked, true ) ) {
					$msg = ! empty( $settings['message'] ) ? $settings['message'] : __( 'We do not accept orders from your country.', 'speedpress-for-woocommerce' );
					wc_add_notice( $msg, 'error' );
				}
			}
		);
	},
);
