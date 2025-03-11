<?php
/**
 * Execute a job on the product meta storage.
 *
 * @since 4.0.0
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Product_Meta_Job class.
 */
abstract class WCPBC_Product_Meta_Job {

	/**
	 * Table names
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Job arguments.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Clear cache flag.
	 *
	 * @var bool
	 */
	protected $clear_cache = false;

	/**
	 * Constructor.
	 *
	 * @param mixed $args Task arguments.
	 */
	protected function __construct( $args = false ) {
		global $wpdb;
		$this->args  = is_array( $args ) ? $args : [];
		$this->table = (object) [
			'prefix'              => $wpdb->prefix,
			'posts'               => $wpdb->posts,
			'postmeta'            => $wpdb->postmeta,
			'product_meta_lookup' => $wpdb->prefix . 'wc_product_meta_lookup',
		];
	}


	/**
	 * Runs the job asynchronous.
	 */
	public function run_async() {
		as_enqueue_async_action(
			'wc_price_based_country_product_meta_job',
			[
				'job'  => substr( get_class( $this ), 23 ),
				'args' => $this->args,
			],
			'wc_price_based_country_product_meta_job',
			false,
			5
		);
	}

	/**
	 * Runs the job.
	 */
	public function run() {
		$this->args        = wc_clean( $this->args );
		$this->clear_cache = false;

		$this->run_job();

		if ( $this->clear_cache ) {
			$this->clear_caches();
		}
	}

	/**
	 * Clear any caches.
	 */
	protected function clear_caches() {
		// Increments the transient version to invalidate cache.
		WC_Cache_Helper::get_transient_version( 'product', true );
		WC_Cache_Helper::get_transient_version( 'product_query', true );
	}

	/**
	 * Does the action of the job.
	 */
	abstract protected function run_job();

	/**
	 * Returns the wpdb instance.
	 *
	 * @return wpdb
	 */
	protected function db() {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Returns the exclude post status filter.
	 *
	 * @param string $tablename Post table name.
	 * @return string
	 */
	protected function get_post_filter( $tablename = 'posts' ) {
		return "{$tablename}.post_type IN ('product', 'product_variation') AND {$tablename}.post_status NOT IN ('trash', 'auto-draft')";
	}

	/**
	 * Returns the pricing zones query.
	 *
	 * @param array $meta_keys Array of metakeys to generate query.
	 * @param array $zone_ids Array of zone IDs.
	 */
	protected function get_zones_metaquery( $meta_keys, $zone_ids = false ) {

		if ( ! $meta_keys ) {
			return false;
		}

		$metaquery = false;
		$query     = [];

		if ( ! $meta_keys ) {
			return false;
		}

		foreach ( WCPBC_Pricing_Zones::get_zones( $zone_ids ) as $zone ) {

			$fields = [];
			foreach ( $meta_keys as $meta_key ) {

				$fields[] = $this->db()->prepare(
					'convert(%s using utf8) AS %s',
					$zone->get_postmetakey( $meta_key ),
					$meta_key . '_field_name'
				);
			}

			$select = implode( ', ', $fields );

			$query[] = $this->db()->prepare(
				"(SELECT %s as zone_id, {$select}, (%s+0) AS exchange_rate )",
				$zone->get_id(),
				$zone->get_exchange_rate()
			);
		}

		if ( count( $query ) ) {
			$metaquery = implode( ' UNION ', $query );
		}
		return $metaquery;
	}

	/**
	 * Prepare for IN stament.
	 *
	 * @param string $query Query statement with one placeholder.
	 * @param array  $values In values.
	 */
	protected function prepare_in( $query, $values ) {

		$pos = strpos( $query, '%' );

		if ( false === $pos ) {
			return $query;
		}

		$specifier = substr( $query, $pos, 2 );
		$specifier = '%d' !== $specifier ? '%s' : '%d';
		$query     = str_replace( '%d', '%s', $query );

		return $this->db()->prepare(
			sprintf(
				$query,
				implode( ', ', array_fill( 0, count( $values ), $specifier ) )
			),
			$values
		);
	}

	/**
	 * Create a new job.
	 *
	 * @param string $job Task name.
	 * @param array  $args Task arguments.
	 */
	public static function create( $job, $args = false ) {
		$classname = 'WCPBC_Product_Meta_Job_' . $job;
		$job       = false;

		if ( class_exists( $classname ) ) {
			$job = new $classname( $args );
		}

		return $job;
	}
}
