<?php
/**
 * Review request Admin notice class.
 *
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Admin_Notice_Review_Request Class.
 */
class WCPBC_Admin_Notice_Review_Request extends WCPBC_Admin_Notice {

	/**
	 * Days to delay constant.
	 */
	private const DAYS_TO_DELAY = 14;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->set_props(
			[
				'id'          => 'review_request',
				'dismissible' => true,
			]
		);
	}

	/**
	 * Return whether the notice should be displayed.
	 *
	 * @return bool
	 */
	protected function should_display() {
		if ( ! parent::should_display() ) {
			return false;
		}

		$install_timestamp = WCPBC_Helper_Options::get( 'install_timestamp' );

		if ( $install_timestamp && ( time() - $install_timestamp > self::DAYS_TO_DELAY * DAY_IN_SECONDS ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Output notice content.
	 */
	protected function print_content() {
		?>
		<p>
			<?php
				// translators: HTML tags.
				printf( esc_html( __( 'Hi! Do you like %1$sWooCommerce Price Based on Country%2$s? Would you mind taking a moment to rate it? It won\'t take more than a minute. Thanks for your support!', 'woocommerce-product-price-based-on-countries' ) ), '<strong>', '</strong>' );
			?>
			<span class="dashicons dashicons-smiley"></span>
		</p>
		<p class="submit">
			<a class="button-primary" target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/woocommerce-product-price-based-on-countries/reviews/?rate=5#new-post"><?php esc_html_e( 'Yes, rate now!', 'woocommerce-product-price-based-on-countries' ); ?></a>
			<a class="button-secondary" href="<?php echo esc_url( $this->get_dismiss_url() ); ?>">
				<?php esc_html_e( 'No, thanks', 'woocommerce-product-price-based-on-countries' ); ?>
			</a>
		</p>
		<?php
	}
}
