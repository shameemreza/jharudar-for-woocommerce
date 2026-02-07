<?php
/**
 * Product Vendors module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Vendors module class.
 *
 * Handles WooCommerce Product Vendors cleanup operations.
 * Requires WooCommerce Product Vendors plugin to be active.
 *
 * @since 0.0.1
 */
class Jharudar_Vendors {

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
		add_action( 'wp_ajax_jharudar_get_vendors', array( $this, 'ajax_get_vendors' ) );
		add_action( 'wp_ajax_jharudar_delete_vendors', array( $this, 'ajax_delete_vendors' ) );
		add_action( 'wp_ajax_jharudar_get_vendor_stats', array( $this, 'ajax_get_vendor_stats' ) );
		add_action( 'wp_ajax_jharudar_delete_vendor_commissions', array( $this, 'ajax_delete_vendor_commissions' ) );
	}

	/**
	 * Check if WooCommerce Product Vendors is active.
	 *
	 * @since 0.0.1
	 * @return bool True if active.
	 */
	public static function is_active() {
		return class_exists( 'WC_Product_Vendors' );
	}

	/**
	 * Get vendors based on filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Vendors data.
	 */
	public function get_vendors( $filters = array() ) {
		if ( ! self::is_active() ) {
			return array(
				'vendors' => array(),
				'total'   => 0,
			);
		}

		$defaults = array(
			'filter_type' => '',
			'limit'       => 50,
			'offset'      => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'taxonomy'   => 'wcpv_product_vendors',
			'hide_empty' => false,
			'number'     => $filters['limit'],
			'offset'     => $filters['offset'],
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		$terms   = get_terms( $args );
		$vendors = array();

		if ( is_wp_error( $terms ) ) {
			return array(
				'vendors' => array(),
				'total'   => 0,
			);
		}

		foreach ( $terms as $term ) {
			$vendor_data = $this->format_vendor_data( $term );

			// Apply filter.
			if ( ! empty( $filters['filter_type'] ) ) {
				switch ( $filters['filter_type'] ) {
					case 'no_products':
						if ( $vendor_data['product_count'] > 0 ) {
							continue 2;
						}
						break;
					case 'no_commissions':
						if ( $vendor_data['commission_count'] > 0 ) {
							continue 2;
						}
						break;
				}
			}

			$vendors[] = $vendor_data;
		}

		// When no filter_type is set, use the total count from get_terms.
		// When filter_type is set, the total is the count of filtered results.
		$total = count( $vendors );
		if ( empty( $filters['filter_type'] ) ) {
			// Get actual total count (ignoring pagination).
			$total_args = $args;
			unset( $total_args['number'], $total_args['offset'] );
			$total_args['fields'] = 'count';
			$total_count          = get_terms( $total_args );
			$total                = is_wp_error( $total_count ) ? count( $vendors ) : (int) $total_count;
		} else {
			$total = $this->count_vendors( $filters );
		}

		return array(
			'vendors' => $vendors,
			'total'   => $total,
		);
	}

	/**
	 * Count vendors matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Vendor count.
	 */
	public function count_vendors( $filters = array() ) {
		if ( ! self::is_active() ) {
			return 0;
		}

		$args = array(
			'taxonomy'   => 'wcpv_product_vendors',
			'hide_empty' => false,
			'fields'     => 'ids',
		);

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return 0;
		}

		if ( empty( $filters['filter_type'] ) ) {
			return count( $terms );
		}

		$count = 0;
		foreach ( $terms as $term_id ) {
			$term = get_term( $term_id, 'wcpv_product_vendors' );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}

			$vendor_data = $this->format_vendor_data( $term );

			switch ( $filters['filter_type'] ) {
				case 'no_products':
					if ( 0 === $vendor_data['product_count'] ) {
						++$count;
					}
					break;
				case 'no_commissions':
					if ( 0 === $vendor_data['commission_count'] ) {
						++$count;
					}
					break;
				default:
					++$count;
			}
		}

		return $count;
	}

	/**
	 * Format vendor data for display.
	 *
	 * @since 0.0.1
	 * @param WP_Term $term Vendor term object.
	 * @return array Formatted vendor data.
	 */
	private function format_vendor_data( $term ) {
		// Use the official utility method to handle both legacy and migrated admin storage.
		if ( class_exists( 'WC_Product_Vendors_Utils' ) && method_exists( 'WC_Product_Vendors_Utils', 'get_vendor_data_by_id' ) ) {
			$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_by_id( $term->term_id );
		} else {
			$vendor_data = get_term_meta( $term->term_id, 'vendor_data', true );
		}

		$email  = '';
		$admins = array();

		if ( is_array( $vendor_data ) ) {
			$email  = isset( $vendor_data['email'] ) ? $vendor_data['email'] : '';
			$admins = isset( $vendor_data['admins'] ) ? (array) $vendor_data['admins'] : array();
		}

		// Count products assigned to this vendor.
		$product_count = $term->count;

		// Count commissions.
		$commission_count = $this->count_vendor_commissions( $term->term_id );

		$admin_names = array();
		foreach ( $admins as $admin_id ) {
			$user = get_user_by( 'id', $admin_id );
			if ( $user ) {
				$admin_names[] = $user->display_name;
			}
		}

		return array(
			'id'               => $term->term_id,
			'name'             => $term->name,
			'slug'             => $term->slug,
			'email'            => $email,
			'product_count'    => $product_count,
			'commission_count' => $commission_count,
			'admins'           => implode( ', ', $admin_names ),
			'edit_url'         => get_edit_term_link( $term->term_id, 'wcpv_product_vendors' ),
		);
	}

	/**
	 * Count commissions for a vendor.
	 *
	 * @since 0.0.1
	 * @param int $vendor_id Vendor term ID.
	 * @return int Commission count.
	 */
	private function count_vendor_commissions( $vendor_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wcpv_commissions';

		// Check if the commissions table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $table_name )
			)
		);

		if ( ! $table_exists ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE vendor_id = %d',
				$table_name,
				$vendor_id
			)
		);

		return (int) $count;
	}

	/**
	 * Delete vendors.
	 *
	 * @since 0.0.1
	 * @param array $vendor_ids Vendor term IDs to delete.
	 * @return array Result data.
	 */
	public function delete_vendors( $vendor_ids ) {
		if ( ! self::is_active() ) {
			return array(
				'deleted' => 0,
				'failed'  => 0,
			);
		}

		$vendor_ids        = jharudar_sanitize_ids( $vendor_ids );
		$deleted           = 0;
		$failed            = 0;
		$products_affected = 0;

		foreach ( $vendor_ids as $vendor_id ) {
			$term = get_term( $vendor_id, 'wcpv_product_vendors' );

			if ( ! $term || is_wp_error( $term ) ) {
				++$failed;
				continue;
			}

			// Track affected products.
			$products_affected += (int) $term->count;

			// Log activity.
			jharudar_log_activity( 'delete', 'vendor', $vendor_id );

			// Delete commissions for this vendor.
			$this->delete_commissions_for_vendor( $vendor_id );

			// Clean up vendor-specific transient caches.
			delete_transient( 'wcpv_vendor_data_' . $vendor_id );
			delete_transient( 'wcpv_vendor_products_' . $vendor_id );
			delete_transient( 'wcpv_vendor_rating_' . $vendor_id );

			// Delete the vendor term (triggers pre_delete_term hook which
			// cleans up _wcpv_active_vendor user meta automatically).
			$result = wp_delete_term( $vendor_id, 'wcpv_product_vendors' );

			if ( is_wp_error( $result ) ) {
				++$failed;
			} else {
				++$deleted;
			}
		}

		$message = sprintf(
			/* translators: %d: Number of vendors deleted. */
			__( 'Deleted %d vendor(s).', 'jharudar-for-woocommerce' ),
			$deleted
		);

		if ( $products_affected > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %d: Number of products now unassigned. */
				__( '%d product(s) are now unassigned from any vendor.', 'jharudar-for-woocommerce' ),
				$products_affected
			);
		}

		return array(
			'deleted'           => $deleted,
			'failed'            => $failed,
			'products_affected' => $products_affected,
			'message'           => $message,
		);
	}

	/**
	 * Delete commissions for a vendor.
	 *
	 * @since 0.0.1
	 * @param int $vendor_id Vendor term ID.
	 * @return int Number of deleted commissions.
	 */
	private function delete_commissions_for_vendor( $vendor_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wcpv_commissions';

		// Check if the commissions table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $table_name )
			)
		);

		if ( ! $table_exists ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			$table_name,
			array( 'vendor_id' => $vendor_id ),
			array( '%d' )
		);

		return $deleted ? $deleted : 0;
	}

	/**
	 * Get vendor statistics.
	 *
	 * @since 0.0.1
	 * @return array Stats data.
	 */
	public static function get_statistics() {
		if ( ! self::is_active() ) {
			return array(
				'total'          => 0,
				'no_products'    => 0,
				'no_commissions' => 0,
			);
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'wcpv_product_vendors',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array(
				'total'          => 0,
				'no_products'    => 0,
				'no_commissions' => 0,
			);
		}

		$stats = array(
			'total'          => count( $terms ),
			'no_products'    => 0,
			'no_commissions' => 0,
		);

		foreach ( $terms as $term ) {
			if ( 0 === $term->count ) {
				++$stats['no_products'];
			}

			if ( 0 === self::count_commissions_static( $term->term_id ) ) {
				++$stats['no_commissions'];
			}
		}

		return $stats;
	}

	/**
	 * Static helper to count commissions for a vendor (avoids re-instantiation).
	 *
	 * @since 0.0.1
	 * @param int $vendor_id Vendor term ID.
	 * @return int Commission count.
	 */
	private static function count_commissions_static( $vendor_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wcpv_commissions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $table_name )
			)
		);

		if ( ! $table_exists ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE vendor_id = %d',
				$table_name,
				$vendor_id
			)
		);

		return (int) $count;
	}

	/**
	 * AJAX handler: Get vendors.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_vendors() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Product Vendors is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'filter_type' => isset( $_POST['filter_type'] ) ? sanitize_key( $_POST['filter_type'] ) : '',
			'limit'       => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'      => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_vendors( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete vendors.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_vendors() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Product Vendors is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$vendor_ids = isset( $_POST['vendor_ids'] ) ? array_map( 'absint', (array) $_POST['vendor_ids'] ) : array();

		if ( empty( $vendor_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No vendors selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->delete_vendors( $vendor_ids );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete vendor commissions.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_vendor_commissions() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		if ( ! self::is_active() ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce Product Vendors is not active.', 'jharudar-for-woocommerce' ) ) );
		}

		$vendor_ids = isset( $_POST['vendor_ids'] ) ? array_map( 'absint', (array) $_POST['vendor_ids'] ) : array();

		if ( empty( $vendor_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No vendors selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$total_deleted = 0;
		foreach ( $vendor_ids as $vendor_id ) {
			jharudar_log_activity( 'delete', 'vendor_commissions', $vendor_id );
			$total_deleted += $this->delete_commissions_for_vendor( $vendor_id );
		}

		wp_send_json_success(
			array(
				'deleted' => $total_deleted,
				'message' => sprintf(
					/* translators: %d: Number of commission records deleted. */
					__( 'Deleted %d commission record(s).', 'jharudar-for-woocommerce' ),
					$total_deleted
				),
			)
		);
	}

	/**
	 * AJAX handler: Get vendor stats.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_vendor_stats() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$stats = self::get_statistics();
		wp_send_json_success( $stats );
	}
}
