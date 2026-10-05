<?php
/**
 * Sticky add to cart on single product pages.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'sticky-add-to-cart',
	'name'        => 'Sticky add to cart',
	'description' => 'Keeps the product title, price, and add-to-cart button on screen after the customer scrolls past the form.',
	'category'    => 'conversion',
	'demand'      => 'high',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'mobile_only',
			'label'   => 'Mobile only',
			'type'    => 'checkbox',
			'default' => 0,
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'wp_footer',
			function () use ( $settings ) {
				if ( ! function_exists( 'is_product' ) || ! is_product() ) {
					return;
				}
				global $product;
				if ( ! $product || ! $product->is_purchasable() ) {
					return;
				}
				$class = ! empty( $settings['mobile_only'] ) ? ' spwa-sticky-atc is-mobile-only' : ' spwa-sticky-atc';
				echo '<div class="' . esc_attr( trim( $class ) ) . '" hidden>';
				echo '<div class="spwa-sticky-atc__meta">';
				echo '<strong>' . esc_html( $product->get_name() ) . '</strong>';
				echo '<span>' . wp_kses_post( $product->get_price_html() ) . '</span>';
				echo '</div>';
				echo '<button type="button" class="button alt spwa-sticky-atc__btn">' . esc_html( $product->single_add_to_cart_text() ) . '</button>';
				echo '</div>';
			}
		);
	},
);
