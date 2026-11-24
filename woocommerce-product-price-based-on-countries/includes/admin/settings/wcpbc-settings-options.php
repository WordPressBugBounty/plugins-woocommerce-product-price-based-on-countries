<?php
/**
 * Plugin options.
 *
 * Returns an array of options.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

return [

	/**
	 * General
	 */
	[
		'id'        => 'general',
		'title'     => __( 'General', 'woocommerce-product-price-based-on-countries' ),
		'paragrahs' => [
			__( 'Configure the general plugin settings.', 'woocommerce-product-price-based-on-countries' ),
			// translators: HTML tags.
			sprintf( __( '%1$sView plugin docs%2$s', 'woocommerce-product-price-based-on-countries' ), '<a target="_blank" rel="external noreferrer noopener" class="wcpbc-external-link" href="' . esc_url( wcpbc_home_url( 'settings', 'docs/getting-started/settings-options' ) ) . '">', '</a>' ),
			// translators: HTML tags.
			sprintf( __( '%1$sGet support%2$s', 'woocommerce-product-price-based-on-countries' ), '<a target="_blank" rel="external noreferrer noopener" class="wcpbc-external-link" href="' . esc_url( wcpbc_home_url( 'settings', 'support' ) ) . '">', '</a>' ),
		],
		'fields'    => array_merge(
			[

				[
					'id'      => 'wc_price_based_country_based_on',
					'label'   => __( 'Price based on', 'woocommerce-product-price-based-on-countries' ),
					'desc'    => __( "This controls which address is used to determine the customer's pricing zone. Geolocation will be used when the customer's address is unknown.", 'woocommerce-product-price-based-on-countries' ),
					'default' => 'billing',
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'options' => [
						'billing'  => __( 'Customer billing country', 'woocommerce-product-price-based-on-countries' ),
						'shipping' => __( 'Customer shipping country', 'woocommerce-product-price-based-on-countries' ),
					],
				],

				[
					'id'      => 'wc_price_based_country_exchange_rate_api',
					'label'   => __( 'Exchange rates source', 'woocommerce-product-price-based-on-countries' ),
					'desc'    => __( 'Select the service that will be used to update the exchange rates.', 'woocommerce-product-price-based-on-countries' ),
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'options' => wcpbc_is_pro() && is_callable( [ 'WCPBC_Update_Exchange_Rates', 'get_providers' ] ) ? wc_list_pluck( WCPBC_Update_Exchange_Rates::get_providers(), 'get_name' ) : [
						'FloatRates',
						'Open Exchange Rates',
						'X-Rates',
					],
					'is_pro'  => true,
				],
			],
			( wcpbc_is_pro() && is_callable( [ 'WCPBC_Update_Exchange_Rates', 'get_providers_fields' ] ) ? WCPBC_Update_Exchange_Rates::get_providers_fields() : [] )
		),
	],

	/**
	 * Cache support
	 */
	[
		'id'        => 'cache',
		'title'     => __( 'Cache support', 'woocommerce-product-price-based-on-countries' ),
		'paragrahs' => [
			__( 'Settings related to geolocation and cache.', 'woocommerce-product-price-based-on-countries' ),
			// translators: HTML tags.
			sprintf( __( '%1$sGeolocation with cache support%2$s', 'woocommerce-product-price-based-on-countries' ), '<a target="_blank" rel="external noreferrer noopener" class="wcpbc-external-link" href="' . esc_url( wcpbc_home_url( 'settings', 'docs/getting-started/geolocation-cache-support' ) ) . '">', '</a>' ),
		],
		'fields'    => [

			[
				'id'      => 'wc_price_based_country_caching_support',
				'default' => 'no',
				'type'    => 'true-false',
				'label'   => __( 'Load product prices in the background', 'woocommerce-product-price-based-on-countries' ),
				// translators: HTML tags.
				'desc'    => __( "Enable this option to refresh the prices via AJAX. It'll run an AJAX request per page.", 'woocommerce-product-price-based-on-countries' ),
			],
		],
	],

	/**
	 * Test mode
	 */
	[
		'id'        => 'test',
		'title'     => __( 'Testing', 'woocommerce-product-price-based-on-countries' ),
		'paragrahs' => [
			// translators: HTML tags.
			sprintf( __( '%1$sGuide: Testing pricing zones and geolocation%2$s', 'woocommerce-product-price-based-on-countries' ), '<a target="_blank" rel="external noreferrer noopener" class="wcpbc-external-link" href="' . esc_url( wcpbc_home_url( wcpbc_home_url( 'settings', 'docs/getting-started/testing' ) ) ) . '">', '</a>' ),
		],
		'fields'    => [

			[
				'label'   => __( 'Enable country switcher in admin bar', 'woocommerce-product-price-based-on-countries' ),
				// translators: HTML tags.
				'desc'    => __( 'Adds a dropdown to the WordPress bar to quickly switch between different countries and verify pricing zones.', 'woocommerce-product-price-based-on-countries' ),
				'id'      => 'wc_price_based_country_test_mode',
				'default' => 'no',
				'type'    => 'true-false',
			],
		],
	],

	/**
	 * Show advanced
	 */
	[
		'id'     => 'show-advanced-options',
		'fields' => [
			/**
			 * Collapse next options.
			 */
			[
				'id'                => 'show_advanced_options_btn',
				'label'             => __( 'Advanced', 'woocommerce-product-price-based-on-countries' ),
				'custom_attributes' => [
					'data-toggle'   => 'collapse',
					'data-target'   => '.wcpbc-settings-section-container.-advanced',
					'role'          => 'button',
					'aria-expanded' => 'false',
				],
				'type'              => 'link',
				'href'              => '#advanced-section',
			],
		],
	],

	/**
	 * Advanced
	 */
	[
		'id'     => 'advanced',
		'fields' => array_merge(
			[

				/**
				 * Product structured data.
				 */
				[
					'id'      => 'wc_price_based_country_product_structured_data',
					'label'   => __( 'Use default prices in product schema markup', 'woocommerce-product-price-based-on-countries' ),
					'desc'    => __( 'Generate schema markup using the default price and currency to prevent search engines from reading the geolocation prices.', 'woocommerce-product-price-based-on-countries' ),
					'default' => 'no',
					'type'    => 'true-false',
				],

				/**
				 * Shipping cost by exchange rate.
				 */
				[
					'id'      => 'wc_price_based_country_shipping_exchange_rate',
					'label'   => __( 'Apply the exchange rate to the shipping cost', 'woocommerce-product-price-based-on-countries' ),
					'desc'    => __( 'Enable this option if you want to use the exchange rate of the pricing zones to convert shipping costs.', 'woocommerce-product-price-based-on-countries' ),
					'default' => 'yes',
					'type'    => 'true-false',
				],

				/**
				 * Run setup wizard.
				 */
				[
					'id'                => 'run_setup_wizard',
					'label'             => __( 'Run the geolocation setup wizard', 'woocommerce-product-price-based-on-countries' ),
					'custom_attributes' => [
						'role' => 'button',
					],
					'type'              => 'link',
					'class'             => 'button',
					'href'              => admin_url( 'admin.php?page=wcpbc-setup' ),
				],

			],
			( class_exists( 'WCPBC_Google_Listing_And_Ads' ) ? WCPBC_Google_Listing_And_Ads::get_setting_options() : [] )
		),
	],

	/**
	 * Submit section.
	 */
	[
		'id'     => 'submit',
		'fields' => [

			/**
			 * Save changes
			 */
			[
				'id'    => 'save_changes',
				'label' => __( 'Save changes', 'woocommerce-product-price-based-on-countries' ),
				'type'  => 'submit',
			],
		],
	],

];
