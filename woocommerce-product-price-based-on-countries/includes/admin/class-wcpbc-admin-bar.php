<?php
/**
 * WooCommerce Price Based Country Admin Bar.
 *
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Admin_Bar Class.
 */
class WCPBC_Admin_Bar {

	/**
	 * Hook actions.
	 */
	public static function init() {
		add_action( 'admin_bar_menu', [ __CLASS__, 'country_selector' ], 9999 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue the country selector assets.
	 */
	public static function enqueue_assets() {
		if ( is_admin() || ! is_admin_bar_showing() || ! current_user_can( 'manage_woocommerce' ) || get_option( 'wc_price_based_country_test_mode', 'no' ) !== 'yes' ) {
			return;
		}

		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_style( 'select2' );
		wp_enqueue_script( 'wcpbc-admin-bar', WCPBC()->plugin_url() . 'assets/js/admin/admin-bar.js', [ 'jquery', 'selectWoo' ], WCPBC()->version, true );
		wp_enqueue_style( 'wcpbc-admin-bar', WCPBC()->plugin_url() . 'assets/css/admin/admin-bar.css', [ 'select2' ], WCPBC()->version );
	}

	/**
	 * Adds country selector dropdown items to the admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
	 */
	public static function country_selector( $wp_admin_bar ) {
		if ( is_admin() || ! current_user_can( 'manage_woocommerce' ) || get_option( 'wc_price_based_country_test_mode', 'no' ) !== 'yes' ) {
			return;
		}

		$countries       = WC()->countries->get_countries();
		$current_country = wcpbc_get_woocommerce_country();

		$wp_admin_bar->add_node(
			[
				'id'    => 'wcpbc-country-selector',
				'title' => self::country_dropdown( $countries, $current_country ),
				'href'  => false,
			]
		);

		$zone = wcpbc_the_zone();

		$wp_admin_bar->add_node(
			[
				'id'    => 'wcpbc-pricing-zone-display',
				// translators: Pricing zone name.
				'title' => sprintf( __( 'Pricing zone: %s', 'woocommerce-product-price-based-on-countries' ), ( $zone ? $zone->get_name() : '&mdash;' ) ),
				'href'  => false,
			]
		);

		if ( $zone ) {
			$currency        = $zone->get_currency();
			$currency_symbol = wp_specialchars_decode( get_woocommerce_currency_symbol( $currency ), ENT_QUOTES );
			$base_currency   = wcpbc_get_base_currency();
			$exchange_rate   = wcpbc_float_to_string( $zone->get_exchange_rate(), true );

			$wp_admin_bar->add_node(
				[
					'id'     => 'wcpbc-pricing-zone-currency',
					'parent' => 'wcpbc-pricing-zone-display',
					// translators: 1: Currency code, 2: Currency symbol.
					'title'  => sprintf( esc_html__( 'Currency: %1$s (%2$s)', 'woocommerce-product-price-based-on-countries' ), esc_html( $currency ), esc_html( $currency_symbol ) ),
					'href'   => false,
				]
			);

			$wp_admin_bar->add_node(
				[
					'id'     => 'wcpbc-pricing-zone-exchange-rate',
					'parent' => 'wcpbc-pricing-zone-display',
					// translators: 1: Base currency code, 2: Exchange rate, 3: Zone currency code.
					'title'  => sprintf( esc_html__( 'Exchange rate: 1 %1$s = %2$s %3$s', 'woocommerce-product-price-based-on-countries' ), esc_html( $base_currency ), esc_html( $exchange_rate ), esc_html( $currency ) ),
					'href'   => false,
				]
			);
		} else {
			$wp_admin_bar->add_node(
				[
					'id'     => 'wcpbc-pricing-zone-not-found',
					'parent' => 'wcpbc-pricing-zone-display',
					'title'  => esc_html__( 'No pricing zone is defined for the current country', 'woocommerce-product-price-based-on-countries' ),
					'href'   => false,
				]
			);
		}
	}

	/**
	 * Retruns the country selector dropdown
	 *
	 * @param array  $countries Array of countries.
	 * @param string $selected_country Selected country code.
	 *
	 * @return string
	 */
	private static function country_dropdown( $countries, $selected_country ) {
		ob_start();
		?>
		<form method="post" id="wcpbc-admin-bar-country-form" style="display:none;">
			<input type="hidden" name="redirect" value="1">
		</form>
		<select id="wcpbc-admin-bar-country" form="wcpbc-admin-bar-country-form" class="wcpbc-admin-bar-country" name="wcpbc-manual-country" aria-label="<?php esc_attr_e( 'Country', 'woocommerce-product-price-based-on-countries' ); ?>">
			<?php foreach ( $countries as $country_code => $country_name ) : ?>
			<option value="<?php echo esc_attr( $country_code ); ?>" <?php selected( $country_code, $selected_country ); ?>><?php echo esc_html( $country_name ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
		return ob_get_clean();
	}
}
