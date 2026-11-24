<?php
/**
 * Multicurrency plugin detected notice class.
 *
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Admin_Notice Class.
 */
class WCPBC_Admin_Notice_Multicurrency_Incompatible extends WCPBC_Admin_Notice {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->set_default_status( self::STATUS_DISMISSED );

		$warning = $this->get_warning_message();

		if ( $warning ) {
			$this->set_props(
				[
					'type'           => 'error',
					'content'        => wpautop( $warning ),
					'default_status' => self::STATUS_VISIBLE,
				]
			);
		}
	}

	/**
	 * Check for multi-currency plugins and return the warning.
	 *
	 * @return string
	 */
	protected function get_warning_message() {
		// translators: 1,2: bold HTML tag, 3: Plugin name.
		$format  = __( 'It looks like another multicurrency plugin is active on your site. %1$sPrice Based on Country will not work properly%2$s. We recomended you disable %3$s.', 'woocommerce-product-price-based-on-countries' );
		$plugins = [
			'Alg_WC_Currency_Switcher' => 'Currency Switcher for WooCommerce by WP Wham',
			'WOOCS_STARTER'            => 'FOX - Currency Switcher Professional for WooCommerce',
			'WOOCS'                    => 'FOX - Currency Switcher Professional for WooCommerce',
			'WOOMULTI_CURRENCY_F'      => 'CURCY - Multi Currency for WooCommerce',
			'FMA_Multi_Currency'       => 'FMA Woo Multi Currency by FME Addons',
			'BeRocket_CE'              => 'Currency Exchange for WooCommerce by BeRocket',
		];

		foreach ( $plugins as $classname => $name ) {
			if ( ! class_exists( $classname ) ) {
				continue;
			}

			return sprintf(
				$format,
				'<strong style="color:#a00;">',
				'</strong>',
				"<strong>{$name}</strong>"
			);
		}

		global $woocommerce_wpml;
		if ( ! empty( $woocommerce_wpml->settings['enable_multi_currency'] ) ) {
			return sprintf(
				$format,
				'<strong style="color:#a00;">',
				'</strong>',
				sprintf(
					'<a href="%s">%s</a>',
					admin_url( 'admin.php?page=wpml-wcml&tab=multi-currency' ),
					__( 'WooCommerce Multilingual multiple currencies option', 'woocommerce-product-price-based-on-countries' )
				),
			);
		}

		return '';
	}
}
