<?php
/**
 * Populate the post meta table with the _price meta key.
 *
 * @since 4.0.0
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Product_Meta_Job_Add_Zone class.
 */
class WCPBC_Product_Meta_Job_Add_Zone extends WCPBC_Product_Meta_Job {

	/**
	 * Runs the job.
	 */
	public function run_job() {

		$meta_keys_query = $this->get_zones_metaquery( [ '_price' ], $this->args );

		if ( $meta_keys_query ) {

			$rows_affected = $this->db()->query(
				$this->db()->prepare(
					"INSERT INTO `{$this->table->postmeta}` (post_id, meta_key, meta_value)
					SELECT product_meta_lookup.product_id  AS post_id,
						zones_query._price_field_name AS meta_key,
						ROUND(product_meta_lookup.min_price * zones_query.exchange_rate, %d) AS meta_value
					FROM `{$this->table->product_meta_lookup}` product_meta_lookup
					CROSS JOIN ({$meta_keys_query}) as zones_query
					WHERE NOT product_meta_lookup.min_price IS NULL
					AND EXISTS (
						SELECT 1 FROM `{$this->table->posts}` posts WHERE {$this->get_post_filter()}
						AND posts.ID = product_meta_lookup.product_id
					)
					AND NOT EXISTS (
						SELECT 1 FROM `{$this->table->postmeta}`
						WHERE `{$this->table->postmeta}`.post_id = product_meta_lookup.product_id
						AND `{$this->table->postmeta}`.meta_key = zones_query._price_field_name
					)",
					wcpbc_get_rounding_precision()
				)
			);

			if ( $rows_affected > 0 ) {

				$this->clear_cache = true;

				WCPBC_Product_Meta_Job::create(
					'Sync_Price_With_Children',
					[
						'zone_id' => $this->args,
					]
				)->run();
			}
		}
	}
}
