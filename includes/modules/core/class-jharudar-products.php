<?php
/**
 * Products module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Products module class.
 *
 * Handles product cleanup operations.
 *
 * @since 0.0.1
 */
class Jharudar_Products {

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
		add_action( 'wp_ajax_jharudar_get_products', array( $this, 'ajax_get_products' ) );
		add_action( 'wp_ajax_jharudar_delete_products', array( $this, 'ajax_delete_products' ) );
		add_action( 'wp_ajax_jharudar_export_products', array( $this, 'ajax_export_products' ) );
		add_action( 'wp_ajax_jharudar_get_orphaned_images', array( $this, 'ajax_get_orphaned_images' ) );
		add_action( 'wp_ajax_jharudar_delete_orphaned_images', array( $this, 'ajax_delete_orphaned_images' ) );
	}

	/**
	 * Get products based on filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Products data.
	 */
	public function get_products( $filters = array() ) {
		$defaults = array(
			'category'     => '',
			'status'       => '',
			'stock_status' => '',
			'product_type' => '',
			'date_before'  => '',
			'date_after'   => '',
			'limit'        => 50,
			'offset'       => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => $filters['limit'],
			'offset'         => $filters['offset'],
			'post_status'    => 'any',
			'fields'         => 'ids',
		);

		// Filter by status.
		if ( ! empty( $filters['status'] ) ) {
			$args['post_status'] = sanitize_key( $filters['status'] );
		}

		// Filter by category.
		if ( ! empty( $filters['category'] ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => absint( $filters['category'] ),
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

		$query       = new WP_Query( $args );
		$product_ids = $query->posts;

		// Filter by stock status and product type (post-query filtering).
		$filtered_products = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			// Filter by stock status.
			if ( ! empty( $filters['stock_status'] ) && $product->get_stock_status() !== $filters['stock_status'] ) {
				continue;
			}

			// Filter by product type.
			if ( ! empty( $filters['product_type'] ) && $product->get_type() !== $filters['product_type'] ) {
				continue;
			}

			$filtered_products[] = $this->format_product_data( $product );
		}

		return array(
			'products' => $filtered_products,
			'total'    => $this->count_products( $filters ),
		);
	}

	/**
	 * Count products matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Product count.
	 */
	public function count_products( $filters = array() ) {
		$filters['limit']  = -1;
		$filters['offset'] = 0;

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		);

		if ( ! empty( $filters['status'] ) ) {
			$args['post_status'] = sanitize_key( $filters['status'] );
		}

		if ( ! empty( $filters['category'] ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => absint( $filters['category'] ),
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

		$query       = new WP_Query( $args );
		$product_ids = $query->posts;

		// Count with post-query filters.
		$count = 0;
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			if ( ! empty( $filters['stock_status'] ) && $product->get_stock_status() !== $filters['stock_status'] ) {
				continue;
			}

			if ( ! empty( $filters['product_type'] ) && $product->get_type() !== $filters['product_type'] ) {
				continue;
			}

			++$count;
		}

		return $count;
	}

	/**
	 * Format product data for display.
	 *
	 * @since 0.0.1
	 * @param WC_Product $product Product object.
	 * @return array Formatted product data.
	 */
	private function format_product_data( $product ) {
		$categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );

		return array(
			'id'           => $product->get_id(),
			'name'         => $product->get_name(),
			'sku'          => $product->get_sku(),
			'status'       => $product->get_status(),
			'price'        => $product->get_price() ? wc_price( $product->get_price() ) : '-',
			'stock_status' => wc_get_stock_html( $product ),
			'type'         => $product->get_type(),
			'categories'   => is_array( $categories ) ? implode( ', ', $categories ) : '',
			'date'         => get_the_date( '', $product->get_id() ),
			'edit_url'     => get_edit_post_link( $product->get_id() ),
		);
	}

	/**
	 * Delete products.
	 *
	 * @since 0.0.1
	 * @param array  $product_ids Product IDs to delete.
	 * @param string $action      Action (delete or trash).
	 * @param bool   $delete_images Whether to delete product images.
	 * @return array Result data.
	 */
	public function delete_products( $product_ids, $action = 'delete', $delete_images = false ) {
		$product_ids = jharudar_sanitize_ids( $product_ids );
		$batch_size  = jharudar_get_batch_size();
		$deleted     = 0;
		$failed      = 0;

		// If batch processing needed.
		if ( count( $product_ids ) > $batch_size ) {
			$this->schedule_batch_delete( $product_ids, $action, $delete_images );
			return array(
				'scheduled' => true,
				'total'     => count( $product_ids ),
				'message'   => __( 'Products are being deleted in the background.', 'jharudar-for-woocommerce' ),
			);
		}

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				++$failed;
				continue;
			}

			// Delete images if requested.
			if ( $delete_images ) {
				$this->delete_product_images( $product );
			}

			// Log activity.
			jharudar_log_activity( $action, 'product', $product_id );

			if ( 'trash' === $action ) {
				$product->set_status( 'trash' );
				$product->save();
			} else {
				$product->delete( true );
			}

			++$deleted;
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
		);
	}

	/**
	 * Delete product images.
	 *
	 * @since 0.0.1
	 * @param WC_Product $product Product object.
	 * @return void
	 */
	private function delete_product_images( $product ) {
		// Delete featured image.
		$thumbnail_id = $product->get_image_id();
		if ( $thumbnail_id ) {
			wp_delete_attachment( $thumbnail_id, true );
		}

		// Delete gallery images.
		$gallery_ids = $product->get_gallery_image_ids();
		foreach ( $gallery_ids as $gallery_id ) {
			wp_delete_attachment( $gallery_id, true );
		}
	}

	/**
	 * Schedule batch delete using Action Scheduler.
	 *
	 * @since 0.0.1
	 * @param array  $product_ids   Product IDs.
	 * @param string $action        Action type.
	 * @param bool   $delete_images Delete images flag.
	 * @return void
	 */
	private function schedule_batch_delete( $product_ids, $action, $delete_images ) {
		$batch_size = jharudar_get_batch_size();
		$batches    = array_chunk( $product_ids, $batch_size );

		foreach ( $batches as $index => $batch ) {
			as_schedule_single_action(
				time() + ( $index * 30 ),
				'jharudar_delete_products_batch',
				array(
					'product_ids'   => $batch,
					'action'        => $action,
					'delete_images' => $delete_images,
				),
				'jharudar'
			);
		}
	}

	/**
	 * Get orphaned product images.
	 *
	 * @since 0.0.1
	 * @param int $limit  Number of images to retrieve.
	 * @param int $offset Offset for pagination.
	 * @return array Orphaned images data.
	 */
	public function get_orphaned_images( $limit = 50, $offset = 0 ) {
		global $wpdb;

		// Get all attachment IDs used by products.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$used_images = $wpdb->get_col(
			"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
			WHERE meta_key IN ('_thumbnail_id', '_product_image_gallery')
			AND meta_value != ''"
		);
		// phpcs:enable

		// Expand gallery images (comma-separated IDs).
		$all_used_ids = array();
		foreach ( $used_images as $value ) {
			$ids = explode( ',', $value );
			foreach ( $ids as $id ) {
				$id = absint( trim( $id ) );
				if ( $id > 0 ) {
					$all_used_ids[] = $id;
				}
			}
		}

		$all_used_ids = array_unique( $all_used_ids );

		// Get product images that are not in use.
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'post_mime_type' => 'image',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_wp_attached_file',
					'value'   => 'woocommerce_uploads',
					'compare' => 'NOT LIKE',
				),
			),
		);

		// Exclude used images (post__not_in is appropriate here to find orphaned/unused images).
		if ( ! empty( $all_used_ids ) ) {
			$args['post__not_in'] = $all_used_ids; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		}

		// Only get images that were uploaded in product context.
		$args['post_parent__in'] = $this->get_all_product_ids();

		$query  = new WP_Query( $args );
		$images = array();

		foreach ( $query->posts as $attachment ) {
			$images[] = array(
				'id'        => $attachment->ID,
				'title'     => $attachment->post_title,
				'url'       => wp_get_attachment_url( $attachment->ID ),
				'thumbnail' => wp_get_attachment_image_url( $attachment->ID, 'thumbnail' ),
				'size'      => size_format( filesize( get_attached_file( $attachment->ID ) ) ),
				'date'      => get_the_date( '', $attachment->ID ),
			);
		}

		return array(
			'images' => $images,
			'total'  => $this->count_orphaned_images(),
		);
	}

	/**
	 * Count orphaned images.
	 *
	 * @since 0.0.1
	 * @return int Orphaned image count.
	 */
	public function count_orphaned_images() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$used_images = $wpdb->get_col(
			"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
			WHERE meta_key IN ('_thumbnail_id', '_product_image_gallery')
			AND meta_value != ''"
		);
		// phpcs:enable

		$all_used_ids = array();
		foreach ( $used_images as $value ) {
			$ids = explode( ',', $value );
			foreach ( $ids as $id ) {
				$id = absint( trim( $id ) );
				if ( $id > 0 ) {
					$all_used_ids[] = $id;
				}
			}
		}

		$all_used_ids = array_unique( $all_used_ids );

		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'post_mime_type' => 'image',
			'fields'         => 'ids',
		);

		// Exclude used images (post__not_in is appropriate here to find orphaned/unused images).
		if ( ! empty( $all_used_ids ) ) {
			$args['post__not_in'] = $all_used_ids; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		}

		$product_ids = $this->get_all_product_ids();
		if ( ! empty( $product_ids ) ) {
			$args['post_parent__in'] = $product_ids;
		} else {
			return 0;
		}

		$query = new WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * Get all product IDs.
	 *
	 * @since 0.0.1
	 * @return array Product IDs.
	 */
	private function get_all_product_ids() {
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		);

		$query = new WP_Query( $args );
		return $query->posts;
	}

	/**
	 * Delete orphaned images.
	 *
	 * @since 0.0.1
	 * @param array $image_ids Image IDs to delete.
	 * @return array Result data.
	 */
	public function delete_orphaned_images( $image_ids ) {
		$image_ids = jharudar_sanitize_ids( $image_ids );
		$deleted   = 0;
		$failed    = 0;

		foreach ( $image_ids as $image_id ) {
			$result = wp_delete_attachment( $image_id, true );

			if ( $result ) {
				jharudar_log_activity( 'delete', 'orphaned_image', $image_id );
				++$deleted;
			} else {
				++$failed;
			}
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
		);
	}

	/**
	 * AJAX handler: Get products.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_products() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'category'     => isset( $_POST['category'] ) ? absint( $_POST['category'] ) : '',
			'status'       => isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '',
			'stock_status' => isset( $_POST['stock_status'] ) ? sanitize_key( $_POST['stock_status'] ) : '',
			'product_type' => isset( $_POST['product_type'] ) ? sanitize_key( $_POST['product_type'] ) : '',
			'date_before'  => isset( $_POST['date_before'] ) ? sanitize_text_field( wp_unslash( $_POST['date_before'] ) ) : '',
			'date_after'   => isset( $_POST['date_after'] ) ? sanitize_text_field( wp_unslash( $_POST['date_after'] ) ) : '',
			'limit'        => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'       => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_products( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete products.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_products() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$product_ids   = isset( $_POST['product_ids'] ) ? array_map( 'absint', (array) $_POST['product_ids'] ) : array();
		$action        = isset( $_POST['delete_action'] ) ? sanitize_key( $_POST['delete_action'] ) : 'delete';
		$delete_images = isset( $_POST['delete_images'] ) && 'true' === $_POST['delete_images'];

		if ( empty( $product_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No products selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->delete_products( $product_ids, $action, $delete_images );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Export products.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_export_products() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$product_ids = isset( $_POST['product_ids'] ) ? array_map( 'absint', (array) $_POST['product_ids'] ) : array();
		$format      = isset( $_POST['format'] ) ? sanitize_key( $_POST['format'] ) : 'csv';

		if ( empty( $product_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No products selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$exporter = new Jharudar_Exporter( $format );
		$filepath = $exporter->export_products( $product_ids )->save();

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
	 * AJAX handler: Get orphaned images.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_orphaned_images() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$limit  = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50;
		$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;

		$result = $this->get_orphaned_images( $limit, $offset );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete orphaned images.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_orphaned_images() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$image_ids = isset( $_POST['image_ids'] ) ? array_map( 'absint', (array) $_POST['image_ids'] ) : array();

		if ( empty( $image_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No images selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->delete_orphaned_images( $image_ids );
		wp_send_json_success( $result );
	}
}
