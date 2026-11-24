<?php
/**
 * WooCommerce Price Based Country Front-End
 *
 * @version 1.8.10
 * @package WCPBC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WCPBC_Frontend Class
 */
class WCPBC_Frontend {

	/**
	 * Hook actions and filters
	 */
	public static function init() {
		add_filter( 'woocommerce_customer_default_location', [ __CLASS__, 'allowed_countries_filter' ] );
		add_filter( 'woocommerce_customer_default_location_array', [ __CLASS__, 'remove_allowed_countries_filter' ] );
		add_filter( 'woocommerce_geolocate_ip', [ __CLASS__, 'fix_real_ip' ], 5 );
		add_filter( 'woocommerce_update_order_review_fragments', [ __CLASS__, 'update_order_review_fragments' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'load_scripts' ], 1 );
		add_action( 'wp_footer', [ __CLASS__, 'print_add_to_cart_with_options_module' ], 1 );
		add_action( 'wc_price_based_country_before_frontend_init', [ __CLASS__, 'set_customer_country' ], 20 );
		add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'update_order_meta' ] );
		add_action( 'woocommerce_store_api_checkout_update_order_meta', [ __CLASS__, 'update_order_meta' ] );
		add_action( 'woocommerce_after_checkout_validation', [ __CLASS__, 'validate_checkout' ], 10, 2 );
		add_action( 'woocommerce_order_refunded', [ __CLASS__, 'order_refunded' ], 10, 2 );
		if ( 'yes' === get_option( 'wc_price_based_country_product_structured_data', 'no' ) ) {
			add_action( 'wp', [ __CLASS__, 'product_structured_data' ] );
		}
		add_action( 'wcpbc_manual_country_selector', [ __CLASS__, 'output_country_selector' ] );
		add_shortcode( 'wcpbc_country_selector', [ __CLASS__, 'shortcode_country_selector' ] );
	}

	/**
	 * Add the allowed countries filter.
	 *
	 * @param mixed $value Value.
	 */
	public static function allowed_countries_filter( $value ) {
		add_filter( 'pre_option_woocommerce_allowed_countries', [ __CLASS__, 'allowed_countries' ] );
		return $value;
	}

	/**
	 * Removes the allowed countries filter.
	 *
	 * @param array $location Country and state default location.
	 * @return array
	 */
	public static function remove_allowed_countries_filter( $location ) {
		remove_filter( 'pre_option_woocommerce_allowed_countries', [ __CLASS__, 'allowed_countries' ] );
		return $location;
	}

	/**
	 * Returns 'all' for allowed countries countries option in the function wc_get_customer_default_location.
	 *
	 * @param string $value Option value.
	 */
	public static function allowed_countries( $value ) {
		return 'all';
	}

	/**
	 * Replaces the HTTP_X_REAL_IP server variable for the geolocation function works appropriately on some hosting.
	 * Run on woocommerce_geolocate_ip filter (before WooCommerce geolocates the IP).
	 *
	 * @since 3.1.0
	 * @param string $value Value to return by the filter.
	 */
	public static function fix_real_ip( $value ) {
		$sucuri_client_ip = empty( $_SERVER['HTTP_X_SUCURI_CLIENTIP'] ) ? false : filter_var( wc_clean( wp_unslash( $_SERVER['HTTP_X_SUCURI_CLIENTIP'] ) ), FILTER_VALIDATE_IP );
		$remote_addr      = empty( $_SERVER['REMOTE_ADDR'] ) ? false : filter_var( wc_clean( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ), FILTER_VALIDATE_IP );

		if ( $sucuri_client_ip ) {
			$_SERVER['HTTP_X_REAL_IP'] = $sucuri_client_ip;
		} elseif ( defined( 'WCPBC_USE_REMOTE_ADDR' ) && WCPBC_USE_REMOTE_ADDR && $remote_addr ) {
			$_SERVER['HTTP_X_REAL_IP'] = $remote_addr;
		}

		return $value;
	}

	/**
	 * Add the mini cart to the update order review fragments.
	 *
	 * @param array $fragments Order review fragments.
	 */
	public static function update_order_review_fragments( $fragments ) {
		$cart_hash = isset( WC()->cart ) && is_callable( [ WC()->cart, 'get_cart_hash' ] ) ? WC()->cart->get_cart_hash() : '-1';

		if ( ! empty( $_COOKIE['woocommerce_cart_hash'] ) && wc_clean( wp_unslash( $_COOKIE['woocommerce_cart_hash'] ) ) !== $cart_hash ) {
			ob_start();

			woocommerce_mini_cart();

			$mini_cart = ob_get_clean();

			$fragments = array_merge(
				$fragments,
				apply_filters(
					'woocommerce_add_to_cart_fragments',
					[
						'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
					]
				)
			);
		}

		return $fragments;
	}

	/**
	 * Register/queue frontend scripts.
	 */
	public static function load_scripts() {

		if ( ! did_action( 'before_woocommerce_init' ) ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Scripts.
		$deps = [];
		if ( wcpbc_is_pro() ) {
			$deps[] = 'wc-price-based-country-pro-frontend';
		}

		wp_register_script( 'wc-price-based-country-ajax-geo', WCPBC()->plugin_url() . 'assets/js/ajax-geolocation' . $suffix . '.js', $deps, WCPBC()->version, true );
		wp_localize_script(
			'wc-price-based-country-ajax-geo',
			'wc_price_based_country_ajax_geo_params',
			[
				'wc_ajax_url' => WC_AJAX::get_endpoint( '%%endpoint%%' ),
			]
		);

		wp_register_style( 'wc-price-based-country-frontend', WCPBC()->plugin_url() . 'assets/css/frontend' . $suffix . '.css', [], WCPBC()->version );

		if ( WCPBC_Ajax_Geolocation::is_enabled() && ! ( is_cart() || is_account_page() || is_checkout() || is_customize_preview() || apply_filters( 'wc_price_based_country_dequeue_script', false ) ) ) {

			do_action( 'wc_price_based_country_before_enqueue_script' );

			wp_enqueue_script( 'wc-price-based-country-ajax-geo' );
			wp_enqueue_style( 'wc-price-based-country-frontend' );

		}
	}

	/**
	 * Print the Add to Cart + Options interactivity module.
	 *
	 * Script modules registered while rendering a block are too late to be
	 * printed by WordPress in the document head. The block itself has already
	 * registered the Interactivity API import map, so this footer module can
	 * resolve its static imports.
	 *
	 * @return void
	 */
	public static function print_add_to_cart_with_options_module() {
		if ( ! ( function_exists( 'did_filter' ) && function_exists( 'wp_print_script_tag' ) && did_filter( 'render_block_woocommerce/add-to-cart-with-options-variation-selector' ) && wp_script_is( 'wc-price-based-country-ajax-geo', 'enqueued' ) ) ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_print_script_tag(
			[
				'id'   => 'wc-price-based-country-ajax-geo-add-to-cart-with-options-js-module',
				'src'  => add_query_arg( 'ver', WCPBC()->version, WCPBC()->plugin_url() . 'assets/js/ajax-geolocation-add-to-cart-with-options' . $suffix . '.js' ),
				'type' => 'module',
			]
		);
	}

	/**
	 * Set the customer country before the frontend pricing is loaded
	 *
	 * @since 1.7.8
	 */
	public static function set_customer_country() {
		static $done = false;
		if ( $done ) {
			// Do only once.
			return;
		}

		$done = true;

		$requests = [
			new WCPBC_Set_Country_Request_Update_Order_Review(),
			new WCPBC_Set_Country_Request_Pay_For_Order(),
			new WCPBC_Set_Country_Request_Shipping_Calculator(),
			new WCPBC_Set_Country_Request_Manual(),
		];

		foreach ( $requests as $request ) {
			if ( $request->set_country() ) {
				break;
			}
		}
	}

	/**
	 * Add zone data to order meta
	 *
	 * @since 2.1.0
	 * @version 3.2.0 Compatible with HPOS.
	 * @param WC_Order $order Order instance.
	 */
	public static function update_order_meta( $order ) {
		if ( ! ( wcpbc_the_zone() && is_a( $order, 'WC_Order' ) ) ) {
			return;
		}

		$base_exchange_rate = wcpbc_the_zone()->get_base_currency_amount( 1 );
		$pricing_zone_data  = wcpbc_the_zone()->get_data();

		// Update metadata.
		$order->update_meta_data( '_wcpbc_base_exchange_rate', $base_exchange_rate );
		$order->update_meta_data( '_wcpbc_pricing_zone', $pricing_zone_data );
	}

	/**
	 * Copy the order metadata to the refund order.
	 *
	 * @since 2.2.2
	 * @param int $order_id Order ID.
	 * @param int $refund_id Refund order ID.
	 */
	public static function order_refunded( $order_id, $refund_id ) {
		$order  = wc_get_order( $order_id );
		$refund = wc_get_order( $refund_id );

		if ( ! ( $order && $refund ) ) {
			return;
		}

		$base_exchange_rate = $order->get_meta( '_wcpbc_base_exchange_rate' );
		$pricing_zone_data  = $order->get_meta( '_wcpbc_pricing_zone' );

		$refund->update_meta_data( '_wcpbc_base_exchange_rate', $base_exchange_rate );
		$refund->update_meta_data( '_wcpbc_pricing_zone', $pricing_zone_data );
		$refund->save();
	}

	/**
	 * Validates that the pricing zone against the order.
	 *
	 * @since  4.2.0
	 * @param  array    $data   An array of posted data.
	 * @param  WP_Error $errors Validation errors.
	 */
	public static function validate_checkout( $data, $errors ) {

		if ( ! ( empty( $errors->errors ) && apply_filters( 'wc_price_based_country_validate_checkout', true, $data ) ) ) {
			return;
		}

		$zone_id         = wcpbc_the_zone() ? wcpbc_the_zone()->get_id() : false;
		$country_zone    = wcpbc_get_zone_by_country();
		$country_zone_id = $country_zone ? $country_zone->get_id() : false;

		if ( $zone_id !== $country_zone_id ) {

			WC()->session->set( 'refresh_totals', true );

			$debug_data = [
				'loaded'   => $zone_id,
				'checkout' => $country_zone_id,
			];

			$errors->add( 'pricing_zone', __( 'We were unable to process your order, please try again.', 'woocommerce-product-price-based-on-countries' ) );

			if ( current_user_can( 'manage_woocommerce' ) ) {
				$errors->add(
					'pricing_zone_debug',
					__( 'The loaded pricing zone does not match the pricing zone for the checkout billing/shipping country. This message is visible only to admins.', 'woocommerce-product-price-based-on-countries' )
				);
				$errors->add( 'pricing_zone_debug_data', wp_json_encode( $debug_data ) );
			}

			if ( function_exists( 'wc_log_order_step' ) ) {
				wc_log_order_step(
					'[Shortcode] Pricing zone validation error. Refresh total and try again.',
					[
						'price_based_on_country' => $debug_data,
					]
				);
			}
		}
	}

	/**
	 * Display structure data for rich snippets in the default price and currency.
	 * Avoid SEO issues related to bot crawling.
	 */
	public static function product_structured_data() {
		if ( wcpbc_is_manual_country_endpoint() ) {
			return;
		}

		if ( has_action( 'woocommerce_single_product_summary', [ WC()->structured_data, 'generate_product_data' ] ) ) {
			remove_action( 'woocommerce_single_product_summary', [ WC()->structured_data, 'generate_product_data' ], 60 );
			add_action(
				'woocommerce_single_product_summary',
				function () {
					WCPBC_Frontend_Pricing::unset();
					WC()->structured_data->generate_product_data();
					WCPBC_Frontend_Pricing::init();
				},
				60
			);
		}

		add_action( 'rank_math/head', [ 'WCPBC_Frontend_Pricing', 'unset' ], 0 );
		add_action( 'rank_math/head', [ 'WCPBC_Frontend_Pricing', 'init' ], 9999 );
	}

	/**
	 * Output manual country select form
	 *
	 * @param string $other_countries_text Other countries text.
	 */
	public static function output_country_selector( $other_countries_text = '' ) {
		$atts = [];

		if ( ! empty( $other_countries_text ) ) {
			$atts = [
				'other_countries_text' => $other_countries_text,
			];
		}

		echo self::shortcode_country_selector( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Return the country select form
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode_country_selector( $atts ) {

		$atts = shortcode_atts(
			[
				'remove_other_countries' => false,
				'flag'                   => 0,
				'other_countries_text'   => apply_filters( 'wcpbc_other_countries_text', __( 'Other countries', 'woocommerce-product-price-based-on-countries' ) ),
				'title'                  => '',
			],
			$atts,
			'wcpbc_country_selector'
		);

		ob_start();

		the_widget(
			'WCPBC_Widget_Country_Selector',
			$atts,
			[
				'before_widget' => '',
				'after_widget'  => '',
			]
		);

		return ob_get_clean();
	}
}
