<?php
/**
 * WooCommerce Price Based Country Admin notice class.
 *
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Admin_Notice Class.
 */
class WCPBC_Admin_Notice {

	/**
	 * Option key used to dimissed notices.
	 *
	 * @var string
	 */
	private const NOTICES_KEY = 'notices_status';

	/**
	 * Status visible.
	 *
	 * @var string
	 */
	public const STATUS_VISIBLE = '1';

	/**
	 * Status dismissed.
	 *
	 * @var string
	 */
	public const STATUS_DISMISSED = '0';

	/**
	 * Object properties.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Constructor
	 *
	 * @param array $props Array of properties $prop => $value to set.
	 */
	public function __construct( $props = [] ) {
		$this->set_defaults();
		$this->set_props( $props );
	}

	/**
	 * Set defaults.
	 */
	protected function set_defaults() {
		$this->data = [
			'default_status' => self::STATUS_VISIBLE,
			'id'             => '',
			'type'           => 'info',
			'dismissible'    => false,
			'inline'         => false,
			'content'        => '',
			'attributes'     => [],
			'screens'        => [],
		];
	}

	/**
	 * Set notice default status.
	 *
	 * @param string $default_status Notice default status.
	 */
	protected function set_default_status( $default_status ) {
		$this->set_prop( 'default_status', $default_status );
	}

	/**
	 * Get notice default status.
	 *
	 * @return string
	 */
	protected function get_default_status() {
		return $this->data['default_status'];
	}

	/**
	 * Set object property.
	 *
	 * @param string $key Property to set.
	 * @param mixed  $value Property value.
	 */
	protected function set_prop( $key, $value ) {
		if ( ! ( isset( $this->data[ $key ] ) && gettype( $value ) === gettype( $this->data[ $key ] ) ) ) {
			return;
		}
		$this->data[ $key ] = $value;
	}

	/**
	 * Set object properties.
	 *
	 * @param array $props Array of properties $prop => $value to set.
	 */
	public function set_props( $props ) {
		if ( ! is_array( $props ) ) {
			return;
		}
		foreach ( $props as $key => $value ) {
			$setter = "set_{$key}";
			if ( is_callable( [ $this, $setter ] ) ) {
				$this->{$setter}( $value );
			}
		}
	}

	/**
	 * Set notice ID.
	 *
	 * @param string $id Notice ID.
	 */
	public function set_id( $id ) {
		$this->set_prop( 'id', $id );
	}

	/**
	 * Get notice ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->data['id'];
	}

	/**
	 * Set notice type.
	 *
	 * @param string $type Notice type.
	 */
	public function set_type( $type ) {
		$this->set_prop( 'type', $type );
	}

	/**
	 * Get notice type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->data['type'];
	}

	/**
	 * Set whether the notice is dismissible.
	 *
	 * @param bool $dismissible Whether the notice is dismissible.
	 */
	public function set_dismissible( $dismissible ) {
		$this->set_prop( 'dismissible', $dismissible );
	}

	/**
	 * Get whether the notice is dismissible.
	 *
	 * @return bool
	 */
	public function get_dismissible() {
		return $this->data['dismissible'];
	}

	/**
	 * Set whether the notice is inline.
	 *
	 * @param bool $inline Whether the notice is inline.
	 */
	public function set_inline( $inline ) {
		$this->set_prop( 'inline', $inline );
	}

	/**
	 * Get whether the notice is inline.
	 *
	 * @return bool
	 */
	public function get_inline() {
		return $this->data['inline'];
	}

	/**
	 * Set notice content.
	 *
	 * @param string $content Notice content.
	 */
	public function set_content( $content ) {
		$this->set_prop( 'content', $content );
	}

	/**
	 * Get notice content.
	 *
	 * @return string
	 */
	public function get_content() {
		return $this->data['content'];
	}

	/**
	 * Set notice attributes.
	 *
	 * @param array $attributes Notice attributes.
	 */
	public function set_attributes( $attributes ) {
		$this->set_prop( 'attributes', $attributes );
	}

	/**
	 * Get notice attributes.
	 *
	 * @return array
	 */
	public function get_attributes() {
		return $this->data['attributes'];
	}

	/**
	 * Set notice screens.
	 *
	 * @param array $screens Notice screens.
	 */
	public function set_screens( $screens ) {
		$this->set_prop( 'screens', $screens );
	}

	/**
	 * Get notice screens.
	 *
	 * @return array
	 */
	public function get_screens() {
		return $this->data['screens'];
	}

	/**
	 * Set notice status.
	 *
	 * @param string $status Notice status (0|1).
	 */
	public function set_status( $status ) {
		if ( ! $this->get_id() ) {
			return;
		}

		$all_status = WCPBC_Helper_Options::get( self::NOTICES_KEY );

		if ( ! is_array( $all_status ) ) {
			$all_status = [];
		}

		$all_status[ $this->get_id() ] = self::STATUS_VISIBLE === (string) $status ? self::STATUS_VISIBLE : self::STATUS_DISMISSED;

		WCPBC_Helper_Options::update( self::NOTICES_KEY, $all_status );
	}

