<?php
/**
 * Wishlist stored on the user or a cookie. Shortcode: [spwa_wishlist]
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'wishlist',
	'name'        => 'Wishlist',
	'description' => 'Heart button on product pages and loops. Guests use a cookie; logged-in customers use their account. List page: [spwa_wishlist]',
	'category'    => 'catalog',
	'demand'      => 'high',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'label_add',
			'label'   => 'Add label',
			'type'    => 'text',
			'default' => 'Save',
		),
		array(
			'key'     => 'label_in',
			'label'   => 'Saved label',
			'type'    => 'text',
			'default' => 'Saved',
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'woocommerce_after_add_to_cart_button',
			function () use ( $settings ) {
				spwa_wishlist_button( $settings );
			}
		);
		add_action(
			'woocommerce_after_shop_loop_item',
			function () use ( $settings ) {
				spwa_wishlist_button( $settings );
			},
			15
		);
		add_shortcode( 'spwa_wishlist', 'spwa_wishlist_shortcode' );
		add_action( 'wp_ajax_spwa_wishlist_toggle', 'spwa_wishlist_ajax' );
		add_action( 'wp_ajax_nopriv_spwa_wishlist_toggle', 'spwa_wishlist_ajax' );
	},
);

/**
 * @return int[]
 */
function spwa_wishlist_ids() {
	if ( is_user_logged_in() ) {
		$ids = get_user_meta( get_current_user_id(), 'spwa_wishlist', true );
		return array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
	}
	if ( empty( $_COOKIE['spwa_wishlist'] ) ) {
		return array();
	}
	$decoded = json_decode( sanitize_text_field( wp_unslash( $_COOKIE['spwa_wishlist'] ) ), true );
	return array_values( array_filter( array_map( 'absint', (array) $decoded ) ) );
}

/**
 * @param int[] $ids Product IDs.
 */
function spwa_wishlist_save( $ids ) {
	$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), 'spwa_wishlist', $ids );
		return;
	}
	setcookie( 'spwa_wishlist', wp_json_encode( $ids ), time() + 30 * DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
	$_COOKIE['spwa_wishlist'] = wp_json_encode( $ids );
}

/**
 * @param array<string,mixed> $settings Module settings.
 */
function spwa_wishlist_button( $settings ) {
	global $product;
	if ( ! $product ) {
		return;
	}
	$ids = spwa_wishlist_ids();
	$in  = in_array( (int) $product->get_id(), $ids, true );
	$add = ! empty( $settings['label_add'] ) ? $settings['label_add'] : __( 'Save', 'speedpress-for-woocommerce' );
	$on  = ! empty( $settings['label_in'] ) ? $settings['label_in'] : __( 'Saved', 'speedpress-for-woocommerce' );
	echo '<button type="button" class="spwa-wish' . ( $in ? ' is-in' : '' ) . '" data-id="' . esc_attr( (string) $product->get_id() ) . '" data-add="' . esc_attr( $add ) . '" data-in="' . esc_attr( $on ) . '">';
	echo esc_html( $in ? $on : $add );
	echo '</button>';
}

function spwa_wishlist_ajax() {
	check_ajax_referer( 'spwa_front', 'nonce' );
	$id  = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$ids = spwa_wishlist_ids();
	if ( $id && 'product' === get_post_type( $id ) ) {
		if ( in_array( $id, $ids, true ) ) {
			$ids = array_values( array_diff( $ids, array( $id ) ) );
			$in  = false;
		} else {
			$ids[] = $id;
			$in    = true;
		}
		spwa_wishlist_save( $ids );
	} else {
		$in = false;
	}
	wp_send_json_success(
		array(
			'in'    => $in,
			'count' => count( $ids ),
		)
	);
}

/**
 * @return string
 */
function spwa_wishlist_shortcode() {
	$ids = spwa_wishlist_ids();
	if ( ! $ids ) {
		return '<p class="spwa-wish-empty">' . esc_html__( 'Your wishlist is empty.', 'speedpress-for-woocommerce' ) . '</p>';
	}
	return do_shortcode( '[products ids="' . implode( ',', $ids ) . '" columns="3"]' );
}
