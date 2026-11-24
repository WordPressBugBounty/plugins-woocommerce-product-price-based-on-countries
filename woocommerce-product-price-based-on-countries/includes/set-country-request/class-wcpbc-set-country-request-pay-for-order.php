<?php
/**
 * Pay for order customer country request.
 *
 * @package WCPBC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sets the customer country on the pay for order page.
 */
class WCPBC_Set_Country_Request_Pay_For_Order extends WCPBC_Set_Country_Request {

	/**
	 * Request var
	 *
	 * @var string
	 */
	protected $method = 'get';

	/**
	 * Maps request keys to properties.
	 *
	 * @var array
	 */
	protected $key_to_prop = [
		'pay_for_order' => 'pay_for_order',
		'key'           => 'order_key',
	];

	/**
	 * Sets the customer country from the order countries.
	 *
	 * @return bool
	 */
	public function set_country() {
		if ( empty( $this->data['pay_for_order'] ) || empty( $this->data['order_key'] ) ) {
			return false;
		}

		$order_id = wc_get_order_id_by_order_key( $this->data['order_key'] );

		if ( ! $order_id ) {
			return true;
		}

		$billing_country  = wcpbc_get_order_country( $order_id, 'billing' );
		$shipping_country = wcpbc_get_order_country( $order_id, 'shipping' );

		if ( $billing_country ) {
			WC()->customer->set_billing_country( $billing_country );
			WC()->customer->set_shipping_country( $billing_country );
		}

		if ( $shipping_country ) {
			WC()->customer->set_shipping_country( $shipping_country );
		}

		return true;
	}
}
