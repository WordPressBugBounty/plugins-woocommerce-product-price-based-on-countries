<?php
/**
 * Delete a pricing zone from the post meta table.
 *
 * @since 4.0.0
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Product_Meta_Job_Delete_Zone class.
 */
class WCPBC_Product_Meta_Job_Delete_Zone extends WCPBC_Product_Meta_Job {

	/**
	 * Metakeys to delete.
	 *
	 * @var array
	 */
	private const META_KEYS = [
		'_price',
		'_regular_price',
		'_sale_price',
		'_price_method',
		'_sale_price_dates',
		'_sale_price_dates_from',
		'_sale_price_dates_to',
	];

	/**
	 * Delete a postmeta key for the zone.
	 *
	 * @param array $meta_keys_to_delete Meta keys to delete.
	 * @return int
	 */
	protected function delete_postmeta_keys( $meta_keys_to_delete ) {

		$where = $this->prepare_in( "AND `{$this->table->postmeta}`.meta_key IN (%s) ", $meta_keys_to_delete );

		return $this->db()->query(
			"DELETE FROM `{$this->table->postmeta}`
			WHERE EXISTS (
				SELECT 1 FROM `{$this->table->posts}` posts
				WHERE posts.ID = `{$this->table->postmeta}`.post_id AND posts.post_type IN ('product', 'product_variation')
			) {$where}"
		);
	}

	/**
	 * Runs the job.
	 */
	public function run_job() {

		$meta_keys_to_delete = [];

		foreach ( $this->args as $zone_id ) {
			if ( WCPBC_Pricing_Zones::get_zone( $zone_id ) ) {
				continue;
			}

			$zone = new WCPBC_Pricing_Zone(
				[
					'id' => $zone_id,
				]
			);

			foreach ( self::META_KEYS as $meta_key ) {
				$meta_keys_to_delete[] = $zone->get_postmetakey( $meta_key );
			}
		}

		if ( $meta_keys_to_delete ) {
			$post_ids = false;

			if ( wp_using_ext_object_cache() ) {
				$post_ids = get_posts(
					[
						'fields'         => 'ids',
						'posts_per_page' => -1,
						'post_type'      => [ 'product', 'product_variation' ],
						'post_status'    => 'publish',
						'meta_key'       => $meta_keys_to_delete, // phpcs:ignore WordPress.DB.SlowDBQuery
						'meta_compare'   => 'EXISTS',
					]
				);
			}

			$rows_affected     = $this->delete_postmeta_keys( $meta_keys_to_delete );
			$this->clear_cache = $rows_affected > 0;

			if ( $this->clear_cache && $post_ids && function_exists( 'wp_cache_delete_multiple' ) ) {
				wp_cache_delete_multiple( $post_ids, 'post_meta' );
			}
		}
	}
}
