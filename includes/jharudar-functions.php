<?php
/**
 * Jharudar helper functions.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'jharudar' ) ) {
	/**
	 * Get the main Jharudar instance.
	 *
	 * This function is defined in class-jharudar.php but included here for reference.
	 *
	 * @since 0.0.1
	 * @return Jharudar
	 */
	function jharudar() {
		return Jharudar::instance();
	}
}

/**
 * Get a Jharudar setting.
 *
 * @since 0.0.1
 * @param string $key           Setting key.
 * @param mixed  $default_value Default value.
 * @return mixed Setting value.
 */
function jharudar_get_setting( $key, $default_value = null ) {
	return jharudar()->get_setting( $key, $default_value );
}

/**
 * Log an activity.
 *
 * @since 0.0.1
 * @param string $action      Action performed.
 * @param string $object_type Object type.
 * @param int    $object_id   Object ID.
 * @param array  $meta        Additional metadata.
 * @return bool True on success.
 */
function jharudar_log_activity( $action, $object_type = '', $object_id = 0, $meta = array() ) {
	if ( ! jharudar_get_setting( 'enable_activity_log', true ) ) {
		return false;
	}

	$logger = new Jharudar_Logger();
	return $logger->log( $action, $object_type, $object_id, $meta );
}

/**
 * Check if WooCommerce is active.
 *
 * @since 0.0.1
 * @return bool True if WooCommerce is active.
 */
function jharudar_is_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * Check if a WooCommerce extension is active.
 *
 * @since 0.0.1
 * @param string $extension Extension name.
 * @return bool True if active.
 */
function jharudar_is_extension_active( $extension ) {
	return jharudar()->is_extension_active( $extension );
}

/**
 * Get formatted date.
 *
 * @since 0.0.1
 * @param string|int $date   Date string or timestamp.
 * @param string     $format Date format.
 * @return string Formatted date.
 */
function jharudar_format_date( $date, $format = '' ) {
	if ( empty( $format ) ) {
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	}

	if ( is_numeric( $date ) ) {
		$timestamp = (int) $date;
	} else {
		$timestamp = strtotime( $date );
	}

	if ( ! $timestamp ) {
		return '';
	}

	return date_i18n( $format, $timestamp );
}

/**
 * Format file size.
 *
 * @since 0.0.1
 * @param int $bytes Size in bytes.
 * @return string Formatted size.
 */
function jharudar_format_bytes( $bytes ) {
	$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

	$bytes  = max( $bytes, 0 );
	$pow    = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
	$pow    = min( $pow, count( $units ) - 1 );
	$bytes /= pow( 1024, $pow );

	return round( $bytes, 2 ) . ' ' . $units[ $pow ];
}

/**
 * Get order statuses for filtering.
 *
 * @since 0.0.1
 * @return array Order statuses.
 */
function jharudar_get_order_statuses() {
	if ( ! function_exists( 'wc_get_order_statuses' ) ) {
		return array();
	}
	return wc_get_order_statuses();
}

/**
 * Get product types for filtering.
 *
 * @since 0.0.1
 * @return array Product types.
 */
function jharudar_get_product_types() {
	if ( ! function_exists( 'wc_get_product_types' ) ) {
		return array();
	}
	return wc_get_product_types();
}

/**
 * Get product categories.
 *
 * @since 0.0.1
 * @param array $args Term query args.
 * @return array Categories.
 */
function jharudar_get_product_categories( $args = array() ) {
	$defaults = array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	);

	$args  = wp_parse_args( $args, $defaults );
	$terms = get_terms( $args );

	if ( is_wp_error( $terms ) ) {
		return array();
	}

	return $terms;
}

/**
 * Check if user has permission to use Jharudar.
 *
 * @since 0.0.1
 * @return bool True if user has permission.
 */
function jharudar_user_can_manage() {
	return current_user_can( 'manage_woocommerce' );
}

/**
 * Sanitize array of IDs.
 *
 * @since 0.0.1
 * @param array $ids Array of IDs.
 * @return array Sanitized IDs.
 */
function jharudar_sanitize_ids( $ids ) {
	if ( ! is_array( $ids ) ) {
		return array();
	}

	return array_map( 'absint', array_filter( $ids ) );
}

/**
 * Get batch size setting.
 *
 * @since 0.0.1
 * @return int Batch size.
 */
function jharudar_get_batch_size() {
	$batch_size = jharudar_get_setting( 'batch_size', 50 );
	return max( 10, min( 500, (int) $batch_size ) );
}

/**
 * Create nonce for AJAX actions.
 *
 * @since 0.0.1
 * @param string $action Action name.
 * @return string Nonce.
 */
function jharudar_create_nonce( $action = 'jharudar_admin_nonce' ) {
	return wp_create_nonce( $action );
}

/**
 * Verify nonce for AJAX actions.
 *
 * @since 0.0.1
 * @param string $nonce  Nonce to verify.
 * @param string $action Action name.
 * @return bool True if valid.
 */
function jharudar_verify_nonce( $nonce, $action = 'jharudar_admin_nonce' ) {
	return wp_verify_nonce( $nonce, $action );
}

/**
 * Get admin URL for Jharudar pages.
 *
 * @since 0.0.1
 * @param string $tab  Tab name.
 * @param array  $args Additional query args.
 * @return string Admin URL.
 */
function jharudar_admin_url( $tab = '', $args = array() ) {
	$url = admin_url( 'admin.php?page=jharudar' );

	if ( ! empty( $tab ) ) {
		$url = add_query_arg( 'tab', $tab, $url );
	}

	if ( ! empty( $args ) ) {
		$url = add_query_arg( $args, $url );
	}

	return $url;
}

/**
 * Check if HPOS is enabled.
 *
 * @since 0.0.1
 * @return bool True if HPOS is enabled.
 */
function jharudar_is_hpos_enabled() {
	if ( ! class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
		return false;
	}

	return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
}

/**
 * Get the orders table name.
 *
 * @since 0.0.1
 * @return string Table name.
 */
function jharudar_get_orders_table() {
	global $wpdb;

	if ( jharudar_is_hpos_enabled() ) {
		return $wpdb->prefix . 'wc_orders';
	}

	return $wpdb->posts;
}

/**
 * Get subscription statuses if Subscriptions is active.
 *
 * @since 0.0.1
 * @return array Subscription statuses.
 */
function jharudar_get_subscription_statuses() {
	if ( ! function_exists( 'wcs_get_subscription_statuses' ) ) {
		return array();
	}
	return wcs_get_subscription_statuses();
}

/**
 * Get membership statuses if Memberships is active.
 *
 * @since 0.0.1
 * @return array Membership statuses.
 */
function jharudar_get_membership_statuses() {
	if ( ! function_exists( 'wc_memberships_get_user_membership_statuses' ) ) {
		return array();
	}
	return wc_memberships_get_user_membership_statuses();
}

/**
 * Get booking statuses if Bookings is active.
 *
 * @since 0.0.1
 * @return array Booking statuses.
 */
function jharudar_get_booking_statuses() {
	if ( ! function_exists( 'get_wc_booking_statuses' ) ) {
		return array();
	}
	return get_wc_booking_statuses();
}
