<?php
/**
 * Back in stock email capture.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'back-in-stock',
	'name'        => 'Back in stock alerts',
	'description' => 'Lets shoppers leave an email on out-of-stock products. Emails them when the product is marked in stock, then clears the list.',
	'category'    => 'catalog',
	'demand'      => 'high',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'heading',
			'label'   => 'Heading',
			'type'    => 'text',
			'default' => 'Email me when this is back',
		),
		array(
			'key'     => 'button',
			'label'   => 'Button',
			'type'    => 'text',
			'default' => 'Notify me',
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'woocommerce_single_product_summary',
			function () use ( $settings ) {
				global $product;
				if ( ! $product || $product->is_in_stock() ) {
					return;
				}
				$heading = ! empty( $settings['heading'] ) ? $settings['heading'] : __( 'Email me when this is back', 'speedpress-for-woocommerce' );
				$button  = ! empty( $settings['button'] ) ? $settings['button'] : __( 'Notify me', 'speedpress-for-woocommerce' );
				echo '<form class="spwa-waitlist" method="post">';
				wp_nonce_field( 'spwa_waitlist', 'spwa_waitlist_nonce' );
				echo '<input type="hidden" name="spwa_wait_product" value="' . esc_attr( (string) $product->get_id() ) . '">';
				echo '<p>' . esc_html( $heading ) . '</p>';
				echo '<input type="email" name="spwa_wait_email" required placeholder="' . esc_attr__( 'Email', 'speedpress-for-woocommerce' ) . '">';
				echo '<button type="submit" class="button">' . esc_html( $button ) . '</button>';
				echo '</form>';
			},
			35
		);

		add_action(
			'template_redirect',
			function () {
				if ( empty( $_POST['spwa_wait_product'] ) || empty( $_POST['spwa_waitlist_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					return;
				}
				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spwa_waitlist_nonce'] ) ), 'spwa_waitlist' ) ) {
					return;
				}
				$product_id = absint( wp_unslash( $_POST['spwa_wait_product'] ) );
				$email      = isset( $_POST['spwa_wait_email'] ) ? sanitize_email( wp_unslash( $_POST['spwa_wait_email'] ) ) : '';
				if ( ! $product_id || ! is_email( $email ) ) {
					return;
				}
				$list = get_post_meta( $product_id, '_spwa_waitlist', true );
				$list = is_array( $list ) ? $list : array();
				$list[ strtolower( $email ) ] = $email;
				update_post_meta( $product_id, '_spwa_waitlist', $list );
				wc_add_notice( __( 'We will email you when this is back in stock.', 'speedpress-for-woocommerce' ), 'success' );
			}
		);

		add_action(
			'woocommerce_product_set_stock_status',
			function ( $product_id, $status ) {
				if ( 'instock' !== $status ) {
					return;
				}
				$list = get_post_meta( $product_id, '_spwa_waitlist', true );
				if ( ! is_array( $list ) || ! $list ) {
					return;
				}
				$product = wc_get_product( $product_id );
				$name    = $product ? $product->get_name() : get_the_title( $product_id );
				$link    = get_permalink( $product_id );
				foreach ( $list as $email ) {
					if ( ! is_email( $email ) ) {
						continue;
					}
					wp_mail(
						$email,
						sprintf(
							/* translators: %s product name */
							__( '%s is back in stock', 'speedpress-for-woocommerce' ),
							$name
						),
						sprintf(
							/* translators: 1 product name, 2 url */
							__( "%1\$s is available again.\n\n%2\$s", 'speedpress-for-woocommerce' ),
							$name,
							$link
						)
					);
				}
				delete_post_meta( $product_id, '_spwa_waitlist' );
			},
			10,
			2
		);
	},
);