	/**
	 * Get notice status.
	 *
	 * @return string STATUS_VISIBLE|STATUS_DISMISSED
	 */
	public function get_status() {
		$status = $this->get_default_status();

		if ( $this->get_id() ) {
			$all_status = WCPBC_Helper_Options::get( self::NOTICES_KEY );
			$status     = is_array( $all_status ) && isset( $all_status[ $this->get_id() ] ) ? $all_status[ $this->get_id() ] : $this->get_default_status();
		}

		return self::STATUS_VISIBLE === (string) $status ? self::STATUS_VISIBLE : self::STATUS_DISMISSED;
	}

	/**
	 * Returns the current_screen ID
	 *
	 * @return string
	 */
	protected function get_current_screen_id() {
		static $screen_id = false;
		if ( false !== $screen_id ) {
			return $screen_id;
		}
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		return $screen_id;
	}

	/**
	 * Return the admin notice hook.
	 *
	 * @return string
	 */
	protected function get_admin_notices_hook() {
		static $admin_notices_hook = false;
		if ( false !== $admin_notices_hook ) {
			return $admin_notices_hook;
		}

		if ( 'woocommerce_page_wc-settings' === $this->get_current_screen_id() ) {

			$current_tab        = empty( $_GET['tab'] ) ? 'general' : wc_clean( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$admin_notices_hook = 'woocommerce_settings_' . $current_tab;

		} else {
			$admin_notices_hook = 'admin_notices';
		}

		return $admin_notices_hook;
	}

	/**
	 * Return the dismiss URL.
	 *
	 * @return string
	 */
	protected function get_dismiss_url() {
		if ( ! $this->get_id() ) {
			return '#';
		}

		return wp_nonce_url(
			add_query_arg(
				[
					'action' => 'wc_price_based_country_dismiss_notice',
					'id'     => $this->get_id(),
				],
				admin_url( 'admin-ajax.php' )
			),
			'wc_price_based_country_dismiss_notice'
		);
	}

	/**
	 * Return whether the notice should be displayed.
	 *
	 * @return bool
	 */
	protected function should_display() {
		if ( ! empty( $this->get_screens() ) && ! in_array( $this->get_current_screen_id(), $this->get_screens(), true ) ) {
			return false;
		}

		$notices_screens = array_merge( wc_get_screen_ids(), [ 'plugins', 'dashboard' ] );
		if ( ! in_array( $this->get_current_screen_id(), $notices_screens, true ) ) {
			return false;
		}

		if ( self::STATUS_VISIBLE !== $this->get_status() ) {
			return false;
		}

		return true;
	}

	/**
	 * Output HTML attributes.
	 */
	protected function print_html_attributes() {
		$classes = [
			'notice',
			'notice-' . $this->get_type(),
		];

		if ( $this->get_dismissible() ) {
			$classes[] = 'is-dismissible';
		}

		if ( $this->get_inline() || 'woocommerce_page_wc-settings' === $this->get_current_screen_id() ) {
			$classes[] = 'inline';
		}

		$html_attributes          = $this->get_attributes();
		$html_attributes['class'] = implode( ' ', $classes ) . ( empty( $html_attributes['class'] ) ? '' : ' ' . (string) $html_attributes['class'] );
		foreach ( $html_attributes as $html_attribute => $value ) {
			printf( '%s="%s"', esc_attr( $html_attribute ), esc_attr( $value ) );
		}
	}

	/**
	 * Output dismiss link.
	 */
	protected function print_dismiss_link() {
		if ( ! ( $this->get_id() && $this->get_dismissible() ) ) {
			return;
		}
		?>
		<a class="notice-dismiss" role="button" href="<?php echo esc_url( $this->get_dismiss_url() ); ?>" style="text-decoration:none;">
			<span class="screen-reader-text">
				<?php esc_html_e( 'Dismiss', 'woocommerce-product-price-based-on-countries' ); ?>
			</span>
		</a>
		<?php
	}

	/**
	 * Output notice content.
	 */
	protected function print_content() {
		echo wp_kses_post( $this->get_content() );
	}

	/**
	 * Output a notice.
	 */
	public function print() {
		if ( ! $this->should_display() ) {
			return;
		}
		?>
		<div <?php $this->print_html_attributes(); ?>>
			<div class="notification-message" style="padding-inline-end: 24px;">
			<?php
				$this->print_dismiss_link();
				$this->print_content();
			?>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue a notice to print on the admin_notices hook.
	 */
	public function enqueue() {
		if ( ! $this->should_display() ) {
			return;
		}

		$admin_hook = $this->get_admin_notices_hook();
		$priority   = 'admin_notices' === $admin_hook ? 10 : 1;

		if ( doing_action( $admin_hook ) ) {
			$this->print();
			return;
		}

		if ( ! has_action( $admin_hook, [ $this, 'print' ] ) ) {
			add_action( $admin_hook, [ $this, 'print' ], $priority );
		}
	}
}
