<?php
/**
 * Memberships module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Memberships module class.
 *
 * Handles WooCommerce Memberships cleanup operations.
 * Requires WooCommerce Memberships plugin to be active.
 *
 * @since 0.0.1
 */
class Jharudar_Memberships {

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
		add_action( 'wp_ajax_jharudar_get_memberships', array( $this, 'ajax_get_memberships' ) );
		add_action( 'wp_ajax_jharudar_delete_memberships', array( $this, 'ajax_delete_memberships' ) );
		add_action( 'wp_ajax_jharudar_export_memberships', array( $this, 'ajax_export_memberships' ) );
		add_action( 'wp_ajax_jharudar_get_membership_stats', array( $this, 'ajax_get_membership_stats' ) );
		add_action( 'wp_ajax_jharudar_empty_trash_memberships', array( $this, 'ajax_empty_trash' ) );
	}

	/**
	 * Check if WooCommerce Memberships is active.
	 *
	 * @since 0.0.1
	 * @return bool True if active.
	 */
	public static function is_active() {
		return function_exists( 'wc_memberships' );
	}

	/**
	 * Get memberships based on filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Memberships data.
	 */
	public function get_memberships( $filters = array() ) {
		if ( ! self::is_active() ) {
			return array(
				'memberships' => array(),
				'total'       => 0,
			);
		}

		$defaults = array(
			'status'      => '',
			'plan'        => '',
			'date_before' => '',
			'date_after'  => '',
			'limit'       => 50,
			'offset'      => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'post_type'      => 'wc_user_membership',
			'posts_per_page' => $filters['limit'],
			'offset'         => $filters['offset'],
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_status'    => 'any',
		);

		// Filter by status.
		if ( ! empty( $filters['status'] ) ) {
			$args['post_status'] = 'wcm-' . sanitize_key( $filters['status'] );
		}

		// Filter by plan.
		if ( ! empty( $filters['plan'] ) ) {
			$args['post_parent'] = absint( $filters['plan'] );
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

		$query       = new WP_Query( $args );
		$memberships = array();

		foreach ( $query->posts as $post ) {
			$membership = wc_memberships_get_user_membership( $post );

			if ( ! $membership ) {
				continue;
			}

			$memberships[] = $this->format_membership_data( $membership );
		}

		// Use found_posts from the original query to avoid a separate count query.
		return array(
			'memberships' => $memberships,
			'total'       => $query->found_posts,
		);
	}

	/**
	 * Count memberships matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Membership count.
	 */
	public function count_memberships( $filters = array() ) {
		if ( ! self::is_active() ) {
			return 0;
		}

		$args = array(
			'post_type'      => 'wc_user_membership',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		);

		if ( ! empty( $filters['status'] ) ) {
			$args['post_status'] = 'wcm-' . sanitize_key( $filters['status'] );
		}

		if ( ! empty( $filters['plan'] ) ) {
			$args['post_parent'] = absint( $filters['plan'] );
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
		return $query->found_posts;
	}

	/**
	 * Format membership data for display.
	 *
	 * @since 0.0.1
	 * @param WC_Memberships_User_Membership $membership Membership object.
	 * @return array Formatted membership data.
	 */
	private function format_membership_data( $membership ) {
		$user    = get_user_by( 'id', $membership->get_user_id() );
		$plan    = $membership->get_plan();
		$created = $membership->get_start_date();
		$expires = $membership->get_end_date();

		$user_display = '';
		if ( $user ) {
			$user_display = $user->display_name;
		}

		return array(
			'id'           => $membership->get_id(),
			'user_id'      => $membership->get_user_id(),
			'user_name'    => $user_display,
			'user_email'   => $user ? $user->user_email : '',
			'plan_name'    => $plan ? $plan->get_name() : __( 'Unknown', 'jharudar-for-woocommerce' ),
			'plan_id'      => $plan ? $plan->get_id() : 0,
			'status'       => $membership->get_status(),
			'status_label' => wc_memberships_get_user_membership_status_name( $membership->get_status() ),
			'start_date'   => $created ? jharudar_format_date( $created, get_option( 'date_format' ) ) : '',
			'end_date'     => $expires ? jharudar_format_date( $expires, get_option( 'date_format' ) ) : __( 'Unlimited', 'jharudar-for-woocommerce' ),
			'edit_url'     => get_edit_post_link( $membership->get_id(), 'raw' ),
		);
	}

	/**
	 * Delete memberships.
	 *
	 * @since 0.0.1
	 * @param array  $membership_ids Membership IDs to delete.
	 * @param string $action         Action (delete or trash).
	 * @return array Result data.
	 */
	public function delete_memberships( $membership_ids, $action = 'delete' ) {
		if ( ! self::is_active() ) {
			return array(
				'deleted' => 0,
				'failed'  => 0,
			);
		}

		$membership_ids = jharudar_sanitize_ids( $membership_ids );
		$batch_size     = jharudar_get_batch_size();
		$deleted        = 0;
		$failed         = 0;

		// If batch processing needed.
		if ( count( $membership_ids ) > $batch_size ) {
			$this->schedule_batch_delete( $membership_ids );
			return array(
				'scheduled' => true,
				'total'     => count( $membership_ids ),
				'message'   => __( 'Memberships are being processed in the background.', 'jharudar-for-woocommerce' ),
			);
		}

		foreach ( $membership_ids as $membership_id ) {
			$membership = wc_memberships_get_user_membership( $membership_id );

			if ( ! $membership ) {
				++$failed;
				continue;
			}

			// Log activity.
			jharudar_log_activity( $action, 'membership', $membership_id );

			if ( 'trash' === $action ) {
				wp_trash_post( $membership_id );
			} else {
				wp_delete_post( $membership_id, true );
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
	 * @param array $membership_ids Membership IDs.
	 * @return void
	 */
	private function schedule_batch_delete( $membership_ids ) {
		$batch_size = jharudar_get_batch_size();
		$batches    = array_chunk( $membership_ids, $batch_size );

		foreach ( $batches as $index => $batch ) {
			as_schedule_single_action(
				time() + ( $index * 30 ),
				'jharudar_delete_memberships_batch',
				array(
					'membership_ids' => $batch,
				),
				'jharudar'
			);
		}
	}

	/**
	 * Count trashed memberships.
	 *
	 * @since 0.2.0
	 * @return int Trashed membership count.
	 */
	public static function count_trashed() {
		if ( ! self::is_active() ) {
			return 0;
		}

		$counts = wp_count_posts( 'wc_user_membership' );

		return isset( $counts->trash ) ? (int) $counts->trash : 0;
	}

	/**
	 * Permanently delete all trashed memberships.
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
			'post_type'      => 'wc_user_membership',
			'post_status'    => 'trash',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query   = new WP_Query( $args );
		$deleted = 0;
		$failed  = 0;

		foreach ( $query->posts as $membership_id ) {
			jharudar_log_activity( 'delete', 'membership', $membership_id );
			wp_delete_post( $membership_id, true );
			++$deleted;
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
		);
	}

	/**
	 * AJAX handler: Empty trash for memberships.
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
			wp_send_json_error( array( 'message' => __( 'WooCommerce Memberships is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->empty_trash();
		wp_send_json_success( $result );
	}

	/**
	 * Get membership statistics.
	 *
	 * @since 0.0.1
	 * @return array Stats data.
	 */
	public static function get_statistics() {
		if ( ! self::is_active() ) {
			return array(
				'total'     => 0,
				'active'    => 0,
				'expired'   => 0,
				'cancelled' => 0,
				'paused'    => 0,
			);
		}

		// Use wp_count_posts for efficient single-query counting.
		$counts = wp_count_posts( 'wc_user_membership' );

		$status_map = array(
			'active'    => 'wcm-active',
			'expired'   => 'wcm-expired',
			'cancelled' => 'wcm-cancelled',
			'paused'    => 'wcm-paused',
		);

		$stats = array(
			'total'     => 0,
			'active'    => 0,
			'expired'   => 0,
			'cancelled' => 0,
			'paused'    => 0,
		);

		// Count main statuses.
		foreach ( $status_map as $key => $status ) {
			$prop            = str_replace( '-', '_', $status );
			$stats[ $key ]   = isset( $counts->$prop ) ? (int) $counts->$prop : 0;
			$stats['total'] += $stats[ $key ];
		}

		// Add other statuses to total.
		$other_statuses = array( 'wcm-pending', 'wcm-free_trial', 'wcm-delayed', 'wcm-complimentary' );
		foreach ( $other_statuses as $other_status ) {
			$prop            = str_replace( '-', '_', $other_status );
			$stats['total'] += isset( $counts->$prop ) ? (int) $counts->$prop : 0;
		}

		return $stats;
	}

	/**
	 * Get available membership statuses.
	 *
	 * @since 0.0.1
	 * @return array Statuses.
	 */
	public function get_statuses() {
		if ( ! function_exists( 'wc_memberships_get_user_membership_statuses' ) ) {
			return array();
		}

		return wc_memberships_get_user_membership_statuses();
	}

	/**
	 * Get available membership plans.
	 *
	 * @since 0.0.1
	 * @return array Plans.
	 */
	public function get_plans() {
		if ( ! self::is_active() ) {
			return array();
		}

		$plans = array();

		// Use the official Memberships API for consistency and internal caching.
		if ( function_exists( 'wc_memberships_get_membership_plans' ) ) {
			$plan_objects = wc_memberships_get_membership_plans();
			foreach ( $plan_objects as $plan ) {
				$plans[ $plan->get_id() ] = $plan->get_name();
			}
		} else {
			// Fallback to WP_Query if the API function is unavailable.
			$plans_query = new WP_Query(
				array(
					'post_type'      => 'wc_membership_plan',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);

			foreach ( $plans_query->posts as $plan_post ) {
				$plans[ $plan_post->ID ] = $plan_post->post_title;
			}
		}

		return $plans;
	}

	/**
	 * AJAX handler: Get memberships.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_memberships() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Memberships is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'status'      => isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '',
			'plan'        => isset( $_POST['plan'] ) ? absint( $_POST['plan'] ) : 0,
			'date_before' => isset( $_POST['date_before'] ) ? sanitize_text_field( wp_unslash( $_POST['date_before'] ) ) : '',
			'date_after'  => isset( $_POST['date_after'] ) ? sanitize_text_field( wp_unslash( $_POST['date_after'] ) ) : '',
			'limit'       => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'      => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_memberships( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete memberships.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_memberships() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Memberships is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$membership_ids = isset( $_POST['membership_ids'] ) ? array_map( 'absint', (array) $_POST['membership_ids'] ) : array();
		$action         = isset( $_POST['delete_action'] ) ? sanitize_key( $_POST['delete_action'] ) : 'delete';

		if ( ! in_array( $action, array( 'delete', 'trash' ), true ) ) {
			$action = 'delete';
		}

		if ( empty( $membership_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No memberships selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result           = $this->delete_memberships( $membership_ids, $action );
		$result['action'] = $action;
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Export memberships.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_export_memberships() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Memberships is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$membership_ids = isset( $_POST['membership_ids'] ) ? array_map( 'absint', (array) $_POST['membership_ids'] ) : array();
		$format         = isset( $_POST['format'] ) ? sanitize_key( $_POST['format'] ) : 'csv';

		if ( empty( $membership_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No memberships selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$export_data = array();

		foreach ( $membership_ids as $membership_id ) {
			$membership = wc_memberships_get_user_membership( $membership_id );
			if ( $membership ) {
				$export_data[] = $this->format_membership_data( $membership );
			}
		}

		$exporter = new Jharudar_Exporter( $format );
		$filepath = $exporter->set_data( $export_data )->set_filename( 'memberships-export' )->save();

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
	 * AJAX handler: Get membership stats.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_membership_stats() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$stats = self::get_statistics();
		wp_send_json_success( $stats );
	}
}
