<?php
/**
 * WC Payments Multicurrency feature disabled notice class.
 *
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Admin_Notice_WC_Payments_Multicurrency_Disabled Class.
 */
class WCPBC_Admin_Notice_WC_Payments_Multicurrency_Disabled extends WCPBC_Admin_Notice {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->set_props(
			[
				'id'             => 'wc_payments_multicurrency_disabled',
				'type'           => 'warning',
				// translators: HTML Tags.
				'content'        => wpautop( sprintf( __( '%1$sThe multi-currency option of WooCommerce Payments has been disabled%2$s to prevent issues with the pricing zones. You do not need to enable this option to receive payments in multiple currencies.', 'woocommerce-product-price-based-on-countries' ), '<strong>', '</strong>' ) ),
				'dismissible'    => true,
				'default_status' => self::STATUS_DISMISSED,
			]
		);
	}

	/**
	 * Output dismiss link.
	 */
	protected function print_dismiss_link() {
		// Nothing to do.
	}

	/**
	 * Print once.
	 */
	public function print() {
		parent::print();
		$this->set_status( self::STATUS_DISMISSED );
	}
}
