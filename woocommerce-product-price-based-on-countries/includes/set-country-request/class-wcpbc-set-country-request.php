<?php
/**
 * Set country request abstract class.
 *
 * @package WCPBC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCPBC_Set_Country_Request.
 */
abstract class WCPBC_Set_Country_Request {

	/**
	 * Array of properties
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Maps request keys to properties.
	 *
	 * @var array
	 */
	protected $key_to_prop = [];

	/**
	 * Request var
	 *
	 * @var string
	 */
	protected $method = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->set_defaults();
		$this->populate_data();
	}

	/**
	 * Set defaults properties.
	 */
	protected function set_defaults() {
		$this->data = [];
		foreach ( array_unique( array_values( $this->key_to_prop ) ) as $prop ) {
			$this->data[ $prop ] = false;
		}
	}

	/**
	 * Returns posted data from HTTP Request.
	 *
	 * @return array
	 */
	protected function get_posted_data() {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( 'post' === $this->method ) {
			return $_POST;
		} elseif ( 'get' === $this->method ) {
			return $_GET;
		} else {
			return $_REQUEST;
		}
		// phpcs:enable WordPress.Security.NonceVerification
	}

	/**
	 * Populates the data.
	 *
	 * @return void
	 */
	protected function populate_data() {
		$posteddata = $this->get_posted_data();
		foreach ( $this->key_to_prop as $key => $prop ) {
			if ( ! empty( $posteddata[ $key ] ) ) {
				$this->data[ $prop ] = wc_clean( wp_unslash( $posteddata[ $key ] ) );
			}
		}
	}

	/**
	 * Sets the requested customer country.
	 *
	 * @return bool
	 */
	abstract public function set_country();
}
