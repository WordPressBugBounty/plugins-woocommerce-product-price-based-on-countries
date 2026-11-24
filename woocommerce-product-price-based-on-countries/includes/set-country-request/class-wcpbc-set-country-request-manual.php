<?php
/**
 * Manual customer country request.
 *
 * @package WCPBC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sets the customer country from the manual country request parameter.
 */
class WCPBC_Set_Country_Request_Manual extends WCPBC_Set_Country_Request {

	/**
	 * Maps request keys to properties.
	 *
	 * @var array
	 */
	protected $key_to_prop = [
		'wcpbc-manual-country' => 'country',
		'redirect'             => 'redirect',
	];

	/**
	 * Populates the data.
	 *
	 * @return void
	 */
	protected function populate_data() {
		parent::populate_data();

		if ( ! $this->data['country'] ) {
			return;
		}

		if ( ! did_action( 'before_woocommerce_init' ) || doing_action( 'before_woocommerce_init' ) ) {
			/* translators: 1: frontend_pricing_init 2: woocommerce_init */
			wc_doing_it_wrong( __FUNCTION__, sprintf( __( '%1$s should not be called before the %2$s action.', 'woocommerce-product-price-based-on-countries' ), 'WCPBC_Set_Country_Request_Manual::populate_data', 'before_woocommerce_init' ), '4.4.0' );
			return;
		}

		$countries = WC()->countries->get_countries();

		if ( ! isset( $countries[ $this->get_country() ] ) ) {
			$this->data['country'] = false;
		}
	}

	/**
	 * Returns the country.
	 *
	 * @return string|bool
	 */
	public function get_country() {
		return empty( $this->data['country'] ) ? false : strtoupper( $this->data['country'] );
	}

	/**
	 * Sets the requested customer country.
	 *
	 * @return bool
	 */
	public function set_country() {
		if ( ! $this->get_country() ) {
			return false;
		}

		wc_nocache_headers();

		$this->update_customer_country();

		add_action( 'wp', [ $this, 'session_save' ], 100 );

		return true;
	}

	/**
	 * Updates the WooCommerce customer country.
	 */
	protected function update_customer_country() {
		wcpbc_set_woocommerce_country( $this->get_country() );
	}

	/**
	 * Saves the customer session and refreshes cart totals.
	 */
	public function session_save() {

		if ( headers_sent() || ! $this->get_country() ) {
			return;
		}

		if ( isset( WC()->session ) && is_callable( [ WC()->session, 'has_session' ] ) && ! WC()->session->has_session() ) {
			do_action( 'woocommerce_set_cart_cookies', true ); //phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

			WC()->customer->save();
		}

		if ( isset( WC()->cart ) && is_a( WC()->cart, 'WC_Cart' ) ) {
			if ( WC()->cart->is_empty() ) {
				// The cart is empty. Update the cart's hash to trigger get_refreshed_fragments.
				wc_setcookie( 'woocommerce_cart_hash', WC()->cart->get_cart_hash() );
			} else {
				WC()->cart->calculate_totals();
			}
		}

		$this->redirect();
	}

	/**
	 * Redirect.
	 *
	 * @return void
	 */
	protected function redirect() {
		if ( '1' !== $this->data['redirect'] ) {
			return;
		}

		global $wp;
		$args        = wc_clean( array_intersect_key( $_GET, array_flip( [ 'p', 'page_id', 'product', 'post_type' ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$current_url = add_query_arg( $args, trailingslashit( home_url( $wp->request ) ) );
		wp_safe_redirect( $current_url );
		exit;
	}
}
