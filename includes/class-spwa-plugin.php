<?php
/**
 * Plugin lifecycle.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

class SPWA_Plugin {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );

		SPWA_Modules::instance();

		if ( is_admin() ) {
			SPWA_Admin::instance();
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );
	}

	public function woocommerce_missing_notice() {
		if ( class_exists( 'WooCommerce' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'SpeedPress for WooCommerce needs WooCommerce to be installed and active.', 'speedpress-for-woocommerce' );
		echo '</p></div>';
	}

	public function frontend_assets() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		if ( ! SPWA_Modules::instance()->any_frontend_enabled() ) {
			return;
		}
		wp_enqueue_style(
			'spwa-front',
			SPWA_URL . 'assets/front.css',
			array(),
			SPWA_VERSION
		);
		wp_enqueue_script(
			'spwa-front',
			SPWA_URL . 'assets/front.js',
			array(),
			SPWA_VERSION,
			true
		);
		wp_localize_script(
			'spwa-front',
			'spwaFront',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'spwa_front' ),
			)
		);
	}
}
