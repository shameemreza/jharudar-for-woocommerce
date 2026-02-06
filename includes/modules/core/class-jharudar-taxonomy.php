<?php
/**
 * Taxonomy module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy module class.
 *
 * Handles taxonomy cleanup operations (categories, tags, attributes).
 *
 * @since 0.0.1
 */
class Jharudar_Taxonomy {

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
		add_action( 'wp_ajax_jharudar_get_taxonomy_items', array( $this, 'ajax_get_taxonomy_items' ) );
		add_action( 'wp_ajax_jharudar_delete_taxonomy_items', array( $this, 'ajax_delete_taxonomy_items' ) );
		add_action( 'wp_ajax_jharudar_get_taxonomy_stats', array( $this, 'ajax_get_taxonomy_stats' ) );
	}

	/**
	 * Get taxonomy items based on filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Taxonomy items data.
	 */
	public function get_taxonomy_items( $filters = array() ) {
		$defaults = array(
			'taxonomy_type' => 'categories', // categories, tags, attributes.
			'filter_type'   => '',           // empty, unused.
			'limit'         => 50,
			'offset'        => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		switch ( $filters['taxonomy_type'] ) {
			case 'tags':
				return $this->get_tags( $filters );
			case 'attributes':
				return $this->get_attributes( $filters );
			default:
				return $this->get_categories( $filters );
		}
	}

	/**
	 * Get product categories.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Categories data.
	 */
	private function get_categories( $filters ) {
		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => $filters['limit'],
			'offset'     => $filters['offset'],
		);

		// If filtering for empty categories.
		if ( 'empty' === $filters['filter_type'] ) {
			$args['count'] = true;
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return array(
				'items' => array(),
				'total' => 0,
			);
		}

		$formatted_items = array();

		foreach ( $terms as $term ) {
			// Skip "Uncategorized" if it's the default.
			if ( 'uncategorized' === $term->slug && get_option( 'default_product_cat' ) === $term->term_id ) {
				continue;
			}

			// Filter by type.
			if ( 'empty' === $filters['filter_type'] && $term->count > 0 ) {
				continue;
			}

			$formatted_items[] = array(
				'id'         => $term->term_id,
				'name'       => $term->name,
				'slug'       => $term->slug,
				'count'      => $term->count,
				'is_empty'   => 0 === $term->count,
				'is_default' => (int) get_option( 'default_product_cat' ) === $term->term_id,
				'parent'     => $term->parent ? $this->get_term_name( $term->parent, 'product_cat' ) : '-',
				'edit_url'   => get_edit_term_link( $term->term_id, 'product_cat' ),
			);
		}

		return array(
			'items' => $formatted_items,
			'total' => $this->count_categories( $filters ),
		);
	}

	/**
	 * Count categories matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Count.
	 */
	private function count_categories( $filters ) {
		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'fields'     => 'ids',
		);

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return 0;
		}

		if ( 'empty' !== $filters['filter_type'] ) {
			return count( $terms );
		}

		$count = 0;
		foreach ( $terms as $term_id ) {
			$term = get_term( $term_id, 'product_cat' );

			// Skip default category.
			if ( 'uncategorized' === $term->slug && get_option( 'default_product_cat' ) === $term->term_id ) {
				continue;
			}

			if ( 0 === $term->count ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Get product tags.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Tags data.
	 */
	private function get_tags( $filters ) {
		$args = array(
			'taxonomy'   => 'product_tag',
			'hide_empty' => false,
			'number'     => $filters['limit'],
			'offset'     => $filters['offset'],
		);

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return array(
				'items' => array(),
				'total' => 0,
			);
		}

		$formatted_items = array();

		foreach ( $terms as $term ) {
			// Filter for unused tags.
			if ( 'unused' === $filters['filter_type'] && $term->count > 0 ) {
				continue;
			}

			$formatted_items[] = array(
				'id'       => $term->term_id,
				'name'     => $term->name,
				'slug'     => $term->slug,
				'count'    => $term->count,
				'is_empty' => 0 === $term->count,
				'parent'   => '-',
				'edit_url' => get_edit_term_link( $term->term_id, 'product_tag' ),
			);
		}

		return array(
			'items' => $formatted_items,
			'total' => $this->count_tags( $filters ),
		);
	}

	/**
	 * Count tags matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Count.
	 */
	private function count_tags( $filters ) {
		$args = array(
			'taxonomy'   => 'product_tag',
			'hide_empty' => 'unused' === $filters['filter_type'],
			'fields'     => 'ids',
		);

		if ( 'unused' === $filters['filter_type'] ) {
			$args['hide_empty'] = false;
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return 0;
		}

		if ( 'unused' !== $filters['filter_type'] ) {
			return count( $terms );
		}

		$count = 0;
		foreach ( $terms as $term_id ) {
			$term = get_term( $term_id, 'product_tag' );
			if ( 0 === $term->count ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Get product attributes.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Attributes data.
	 */
	private function get_attributes( $filters ) {
		$attribute_taxonomies = wc_get_attribute_taxonomies();

		if ( empty( $attribute_taxonomies ) ) {
			return array(
				'items' => array(),
				'total' => 0,
			);
		}

		$formatted_items = array();
		$index           = 0;

		foreach ( $attribute_taxonomies as $attribute ) {
			// Handle pagination.
			if ( $index < $filters['offset'] ) {
				++$index;
				continue;
			}

			if ( count( $formatted_items ) >= $filters['limit'] ) {
				break;
			}

			$taxonomy   = wc_attribute_taxonomy_name( $attribute->attribute_name );
			$term_count = wp_count_terms( $taxonomy );

			// Check for unused attributes (no terms).
			if ( 'unused' === $filters['filter_type'] ) {
				// Check if attribute is used in any products.
				$is_used = $this->is_attribute_used( $attribute->attribute_id, $attribute->attribute_name );
				if ( $is_used ) {
					++$index;
					continue;
				}
			}

			$terms_count = is_wp_error( $term_count ) ? 0 : $term_count;
			$is_used     = $this->is_attribute_used( $attribute->attribute_id, $attribute->attribute_name );

			// Get truncated list of terms for display (first 3).
			$terms_preview = $this->get_truncated_terms( $taxonomy, 3 );

			$formatted_items[] = array(
				'id'            => $attribute->attribute_id,
				'name'          => $attribute->attribute_label,
				'slug'          => $attribute->attribute_name,
				'terms_count'   => $terms_count,
				'terms_preview' => $terms_preview,
				'is_empty'      => ! $is_used,
				'type'          => $attribute->attribute_type,
				'order_by'      => $attribute->attribute_orderby,
				'edit_url'      => admin_url( 'edit.php?post_type=product&page=product_attributes&edit=' . $attribute->attribute_id ),
			);

			++$index;
		}

		return array(
			'items' => $formatted_items,
			'total' => $this->count_attributes( $filters ),
		);
	}

	/**
	 * Count attributes matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Count.
	 */
	private function count_attributes( $filters ) {
		$attribute_taxonomies = wc_get_attribute_taxonomies();

		if ( empty( $attribute_taxonomies ) ) {
			return 0;
		}

		if ( 'unused' !== $filters['filter_type'] ) {
			return count( $attribute_taxonomies );
		}

		$count = 0;
		foreach ( $attribute_taxonomies as $attribute ) {
			if ( ! $this->is_attribute_used( $attribute->attribute_id, $attribute->attribute_name ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Get a truncated list of terms for display.
	 *
	 * @since 0.0.1
	 * @param string $taxonomy Taxonomy name.
	 * @param int    $limit    Maximum number of terms to return.
	 * @return array Array with 'terms' (truncated list) and 'has_more' flag.
	 */
	private function get_truncated_terms( $taxonomy, $limit = 3 ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => $limit + 1, // Get one extra to check if there are more.
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array(
				'terms'    => array(),
				'has_more' => false,
			);
		}

		$has_more       = count( $terms ) > $limit;
		$truncated      = array_slice( $terms, 0, $limit );
		$term_names     = array_map(
			function ( $term ) {
				return $term->name;
			},
			$truncated
		);

		return array(
			'terms'    => $term_names,
			'has_more' => $has_more,
		);
	}

	/**
	 * Check if attribute is used by any products.
	 *
	 * @since 0.0.1
	 * @param int    $attribute_id   Attribute ID.
	 * @param string $attribute_name Optional. Attribute name/slug for direct lookup.
	 * @return bool True if used.
	 */
	private function is_attribute_used( $attribute_id, $attribute_name = '' ) {
		return self::check_attribute_used( $attribute_id, $attribute_name );
	}

	/**
	 * Check if an attribute is used by any products (static version).
	 *
	 * @since 0.0.1
	 * @param int    $attribute_id   Attribute ID.
	 * @param string $attribute_name Optional. Attribute name/slug for direct lookup.
	 * @return bool True if used.
	 */
	public static function check_attribute_used( $attribute_id, $attribute_name = '' ) {
		global $wpdb;

		// If attribute name is provided, use it directly (more reliable).
		if ( ! empty( $attribute_name ) ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute_name );
		} else {
			// Fallback to looking up by ID.
			$attribute = wc_get_attribute( $attribute_id );
			if ( ! $attribute ) {
				return false;
			}
			$taxonomy = wc_attribute_taxonomy_name( $attribute->slug );
		}

		// Method 1: Check if any products have terms in this attribute taxonomy.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$term_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT tr.object_id) FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE tt.taxonomy = %s",
				$taxonomy
			)
		);

		if ( $term_count > 0 ) {
			return true;
		}

		// Method 2: Check if attribute is used in product meta (for variation attributes).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$meta_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta}
				WHERE meta_key = %s AND meta_value != ''",
				'attribute_' . $attribute_name
			)
		);

		if ( $meta_count > 0 ) {
			return true;
		}

		// Method 3: Check in serialized _product_attributes meta.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$serialized_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta}
				WHERE meta_key = '_product_attributes' AND meta_value LIKE %s",
				'%' . $wpdb->esc_like( $taxonomy ) . '%'
			)
		);

		return $serialized_count > 0;
	}

	/**
	 * Get term name by ID.
	 *
	 * @since 0.0.1
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return string Term name.
	 */
	private function get_term_name( $term_id, $taxonomy ) {
		$term = get_term( $term_id, $taxonomy );
		return $term && ! is_wp_error( $term ) ? $term->name : '-';
	}

	/**
	 * Delete taxonomy items.
	 *
	 * @since 0.0.1
	 * @param array  $item_ids      Item IDs to delete.
	 * @param string $taxonomy_type Taxonomy type.
	 * @return array Result data.
	 */
	public function delete_taxonomy_items( $item_ids, $taxonomy_type ) {
		$item_ids   = jharudar_sanitize_ids( $item_ids );
		$batch_size = jharudar_get_batch_size();
		$deleted    = 0;
		$failed     = 0;
		$skipped    = 0;

		// If batch processing needed.
		if ( count( $item_ids ) > $batch_size ) {
			$this->schedule_batch_delete( $item_ids, $taxonomy_type );
			return array(
				'scheduled' => true,
				'total'     => count( $item_ids ),
				'message'   => __( 'Items are being deleted in the background.', 'jharudar-for-woocommerce' ),
			);
		}

		foreach ( $item_ids as $item_id ) {
			$result = $this->delete_single_item( $item_id, $taxonomy_type );

			if ( 'deleted' === $result ) {
				++$deleted;
			} elseif ( 'skipped' === $result ) {
				++$skipped;
			} else {
				++$failed;
			}
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
			'skipped' => $skipped,
		);
	}

	/**
	 * Delete a single taxonomy item.
	 *
	 * @since 0.0.1
	 * @param int    $item_id       Item ID.
	 * @param string $taxonomy_type Taxonomy type.
	 * @return string Result (deleted, skipped, failed).
	 */
	private function delete_single_item( $item_id, $taxonomy_type ) {
		switch ( $taxonomy_type ) {
			case 'categories':
				// Check if it's the default category.
				$term = get_term( $item_id, 'product_cat' );
				if ( ! $term || is_wp_error( $term ) ) {
					return 'failed';
				}

				if ( 'uncategorized' === $term->slug && get_option( 'default_product_cat' ) === $term->term_id ) {
					return 'skipped';
				}

				$result = wp_delete_term( $item_id, 'product_cat' );
				if ( $result && ! is_wp_error( $result ) ) {
					jharudar_log_activity( 'delete', 'category', $item_id );
					return 'deleted';
				}
				return 'failed';

			case 'tags':
				$result = wp_delete_term( $item_id, 'product_tag' );
				if ( $result && ! is_wp_error( $result ) ) {
					jharudar_log_activity( 'delete', 'tag', $item_id );
					return 'deleted';
				}
				return 'failed';

			case 'attributes':
				$result = wc_delete_attribute( $item_id );
				if ( $result ) {
					jharudar_log_activity( 'delete', 'attribute', $item_id );
					return 'deleted';
				}
				return 'failed';

			default:
				return 'failed';
		}
	}

	/**
	 * Schedule batch delete using Action Scheduler.
	 *
	 * @since 0.0.1
	 * @param array  $item_ids      Item IDs.
	 * @param string $taxonomy_type Taxonomy type.
	 * @return void
	 */
	private function schedule_batch_delete( $item_ids, $taxonomy_type ) {
		$batch_size = jharudar_get_batch_size();
		$batches    = array_chunk( $item_ids, $batch_size );

		foreach ( $batches as $index => $batch ) {
			as_schedule_single_action(
				time() + ( $index * 30 ),
				'jharudar_delete_taxonomy_batch',
				array(
					'item_ids'      => $batch,
					'taxonomy_type' => $taxonomy_type,
				),
				'jharudar'
			);
		}
	}

	/**
	 * Get taxonomy statistics.
	 *
	 * @since 0.0.1
	 * @return array Stats data.
	 */
	public static function get_statistics() {
		// Categories stats.
		$all_categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		$empty_cat_count = 0;
		if ( ! is_wp_error( $all_categories ) ) {
			foreach ( $all_categories as $term_id ) {
				$term = get_term( $term_id, 'product_cat' );
				if ( $term && 0 === $term->count ) {
					// Skip default category.
					if ( 'uncategorized' !== $term->slug || get_option( 'default_product_cat' ) !== $term->term_id ) {
						++$empty_cat_count;
					}
				}
			}
		}

		// Tags stats.
		$all_tags       = get_terms(
			array(
				'taxonomy'   => 'product_tag',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		$unused_tags    = 0;
		$all_tags_count = 0;

		if ( ! is_wp_error( $all_tags ) ) {
			$all_tags_count = count( $all_tags );
			foreach ( $all_tags as $term_id ) {
				$term = get_term( $term_id, 'product_tag' );
				if ( $term && 0 === $term->count ) {
					++$unused_tags;
				}
			}
		}

		// Attributes stats.
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		$all_attributes       = is_array( $attribute_taxonomies ) ? count( $attribute_taxonomies ) : 0;
		$unused_attributes    = 0;

		if ( ! empty( $attribute_taxonomies ) ) {
			foreach ( $attribute_taxonomies as $attribute ) {
				if ( ! self::check_attribute_used( $attribute->attribute_id, $attribute->attribute_name ) ) {
					++$unused_attributes;
				}
			}
		}

		return array(
			'total_categories'  => is_wp_error( $all_categories ) ? 0 : count( $all_categories ),
			'empty_categories'  => $empty_cat_count,
			'total_tags'        => $all_tags_count,
			'unused_tags'       => $unused_tags,
			'total_attributes'  => $all_attributes,
			'unused_attributes' => $unused_attributes,
		);
	}

	/**
	 * AJAX handler: Get taxonomy items.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_taxonomy_items() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'taxonomy_type' => isset( $_POST['taxonomy_type'] ) ? sanitize_key( $_POST['taxonomy_type'] ) : 'categories',
			'filter_type'   => isset( $_POST['filter_type'] ) ? sanitize_key( $_POST['filter_type'] ) : '',
			'limit'         => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'        => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_taxonomy_items( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete taxonomy items.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_taxonomy_items() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$item_ids      = isset( $_POST['item_ids'] ) ? array_map( 'absint', (array) $_POST['item_ids'] ) : array();
		$taxonomy_type = isset( $_POST['taxonomy_type'] ) ? sanitize_key( $_POST['taxonomy_type'] ) : 'categories';

		if ( empty( $item_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No items selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->delete_taxonomy_items( $item_ids, $taxonomy_type );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Get taxonomy stats.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_taxonomy_stats() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$stats = $this->get_stats();
		wp_send_json_success( $stats );
	}
}
