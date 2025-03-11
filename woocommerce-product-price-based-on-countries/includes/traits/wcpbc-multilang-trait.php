<?php
/**
 * Multilang functions.
 *
 * @since 4.0.0
 * @package WCPBC/Trait
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Multilang_Trait Trait
 */
trait WCPBC_Multilang_Trait {

	/**
	 * The single instance of the class.
	 *
	 * @var object;
	 */
	private static $instance;

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 */
	final public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'woocommerce' ), '4.6' );
		die();
	}

	/**
	 * Enqueue a post ID for multilang sync.
	 *
	 * @param int $post_id Post ID.
	 */
	protected function enqueue_sync( $post_id ) {
		foreach ( array_keys( WCPBC_Pricing_Zones::get_zones() ) as $zone_id ) {
			WCPBC_Product_Meta_Data::maybe_enqueue_multilang_sync( $post_id, $zone_id );
		}
	}

	/**
	 * Returns the translation post IDs for the give post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	protected function get_translations( $post_id ) {
		return [];
	}

	/**
	 * Should copy metadata?
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	protected function should_copy_meta( $post_id ) {
		return true;
	}

	/**
	 * Syncs the product meta with the translations.
	 *
	 * @param int                $post_id Post ID.
	 * @param WCPBC_Pricing_Zone $zone Pricing zone instance.
	 */
	protected function sync_metadata( $post_id, $zone ) {

		$metadata = $zone->get_postmeta( $post_id );

		foreach ( $this->get_translations( $post_id ) as $tr_post_id ) {

			$tr_metakeys = array_keys( $zone->get_postmeta( $tr_post_id ) );

			foreach ( $tr_metakeys as $meta_key ) {

				if ( ! isset( $metadata[ $meta_key ] ) ) {
					$zone->delete_postmeta( $tr_post_id, $meta_key );
				}
			}

			foreach ( $metadata as $meta_key => $meta_value ) {
				$zone->set_postmeta( $tr_post_id, $meta_key, $meta_value );
			}
		}
	}

	/**
	 * Syncs a queue
	 *
	 * @param array $queue Array of post_id => zones to sync with the translations.
	 */
	public function sync_queue( $queue ) {

		$zones = WCPBC_Pricing_Zones::get_zones();

		foreach ( $queue as $post_id => $zone_ids ) {
			if ( ! $this->should_copy_meta( $post_id ) ) {
				continue;
			}

			foreach ( $zone_ids as $id ) {
				if ( ! isset( $zones[ $id ] ) ) {
					continue;
				}

				$this->sync_metadata( $post_id, $zones[ $id ] );
			}
		}
	}
}

