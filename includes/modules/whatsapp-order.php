<?php
/**
 * WhatsApp order button.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'whatsapp-order',
	'name'        => 'WhatsApp order',
	'description' => 'Adds a WhatsApp button on the product page with the product name and link prefilled. High demand for stores that close sales in chat.',
	'category'    => 'conversion',
	'demand'      => 'high',
	'frontend'    => true,
	'settings'    => array(
		array(
			'key'     => 'phone',
			'label'   => 'WhatsApp number',
			'type'    => 'text',
			'default' => '',
			'help'    => 'Country code, digits only. Example: 15551234567',
		),
		array(
			'key'     => 'label',
			'label'   => 'Button label',
			'type'    => 'text',
			'default' => 'Order on WhatsApp',
		),
		array(
			'key'     => 'message',
			'label'   => 'Message ({name} and {url} are replaced)',
			'type'    => 'textarea',
			'default' => 'Hi, I want to order {name}: {url}',
		),
	),
	'boot'        => function ( $settings ) {
		add_action(
			'woocommerce_after_add_to_cart_form',
			function () use ( $settings ) {
				global $product;
				$phone = isset( $settings['phone'] ) ? preg_replace( '/\D+/', '', (string) $settings['phone'] ) : '';
				if ( ! $product || '' === $phone ) {
					return;
				}
				$tpl = ! empty( $settings['message'] ) ? $settings['message'] : 'Hi, I want to order {name}: {url}';
				$msg = str_replace(
					array( '{name}', '{url}' ),
					array( $product->get_name(), get_permalink( $product->get_id() ) ),
					$tpl
				);
				$label = ! empty( $settings['label'] ) ? $settings['label'] : __( 'Order on WhatsApp', 'speedpress-for-woocommerce' );
				$url   = 'https://wa.me/' . rawurlencode( $phone ) . '?text=' . rawurlencode( $msg );
				echo '<a class="button spwa-whatsapp" target="_blank" rel="noopener" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
			}
		);
	},
);
