<?php
/**
 * Sold counter from WooCommerce total_sales.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'sold-counter',
	'name'        => 'Product Purchase Counter',
	'description' => 'Counts how many times a product is purchased.',
	'category'    => 'general',
	'tier'        => 'free',
	'demand'      => 'medium',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'label',
			'label'   => 'Label ({count} is replaced)',
			'type'    => 'text',
			'default' => '{count} sold',
		),
		array(
			'key'     => 'min_show',
			'label'   => 'Hide until this many sales',
			'type'    => 'number',
			'default' => 1,
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'woocommerce_single_product_summary',
			function () use ( $settings ) {
				global $product;
				if ( ! $product ) {
					return;
				}
				$count = (int) $product->get_total_sales();
				$min   = isset( $settings['min_show'] ) ? (int) $settings['min_show'] : 1;
				if ( $count < $min ) {
					return;
				}
				$tpl = ! empty( $settings['label'] ) ? $settings['label'] : '{count} sold';
				echo '<p class="spwa-proof spwa-proof--sold">' . esc_html( str_replace( '{count}', number_format_i18n( $count ), $tpl ) ) . '</p>';
			},
			26
		);
	},
);
