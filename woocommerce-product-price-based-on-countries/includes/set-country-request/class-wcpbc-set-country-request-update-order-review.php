<?php
/**
 * Checkout customer country request.
 *
 * @package WCPBC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sets the customer country during checkout updates.
 */
class WCPBC_Set_Country_Request_Update_Order_Review extends WCPBC_Set_Country_Request {

	/**
	 * Maps request keys to properties.
	 *
	 * @var array
	 */
	protected $key_to_prop = [
		'country'   => 'country',
		's_country' => 's_country',
	];

	/**
	 * Populates the data.
	 *
	 * @return void
	 */
	protected function populate_data() {
		if ( ! ( defined( 'WC_DOING_AJAX' ) && WC_DOING_AJAX && isset( $_GET['wc-ajax'] ) && 'update_order_review' === $_GET['wc-ajax'] && check_ajax_referer( 'update-order-review', 'security', false ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$postdata  = $this->get_posted_data();
		$countries = WC()->countries->get_countries();
		foreach ( [ 'country', 's_country' ] as $key ) {
			if ( isset( $postdata[ $key ] ) && ! isset( $countries[ wc_clean( wp_unslash( $postdata[ $key ] ) ) ] ) ) {
				// Avoid WooCommerce updating the country with an invalid value such as "undefined".
				$_POST[ $key ] = null; // phpcs:ignore WordPress.Security.NonceVerification
			}
		}
		parent::populate_data();
	}

	/**
	 * Sets the customer country from checkout data.
	 *
	 * @return bool
	 */
	public function set_country() {

		if ( empty( $this->data['country'] ) ) {
			return false;
		}

		WC()->customer->set_billing_country( $this->data['country'] );
		WC()->customer->set_shipping_country( $this->data['country'] );

		if ( ! wc_ship_to_billing_address_only() && $this->data['s_country'] ) {
			WC()->customer->set_shipping_country( $this->data['s_country'] );
		}

		return true;
	}
}
