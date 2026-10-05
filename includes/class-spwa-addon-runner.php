<?php
/**
 * Boots catalog addons. Dedicated modules keep their own boot callbacks.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

class SPWA_Addon_Runner {

	/**
	 * @param array<string,mixed> $addon Addon definition.
	 * @param array<string,mixed> $settings Saved settings.
	 */
	public static function boot( $addon, $settings ) {
		$type = isset( $addon['type'] ) ? $addon['type'] : '';
		if ( ! $type ) {
			return;
		}
		if ( method_exists( __CLASS__, $type ) ) {
			call_user_func( array( __CLASS__, $type ), $settings );
			return;
		}
		if ( class_exists( 'SPWA_Addon_Pack' ) && method_exists( 'SPWA_Addon_Pack', $type ) ) {
			call_user_func( array( 'SPWA_Addon_Pack', $type ), $settings );
		}
	}

	/**
	 * @param array<string,mixed> $settings Settings.
	 * @param string              $key Key.
	 * @param string              $fallback Fallback.
	 * @return string
	 */
	private static function text( $settings, $key, $fallback = '' ) {
		return isset( $settings[ $key ] ) && '' !== (string) $settings[ $key ] ? (string) $settings[ $key ] : $fallback;
	}

	public static function announcement( $settings ) {
		add_action(
			'wp_body_open',
			function () use ( $settings ) {
				$text = self::text( $settings, 'text' );
				if ( '' === $text ) {
					return;
				}
				echo '<div class="spwa-announce">' . esc_html( $text ) . '</div>';
			}
		);
	}

	public static function shop_banner( $settings ) {
		add_action(
			'woocommerce_before_shop_loop',
			function () use ( $settings ) {
				echo '<p class="spwa-note">' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
			},
			5
		);
	}

	public static function summary( $settings ) {
		add_action(
			'woocommerce_single_product_summary',
			function () use ( $settings ) {
				$note = self::text( $settings, 'text', self::text( $settings, 'note' ) );
				if ( '' === $note ) {
					return;
				}
				echo '<p class="spwa-note">' . esc_html( $note ) . '</p>';
			},
			24
		);
	}

	public static function sku( $settings ) {
		add_action(
			'woocommerce_single_product_summary',
			function () {
				global $product;
				if ( $product && $product->get_sku() ) {
					echo '<p class="spwa-note">' . esc_html__( 'SKU', 'speedpress-for-woocommerce' ) . ': ' . esc_html( $product->get_sku() ) . '</p>';
				}
			},
			6
		);
	}

	public static function add_to_cart_text( $settings ) {
		$label = self::text( $settings, 'label', __( 'Add to cart', 'speedpress-for-woocommerce' ) );
		add_filter(
			'woocommerce_product_single_add_to_cart_text',
			function () use ( $label ) {
				return $label;
			}
		);
		add_filter(
			'woocommerce_product_add_to_cart_text',
			function () use ( $label ) {
				return $label;
			}
		);
	}

	public static function remove_generator() {
		remove_action( 'wp_head', 'wc_generator_tag' );
	}

	public static function hide_related() {
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	}

	public static function hide_upsells() {
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	}

	public static function related_limit( $settings ) {
		add_filter(
			'woocommerce_output_related_products_args',
			function ( $args ) use ( $settings ) {
				$args['posts_per_page'] = max( 1, (int) self::text( $settings, 'count', '4' ) );
				return $args;
			}
		);
	}

	public static function min_qty( $settings ) {
		add_filter(
			'woocommerce_quantity_input_min',
			function ( $min, $product ) use ( $settings ) {
				return max( (int) $min, (int) self::text( $settings, 'qty', '1' ) );
			},
			10,
			2
		);
	}

	public static function max_qty( $settings ) {
		add_filter(
			'woocommerce_quantity_input_max',
			function ( $max, $product ) use ( $settings ) {
				$cap = (int) self::text( $settings, 'qty', '10' );
				return $cap > 0 ? $cap : $max;
			},
			10,
			2
		);
	}

	public static function sold_individually() {
		add_filter( 'woocommerce_is_sold_individually', '__return_true' );
	}

	public static function oos_text( $settings ) {
		add_filter(
			'woocommerce_get_availability_text',
			function ( $text, $product ) use ( $settings ) {
				if ( $product && ! $product->is_in_stock() ) {
					return self::text( $settings, 'text', $text );
				}
				return $text;
			},
			10,
			2
		);
	}

	public static function oos_note( $settings ) {
		add_action(
			'woocommerce_single_product_summary',
			function () use ( $settings ) {
				global $product;
				if ( $product && ! $product->is_in_stock() ) {
					echo '<p class="spwa-note">' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
				}
			},
			28
		);
	}

	public static function hide_meta() {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	}

	public static function share() {
		add_action(
			'woocommerce_single_product_summary',
			function () {
				$url = rawurlencode( get_permalink() );
				echo '<p class="spwa-share">';
				echo '<a href="mailto:?body=' . esc_attr( $url ) . '">' . esc_html__( 'Email', 'speedpress-for-woocommerce' ) . '</a> ';
				echo '<a target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url=' . esc_attr( $url ) . '">X</a>';
				echo '</p>';
			},
			36
		);
	}

	public static function theme_support( $settings ) {
		add_action(
			'wp',
			function () use ( $settings ) {
				$feature = self::text( $settings, 'feature' );
				if ( $feature ) {
					remove_theme_support( $feature );
				}
			}
		);
	}

	public static function empty_cart( $settings ) {
		add_action(
			'woocommerce_cart_actions',
			function () use ( $settings ) {
				$url = wp_nonce_url( add_query_arg( 'spwa_empty_cart', '1', wc_get_cart_url() ), 'spwa_empty_cart' );
				echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html( self::text( $settings, 'label', __( 'Empty cart', 'speedpress-for-woocommerce' ) ) ) . '</a>';
			}
		);
		add_action(
			'template_redirect',
			function () {
				if ( empty( $_GET['spwa_empty_cart'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					return;
				}
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'spwa_empty_cart' ) ) {
					return;
				}
				if ( function_exists( 'WC' ) && WC()->cart ) {
					WC()->cart->empty_cart();
				}
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		);
	}

	public static function continue_shopping( $settings ) {
		add_action(
			'woocommerce_cart_actions',
			function () use ( $settings ) {
				echo '<a class="button" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html( self::text( $settings, 'label', __( 'Continue shopping', 'speedpress-for-woocommerce' ) ) ) . '</a>';
			}
		);
	}

	public static function disable_coupons() {
		add_filter( 'woocommerce_coupons_enabled', '__return_false' );
	}

	public static function place_order( $settings ) {
		add_filter(
			'woocommerce_order_button_text',
			function () use ( $settings ) {
				return self::text( $settings, 'label', __( 'Place order', 'speedpress-for-woocommerce' ) );
			}
		);
	}

	public static function require_phone() {
		add_filter(
			'woocommerce_checkout_fields',
			function ( $fields ) {
				if ( isset( $fields['billing']['billing_phone'] ) ) {
					$fields['billing']['billing_phone']['required'] = true;
				}
				return $fields;
			}
		);
	}

	public static function thankyou_redirect( $settings ) {
		add_action(
			'template_redirect',
			function () use ( $settings ) {
				if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
					return;
				}
				$url = esc_url_raw( self::text( $settings, 'url' ) );
				if ( $url ) {
					wp_safe_redirect( $url );
					exit;
				}
			}
		);
	}

	public static function hide_order_again() {
		remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
	}

	public static function gift_wrap( $settings ) {
		add_action(
			'woocommerce_review_order_before_payment',
			function () use ( $settings ) {
				echo '<p class="spwa-note"><label><input type="checkbox" name="spwa_gift_wrap" value="1"> ' . esc_html( self::text( $settings, 'label', __( 'Gift wrap', 'speedpress-for-woocommerce' ) ) ) . '</label></p>';
			}
		);
		add_action(
			'woocommerce_cart_calculate_fees',
			function ( $cart ) use ( $settings ) {
				if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
					return;
				}
				if ( empty( $_POST['spwa_gift_wrap'] ) && empty( WC()->session->get( 'spwa_gift_wrap' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					return;
				}
				$fee = (float) self::text( $settings, 'fee', '5' );
				if ( $fee > 0 ) {
					$cart->add_fee( self::text( $settings, 'label', __( 'Gift wrap', 'speedpress-for-woocommerce' ) ), $fee );
				}
			}
		);
	}

	public static function checkout_notice( $settings ) {
		add_action(
			'woocommerce_before_checkout_form',
			function () use ( $settings ) {
				echo '<p class="spwa-note">' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
			}
		);
	}

	public static function cart_notice( $settings ) {
		add_action(
			'woocommerce_before_cart',
			function () use ( $settings ) {
				echo '<p class="spwa-note">' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
			}
		);
	}

	public static function hide_ship_calc() {
		add_filter( 'woocommerce_cart_ready_to_calc_shipping', '__return_false' );
	}

	public static function rename_shipping( $settings ) {
		add_filter(
			'woocommerce_package_rates',
			function ( $rates ) use ( $settings ) {
				$find = self::text( $settings, 'find' );
				$repl = self::text( $settings, 'replace' );
				if ( '' === $find || '' === $repl ) {
					return $rates;
				}
				foreach ( $rates as $rate ) {
					if ( false !== stripos( $rate->get_label(), $find ) ) {
						$rate->set_label( $repl );
					}
				}
				return $rates;
			}
		);
	}

	public static function pickup_note( $settings ) {
		add_action(
			'woocommerce_review_order_after_shipping',
			function () use ( $settings ) {
				$chosen = WC()->session ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();
				$hit    = false;
				foreach ( (array) $chosen as $method ) {
					if ( false !== strpos( (string) $method, 'local_pickup' ) ) {
						$hit = true;
					}
				}
				if ( $hit ) {
					echo '<tr><td colspan="2"><p class="spwa-note">' . esc_html( self::text( $settings, 'text' ) ) . '</p></td></tr>';
				}
			}
		);
	}

	public static function free_label( $settings ) {
		add_filter(
			'woocommerce_package_rates',
			function ( $rates ) use ( $settings ) {
				$label = self::text( $settings, 'label', __( 'Free delivery', 'speedpress-for-woocommerce' ) );
				foreach ( $rates as $rate ) {
					if ( 'free_shipping' === $rate->method_id ) {
						$rate->set_label( $label );
					}
				}
				return $rates;
			}
		);
	}

	public static function virtual_shipping() {
		add_filter(
			'woocommerce_cart_needs_shipping_address',
			function ( $needs ) {
				if ( WC()->cart && ! WC()->cart->needs_shipping() ) {
					return false;
				}
				return $needs;
			}
		);
	}

	public static function link_button( $settings ) {
		add_action(
			'woocommerce_after_add_to_cart_form',
			function () use ( $settings ) {
				$label = self::text( $settings, 'label', __( 'Contact', 'speedpress-for-woocommerce' ) );
				if ( ! empty( $settings['phone'] ) ) {
					$href = 'tel:' . preg_replace( '/[^\d+]/', '', (string) $settings['phone'] );
				} else {
					$href = self::text( $settings, 'url' );
				}
				if ( ! $href ) {
					return;
				}
				echo '<a class="button spwa-linkbtn" href="' . esc_url( $href ) . '">' . esc_html( $label ) . '</a>';
			}
		);
	}

	public static function register_note( $settings ) {
		add_action(
			'woocommerce_register_form_start',
			function () use ( $settings ) {
				echo '<p class="spwa-note">' . esc_html( self::text( $settings, 'text', self::text( $settings, 'note' ) ) ) . '</p>';
			}
		);
	}

	public static function dequeue_fragments() {
		add_action(
			'wp_enqueue_scripts',
			function () {
				if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
					return;
				}
				wp_dequeue_script( 'wc-cart-fragments' );
			},
			99
		);
	}

	public static function dequeue_meter() {
		add_action(
			'wp_print_scripts',
			function () {
				wp_dequeue_script( 'wc-password-strength-meter' );
			},
			100
		);
	}

	public static function per_page( $settings ) {
		add_filter(
			'loop_shop_per_page',
			function () use ( $settings ) {
				return max( 1, (int) self::text( $settings, 'count', '12' ) );
			}
		);
	}

	public static function columns( $settings ) {
		add_filter(
			'loop_shop_columns',
			function () use ( $settings ) {
				return max( 1, (int) self::text( $settings, 'count', '3' ) );
			}
		);
	}

	public static function login_redirect() {
		add_filter(
			'woocommerce_login_redirect',
			function () {
				return wc_get_page_permalink( 'myaccount' );
			}
		);
	}

	public static function logout_redirect() {
		add_filter(
			'woocommerce_logout_default_redirect_url',
			function () {
				return wc_get_page_permalink( 'shop' );
			}
		);
	}

	public static function account_welcome( $settings ) {
		add_action(
			'woocommerce_account_dashboard',
			function () use ( $settings ) {
				echo '<p class="spwa-note">' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
			}
		);
	}

	public static function hide_downloads() {
		add_filter(
			'woocommerce_account_menu_items',
			function ( $items ) {
				unset( $items['downloads'] );
				return $items;
			}
		);
	}

	public static function orders_note( $settings ) {
		add_action(
			'woocommerce_before_account_orders',
			function () use ( $settings ) {
				echo '<p class="spwa-note">' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
			}
		);
	}

	public static function hide_oos() {
		add_action(
			'woocommerce_product_query',
			function ( $q ) {
				$meta   = (array) $q->get( 'meta_query' );
				$meta[] = array(
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '=',
				);
				$q->set( 'meta_query', $meta );
			}
		);
	}

	public static function backorder_text( $settings ) {
		add_filter(
			'woocommerce_get_availability_text',
			function ( $text, $product ) use ( $settings ) {
				if ( $product && $product->is_on_backorder() ) {
					return self::text( $settings, 'text', $text );
				}
				return $text;
			},
			10,
			2
		);
	}

	public static function low_threshold( $settings ) {
		add_filter(
			'pre_option_woocommerce_notify_low_stock_amount',
			function () use ( $settings ) {
				return (string) max( 0, (int) self::text( $settings, 'amount', '2' ) );
			}
		);
	}

	public static function stock_badge( $settings ) {
		add_action(
			'woocommerce_after_shop_loop_item_title',
			function () use ( $settings ) {
				global $product;
				if ( ! $product ) {
					return;
				}
				if ( ! $product->is_in_stock() ) {
					echo '<span class="spwa-badge spwa-badge--oos">' . esc_html__( 'Out of stock', 'speedpress-for-woocommerce' ) . '</span>';
					return;
				}
				$qty = $product->managing_stock() ? (int) $product->get_stock_quantity() : 0;
				$low = (int) self::text( $settings, 'low', '5' );
				if ( $qty > 0 && $qty <= $low ) {
					/* translators: %d: remaining stock quantity */
					echo '<span class="spwa-badge">' . esc_html( sprintf( __( 'Only %d left', 'speedpress-for-woocommerce' ), $qty ) ) . '</span>';
				}
			}
		);
	}

	public static function honeypot() {
		add_action(
			'woocommerce_after_checkout_billing_form',
			function () {
				echo '<p style="position:absolute;left:-9999px" aria-hidden="true"><label>Company website<input type="text" name="spwa_hp" tabindex="-1" autocomplete="off"></label></p>';
			}
		);
		add_action(
			'woocommerce_checkout_process',
			function () {
				if ( ! empty( $_POST['spwa_hp'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					wc_add_notice( __( 'Checkout could not be completed.', 'speedpress-for-woocommerce' ), 'error' );
				}
			}
		);
	}

	public static function hide_admin_bar() {
		add_filter(
			'show_admin_bar',
			function ( $show ) {
				if ( current_user_can( 'manage_woocommerce' ) ) {
					return $show;
				}
				return false;
			}
		);
	}

	public static function login_wall() {
		add_action(
			'template_redirect',
			function () {
				if ( is_user_logged_in() || is_admin() ) {
					return;
				}
				if ( ( function_exists( 'is_shop' ) && is_shop() ) || is_product() || is_product_taxonomy() ) {
					wp_safe_redirect( wp_login_url( get_permalink() ) );
					exit;
				}
			}
		);
	}

	public static function privacy_note( $settings ) {
		add_action(
			'woocommerce_review_order_after_submit',
			function () use ( $settings ) {
				echo '<p class="spwa-note">' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
			}
		);
	}

	public static function webhook( $settings ) {
		add_action(
			'woocommerce_new_order',
			function ( $order_id ) use ( $settings ) {
				$url = esc_url_raw( self::text( $settings, 'url' ) );
				if ( ! $url ) {
					return;
				}
				$order = wc_get_order( $order_id );
				wp_remote_post(
					$url,
					array(
						'timeout' => 5,
						'body'    => array(
							'order_id' => $order_id,
							'total'    => $order ? $order->get_total() : '',
							'status'   => $order ? $order->get_status() : '',
						),
					)
				);
			}
		);
	}

	public static function email_footer( $settings ) {
		add_filter(
			'woocommerce_email_footer_text',
			function ( $text ) use ( $settings ) {
				$extra = self::text( $settings, 'text' );
				return $extra ? $text . "\n" . $extra : $text;
			}
		);
	}

	public static function hide_breadcrumbs() {
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	}

	public static function shop_title( $settings ) {
		add_filter(
			'woocommerce_page_title',
			function ( $title ) use ( $settings ) {
				if ( function_exists( 'is_shop' ) && is_shop() ) {
					return self::text( $settings, 'title', $title );
				}
				return $title;
			}
		);
	}

	public static function hide_term_desc() {
		add_filter( 'woocommerce_taxonomy_archive_description_raw', '__return_empty_string' );
	}

	public static function trim_excerpt( $settings ) {
		add_filter(
			'woocommerce_short_description',
			function ( $desc ) use ( $settings ) {
				$words = max( 1, (int) self::text( $settings, 'words', '24' ) );
				return wp_trim_words( wp_strip_all_tags( $desc ), $words );
			}
		);
	}

	public static function mobile_bar( $settings ) {
		add_action(
			'wp_footer',
			function () use ( $settings ) {
				$phone = preg_replace( '/[^\d+]/', '', self::text( $settings, 'phone' ) );
				if ( ! $phone ) {
					return;
				}
				echo '<a class="spwa-mobilebar" href="tel:' . esc_attr( $phone ) . '">' . esc_html( self::text( $settings, 'label', __( 'Call the shop', 'speedpress-for-woocommerce' ) ) ) . '</a>';
			}
		);
	}

	public static function autocomplete_virtual() {
		add_action(
			'woocommerce_payment_complete',
			function ( $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					return;
				}
				$virtual = true;
				foreach ( $order->get_items() as $item ) {
					$product = $item->get_product();
					if ( $product && ! $product->is_virtual() ) {
						$virtual = false;
					}
				}
				if ( $virtual ) {
					$order->update_status( 'completed', __( 'Virtual order auto-completed by SpeedPress.', 'speedpress-for-woocommerce' ) );
				}
			}
		);
	}

	public static function hold_stock( $settings ) {
		add_filter(
			'pre_option_woocommerce_hold_stock_minutes',
			function () use ( $settings ) {
				return (string) max( 1, (int) self::text( $settings, 'minutes', '60' ) );
			}
		);
	}

	public static function order_column() {
		add_filter(
			'manage_edit-shop_order_columns',
			function ( $cols ) {
				$cols['spwa_items'] = __( 'Items', 'speedpress-for-woocommerce' );
				return $cols;
			}
		);
		add_action(
			'manage_shop_order_posts_custom_column',
			function ( $col, $post_id ) {
				if ( 'spwa_items' !== $col ) {
					return;
				}
				$order = wc_get_order( $post_id );
				echo $order ? (int) $order->get_item_count() : '';
			},
			10,
			2
		);
	}

	public static function export_orders() {
		add_action(
			'admin_menu',
			function () {
				add_submenu_page(
					'woocommerce',
					__( 'Export orders', 'speedpress-for-woocommerce' ),
					__( 'Export orders', 'speedpress-for-woocommerce' ),
					'manage_woocommerce',
					'spwa-export-orders',
					function () {
						if ( ! current_user_can( 'manage_woocommerce' ) ) {
							return;
						}
						if ( isset( $_GET['spwa_export'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'spwa_export' ) ) {
							$orders = wc_get_orders( array( 'limit' => 200, 'orderby' => 'date', 'order' => 'DESC' ) );
							header( 'Content-Type: text/csv; charset=utf-8' );
							header( 'Content-Disposition: attachment; filename=orders.csv' );
							echo "id,status,total,email,date\n";
							foreach ( $orders as $order ) {
								echo esc_html( $order->get_id() ) . ',' . esc_html( $order->get_status() ) . ',' . esc_html( $order->get_total() ) . ',' . esc_html( $order->get_billing_email() ) . ',' . esc_html( $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : '' ) . "\n";
							}
							exit;
						}
						$url = wp_nonce_url( admin_url( 'admin.php?page=spwa-export-orders&spwa_export=1' ), 'spwa_export' );
						echo '<div class="wrap"><h1>' . esc_html__( 'Export orders', 'speedpress-for-woocommerce' ) . '</h1><p><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'Download last 200 orders', 'speedpress-for-woocommerce' ) . '</a></p></div>';
					}
				);
			}
		);
	}

	public static function top_sellers() {
		add_action(
			'wp_dashboard_setup',
			function () {
				wp_add_dashboard_widget(
					'spwa_top_sellers',
					__( 'Top sellers', 'speedpress-for-woocommerce' ),
					function () {
						$products = wc_get_products( array( 'limit' => 5, 'orderby' => 'popularity', 'order' => 'DESC', 'status' => 'publish' ) );
						if ( ! $products ) {
							echo '<p>' . esc_html__( 'No products yet.', 'speedpress-for-woocommerce' ) . '</p>';
							return;
						}
						echo '<ol>';
						foreach ( $products as $product ) {
							echo '<li>' . esc_html( $product->get_name() ) . ' — ' . esc_html( (string) $product->get_total_sales() ) . '</li>';
						}
						echo '</ol>';
					}
				);
			}
		);
	}

	public static function price_suffix( $settings ) {
		add_filter(
			'woocommerce_get_price_suffix',
			function ( $suffix ) use ( $settings ) {
				$extra = self::text( $settings, 'suffix' );
				return $extra ? ' <small class="spwa-suffix">' . esc_html( $extra ) . '</small>' : $suffix;
			}
		);
	}

	public static function guest_off() {
		add_filter( 'pre_option_woocommerce_enable_guest_checkout', function () { return 'no'; } );
	}

	public static function ajax_archive() {
		add_filter( 'pre_option_woocommerce_enable_ajax_add_to_cart', function () { return 'yes'; } );
	}

	public static function reviews_off() {
		add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
			unset( $tabs['reviews'] );
			return $tabs;
		} );
	}
}
