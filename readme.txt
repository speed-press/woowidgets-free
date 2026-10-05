=== SpeedPress for WooCommerce ===
Contributors: devlaju
Tags: woocommerce, addons, checkout, wishlist, performance
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modular WooCommerce addons with a SaaS-style admin. Turn each feature on with a toggle.

== Description ==

SpeedPress for WooCommerce is a module console for WooCommerce. Nothing runs until you flip its toggle.

Included modules:

* Speed boost (cart fragments, password meter, marketplace nags)
* Buy Now
* Free shipping progress bar
* Sticky add to cart
* Cart order bump
* WhatsApp order
* Product views
* Sold counter
* Low stock urgency
* Product badges
* Wishlist (`[spwa_wishlist]`)
* Recently viewed (`[spwa_recently_viewed]`)
* Catalog mode
* Back in stock alerts
* Delivery estimate
* Auto-apply coupon
* Checkout simplifier
* Free shipping wins
* Minimum order
* Thank-you message

Other plugins can register modules with the `spwa_register_modules` filter. Disabled modules do not load their hooks.

== Installation ==

1. Upload the `speedpress-for-woocommerce` folder to `/wp-content/plugins/`.
2. Activate WooCommerce, then activate this plugin.
3. Open SpeedPress in the admin menu and toggle the modules you want.

== Frequently Asked Questions ==

= Can I enable one feature and leave the rest off? =

Yes. Each module has its own toggle. Off means its hooks are not registered.

= Can another plugin add a module? =

Yes. Filter `spwa_register_modules` and return your module array. See the Extend tab in the plugin admin.

= Will this slow the store? =

Only enabled modules load. The speed boost module removes cart-fragment requests on pages that are not cart, checkout, or account.

== Changelog ==

= 1.2.0 =
* Suite now ships 100 addons across the sidebar groups.
* Fixed the SPWA menu icon and the admin layout inside wp-admin.

= 1.1.1 =
* Admin rebuilt to the suite layout: sidebar groups, Free badge, Disabled toggle, gear settings, search.

= 1.1.0 =
* Replaced the compiled admin bundle with a no-build SaaS module console.
* Added the most requested store modules: Buy Now, shipping bar, checkout simplifier, wishlist, back in stock, badges, order bump, WhatsApp, and speed boost.
* Added `spwa_register_modules` so other plugins can register toggles.

= 1.0.0 =
* Initial release.
