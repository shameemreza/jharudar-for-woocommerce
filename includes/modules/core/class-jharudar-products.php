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
		add_action( 'wp_ajax_jharudar_get_duplicate_products', array( $this, 'ajax_get_duplicate_products' ) );
		add_action( 'wp_ajax_jharudar_delete_duplicate_products', array( $this, 'ajax_delete_duplicate_products' ) );
		add_action( 'wp_ajax_jharudar_export_duplicate_products', array( $this, 'ajax_export_duplicate_products' ) );
		add_action( 'wp_ajax_jharudar_check_product_dependencies', array( $this, 'ajax_check_product_dependencies' ) );
		add_action( 'wp_ajax_jharudar_empty_trash_products', array( $this, 'ajax_empty_trash' ) );

		// 301 redirect template_redirect hook for deleted duplicates.
		add_action( 'template_redirect', array( $this, 'handle_duplicate_redirects' ) );
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

	/**
	 * Get duplicate products grouped by the matching field value.
	 *
	 * Returns an array of "duplicate sets" – each set contains products
	 * that share the same field value (name, SKU, or slug).
	 *
	 * @since 0.1.0
	 * @param string $match_by Match type: 'name', 'sku', 'slug', or 'normalized_name'.
	 * @return array {
	 *     @type array  $groups Array of duplicate groups, each containing 'value' and 'products'.
	 *     @type int    $total_groups  Number of duplicate sets.
	 *     @type int    $total_products Total duplicate products (across all groups).
	 * }
	 */
	public function get_duplicate_products( $match_by = 'name' ) {
		global $wpdb;

		$groups = array();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		switch ( $match_by ) {
			case 'sku':
				$duplicates = $wpdb->get_results(
					"SELECT pm.meta_value AS match_value, GROUP_CONCAT(pm.post_id ORDER BY p.post_date ASC) AS ids
					FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
					WHERE pm.meta_key = '_sku'
					AND pm.meta_value != ''
					AND p.post_type IN ('product', 'product_variation')
					AND p.post_status != 'trash'
					GROUP BY pm.meta_value
					HAVING COUNT(*) > 1
					ORDER BY COUNT(*) DESC"
				);
				break;

			case 'slug':
				// Match by post_name (slug), stripping trailing -N suffixes to find copies.
				$duplicates = $wpdb->get_results(
					"SELECT
						CASE
							WHEN post_name REGEXP '-[0-9]+$'
							THEN TRIM(TRAILING CONCAT('-', SUBSTRING_INDEX(post_name, '-', -1)) FROM post_name)
							ELSE post_name
						END AS match_value,
						GROUP_CONCAT(ID ORDER BY post_date ASC) AS ids
					FROM {$wpdb->posts}
					WHERE post_type = 'product'
					AND post_status != 'trash'
					GROUP BY match_value
					HAVING COUNT(*) > 1
					ORDER BY COUNT(*) DESC"
				);
				break;

			case 'normalized_name':
				$duplicates = $wpdb->get_results(
					"SELECT LOWER(TRIM(post_title)) AS match_value, GROUP_CONCAT(ID ORDER BY post_date ASC) AS ids
					FROM {$wpdb->posts}
					WHERE post_type = 'product'
					AND post_status != 'trash'
					AND post_title != ''
					GROUP BY match_value
					HAVING COUNT(*) > 1
					ORDER BY COUNT(*) DESC"
				);
				break;

			default: // 'name' – exact title match.
				$duplicates = $wpdb->get_results(
					"SELECT post_title AS match_value, GROUP_CONCAT(ID ORDER BY post_date ASC) AS ids
					FROM {$wpdb->posts}
					WHERE post_type = 'product'
					AND post_status != 'trash'
					AND post_title != ''
					GROUP BY post_title
					HAVING COUNT(*) > 1
					ORDER BY COUNT(*) DESC"
				);
				break;
		}
		// phpcs:enable

		$total_products = 0;

		if ( ! empty( $duplicates ) ) {
			foreach ( $duplicates as $row ) {
				$product_ids = array_map( 'absint', explode( ',', $row->ids ) );
				$products    = array();

				foreach ( $product_ids as $pid ) {
					$product = wc_get_product( $pid );
					if ( ! $product ) {
						continue;
					}
					$products[] = array(
						'id'     => $product->get_id(),
						'name'   => $product->get_name(),
						'sku'    => $product->get_sku(),
						'status' => $product->get_status(),
						'price'  => $product->get_price() ? wc_price( $product->get_price() ) : '-',
						'type'   => $product->get_type(),
						'date'   => get_the_date( get_option( 'date_format' ), $product->get_id() ),
						'slug'   => $product->get_slug(),
					);
				}

				if ( count( $products ) > 1 ) {
					$groups[]        = array(
						'value'    => $row->match_value,
						'products' => $products,
					);
					$total_products += count( $products );
				}
			}
		}

		return array(
			'groups'         => $groups,
			'total_groups'   => count( $groups ),
			'total_products' => $total_products,
		);
	}

	/**
	 * Count duplicate product groups and total duplicate products.
	 *
	 * @since 0.1.0
	 * @param string $match_by Match type.
	 * @return array {
	 *     @type int $groups   Number of duplicate groups.
	 *     @type int $products Total number of duplicate products.
	 * }
	 */
	public function count_duplicate_products( $match_by = 'name' ) {
		$result = $this->get_duplicate_products( $match_by );

		return array(
			'groups'   => $result['total_groups'],
			'products' => $result['total_products'],
		);
	}

	/**
	 * AJAX handler: Get duplicate products.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function ajax_get_duplicate_products() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$match_by = isset( $_POST['match_by'] ) ? sanitize_key( $_POST['match_by'] ) : 'name';
		$allowed  = array( 'name', 'sku', 'slug', 'normalized_name' );

		if ( ! in_array( $match_by, $allowed, true ) ) {
			$match_by = 'name';
		}

		$result = $this->get_duplicate_products( $match_by );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete duplicate products.
	 *
	 * Accepts an explicit list of product IDs chosen by the user.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function ajax_delete_duplicate_products() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$product_ids     = isset( $_POST['product_ids'] ) ? array_map( 'absint', (array) $_POST['product_ids'] ) : array();
		$delete_images   = isset( $_POST['delete_images'] ) && 'true' === $_POST['delete_images'];
		$action          = isset( $_POST['delete_action'] ) ? sanitize_key( $_POST['delete_action'] ) : 'delete';
		$setup_redirects = isset( $_POST['setup_redirects'] ) && 'true' === $_POST['setup_redirects'];
		$redirect_map    = isset( $_POST['redirect_map'] ) ? array_map( 'absint', wp_unslash( (array) $_POST['redirect_map'] ) ) : array();

		if ( empty( $product_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No products selected.', 'jharudar-for-woocommerce' ) ) );
		}

		// Validate action.
		if ( ! in_array( $action, array( 'delete', 'trash', 'draft' ), true ) ) {
			$action = 'delete';
		}

		// Handle draft action — set status to draft instead of deleting.
		if ( 'draft' === $action ) {
			$result = $this->set_products_status( $product_ids, 'draft' );
		} else {
			$result = $this->delete_products( $product_ids, $action, $delete_images );
		}

		// Set up 301 redirects if requested.
		$redirects_created = 0;
		if ( $setup_redirects && ! empty( $redirect_map ) ) {
			$redirects_created = $this->create_duplicate_redirects( $redirect_map );
		}

		$result['redirects_created'] = $redirects_created;
		$result['action']            = $action;

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Export duplicate products.
	 *
	 * @since 0.1.1
	 * @return void
	 */
	public function ajax_export_duplicate_products() {
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
	 * Set products to a specific status (draft, pending, etc.).
	 *
	 * @since 0.1.1
	 * @param array  $product_ids Product IDs.
	 * @param string $status      Target status.
	 * @return array Result with updated and failed counts.
	 */
	public function set_products_status( $product_ids, $status = 'draft' ) {
		$product_ids = jharudar_sanitize_ids( $product_ids );
		$updated     = 0;
		$failed      = 0;

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				++$failed;
				continue;
			}

			jharudar_log_activity( 'status_change', 'product', $product_id );

			$product->set_status( $status );
			$product->save();

			++$updated;
		}

		return array(
			'deleted' => $updated,
			'failed'  => $failed,
		);
	}

	/**
	 * Create 301 redirects for deleted duplicate products.
	 *
	 * Stores redirects in an option: slug of deleted product → URL of kept product.
	 *
	 * @since 0.1.1
	 * @param array $redirect_map Associative array: deleted product ID → kept product ID.
	 * @return int Number of redirects created.
	 */
	public function create_duplicate_redirects( $redirect_map ) {
		$redirects = get_option( 'jharudar_duplicate_redirects', array() );
		$count     = 0;

		foreach ( $redirect_map as $deleted_id => $kept_id ) {
			$deleted_id = absint( $deleted_id );
			$kept_id    = absint( $kept_id );

			if ( ! $deleted_id || ! $kept_id ) {
				continue;
			}

			// Get the slug before it is potentially deleted.
			$deleted_product = wc_get_product( $deleted_id );
			$kept_product    = wc_get_product( $kept_id );

			if ( ! $kept_product ) {
				continue;
			}

			$deleted_slug = $deleted_product ? $deleted_product->get_slug() : '';

			// Also try the post object slug if the product is already gone.
			if ( empty( $deleted_slug ) ) {
				$post = get_post( $deleted_id );
				if ( $post ) {
					$deleted_slug = $post->post_name;
				}
			}

			if ( empty( $deleted_slug ) ) {
				continue;
			}

			$kept_permalink = $kept_product->get_permalink();

			if ( $kept_permalink ) {
				$redirects[ $deleted_slug ] = $kept_permalink;
				++$count;
			}
		}

		if ( $count > 0 ) {
			update_option( 'jharudar_duplicate_redirects', $redirects, false );
		}

		return $count;
	}

	/**
	 * Handle 301 redirects for deleted duplicate products.
	 *
	 * Fires on template_redirect. Checks if the current 404 URL matches
	 * a previously deleted product slug and redirects to the kept product.
	 *
	 * @since 0.1.1
	 * @return void
	 */
	public function handle_duplicate_redirects() {
		if ( ! is_404() ) {
			return;
		}

		$redirects = get_option( 'jharudar_duplicate_redirects', array() );

		if ( empty( $redirects ) ) {
			return;
		}

		// Get the current request slug from the URL.
		$request_path = isset( $_SERVER['REQUEST_URI'] ) ? trim( wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ), '/' ) : '';
		$slug         = basename( $request_path );

		if ( isset( $redirects[ $slug ] ) ) {
			wp_safe_redirect( $redirects[ $slug ], 301 );
			exit;
		}
	}

	/**
	 * Count trashed products.
	 *
	 * @since 0.2.0
	 * @return int Trashed product count.
	 */
	public static function count_trashed() {
		$counts = wp_count_posts( 'product' );

		return isset( $counts->trash ) ? (int) $counts->trash : 0;
	}

	/**
	 * Permanently delete all trashed products.
	 *
	 * @since 0.2.0
	 * @return array Result data.
	 */
	public function empty_trash() {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'trash',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query   = new WP_Query( $args );
		$deleted = 0;
		$failed  = 0;

		foreach ( $query->posts as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				wp_delete_post( $product_id, true );
				++$deleted;
				continue;
			}

			jharudar_log_activity( 'delete', 'product', $product_id );
			$product->delete( true );
			++$deleted;
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
		);
	}

	/**
	 * AJAX handler: Empty trash for products.
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
	 * Check product dependencies (subscriptions, bookings, memberships).
	 *
	 * Returns warnings for products whose types indicate related active data
	 * (e.g. subscription products with active subscriptions).
	 *
	 * @since 0.1.0
	 * @param array $product_ids Product IDs to check.
	 * @return array Array of warning strings.
	 */
	public function get_related_data_warnings( $product_ids ) {
		$product_ids        = jharudar_sanitize_ids( $product_ids );
		$warnings           = array();
		$subscription_count = 0;
		$booking_count      = 0;
		$membership_count   = 0;

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$type = $product->get_type();

			// Check subscription products.
			if ( in_array( $type, array( 'subscription', 'variable-subscription' ), true )
				&& function_exists( 'wcs_get_subscriptions' ) ) {
				$subs                = wcs_get_subscriptions(
					array(
						'product_id'             => $product_id,
						'subscription_status'    => array( 'active', 'on-hold', 'pending' ),
						'subscriptions_per_page' => -1,
					)
				);
				$subscription_count += count( $subs );
			}

			// Check booking products.
			if ( 'booking' === $type && class_exists( 'WC_Bookings' ) ) {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$bk_count       = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
						INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
						WHERE p.post_type = 'wc_booking'
						AND p.post_status NOT IN ('trash', 'cancelled', 'complete')
						AND pm.meta_key = '_booking_product_id'
						AND pm.meta_value = %d",
						$product_id
					)
				);
				$booking_count += $bk_count;
			}
		}

		// Check memberships by plan (plans are products in WooCommerce Memberships).
		if ( function_exists( 'wc_memberships_get_membership_plans' ) ) {
			$plans = wc_memberships_get_membership_plans();
			foreach ( $plans as $plan ) {
				$plan_product_ids = $plan->get_product_ids();
				if ( ! empty( array_intersect( $product_ids, $plan_product_ids ) ) ) {
					// Count active memberships for this plan.
					$mem_args = array(
						'post_type'      => 'wc_user_membership',
						'post_parent'    => $plan->get_id(),
						'post_status'    => array( 'wcm-active', 'wcm-complimentary', 'wcm-pending' ),
						'posts_per_page' => -1,
						'fields'         => 'ids',
					);

					$mem_query         = new WP_Query( $mem_args );
					$membership_count += $mem_query->found_posts;
				}
			}
		}

		if ( $subscription_count > 0 ) {
			$warnings[] = sprintf(
				/* translators: %d: number of active subscriptions. */
				_n(
					'%d active subscription will be orphaned.',
					'%d active subscriptions will be orphaned.',
					$subscription_count,
					'jharudar-for-woocommerce'
				),
				$subscription_count
			);
		}

		if ( $booking_count > 0 ) {
			$warnings[] = sprintf(
				/* translators: %d: number of active bookings. */
				_n(
					'%d active/upcoming booking will be orphaned.',
					'%d active/upcoming bookings will be orphaned.',
					$booking_count,
					'jharudar-for-woocommerce'
				),
				$booking_count
			);
		}

		if ( $membership_count > 0 ) {
			$warnings[] = sprintf(
				/* translators: %d: number of active memberships. */
				_n(
					'%d active membership will be orphaned.',
					'%d active memberships will be orphaned.',
					$membership_count,
					'jharudar-for-woocommerce'
				),
				$membership_count
			);
		}

		return $warnings;
	}

	/**
	 * AJAX handler: Check product dependencies before deletion.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function ajax_check_product_dependencies() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$product_ids = isset( $_POST['product_ids'] ) ? array_map( 'absint', (array) $_POST['product_ids'] ) : array();

		if ( empty( $product_ids ) ) {
			wp_send_json_success( array( 'warnings' => array() ) );
			return;
		}

		$warnings = $this->get_related_data_warnings( $product_ids );
		wp_send_json_success( array( 'warnings' => $warnings ) );
	}
}
