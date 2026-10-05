<?php
/**
 * Force login before cart and checkout.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'force-login',
	'name'        => 'Force Login Before Cart',
	'description' => 'Force user to log in before cart.',
	'category'    => 'general',
	'tier'        => 'free',
	'demand'      => 'medium',
	'frontend'    => false,
	'settings'    => array(
		array(
			'key'     => 'checkout_too',
			'label'   => 'Also require login on checkout',
			'type'    => 'checkbox',
			'default' => 1,
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'template_redirect',
			function () use ( $settings ) {
				if ( is_user_logged_in() || is_admin() ) {
					return;
				}
				if ( ! function_exists( 'is_cart' ) ) {
					return;
				}
				$hit = is_cart() || ( ! empty( $settings['checkout_too'] ) && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) );
				if ( ! $hit ) {
					return;
				}
				$back = is_cart() ? wc_get_cart_url() : wc_get_checkout_url();
				wp_safe_redirect( wp_login_url( $back ) );
				exit;
			}
		);
	},
);
