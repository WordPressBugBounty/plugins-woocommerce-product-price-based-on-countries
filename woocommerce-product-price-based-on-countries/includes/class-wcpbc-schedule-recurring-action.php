<?php
/**
 * Recurring actions handler. Schedule action that runs at specific time.
 * Do no requires removing the schedule on plugin deactivation.
 *
 * @since 4.3.0
 * @package WCPBC
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_Schedule_Recurring_Action Class
 */
class WCPBC_Schedule_Recurring_Action {

	/**
	 * Recurring actions.
	 *
	 * @var array
	 */
	private static $data = [];

	/**
	 * Add an action to run recurring.
	 *
	 * @param string $hook Name of the action hook.
	 * @param string $datetime Relative date Date format as (+3 day, tomorrow, +1 week, tomorrow 2:00 am).
	 */
	public static function add( $hook, $datetime ) {
		self::$data[ $hook ] = $datetime;

		add_action( $hook, [ __CLASS__, 'schedule_again' ], 9999 );

		if ( ! has_action( 'admin_init', [ __CLASS__, 'install' ] ) ) {
			add_action( 'admin_init', [ __CLASS__, 'install' ] );
		}
	}

	/**
	 * Schedule the actions.
	 */
	public static function install() {
		if ( wp_doing_ajax() || wp_cache_get( __METHOD__ ) ) {
			return;
		}

		foreach ( self::$data as $hook => $datetime ) {
			self::schedule_action( $hook, $datetime );
		}

		wp_cache_set( __METHOD__, true, '', HOUR_IN_SECONDS );
	}

	/**
	 * Schedule the action on shutdown.
	 */
	public static function schedule_again() {
		$hook = current_action();
		if ( empty( self::$data[ $hook ] ) ) {
			return;
		}
		self::schedule_action( $hook, self::$data[ $hook ] );
	}

	/**
	 * Schedule an action hook.
	 *
	 * @param string $hook Name of the action hook.
	 * @param string $datetime Relative date format (+3 day, tomorrow, +1 week, tomorrow 2:00 am).
	 */
	private static function schedule_action( $hook, $datetime ) {
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return;
		}

		$timestamp = 0;

		try {
			$timestamp = ( new DateTime( $datetime, wp_timezone() ) )->format( 'U' );
		} catch ( \Exception $e ) {
			WCPBC_Debug_Logger::log_error( $e->getMessage(), __METHOD__ );
		}

		if ( $timestamp > time() ) {

			$group   = strtolower( __CLASS__ );
			$actions = as_get_scheduled_actions(
				[
					'hook'     => $hook,
					'group'    => $group,
					'status'   => ActionScheduler_Store::STATUS_PENDING,
					'per_page' => 1,
				]
			);

			if ( ! empty( $actions ) ) {
				return;
			}

			as_schedule_single_action( $timestamp, $hook, [], $group, false );
		}
	}
}
