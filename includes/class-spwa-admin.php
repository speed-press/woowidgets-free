<?php
/**
 * SaaS-style admin matching the SpeedPress suite layout.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

class SPWA_Admin {

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
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		add_filter( 'plugin_action_links_' . SPWA_BASENAME, array( $this, 'action_links' ) );
		add_action( 'wp_ajax_spwa_toggle', array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_spwa_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_spwa_bulk', array( $this, 'ajax_bulk' ) );
	}

	/**
	 * @param array<int,string> $links Plugin links.
	 * @return array<int,string>
	 */
	public function action_links( $links ) {
		$url = admin_url( 'admin.php?page=speedpress-woocommerce' );
		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Addons', 'speedpress-for-woocommerce' ) . '</a>'
		);
		return $links;
	}

	/**
	 * @param string $classes Admin body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'toplevel_page_speedpress-woocommerce' === $screen->id ) {
			$classes .= ' spwa-suite-page';
		}
		return $classes;
	}

	public function menu() {
		add_menu_page(
			__( 'WooWidget', 'speedpress-for-woocommerce' ),
			__( 'WooWidget', 'speedpress-for-woocommerce' ),
			'manage_woocommerce',
			'speedpress-woocommerce',
			array( $this, 'render' ),
			'dashicons-store',
			56
		);
		add_action( 'admin_head', array( $this, 'menu_icon_css' ) );
	}

	/**
	 * WordPress drops colored data-URI icons. Paint the SP mark with CSS instead.
	 */
	public function menu_icon_css() {
		echo '<style>
			#adminmenu #toplevel_page_speedpress-woocommerce .wp-menu-image{background:none!important}
			#adminmenu #toplevel_page_speedpress-woocommerce .wp-menu-image img{display:none!important}
			#adminmenu #toplevel_page_speedpress-woocommerce .wp-menu-image:before{content:"SP";font-family:Arial,Helvetica,sans-serif!important;font-size:9px!important;font-weight:700;line-height:20px!important;width:20px!important;height:20px!important;margin:7px 8px 0 0!important;padding:0;text-align:center;color:#fff!important;background:linear-gradient(135deg,#2563EB,#9333EA);border-radius:6px;display:inline-block;speak:none}
			#adminmenu #toplevel_page_speedpress-woocommerce:hover .wp-menu-image:before,
			#adminmenu #toplevel_page_speedpress-woocommerce.wp-has-current-submenu .wp-menu-image:before{color:#fff!important;background:linear-gradient(135deg,#2563EB,#9333EA)}
		</style>';
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'toplevel_page_speedpress-woocommerce' !== $screen->id ) {
			return;
		}
		echo '<style>
			body.toplevel_page_speedpress-woocommerce #wpcontent{padding-left:0}
			body.toplevel_page_speedpress-woocommerce #wpbody-content{padding-bottom:0}
			body.toplevel_page_speedpress-woocommerce #wpfooter,
			body.toplevel_page_speedpress-woocommerce .notice,
			body.toplevel_page_speedpress-woocommerce .update-nag{display:none}
		</style>';
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	public function assets( $hook ) {
		if ( 'toplevel_page_speedpress-woocommerce' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'spwa-admin', SPWA_URL . 'assets/admin.css', array(), SPWA_VERSION );
		wp_enqueue_script( 'spwa-admin', SPWA_URL . 'assets/admin.js', array(), SPWA_VERSION, true );
		wp_localize_script(
			'spwa-admin',
			'spwaAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'spwa_admin' ),
				'i18n'    => array(
					'saved'    => __( 'Saved', 'speedpress-for-woocommerce' ),
					'error'    => __( 'Could not save. Try again.', 'speedpress-for-woocommerce' ),
					'on'       => __( 'Enabled', 'speedpress-for-woocommerce' ),
					'off'      => __( 'Disabled', 'speedpress-for-woocommerce' ),
					'addons'   => __( 'addons', 'speedpress-for-woocommerce' ),
				),
			)
		);
	}

	public function ajax_toggle() {
		check_ajax_referer( 'spwa_admin', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}
		$slug    = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$enabled = ! empty( $_POST['enabled'] );
		if ( ! SPWA_Modules::instance()->get( $slug ) ) {
			wp_send_json_error( array( 'message' => 'Unknown module' ), 400 );
		}
		SPWA_Modules::instance()->set_enabled( $slug, $enabled );
		wp_send_json_success(
			array(
				'enabled' => $enabled ? 1 : 0,
				'count'   => SPWA_Modules::instance()->enabled_count(),
			)
		);
	}

	public function ajax_save_settings() {
		check_ajax_referer( 'spwa_admin', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}
		$slug   = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$module = SPWA_Modules::instance()->get( $slug );
		if ( ! $module ) {
			wp_send_json_error( array( 'message' => 'Unknown module' ), 400 );
		}
		$raw = array();
		if ( isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ) {
			$raw = map_deep( wp_unslash( $_POST['settings'] ), 'sanitize_text_field' );
		}
		$clean = $this->sanitize_settings( $module, $raw );
		SPWA_Modules::instance()->save_settings( $slug, $clean );
		wp_send_json_success( array( 'settings' => $clean ) );
	}

	public function ajax_bulk() {
		check_ajax_referer( 'spwa_admin', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}
		$mode    = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';
		$modules = SPWA_Modules::instance()->all();
		foreach ( $modules as $slug => $module ) {
			if ( 'disable_all' === $mode ) {
				SPWA_Modules::instance()->set_enabled( $slug, false );
			} elseif ( 'enable_free' === $mode ) {
				$premium = isset( $module['tier'] ) && 'premium' === $module['tier'];
				SPWA_Modules::instance()->set_enabled( $slug, ! $premium );
			} elseif ( 'enable_recommended' === $mode ) {
				SPWA_Modules::instance()->set_enabled( $slug, isset( $module['demand'] ) && 'high' === $module['demand'] );
			}
		}
		wp_send_json_success( array( 'count' => SPWA_Modules::instance()->enabled_count() ) );
	}

	private function guard() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}
		check_ajax_referer( 'spwa_admin', 'nonce' );
	}

	/**
	 * @param array<string,mixed> $module Module definition.
	 * @param array<string,mixed> $raw Posted values.
	 * @return array<string,mixed>
	 */
	private function sanitize_settings( $module, $raw ) {
		$clean = array();
		if ( empty( $module['settings'] ) ) {
			return $clean;
		}
		foreach ( $module['settings'] as $field ) {
			$key  = $field['key'];
			$type = isset( $field['type'] ) ? $field['type'] : 'text';
			$val  = isset( $raw[ $key ] ) ? $raw[ $key ] : '';
			if ( 'checkbox' === $type ) {
				$clean[ $key ] = ! empty( $val ) ? 1 : 0;
			} elseif ( 'number' === $type ) {
				$clean[ $key ] = is_numeric( $val ) ? $val + 0 : 0;
			} elseif ( 'textarea' === $type ) {
				$clean[ $key ] = sanitize_textarea_field( (string) $val );
			} else {
				$clean[ $key ] = sanitize_text_field( (string) $val );
			}
		}
		return $clean;
	}

	/**
	 * Sidebar groups, in the order shown in the suite.
	 *
	 * @return array<string,array<string,string>>
	 */
	private function groups() {
		return array(
			'dashboard'    => array(
				'label' => __( 'Dashboard', 'speedpress-for-woocommerce' ),
				'icon'  => 'grid',
				'desc'  => __( 'A snapshot of which addons are on.', 'speedpress-for-woocommerce' ),
			),
			'general'      => array(
				'label' => __( 'General Addons', 'speedpress-for-woocommerce' ),
				'icon'  => 'home',
				'desc'  => __( 'Improve your general store visibility and user experience with general addons.', 'speedpress-for-woocommerce' ),
			),
			'product'      => array(
				'label' => __( 'Product Addons', 'speedpress-for-woocommerce' ),
				'icon'  => 'box',
				'desc'  => __( 'Badges, wishlist, stock alerts, and other product-page addons.', 'speedpress-for-woocommerce' ),
			),
			'cart'         => array(
				'label' => __( 'Cart & Checkout', 'speedpress-for-woocommerce' ),
				'icon'  => 'cart',
				'desc'  => __( 'Buy now, order bumps, checkout fields, and thank-you notes.', 'speedpress-for-woocommerce' ),
			),
			'shipping'     => array(
				'label' => __( 'Shipping & Payment', 'speedpress-for-woocommerce' ),
				'icon'  => 'truck',
				'desc'  => __( 'Free-shipping progress and rate rules.', 'speedpress-for-woocommerce' ),
			),
			'marketing'    => array(
				'label' => __( 'Marketing & Sales', 'speedpress-for-woocommerce' ),
				'icon'  => 'megaphone',
				'desc'  => __( 'Coupons and chat-to-order tools.', 'speedpress-for-woocommerce' ),
			),
			'seo'          => array(
				'label' => __( 'SEO & Performance', 'speedpress-for-woocommerce' ),
				'icon'  => 'search',
				'desc'  => __( 'Remove storefront work the shopper does not need.', 'speedpress-for-woocommerce' ),
			),
			'customer'     => array(
				'label' => __( 'Customer Management', 'speedpress-for-woocommerce' ),
				'icon'  => 'users',
				'desc'  => __( 'Account and customer-flow addons.', 'speedpress-for-woocommerce' ),
			),
			'inventory'    => array(
				'label' => __( 'Inventory Management', 'speedpress-for-woocommerce' ),
				'icon'  => 'archive',
				'desc'  => __( 'Stock and catalog control.', 'speedpress-for-woocommerce' ),
			),
			'security'     => array(
				'label' => __( 'Security & Compliance', 'speedpress-for-woocommerce' ),
				'icon'  => 'shield',
				'desc'  => __( 'Checkout restrictions and store access.', 'speedpress-for-woocommerce' ),
			),
			'integrations' => array(
				'label' => __( 'Integrations & API', 'speedpress-for-woocommerce' ),
				'icon'  => 'plug',
				'desc'  => __( 'Connections to outside tools.', 'speedpress-for-woocommerce' ),
			),
			'content'      => array(
				'label' => __( 'Content & Design', 'speedpress-for-woocommerce' ),
				'icon'  => 'palette',
				'desc'  => __( 'Storefront layout addons.', 'speedpress-for-woocommerce' ),
			),
			'mobile'       => array(
				'label' => __( 'Mobile & Apps', 'speedpress-for-woocommerce' ),
				'icon'  => 'phone',
				'desc'  => __( 'Mobile storefront addons.', 'speedpress-for-woocommerce' ),
			),
			'automation'   => array(
				'label' => __( 'Automation & Workflows', 'speedpress-for-woocommerce' ),
				'icon'  => 'flow',
				'desc'  => __( 'Automatic store actions.', 'speedpress-for-woocommerce' ),
			),
			'reports'      => array(
				'label' => __( 'Reports & Analytics', 'speedpress-for-woocommerce' ),
				'icon'  => 'chart',
				'desc'  => __( 'Views, sales, and conversion counters.', 'speedpress-for-woocommerce' ),
			),
		);
	}

	/**
	 * @param array<string,mixed> $module Module definition.
	 * @return string
	 */
	private function group_for( $module ) {
		$slug = $module['slug'];
		$map  = array(
			'block-country'       => 'general',
			'force-login'         => 'general',
			'low-stock'           => 'general',
			'maintenance-mode'    => 'general',
			'sold-counter'        => 'general',
			'views-counter'       => 'general',
			'product-badges'      => 'product',
			'wishlist'            => 'product',
			'catalog-mode'        => 'product',
			'back-in-stock'       => 'product',
			'delivery-estimate'   => 'product',
			'recently-viewed'     => 'product',
			'sticky-add-to-cart'  => 'product',
			'buy-now'             => 'cart',
			'checkout-simplifier' => 'cart',
			'order-bump'          => 'cart',
			'thank-you'           => 'cart',
			'minimum-order'       => 'cart',
			'free-shipping-bar'   => 'shipping',
			'free-shipping-only'  => 'shipping',
			'whatsapp-order'      => 'marketing',
			'auto-coupon'         => 'marketing',
			'speed-boost'         => 'seo',
		);
		if ( isset( $map[ $slug ] ) ) {
			return $map[ $slug ];
		}
		$cat = isset( $module['category'] ) ? $module['category'] : 'general';
		return isset( $this->groups()[ $cat ] ) ? $cat : 'general';
	}

	/**
	 * @param string $name Icon name.
	 * @return string
	 */
	private function icon( $name ) {
		$icons = array(
			'grid'      => '<rect x="3" y="3" width="6" height="6" rx="1"/><rect x="11" y="3" width="6" height="6" rx="1"/><rect x="3" y="11" width="6" height="6" rx="1"/><rect x="11" y="11" width="6" height="6" rx="1"/>',
			'home'      => '<path d="M4 10.5 10 4l6 6.5"/><path d="M6 9.5V16h8V9.5"/>',
			'box'       => '<path d="M3 7l7-3 7 3-7 3-7-3z"/><path d="M3 7v6l7 3 7-3V7"/><path d="M10 10v6"/>',
			'cart'      => '<path d="M4 5h1.5l1.2 8h8.6l1.2-5H7"/><circle cx="9" cy="16" r="1"/><circle cx="14" cy="16" r="1"/>',
			'truck'     => '<path d="M3 7h9v7H3z"/><path d="M12 9h3l2 2v3h-5"/><circle cx="6.5" cy="15.5" r="1"/><circle cx="14.5" cy="15.5" r="1"/>',
			'megaphone' => '<path d="M4 9v2l8 3V6L4 9z"/><path d="M12 8.5c1.5.4 2.5 1.2 2.5 1.5S13.5 11 12 11.5"/><path d="M6 11.5V14"/>',
			'search'    => '<circle cx="9" cy="9" r="4.5"/><path d="M12.5 12.5 16 16"/>',
			'users'     => '<circle cx="8" cy="8" r="2.2"/><circle cx="13" cy="8.5" r="1.7"/><path d="M4.5 15c.6-2 2-3 3.5-3s2.9 1 3.5 3"/><path d="M12 12.2c1.3.1 2.3.8 2.8 2.3"/>',
			'archive'   => '<path d="M3 5h14v3H3z"/><path d="M4 8h12v8H4z"/><path d="M8 11h4"/>',
			'shield'    => '<path d="M10 3l6 2v5c0 3.2-2.4 5.4-6 7-3.6-1.6-6-3.8-6-7V5l6-2z"/>',
			'plug'      => '<path d="M8 3v4M12 3v4"/><path d="M6 7h8v3a4 4 0 0 1-8 0V7z"/><path d="M10 14v3"/>',
			'palette'   => '<circle cx="8" cy="8" r="4.5"/><circle cx="13.5" cy="7" r="1"/><circle cx="14" cy="11" r="1"/><circle cx="11" cy="14" r="1"/>',
			'phone'     => '<rect x="6" y="3" width="8" height="14" rx="1.5"/><path d="M9 15h2"/>',
			'flow'      => '<circle cx="5" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="10" cy="14" r="1.5"/><path d="M6.5 6H13.5M5 7.5 8.8 13M15 7.5 11.2 13"/>',
			'chart'     => '<path d="M4 16V4M4 16h12"/><path d="M7 13V9M11 13V6M15 13v-3"/>',
		);
		$inner = isset( $icons[ $name ] ) ? $icons[ $name ] : $icons['grid'];
		return '<svg viewBox="0 0 20 20" aria-hidden="true">' . $inner . '</svg>';
	}

	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$modules = SPWA_Modules::instance()->all();
		$groups  = $this->groups();
		$counts  = array();
		foreach ( $groups as $key => $group ) {
			$counts[ $key ] = 0;
		}
		foreach ( $modules as $module ) {
			$group = $this->group_for( $module );
			if ( ! isset( $counts[ $group ] ) ) {
				$counts[ $group ] = 0;
			}
			$counts[ $group ]++;
		}
		$enabled = SPWA_Modules::instance()->enabled_count();
		$free    = 0;
		$premium = 0;
		$high    = 0;
		foreach ( $modules as $module ) {
			if ( isset( $module['tier'] ) && 'premium' === $module['tier'] ) {
				$premium++;
			} else {
				$free++;
			}
			if ( isset( $module['demand'] ) && 'high' === $module['demand'] ) {
				$high++;
			}
		}
		$hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		?>
		<div class="spwa-app">
			<aside class="spwa-side">
				<div class="spwa-brand">
					<span class="spwa-mark">SP</span>
					<div>
						<strong>SpeedPress</strong>
						<em><?php esc_html_e( 'WooCommerce Suite', 'speedpress-for-woocommerce' ); ?></em>
					</div>
				</div>
				<nav class="spwa-nav">
					<?php foreach ( $groups as $key => $group ) : ?>
						<button type="button" class="<?php echo 'general' === $key ? 'is-active' : ''; ?>" data-spwa-nav="<?php echo esc_attr( $key ); ?>">
							<?php echo $this->icon( $group['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( $group['label'] ); ?></span>
							<?php if ( 'dashboard' !== $key && ! empty( $counts[ $key ] ) ) : ?>
								<b><?php echo (int) $counts[ $key ]; ?></b>
							<?php endif; ?>
						</button>
					<?php endforeach; ?>
				</nav>
			</aside>
			<div class="spwa-content">
				<header class="spwa-bar">
					<p class="spwa-crumb">
						<?php echo $this->icon( 'home' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span>SpeedPress</span>
						<i>›</i>
						<strong id="spwa-crumb-current"><?php esc_html_e( 'General Addons', 'speedpress-for-woocommerce' ); ?></strong>
					</p>
					<label class="spwa-search">
						<?php echo $this->icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<input type="search" id="spwa-search" placeholder="<?php esc_attr_e( 'Search addons…', 'speedpress-for-woocommerce' ); ?>">
						<kbd>⌘ K</kbd>
					</label>
				</header>

				<section class="spwa-panel" data-spwa-panel="dashboard" data-label="<?php esc_attr_e( 'Dashboard', 'speedpress-for-woocommerce' ); ?>" hidden>
					<div class="spwa-hero">
						<div>
							<p><?php esc_html_e( 'WooWidget', 'speedpress-for-woocommerce' ); ?></p>
							<h1><?php esc_html_e( 'Your store suite', 'speedpress-for-woocommerce' ); ?></h1>
							<span><?php
							/* translators: 1: total widgets, 2: enabled widgets */
							echo esc_html( sprintf( __( '%1$d widgets ready · %2$d turned on', 'speedpress-for-woocommerce' ), count( $modules ), $enabled ) );
							?></span>
						</div>
						<div class="spwa-hero-actions">
							<button type="button" class="spwa-action" data-bulk="enable_recommended"><?php
							/* translators: %d: number of recommended widgets */
							echo esc_html( sprintf( __( 'Enable %d recommended', 'speedpress-for-woocommerce' ), $high ) );
							?></button>
							<button type="button" class="spwa-action is-quiet" data-bulk="enable_free"><?php esc_html_e( 'Enable free', 'speedpress-for-woocommerce' ); ?></button>
							<button type="button" class="spwa-action is-quiet" data-bulk="disable_all"><?php esc_html_e( 'Disable all', 'speedpress-for-woocommerce' ); ?></button>
						</div>
					</div>
					<div class="spwa-metrics">
						<button type="button" class="spwa-metric" data-spwa-jump="general"><em><?php esc_html_e( 'Live', 'speedpress-for-woocommerce' ); ?></em><b id="spwa-on-count"><?php echo (int) $enabled; ?></b><span><?php esc_html_e( 'Enabled widgets', 'speedpress-for-woocommerce' ); ?></span></button>
						<div class="spwa-metric"><em><?php esc_html_e( 'Idle', 'speedpress-for-woocommerce' ); ?></em><b><?php echo (int) ( count( $modules ) - $enabled ); ?></b><span><?php esc_html_e( 'Turned off', 'speedpress-for-woocommerce' ); ?></span></div>
						<div class="spwa-metric"><em><?php esc_html_e( 'Library', 'speedpress-for-woocommerce' ); ?></em><b><?php echo (int) $free; ?></b><span><?php esc_html_e( 'Free widgets', 'speedpress-for-woocommerce' ); ?></span></div>
						<div class="spwa-metric"><em><?php esc_html_e( 'Library', 'speedpress-for-woocommerce' ); ?></em><b><?php echo (int) count( $modules ); ?></b><span><?php esc_html_e( 'Total widgets', 'speedpress-for-woocommerce' ); ?></span></div>
					</div>
					<div class="spwa-dash-grid">
						<section class="spwa-box">
							<h2><?php esc_html_e( 'Store health', 'speedpress-for-woocommerce' ); ?></h2>
							<ul class="spwa-env">
								<li><span><?php esc_html_e( 'WooCommerce', 'speedpress-for-woocommerce' ); ?></span><b><?php echo esc_html( defined( 'WC_VERSION' ) ? WC_VERSION : __( 'Not active', 'speedpress-for-woocommerce' ) ); ?></b></li>
								<li><span><?php esc_html_e( 'WordPress', 'speedpress-for-woocommerce' ); ?></span><b><?php echo esc_html( get_bloginfo( 'version' ) ); ?></b></li>
								<li><span><?php esc_html_e( 'PHP', 'speedpress-for-woocommerce' ); ?></span><b><?php echo esc_html( PHP_VERSION ); ?></b></li>
								<li><span><?php esc_html_e( 'Orders table', 'speedpress-for-woocommerce' ); ?></span><b><?php echo $hpos ? esc_html__( 'HPOS', 'speedpress-for-woocommerce' ) : esc_html__( 'Posts', 'speedpress-for-woocommerce' ); ?></b></li>
								<li><span><?php esc_html_e( 'Widgets', 'speedpress-for-woocommerce' ); ?></span><b><?php echo esc_html( SPWA_VERSION ); ?></b></li>
							</ul>
						</section>
						<section class="spwa-box">
							<h2><?php esc_html_e( 'Enabled now', 'speedpress-for-woocommerce' ); ?></h2>
							<ul class="spwa-enabled" id="spwa-enabled-list">
								<?php
								$any_on = false;
								foreach ( $modules as $module ) :
									if ( ! SPWA_Modules::instance()->is_enabled( $module['slug'] ) ) {
										continue;
									}
									$any_on = true;
									?>
									<li data-enabled-slug="<?php echo esc_attr( $module['slug'] ); ?>"><?php echo esc_html( $module['name'] ); ?></li>
								<?php endforeach; ?>
								<li class="spwa-none" <?php echo $any_on ? 'hidden' : ''; ?>><?php esc_html_e( 'Nothing is enabled yet. Open a category or enable recommended widgets.', 'speedpress-for-woocommerce' ); ?></li>
							</ul>
						</section>
						<section class="spwa-box spwa-box-wide">
							<h2><?php esc_html_e( 'Widget libraries', 'speedpress-for-woocommerce' ); ?></h2>
							<div class="spwa-cats">
								<?php foreach ( $groups as $key => $group ) : ?>
									<?php if ( 'dashboard' === $key ) { continue; } ?>
									<button type="button" data-spwa-jump="<?php echo esc_attr( $key ); ?>">
										<?php echo $this->icon( $group['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<span><?php echo esc_html( $group['label'] ); ?></span>
										<b><?php echo (int) $counts[ $key ]; ?></b>
									</button>
								<?php endforeach; ?>
							</div>
						</section>
					</div>
				</section>

				<section class="spwa-panel" data-spwa-panel="search" data-label="<?php esc_attr_e( 'Search', 'speedpress-for-woocommerce' ); ?>" hidden>
					<div class="spwa-head">
						<div>
							<h1><?php esc_html_e( 'Search results', 'speedpress-for-woocommerce' ); ?> <span class="spwa-count" id="spwa-search-count">0</span></h1>
							<p id="spwa-search-query"></p>
						</div>
					</div>
					<div class="spwa-list" id="spwa-search-list"></div>
					<p class="spwa-empty" id="spwa-search-empty" hidden><?php esc_html_e( 'No addons match that search.', 'speedpress-for-woocommerce' ); ?></p>
				</section>

				<?php foreach ( $groups as $key => $group ) : ?>
					<?php if ( 'dashboard' === $key ) { continue; } ?>
					<?php
					$in_group = array();
					foreach ( $modules as $module ) {
						if ( $this->group_for( $module ) === $key ) {
							$in_group[] = $module;
						}
					}
					$free_n    = 0;
					$premium_n = 0;
					foreach ( $in_group as $module ) {
						if ( isset( $module['tier'] ) && 'premium' === $module['tier'] ) {
							$premium_n++;
						} else {
							$free_n++;
						}
					}
					?>
					<section class="spwa-panel" data-spwa-panel="<?php echo esc_attr( $key ); ?>" data-label="<?php echo esc_attr( $group['label'] ); ?>" data-desc="<?php echo esc_attr( $group['desc'] ); ?>" <?php echo 'general' === $key ? '' : 'hidden'; ?>>
						<div class="spwa-head">
							<div>
								<h1><?php echo esc_html( $group['label'] ); ?></h1>
								<p><?php echo esc_html( $group['desc'] ); ?></p>
							</div>
							<span class="spwa-count"><?php echo (int) count( $in_group ); ?> <?php esc_html_e( 'addons', 'speedpress-for-woocommerce' ); ?></span>
						</div>
						<div class="spwa-grid">
							<?php foreach ( $in_group as $module ) : ?>
								<?php $this->render_card( $module ); ?>
							<?php endforeach; ?>
						</div>
						<p class="spwa-empty" hidden><?php esc_html_e( 'No addons in this view.', 'speedpress-for-woocommerce' ); ?></p>
					</section>
				<?php endforeach; ?>
			</div>
			<div class="spwa-modal" id="spwa-modal" hidden>
				<div class="spwa-modal-backdrop" data-spwa-close></div>
				<div class="spwa-modal-card" role="dialog" aria-modal="true" aria-labelledby="spwa-modal-title">
					<header class="spwa-modal-head">
						<span class="spwa-modal-mark">SP</span>
						<div>
							<p><?php esc_html_e( 'Addon settings', 'speedpress-for-woocommerce' ); ?></p>
							<h2 id="spwa-modal-title"></h2>
							<small id="spwa-modal-desc"></small>
						</div>
						<button type="button" class="spwa-modal-x" data-spwa-close aria-label="<?php esc_attr_e( 'Close', 'speedpress-for-woocommerce' ); ?>">×</button>
					</header>
					<div class="spwa-modal-body" id="spwa-modal-body"></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array<string,mixed> $module Module definition.
	 */
	private function render_card( $module ) {
		$slug     = $module['slug'];
		$on       = SPWA_Modules::instance()->is_enabled( $slug );
		$settings = SPWA_Modules::instance()->settings_for( $slug );
		$tier     = ( isset( $module['tier'] ) && 'premium' === $module['tier'] ) ? 'premium' : 'free';
		?>
		<article class="spwa-card<?php echo $on ? ' is-on' : ''; ?>" data-slug="<?php echo esc_attr( $slug ); ?>" data-tier="<?php echo esc_attr( $tier ); ?>" data-name="<?php echo esc_attr( strtolower( $module['name'] . ' ' . $module['description'] ) ); ?>">
			<div class="spwa-card-top">
				<h3><?php echo esc_html( $module['name'] ); ?></h3>
				<span class="spwa-tier spwa-tier--free"><?php esc_html_e( 'Free', 'speedpress-for-woocommerce' ); ?></span>
			</div>
			<p><?php echo esc_html( $module['description'] ); ?></p>
			<div class="spwa-card-foot">
				<label class="spwa-switch">
					<input type="checkbox" class="spwa-toggle" <?php checked( $on ); ?>>
					<span></span>
					<em class="spwa-state"><?php echo $on ? esc_html__( 'Enabled', 'speedpress-for-woocommerce' ) : esc_html__( 'Disabled', 'speedpress-for-woocommerce' ); ?></em>
				</label>
				<?php if ( ! empty( $module['settings'] ) ) : ?>
					<button type="button" class="spwa-gear" <?php echo $on ? '' : 'hidden'; ?> aria-label="<?php esc_attr_e( 'Settings', 'speedpress-for-woocommerce' ); ?>">
						<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 6h12M4 10h12M4 14h12"/><circle cx="8" cy="6" r="1.6" fill="currentColor" stroke="none"/><circle cx="13" cy="10" r="1.6" fill="currentColor" stroke="none"/><circle cx="7" cy="14" r="1.6" fill="currentColor" stroke="none"/></svg>
					</button>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $module['settings'] ) ) : ?>
				<form class="spwa-settings" hidden>
					<div class="spwa-fields">
						<?php foreach ( $module['settings'] as $field ) : ?>
							<?php $this->render_field( $field, $settings ); ?>
						<?php endforeach; ?>
					</div>
					<div class="spwa-modal-actions">
						<button type="button" class="spwa-cancel" data-spwa-close><?php esc_html_e( 'Cancel', 'speedpress-for-woocommerce' ); ?></button>
						<button type="submit" class="spwa-save"><?php esc_html_e( 'Save settings', 'speedpress-for-woocommerce' ); ?></button>
						<span class="spwa-save-note" aria-live="polite"></span>
					</div>
				</form>
			<?php endif; ?>
		</article>
		<?php
	}

	/**
	 * @param array<string,mixed> $field Field schema.
	 * @param array<string,mixed> $settings Saved settings.
	 */
	private function render_field( $field, $settings ) {
		$key   = $field['key'];
		$type  = isset( $field['type'] ) ? $field['type'] : 'text';
		$label = isset( $field['label'] ) ? $field['label'] : $key;
		$value = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		echo '<label class="spwa-field">';
		if ( 'checkbox' === $type ) {
			echo '<span class="spwa-check"><input type="checkbox" name="' . esc_attr( $key ) . '" value="1" ' . checked( ! empty( $value ), true, false ) . '> ' . esc_html( $label ) . '</span>';
		} elseif ( 'textarea' === $type ) {
			echo '<span>' . esc_html( $label ) . '</span>';
			echo '<textarea name="' . esc_attr( $key ) . '" rows="3">' . esc_textarea( (string) $value ) . '</textarea>';
		} elseif ( 'select' === $type && ! empty( $field['options'] ) ) {
			echo '<span>' . esc_html( $label ) . '</span><select name="' . esc_attr( $key ) . '">';
			foreach ( $field['options'] as $opt => $opt_label ) {
				echo '<option value="' . esc_attr( $opt ) . '" ' . selected( (string) $value, (string) $opt, false ) . '>' . esc_html( $opt_label ) . '</option>';
			}
			echo '</select>';
		} else {
			$input = ( 'number' === $type ) ? 'number' : 'text';
			$step = isset( $field['step'] ) ? $field['step'] : '';
			echo '<span>' . esc_html( $label ) . '</span>';
			echo '<input type="' . esc_attr( $input ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( (string) $value ) . '"' . ( '' !== $step ? ' step="' . esc_attr( $step ) . '"' : '' ) . '>';
		}
		if ( ! empty( $field['help'] ) ) {
			echo '<small>' . esc_html( $field['help'] ) . '</small>';
		}
		echo '</label>';
	}
}
