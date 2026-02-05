<?php
/**
 * Jharudar Automation.
 *
 * Handles automatic cleanup actions based on settings.
 * Compatible with managed hosting environments (WordPress.com, WP Engine, Kinsta, etc.).
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Jharudar_Automation class.
 *
 * @since 0.0.1
 */
class Jharudar_Automation {

	/**
	 * Initialize automation hooks.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		// Auto-delete zero stock products.
		add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_delete_zero_stock_products' ) );

		// Auto-delete product images on product deletion.
		add_action( 'before_delete_post', array( $this, 'maybe_delete_product_images' ) );
	}

	/**
	 * Maybe delete products with zero stock after order completion.
	 *
	 * @since 0.0.1
	 * @param int $order_id Order ID.
	 */
	public function maybe_delete_zero_stock_products( $order_id ) {
		// Check if feature is enabled.
		if ( ! jharudar_get_setting( 'auto_delete_zero_stock', false ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			if (
				$product instanceof WC_Product &&
				$product->managing_stock() &&
				$product->get_stock_quantity() <= 0
			) {
				$product_id   = $product->get_id();
				$product_name = $product->get_name();

				// Delete the product permanently.
				wp_delete_post( $product_id, true );

				// Log the activity.
				jharudar_log_activity(
					'auto_delete_zero_stock',
					'product',
					$product_id,
					array(
						'product_name' => $product_name,
						'order_id'     => $order_id,
						'reason'       => 'Stock reached zero after order completion',
					)
				);
			}
		}
	}

	/**
	 * Maybe delete product images when product is deleted.
	 *
	 * Uses managed hosting compatible approach.
	 *
	 * @since 0.0.1
	 * @param int $post_id Post ID.
	 */
	public function maybe_delete_product_images( $post_id ) {
		// Check if feature is enabled.
		if ( ! jharudar_get_setting( 'auto_delete_product_images', false ) ) {
			return;
		}

		// Check if WooCommerce is active.
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		// Check post type.
		if ( get_post_type( $post_id ) !== 'product' ) {
			return;
		}

		// Get product.
		$product = wc_get_product( $post_id );
		if ( ! $product ) {
			return;
		}

		$skip_shared = jharudar_get_setting( 'skip_shared_images', true );

		// Get images.
		$featured_id = $product->get_image_id();
		$gallery_ids = $product->get_gallery_image_ids();

		$deleted_images = array();

		// Delete featured image if exists.
		if ( $featured_id ) {
			if ( ! $skip_shared || ! $this->is_image_used_by_others( $featured_id, $post_id ) ) {
				wp_delete_attachment( $featured_id, true );
				$deleted_images[] = $featured_id;
			}
		}

		// Delete gallery images.
		if ( is_array( $gallery_ids ) ) {
			foreach ( $gallery_ids as $image_id ) {
				if ( ! $skip_shared || ! $this->is_image_used_by_others( $image_id, $post_id ) ) {
					wp_delete_attachment( $image_id, true );
					$deleted_images[] = $image_id;
				}
			}
		}

		// Log the activity if images were deleted.
		if ( ! empty( $deleted_images ) ) {
			jharudar_log_activity(
				'auto_delete_product_images',
				'product',
				$post_id,
				array(
					'product_name'   => $product->get_name(),
					'images_deleted' => count( $deleted_images ),
					'image_ids'      => $deleted_images,
				)
			);
		}
	}

	/**
	 * Check if an image is used by other products.
	 *
	 * Uses a managed hosting compatible approach (simpler meta queries).
	 *
	 * @since 0.0.1
	 * @param int $image_id           Image attachment ID.
	 * @param int $current_product_id Current product being deleted.
	 * @return bool True if image is used by other products.
	 */
	private function is_image_used_by_others( $image_id, $current_product_id ) {
		// Check if image is used as featured image elsewhere.
		$args = array(
			'post_type'      => 'product',
			'post__not_in'   => array( $current_product_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			'meta_key'       => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $image_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);

		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			return true;
		}

		// Check gallery usage - managed hosting compatible approach.
		$args2 = array(
			'post_type'      => 'product',
			'post__not_in'   => array( $current_product_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			'meta_key'       => '_product_image_gallery', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare'   => 'LIKE',
			'meta_value'     => $image_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);

		$query2 = new WP_Query( $args2 );
		return $query2->have_posts();
	}
}
