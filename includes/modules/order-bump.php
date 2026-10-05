<?php
/**
 * One-product order bump on the cart.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'order-bump',
	'name'        => 'Cart order bump',
	'description' => 'Offers one extra product on the cart page with a single checkbox. Use it for a low-priced add-on, not a second catalog.',
	'category'    => 'conversion',
	'demand'      => 'high',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'product_id',
			'label'   => 'Product ID to offer',
			'type'    => 'number',
			'default' => 0,
			'help'    => 'Find the ID on the product edit screen.',
		),
		array(
			'key'     => 'headline',
			'label'   => 'Headline',
			'type'    => 'text',
			'default' => 'Add this to your order',
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'woocommerce_after_cart_table',
			function () use ( $settings ) {
				$product_id = isset( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;
				if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
					return;
				}
				$product = wc_get_product( $product_id );
				if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
					return;
				}
				if ( ! WC()->cart ) {
					return;
				}
				foreach ( WC()->cart->get_cart() as $item ) {
					if ( (int) $item['product_id'] === $product_id ) {
						return;
					}
				}
				$headline = ! empty( $settings['headline'] ) ? $settings['headline'] : __( 'Add this to your order', 'speedpress-for-woocommerce' );
				$url      = wp_nonce_url(
					add_query_arg(
						array(
							'spwa_bump' => $product_id,
						),
						wc_get_cart_url()
					),
					'spwa_bump_' . $product_id
				);
				echo '<div class="spwa-bump">';
				echo '<p class="spwa-bump__title">' . esc_html( $headline ) . '</p>';
				echo '<a class="spwa-bump__row" href="' . esc_url( $url ) . '">';
				echo wp_kses_post( $product->get_image( 'thumbnail' ) );
				echo '<span><strong>' . esc_html( $product->get_name() ) . '</strong>';
				echo '<em>' . wp_kses_post( $product->get_price_html() ) . '</em></span>';
				echo '<b>' . esc_html__( 'Add', 'speedpress-for-woocommerce' ) . '</b>';
				echo '</a></div>';
			}
		);

		add_action(
			'template_redirect',
			function () {
				if ( empty( $_GET['spwa_bump'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					return;
				}
				$product_id = absint( wp_unslash( $_GET['spwa_bump'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( ! $product_id || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'spwa_bump_' . $product_id ) ) {
					return;
				}
				if ( WC()->cart ) {
					WC()->cart->add_to_cart( $product_id, 1 );
				}
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		);
	},
);
