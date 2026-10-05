# SpeedPress for WooCommerce

Modular WooCommerce widgets with a SaaS-style admin. Nothing runs until its toggle is on.

- Plugin name: SpeedPress for WooCommerce
- Menu label: WooWidget
- Version: 1.4.3
- Author: Md Laju Miah
- Text domain: `speedpress-for-woocommerce`
- Requires: WordPress 6.0, PHP 7.4, WooCommerce 8.0
- Tested up to: WordPress 7.1

## Install

1. Copy the `speedpress-for-woocommerce` folder into `wp-content/plugins/`.
2. Activate SpeedPress for WooCommerce.
3. Open **WooWidget** in the WordPress admin.
4. Turn on only the widgets you need. Settings appear after a widget is enabled.

## Admin

The suite is a two-column screen.

- Left sidebar: dashboard and widget libraries.
- Top bar: breadcrumb and search (`Ctrl/Cmd + K`).
- Widget cards: title, Free badge, description, toggle, and a settings icon.
- Settings open in a popup. The settings icon is hidden while the widget is off.
- Dashboard: live count, recommended enable, enable free, disable all, store health, and library cards.

Brand color: `linear-gradient(135deg,#2563EB,#9333EA)`.

Cards are four across on a wide screen.

## What is included

209 widgets.

- 23 dedicated modules in `includes/modules/`
- 77 catalog widgets in `includes/addons-catalog.php`
- 109 extra widgets in `includes/addons-catalog-extra.php`

Dedicated modules include Buy Now, wishlist, catalog mode, low stock, back in stock, free-shipping bar, order bump, sticky add to cart, WhatsApp order, maintenance mode, and speed boost.

Catalog widgets cover product pages, cart and checkout, shipping labels, account screens, stock, emails, SEO, and admin lists. Each one boots only after it is enabled.

## File map

```
speedpress-for-woocommerce.php          Bootstrap, version, requires
readme.txt                              WordPress.org readme
uninstall.php                           Cleanup on delete
assets/admin.css                        Suite styles
assets/admin.js                         Toggles, search, settings popup
assets/front.css                        Storefront styles
assets/front.js                         Storefront behavior
includes/class-spwa-plugin.php          Lifecycle
includes/class-spwa-admin.php           Menu, dashboard, ajax
includes/class-spwa-modules.php         Registry and enable map
includes/class-spwa-addon-runner.php    Catalog hook runner
includes/class-spwa-addon-pack.php      Extra widget hooks
includes/addons-catalog.php             First catalog
includes/addons-catalog-extra.php       Second catalog
includes/modules/*.php                  Dedicated widgets
languages/                              Translation folder
```

## Options

- `spwa_modules` — slug to enabled map
- `spwa_module_settings` — saved settings per slug

Ajax actions, all nonce-checked with `spwa_admin`:

- `spwa_toggle`
- `spwa_save_settings`
- `spwa_bulk` — `enable_recommended`, `enable_free`, `disable_all`

## Add a widget

Return an array from a file in `includes/modules/`, or append a catalog entry.

```php
<?php
defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'gift-note',
	'name'        => 'Gift Note',
	'description' => 'Shows a gift note on the cart.',
	'category'    => 'cart',
	'tier'        => 'free',
	'demand'      => 'high',
	'settings'    => array(
		array(
			'key'     => 'text',
			'label'   => 'Note',
			'type'    => 'text',
			'default' => 'Leave a gift message in the order notes.',
		),
	),
	'boot'        => function ( $settings ) {
		add_action( 'woocommerce_before_cart', function () use ( $settings ) {
			if ( empty( $settings['text'] ) ) {
				return;
			}
			echo '<p class="spwa-note">' . esc_html( $settings['text'] ) . '</p>';
		} );
	},
);
```

Catalog entries use a `type` that maps to a method on `SPWA_Addon_Runner` or `SPWA_Addon_Pack`.

Other plugins can register modules with the `spwa_register_modules` filter.

## Categories

`dashboard`, `general`, `product`, `cart`, `shipping`, `marketing`, `seo`, `customer`, `inventory`, `security`, `integrations`, `content`, `mobile`, `automation`, `reports`

## License

GPL v2 or later.
