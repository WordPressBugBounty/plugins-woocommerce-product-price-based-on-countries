<?php
/**
 * Unconnected Admin notice class.
 *
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Admin_Notice Class.
 */
class WCPBC_Admin_Notice_Unconnected extends WCPBC_Admin_Notice {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->set_props(
			[
				'type'       => 'error',
				// translators: HTML Tags.
				'content'    => wpautop( sprintf( __( '%1$sWarning!%2$s You didn\'t set your %1$sWooCommerce Price Based Country Pro%2$s license key yet, which means you\'re missing out on updates and support! Enter your %3$slicense key%4$s.', 'woocommerce-product-price-based-on-countries' ), '<strong>', '</strong>', '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=price-based-country&section=license' ) ) . '">', '</a>' ) ),
				'attributes' => [
					'style' => 'display:block !important',
				],
			]
		);
	}

	/**
	 * Return whether the notice should be displayed.
	 *
	 * @return bool
	 */
	protected function should_display() {
		if ( ! is_callable( [ 'WCPBC_License_Settings', 'is_license_connected' ] ) ) {
			return false;
		}

		if ( WCPBC_License_Settings::is_license_connected()
			|| ( isset( $_GET['tab'] ) && 'price-based-country' === $_GET['tab'] && isset( $_GET['section'] ) && 'license' === $_GET['section'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}

		return parent::should_display();
	}
}
