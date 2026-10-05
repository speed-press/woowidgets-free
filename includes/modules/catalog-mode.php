<?php
/**
 * Catalog mode: hide prices and purchasing.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'catalog-mode',
	'name'        => 'Catalog mode',
	'description' => 'Turns the store into a catalog: hide prices, hide add to cart, or both. Use it for quote-only shops.',
	'category'    => 'catalog',
	'demand'      => 'medium',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'hide_price',
			'label'   => 'Hide prices',
			'type'    => 'checkbox',
			'default' => 1,
		),
		array(
			'key'     => 'hide_button',
			'label'   => 'Hide add to cart',
			'type'    => 'checkbox',
			'default' => 1,
		),
		array(
			'key'     => 'note',
			'label'   => 'Replacement note',
			'type'    => 'text',
			'default' => 'Contact us for pricing',
		),
	),
	'boot'        => function ( $settings ) {
		if ( ! empty( $settings['hide_button'] ) ) {
			add_filter( 'woocommerce_is_purchasable', '__return_false' );
			remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		}
		if ( ! empty( $settings['hide_price'] ) ) {
			add_filter( 'woocommerce_get_price_html', '__return_empty_string' );
		}
		if ( ! empty( $settings['note'] ) ) {
			add_action(
				'woocommerce_single_product_summary',
				function () use ( $settings ) {
					echo '<p class="spwa-catalog-note">' . esc_html( $settings['note'] ) . '</p>';
				},
				31
			);
		}
	},
);
