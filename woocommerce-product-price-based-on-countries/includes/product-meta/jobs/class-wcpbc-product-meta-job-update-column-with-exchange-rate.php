<?php
/**
 * Update column with exchange rate.
 *
 * @since 4.0.0
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Legacy_Job_Update_Column_With_Exchange_Rate class.
 */
class WCPBC_Product_Meta_Job_Update_Column_With_Exchange_Rate extends WCPBC_Product_Meta_Job {

	/**
	 * Runs the job.
	 */
	public function run_job() {

		$meta_keys_query = $this->get_zones_metaquery( [ '_price', '_price_method' ], $this->args );

		if ( ! $meta_keys_query ) {
			return false;
		}

		$rows_affected = $this->db()->query(
			$this->db()->prepare(
				"UPDATE `{$this->table->postmeta}` INNER JOIN
					(
						SELECT product_meta_lookup.product_id as post_id,
							zones_query._price_field_name as meta_key,
							ROUND(product_meta_lookup.min_price * zones_query.exchange_rate, %d) as meta_value
						FROM `{$this->table->product_meta_lookup}` product_meta_lookup
						INNER JOIN `{$this->table->posts}` posts  ON posts.Id = product_meta_lookup.product_id
						CROSS JOIN ({$meta_keys_query}) as zones_query
						LEFT JOIN `{$this->table->postmeta}` postmeta ON
								product_meta_lookup.product_id = postmeta.post_id
							AND postmeta.meta_key = zones_query._price_method_field_name
							AND postmeta.meta_value = 'manual'
						WHERE {$this->get_post_filter()}
						AND NOT product_meta_lookup.min_price IS NULL
						AND postmeta.meta_id IS NULL
					) source_data
				ON `{$this->table->postmeta}`.post_id = source_data.post_id
				AND `{$this->table->postmeta}`.meta_key = source_data.meta_key
				SET `{$this->table->postmeta}`.meta_value = source_data.meta_value
				WHERE source_data.meta_value != ROUND(`{$this->table->postmeta}`.meta_value+0, %d)",
				wcpbc_get_rounding_precision(),
				wcpbc_get_rounding_precision()
			)
		);

		$rows_affected += $this->db()->query(
			"UPDATE `{$this->table->postmeta}` SET meta_value = ''
			WHERE meta_key IN ( SELECT _price_field_name FROM ({$meta_keys_query}) as zones_query )
			AND meta_value != ''
			AND NOT EXISTS (
				SELECT 1 FROM `{$this->table->product_meta_lookup}` product_meta_lookup
				WHERE product_meta_lookup.product_id = `{$this->table->postmeta}`.post_id
				AND NOT product_meta_lookup.min_price IS NULL
			)"
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
