<?php
/**
 * Coupons module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupons module class.
 *
 * Handles coupon cleanup operations.
 *
 * @since 0.0.1
 */
class Jharudar_Coupons {

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
		add_action( 'wp_ajax_jharudar_get_coupons', array( $this, 'ajax_get_coupons' ) );
		add_action( 'wp_ajax_jharudar_delete_coupons', array( $this, 'ajax_delete_coupons' ) );
		add_action( 'wp_ajax_jharudar_export_coupons', array( $this, 'ajax_export_coupons' ) );
		add_action( 'wp_ajax_jharudar_get_coupon_stats', array( $this, 'ajax_get_coupon_stats' ) );
	}

	/**
	 * Get coupons based on filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Coupons data.
	 */
	public function get_coupons( $filters = array() ) {
		$defaults = array(
			'filter_type' => '',
			'date_before' => '',
			'date_after'  => '',
			'limit'       => 50,
			'offset'      => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'post_type'      => 'shop_coupon',
			'posts_per_page' => $filters['limit'],
			'offset'         => $filters['offset'],
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

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

		// Get coupons.
		$query      = new WP_Query( $args );
		$coupon_ids = $query->posts;

		$filtered_coupons = array();

		foreach ( $coupon_ids as $coupon_post ) {
			$coupon_id = is_object( $coupon_post ) ? $coupon_post->ID : $coupon_post;
			$coupon    = new WC_Coupon( $coupon_id );

			if ( ! $coupon || ! $coupon->get_id() ) {
				continue;
			}

			// Filter by type.
			if ( ! empty( $filters['filter_type'] ) ) {
				switch ( $filters['filter_type'] ) {
					case 'expired':
						if ( ! $this->is_coupon_expired( $coupon ) ) {
							continue 2;
						}
						break;
					case 'unused':
						if ( $coupon->get_usage_count() > 0 ) {
							continue 2;
						}
						break;
					case 'usage_limit_reached':
						if ( ! $this->is_usage_limit_reached( $coupon ) ) {
							continue 2;
						}
						break;
				}
			}

			$filtered_coupons[] = $this->format_coupon_data( $coupon );
		}

		return array(
			'coupons' => $filtered_coupons,
			'total'   => $this->count_coupons( $filters ),
		);
	}

	/**
	 * Count coupons matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Coupon count.
	 */
	public function count_coupons( $filters = array() ) {
		$args = array(
			'post_type'      => 'shop_coupon',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);

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

		$query      = new WP_Query( $args );
		$coupon_ids = $query->posts;

		if ( empty( $filters['filter_type'] ) ) {
			return count( $coupon_ids );
		}

		$count = 0;

		foreach ( $coupon_ids as $coupon_id ) {
			$coupon = new WC_Coupon( $coupon_id );

			if ( ! $coupon || ! $coupon->get_id() ) {
				continue;
			}

			switch ( $filters['filter_type'] ) {
				case 'expired':
					if ( $this->is_coupon_expired( $coupon ) ) {
						++$count;
					}
					break;
				case 'unused':
					if ( 0 === $coupon->get_usage_count() ) {
						++$count;
					}
					break;
				case 'usage_limit_reached':
					if ( $this->is_usage_limit_reached( $coupon ) ) {
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
	 * Check if coupon is expired.
	 *
	 * @since 0.0.1
	 * @param WC_Coupon $coupon Coupon object.
	 * @return bool True if expired.
	 */
	private function is_coupon_expired( $coupon ) {
		$expiry_date = $coupon->get_date_expires();

		if ( ! $expiry_date ) {
			return false;
		}

		return $expiry_date->getTimestamp() < time();
	}

	/**
	 * Check if coupon usage limit is reached.
	 *
	 * @since 0.0.1
	 * @param WC_Coupon $coupon Coupon object.
	 * @return bool True if limit reached.
	 */
	private function is_usage_limit_reached( $coupon ) {
		$usage_limit = $coupon->get_usage_limit();

		if ( ! $usage_limit || $usage_limit <= 0 ) {
			return false;
		}

		return $coupon->get_usage_count() >= $usage_limit;
	}

	/**
	 * Format coupon data for display.
	 *
	 * @since 0.0.1
	 * @param WC_Coupon $coupon Coupon object.
	 * @return array Formatted coupon data.
	 */
	private function format_coupon_data( $coupon ) {
		$expiry_date = $coupon->get_date_expires();
		$usage_limit = $coupon->get_usage_limit();

		// Determine coupon status.
		$status = 'active';
		if ( $this->is_coupon_expired( $coupon ) ) {
			$status = 'expired';
		} elseif ( $this->is_usage_limit_reached( $coupon ) ) {
			$status = 'limit_reached';
		}

		// Format discount amount.
		$discount_type   = $coupon->get_discount_type();
		$discount_amount = $coupon->get_amount();

		if ( 'percent' === $discount_type ) {
			$discount_display = $discount_amount . '%';
		} else {
			$discount_display = wc_price( $discount_amount );
		}

		return array(
			'id'            => $coupon->get_id(),
			'code'          => $coupon->get_code(),
			'discount_type' => wc_get_coupon_type( $discount_type ),
			'amount'        => $discount_display,
			'usage_count'   => $coupon->get_usage_count(),
			'usage_limit'   => $usage_limit ? $usage_limit : __( 'Unlimited', 'jharudar-for-woocommerce' ),
			'expiry_date'   => $expiry_date ? $expiry_date->date_i18n( get_option( 'date_format' ) ) : __( 'Never', 'jharudar-for-woocommerce' ),
			'status'        => $status,
			'date'          => get_the_date( '', $coupon->get_id() ),
			'edit_url'      => get_edit_post_link( $coupon->get_id() ),
		);
	}

	/**
	 * Delete coupons.
	 *
	 * @since 0.0.1
	 * @param array  $coupon_ids Coupon IDs to delete.
	 * @param string $action     Action (delete or trash).
	 * @return array Result data.
	 */
	public function delete_coupons( $coupon_ids, $action = 'delete' ) {
		$coupon_ids = jharudar_sanitize_ids( $coupon_ids );
		$batch_size = jharudar_get_batch_size();
		$deleted    = 0;
		$failed     = 0;

		// If batch processing needed.
		if ( count( $coupon_ids ) > $batch_size ) {
			$this->schedule_batch_delete( $coupon_ids, $action );
			return array(
				'scheduled' => true,
				'total'     => count( $coupon_ids ),
				'message'   => __( 'Coupons are being deleted in the background.', 'jharudar-for-woocommerce' ),
			);
		}

		foreach ( $coupon_ids as $coupon_id ) {
			$coupon = new WC_Coupon( $coupon_id );

			if ( ! $coupon || ! $coupon->get_id() ) {
				++$failed;
				continue;
			}

			// Log activity.
			jharudar_log_activity( $action, 'coupon', $coupon_id );

			if ( 'trash' === $action ) {
				wp_trash_post( $coupon_id );
			} else {
				$coupon->delete( true );
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
	 * @param array  $coupon_ids Coupon IDs.
	 * @param string $action     Action type.
	 * @return void
	 */
	private function schedule_batch_delete( $coupon_ids, $action ) {
		$batch_size = jharudar_get_batch_size();
		$batches    = array_chunk( $coupon_ids, $batch_size );

		foreach ( $batches as $index => $batch ) {
			as_schedule_single_action(
				time() + ( $index * 30 ),
				'jharudar_delete_coupons_batch',
				array(
					'coupon_ids' => $batch,
					'action'     => $action,
				),
				'jharudar'
			);
		}
	}

	/**
	 * Get coupon statistics.
	 *
	 * @since 0.0.1
	 * @return array Stats data.
	 */
	public static function get_statistics() {
		$args = array(
			'post_type'      => 'shop_coupon',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);

		$query      = new WP_Query( $args );
		$coupon_ids = $query->posts;

		$stats = array(
			'total'         => count( $coupon_ids ),
			'expired'       => 0,
			'unused'        => 0,
			'limit_reached' => 0,
		);

		foreach ( $coupon_ids as $coupon_id ) {
			$coupon = new WC_Coupon( $coupon_id );

			if ( ! $coupon || ! $coupon->get_id() ) {
				continue;
			}

			if ( self::check_coupon_expired( $coupon ) ) {
				++$stats['expired'];
			}

			if ( 0 === $coupon->get_usage_count() ) {
				++$stats['unused'];
			}

			if ( self::check_usage_limit_reached( $coupon ) ) {
				++$stats['limit_reached'];
			}
		}

		return $stats;
	}

	/**
	 * Check if coupon is expired (static version).
	 *
	 * @since 0.0.1
	 * @param WC_Coupon $coupon Coupon object.
	 * @return bool True if expired.
	 */
	public static function check_coupon_expired( $coupon ) {
		$expiry_date = $coupon->get_date_expires();
		if ( $expiry_date && $expiry_date->getTimestamp() < time() ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if usage limit reached (static version).
	 *
	 * @since 0.0.1
	 * @param WC_Coupon $coupon Coupon object.
	 * @return bool True if limit reached.
	 */
	public static function check_usage_limit_reached( $coupon ) {
		$usage_limit = $coupon->get_usage_limit();
		$usage_count = $coupon->get_usage_count();
		if ( $usage_limit > 0 && $usage_count >= $usage_limit ) {
			return true;
		}
		return false;
	}

	/**
	 * AJAX handler: Get coupons.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_coupons() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'filter_type' => isset( $_POST['filter_type'] ) ? sanitize_key( $_POST['filter_type'] ) : '',
			'date_before' => isset( $_POST['date_before'] ) ? sanitize_text_field( wp_unslash( $_POST['date_before'] ) ) : '',
			'date_after'  => isset( $_POST['date_after'] ) ? sanitize_text_field( wp_unslash( $_POST['date_after'] ) ) : '',
			'limit'       => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'      => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_coupons( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete coupons.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_coupons() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$coupon_ids = isset( $_POST['coupon_ids'] ) ? array_map( 'absint', (array) $_POST['coupon_ids'] ) : array();
		$action     = isset( $_POST['delete_action'] ) ? sanitize_key( $_POST['delete_action'] ) : 'delete';

		if ( empty( $coupon_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No coupons selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->delete_coupons( $coupon_ids, $action );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Export coupons.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_export_coupons() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$coupon_ids = isset( $_POST['coupon_ids'] ) ? array_map( 'absint', (array) $_POST['coupon_ids'] ) : array();
		$format     = isset( $_POST['format'] ) ? sanitize_key( $_POST['format'] ) : 'csv';

		if ( empty( $coupon_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No coupons selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$exporter = new Jharudar_Exporter( $format );
		$filepath = $exporter->export_coupons( $coupon_ids )->save();

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
	 * AJAX handler: Get coupon stats.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_coupon_stats() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$stats = $this->get_stats();
		wp_send_json_success( $stats );
	}
}
