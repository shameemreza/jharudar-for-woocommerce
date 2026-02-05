<?php
/**
 * Customers module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customers module class.
 *
 * Handles customer cleanup operations.
 *
 * @since 0.0.1
 */
class Jharudar_Customers {

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
		add_action( 'wp_ajax_jharudar_get_customers', array( $this, 'ajax_get_customers' ) );
		add_action( 'wp_ajax_jharudar_delete_customers', array( $this, 'ajax_delete_customers' ) );
		add_action( 'wp_ajax_jharudar_anonymize_customers', array( $this, 'ajax_anonymize_customers' ) );
		add_action( 'wp_ajax_jharudar_export_customers', array( $this, 'ajax_export_customers' ) );
	}

	/**
	 * Get customers based on filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Customers data.
	 */
	public function get_customers( $filters = array() ) {
		$defaults = array(
			'filter_type'    => '',        // zero_orders, inactive, by_date.
			'inactive_months' => 12,       // For inactive filter.
			'date_before'    => '',        // Registration date before.
			'role'           => 'customer',
			'limit'          => 50,
			'offset'         => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'role'   => $filters['role'],
			'number' => $filters['limit'],
			'offset' => $filters['offset'],
		);

		// Filter by registration date.
		if ( ! empty( $filters['date_before'] ) ) {
			$args['date_query'] = array(
				array(
					'before'    => sanitize_text_field( $filters['date_before'] ),
					'inclusive' => true,
				),
			);
		}

		$users      = get_users( $args );
		$customers  = array();

		foreach ( $users as $user ) {
			// Skip admins and shop managers.
			if ( user_can( $user->ID, 'manage_woocommerce' ) ) {
				continue;
			}

			$customer = new WC_Customer( $user->ID );

			// Apply filter type.
			if ( 'zero_orders' === $filters['filter_type'] ) {
				if ( $customer->get_order_count() > 0 ) {
					continue;
				}
			} elseif ( 'inactive' === $filters['filter_type'] ) {
				$last_order = $customer->get_last_order();
				if ( $last_order ) {
					$last_order_date = $last_order->get_date_created();
					if ( $last_order_date ) {
						$months_ago = strtotime( '-' . absint( $filters['inactive_months'] ) . ' months' );
						if ( $last_order_date->getTimestamp() > $months_ago ) {
							continue; // Has recent order, skip.
						}
					}
				}
			}

			$customers[] = $this->format_customer_data( $customer, $user );
		}

		return array(
			'customers' => $customers,
			'total'     => $this->count_customers( $filters ),
		);
	}

	/**
	 * Count customers matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Customer count.
	 */
	public function count_customers( $filters = array() ) {
		$args = array(
			'role'   => isset( $filters['role'] ) ? $filters['role'] : 'customer',
			'number' => -1,
			'fields' => 'ID',
		);

		if ( ! empty( $filters['date_before'] ) ) {
			$args['date_query'] = array(
				array(
					'before'    => sanitize_text_field( $filters['date_before'] ),
					'inclusive' => true,
				),
			);
		}

		$users = get_users( $args );
		$count = 0;

		$filter_type     = isset( $filters['filter_type'] ) ? $filters['filter_type'] : '';
		$inactive_months = isset( $filters['inactive_months'] ) ? absint( $filters['inactive_months'] ) : 12;

		foreach ( $users as $user_id ) {
			// Skip admins and shop managers.
			if ( user_can( $user_id, 'manage_woocommerce' ) ) {
				continue;
			}

			$customer = new WC_Customer( $user_id );

			if ( 'zero_orders' === $filter_type ) {
				if ( $customer->get_order_count() > 0 ) {
					continue;
				}
			} elseif ( 'inactive' === $filter_type ) {
				$last_order = $customer->get_last_order();
				if ( $last_order ) {
					$last_order_date = $last_order->get_date_created();
					if ( $last_order_date ) {
						$months_ago = strtotime( '-' . $inactive_months . ' months' );
						if ( $last_order_date->getTimestamp() > $months_ago ) {
							continue;
						}
					}
				}
			}

			++$count;
		}

		return $count;
	}

	/**
	 * Format customer data for display.
	 *
	 * @since 0.0.1
	 * @param WC_Customer $customer Customer object.
	 * @param WP_User     $user     User object.
	 * @return array Formatted customer data.
	 */
	private function format_customer_data( $customer, $user ) {
		$last_order      = $customer->get_last_order();
		$last_order_date = '-';

		if ( $last_order && $last_order->get_date_created() ) {
			$last_order_date = $last_order->get_date_created()->date_i18n( get_option( 'date_format' ) );
		}

		$name = trim( $customer->get_first_name() . ' ' . $customer->get_last_name() );
		if ( empty( $name ) ) {
			$name = $customer->get_display_name();
		}

		return array(
			'id'              => $customer->get_id(),
			'name'            => $name,
			'email'           => $customer->get_email(),
			'date_registered' => $user->user_registered ? date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) : '-',
			'orders_count'    => $customer->get_order_count(),
			'total_spent'     => wc_price( $customer->get_total_spent() ),
			'last_order_date' => $last_order_date,
			'role'            => implode( ', ', $user->roles ),
			'edit_url'        => get_edit_user_link( $customer->get_id() ),
		);
	}

	/**
	 * Delete customers.
	 *
	 * @since 0.0.1
	 * @param array $customer_ids Customer IDs to delete.
	 * @param int   $reassign_to  User ID to reassign content to.
	 * @return array Result data.
	 */
	public function delete_customers( $customer_ids, $reassign_to = 0 ) {
		$customer_ids = jharudar_sanitize_ids( $customer_ids );
		$batch_size   = jharudar_get_batch_size();
		$deleted      = 0;
		$failed       = 0;
		$skipped      = 0;

		// If batch processing needed.
		if ( count( $customer_ids ) > $batch_size ) {
			$this->schedule_batch_delete( $customer_ids, $reassign_to );
			return array(
				'scheduled' => true,
				'total'     => count( $customer_ids ),
				'message'   => __( 'Customers are being deleted in the background.', 'jharudar-for-woocommerce' ),
			);
		}

		foreach ( $customer_ids as $customer_id ) {
			$user = get_user_by( 'id', $customer_id );

			if ( ! $user ) {
				++$failed;
				continue;
			}

			// Skip administrators and shop managers.
			if ( user_can( $customer_id, 'manage_woocommerce' ) ) {
				++$skipped;
				continue;
			}

			// Log activity.
			jharudar_log_activity( 'delete', 'customer', $customer_id );

			// Delete the user.
			if ( $reassign_to > 0 ) {
				wp_delete_user( $customer_id, $reassign_to );
			} else {
				wp_delete_user( $customer_id );
			}

			++$deleted;
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
			'skipped' => $skipped,
		);
	}

	/**
	 * Anonymize customers.
	 *
	 * @since 0.0.1
	 * @param array $customer_ids Customer IDs to anonymize.
	 * @return array Result data.
	 */
	public function anonymize_customers( $customer_ids ) {
		$customer_ids = jharudar_sanitize_ids( $customer_ids );
		$anonymized   = 0;
		$failed       = 0;
		$skipped      = 0;

		foreach ( $customer_ids as $customer_id ) {
			$user = get_user_by( 'id', $customer_id );

			if ( ! $user ) {
				++$failed;
				continue;
			}

			// Skip administrators and shop managers.
			if ( user_can( $customer_id, 'manage_woocommerce' ) ) {
				++$skipped;
				continue;
			}

			// Anonymize customer data.
			$this->anonymize_customer( $customer_id );

			// Log activity.
			jharudar_log_activity( 'anonymize', 'customer', $customer_id );

			++$anonymized;
		}

		return array(
			'anonymized' => $anonymized,
			'failed'     => $failed,
			'skipped'    => $skipped,
		);
	}

	/**
	 * Anonymize a single customer.
	 *
	 * @since 0.0.1
	 * @param int $customer_id Customer ID.
	 * @return void
	 */
	private function anonymize_customer( $customer_id ) {
		$anonymous_email = 'anonymized-' . $customer_id . '@example.com';

		// Update user record.
		wp_update_user(
			array(
				'ID'           => $customer_id,
				'user_email'   => $anonymous_email,
				'display_name' => __( 'Anonymized User', 'jharudar-for-woocommerce' ),
				'first_name'   => '',
				'last_name'    => '',
			)
		);

		// Clear WooCommerce customer meta.
		$meta_keys = array(
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_postcode',
			'billing_state',
			'billing_country',
			'billing_phone',
			'billing_email',
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_postcode',
			'shipping_state',
			'shipping_country',
			'description',
		);

		foreach ( $meta_keys as $key ) {
			delete_user_meta( $customer_id, $key );
		}

		// Also update billing_email to the anonymized version.
		update_user_meta( $customer_id, 'billing_email', $anonymous_email );
	}

	/**
	 * Schedule batch delete using Action Scheduler.
	 *
	 * @since 0.0.1
	 * @param array $customer_ids Customer IDs.
	 * @param int   $reassign_to  User ID to reassign content to.
	 * @return void
	 */
	private function schedule_batch_delete( $customer_ids, $reassign_to ) {
		$batch_size = jharudar_get_batch_size();
		$batches    = array_chunk( $customer_ids, $batch_size );

		foreach ( $batches as $index => $batch ) {
			as_schedule_single_action(
				time() + ( $index * 30 ),
				'jharudar_delete_customers_batch',
				array(
					'customer_ids' => $batch,
					'reassign_to'  => $reassign_to,
				),
				'jharudar'
			);
		}
	}

	/**
	 * Get customer statistics.
	 *
	 * @since 0.0.1
	 * @return array Statistics.
	 */
	public static function get_statistics() {
		$total_customers = count(
			get_users(
				array(
					'role'   => 'customer',
					'fields' => 'ID',
				)
			)
		);

		// Count zero-order customers.
		$zero_order_count = 0;
		$inactive_count   = 0;

		$customers = get_users(
			array(
				'role'   => 'customer',
				'fields' => 'ID',
			)
		);

		$months_ago = strtotime( '-12 months' );

		foreach ( $customers as $customer_id ) {
			if ( user_can( $customer_id, 'manage_woocommerce' ) ) {
				continue;
			}

			$customer = new WC_Customer( $customer_id );

			if ( 0 === $customer->get_order_count() ) {
				++$zero_order_count;
			}

			$last_order = $customer->get_last_order();
			if ( $last_order && $last_order->get_date_created() ) {
				if ( $last_order->get_date_created()->getTimestamp() < $months_ago ) {
					++$inactive_count;
				}
			} elseif ( 0 === $customer->get_order_count() ) {
				// If no orders, check registration date.
				$user = get_user_by( 'id', $customer_id );
				if ( $user && strtotime( $user->user_registered ) < $months_ago ) {
					++$inactive_count;
				}
			}
		}

		return array(
			'total'       => $total_customers,
			'zero_orders' => $zero_order_count,
			'inactive'    => $inactive_count,
		);
	}

	/**
	 * AJAX handler: Get customers.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_customers() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'filter_type'     => isset( $_POST['filter_type'] ) ? sanitize_key( $_POST['filter_type'] ) : '',
			'inactive_months' => isset( $_POST['inactive_months'] ) ? absint( $_POST['inactive_months'] ) : 12,
			'date_before'     => isset( $_POST['date_before'] ) ? sanitize_text_field( wp_unslash( $_POST['date_before'] ) ) : '',
			'role'            => isset( $_POST['role'] ) ? sanitize_key( $_POST['role'] ) : 'customer',
			'limit'           => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'          => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_customers( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete customers.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_customers() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$customer_ids = isset( $_POST['customer_ids'] ) ? array_map( 'absint', (array) $_POST['customer_ids'] ) : array();
		$reassign_to  = isset( $_POST['reassign_to'] ) ? absint( $_POST['reassign_to'] ) : 0;

		if ( empty( $customer_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No customers selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->delete_customers( $customer_ids, $reassign_to );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Anonymize customers.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_anonymize_customers() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$customer_ids = isset( $_POST['customer_ids'] ) ? array_map( 'absint', (array) $_POST['customer_ids'] ) : array();

		if ( empty( $customer_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No customers selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->anonymize_customers( $customer_ids );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Export customers.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_export_customers() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$customer_ids = isset( $_POST['customer_ids'] ) ? array_map( 'absint', (array) $_POST['customer_ids'] ) : array();
		$format       = isset( $_POST['format'] ) ? sanitize_key( $_POST['format'] ) : 'csv';

		if ( empty( $customer_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No customers selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$exporter = new Jharudar_Exporter( $format );
		$filepath = $exporter->export_customers( $customer_ids )->save();

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
