<?php
/**
 * Uninstall Jharudar for WooCommerce.
 *
 * This file runs when the plugin is uninstalled (deleted) from WordPress.
 * It removes all plugin data from the database if the user has opted to do so.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly or not uninstalling.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check user capability.
if ( ! current_user_can( 'activate_plugins' ) ) {
	exit;
}

// Get plugin settings.
$jharudar_settings = get_option( 'jharudar_settings', array() );

// Check if user wants to delete all data on uninstall.
$jharudar_delete_data = isset( $jharudar_settings['delete_data_on_uninstall'] ) && $jharudar_settings['delete_data_on_uninstall'];

if ( $jharudar_delete_data ) {
	// Delete plugin options.
	delete_option( 'jharudar_settings' );
	delete_option( 'jharudar_version' );
	delete_option( 'jharudar_db_version' );
	delete_option( 'jharudar_installed' );

	// Delete all transients.
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup, caching not applicable.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'%_transient_jharudar_%',
			'%_transient_timeout_jharudar_%'
		)
	);

	// Delete activity log entries.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup, caching not applicable.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'jharudar_activity_log_%'
		)
	);

	// Delete scheduled actions.
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( 'jharudar_scheduled_cleanup' );
		as_unschedule_all_actions( 'jharudar_process_batch' );
	}

	// Delete user meta.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup, caching not applicable.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
			'jharudar_%'
		)
	);

	// Clear any remaining caches.
	wp_cache_flush();
}
