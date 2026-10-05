<?php
/**
 * Sale, new, and custom product badges.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'product-badges',
	'name'        => 'Product badges',
	'description' => 'Replaces the default Sale flash, adds a New badge for recent products, and a custom badge from a product field.',
	'category'    => 'proof',
	'demand'      => 'high',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'sale_text',
			'label'   => 'Sale badge text',
			'type'    => 'text',
			'default' => 'Sale',
		),
		array(
			'key'     => 'new_days',
			'label'   => 'Mark products newer than this many days as New (0 = off)',
			'type'    => 'number',
			'default' => 14,
		),
		array(
			'key'     => 'new_text',
			'label'   => 'New badge text',
			'type'    => 'text',
			'default' => 'New',
		),
	),
	'boot'        => function ( $settings ) {
		add_filter(
			'woocommerce_sale_flash',
			function ( $html ) use ( $settings ) {
				$text = ! empty( $settings['sale_text'] ) ? $settings['sale_text'] : __( 'Sale', 'speedpress-for-woocommerce' );
				return '<span class="onsale spwa-badge spwa-badge--sale">' . esc_html( $text ) . '</span>';
			}
		);

		add_action(
			'woocommerce_before_shop_loop_item_title',
			function () use ( $settings ) {
				spwa_badges_render( $settings, false );
			},
			9
		);
		add_action(
			'woocommerce_before_single_product_summary',
			function () use ( $settings ) {
				echo '<div class="spwa-badge-stack">';
				spwa_badges_render( $settings, true );
				echo '</div>';
			},
			12
		);

		add_action(
			'woocommerce_product_options_general_product_data',
			function () {
				woocommerce_wp_text_input(
					array(
						'id'          => '_spwa_badge',
						'label'       => __( 'Custom badge', 'speedpress-for-woocommerce' ),
						'desc_tip'    => true,
						'description' => __( 'Shown by SpeedPress Product badges. Leave empty to hide.', 'speedpress-for-woocommerce' ),
					)
				);
			}
		);
		add_action(
			'woocommerce_process_product_meta',
			function ( $post_id ) {
				if ( ! isset( $_POST['_spwa_badge'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					return;
				}
				update_post_meta( $post_id, '_spwa_badge', sanitize_text_field( wp_unslash( $_POST['_spwa_badge'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}
		);
	},
);

/**
 * @param array<string,mixed> $settings Module settings.
 * @param bool                $single Single product context.
 */
function spwa_badges_render( $settings, $single ) {
	global $product;
	if ( ! $product ) {
		return;
	}
	$days = isset( $settings['new_days'] ) ? (int) $settings['new_days'] : 0;
	if ( $days > 0 ) {
		$created = strtotime( $product->get_date_created() ? $product->get_date_created()->date( 'Y-m-d H:i:s' ) : '' );
		if ( $created && ( time() - $created ) < ( $days * DAY_IN_SECONDS ) ) {
			$text = ! empty( $settings['new_text'] ) ? $settings['new_text'] : __( 'New', 'speedpress-for-woocommerce' );
			echo '<span class="spwa-badge spwa-badge--new">' . esc_html( $text ) . '</span>';
		}
	}
	$custom = get_post_meta( $product->get_id(), '_spwa_badge', true );
	if ( $custom ) {
		echo '<span class="spwa-badge spwa-badge--custom">' . esc_html( $custom ) . '</span>';
	}
}
