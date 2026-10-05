<?php
/**
 * Product view counter.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'views-counter',
	'name'        => 'Product Views Counter',
	'description' => 'Counts how many times a product is viewed or clicked by user.',
	'category'    => 'general',
	'tier'        => 'free',
	'demand'      => 'high',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'label',
			'label'   => 'Label ({count} is replaced)',
			'type'    => 'text',
			'default' => '{count} people viewed this',
		),
		array(
			'key'     => 'min_show',
			'label'   => 'Hide until this many views',
			'type'    => 'number',
			'default' => 3,
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'template_redirect',
			function () {
				if ( ! function_exists( 'is_product' ) || ! is_product() ) {
					return;
				}
				$product_id = get_queried_object_id();
				if ( ! $product_id ) {
					return;
				}
				$cookie = 'spwa_viewed_' . $product_id;
				if ( isset( $_COOKIE[ $cookie ] ) ) {
					return;
				}
				$count = (int) get_post_meta( $product_id, '_spwa_views', true );
				update_post_meta( $product_id, '_spwa_views', $count + 1 );
				setcookie( $cookie, '1', time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
			}
		);
		add_action(
			'woocommerce_single_product_summary',
			function () use ( $settings ) {
				global $product;
				if ( ! $product ) {
					return;
				}
				$count = (int) get_post_meta( $product->get_id(), '_spwa_views', true );
				$min   = isset( $settings['min_show'] ) ? (int) $settings['min_show'] : 0;
				if ( $count < $min ) {
					return;
				}
				$tpl = ! empty( $settings['label'] ) ? $settings['label'] : '{count} people viewed this';
				echo '<p class="spwa-proof">' . esc_html( str_replace( '{count}', number_format_i18n( $count ), $tpl ) ) . '</p>';
			},
			25
		);
	},
);
