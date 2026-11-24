<?php
/**
 * Shipping calculator customer country request.
 *
 * @package WCPBC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sets the customer country from the shipping calculator.
 */
class WCPBC_Set_Country_Request_Shipping_Calculator extends WCPBC_Set_Country_Request {

	/**
	 * Maps request keys to properties.
	 *
	 * @var array
	 */
	protected $key_to_prop = [
		'calc_shipping_country'                 => 'country',
		'calc_shipping_postcode'                => 'postcode',
		'calc_shipping'                         => 'calculate_shipping',
		'_wpnonce'                              => 'wpnonce',
		'woocommerce-shipping-calculator-nonce' => 'wpnonce',
	];

	/**
	 * Request method.
	 *
	 * @var string
	 */
	protected $method = 'post';

	/**
	 * Sets the customer country from the shipping calculator data.
	 *
	 * @return bool
	 */
	public function set_country() {
		if ( empty( $this->data['country'] ) || empty( $this->data['calculate_shipping'] ) || ! $this->verify_nonce() ) {
			return false;
		}

		$postcode = ! empty( $this->data['postcode'] ) ? wc_format_postcode( $this->data['postcode'], $this->data['country'] ) : '';

		if ( $postcode && is_callable( [ 'WC_Validation', 'is_postcode' ] ) && ! WC_Validation::is_postcode( $postcode, $this->data['country'] ) ) {
			return true;
		}

		/**
		 * Filters whether the billing country is updated by the shipping calculator.
		 *
		 * @since 4.0.11
		 * @param bool $update_billing_country Whether to update the billing country.
		 */
		if ( ! WC()->customer->get_billing_first_name() || apply_filters( 'wc_price_based_country_calculate_shipping_billing_country_update', true ) ) {
			WC()->customer->set_billing_country( $this->data['country'] );
		}

		WC()->customer->set_shipping_country( $this->data['country'] );

		return true;
	}

	/**
	 * Verifies the shipping calculator nonce.
	 *
	 * @return bool
	 */
	private function verify_nonce() {
		if ( empty( $this->data['wpnonce'] ) ) {
			return false;
		}
		return wp_verify_nonce( $this->data['wpnonce'], 'woocommerce-shipping-calculator' ) || wp_verify_nonce( $this->data['wpnonce'], 'woocommerce-cart' );
	}
}
