<?php
/**
 * Tax Rates module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tax Rates module class.
 *
 * Handles tax rate cleanup operations.
 *
 * @since 0.0.1
 */
class Jharudar_Tax_Rates {

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'wp_ajax_jharudar_get_tax_rates', array( $this, 'ajax_get_tax_rates' ) );
		add_action( 'wp_ajax_jharudar_delete_tax_rates', array( $this, 'ajax_delete_tax_rates' ) );
		add_action( 'wp_ajax_jharudar_export_tax_rates', array( $this, 'ajax_export_tax_rates' ) );
		add_action( 'wp_ajax_jharudar_get_tax_stats', array( $this, 'ajax_get_tax_stats' ) );
	}

	/**
	 * Get tax rates based on filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Tax rates data.
	 */
	public function get_tax_rates( $filters = array() ) {
		global $wpdb;

		$defaults = array(
			'country'   => '',
			'tax_class' => '',
			'limit'     => 50,
			'offset'    => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		// Sanitize filter values.
		$country   = ! empty( $filters['country'] ) ? sanitize_text_field( $filters['country'] ) : '';
		$tax_class = ! empty( $filters['tax_class'] ) ? sanitize_text_field( $filters['tax_class'] ) : '';
		$offset    = (int) $filters['offset'];
		$limit     = (int) $filters['limit'];

		// WooCommerce doesn't provide an API for tax rates, so we must query the table directly.
		// All user input is sanitized above and properly prepared in queries below.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching

		// Build query based on filter combinations.
		if ( ! empty( $country ) && ! empty( $tax_class ) ) {
			// Both country and tax class filters.
			if ( 'standard' === $tax_class ) {
				$total     = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = %s AND tax_rate_class = ''",
						$country
					)
				);
				$tax_rates = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = %s AND tax_rate_class = '' ORDER BY tax_rate_order ASC LIMIT %d OFFSET %d",
						$country,
						$limit,
						$offset
					)
				);
			} else {
				$total     = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = %s AND tax_rate_class = %s",
						$country,
						$tax_class
					)
				);
				$tax_rates = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = %s AND tax_rate_class = %s ORDER BY tax_rate_order ASC LIMIT %d OFFSET %d",
						$country,
						$tax_class,
						$limit,
						$offset
					)
				);
			}
		} elseif ( ! empty( $country ) ) {
			// Only country filter.
			$total     = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = %s",
					$country
				)
			);
			$tax_rates = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = %s ORDER BY tax_rate_order ASC LIMIT %d OFFSET %d",
					$country,
					$limit,
					$offset
				)
			);
		} elseif ( ! empty( $tax_class ) ) {
			// Only tax class filter.
			if ( 'standard' === $tax_class ) {
				$total     = $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_class = ''"
				);
				$tax_rates = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_class = '' ORDER BY tax_rate_order ASC LIMIT %d OFFSET %d",
						$limit,
						$offset
					)
				);
			} else {
				$total     = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_class = %s",
						$tax_class
					)
				);
				$tax_rates = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_class = %s ORDER BY tax_rate_order ASC LIMIT %d OFFSET %d",
						$tax_class,
						$limit,
						$offset
					)
				);
			}
		} else {
			// No filters.
			$total     = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates"
			);
			$tax_rates = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates ORDER BY tax_rate_order ASC LIMIT %d OFFSET %d",
					$limit,
					$offset
				)
			);
		}

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching

		$formatted_rates = array();

		foreach ( $tax_rates as $rate ) {
			$formatted_rates[] = $this->format_tax_rate_data( $rate );
		}

		return array(
			'tax_rates' => $formatted_rates,
			'total'     => (int) $total,
		);
	}

	/**
	 * Format tax rate data for display.
	 *
	 * @since 0.0.1
	 * @param object $rate Tax rate object.
	 * @return array Formatted tax rate data.
	 */
	private function format_tax_rate_data( $rate ) {
		$tax_classes   = WC_Tax::get_tax_classes();
		$tax_class_map = array( '' => __( 'Standard', 'jharudar-for-woocommerce' ) );
		foreach ( $tax_classes as $class ) {
			$tax_class_map[ sanitize_title( $class ) ] = $class;
		}

		return array(
			'id'        => $rate->tax_rate_id,
			'country'   => $rate->tax_rate_country ? $rate->tax_rate_country : '*',
			'state'     => $rate->tax_rate_state ? $rate->tax_rate_state : '*',
			'postcode'  => $this->get_tax_rate_locations( $rate->tax_rate_id, 'postcode' ),
			'city'      => $this->get_tax_rate_locations( $rate->tax_rate_id, 'city' ),
			'rate'      => $rate->tax_rate,
			'name'      => $rate->tax_rate_name,
			'priority'  => $rate->tax_rate_priority,
			'compound'  => $rate->tax_rate_compound ? __( 'Yes', 'jharudar-for-woocommerce' ) : __( 'No', 'jharudar-for-woocommerce' ),
			'shipping'  => $rate->tax_rate_shipping ? __( 'Yes', 'jharudar-for-woocommerce' ) : __( 'No', 'jharudar-for-woocommerce' ),
			'tax_class' => isset( $tax_class_map[ $rate->tax_rate_class ] ) ? $tax_class_map[ $rate->tax_rate_class ] : $rate->tax_rate_class,
		);
	}

	/**
	 * Get tax rate locations (postcodes or cities).
	 *
	 * @since 0.0.1
	 * @param int    $rate_id       Tax rate ID.
	 * @param string $location_type Location type (postcode or city).
	 * @return string Locations.
	 */
	private function get_tax_rate_locations( $rate_id, $location_type ) {
		global $wpdb;

		$cache_key = 'jharudar_tax_locations_' . $rate_id . '_' . $location_type;
		$locations = wp_cache_get( $cache_key, 'jharudar' );

		if ( false === $locations ) {
			// WooCommerce tax rate locations have no API, must use direct query.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
			$locations = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = %d AND location_type = %s",
					$rate_id,
					$location_type
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
			wp_cache_set( $cache_key, $locations, 'jharudar', HOUR_IN_SECONDS );
		}

		if ( empty( $locations ) ) {
			return '*';
		}

		$display = implode( '; ', array_slice( $locations, 0, 3 ) );
		if ( count( $locations ) > 3 ) {
			/* translators: %d: number of additional locations */
			$display .= sprintf( __( ' +%d more', 'jharudar-for-woocommerce' ), count( $locations ) - 3 );
		}

		return $display;
	}

	/**
	 * Delete tax rates.
	 *
	 * @since 0.0.1
	 * @param array $rate_ids Tax rate IDs to delete.
	 * @return array Result data.
	 */
	public function delete_tax_rates( $rate_ids ) {
		global $wpdb;

		$rate_ids   = jharudar_sanitize_ids( $rate_ids );
		$batch_size = jharudar_get_batch_size();
		$deleted    = 0;
		$failed     = 0;

		// If batch processing needed.
		if ( count( $rate_ids ) > $batch_size ) {
			$this->schedule_batch_delete( $rate_ids );
			return array(
				'scheduled' => true,
				'total'     => count( $rate_ids ),
				'message'   => __( 'Tax rates are being deleted in the background.', 'jharudar-for-woocommerce' ),
			);
		}

		foreach ( $rate_ids as $rate_id ) {
			// Clear location caches for this rate.
			wp_cache_delete( 'jharudar_tax_locations_' . $rate_id . '_postcode', 'jharudar' );
			wp_cache_delete( 'jharudar_tax_locations_' . $rate_id . '_city', 'jharudar' );

			// WooCommerce tax rates have no delete API, must use direct query.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->delete(
				$wpdb->prefix . 'woocommerce_tax_rate_locations',
				array( 'tax_rate_id' => $rate_id ),
				array( '%d' )
			);

			$result = $wpdb->delete(
				$wpdb->prefix . 'woocommerce_tax_rates',
				array( 'tax_rate_id' => $rate_id ),
				array( '%d' )
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

			if ( $result ) {
				jharudar_log_activity( 'delete', 'tax_rate', $rate_id );
				++$deleted;
			} else {
				++$failed;
			}
		}

		// Clear all tax rate caches.
		wp_cache_delete( 'jharudar_tax_stats', 'jharudar' );
		wp_cache_delete( 'jharudar_tax_countries', 'jharudar' );
		WC_Cache_Helper::invalidate_cache_group( 'taxes' );

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
		);
	}

	/**
	 * Schedule batch delete using Action Scheduler.
	 *
	 * @since 0.0.1
	 * @param array $rate_ids Tax rate IDs.
	 * @return void
	 */
	private function schedule_batch_delete( $rate_ids ) {
		$batch_size = jharudar_get_batch_size();
		$batches    = array_chunk( $rate_ids, $batch_size );

		foreach ( $batches as $index => $batch ) {
			as_schedule_single_action(
				time() + ( $index * 30 ),
				'jharudar_delete_tax_rates_batch',
				array(
					'rate_ids' => $batch,
				),
				'jharudar'
			);
		}
	}

	/**
	 * Get tax rate statistics.
	 *
	 * @since 0.0.1
	 * @return array Stats data.
	 */
	public static function get_statistics() {
		global $wpdb;

		$cache_key = 'jharudar_tax_stats';
		$stats     = wp_cache_get( $cache_key, 'jharudar' );

		if ( false === $stats ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- WooCommerce tax rates have no statistics API.

			// Total tax rates.
			$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates" );

			// Tax rates by country.
			$countries = $wpdb->get_var(
				"SELECT COUNT(DISTINCT tax_rate_country) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country != ''"
			);

			// Tax classes with rates.
			$classes = $wpdb->get_var(
				"SELECT COUNT(DISTINCT tax_rate_class) FROM {$wpdb->prefix}woocommerce_tax_rates"
			);

			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

			$stats = array(
				'total'     => (int) $total,
				'countries' => (int) $countries,
				'classes'   => (int) $classes,
			);

			wp_cache_set( $cache_key, $stats, 'jharudar', HOUR_IN_SECONDS );
		}

		return $stats;
	}

	/**
	 * Get available countries from tax rates.
	 *
	 * @since 0.0.1
	 * @return array Countries.
	 */
	public function get_countries() {
		global $wpdb;

		$cache_key = 'jharudar_tax_countries';
		$result    = wp_cache_get( $cache_key, 'jharudar' );

		if ( false === $result ) {
			// WooCommerce tax rates have no API for country list, must use direct query.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
			$countries = $wpdb->get_col(
				"SELECT DISTINCT tax_rate_country FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country != '' ORDER BY tax_rate_country ASC"
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

			$wc_countries = WC()->countries->get_countries();
			$result       = array();

			foreach ( $countries as $code ) {
				$result[ $code ] = isset( $wc_countries[ $code ] ) ? $wc_countries[ $code ] : $code;
			}

			wp_cache_set( $cache_key, $result, 'jharudar', HOUR_IN_SECONDS );
		}

		return $result;
	}

	/**
	 * Get available tax classes.
	 *
	 * @since 0.0.1
	 * @return array Tax classes.
	 */
	public function get_tax_classes() {
		$classes = WC_Tax::get_tax_classes();
		$result  = array(
			'standard' => __( 'Standard', 'jharudar-for-woocommerce' ),
		);

		foreach ( $classes as $class ) {
			$result[ sanitize_title( $class ) ] = $class;
		}

		return $result;
	}

	/**
	 * AJAX handler: Get tax rates.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_tax_rates() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'country'   => isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '',
			'tax_class' => isset( $_POST['tax_class'] ) ? sanitize_key( $_POST['tax_class'] ) : '',
			'limit'     => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'    => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_tax_rates( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete tax rates.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_tax_rates() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$rate_ids = isset( $_POST['rate_ids'] ) ? array_map( 'absint', (array) $_POST['rate_ids'] ) : array();

		if ( empty( $rate_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No tax rates selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->delete_tax_rates( $rate_ids );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Export tax rates.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_export_tax_rates() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$rate_ids = isset( $_POST['rate_ids'] ) ? array_map( 'absint', (array) $_POST['rate_ids'] ) : array();
		$format   = isset( $_POST['format'] ) ? sanitize_key( $_POST['format'] ) : 'csv';

		if ( empty( $rate_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No tax rates selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$exporter = new Jharudar_Exporter( $format );
		$filepath = $exporter->export_tax_rates( $rate_ids )->save();

		if ( $filepath ) {
			$upload_dir = wp_upload_dir();
			$file_url   = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $filepath );

			wp_send_json_success(
				array(
					'file_url'  => $file_url,
					'file_path' => $filepath,
					'message'   => __( 'Export completed successfully.', 'jharudar-for-woocommerce' ),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Export failed.', 'jharudar-for-woocommerce' ) ) );
		}
	}

	/**
	 * AJAX handler: Get tax stats.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_tax_stats() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$stats = $this->get_stats();
		wp_send_json_success( $stats );
	}
}
