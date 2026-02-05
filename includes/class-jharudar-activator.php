<?php
/**
 * Plugin activator.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin activator class.
 *
 * Handles tasks that need to run on plugin activation.
 *
 * @since 0.0.1
 */
class Jharudar_Activator {

	/**
	 * Run activation tasks.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public static function activate() {
		// Set installation timestamp.
		if ( ! get_option( 'jharudar_installed' ) ) {
			update_option( 'jharudar_installed', time() );
		}

		// Set version.
		update_option( 'jharudar_version', JHARUDAR_VERSION );

		// Initialize default settings if not set.
		self::init_default_settings();

		// Set transient to show welcome notice.
		set_transient( 'jharudar_activated', true, 30 );

		// Schedule cleanup tasks.
		self::schedule_events();

		// Clear any cached data.
		wp_cache_flush();

		/**
		 * Fires after Jharudar has been activated.
		 *
		 * @since 0.0.1
		 */
		do_action( 'jharudar_activated' );
	}

	/**
	 * Initialize default settings.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private static function init_default_settings() {
		$existing_settings = get_option( 'jharudar_settings', array() );

		// Only set defaults if no settings exist.
		if ( ! empty( $existing_settings ) ) {
			return;
		}

		$default_settings = array(
			'delete_data_on_uninstall' => false,
			'enable_activity_log'      => true,
			'log_retention_days'       => 30,
			'batch_size'               => 50,
			'require_confirmation'     => true,
			'require_export'           => false,
			'email_notifications'      => false,
			'notification_email'       => get_option( 'admin_email' ),
		);

		update_option( 'jharudar_settings', $default_settings );
	}

	/**
	 * Schedule recurring events.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private static function schedule_events() {
		// Schedule daily cleanup of activity log.
		if ( function_exists( 'as_has_scheduled_action' ) && ! as_has_scheduled_action( 'jharudar_daily_maintenance' ) ) {
			as_schedule_recurring_action(
				strtotime( 'tomorrow 3:00 am' ),
				DAY_IN_SECONDS,
				'jharudar_daily_maintenance',
				array(),
				'jharudar'
			);
		}
	}
}
