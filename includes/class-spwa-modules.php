<?php
/**
 * Module registry.
 *
 * Other plugins can register modules:
 *
 * add_filter( 'spwa_register_modules', function( $modules ) {
 *     $modules['my-feature'] = array(
 *         'slug'        => 'my-feature',
 *         'name'        => 'My feature',
 *         'description' => 'What it does.',
 *         'category'    => 'conversion', // performance|conversion|proof|catalog|checkout
 *         'demand'      => 'high',       // high|medium
 *         'frontend'    => true,
 *         'settings'    => array(
 *             array( 'key' => 'label', 'label' => 'Label', 'type' => 'text', 'default' => 'Go' ),
 *         ),
 *         'boot'        => function( $settings ) { ... },
 *     );
 *     return $modules;
 * } );
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

class SPWA_Modules {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @var array<string,array>
	 */
	private $modules = array();

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
		$this->modules = $this->load_bundled();
		$this->modules = apply_filters( 'spwa_register_modules', $this->modules );
		add_action( 'woocommerce_init', array( $this, 'boot_enabled' ) );
	}

	/**
	 * @return array<string,array>
	 */
	private function load_bundled() {
		$modules = array();
		$dir     = SPWA_PATH . 'includes/modules/';
		foreach ( glob( $dir . '*.php' ) as $file ) {
			$module = include $file;
			if ( is_array( $module ) && ! empty( $module['slug'] ) ) {
				$modules[ $module['slug'] ] = $module;
			}
		}
		$catalogs = array(
			SPWA_PATH . 'includes/addons-catalog.php',
			SPWA_PATH . 'includes/addons-catalog-extra.php',
		);
		foreach ( $catalogs as $catalog ) {
			if ( ! file_exists( $catalog ) ) {
				continue;
			}
			$extra = include $catalog;
			if ( ! is_array( $extra ) ) {
				continue;
			}
			foreach ( $extra as $addon ) {
				if ( empty( $addon['slug'] ) || isset( $modules[ $addon['slug'] ] ) ) {
					continue;
				}
				$addon['boot'] = function ( $settings ) use ( $addon ) {
					SPWA_Addon_Runner::boot( $addon, $settings );
				};
				$modules[ $addon['slug'] ] = $addon;
			}
		}
		return $modules;
	}

	/**
	 * @return array<string,array>
	 */
	public function all() {
		return $this->modules;
	}

	/**
	 * @param string $slug Module slug.
	 * @return array|null
	 */
	public function get( $slug ) {
		return isset( $this->modules[ $slug ] ) ? $this->modules[ $slug ] : null;
	}

	/**
	 * @return array<string,int>
	 */
	public function enabled_map() {
		$stored = get_option( 'spwa_modules', array() );
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * @param string $slug Module slug.
	 * @return bool
	 */
	public function is_enabled( $slug ) {
		$map = $this->enabled_map();
		return ! empty( $map[ $slug ] );
	}

	/**
	 * @param string $slug Module slug.
	 * @return array<string,mixed>
	 */
	public function settings_for( $slug ) {
		$all     = get_option( 'spwa_module_settings', array() );
		$saved   = ( is_array( $all ) && isset( $all[ $slug ] ) && is_array( $all[ $slug ] ) ) ? $all[ $slug ] : array();
		$module  = $this->get( $slug );
		$defaults = array();
		if ( $module && ! empty( $module['settings'] ) ) {
			foreach ( $module['settings'] as $field ) {
				$defaults[ $field['key'] ] = isset( $field['default'] ) ? $field['default'] : '';
			}
		}
		return array_merge( $defaults, $saved );
	}

	/**
	 * @param string $slug Module slug.
	 * @param bool   $enabled On or off.
	 */
	public function set_enabled( $slug, $enabled ) {
		$map           = $this->enabled_map();
		$map[ $slug ]  = $enabled ? 1 : 0;
		update_option( 'spwa_modules', $map, false );
	}

	/**
	 * @param string               $slug Module slug.
	 * @param array<string,mixed>  $settings Sanitized settings.
	 */
	public function save_settings( $slug, $settings ) {
		$all          = get_option( 'spwa_module_settings', array() );
		if ( ! is_array( $all ) ) {
			$all = array();
		}
		$all[ $slug ] = $settings;
		update_option( 'spwa_module_settings', $all, false );
	}

	/**
	 * Boot only enabled modules, and only when WooCommerce is loaded.
	 */
	public function boot_enabled() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		foreach ( $this->modules as $slug => $module ) {
			if ( ! $this->is_enabled( $slug ) ) {
				continue;
			}
			if ( empty( $module['boot'] ) || ! is_callable( $module['boot'] ) ) {
				continue;
			}
			call_user_func( $module['boot'], $this->settings_for( $slug ) );
		}
	}

	/**
	 * @return bool
	 */
	public function any_frontend_enabled() {
		foreach ( $this->modules as $slug => $module ) {
			if ( ! empty( $module['frontend'] ) && $this->is_enabled( $slug ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return int
	 */
	public function enabled_count() {
		$count = 0;
		foreach ( $this->modules as $slug => $module ) {
			if ( $this->is_enabled( $slug ) ) {
				$count++;
			}
		}
		return $count;
	}
}
