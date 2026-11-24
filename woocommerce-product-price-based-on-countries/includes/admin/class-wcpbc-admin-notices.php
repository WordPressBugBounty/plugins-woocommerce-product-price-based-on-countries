<?php
/**
 * Display notices in admin
 *
 * @since   1.7.0
 * @version 1.8.10
 * @package WCPBC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCPBC_Admin_Notices', false ) ) :

	/**
	 * WCPBC_Admin_Notices Class
	 */
	class WCPBC_Admin_Notices {

		/**
		 * Init notices
		 */
		public static function init() {
			add_action( 'admin_head', [ __CLASS__, 'enqueue_notices' ] );
			add_action( 'wp_ajax_wc_price_based_country_dismiss_notice', [ __CLASS__, 'dismiss_notice' ] );

			// Product type not supported.
			add_action( 'woocommerce_product_options_general_product_data', [ __CLASS__, 'display_product_type_not_supported' ], 9 );
		}

		/**
		 * Return all notices array
		 *
		 * @return WCPBC_Admin_Notice[]
		 */
		private static function get_all() {
			static $notices = false;
			if ( false === $notices ) {
				$notices = array_merge(
					[
						new WCPBC_Admin_Notice_Unconnected(),
						new WCPBC_Admin_Notice_WC_Payments_Multicurrency_Disabled(),
						new WCPBC_Admin_Notice_Multicurrency_Incompatible(),
						new WCPBC_Admin_Notice_Review_Request(),
						new WCPBC_Admin_Notice_Update_Welcome(),
					],
					WCPBC_Admin_Ads::admin_notices()
				);
			}

			return $notices;
		}

		/**
		 * Add notices to admin_notices hook
		 */
		public static function enqueue_notices() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			$notices = self::get_all();
			foreach ( $notices as $notice ) {
				$notice->enqueue();
			}
		}


		/**
		 * Hide a notice via ajax.
		 */
		public static function dismiss_notice() {

			check_ajax_referer( 'wc_price_based_country_dismiss_notice' );

			$notice_id = isset( $_GET['id'] ) ? wc_clean( wp_unslash( $_GET['id'] ) ) : false;
			$notices   = self::get_all();

			foreach ( $notices as $notice ) {
				if ( $notice->get_id() && $notice->get_id() === $notice_id ) {
					$notice->set_status( WCPBC_Admin_Notice::STATUS_DISMISSED );
					break;
				}
			}

			wp_safe_redirect( wp_get_referer() );
			exit;
		}

		/**
		 * Output product type not supported notice.
		 */
		public static function display_product_type_not_supported() {
			$supported_product_types = array_keys( wcpbc_product_types_supported() );

			$args = [
				'type'    => 'warning',
				'inline'  => true,
				'content' => sprintf(
					// Translators: 1,2,3,4 HTML tags.
					esc_html__(
						'%1$sHi, this product type is not supported by %2$sPrice Based on Country%3$s. Use it with caution.%4$s',
						'woocommerce-product-price-based-on-countries'
					),
					'<p style="margin:0;">',
					'<strong>',
					'</strong>',
					'</p>',
				),
			];

			foreach ( wc_get_product_types() as $value => $label ) {
				if ( in_array( $value, $supported_product_types, true ) ) {
					continue;
				}
				$notice = new \WCPBC_Admin_Notice( $args );
				$notice->set_attributes(
					[
						'class' => "wc-pbc-show-if-not-supported product-type-{$value}",
						'style' => 'margin:0;display:none;',
					]
				);
				$notice->print();
			}
		}
	}

endif;
