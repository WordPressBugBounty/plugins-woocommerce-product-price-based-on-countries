=== Price Based on Country for WooCommerce ===
Contributors: oscargare
Tags:  woocommerce, price based country, price by country, geoip, woocommerce-multi-currency
Requires at least: 3.8
Tested up to: 6.8
Stable tag: 4.0.11
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add multicurrency support to WooCommerce, allowing you set product's prices in multiple currencies based on country of your site's visitor.

== Description ==

**Price Based on Country for WooCommerce** allows you to sell the same product in multiple currencies based on the country of the customer.

= How it works =

The plugin detects automatically the country of the website visitor throught the geolocation feature included in WooCommerce (2.3.0 or later) and display the currency and price you have defined previously for this country.

You have two ways to set product's price for each country:

* Calculate price by applying the exchange rate.
* Set price manually.

When country changes on checkout page, the cart, the order preview and all shop are updated to display the correct currency and pricing.

= Multicurrency =
Sell and receive payments in different currencies, reducing the costs of currency conversions.

= Country Switcher =
The extension include a country switcher widget to allow your customer change the country from the frontend of your website.

= Shipping currency conversion =
Apply currency conversion to Flat and International Flat Rate Shipping.

= Compatible with WPML =
WooCommerce Product Price Based on Countries is officially compatible with [WPML](https://wpml.org/extensions/woocommerce-product-price-based-countries/).

= Upgrade to Pro =

>This plugin offers a Pro addon which adds the following features:

>* Guaranteed support by private ticket system.
>* Automatic updates of exchange rates.
>* Add an exchange rate fee.
>* Round to nearest.
>* Display the currency code next to price.
>* Compatible with the WooCommerce built-in CSV importer and exporter.
>* Thousand separator, decimal separator and number of decimals by pricing zone.
>* Currency switcher widget.
>* Support to WooCommerce Subscriptions by Prospress .
>* Support to WooCommerce Product Bundles by SomewhereWarm .
>* Support to WooCommerce Product Add-ons by WooCommerce .
>* Support to WooCommerce Bookings by WooCommerce .
>* Support to WooCommerce Composite Product by SomewhereWarm.
>* Support to WooCommerce Name Your Price by Kathy Darling.
>* Bulk editing of variations princing.
>* Support for manual orders.
>* More features and integrations is coming.

>[Get Price Based on Country Pro now](https://www.pricebasedcountry.com?utm_source=wordpress.org&utm_medium=readme&utm_campaign=Extend)

= Requirements =

* WooCommerce 3.4 or later.
* If you want to receive payments in more of one currency, a payment gateway that supports them.

== Installation ==

1. Download, install and activate the plugin.
1. Go to WooCommerce -> Settings -> Product Price Based on Country and configure as required.
1. Go to the product page and sets the price for the countries you have configured avobe.

= Adding a country selector to the front-end =

Once you’ve added support for multiple country and their currencies, you could display a country selector in the theme. You can display the country selector with a shortcode or as a hook.

**Shortcode**

[wcpbc_country_selector other_countries_text="Other countries"]

**PHP Code**

do_action('wcpbc_manual_country_selector', 'Other countries');

= Customize country selector (only for developers) =

1. Add action "wcpbc_manual_country_selector" to your theme.
1. To customize the country selector:
	1. Create a directory named "woocommerce-product-price-based-on-countries" in your theme directory.
	1. Copy to the directory created avobe the file "country-selector.php" included in the plugin.
	1. Work with this file.

== Frequently Asked Questions ==

= How might I test if the prices are displayed correctly for a given country? =

If you are in a test environment, you can configure the test mode in the setting page.

In a production environment you can use a privacy VPN tools like [TunnelBear](https://www.tunnelbear.com/) or [ZenMate](https://zenmate.com/)

You should do the test in a private browsing window to prevent data stored in the session. Open a private window on [Firefox](https://support.mozilla.org/en-US/kb/private-browsing-use-firefox-without-history#w_how-do-i-open-a-new-private-window) or on [Chrome](https://support.google.com/chromebook/answer/95464?hl=en)

== Screenshots ==

1. Simple to get started with the Geolocation setup wizard.
2. Unlimited price zones.
3. Pricing zone properties.
4. Pricing zone properties (2).
5. Plugin settings.
6. Set the price manually or calculate by the exchange rate.
7. Includes a country selector widget.

== Changelog ==

= 4.0.11 (2025-09-23) =
* Added: Tested up WooCommerce 10.2+.
* Added: Tested up WordPress 6.8+.
* Tweak: Code improvements in the function that runs on the cart shipping calculator action.

= 4.0.10 (2025-08-12) =
* Fixed: PHP Notice: Function _load_textdomain_just_in_time was called incorrectly when "WooCommerce Stripe Payment Gateway" plugin is active.
* Fixed: Error on the "get product on sale" function.

= 4.0.9 (2025-07-28) =
* Fixed: JavaScript error if "WooCommerce Stripe Payment Gateway" plugin is active but the Stripe payment gateway is disabled.

= 4.0.8 (2025-07-08) =
* Fixed: PHP error on database update to version 2.2.8.
* Fixed: CartFlows support: The product price remains unchanged when switching to a different price zone during checkout.

= 4.0.7 (2025-06-25) =
* Added: Tested up WooCommerce 9.9+.
* Fixed: Polylang and WPML compatibility: Product meta is not synchronized when the translation is added after editing the prices.
* Fixed: The "Country Switcher" widget does not include the shop's base country in some use cases.

= 4.0.6 (2025-05-27) =
* Added: Tested up WooCommerce 9.8+.
* Fixed: The pricing zone internal cache causes issues with discount plugins.
* Fixed: Blocks included are not available on the Customizer > Widgets page

= 4.0.5 (2025-05-13) =
* Fixed: All Products for WooCommerce Subscriptions compatibility: The Ajax geolocation feature replaces variations' prices incorrectly.
* Fixed: WPML compatibility: Product meta synchronization fails when the main site language does not match the product's original language.
* Fixed: PHP error when the plugin functions are called before the plugins_loaded hook ends.

= 4.0.4 (2025-04-08) =
* Fixed: MySql error on the "sync price with children" task if the option "Hide out-of-stock items from the catalog" is enabled.
* Fixed: The Analytics Taxes report is not converted to the default currency.

= 4.0.3 (2025-03-28) =
* Fixed: Compatibility issue with "WooCommerce Stripe Payment Gateway by Automattic" 9.3+.

= 4.0.2 (2025-03-25) =
* Fixed: PHP Warning when third-party dev adds a nonvalid regex using the 'wc_price_based_country_frontend_rest_routes' filter.
* Fixed: I18n: Text domain mismatch on some texts.
* Fixed: Security bugs.

= 4.0.1 (2025-03-17) =
* Fixed: Polylang compatibility: Empty query var index generates a PHP error.
* Fixed: The variation prices transient cache is overridden due to the incorrect hash.

= 4.0.0 (2025-03-11) =
* Added: Tested up WooCommerce 9.7+.
* Added: Core performance improvements:
    - Display prices on the page 10%-15% faster.
	- The number of the post_meta rows needed to store the prices has been reduced.
	- All queries have been optimized.
	- The admin background process runs now on the Action Scheduler.
* Added: Polylang support: Synchronizes data between languages.
* Added WPML support: Data synchronization between languages now runs in the plugin core instead of being delegated to WMPL.
* Added: Country Switcher as WordPress Block.
* Added: Users can add all countries to pricing zones instead of only the ones included in the "Selling location(s)" option.
* Added: Responsive design to settings pages.
* Tweak: Prevent browsers from asking users if they want to resend the form on page refresh after switching countries using the widgets.
* Fixed: The price filter classic widget (by WooCommerce) displays incorrect minimum and maximum values.

[Introducing Price Based on Country for WooCommerce 4.0](https://www.pricebasedcountry.com/2025/03/10/introducing-price-based-on-country-for-woocommerce-4-0/)

[See changelog for all versions](https://plugins.svn.wordpress.org/woocommerce-product-price-based-on-countries/trunk/changelog.txt).

== Upgrade Notice ==

= 4.0 =
<strong>4.0 is a major update</strong>. We recommend that you backup your website before updating it.