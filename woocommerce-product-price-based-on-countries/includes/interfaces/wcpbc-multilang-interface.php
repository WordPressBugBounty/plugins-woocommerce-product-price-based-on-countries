<?php
/**
 * Multilang Interface
 *
 * @version 3.0.0
 * @package WCPBC\Interface
 */

/**
 * WCPBC_Multilang_Interface
 */
interface WCPBC_Multilang_Interface {

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	public static function instance();

	/**
	 * Syncs a queue
	 *
	 * @param array $queue Array of post_id => zones to sync with the translations.
	 */
	public function sync_queue( $queue );
}
