<?php
/**
 * Cleanup on uninstall.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'spwa_modules' );
delete_option( 'spwa_module_settings' );
delete_option( 'spwa_version' );
