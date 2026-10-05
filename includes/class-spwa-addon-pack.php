<?php
/**
 * Hooks for the extra addon pack.
 *
 * @package SpeedPress_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

class SPWA_Addon_Pack {

	private static function text( $settings, $key, $fallback = '' ) {
		return isset( $settings[ $key ] ) && '' !== (string) $settings[ $key ] ? (string) $settings[ $key ] : $fallback;
	}

	private static function note( $hook, $settings, $priority = 20 ) {
		add_action(
			$hook,
			function () use ( $settings ) {
				$text = self::text( $settings, 'text', self::text( $settings, 'note' ) );
				if ( '' === $text ) {
					return;
				}
				echo '<p class="spwa-note">' . esc_html( $text ) . '</p>';
			},
			$priority
		);
	}

	public static function product_note( $settings ) {
		self::note( 'woocommerce_single_product_summary', $settings, 24 );
	}

	public static function cart_note( $settings ) {
		self::note( 'woocommerce_before_cart', $settings, 8 );
	}

	public static function checkout_note( $settings ) {
		self::note( 'woocommerce_before_checkout_form', $settings, 8 );
	}

	public static function thankyou_note( $settings ) {
		self::note( 'woocommerce_thankyou', $settings, 8 );
	}

	public static function account_note( $settings ) {
		self::note( 'woocommerce_account_content', $settings, 4 );
	}

	public static function shop_note( $settings ) {
		self::note( 'woocommerce_before_shop_loop', $settings, 6 );
	}

	public static function payment_note( $settings ) {
		self::note( 'woocommerce_review_order_before_payment', $settings, 8 );
	}

	public static function email_note( $settings ) {
		add_action(
			'woocommerce_email_before_order_table',
			function () use ( $settings ) {
				$text = self::text( $settings, 'text' );
				if ( '' !== $text ) {
					echo '<p>' . esc_html( $text ) . '</p>';
				}
			}
		);
	}

	public static function footer_note( $settings ) {
		add_action(
			'wp_footer',
			function () use ( $settings ) {
				if ( ! function_exists( 'is_woocommerce' ) || ! ( is_woocommerce() || is_cart() || is_checkout() ) ) {
					return;
				}
				$text = self::text( $settings, 'text', self::text( $settings, 'hours' ) );
				if ( '' !== $text ) {
					echo '<p class="spwa-note" style="text-align:center">' . esc_html( $text ) . '</p>';
				}
			}
		);
	}

	public static function integrations_note( $settings ) {
		add_action(
			'admin_notices',
			function () use ( $settings ) {
				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					return;
				}
				echo '<div class="notice notice-info"><p>' . esc_html( self::text( $settings, 'text' ) ) . '</p></div>';
			}
		);
	}

	public static function product_link( $settings ) {
		add_action(
			'woocommerce_single_product_summary',
			function () use ( $settings ) {
				$url = self::text( $settings, 'url' );
				if ( '' === $url ) {
					return;
				}
				echo '<p class="spwa-note"><a href="' . esc_url( $url ) . '">' . esc_html( self::text( $settings, 'label', __( 'Learn more', 'speedpress-for-woocommerce' ) ) ) . '</a></p>';
			},
			26
		);
	}

	public static function show_weight( $settings ) {
		add_action(
			'woocommerce_single_product_summary',
			function () {
				global $product;
				if ( $product && $product->has_weight() ) {
					echo '<p class="spwa-note">' . esc_html__( 'Weight', 'speedpress-for-woocommerce' ) . ': ' . esc_html( $product->get_weight() . ' ' . get_option( 'woocommerce_weight_unit' ) ) . '</p>';
				}
			},
			25
		);
	}

	public static function show_dimensions( $settings ) {
		add_action(
			'woocommerce_single_product_summary',
			function () {
				global $product;
				if ( $product && $product->has_dimensions() ) {
					echo '<p class="spwa-note">' . esc_html__( 'Dimensions', 'speedpress-for-woocommerce' ) . ': ' . esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) ) . '</p>';
				}
			},
			25
		);
	}

	public static function sale_flash( $settings ) {
		$label = self::text( $settings, 'label', __( 'Sale', 'speedpress-for-woocommerce' ) );
		add_filter(
			'woocommerce_sale_flash',
			function () use ( $label ) {
				return '<span class="onsale">' . esc_html( $label ) . '</span>';
			}
		);
	}

	public static function hide_sale_flash() {
		add_filter( 'woocommerce_sale_flash', '__return_empty_string' );
	}

	public static function variation_prompt( $settings ) {
		$label = self::text( $settings, 'label', __( 'Choose an option', 'speedpress-for-woocommerce' ) );
		add_filter(
			'woocommerce_dropdown_variation_attribute_options_args',
			function ( $args ) use ( $label ) {
				$args['show_option_none'] = $label;
				return $args;
			}
		);
	}

	public static function external_text( $settings ) {
		$label = self::text( $settings, 'label', __( 'Buy product', 'speedpress-for-woocommerce' ) );
		add_filter(
			'woocommerce_product_single_add_to_cart_text',
			function ( $text, $product ) use ( $label ) {
				return $product && $product->is_type( 'external' ) ? $label : $text;
			},
			10,
			2
		);
	}

	public static function custom_tab( $settings ) {
		add_filter(
			'woocommerce_product_tabs',
			function ( $tabs ) use ( $settings ) {
				$tabs['spwa_extra'] = array(
					'title'    => self::text( $settings, 'title', __( 'More', 'speedpress-for-woocommerce' ) ),
					'priority' => 40,
					'callback' => function () use ( $settings ) {
						echo '<p>' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
					},
				);
				return $tabs;
			}
		);
	}

	public static function hide_desc_tab() {
		add_filter(
			'woocommerce_product_tabs',
			function ( $tabs ) {
				unset( $tabs['description'] );
				return $tabs;
			}
		);
	}

	public static function hide_info_tab() {
		add_filter(
			'woocommerce_product_tabs',
			function ( $tabs ) {
				unset( $tabs['additional_information'] );
				return $tabs;
			}
		);
	}

	public static function hide_reviews_tab() {
		add_filter(
			'woocommerce_product_tabs',
			function ( $tabs ) {
				unset( $tabs['reviews'] );
				return $tabs;
			}
		);
	}

	public static function related_cols( $settings ) {
		add_filter(
			'woocommerce_output_related_products_args',
			function ( $args ) use ( $settings ) {
				$args['columns'] = max( 1, (int) self::text( $settings, 'count', '4' ) );
				return $args;
			}
		);
	}

	public static function upsell_limit( $settings ) {
		add_filter(
			'woocommerce_upsells_total',
			function () use ( $settings ) {
				return max( 1, (int) self::text( $settings, 'count', '4' ) );
			}
		);
	}

	public static function hide_cross() {
		remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
	}

	public static function cross_limit( $settings ) {
		add_filter(
			'woocommerce_cross_sells_total',
			function () use ( $settings ) {
				return max( 1, (int) self::text( $settings, 'count', '2' ) );
			}
		);
	}

	public static function qty_step( $settings ) {
		add_filter(
			'woocommerce_quantity_input_step',
			function () use ( $settings ) {
				return max( 1, (int) self::text( $settings, 'step', '1' ) );
			}
		);
	}

	public static function gallery_zoom_off() {
		add_filter( 'woocommerce_single_product_zoom_enabled', '__return_false' );
	}

	public static function gallery_lightbox_off() {
		add_filter( 'woocommerce_single_product_photoswipe_enabled', '__return_false' );
	}

	public static function gallery_slider_off() {
		add_filter( 'woocommerce_single_product_flexslider_enabled', '__return_false' );
	}

	public static function loop_button( $settings ) {
		$label = self::text( $settings, 'label', __( 'Add to cart', 'speedpress-for-woocommerce' ) );
		add_filter(
			'woocommerce_product_add_to_cart_text',
			function () use ( $label ) {
				return $label;
			}
		);
	}

	public static function hide_loop_price() {
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	}

	public static function hide_loop_rating() {
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
	}

	public static function stock_text( $settings ) {
		add_filter(
			'woocommerce_get_availability_text',
			function ( $text, $product ) use ( $settings ) {
				if ( $product && $product->is_in_stock() && ! $product->backorders_require_notification() ) {
					return self::text( $settings, 'text', $text );
				}
				return $text;
			},
			10,
			2
		);
	}

	public static function only_n_left( $settings ) {
		add_action(
			'woocommerce_single_product_summary',
			function () use ( $settings ) {
				global $product;
				$limit = max( 1, (int) self::text( $settings, 'limit', '5' ) );
				if ( $product && $product->managing_stock() && $product->get_stock_quantity() <= $limit ) {
					$left = (int) $product->get_stock_quantity();
					/* translators: %d: remaining stock quantity */
					echo '<p class="spwa-note">' . esc_html( sprintf( __( 'Only %d left.', 'speedpress-for-woocommerce' ), $left ) ) . '</p>';
				}
			},
			27
		);
	}

	public static function hide_oos_vars() {
		add_filter( 'woocommerce_hide_out_of_stock_items', '__return_true' );
	}

	public static function backorder_note( $settings ) {
		add_action(
			'woocommerce_single_product_summary',
			function () use ( $settings ) {
				global $product;
				if ( $product && $product->is_on_backorder() ) {
					echo '<p class="spwa-note">' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
				}
			},
			27
		);
	}

	public static function cart_redirect() {
		add_filter(
			'woocommerce_add_to_cart_redirect',
			function () {
				return wc_get_cart_url();
			}
		);
	}

	public static function empty_message( $settings ) {
		add_filter(
			'wc_empty_cart_message',
			function () use ( $settings ) {
				return self::text( $settings, 'text' );
			}
		);
	}

	public static function coupon_label( $settings ) {
		$label = self::text( $settings, 'label', __( 'Coupon', 'speedpress-for-woocommerce' ) );
		add_filter(
			'woocommerce_cart_totals_coupon_label',
			function () use ( $label ) {
				return $label;
			}
		);
	}

	public static function notes_placeholder( $settings ) {
		add_filter(
			'woocommerce_checkout_fields',
			function ( $fields ) use ( $settings ) {
				if ( isset( $fields['order']['order_comments'] ) ) {
					$fields['order']['order_comments']['placeholder'] = self::text( $settings, 'text' );
				}
				return $fields;
			}
		);
	}

	public static function require_company() {
		add_filter(
			'woocommerce_checkout_fields',
			function ( $fields ) {
				if ( isset( $fields['billing']['billing_company'] ) ) {
					$fields['billing']['billing_company']['required'] = true;
				}
				return $fields;
			}
		);
	}

	public static function hide_company() {
		add_filter(
			'woocommerce_checkout_fields',
			function ( $fields ) {
				unset( $fields['billing']['billing_company'], $fields['shipping']['shipping_company'] );
				return $fields;
			}
		);
	}

	public static function hide_address2() {
		add_filter(
			'woocommerce_checkout_fields',
			function ( $fields ) {
				unset( $fields['billing']['billing_address_2'], $fields['shipping']['shipping_address_2'] );
				return $fields;
			}
		);
	}

	public static function default_country( $settings ) {
		$code = strtoupper( self::text( $settings, 'code', 'US' ) );
		add_filter(
			'default_checkout_billing_country',
			function () use ( $code ) {
				return $code;
			}
		);
	}

	public static function create_account_checked() {
		add_filter( 'woocommerce_create_account_default_checked', '__return_true' );
	}

	public static function age_confirm( $settings ) {
		add_action(
			'woocommerce_review_order_before_submit',
			function () use ( $settings ) {
				echo '<p class="form-row"><label><input type="checkbox" name="spwa_age" value="1"> ' . esc_html( self::text( $settings, 'label' ) ) . '</label></p>';
			}
		);
		add_action(
			'woocommerce_checkout_process',
			function () {
				if ( empty( $_POST['spwa_age'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					wc_add_notice( __( 'Please confirm your age to place this order.', 'speedpress-for-woocommerce' ), 'error' );
				}
			}
		);
	}

	public static function newsletter() {
		add_action(
			'woocommerce_review_order_before_submit',
			function () {
				$label = __( 'Email me new work and restocks', 'speedpress-for-woocommerce' );
				echo '<p class="form-row"><label><input type="checkbox" name="spwa_news" value="1"> ' . esc_html( $label ) . '</label></p>';
			}
		);
		add_action(
			'woocommerce_checkout_update_order_meta',
			function ( $order_id ) {
				if ( ! empty( $_POST['spwa_news'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$order = wc_get_order( $order_id );
					if ( $order ) {
						$order->update_meta_data( '_spwa_newsletter', 'yes' );
						$order->save();
					}
				}
			}
		);
	}

	public static function rate_label( $settings ) {
		add_filter(
			'woocommerce_cart_shipping_method_full_label',
			function ( $label, $method ) use ( $settings ) {
				$from = self::text( $settings, 'from' );
				$to   = self::text( $settings, 'to' );
				if ( '' !== $from && false !== stripos( wp_strip_all_tags( $label ), $from ) ) {
					return str_ireplace( $from, $to, $label );
				}
				return $label;
			},
			10,
			2
		);
	}

	public static function hide_payments_tab( $settings ) {
		add_filter(
			'woocommerce_account_menu_items',
			function ( $items ) {
				unset( $items['payment-methods'] );
				return $items;
			}
		);
	}

	public static function hide_address_tab( $settings ) {
		add_filter(
			'woocommerce_account_menu_items',
			function ( $items ) {
				unset( $items['edit-address'] );
				return $items;
			}
		);
	}

	public static function empty_downloads( $settings ) {
		add_filter(
			'woocommerce_download_no_results',
			function () use ( $settings ) {
				echo '<p>' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
			}
		);
	}

	public static function empty_orders( $settings ) {
		add_action(
			'woocommerce_before_account_orders',
			function () use ( $settings ) {
				echo '<p class="spwa-note">' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
			}
		);
	}

	public static function noindex( $settings ) {
		add_action(
			'wp_head',
			function () use ( $settings ) {
				$page = self::text( $settings, 'pages', 'cart' );
				$match = ( 'checkout' === $page && function_exists( 'is_checkout' ) && is_checkout() )
					|| ( 'account' === $page && function_exists( 'is_account_page' ) && is_account_page() )
					|| ( 'cart' === $page && function_exists( 'is_cart' ) && is_cart() );
				if ( $match ) {
					echo '<meta name="robots" content="noindex,nofollow">' . "\n";
				}
			}
		);
	}

	public static function title_suffix( $settings ) {
		add_filter(
			'document_title_parts',
			function ( $parts ) use ( $settings ) {
				if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() ) ) {
					$parts['title'] .= ' ' . self::text( $settings, 'text' );
				}
				return $parts;
			}
		);
	}

	public static function breadcrumb_home( $settings ) {
		add_filter(
			'woocommerce_breadcrumb_defaults',
			function ( $args ) use ( $settings ) {
				$args['home'] = self::text( $settings, 'label', __( 'Home', 'speedpress-for-woocommerce' ) );
				return $args;
			}
		);
	}

	public static function hide_sorting() {
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	}

	public static function hide_result_count() {
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	}

	public static function default_sort( $settings ) {
		add_filter(
			'woocommerce_default_catalog_orderby',
			function () use ( $settings ) {
				return self::text( $settings, 'orderby', 'menu_order' );
			}
		);
	}

	public static function no_products( $settings ) {
		add_filter(
			'woocommerce_no_products_found',
			function () use ( $settings ) {
				echo '<p class="woocommerce-info">' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
			}
		);
	}

	public static function search_empty( $settings ) {
		add_action(
			'woocommerce_no_products_found',
			function () use ( $settings ) {
				if ( is_search() ) {
					echo '<p class="spwa-note">' . esc_html( self::text( $settings, 'text' ) ) . '</p>';
				}
			}
		);
	}

	public static function tap_css() {
		add_action(
			'wp_head',
			function () {
				echo '<style>.single_add_to_cart_button,.checkout-button,#place_order{min-height:46px;padding:12px 18px}</style>';
			}
		);
	}

	public static function autocomplete_paid() {
		add_action(
			'woocommerce_payment_complete',
			function ( $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order && $order->is_paid() ) {
					$order->update_status( 'completed', __( 'Autocomplete paid order.', 'speedpress-for-woocommerce' ) );
				}
			}
		);
	}

	public static function hold_minutes( $settings ) {
		add_filter(
			'woocommerce_hold_stock_minutes',
			function () use ( $settings ) {
				return max( 1, (int) self::text( $settings, 'minutes', '60' ) );
			}
		);
	}

	public static function orders_per_page( $settings ) {
		add_filter(
			'woocommerce_my_account_my_orders_query',
			function ( $args ) use ( $settings ) {
				$args['posts_per_page'] = max( 1, (int) self::text( $settings, 'count', '20' ) );
				return $args;
			}
		);
	}

	public static function products_per_page( $settings ) {
		add_filter(
			'edit_product_per_page',
			function () use ( $settings ) {
				return max( 1, (int) self::text( $settings, 'count', '20' ) );
			}
		);
	}

	public static function stock_column() {
		add_filter(
			'manage_edit-product_columns',
			function ( $columns ) {
				$columns['spwa_stock'] = __( 'Stock', 'speedpress-for-woocommerce' );
				return $columns;
			}
		);
		add_action(
			'manage_product_posts_custom_column',
			function ( $column, $post_id ) {
				if ( 'spwa_stock' !== $column ) {
					return;
				}
				$product = wc_get_product( $post_id );
				echo $product ? esc_html( $product->get_stock_status() ) : '';
			},
			10,
			2
		);
	}

	public static function admin_footer() {
		add_filter(
			'admin_footer_text',
			function ( $text ) {
				return $text . ' · ' . esc_html( get_bloginfo( 'name' ) );
			}
		);
	}

	public static function brand_schema( $settings ) {
		add_filter(
			'woocommerce_structured_data_product',
			function ( $markup ) use ( $settings ) {
				$markup['brand'] = array(
					'@type' => 'Brand',
					'name'  => self::text( $settings, 'brand', get_bloginfo( 'name' ) ),
				);
				return $markup;
			}
		);
	}

	public static function price_suffix( $settings ) {
		add_filter(
			'woocommerce_get_price_suffix',
			function ( $suffix ) use ( $settings ) {
				return ' <small class="spwa-note">' . esc_html( self::text( $settings, 'text' ) ) . '</small>';
			}
		);
	}

	public static function lean_fragments() {
		add_action(
			'wp_enqueue_scripts',
			function () {
				if ( function_exists( 'is_cart' ) && ! is_cart() && ! is_checkout() ) {
					wp_dequeue_script( 'wc-cart-fragments' );
				}
			},
			99
		);
	}

	public static function placeholder_img( $settings ) {
		add_filter(
			'woocommerce_placeholder_img_src',
			function ( $src ) use ( $settings ) {
				$url = self::text( $settings, 'url' );
				return '' !== $url ? $url : $src;
			}
		);
	}

	public static function honeypot_pack() {
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
}
