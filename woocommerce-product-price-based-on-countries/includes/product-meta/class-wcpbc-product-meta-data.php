<?php
/**
 * Product meta data.
 *
 * Updates the meta data on certain actions.
 *
 * @since 4.0.0
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Product_Meta_Data class.
 */
class WCPBC_Product_Meta_Data {

	/**
	 * Product sync queue.
	 *
	 * @var array
	 */
	private static $children_sync_queue = [];

	/**
	 * Updated meta price queue;
	 *
	 * @var array
	 */
	private static $updated_meta_price = [];

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'shutdown', [ __CLASS__, 'maybe_sync_products' ], 9 );
		add_action( 'updated_postmeta', [ __CLASS__, 'maybe_enqueue_update_meta_price' ], 10, 4 );
		add_action( 'update_option_wc_price_based_country_regions', [ __CLASS__, 'update_pricing_zones' ], 10, 2 );
		add_action( 'wc_after_products_starting_sales', [ __CLASS__, 'after_products_starting_sales' ], 10, 2 );
		add_action( 'wc_after_products_ending_sales', [ __CLASS__, 'after_products_ending_sales' ], 10, 2 );
		add_action( 'woocommerce_scheduled_sales', [ __CLASS__, 'scheduled_sales' ], 11 );
		add_action( 'wc_price_based_country_product_meta_job', [ __CLASS__, 'run_product_meta_job' ], 10, 2 );
	}

	/**
	 * Maybe enqueue a parent product for children price sync.
	 *
	 * @param int    $post_id Product ID.
	 * @param string $zone_id Zone ID.
	 */
	public static function maybe_enqueue_children_sync( $post_id, $zone_id ) {
		$parent_id = wp_get_post_parent_id( $post_id );
		if ( ! $parent_id ) {
			return;
		}

		if ( ! isset( self::$children_sync_queue['parent_id'] ) ) {
			self::$children_sync_queue['parent_id'] = [];
		}

		if ( ! in_array( absint( $parent_id ), self::$children_sync_queue['parent_id'], true ) ) {
			self::$children_sync_queue['parent_id'][] = absint( $parent_id );
		}

		if ( ! isset( self::$children_sync_queue['zone_id'] ) ) {
			self::$children_sync_queue['zone_id'] = [];
		}

		if ( ! in_array( $zone_id, self::$children_sync_queue['zone_id'], true ) ) {
			self::$children_sync_queue['zone_id'][] = $zone_id;
		}
	}

	/**
	 * Maybe enqueue a product for multilang price sync.
	 *
	 * @param int    $post_id Product ID.
	 * @param string $zone_id Zone ID.
	 */
	public static function maybe_enqueue_multilang_sync( $post_id, $zone_id ) {
		$multilang = self::get_multilang_handler();

		if ( ! $multilang ) {
			return;
		}

		$multilang->enqueue( $post_id, $zone_id );
	}

	/**
	 * Returns the multilang handler.
	 *
	 * @return WCPBC_WPML|bool False is there is no multilang handler.
	 */
	private static function get_multilang_handler() {
		if ( class_exists( 'WCPBC_WPML' ) ) {

			return WCPBC_WPML::instance();

		} elseif ( class_exists( 'WCPBC_Polylang' ) ) {

			return WCPBC_Polylang::instance();
		}
		return false;
	}

	/**
	 * Clear transient cache for product data.
	 *
	 * @param int $post_id (default: 0) The product ID.
	 */
	public static function delete_product_transients( $post_id = 0 ) {
		delete_transient( 'wcpbc_products_onsale' );

		// Increments the transient version to invalidate cache.
		WC_Cache_Helper::get_transient_version( 'product', true );
		WC_Cache_Helper::get_transient_version( 'product_query', true );

		$parent_id = $post_id > 0 ? wp_get_post_parent_id( $post_id ) : false;
		if ( $parent_id ) {
			delete_transient( 'wc_var_prices_' . $parent_id );
		}
	}

	/**
	 * Syncs product prices if there are items in the queues.
	 */
	public static function maybe_sync_products() {
		self::maybe_update_meta_price();
		self::maybe_multilang_sync();
		self::maybe_sync_price_with_children();
	}

	/**
	 * Update exchange rate prices of the products in the queue.
	 */
	private static function maybe_update_meta_price() {

		foreach ( self::$updated_meta_price as $post_id ) {
			foreach ( WCPBC_Pricing_Zones::get_zones() as $zone ) {

				if ( ! $zone->is_exchange_rate_price( $post_id ) ) {
					continue;
				}

				$zone->set_postmeta(
					$post_id,
					'_price',
					$zone->get_exchange_rate_price_by_post( $post_id, '_price' )
				);
			}
		}
		self::$updated_meta_price = [];
	}

	/**
	 * Sync the metadata with the product translations.
	 */
	private static function maybe_multilang_sync() {
		static $avoid_recursion = false;

		if ( $avoid_recursion ) {
			return;
		}

		$multilang = self::get_multilang_handler();

		if ( ! $multilang ) {
			return;
		}

		$avoid_recursion = true;

		$multilang->sync_queue();

		$avoid_recursion = false;
	}

	/**
	 * Syncs product prices with children if there are products in the queue.
	 */
	private static function maybe_sync_price_with_children() {
		if ( isset( self::$children_sync_queue['parent_id'], self::$children_sync_queue['zone_id'] ) && count( self::$children_sync_queue['parent_id'] ) && count( self::$children_sync_queue['zone_id'] ) ) {

			WCPBC_Product_Meta_Job::create( 'Sync_Price_With_Children', self::$children_sync_queue )->run();
		}
		self::$children_sync_queue = [];
	}

	/**
	 * After updated the _price postmeta key
	 *
	 * @param int    $meta_id    ID of updated metadata entry.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value.
	 */
	public static function maybe_enqueue_update_meta_price( $meta_id, $object_id, $meta_key, $meta_value ) {
		if (
			'_price' === $meta_key
			&& in_array( get_post_type( $object_id ), [ 'product', 'product_variation' ], true )
			&& ! in_array( get_post_status( $object_id ), [ 'trash', 'auto-draft' ], true )
			&& ! in_array( $object_id, self::$updated_meta_price, true )
		) {
			self::$updated_meta_price[] = $object_id;
		}
	}

	/**
	 * Updates the product meta rows after pricing zones opion is updated.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $value     The new option value.
	 */
	public static function update_pricing_zones( $old_value, $value ) {
		if ( ! is_array( $value ) ) {
			return;
		}

		$ids = [];
		foreach ( $old_value as $key => $data ) {
			if ( ! isset( $value[ $key ] ) ) {
				$ids[] = $key;
			}
		}

		if ( count( $ids ) ) {
			WCPBC_Product_Meta_Job::create( 'Delete_Zone', $ids )->run_async();
		}

		$ids = [];
		foreach ( $value as $key => $data ) {
			if ( ! isset( $old_value[ $key ] ) ) {
				$ids[] = $key;
			}
		}

		if ( count( $ids ) ) {
			WCPBC_Product_Meta_Job::create( 'Add_Zone', $ids )->run_async();
		}

		$ids = [];
		foreach ( $value as $key => $data ) {
			if ( ! isset( $old_value[ $key ] ) ) {
				continue;
			}

			$old_exchange_rate = isset( $old_value[ $key ]['exchange_rate'] ) ? $old_value[ $key ]['exchange_rate'] : '';
			$new_exchange_rate = isset( $value[ $key ]['exchange_rate'] ) ? $value[ $key ]['exchange_rate'] : '';

			if ( $old_exchange_rate !== $new_exchange_rate ) {
				$ids[] = $key;
			}
		}

		if ( count( $ids ) ) {
			WCPBC_Product_Meta_Job::create( 'Update_Column_With_Exchange_Rate', $ids )->run_async();
		}
	}

	/**
	 * Updates price after starting sales.
	 *
	 * @param array $product_ids Array of product IDs which starting/ending sales.
	 */
	public static function after_products_starting_sales( $product_ids ) {
		WCPBC_Product_Meta_Job::create(
			'Starting_Sales',
			[
				'method'      => 'default',
				'product_ids' => $product_ids,
			]
		)->run();
	}

	/**
	 * Updates price after ending sales.
	 *
	 * @param array $product_ids Array of product IDs which starting/ending sales.
	 */
	public static function after_products_ending_sales( $product_ids ) {
		WCPBC_Product_Meta_Job::create(
			'Ending_Sales',
			[
				'method'      => 'default',
				'product_ids' => $product_ids,
			]
		)->run();
	}

	/**
	 * Handles the start and end of scheduled sales with manual dates.
	 */
	public static function scheduled_sales() {
		WCPBC_Product_Meta_Job::create( 'Starting_Sales' )->run();
		WCPBC_Product_Meta_Job::create( 'Ending_Sales' )->run();
	}

	/**
	 * Run async job.
	 *
	 * @param string $name Job name.
	 * @param array  $args Arguments.
	 */
	public static function run_product_meta_job( $name, $args = [] ) {
		$job = WCPBC_Product_Meta_Job::create( $name, $args );
		if ( $job ) {
			$job->run();
		}
	}


}
