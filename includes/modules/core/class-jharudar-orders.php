<?php
/**
 * Orders module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orders module class.
 *
 * Handles order cleanup operations.
 *
 * @since 0.0.1
 */
class Jharudar_Orders {

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
		add_action( 'wp_ajax_jharudar_get_orders', array( $this, 'ajax_get_orders' ) );
		add_action( 'wp_ajax_jharudar_delete_orders', array( $this, 'ajax_delete_orders' ) );
		add_action( 'wp_ajax_jharudar_anonymize_orders', array( $this, 'ajax_anonymize_orders' ) );
		add_action( 'wp_ajax_jharudar_export_orders', array( $this, 'ajax_export_orders' ) );
		add_action( 'wp_ajax_jharudar_empty_trash_orders', array( $this, 'ajax_empty_trash' ) );
	}

	/**
	 * Get orders based on filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Orders data.
	 */
	public function get_orders( $filters = array() ) {
		$defaults = array(
			'status'         => '',
			'payment_method' => '',
			'date_after'     => '',
			'date_before'    => '',
			'customer_id'    => '',
			'limit'          => 50,
			'offset'         => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'limit'  => $filters['limit'],
			'offset' => $filters['offset'],
			'return' => 'ids',
		);

		// Filter by status.
		if ( ! empty( $filters['status'] ) ) {
			$args['status'] = sanitize_key( $filters['status'] );
		} else {
			$args['status'] = array_keys( wc_get_order_statuses() );
		}

		// Filter by payment method.
		if ( ! empty( $filters['payment_method'] ) ) {
			$args['payment_method'] = sanitize_text_field( $filters['payment_method'] );
		}

		// Filter by customer.
		if ( ! empty( $filters['customer_id'] ) ) {
			$args['customer_id'] = absint( $filters['customer_id'] );
		}

		// Filter by date range.
		if ( ! empty( $filters['date_after'] ) ) {
			$args['date_created'] = '>=' . sanitize_text_field( $filters['date_after'] );
		}

		if ( ! empty( $filters['date_before'] ) ) {
			if ( isset( $args['date_created'] ) ) {
				// WooCommerce doesn't support between in a single query, use date_query.
				unset( $args['date_created'] );
				$args['date_query'] = array(
					array(
						'after'     => sanitize_text_field( $filters['date_after'] ),
						'before'    => sanitize_text_field( $filters['date_before'] ),
						'inclusive' => true,
					),
				);
			} else {
				$args['date_created'] = '<=' . sanitize_text_field( $filters['date_before'] );
			}
		}

		$order_ids = wc_get_orders( $args );
		$orders    = array();

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$orders[] = $this->format_order_data( $order );
			}
		}

		return array(
			'orders' => $orders,
			'total'  => $this->count_orders( $filters ),
		);
	}

	/**
	 * Count orders matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Order count.
	 */
	public function count_orders( $filters = array() ) {
		$args = array(
			'limit'  => -1,
			'return' => 'ids',
		);

		if ( ! empty( $filters['status'] ) ) {
			$args['status'] = sanitize_key( $filters['status'] );
		} else {
			$args['status'] = array_keys( wc_get_order_statuses() );
		}

		if ( ! empty( $filters['payment_method'] ) ) {
			$args['payment_method'] = sanitize_text_field( $filters['payment_method'] );
		}

		if ( ! empty( $filters['customer_id'] ) ) {
			$args['customer_id'] = absint( $filters['customer_id'] );
		}

		if ( ! empty( $filters['date_after'] ) && ! empty( $filters['date_before'] ) ) {
			$args['date_query'] = array(
				array(
					'after'     => sanitize_text_field( $filters['date_after'] ),
					'before'    => sanitize_text_field( $filters['date_before'] ),
					'inclusive' => true,
				),
			);
		} elseif ( ! empty( $filters['date_after'] ) ) {
			$args['date_created'] = '>=' . sanitize_text_field( $filters['date_after'] );
		} elseif ( ! empty( $filters['date_before'] ) ) {
			$args['date_created'] = '<=' . sanitize_text_field( $filters['date_before'] );
		}

		$order_ids = wc_get_orders( $args );
		return count( $order_ids );
	}

	/**
	 * Format order data for display.
	 *
	 * @since 0.0.1
	 * @param WC_Order $order Order object.
	 * @return array Formatted order data.
	 */
	private function format_order_data( $order ) {
		$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		$customer_name = trim( $customer_name );

		if ( empty( $customer_name ) ) {
			$customer_name = __( 'Guest', 'jharudar-for-woocommerce' );
		}

		return array(
			'id'             => $order->get_id(),
			'order_number'   => $order->get_order_number(),
			'status'         => wc_get_order_status_name( $order->get_status() ),
			'status_key'     => $order->get_status(),
			'date'           => $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '-',
			'total'          => $order->get_formatted_order_total(),
			'customer'       => $customer_name,
			'customer_email' => $order->get_billing_email(),
			'payment_method' => $order->get_payment_method_title(),
			'items_count'    => $order->get_item_count(),
			'edit_url'       => $order->get_edit_order_url(),
		);
	}

	/**
	 * Delete orders.
	 *
	 * @since 0.0.1
	 * @param array  $order_ids Order IDs to delete.
	 * @param string $action    Action (delete or trash).
	 * @return array Result data.
	 */
	public function delete_orders( $order_ids, $action = 'delete' ) {
		$order_ids  = jharudar_sanitize_ids( $order_ids );
		$batch_size = jharudar_get_batch_size();
		$deleted    = 0;
		$failed     = 0;

		// If batch processing needed.
		if ( count( $order_ids ) > $batch_size ) {
			$this->schedule_batch_delete( $order_ids, $action );
			return array(
				'scheduled' => true,
				'total'     => count( $order_ids ),
				'message'   => __( 'Orders are being deleted in the background.', 'jharudar-for-woocommerce' ),
			);
		}

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				++$failed;
				continue;
			}

			// Log activity.
			jharudar_log_activity( $action, 'order', $order_id );

			if ( 'trash' === $action ) {
				$order->set_status( 'trash' );
				$order->save();
			} else {
				$order->delete( true );
			}

			++$deleted;
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
		);
	}

	/**
	 * Anonymize orders.
	 *
	 * @since 0.0.1
	 * @param array $order_ids Order IDs to anonymize.
	 * @return array Result data.
	 */
	public function anonymize_orders( $order_ids ) {
		$order_ids  = jharudar_sanitize_ids( $order_ids );
		$anonymized = 0;
		$failed     = 0;

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				++$failed;
				continue;
			}

			// Anonymize personal data.
			$this->anonymize_order( $order );

			// Log activity.
			jharudar_log_activity( 'anonymize', 'order', $order_id );

			++$anonymized;
		}

		return array(
			'anonymized' => $anonymized,
			'failed'     => $failed,
		);
	}

	/**
	 * Anonymize a single order.
	 *
	 * @since 0.0.1
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	private function anonymize_order( $order ) {
		$anonymized_data = array(
			'billing_first_name'  => __( 'Anonymized', 'jharudar-for-woocommerce' ),
			'billing_last_name'   => '',
			'billing_company'     => '',
			'billing_address_1'   => '',
			'billing_address_2'   => '',
			'billing_city'        => '',
			'billing_postcode'    => '',
			'billing_state'       => '',
			'billing_country'     => '',
			'billing_email'       => 'anonymized-' . $order->get_id() . '@example.com',
			'billing_phone'       => '',
			'shipping_first_name' => __( 'Anonymized', 'jharudar-for-woocommerce' ),
			'shipping_last_name'  => '',
			'shipping_company'    => '',
			'shipping_address_1'  => '',
			'shipping_address_2'  => '',
			'shipping_city'       => '',
			'shipping_postcode'   => '',
			'shipping_state'      => '',
			'shipping_country'    => '',
		);

		foreach ( $anonymized_data as $key => $value ) {
			$method = 'set_' . $key;
			if ( method_exists( $order, $method ) ) {
				$order->$method( $value );
			}
		}

		// Remove customer IP and user agent.
		$order->set_customer_ip_address( '' );
		$order->set_customer_user_agent( '' );

		// Add note about anonymization.
		$order->add_order_note( __( 'Order personal data anonymized by Jharudar.', 'jharudar-for-woocommerce' ) );

		$order->save();
	}

	/**
	 * Schedule batch delete using Action Scheduler.
	 *
	 * @since 0.0.1
	 * @param array  $order_ids Order IDs.
	 * @param string $action    Action type.
	 * @return void
	 */
	private function schedule_batch_delete( $order_ids, $action ) {
		$batch_size = jharudar_get_batch_size();
		$batches    = array_chunk( $order_ids, $batch_size );

		foreach ( $batches as $index => $batch ) {
			as_schedule_single_action(
				time() + ( $index * 30 ),
				'jharudar_delete_orders_batch',
				array(
					'order_ids' => $batch,
					'action'    => $action,
				),
				'jharudar'
			);
		}
	}

	/**
	 * Count trashed orders.
	 *
	 * @since 0.2.0
	 * @return int Trashed order count.
	 */
	public static function count_trashed() {
		return count(
			wc_get_orders(
				array(
					'limit'  => -1,
					'return' => 'ids',
					'status' => 'trash',
				)
			)
		);
	}

	/**
	 * Permanently delete all trashed orders.
	 *
	 * @since 0.2.0
	 * @return array Result data.
	 */
	public function empty_trash() {
		$order_ids = wc_get_orders(
			array(
				'limit'  => -1,
				'return' => 'ids',
				'status' => 'trash',
			)
		);

		$deleted = 0;
		$failed  = 0;

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				++$failed;
				continue;
			}

			jharudar_log_activity( 'delete', 'order', $order_id );
			$order->delete( true );
			++$deleted;
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
		);
	}

	/**
	 * AJAX handler: Empty trash for orders.
	 *
	 * @since 0.2.0
	 * @return void
	 */
	public function ajax_empty_trash() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->empty_trash();
		wp_send_json_success( $result );
	}

	/**
	 * Get order statistics.
	 *
	 * @since 0.0.1
	 * @return array Statistics data.
	 */
	public static function get_statistics() {
		$stats = array(
			'total'      => 0,
			'pending'    => 0,
			'processing' => 0,
			'completed'  => 0,
			'on_hold'    => 0,
			'cancelled'  => 0,
			'refunded'   => 0,
			'failed'     => 0,
		);

		// Get total orders count.
		$stats['total'] = count(
			wc_get_orders(
				array(
					'limit'  => -1,
					'return' => 'ids',
					'status' => array_keys( wc_get_order_statuses() ),
				)
			)
		);

		// Get counts for specific statuses.
		$status_counts = array(
			'pending'    => 'wc-pending',
			'processing' => 'wc-processing',
			'completed'  => 'wc-completed',
			'on_hold'    => 'wc-on-hold',
			'cancelled'  => 'wc-cancelled',
			'refunded'   => 'wc-refunded',
			'failed'     => 'wc-failed',
		);

		foreach ( $status_counts as $key => $status ) {
			$stats[ $key ] = count(
				wc_get_orders(
					array(
						'limit'  => -1,
						'return' => 'ids',
						'status' => $status,
					)
				)
			);
		}

		return $stats;
	}

	/**
	 * Get available payment methods.
	 *
	 * @since 0.0.1
	 * @return array Payment methods.
	 */
	public static function get_payment_methods() {
		$gateways        = WC()->payment_gateways()->payment_gateways();
		$payment_methods = array();

		foreach ( $gateways as $gateway_id => $gateway ) {
			$payment_methods[ $gateway_id ] = $gateway->get_title();
		}

		return $payment_methods;
	}

	/**
	 * AJAX handler: Get orders.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_orders() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'status'         => isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '',
			'payment_method' => isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '',
			'date_after'     => isset( $_POST['date_after'] ) ? sanitize_text_field( wp_unslash( $_POST['date_after'] ) ) : '',
			'date_before'    => isset( $_POST['date_before'] ) ? sanitize_text_field( wp_unslash( $_POST['date_before'] ) ) : '',
			'customer_id'    => isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : '',
			'limit'          => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'         => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_orders( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete orders.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_orders() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$order_ids = isset( $_POST['order_ids'] ) ? array_map( 'absint', (array) $_POST['order_ids'] ) : array();
		$action    = isset( $_POST['delete_action'] ) ? sanitize_key( $_POST['delete_action'] ) : 'delete';

		if ( empty( $order_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No orders selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->delete_orders( $order_ids, $action );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Anonymize orders.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_anonymize_orders() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$order_ids = isset( $_POST['order_ids'] ) ? array_map( 'absint', (array) $_POST['order_ids'] ) : array();

		if ( empty( $order_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No orders selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->anonymize_orders( $order_ids );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Export orders.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_export_orders() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$order_ids = isset( $_POST['order_ids'] ) ? array_map( 'absint', (array) $_POST['order_ids'] ) : array();
		$format    = isset( $_POST['format'] ) ? sanitize_key( $_POST['format'] ) : 'csv';

		if ( empty( $order_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No orders selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$exporter = new Jharudar_Exporter( $format );
		$filepath = $exporter->export_orders( $order_ids )->save();

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
}
