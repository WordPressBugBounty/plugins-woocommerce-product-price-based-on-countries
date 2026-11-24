<?php
/**
 * Handle the Store API requests.
 *
 * @package WCPBC
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WCPBC_Store_API' ) ) {
	return;
}

/**
 * WCPBC_Debug_Logger Class
 */
final class WCPBC_Store_API {

	/**
	 * Single instances of the class.
	 *
	 * @var WCPBC_Store_API
	 */
	private static $instance = null;

	/**
	 * Main instance.
	 *
	 * Ensures only one instance of the class is loaded.
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {

		$path = $this->get_path();

		if ( preg_match( '/checkout\/[0-9]+/', $path ) ) {
			return;
		}

		add_action( 'rest_api_init', [ $this, 'rest_api_init' ], 9999 );
		add_action( 'woocommerce_store_api_cart_update_customer_from_request', [ $this, 'store_api_cart_update_customer' ], 0 );
		add_action( 'woocommerce_store_api_checkout_update_customer_from_request', [ $this, 'store_api_cart_update_customer' ], 0 );

		if ( 'products/collection-data' === $this->get_path() && ( isset( $_GET['min_price'] ) || isset( $_GET['max_price'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			add_filter( 'query', [ $this, 'price_filter_sql' ] );
		}
	}

	/**
	 * Default REST Init.
	 *
	 * Load the customer to get customer information.
	 */
	public function rest_api_init() {
		if ( ! ( did_action( 'before_woocommerce_init' ) && ! doing_action( 'before_woocommerce_init' ) && function_exists( 'WC' ) ) ) {
			return;
		}

		$session_set  = false;
		$customer_set = false;

		if ( is_null( WC()->session ) ) {
			$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			if ( class_exists( $session_class ) ) {
				$session_handler = new $session_class();
				if ( is_a( $session_handler, 'WC_Session_Handler' ) && is_callable( [ $session_handler, 'init_session_cookie' ] ) ) {
					WC()->session = $session_handler;
					WC()->session->init_session_cookie();
					$session_set = true;
				} elseif ( is_a( $session_handler, 'Automattic\WooCommerce\StoreApi\SessionHandler' ) && is_callable( [ $session_handler, 'init' ] ) ) {
					WC()->session = $session_handler;
					WC()->session->init();
					remove_action( 'shutdown', [ WC()->session, 'save_data' ], 20 );
					$session_set = true;
				}

				unset( $session_handler );
			}
		}

		if ( is_null( WC()->customer ) && ! is_null( WC()->session ) ) {

			if ( ! function_exists( 'wc_get_chosen_shipping_method_ids' ) ) {
				// Frontend includes.
				WC()->frontend_includes();
			}

			WC()->customer = new WC_Customer( get_current_user_id(), true );
			$customer_set  = true;
		}

		/**
		 * Fires before frontend pricing init.
		 *
		 * @since 1.7.21
		 */
		do_action( 'wc_price_based_country_before_frontend_init' );

		wcpbc()->frontend_pricing_init();

		/**
		 * Fires after frontend pricing init.
		 *
		 * @since 1.8.25
		 */
		do_action( 'wc_price_based_country_frontend_init' );

		if ( $session_set ) {
			WC()->session = null;
		}

		if ( $customer_set ) {
			WC()->customer = null;
		}
	}

	/**
	 * Modify the min/max price SQL query.
	 *
	 * @param string $query The SQL Query.
	 * @see Automattic\WooCommerce\StoreApi\Utilities\get_filtered_price
	 */
	public function price_filter_sql( $query ) {
		global $wpdb;

		if ( wcpbc_the_zone() && 'select min( min_price ) as min_price, max( max_price ) as max_price' === strtolower( substr( trim( $query ), 0, 67 ) ) ) {
			$query = WCPBC_Frontend_Pricing::price_filter_sql( $query );
		}

		return $query;
	}

	/**
	 * Init the frontend pricing after store API updated customer address.
	 */
	public function store_api_cart_update_customer() {
		$zone            = wcpbc_get_zone_by_country();
		$zone_id         = $zone ? $zone->get_id() : null;
		$current_zone_id = wcpbc_the_zone() ? wcpbc_the_zone()->get_id() : null;

		if ( $zone_id !== $current_zone_id ) {

			WCPBC_Frontend_Pricing::unset();
			wcpbc()->frontend_pricing_init();

			if ( did_action( 'woocommerce_cart_loaded_from_session' ) && WC()->cart && class_exists( 'WC_Cart_Session' ) ) {
				// Need to reload the cart to update cart items and data.
				$cart_session = new WC_Cart_Session( WC()->cart );
				$cart_session->get_cart_from_session();
			}
		}
	}

	/**
	 * Returns the store API path.
	 *
	 * @return string
	 */
	protected function get_path() {
		$path = false;
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return $path;
		}

		$uri_path = trailingslashit( wp_parse_url( wc_clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) );
		$pattern  = '/' . str_replace( '/', '\/', trailingslashit( rest_get_url_prefix() ) . 'wc/store/v[0-9]+/(.+)/' ) . '/';
		$matches  = [];

		if ( 1 === preg_match( $pattern, $uri_path, $matches ) && isset( $matches[1] ) ) {
			$path = $matches[1];
		}

		if ( 'batch' === $path ) {
			$path = $this->get_path_from_batch_request();
		}

		return $path;
	}

	/**
	 * Returns the store API path.
	 *
	 * @return string
	 */
	protected function get_path_from_batch_request() {
		$path = false;
		if ( ! is_callable( [ 'WP_REST_Server', 'get_raw_data' ] ) ) {
			return $path;
		}

		$raw_data = json_decode( WP_REST_Server::get_raw_data() );

		if ( isset( $raw_data->requests ) && is_array( $raw_data->requests ) && isset( $raw_data->requests[0]->path ) ) {
			$matches = [];
			$pattern = '/\/wc\/store\/v[0-9]+\/(.+)/';

			if ( 1 === preg_match( $pattern, $raw_data->requests[0]->path, $matches ) && isset( $matches[1] ) ) {
				$path = $matches[1];
			}
		}

		return $path;
	}

	/**
	 * Prevent object cloning
	 */
	protected function __clone() {}

	/**
	 * Prevent unserializing.
	 */
	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'woocommerce-product-price-based-on-countries' ), '4.6' );
		die();
	}
}
