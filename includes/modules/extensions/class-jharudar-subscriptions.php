<?php
/**
 * Subscriptions module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subscriptions module class.
 *
 * Handles WooCommerce Subscriptions cleanup operations.
 * Requires WooCommerce Subscriptions plugin to be active.
 *
 * @since 0.0.1
 */
class Jharudar_Subscriptions {

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
		add_action( 'wp_ajax_jharudar_get_subscriptions', array( $this, 'ajax_get_subscriptions' ) );
		add_action( 'wp_ajax_jharudar_delete_subscriptions', array( $this, 'ajax_delete_subscriptions' ) );
		add_action( 'wp_ajax_jharudar_export_subscriptions', array( $this, 'ajax_export_subscriptions' ) );
		add_action( 'wp_ajax_jharudar_get_subscription_stats', array( $this, 'ajax_get_subscription_stats' ) );
		add_action( 'wp_ajax_jharudar_empty_trash_subscriptions', array( $this, 'ajax_empty_trash' ) );
	}

	/**
	 * Check if WooCommerce Subscriptions is active.
	 *
	 * @since 0.0.1
	 * @return bool True if active.
	 */
	public static function is_active() {
		return class_exists( 'WC_Subscriptions' );
	}

	/**
	 * Get subscriptions based on filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Subscriptions data.
	 */
	public function get_subscriptions( $filters = array() ) {
		if ( ! self::is_active() ) {
			return array(
				'subscriptions' => array(),
				'total'         => 0,
			);
		}

		$defaults = array(
			'status'      => '',
			'date_before' => '',
			'date_after'  => '',
			'limit'       => 50,
			'offset'      => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'subscriptions_per_page' => $filters['limit'],
			'offset'                 => $filters['offset'],
			'orderby'                => 'start_date',
			'order'                  => 'DESC',
		);

		// Filter by status.
		if ( ! empty( $filters['status'] ) ) {
			$args['subscription_status'] = sanitize_key( $filters['status'] );
		}

		// Filter by date using date_created range syntax (supported by wc_get_orders).
		if ( ! empty( $filters['date_after'] ) || ! empty( $filters['date_before'] ) ) {
			$after                = ! empty( $filters['date_after'] ) ? sanitize_text_field( $filters['date_after'] ) : '';
			$before               = ! empty( $filters['date_before'] ) ? sanitize_text_field( $filters['date_before'] ) : '';
			$args['date_created'] = $after . '...' . $before;
		}

		$subscriptions_query = wcs_get_subscriptions( $args );

		$subscriptions = array();

		foreach ( $subscriptions_query as $subscription ) {
			$subscriptions[] = $this->format_subscription_data( $subscription );
		}

		// For the total, run a count-only query to avoid loading all objects.
		$count_args                           = $args;
		$count_args['subscriptions_per_page'] = -1;
		$count_args['offset']                 = 0;
		$total                                = count( wcs_get_subscriptions( $count_args ) );

		return array(
			'subscriptions' => $subscriptions,
			'total'         => $total,
		);
	}

	/**
	 * Count subscriptions matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Subscription count.
	 */
	public function count_subscriptions( $filters = array() ) {
		if ( ! self::is_active() ) {
			return 0;
		}

		$args = array(
			'subscriptions_per_page' => -1,
		);

		if ( ! empty( $filters['status'] ) ) {
			$args['subscription_status'] = sanitize_key( $filters['status'] );
		}

		// Use date_created range syntax supported by wc_get_orders.
		if ( ! empty( $filters['date_after'] ) || ! empty( $filters['date_before'] ) ) {
			$after                = ! empty( $filters['date_after'] ) ? sanitize_text_field( $filters['date_after'] ) : '';
			$before               = ! empty( $filters['date_before'] ) ? sanitize_text_field( $filters['date_before'] ) : '';
			$args['date_created'] = $after . '...' . $before;
		}

		return count( wcs_get_subscriptions( $args ) );
	}

	/**
	 * Format subscription data for display.
	 *
	 * @since 0.0.1
	 * @param WC_Subscription $subscription Subscription object.
	 * @return array Formatted subscription data.
	 */
	private function format_subscription_data( $subscription ) {
		$billing_email = $subscription->get_billing_email();
		$customer_name = trim( $subscription->get_billing_first_name() . ' ' . $subscription->get_billing_last_name() );

		if ( empty( $customer_name ) ) {
			$customer_name = $billing_email;
		}

		$start_date = $subscription->get_date( 'date_created' );
		$end_date   = $subscription->get_date( 'end' );
		$next_date  = $subscription->get_date( 'next_payment' );

		return array(
			'id'            => $subscription->get_id(),
			'status'        => $subscription->get_status(),
			'status_label'  => wcs_get_subscription_status_name( $subscription->get_status() ),
			'customer'      => $customer_name,
			'email'         => $billing_email,
			'total'         => $subscription->get_formatted_order_total(),
			'period'        => $subscription->get_billing_period(),
			'interval'      => $subscription->get_billing_interval(),
			'start_date'    => $start_date ? jharudar_format_date( $start_date, get_option( 'date_format' ) ) : '',
			'end_date'      => $end_date ? jharudar_format_date( $end_date, get_option( 'date_format' ) ) : __( 'Never', 'jharudar-for-woocommerce' ),
			'next_payment'  => $next_date ? jharudar_format_date( $next_date, get_option( 'date_format' ) ) : __( 'N/A', 'jharudar-for-woocommerce' ),
			'renewal_count' => $subscription->get_payment_count(),
			'edit_url'      => $subscription->get_edit_order_url(),
		);
	}

	/**
	 * Delete subscriptions.
	 *
	 * @since 0.0.1
	 * @param array  $subscription_ids Subscription IDs to delete.
	 * @param bool   $delete_renewals  Whether to also delete renewal orders.
	 * @param string $action           Action (delete or trash).
	 * @return array Result data.
	 */
	public function delete_subscriptions( $subscription_ids, $delete_renewals = false, $action = 'delete' ) {
		if ( ! self::is_active() ) {
			return array(
				'deleted'          => 0,
				'failed'           => 0,
				'renewals_deleted' => 0,
			);
		}

		$subscription_ids = jharudar_sanitize_ids( $subscription_ids );
		$batch_size       = jharudar_get_batch_size();
		$deleted          = 0;
		$failed           = 0;
		$renewals_deleted = 0;

		// If batch processing needed.
		if ( count( $subscription_ids ) > $batch_size ) {
			$this->schedule_batch_delete( $subscription_ids, $delete_renewals );
			return array(
				'scheduled' => true,
				'total'     => count( $subscription_ids ),
				'message'   => __( 'Subscriptions are being processed in the background.', 'jharudar-for-woocommerce' ),
			);
		}

		foreach ( $subscription_ids as $subscription_id ) {
			$subscription = wcs_get_subscription( $subscription_id );

			if ( ! $subscription ) {
				++$failed;
				continue;
			}

			// Delete renewal orders first if requested.
			if ( $delete_renewals ) {
				$renewal_ids = $subscription->get_related_orders( 'ids', 'renewal' );
				if ( ! empty( $renewal_ids ) ) {
					foreach ( $renewal_ids as $renewal_id ) {
						$renewal_order = wc_get_order( $renewal_id );
						if ( $renewal_order ) {
							jharudar_log_activity( $action, 'renewal_order', $renewal_id );
							if ( 'trash' === $action ) {
								$renewal_order->set_status( 'trash' );
								$renewal_order->save();
							} else {
								$renewal_order->delete( true );
							}
							++$renewals_deleted;
						}
					}
				}
			}

			// Log activity.
			jharudar_log_activity( $action, 'subscription', $subscription_id );

			if ( 'trash' === $action ) {
				$subscription->update_status( 'trash' );
			} else {
				$subscription->delete( true );
			}
			++$deleted;
		}

		return array(
			'deleted'          => $deleted,
			'failed'           => $failed,
			'renewals_deleted' => $renewals_deleted,
		);
	}

	/**
	 * Schedule batch delete using Action Scheduler.
	 *
	 * @since 0.0.1
	 * @param array $subscription_ids Subscription IDs.
	 * @param bool  $delete_renewals  Whether to also delete renewal orders.
	 * @return void
	 */
	private function schedule_batch_delete( $subscription_ids, $delete_renewals = false ) {
		$batch_size = jharudar_get_batch_size();
		$batches    = array_chunk( $subscription_ids, $batch_size );

		foreach ( $batches as $index => $batch ) {
			as_schedule_single_action(
				time() + ( $index * 30 ),
				'jharudar_delete_subscriptions_batch',
				array(
					'subscription_ids' => $batch,
					'delete_renewals'  => $delete_renewals,
				),
				'jharudar'
			);
		}
	}

	/**
	 * Count trashed subscriptions.
	 *
	 * @since 0.2.0
	 * @return int Trashed subscription count.
	 */
	public static function count_trashed() {
		if ( ! self::is_active() ) {
			return 0;
		}

		return count(
			wcs_get_subscriptions(
				array(
					'subscription_status'    => 'trash',
					'subscriptions_per_page' => -1,
				)
			)
		);
	}

	/**
	 * Permanently delete all trashed subscriptions.
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

		$subscriptions = wcs_get_subscriptions(
			array(
				'subscription_status'    => 'trash',
				'subscriptions_per_page' => -1,
			)
		);

		$deleted = 0;
		$failed  = 0;

		foreach ( $subscriptions as $subscription ) {
			jharudar_log_activity( 'delete', 'subscription', $subscription->get_id() );
			$subscription->delete( true );
			++$deleted;
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
		);
	}

	/**
	 * AJAX handler: Empty trash for subscriptions.
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
			wp_send_json_error( array( 'message' => __( 'WooCommerce Subscriptions is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->empty_trash();
		wp_send_json_success( $result );
	}

	/**
	 * Get subscription statistics.
	 *
	 * @since 0.0.1
	 * @return array Stats data.
	 */
	public static function get_statistics() {
		if ( ! self::is_active() ) {
			return array(
				'total'     => 0,
				'active'    => 0,
				'on_hold'   => 0,
				'cancelled' => 0,
				'expired'   => 0,
			);
		}

		$stats = array(
			'total'     => 0,
			'active'    => 0,
			'on_hold'   => 0,
			'cancelled' => 0,
			'expired'   => 0,
		);

		$status_counts = array(
			'active'    => 'active',
			'on-hold'   => 'on_hold',
			'cancelled' => 'cancelled',
			'expired'   => 'expired',
		);

		foreach ( $status_counts as $wc_status => $stat_key ) {
			$count = count(
				wcs_get_subscriptions(
					array(
						'subscription_status'    => $wc_status,
						'subscriptions_per_page' => -1,
					)
				)
			);

			$stats[ $stat_key ] = $count;
			$stats['total']    += $count;
		}

		// Add other statuses (including switched) to total.
		$other_statuses = array( 'pending', 'pending-cancel', 'switched' );
		foreach ( $other_statuses as $other_status ) {
			$stats['total'] += count(
				wcs_get_subscriptions(
					array(
						'subscription_status'    => $other_status,
						'subscriptions_per_page' => -1,
					)
				)
			);
		}

		return $stats;
	}

	/**
	 * Get available subscription statuses.
	 *
	 * @since 0.0.1
	 * @return array Statuses.
	 */
	public function get_statuses() {
		if ( ! function_exists( 'wcs_get_subscription_statuses' ) ) {
			return array();
		}

		return wcs_get_subscription_statuses();
	}

	/**
	 * AJAX handler: Get subscriptions.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_subscriptions() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Subscriptions is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'status'      => isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '',
			'date_before' => isset( $_POST['date_before'] ) ? sanitize_text_field( wp_unslash( $_POST['date_before'] ) ) : '',
			'date_after'  => isset( $_POST['date_after'] ) ? sanitize_text_field( wp_unslash( $_POST['date_after'] ) ) : '',
			'limit'       => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'      => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_subscriptions( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete subscriptions.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_subscriptions() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Subscriptions is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$subscription_ids = isset( $_POST['subscription_ids'] ) ? array_map( 'absint', (array) $_POST['subscription_ids'] ) : array();
		$delete_renewals  = isset( $_POST['delete_renewals'] ) && 'true' === $_POST['delete_renewals'];
		$action           = isset( $_POST['delete_action'] ) ? sanitize_key( $_POST['delete_action'] ) : 'delete';

		if ( ! in_array( $action, array( 'delete', 'trash' ), true ) ) {
			$action = 'delete';
		}

		if ( empty( $subscription_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No subscriptions selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result           = $this->delete_subscriptions( $subscription_ids, $delete_renewals, $action );
		$result['action'] = $action;
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Export subscriptions.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_export_subscriptions() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Subscriptions is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$subscription_ids = isset( $_POST['subscription_ids'] ) ? array_map( 'absint', (array) $_POST['subscription_ids'] ) : array();
		$format           = isset( $_POST['format'] ) ? sanitize_key( $_POST['format'] ) : 'csv';

		if ( empty( $subscription_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No subscriptions selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$export_data = array();

		foreach ( $subscription_ids as $sub_id ) {
			$subscription = wcs_get_subscription( $sub_id );
			if ( $subscription ) {
				$export_data[] = $this->format_subscription_data( $subscription );
			}
		}

		$exporter = new Jharudar_Exporter( $format );
		$filepath = $exporter->set_data( $export_data )->set_filename( 'subscriptions-export' )->save();

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
	 * AJAX handler: Get subscription stats.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_subscription_stats() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$stats = self::get_statistics();
		wp_send_json_success( $stats );
	}
}
