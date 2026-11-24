<?php
/**
 * Update welcome admin notice class.
 *
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Admin_Notice_Update_Welcome Class.
 */
class WCPBC_Admin_Notice_Update_Welcome extends WCPBC_Admin_Notice {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->set_props(
			[
				'id'             => 'update_welcome',
				'dismissible'    => true,
				'default_status' => self::STATUS_DISMISSED,
			]
		);
	}

	/**
	 * Output notice content.
	 */
	protected function print_content() {
		?>
		<p style="font-size:1rem;font-weight:600;">
			<?php esc_html_e( 'Thank you for updating to Price Based on Country 4.4!', 'woocommerce-product-price-based-on-countries' ); ?>
		</p>
		<p style="margin-bottom:1rem;">
			<?php esc_html_e( 'The new version includes a country selector in the admin bar for easier pricing zone testing, alongside frontend performance and SEO improvements.', 'woocommerce-product-price-based-on-countries' ); ?>
		</p>
		<p>
			<a href="<?php echo esc_url( wcpbc_home_url( 'update-welcome-notice', '2026/11/24/price-based-on-country-4-4-0-release-notes' ) ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">
				<?php esc_html_e( 'Read release notes', 'woocommerce-product-price-based-on-countries' ); ?>
			</a>
			<a href="<?php echo esc_url( $this->get_dismiss_url() ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Dismiss', 'woocommerce-product-price-based-on-countries' ); ?>
			</a>
		</p>
		<?php
	}
}
