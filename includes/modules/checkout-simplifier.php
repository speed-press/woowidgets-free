<?php
/**
 * Remove checkout fields stores usually do not need.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'checkout-simplifier',
	'name'        => 'Checkout simplifier',
	'description' => 'Removes company, address line 2, phone, or order notes. Fewer fields is one of the most requested checkout fixes.',
	'category'    => 'checkout',
	'demand'      => 'high',
	'frontend'    => false,
	'settings'    => array(
		array(
			'key'     => 'company',
			'label'   => 'Remove company',
			'type'    => 'checkbox',
			'default' => 1,
		),
		array(
			'key'     => 'address_2',
			'label'   => 'Remove address line 2',
			'type'    => 'checkbox',
			'default' => 0,
		),
		array(
			'key'     => 'phone',
			'label'   => 'Remove phone',
			'type'    => 'checkbox',
			'default' => 0,
		),
		array(
			'key'     => 'order_notes',
			'label'   => 'Remove order notes',
			'type'    => 'checkbox',
			'default' => 1,
		),
	),
	'boot'        => function ( $settings ) {
		add_filter(
			'woocommerce_checkout_fields',
			function ( $fields ) use ( $settings ) {
				$map = array(
					'company'   => 'billing_company',
					'address_2' => 'billing_address_2',
					'phone'     => 'billing_phone',
				);
				foreach ( $map as $key => $field ) {
					if ( ! empty( $settings[ $key ] ) && isset( $fields['billing'][ $field ] ) ) {
						unset( $fields['billing'][ $field ] );
					}
				}
				if ( ! empty( $settings['address_2'] ) && isset( $fields['shipping']['shipping_address_2'] ) ) {
					unset( $fields['shipping']['shipping_address_2'] );
				}
				if ( ! empty( $settings['order_notes'] ) && isset( $fields['order']['order_comments'] ) ) {
					unset( $fields['order']['order_comments'] );
				}
				return $fields;
			}
		);
	},
);
