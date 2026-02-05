<?php
/**
 * Plugin deactivator.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin deactivator class.
 *
 * Handles tasks that need to run on plugin deactivation.
 *
 * @since 0.0.1
 */
class Jharudar_Deactivator {

	/**
	 * Run deactivation tasks.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public static function deactivate() {
		// Unschedule all plugin events.
		self::unschedule_events();

		// Clear transients.
		self::clear_transients();

		/**
		 * Fires after Jharudar has been deactivated.
		 *
		 * @since 0.0.1
		 */
		do_action( 'jharudar_deactivated' );
	}

	/**
	 * Unschedule all plugin events.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private static function unschedule_events() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'jharudar_daily_maintenance' );
			as_unschedule_all_actions( 'jharudar_scheduled_cleanup' );
			as_unschedule_all_actions( 'jharudar_process_batch' );
		}
	}

	/**
	 * Clear plugin transients.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private static function clear_transients() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup operation, caching not applicable.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'%_transient_jharudar_%',
				'%_transient_timeout_jharudar_%'
			)
		);
	}
}
