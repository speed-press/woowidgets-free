<?php
/**
 * Plugin Name:       SpeedPress for WooCommerce
 * Plugin URI:        https://wpspeedpress.com/speedpress-for-woocommerce/
 * Description:       Modular WooCommerce addons with a SaaS-style admin. Turn features on with a toggle — speed, checkout, social proof, wishlist, Buy Now, and more. Extendable by other plugins.
 * Version:           1.4.3
 * Author:            Md Laju Miah
 * Author URI:        https://wpspeedpress.com/
 * Text Domain:       speedpress-for-woocommerce
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.0
 * WC tested up to:   9.4
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'SPWA_VERSION', '1.4.3' );
define( 'SPWA_FILE', __FILE__ );
define( 'SPWA_PATH', plugin_dir_path( __FILE__ ) );
define( 'SPWA_URL', plugin_dir_url( __FILE__ ) );
define( 'SPWA_BASENAME', plugin_basename( __FILE__ ) );

require_once SPWA_PATH . 'includes/class-spwa-addon-runner.php';
require_once SPWA_PATH . 'includes/class-spwa-addon-pack.php';
require_once SPWA_PATH . 'includes/class-spwa-modules.php';
require_once SPWA_PATH . 'includes/class-spwa-admin.php';
require_once SPWA_PATH . 'includes/class-spwa-plugin.php';

/**
 * Boot the plugin after plugins_loaded so WooCommerce is available.
 */
function spwa_boot() {
	SPWA_Plugin::instance();
}
add_action( 'plugins_loaded', 'spwa_boot' );

/**
 * Activation: seed module defaults. Does not force-enable anything.
 */
function spwa_activate() {
	if ( ! get_option( 'spwa_modules' ) ) {
		update_option( 'spwa_modules', array(), false );
	}
	if ( ! get_option( 'spwa_module_settings' ) ) {
		update_option( 'spwa_module_settings', array(), false );
	}
	update_option( 'spwa_version', SPWA_VERSION, false );
}
register_activation_hook( __FILE__, 'spwa_activate' );

/**
 * Declare HPOS compatibility.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SPWA_FILE, true );
		}
	}
);
