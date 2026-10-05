<?php
/**
 * Hide paid shipping methods when free shipping is available.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'free-shipping-only',
	'name'        => 'Free shipping wins',
	'description' => 'Hides paid shipping rates when a free shipping rate is already available for that package.',
	'category'    => 'checkout',
	'demand'      => 'high',
	'frontend'    => false,
	'settings'    => array(),
	'boot'        => function () {
		add_filter(
			'woocommerce_package_rates',
			function ( $rates ) {
				$free = array();
				foreach ( $rates as $id => $rate ) {
					if ( 'free_shipping' === $rate->method_id ) {
						$free[ $id ] = $rate;
					}
				}
				return $free ? $free : $rates;
			}
		);
	},
);
