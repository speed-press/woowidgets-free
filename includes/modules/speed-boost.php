<?php
/**
 * Speed boost: stop WooCommerce from loading work the storefront does not need.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'speed-boost',
	'name'        => 'Speed boost',
	'description' => 'Stops cart-fragment AJAX on normal pages, the password-strength meter, and WooCommerce marketplace nags. The single most requested admin complaint is a slow store.',
	'category'    => 'performance',
	'demand'      => 'high',
	'frontend'    => false,
	'settings'    => array(
		array(
			'key'     => 'cart_fragments',
			'label'   => 'Disable cart fragments outside cart, checkout, and account',
			'type'    => 'checkbox',
			'default' => 1,
		),
		array(
			'key'     => 'password_meter',
			'label'   => 'Disable password strength meter on checkout and account',
			'type'    => 'checkbox',
			'default' => 1,
		),
		array(
			'key'     => 'marketplace',
			'label'   => 'Hide WooCommerce marketplace suggestions in wp-admin',
			'type'    => 'checkbox',
			'default' => 1,
		),
	),
	'boot'        => function ( $settings ) {
		if ( ! empty( $settings['cart_fragments'] ) ) {
			add_action(
				'wp_enqueue_scripts',
				function () {
					if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
						return;
					}
					wp_dequeue_script( 'wc-cart-fragments' );
				},
				99
			);
		}
		if ( ! empty( $settings['password_meter'] ) ) {
			add_action(
				'wp_print_scripts',
				function () {
					wp_dequeue_script( 'wc-password-strength-meter' );
				},
				100
			);
		}
		if ( ! empty( $settings['marketplace'] ) ) {
			add_filter( 'woocommerce_admin_features', function ( $features ) {
				return array_values( array_diff( (array) $features, array( 'marketing', 'marketplace' ) ) );
			} );
			add_filter( 'woocommerce_allow_marketplace_suggestions', '__return_false' );
		}
	},
);
