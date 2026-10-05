<?php
/**
 * Storefront maintenance mode.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'maintenance-mode',
	'name'        => 'Maintenance Mode',
	'description' => 'Allows you to put your store in maintenance mode.',
	'category'    => 'general',
	'tier'        => 'free',
	'demand'      => 'medium',
	'frontend'    => false,
	'settings'    => array(
		array(
			'key'     => 'message',
			'label'   => 'Message',
			'type'    => 'textarea',
			'default' => 'The store is currently undergoing maintenance. Please check back later.',
		),
		array(
			'key'     => 'redirect',
			'label'   => 'Redirect URL (optional)',
			'type'    => 'text',
			'default' => '',
			'help'    => 'Leave empty to show the message. Logged-in shop managers are not redirected.',
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'template_redirect',
			function () use ( $settings ) {
				if ( is_admin() || wp_doing_ajax() || current_user_can( 'manage_woocommerce' ) ) {
					return;
				}
				if ( function_exists( 'is_login' ) && is_login() ) {
					return;
				}
				$link = isset( $settings['redirect'] ) ? esc_url_raw( $settings['redirect'] ) : '';
				if ( $link ) {
					wp_safe_redirect( $link, 302 );
					exit;
				}
				$message = ! empty( $settings['message'] ) ? $settings['message'] : __( 'The store is currently undergoing maintenance. Please check back later.', 'speedpress-for-woocommerce' );
				wp_die( '<h1>' . esc_html__( 'Maintenance Mode', 'speedpress-for-woocommerce' ) . '</h1><p>' . esc_html( $message ) . '</p>', esc_html__( 'Maintenance Mode', 'speedpress-for-woocommerce' ), array( 'response' => 503 ) );
			}
		);
	},
);
