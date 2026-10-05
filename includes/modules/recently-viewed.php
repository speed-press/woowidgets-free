<?php
/**
 * Recently viewed products. Shortcode: [spwa_recently_viewed]
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'recently-viewed',
	'name'        => 'Recently viewed',
	'description' => 'Remembers products this browser opened and prints them with [spwa_recently_viewed]. Useful on the cart and home page.',
	'category'    => 'catalog',
	'demand'      => 'medium',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'limit',
			'label'   => 'Products to remember',
			'type'    => 'number',
			'default' => 8,
		),
		array(
			'key'     => 'title',
			'label'   => 'Shortcode heading',
			'type'    => 'text',
			'default' => 'Recently viewed',
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'template_redirect',
			function () use ( $settings ) {
				if ( ! function_exists( 'is_product' ) || ! is_product() ) {
					return;
				}
				$id = get_queried_object_id();
				if ( ! $id ) {
					return;
				}
				$limit = isset( $settings['limit'] ) ? max( 1, (int) $settings['limit'] ) : 8;
				$ids   = array();
				if ( ! empty( $_COOKIE['spwa_recent'] ) ) {
					$ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_COOKIE['spwa_recent'] ) ) ) ) );
				}
				$ids = array_values( array_diff( $ids, array( $id ) ) );
				array_unshift( $ids, $id );
				$ids = array_slice( $ids, 0, $limit );
				setcookie( 'spwa_recent', implode( ',', $ids ), time() + 30 * DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
			}
		);
		add_shortcode(
			'spwa_recently_viewed',
			function () use ( $settings ) {
				if ( empty( $_COOKIE['spwa_recent'] ) ) {
					return '';
				}
				$ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_COOKIE['spwa_recent'] ) ) ) ) );
				$current = get_queried_object_id();
				$ids = array_values( array_diff( $ids, array( $current ) ) );
				if ( ! $ids ) {
					return '';
				}
				$title = ! empty( $settings['title'] ) ? $settings['title'] : __( 'Recently viewed', 'speedpress-for-woocommerce' );
				$html  = '<section class="spwa-recent"><h2>' . esc_html( $title ) . '</h2>';
				$html .= do_shortcode( '[products ids="' . implode( ',', $ids ) . '" columns="4"]' );
				$html .= '</section>';
				return $html;
			}
		);
	},
);
