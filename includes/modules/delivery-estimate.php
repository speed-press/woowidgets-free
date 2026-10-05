<?php
/**
 * Estimated delivery line on the product page.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'delivery-estimate',
	'name'        => 'Delivery estimate',
	'description' => 'Prints a plain “order in the next X, get it by date” line. Handling days plus transit days. Not a carrier API.',
	'category'    => 'catalog',
	'demand'      => 'high',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'handling',
			'label'   => 'Handling days',
			'type'    => 'number',
			'default' => 1,
		),
		array(
			'key'     => 'transit',
			'label'   => 'Transit days',
			'type'    => 'number',
			'default' => 4,
		),
		array(
			'key'     => 'cutoff',
			'label'   => 'Same-day cutoff hour (0–23, store time)',
			'type'    => 'number',
			'default' => 14,
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'woocommerce_single_product_summary',
			function () use ( $settings ) {
				global $product;
				if ( ! $product || ! $product->is_in_stock() ) {
					return;
				}
				$handling = isset( $settings['handling'] ) ? max( 0, (int) $settings['handling'] ) : 1;
				$transit  = isset( $settings['transit'] ) ? max( 0, (int) $settings['transit'] ) : 4;
				$cutoff   = isset( $settings['cutoff'] ) ? min( 23, max( 0, (int) $settings['cutoff'] ) ) : 14;
				$now      = current_time( 'timestamp' );
				$hour     = (int) wp_date( 'G', $now );
				$extra    = ( $hour >= $cutoff ) ? 1 : 0;
				$arrive   = $now + ( $handling + $transit + $extra ) * DAY_IN_SECONDS;
				$date     = wp_date( get_option( 'date_format' ), $arrive );
				echo '<p class="spwa-eta">';
				echo esc_html(
					sprintf(
						/* translators: %s arrival date */
						__( 'Order today, estimated arrival %s', 'speedpress-for-woocommerce' ),
						$date
					)
				);
				echo '</p>';
			},
			22
		);
	},
);
