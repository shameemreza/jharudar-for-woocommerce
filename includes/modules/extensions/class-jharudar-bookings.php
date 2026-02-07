<?php
/**
 * Bookings module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bookings module class.
 *
 * Handles WooCommerce Bookings cleanup operations.
 * Requires WooCommerce Bookings plugin to be active.
 *
 * @since 0.0.1
 */
class Jharudar_Bookings {

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
		add_action( 'wp_ajax_jharudar_get_bookings', array( $this, 'ajax_get_bookings' ) );
		add_action( 'wp_ajax_jharudar_delete_bookings', array( $this, 'ajax_delete_bookings' ) );
		add_action( 'wp_ajax_jharudar_export_bookings', array( $this, 'ajax_export_bookings' ) );
		add_action( 'wp_ajax_jharudar_get_booking_stats', array( $this, 'ajax_get_booking_stats' ) );
		add_action( 'wp_ajax_jharudar_empty_trash_bookings', array( $this, 'ajax_empty_trash' ) );
	}

	/**
	 * Check if WooCommerce Bookings is active.
	 *
	 * @since 0.0.1
	 * @return bool True if active.
	 */
	public static function is_active() {
		return class_exists( 'WC_Bookings' );
	}

	/**
	 * Get bookings based on filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Bookings data.
	 */
	public function get_bookings( $filters = array() ) {
		if ( ! self::is_active() ) {
			return array(
				'bookings' => array(),
				'total'    => 0,
			);
		}

		$defaults = array(
			'status'      => '',
			'date_before' => '',
			'date_after'  => '',
			'past_only'   => false,
			'limit'       => 50,
			'offset'      => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'post_type'      => 'wc_booking',
			'posts_per_page' => $filters['limit'],
			'offset'         => $filters['offset'],
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_status'    => 'any',
		);

		// Filter by status.
		if ( ! empty( $filters['status'] ) ) {
			$args['post_status'] = sanitize_key( $filters['status'] );
		}

		// Filter by date.
		if ( ! empty( $filters['date_before'] ) || ! empty( $filters['date_after'] ) ) {
			$date_query = array();

			if ( ! empty( $filters['date_before'] ) ) {
				$date_query['before'] = sanitize_text_field( $filters['date_before'] );
			}

			if ( ! empty( $filters['date_after'] ) ) {
				$date_query['after'] = sanitize_text_field( $filters['date_after'] );
			}

			$args['date_query'] = array( $date_query );
		}

		$query    = new WP_Query( $args );
		$bookings = array();

		foreach ( $query->posts as $post ) {
			$booking = get_wc_booking( $post->ID );

			if ( ! $booking ) {
				continue;
			}

			// Filter by past bookings using raw timestamp for reliable comparison.
			if ( $filters['past_only'] ) {
				$end_timestamp = $booking->get_end();
				if ( $end_timestamp && $end_timestamp > time() ) {
					continue;
				}
			}

			$bookings[] = $this->format_booking_data( $booking );
		}

		// Use found_posts from the query to avoid a separate count query.
		$total = $query->found_posts;

		// For past_only, the total must be recalculated since we filter in PHP.
		if ( $filters['past_only'] ) {
			$total = $this->count_bookings( $filters );
		}

		return array(
			'bookings' => $bookings,
			'total'    => $total,
		);
	}

	/**
	 * Count bookings matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Booking count.
	 */
	public function count_bookings( $filters = array() ) {
		if ( ! self::is_active() ) {
			return 0;
		}

		$args = array(
			'post_type'      => 'wc_booking',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		);

		if ( ! empty( $filters['status'] ) ) {
			$args['post_status'] = sanitize_key( $filters['status'] );
		}

		if ( ! empty( $filters['date_before'] ) || ! empty( $filters['date_after'] ) ) {
			$date_query = array();

			if ( ! empty( $filters['date_before'] ) ) {
				$date_query['before'] = sanitize_text_field( $filters['date_before'] );
			}

			if ( ! empty( $filters['date_after'] ) ) {
				$date_query['after'] = sanitize_text_field( $filters['date_after'] );
			}

			$args['date_query'] = array( $date_query );
		}

		$query = new WP_Query( $args );

		if ( empty( $filters['past_only'] ) ) {
			return $query->found_posts;
		}

		// For past_only, compare using raw timestamps for locale-safe comparison.
		$count = 0;
		foreach ( $query->posts as $booking_id ) {
			$booking = get_wc_booking( $booking_id );
			if ( $booking ) {
				$end_timestamp = $booking->get_end();
				if ( $end_timestamp && $end_timestamp <= time() ) {
					++$count;
				}
			}
		}

		return $count;
	}

	/**
	 * Format booking data for display.
	 *
	 * @since 0.0.1
	 * @param WC_Booking $booking Booking object.
	 * @return array Formatted booking data.
	 */
	private function format_booking_data( $booking ) {
		$customer_id = $booking->get_customer_id();
		$customer    = '';

		if ( $customer_id ) {
			$user = get_user_by( 'id', $customer_id );
			if ( $user ) {
				$customer = $user->display_name;
			}
		}

		if ( empty( $customer ) ) {
			$customer = __( 'Guest', 'jharudar-for-woocommerce' );
		}

		$product    = $booking->get_product();
		$order      = $booking->get_order();
		$start_date = $booking->get_start_date();
		$end_date   = $booking->get_end_date();

		// Use the correct pluralised function name: wc_bookings_get_status_label().
		$status_label = function_exists( 'wc_bookings_get_status_label' )
			? wc_bookings_get_status_label( $booking->get_status() )
			: ucfirst( $booking->get_status() );

		return array(
			'id'           => $booking->get_id(),
			'status'       => $booking->get_status(),
			'status_label' => $status_label,
			'customer'     => $customer,
			'customer_id'  => $customer_id,
			'product'      => $product ? $product->get_name() : __( 'Deleted product', 'jharudar-for-woocommerce' ),
			'product_id'   => $booking->get_product_id(),
			'order_id'     => $order ? $order->get_id() : 0,
			'start_date'   => $start_date ? $start_date : '',
			'end_date'     => $end_date ? $end_date : '',
			'persons'      => $booking->has_persons() ? $booking->get_persons_total() : '',
			'created_date' => get_the_date( get_option( 'date_format' ), $booking->get_id() ),
			'edit_url'     => get_edit_post_link( $booking->get_id(), 'raw' ),
		);
	}

	/**
	 * Delete bookings.
	 *
	 * @since 0.0.1
	 * @param array  $booking_ids   Booking IDs to delete.
	 * @param bool   $delete_orders Whether to also delete linked orders.
	 * @param string $action        Action (delete or trash).
	 * @return array Result data.
	 */
	public function delete_bookings( $booking_ids, $delete_orders = false, $action = 'delete' ) {
		if ( ! self::is_active() ) {
			return array(
				'deleted'        => 0,
				'failed'         => 0,
				'orders_deleted' => 0,
			);
		}

		$booking_ids = jharudar_sanitize_ids( $booking_ids );
		$batch_size  = jharudar_get_batch_size();
		$deleted     = 0;
		$failed      = 0;

		// Track deleted order IDs to avoid deleting the same order twice
		// (multiple bookings may share a single order).
		$deleted_order_ids = array();
		$orders_deleted    = 0;

		// If batch processing needed.
		if ( count( $booking_ids ) > $batch_size ) {
			$this->schedule_batch_delete( $booking_ids, $delete_orders );
			return array(
				'scheduled' => true,
				'total'     => count( $booking_ids ),
				'message'   => __( 'Bookings are being processed in the background.', 'jharudar-for-woocommerce' ),
			);
		}

		foreach ( $booking_ids as $booking_id ) {
			$booking = get_wc_booking( $booking_id );

			if ( ! $booking ) {
				++$failed;
				continue;
			}

			// Delete linked order if requested (before deleting the booking).
			if ( $delete_orders ) {
				$order_id = $booking->get_order_id();
				if ( $order_id && ! in_array( $order_id, $deleted_order_ids, true ) ) {
					$order = wc_get_order( $order_id );
					if ( $order ) {
						jharudar_log_activity( $action, 'booking_order', $order_id );
						if ( 'trash' === $action ) {
							$order->set_status( 'trash' );
							$order->save();
						} else {
							$order->delete( true );
						}
						$deleted_order_ids[] = $order_id;
						++$orders_deleted;
					}
				}
			}

			// Log activity.
			jharudar_log_activity( $action, 'booking', $booking_id );

			if ( 'trash' === $action ) {
				wp_trash_post( $booking_id );
			} else {
				// Use WC_Booking::delete() to trigger proper cleanup hooks
				// (Google Calendar sync, slot transients, woocommerce_delete_booking action).
				$booking->delete( true );
			}
			++$deleted;
		}

		return array(
			'deleted'        => $deleted,
			'failed'         => $failed,
			'orders_deleted' => $orders_deleted,
		);
	}

	/**
	 * Schedule batch delete using Action Scheduler.
	 *
	 * @since 0.0.1
	 * @param array $booking_ids   Booking IDs.
	 * @param bool  $delete_orders Whether to also delete linked orders.
	 * @return void
	 */
	private function schedule_batch_delete( $booking_ids, $delete_orders = false ) {
		$batch_size = jharudar_get_batch_size();
		$batches    = array_chunk( $booking_ids, $batch_size );

		foreach ( $batches as $index => $batch ) {
			as_schedule_single_action(
				time() + ( $index * 30 ),
				'jharudar_delete_bookings_batch',
				array(
					'booking_ids'   => $batch,
					'delete_orders' => $delete_orders,
				),
				'jharudar'
			);
		}
	}

	/**
	 * Count trashed bookings.
	 *
	 * @since 0.2.0
	 * @return int Trashed booking count.
	 */
	public static function count_trashed() {
		if ( ! self::is_active() ) {
			return 0;
		}

		$counts = wp_count_posts( 'wc_booking' );

		return isset( $counts->trash ) ? (int) $counts->trash : 0;
	}

	/**
	 * Permanently delete all trashed bookings.
	 *
	 * @since 0.2.0
	 * @return array Result data.
	 */
	public function empty_trash() {
		if ( ! self::is_active() ) {
			return array(
				'deleted' => 0,
				'failed'  => 0,
			);
		}

		$args = array(
			'post_type'      => 'wc_booking',
			'post_status'    => 'trash',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query   = new WP_Query( $args );
		$deleted = 0;
		$failed  = 0;

		foreach ( $query->posts as $booking_id ) {
			$booking = get_wc_booking( $booking_id );

			if ( $booking ) {
				jharudar_log_activity( 'delete', 'booking', $booking_id );
				$booking->delete( true );
			} else {
				wp_delete_post( $booking_id, true );
			}
			++$deleted;
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
		);
	}

	/**
	 * AJAX handler: Empty trash for bookings.
	 *
	 * @since 0.2.0
	 * @return void
	 */
	public function ajax_empty_trash() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Bookings is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->empty_trash();
		wp_send_json_success( $result );
	}

	/**
	 * Get booking statistics.
	 *
	 * @since 0.0.1
	 * @return array Stats data.
	 */
	public static function get_statistics() {
		if ( ! self::is_active() ) {
			return array(
				'total'     => 0,
				'confirmed' => 0,
				'paid'      => 0,
				'complete'  => 0,
				'cancelled' => 0,
				'past'      => 0,
			);
		}

		// Use wp_count_posts for efficient single-query counting.
		$counts = wp_count_posts( 'wc_booking' );

		$stats = array(
			'total'     => 0,
			'confirmed' => isset( $counts->confirmed ) ? (int) $counts->confirmed : 0,
			'paid'      => isset( $counts->paid ) ? (int) $counts->paid : 0,
			'complete'  => isset( $counts->complete ) ? (int) $counts->complete : 0,
			'cancelled' => isset( $counts->cancelled ) ? (int) $counts->cancelled : 0,
			'past'      => 0,
		);

		// Sum all known statuses for total.
		$all_statuses = array( 'confirmed', 'paid', 'complete', 'cancelled', 'unpaid', 'pending-confirmation', 'in-cart', 'was-in-cart' );
		foreach ( $all_statuses as $status ) {
			$key = str_replace( '-', '_', $status );
			if ( isset( $counts->$status ) ) {
				$stats['total'] += (int) $counts->$status;
			} elseif ( isset( $counts->$key ) ) {
				$stats['total'] += (int) $counts->$key;
			}
		}

		// Count past bookings using meta query on raw end timestamp for reliable comparison.
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$past_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_type = 'wc_booking'
				AND p.post_status != 'trash'
				AND pm.meta_key = '_booking_end'
				AND pm.meta_value != ''
				AND pm.meta_value < %s",
				gmdate( 'YmdHis' )
			)
		);

		$stats['past'] = (int) $past_count;

		return $stats;
	}

	/**
	 * Get available booking statuses.
	 *
	 * @since 0.0.1
	 * @return array Statuses.
	 */
	public function get_statuses() {
		if ( ! function_exists( 'get_wc_booking_statuses' ) ) {
			return array();
		}

		return get_wc_booking_statuses();
	}

	/**
	 * AJAX handler: Get bookings.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_bookings() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Bookings is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'status'      => isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '',
			'date_before' => isset( $_POST['date_before'] ) ? sanitize_text_field( wp_unslash( $_POST['date_before'] ) ) : '',
			'date_after'  => isset( $_POST['date_after'] ) ? sanitize_text_field( wp_unslash( $_POST['date_after'] ) ) : '',
			'past_only'   => isset( $_POST['past_only'] ) && 'true' === $_POST['past_only'],
			'limit'       => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'      => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_bookings( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete bookings.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_bookings() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Bookings is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$booking_ids   = isset( $_POST['booking_ids'] ) ? array_map( 'absint', (array) $_POST['booking_ids'] ) : array();
		$delete_orders = isset( $_POST['delete_orders'] ) && 'true' === $_POST['delete_orders'];
		$action        = isset( $_POST['delete_action'] ) ? sanitize_key( $_POST['delete_action'] ) : 'delete';

		if ( ! in_array( $action, array( 'delete', 'trash' ), true ) ) {
			$action = 'delete';
		}

		if ( empty( $booking_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No bookings selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result           = $this->delete_bookings( $booking_ids, $delete_orders, $action );
		$result['action'] = $action;
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Export bookings.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_export_bookings() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Bookings is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$booking_ids = isset( $_POST['booking_ids'] ) ? array_map( 'absint', (array) $_POST['booking_ids'] ) : array();
		$format      = isset( $_POST['format'] ) ? sanitize_key( $_POST['format'] ) : 'csv';

		if ( empty( $booking_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No bookings selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$export_data = array();

		foreach ( $booking_ids as $booking_id ) {
			$booking = get_wc_booking( $booking_id );
			if ( $booking ) {
				$export_data[] = $this->format_booking_data( $booking );
			}
		}

		$exporter = new Jharudar_Exporter( $format );
		$filepath = $exporter->set_data( $export_data )->set_filename( 'bookings-export' )->save();

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
	 * AJAX handler: Get booking stats.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_booking_stats() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$stats = self::get_statistics();
		wp_send_json_success( $stats );
	}
}
