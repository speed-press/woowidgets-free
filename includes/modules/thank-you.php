<?php
/**
 * Custom thank-you message.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'thank-you',
	'name'        => 'Thank-you message',
	'description' => 'Adds your own note on the order-received page: pickup instructions, a care note, or what happens next.',
	'category'    => 'checkout',
	'demand'      => 'medium',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'message',
			'label'   => 'Message',
			'type'    => 'textarea',
			'default' => 'Thanks for the order. We will email tracking when it ships.',
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'woocommerce_thankyou',
			function () use ( $settings ) {
				if ( empty( $settings['message'] ) ) {
					return;
				}
				echo '<div class="spwa-thanks"><p>' . esc_html( $settings['message'] ) . '</p></div>';
			},
			5
		);
	},
);
