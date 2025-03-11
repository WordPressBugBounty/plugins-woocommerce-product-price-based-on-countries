<?php
/**
 * End of scheduled sales.
 *
 * @since 4.0.0
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Product_Meta_Job_Ending_Sales class.
 */
class WCPBC_Product_Meta_Job_Ending_Sales extends WCPBC_Product_Meta_Job {

	/**
	 * Ends the sale.
	 *
	 * @param array $rows Array of zone Id and post ID to set the sale price.
	 */
	protected function remove_sale_price( $rows ) {
		$zone = false;

		foreach ( $rows as $row ) {

			if ( ! $zone || $zone->get_id() !== $row->zone_id ) {
				$zone = WCPBC_Pricing_Zones::get_zone( $row->zone_id );
			}

			if ( ! $zone ) {
				continue;
			}

			$regular_price = $zone->get_postmeta( $row->post_id, '_regular_price' );

			$zone->set_postmeta( $row->post_id, '_price', $regular_price );
			$zone->set_postmeta( $row->post_id, '_sale_price', '' );
			$zone->delete_postmeta( $row->post_id, '_sale_price_dates_to' );
			$zone->delete_postmeta( $row->post_id, '_sale_price_dates_from' );
		}
	}

	/**
	 * Runs the job.
	 */
	public function run_job() {
		$this->args      = wp_parse_args(
			$this->args,
			[
				'method'      => 'manual',
				'product_ids' => [],
			]
		);
		$meta_keys_query = $this->get_zones_metaquery( [ '_price_method', '_sale_price_dates', '_sale_price', '_sale_price_dates_to' ] );
		$rows            = [];

		if ( 'manual' !== $this->args['method'] && ! empty( $this->args['product_ids'] ) ) {

			$starting_sale_where = $this->prepare_in( 'posts.ID IN (%d)', array_map( 'absint', $this->args['product_ids'] ) );

			$rows = $this->db()->get_results(
				"SELECT zones_query.zone_id, posts.ID as post_id
				FROM {$this->table->posts} posts
				CROSS JOIN ({$meta_keys_query}) as zones_query
				INNER JOIN {$this->table->postmeta} meta__price_method
					ON meta__price_method.post_id = posts.ID
					AND meta__price_method.meta_key = zones_query._price_method_field_name
				WHERE {$this->get_post_filter()} AND {$starting_sale_where}
				AND meta__price_method.meta_value = 'manual'
				AND NOT EXISTS (
					SELECT 1 FROM {$this->table->postmeta} postmeta
					WHERE posts.ID = postmeta.post_id
					AND zones_query._sale_price_dates_field_name = postmeta.meta_key
					AND postmeta.meta_value = 'manual'
				) ORDER BY zones_query.zone_id, posts.ID"
			);

		} elseif ( 'manual' === $this->args['method'] ) {

			$rows = $this->db()->get_results(
				$this->db()->prepare(
					"SELECT zones_query.zone_id, posts.ID as post_id
					FROM {$this->table->posts} posts
					CROSS JOIN ({$meta_keys_query}) as zones_query
					INNER JOIN {$this->table->postmeta} meta__price_method
						ON meta__price_method.post_id = posts.ID
						AND meta__price_method.meta_key = zones_query._price_method_field_name
					INNER JOIN {$this->table->postmeta} meta__sale_price_dates
						ON meta__sale_price_dates.post_id = posts.ID
						AND meta__sale_price_dates.meta_key = zones_query._sale_price_dates_field_name
					INNER JOIN {$this->table->postmeta} meta__sale_price_dates_to
						ON meta__sale_price_dates_to.post_id = posts.ID
						AND meta__sale_price_dates_to.meta_key = zones_query._sale_price_dates_to_field_name
					WHERE {$this->get_post_filter()}
					AND meta__price_method.meta_value = 'manual'
					AND meta__sale_price_dates.meta_value = 'manual'
					AND meta__sale_price_dates_to.meta_value < %s
					AND meta__sale_price_dates_to.meta_value > 0
					ORDER BY zones_query.zone_id, posts.ID",
					time()
				)
			);
		}

		$this->remove_sale_price( $rows );
	}
}
