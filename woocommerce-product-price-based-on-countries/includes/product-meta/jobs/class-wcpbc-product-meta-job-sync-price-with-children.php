<?php
/**
 * Sync product price with children.
 *
 * @since 4.0.0
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Custom_Table_Job_Add class.
 */
class WCPBC_Product_Meta_Job_Sync_Price_With_Children extends WCPBC_Product_Meta_Job {

	/**
	 * Returns the post query.
	 *
	 * @param array $parent_ids Parent IDs.
	 * @return array
	 */
	protected function get_post_query( $parent_ids = false ) {
		$query = [
			'from'  => "FROM `{$this->table->posts}` posts ",
			'where' => "WHERE posts.post_type = 'product_variation' AND posts.post_status = 'publish' ",
		];

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$query['from']  .= "INNER JOIN `{$this->table->prefix}wc_product_meta_lookup` product_meta_lookup ON product_meta_lookup.product_id = posts.ID ";
			$query['where'] .= "AND product_meta_lookup.stock_status <> 'outofstock' ";
		}

		$product_type_clauses = (
			new WP_Tax_Query(
				[
					[
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => wcpbc_wrapper_product_types(),
						'operator' => 'IN',
					],
				]
			)
		)->get_sql( 'posts', 'post_parent' );

		$query['from']  .= str_replace( 'LEFT', 'INNER', $product_type_clauses['join'] ) . ' ';
		$query['where'] .= $product_type_clauses['where'];

		if ( false !== $parent_ids ) {

			// Filter posts.
			$query['where'] .= $this->prepare_in( ' AND posts.post_parent IN (%d) ', $parent_ids );
		}

		return $query;
	}

	/**
	 * Runs the job.
	 */
	public function run_job() {
		if ( ! ( isset( $this->args['zone_id'] ) && is_array( $this->args['zone_id'] ) ) ) {
			return;
		}

		$zone_ids        = $this->args['zone_id'];
		$parent_ids      = isset( $this->args['parent_id'] ) && is_array( $this->args['parent_id'] ) && count( $this->args['parent_id'] ) ? $this->args['parent_id'] : false;
		$post_query      = $this->get_post_query( $parent_ids );
		$zones_metaquery = $this->get_zones_metaquery( [ '_price', '_price_method' ], $zone_ids );

		if ( ! $zones_metaquery ) {
			return;
		}

		$this->db()->query(
			"INSERT INTO `{$this->table->postmeta}` (post_id, meta_key, meta_value)
			SELECT DISTINCT posts.post_parent, zone_metaquery._price_method_field_name, 'manual'
			{$post_query['from']}
			CROSS JOIN ($zones_metaquery) as zone_metaquery
			{$post_query['where']} AND NOT EXISTS (
				SELECT 1 FROM `{$this->table->postmeta}`
				WHERE posts.post_parent = `{$this->table->postmeta}`.post_id
				AND zone_metaquery._price_method_field_name = `{$this->table->postmeta}`.meta_key)"
		);

		$this->db()->query(
			"UPDATE `{$this->table->postmeta}`
			SET meta_value = 'manual'
			WHERE `{$this->table->postmeta}`.meta_key IN ( SELECT _price_method_field_name FROM ($zones_metaquery) as zone_metaquery)
			AND EXISTS (SELECT 1 {$post_query['from']} {$post_query['where']} AND posts.post_parent = `{$this->table->postmeta}`.post_id)
			AND `{$this->table->postmeta}`.meta_value != 'manual'"
		);

		$this->db()->query(
			"DELETE FROM `{$this->table->postmeta}`
			WHERE `{$this->table->postmeta}`.meta_key IN ( SELECT _price_field_name FROM ($zones_metaquery) as zone_metaquery)
			AND EXISTS (SELECT 1 {$post_query['from']} {$post_query['where']} AND posts.post_parent = `{$this->table->postmeta}`.post_id)"
		);

		foreach ( [ 'MIN', 'MAX' ] as $min_or_max ) {
			$this->db()->query(
				"INSERT INTO `{$this->table->postmeta}` (post_id, meta_key, meta_value)
				SELECT posts.post_parent, zone_metaquery._price_field_name, {$min_or_max}(postmeta.meta_value +0)
				{$post_query['from']}
				CROSS JOIN ($zones_metaquery) as zone_metaquery
				INNER JOIN `{$this->table->postmeta}` postmeta ON postmeta.meta_key = zone_metaquery._price_field_name AND postmeta.post_id = posts.ID
				{$post_query['where']} AND postmeta.meta_value != ''
				GROUP BY posts.post_parent, zone_metaquery._price_field_name"
			);
		}
	}
}
