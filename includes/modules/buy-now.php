<?php
/**
 * Buy Now: skip the cart and go straight to checkout.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'buy-now',
	'name'        => 'Buy Now',
	'description' => 'Adds a Buy Now button on product pages that adds the item and sends the customer to checkout. Cuts a step stores lose sales on.',
	'category'    => 'conversion',
	'demand'      => 'high',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'label',
			'label'   => 'Button label',
			'type'    => 'text',
			'default' => 'Buy now',
		),
		array(
			'key'     => 'empty_cart',
			'label'   => 'Replace the cart with this product (recommended)',
			'type'    => 'checkbox',
			'default' => 1,
			'help'    => 'Off keeps existing cart items and just adds this one.',
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'woocommerce_after_add_to_cart_button',
			function () use ( $settings ) {
				global $product;
				if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
					return;
				}
				$label = ! empty( $settings['label'] ) ? $settings['label'] : __( 'Buy now', 'speedpress-for-woocommerce' );
				echo '<button type="submit" name="spwa_buy_now" value="' . esc_attr( (string) $product->get_id() ) . '" class="button alt spwa-buy-now">' . esc_html( $label ) . '</button>';
			}
		);

		add_action(
			'wp_loaded',
			function () use ( $settings ) {
				if ( empty( $_POST['spwa_buy_now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					return;
				}
				$product_id   = absint( wp_unslash( $_POST['spwa_buy_now'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( ! $product_id || ! function_exists( 'WC' ) || ! WC()->cart ) {
					return;
				}
				if ( ! empty( $settings['empty_cart'] ) ) {
					WC()->cart->empty_cart();
				}
				$qty = isset( $_POST['quantity'] ) ? absint( wp_unslash( $_POST['quantity'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$variation = array();
				foreach ( $_POST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					if ( 0 === strpos( (string) $key, 'attribute_' ) ) {
						$variation[ sanitize_title( $key ) ] = wc_clean( wp_unslash( $value ) );
					}
				}
				$added = WC()->cart->add_to_cart( $product_id, max( 1, (int) $qty ), $variation_id, $variation );
				if ( $added ) {
					wp_safe_redirect( wc_get_checkout_url() );
					exit;
				}
			},
			15
		);
	},
);
