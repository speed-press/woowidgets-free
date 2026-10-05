<?php
/**
 * Free shipping progress bar.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'free-shipping-bar',
	'name'        => 'Free shipping bar',
	'description' => 'Shows how much more a customer needs to spend to unlock free shipping. One of the highest-converting cart widgets stores ask for.',
	'category'    => 'conversion',
	'demand'      => 'high',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'threshold',
			'label'   => 'Free shipping threshold',
			'type'    => 'number',
			'default' => 75,
			'step'    => '0.01',
			'help'    => 'Use the same currency as the store. Match this to your free-shipping method.',
		),
		array(
			'key'     => 'message',
			'label'   => 'Remaining message ({amount} is replaced)',
			'type'    => 'text',
			'default' => 'Add {amount} more for free shipping',
		),
		array(
			'key'     => 'success',
			'label'   => 'Unlocked message',
			'type'    => 'text',
			'default' => 'You unlocked free shipping',
		),
	),
	'boot'        => function ( $settings ) {
		$render = function () use ( $settings ) {
			if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
				return;
			}
			$threshold = isset( $settings['threshold'] ) ? (float) $settings['threshold'] : 0;
			if ( $threshold <= 0 ) {
				return;
			}
			$total = (float) WC()->cart->get_displayed_subtotal();
			if ( WC()->cart->display_prices_including_tax() ) {
				$total = (float) WC()->cart->get_subtotal() + (float) WC()->cart->get_subtotal_tax();
			} else {
				$total = (float) WC()->cart->get_subtotal();
			}
			$left = max( 0, $threshold - $total );
			$pct  = min( 100, ( $total / $threshold ) * 100 );
			if ( $left <= 0 ) {
				$text = ! empty( $settings['success'] ) ? $settings['success'] : __( 'You unlocked free shipping', 'speedpress-for-woocommerce' );
			} else {
				$tpl  = ! empty( $settings['message'] ) ? $settings['message'] : 'Add {amount} more for free shipping';
				$text = str_replace( '{amount}', wp_strip_all_tags( wc_price( $left ) ), $tpl );
			}
			echo '<div class="spwa-shipbar" data-pct="' . esc_attr( (string) round( $pct ) ) . '">';
			echo '<p>' . esc_html( $text ) . '</p>';
			echo '<span><i style="width:' . esc_attr( (string) round( $pct ) ) . '%"></i></span>';
			echo '</div>';
		};
		add_action( 'woocommerce_before_cart', $render );
		add_action( 'woocommerce_before_checkout_form', $render );
		add_action( 'woocommerce_widget_shopping_cart_before_buttons', $render );
	},
);
