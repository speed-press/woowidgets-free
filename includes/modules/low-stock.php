<?php
/**
 * Low stock urgency line and admin email.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'low-stock',
	'name'        => 'Low Stock Notifier',
	'description' => 'Allows you to get notified when your product stock is lower.',
	'category'    => 'general',
	'tier'        => 'free',
	'demand'      => 'high',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'threshold',
			'label'   => 'Show message at or below this quantity',
			'type'    => 'number',
			'default' => 5,
		),
		array(
			'key'     => 'label',
			'label'   => 'Message ({count} is replaced)',
			'type'    => 'text',
			'default' => 'Only {count} left in stock',
		),
		array(
			'key'     => 'email',
			'label'   => 'Email the shop manager on WooCommerce low-stock',
			'type'    => 'checkbox',
			'default' => 1,
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'woocommerce_single_product_summary',
			function () use ( $settings ) {
				global $product;
				if ( ! $product || ! $product->managing_stock() ) {
					return;
				}
				$qty = $product->get_stock_quantity();
				$max = isset( $settings['threshold'] ) ? (int) $settings['threshold'] : 5;
				if ( null === $qty || $qty <= 0 || $qty > $max ) {
					return;
				}
				$tpl = ! empty( $settings['label'] ) ? $settings['label'] : 'Only {count} left in stock';
				echo '<p class="spwa-low">' . esc_html( str_replace( '{count}', (string) (int) $qty, $tpl ) ) . '</p>';
			},
			21
		);

		if ( ! empty( $settings['email'] ) ) {
			add_action(
				'woocommerce_low_stock',
				function ( $product ) {
					if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
						return;
					}
					$to      = get_option( 'woocommerce_stock_email_recipient', get_option( 'admin_email' ) );
					$subject = sprintf(
						/* translators: %s product name */
						__( 'Low stock: %s', 'speedpress-for-woocommerce' ),
						$product->get_name()
					);
					$body = sprintf(
						/* translators: 1 product name, 2 quantity, 3 edit link */
						__( "%1\$s is down to %2\$d.\n\nEdit: %3\$s", 'speedpress-for-woocommerce' ),
						$product->get_name(),
						(int) $product->get_stock_quantity(),
						get_edit_post_link( $product->get_id(), 'raw' )
					);
					wp_mail( $to, $subject, $body );
				}
			);
		}
	},
);
