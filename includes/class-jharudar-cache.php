<?php
/**
 * Cache helper class for Jharudar.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache helper class.
 *
 * Provides transient caching for stats and data.
 *
 * @since 0.0.1
 */
class Jharudar_Cache {

	/**
	 * Cache prefix.
	 *
	 * @var string
	 */
	const CACHE_PREFIX = 'jharudar_';

	/**
	 * Default cache expiration in seconds (15 minutes).
	 *
	 * @var int
	 */
	const DEFAULT_EXPIRATION = 900;

	/**
	 * Cache keys that can be cleared.
	 *
	 * @var array
	 */
	private static $cache_keys = array(
		'coupon_stats',
		'taxonomy_stats',
		'store_stats',
		'tax_rates_stats',
		'shipping_stats',
	);

	/**
	 * Initialize cache hooks.
	 *
	 * @since 0.0.1
	 */
	public static function init() {
		// AJAX handler for clearing cache.
		add_action( 'wp_ajax_jharudar_clear_cache', array( __CLASS__, 'ajax_clear_cache' ) );

		// Auto-clear cache on relevant actions.
		add_action( 'woocommerce_coupon_options_save', array( __CLASS__, 'clear_coupon_cache' ) );
		add_action( 'woocommerce_delete_coupon', array( __CLASS__, 'clear_coupon_cache' ) );
		add_action( 'created_term', array( __CLASS__, 'clear_taxonomy_cache' ) );
		add_action( 'delete_term', array( __CLASS__, 'clear_taxonomy_cache' ) );
		add_action( 'woocommerce_attribute_added', array( __CLASS__, 'clear_taxonomy_cache' ) );
		add_action( 'woocommerce_attribute_deleted', array( __CLASS__, 'clear_taxonomy_cache' ) );
		add_action( 'woocommerce_new_order', array( __CLASS__, 'clear_store_cache' ) );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'clear_store_cache' ) );
	}

	/**
	 * Get cached value.
	 *
	 * @since 0.0.1
	 * @param string $key Cache key (without prefix).
	 * @return mixed|false Cached value or false if not found.
	 */
	public static function get( $key ) {
		return get_transient( self::CACHE_PREFIX . $key );
	}

	/**
	 * Set cached value.
	 *
	 * @since 0.0.1
	 * @param string $key        Cache key (without prefix).
	 * @param mixed  $value      Value to cache.
	 * @param int    $expiration Optional. Expiration in seconds. Default 15 minutes.
	 * @return bool True if successful.
	 */
	public static function set( $key, $value, $expiration = self::DEFAULT_EXPIRATION ) {
		return set_transient( self::CACHE_PREFIX . $key, $value, $expiration );
	}

	/**
	 * Delete cached value.
	 *
	 * @since 0.0.1
	 * @param string $key Cache key (without prefix).
	 * @return bool True if successful.
	 */
	public static function delete( $key ) {
		return delete_transient( self::CACHE_PREFIX . $key );
	}

	/**
	 * Clear all Jharudar caches.
	 *
	 * @since 0.0.1
	 * @return int Number of caches cleared.
	 */
	public static function clear_all() {
		$cleared = 0;

		foreach ( self::$cache_keys as $key ) {
			if ( self::delete( $key ) ) {
				++$cleared;
			}
		}

		/**
		 * Fires after all caches are cleared.
		 *
		 * @since 0.0.1
		 * @param int $cleared Number of caches cleared.
		 */
		do_action( 'jharudar_cache_cleared', $cleared );

		return $cleared;
	}

	/**
	 * Clear coupon-related cache.
	 *
	 * @since 0.0.1
	 */
	public static function clear_coupon_cache() {
		self::delete( 'coupon_stats' );
	}

	/**
	 * Clear taxonomy-related cache.
	 *
	 * @since 0.0.1
	 */
	public static function clear_taxonomy_cache() {
		self::delete( 'taxonomy_stats' );
	}

	/**
	 * Clear store-related cache.
	 *
	 * @since 0.0.1
	 */
	public static function clear_store_cache() {
		self::delete( 'store_stats' );
	}

	/**
	 * AJAX handler for clearing cache.
	 *
	 * @since 0.0.1
	 */
	public static function ajax_clear_cache() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'jharudar_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'jharudar-for-woocommerce' ) ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$cleared = self::clear_all();

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of caches cleared */
					__( 'Cache cleared successfully. %d cache entries removed.', 'jharudar-for-woocommerce' ),
					$cleared
				),
			)
		);
	}

	/**
	 * Get cache status for display.
	 *
	 * @since 0.0.1
	 * @return array Array of cache status info.
	 */
	public static function get_cache_status() {
		$status = array();

		foreach ( self::$cache_keys as $key ) {
			$value          = self::get( $key );
			$status[ $key ] = array(
				'exists' => false !== $value,
				'key'    => $key,
			);
		}

		return $status;
	}
}

// Initialize cache hooks.
Jharudar_Cache::init();
