<?php
/**
 * Appointments module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Appointments module class.
 *
 * Handles WooCommerce Appointments cleanup operations.
 * Requires WooCommerce Appointments plugin to be active.
 *
 * @since 0.0.1
 */
class Jharudar_Appointments {

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
		add_action( 'wp_ajax_jharudar_get_appointments', array( $this, 'ajax_get_appointments' ) );
		add_action( 'wp_ajax_jharudar_delete_appointments', array( $this, 'ajax_delete_appointments' ) );
		add_action( 'wp_ajax_jharudar_export_appointments', array( $this, 'ajax_export_appointments' ) );
		add_action( 'wp_ajax_jharudar_get_appointment_stats', array( $this, 'ajax_get_appointment_stats' ) );
		add_action( 'wp_ajax_jharudar_empty_trash_appointments', array( $this, 'ajax_empty_trash' ) );
	}

	/**
	 * Check if WooCommerce Appointments is active.
	 *
	 * @since 0.0.1
	 * @return bool True if active.
	 */
	public static function is_active() {
		return class_exists( 'WC_Appointments' );
	}

	/**
	 * Get appointments based on filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Appointments data.
	 */
	public function get_appointments( $filters = array() ) {
		if ( ! self::is_active() ) {
			return array(
				'appointments' => array(),
				'total'        => 0,
			);
		}

		$defaults = array(
			'status'      => '',
			'date_before' => '',
			'date_after'  => '',
			'past_only'   => false,
			'staff'       => '',
			'limit'       => 50,
			'offset'      => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'post_type'      => 'wc_appointment',
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

		// Filter by staff.
		if ( ! empty( $filters['staff'] ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_appointment_staff_id',
					'value' => absint( $filters['staff'] ),
				),
			);
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

		$query        = new WP_Query( $args );
		$appointments = array();

		foreach ( $query->posts as $post ) {
			$appointment = get_wc_appointment( $post->ID );

			if ( ! $appointment ) {
				continue;
			}

			// Filter by past appointments using raw timestamp for locale-safe comparison.
			if ( $filters['past_only'] ) {
				$end_timestamp = method_exists( $appointment, 'get_end' ) ? $appointment->get_end() : strtotime( $appointment->get_end_date() );
				if ( $end_timestamp && $end_timestamp > time() ) {
					continue;
				}
			}

			$appointments[] = $this->format_appointment_data( $appointment );
		}

		// Use found_posts from the query to avoid a separate count query.
		$total = $query->found_posts;

		// For past_only, the total must be recalculated since we filter in PHP.
		if ( $filters['past_only'] ) {
			$total = $this->count_appointments( $filters );
		}

		return array(
			'appointments' => $appointments,
			'total'        => $total,
		);
	}

	/**
	 * Count appointments matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Appointment count.
	 */
	public function count_appointments( $filters = array() ) {
		if ( ! self::is_active() ) {
			return 0;
		}

		$args = array(
			'post_type'      => 'wc_appointment',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		);

		if ( ! empty( $filters['status'] ) ) {
			$args['post_status'] = sanitize_key( $filters['status'] );
		}

		if ( ! empty( $filters['staff'] ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_appointment_staff_id',
					'value' => absint( $filters['staff'] ),
				),
			);
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
		foreach ( $query->posts as $appointment_id ) {
			$appointment = get_wc_appointment( $appointment_id );
			if ( $appointment ) {
				$end_timestamp = method_exists( $appointment, 'get_end' ) ? $appointment->get_end() : strtotime( $appointment->get_end_date() );
				if ( $end_timestamp && $end_timestamp <= time() ) {
					++$count;
				}
			}
		}

		return $count;
	}

	/**
	 * Format appointment data for display.
	 *
	 * @since 0.0.1
	 * @param WC_Appointment $appointment Appointment object.
	 * @return array Formatted appointment data.
	 */
	private function format_appointment_data( $appointment ) {
		$customer_id = $appointment->get_customer_id();
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

		$product    = $appointment->get_product();
		$order      = $appointment->get_order();
		$start_date = $appointment->get_start_date();
		$end_date   = $appointment->get_end_date();
		// Safely retrieve staff info - WC Appointments uses get_staff_ids(),
		// WC Bookings uses get_resource_id() (resources, not staff).
		$staff_name = '';
		if ( method_exists( $appointment, 'get_staff_ids' ) ) {
			$staff_ids = $appointment->get_staff_ids();
			if ( ! empty( $staff_ids ) ) {
				$staff_names = array();
				foreach ( (array) $staff_ids as $staff_id ) {
					$staff_user = get_user_by( 'id', $staff_id );
					if ( $staff_user ) {
						$staff_names[] = $staff_user->display_name;
					}
				}
				$staff_name = implode( ', ', $staff_names );
			}
		}

		// Use the correct function name with function_exists guard for safety.
		$status_label = function_exists( 'wc_appointment_get_status_label' )
			? wc_appointment_get_status_label( $appointment->get_status() )
			: ucfirst( $appointment->get_status() );

		return array(
			'id'           => $appointment->get_id(),
			'status'       => $appointment->get_status(),
			'status_label' => $status_label,
			'customer'     => $customer,
			'customer_id'  => $customer_id,
			'product'      => $product ? $product->get_name() : __( 'Deleted product', 'jharudar-for-woocommerce' ),
			'product_id'   => $appointment->get_product_id(),
			'order_id'     => $order ? $order->get_id() : 0,
			'staff'        => $staff_name,
			'start_date'   => $start_date ? $start_date : '',
			'end_date'     => $end_date ? $end_date : '',
			'created_date' => get_the_date( get_option( 'date_format' ), $appointment->get_id() ),
			'edit_url'     => get_edit_post_link( $appointment->get_id(), 'raw' ),
		);
	}

	/**
	 * Delete appointments.
	 *
	 * @since 0.0.1
	 * @param array  $appointment_ids Appointment IDs to delete.
	 * @param string $action          Action (delete or trash).
	 * @return array Result data.
	 */
	public function delete_appointments( $appointment_ids, $action = 'delete' ) {
		if ( ! self::is_active() ) {
			return array(
				'deleted' => 0,
				'failed'  => 0,
			);
		}

		$appointment_ids = jharudar_sanitize_ids( $appointment_ids );
		$batch_size      = jharudar_get_batch_size();
		$deleted         = 0;
		$failed          = 0;

		// If batch processing needed.
		if ( count( $appointment_ids ) > $batch_size ) {
			$this->schedule_batch_delete( $appointment_ids );
			return array(
				'scheduled' => true,
				'total'     => count( $appointment_ids ),
				'message'   => __( 'Appointments are being processed in the background.', 'jharudar-for-woocommerce' ),
			);
		}

		foreach ( $appointment_ids as $appointment_id ) {
			$appointment = get_wc_appointment( $appointment_id );

			if ( ! $appointment ) {
				++$failed;
				continue;
			}

			// Log activity.
			jharudar_log_activity( $action, 'appointment', $appointment_id );

			if ( 'trash' === $action ) {
				wp_trash_post( $appointment_id );
			} elseif ( method_exists( $appointment, 'delete' ) ) {
				// Use WC_Appointment::delete() to trigger proper cleanup hooks.
				$appointment->delete( true );
			} else {
				wp_delete_post( $appointment_id, true );
			}
			++$deleted;
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
		);
	}

	/**
	 * Schedule batch delete using Action Scheduler.
	 *
	 * @since 0.0.1
	 * @param array $appointment_ids Appointment IDs.
	 * @return void
	 */
	private function schedule_batch_delete( $appointment_ids ) {
		$batch_size = jharudar_get_batch_size();
		$batches    = array_chunk( $appointment_ids, $batch_size );

		foreach ( $batches as $index => $batch ) {
			as_schedule_single_action(
				time() + ( $index * 30 ),
				'jharudar_delete_appointments_batch',
				array(
					'appointment_ids' => $batch,
				),
				'jharudar'
			);
		}
	}

	/**
	 * Count trashed appointments.
	 *
	 * @since 0.2.0
	 * @return int Trashed appointment count.
	 */
	public static function count_trashed() {
		if ( ! self::is_active() ) {
			return 0;
		}

		$counts = wp_count_posts( 'wc_appointment' );

		return isset( $counts->trash ) ? (int) $counts->trash : 0;
	}

	/**
	 * Permanently delete all trashed appointments.
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
			'post_type'      => 'wc_appointment',
			'post_status'    => 'trash',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query   = new WP_Query( $args );
		$deleted = 0;
		$failed  = 0;

		foreach ( $query->posts as $appointment_id ) {
			$appointment = get_wc_appointment( $appointment_id );

			if ( $appointment && method_exists( $appointment, 'delete' ) ) {
				jharudar_log_activity( 'delete', 'appointment', $appointment_id );
				$appointment->delete( true );
			} else {
				wp_delete_post( $appointment_id, true );
			}
			++$deleted;
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
		);
	}

	/**
	 * AJAX handler: Empty trash for appointments.
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
			wp_send_json_error( array( 'message' => __( 'WooCommerce Appointments is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->empty_trash();
		wp_send_json_success( $result );
	}

	/**
	 * Get appointment statistics.
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
		$counts = wp_count_posts( 'wc_appointment' );

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

		// Count past appointments using meta query on raw end timestamp.
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$past_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_type = 'wc_appointment'
				AND p.post_status != 'trash'
				AND pm.meta_key = '_appointment_end'
				AND pm.meta_value != ''
				AND pm.meta_value < %s",
				gmdate( 'YmdHis' )
			)
		);

		$stats['past'] = (int) $past_count;

		return $stats;
	}

	/**
	 * Get available appointment statuses.
	 *
	 * @since 0.0.1
	 * @return array Statuses.
	 */
	public function get_statuses() {
		if ( ! function_exists( 'get_wc_appointment_statuses' ) ) {
			return array();
		}

		return get_wc_appointment_statuses();
	}

	/**
	 * AJAX handler: Get appointments.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_appointments() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Appointments is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'status'      => isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '',
			'date_before' => isset( $_POST['date_before'] ) ? sanitize_text_field( wp_unslash( $_POST['date_before'] ) ) : '',
			'date_after'  => isset( $_POST['date_after'] ) ? sanitize_text_field( wp_unslash( $_POST['date_after'] ) ) : '',
			'past_only'   => isset( $_POST['past_only'] ) && 'true' === $_POST['past_only'],
			'staff'       => isset( $_POST['staff'] ) ? absint( $_POST['staff'] ) : '',
			'limit'       => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'      => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_appointments( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete appointments.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_appointments() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Appointments is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$appointment_ids = isset( $_POST['appointment_ids'] ) ? array_map( 'absint', (array) $_POST['appointment_ids'] ) : array();
		$action          = isset( $_POST['delete_action'] ) ? sanitize_key( $_POST['delete_action'] ) : 'delete';

		if ( ! in_array( $action, array( 'delete', 'trash' ), true ) ) {
			$action = 'delete';
		}

		if ( empty( $appointment_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No appointments selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result           = $this->delete_appointments( $appointment_ids, $action );
		$result['action'] = $action;
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Export appointments.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_export_appointments() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Appointments is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$appointment_ids = isset( $_POST['appointment_ids'] ) ? array_map( 'absint', (array) $_POST['appointment_ids'] ) : array();
		$format          = isset( $_POST['format'] ) ? sanitize_key( $_POST['format'] ) : 'csv';

		if ( empty( $appointment_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No appointments selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$export_data = array();

		foreach ( $appointment_ids as $appointment_id ) {
			$appointment = get_wc_appointment( $appointment_id );
			if ( $appointment ) {
				$export_data[] = $this->format_appointment_data( $appointment );
			}
		}

		$exporter = new Jharudar_Exporter( $format );
		$filepath = $exporter->set_data( $export_data )->set_filename( 'appointments-export' )->save();

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
	 * AJAX handler: Get appointment stats.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_appointment_stats() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$stats = self::get_statistics();
		wp_send_json_success( $stats );
	}
}
