<?php
/**
 * Shipping module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shipping module class.
 *
 * Handles shipping configuration cleanup operations.
 *
 * @since 0.0.1
 */
class Jharudar_Shipping {

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
		add_action( 'wp_ajax_jharudar_get_shipping_zones', array( $this, 'ajax_get_shipping_zones' ) );
		add_action( 'wp_ajax_jharudar_delete_shipping_zones', array( $this, 'ajax_delete_shipping_zones' ) );
		add_action( 'wp_ajax_jharudar_get_shipping_classes', array( $this, 'ajax_get_shipping_classes' ) );
		add_action( 'wp_ajax_jharudar_delete_shipping_classes', array( $this, 'ajax_delete_shipping_classes' ) );
		add_action( 'wp_ajax_jharudar_get_shipping_stats', array( $this, 'ajax_get_shipping_stats' ) );
	}

	/**
	 * Get shipping zones.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Shipping zones data.
	 */
	public function get_shipping_zones( $filters = array() ) {
		$defaults = array(
			'filter_type' => '',
			'limit'       => 50,
			'offset'      => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		$zones      = WC_Shipping_Zones::get_zones();
		$rest_world = new WC_Shipping_Zone( 0 );

		// Add "Rest of the World" zone.
		$zones_array   = array_values( $zones );
		$zones_array[] = array(
			'id'                      => 0,
			'zone_id'                 => 0,
			'zone_name'               => $rest_world->get_zone_name(),
			'zone_order'              => $rest_world->get_zone_order(),
			'zone_locations'          => $rest_world->get_zone_locations(),
			'shipping_methods'        => $rest_world->get_shipping_methods(),
			'formatted_zone_location' => $rest_world->get_formatted_location(),
		);

		$formatted_zones = array();
		$index           = 0;

		foreach ( $zones_array as $zone_data ) {
			// Handle pagination.
			if ( $index < $filters['offset'] ) {
				++$index;
				continue;
			}

			if ( count( $formatted_zones ) >= $filters['limit'] ) {
				break;
			}

			$zone = new WC_Shipping_Zone( $zone_data['zone_id'] );

			// Filter for empty zones (no methods).
			if ( 'empty' === $filters['filter_type'] ) {
				$methods = $zone->get_shipping_methods();
				if ( ! empty( $methods ) ) {
					++$index;
					continue;
				}
			}

			$formatted_zones[] = $this->format_shipping_zone_data( $zone_data );
			++$index;
		}

		return array(
			'zones' => $formatted_zones,
			'total' => $this->count_shipping_zones( $filters ),
		);
	}

	/**
	 * Count shipping zones matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Count.
	 */
	private function count_shipping_zones( $filters ) {
		$zones = WC_Shipping_Zones::get_zones();

		// Add Rest of the World.
		$total = count( $zones ) + 1;

		if ( 'empty' !== $filters['filter_type'] ) {
			return $total;
		}

		$count = 0;

		foreach ( $zones as $zone_data ) {
			$zone    = new WC_Shipping_Zone( $zone_data['zone_id'] );
			$methods = $zone->get_shipping_methods();

			if ( empty( $methods ) ) {
				++$count;
			}
		}

		// Check Rest of the World.
		$rest_world = new WC_Shipping_Zone( 0 );
		if ( empty( $rest_world->get_shipping_methods() ) ) {
			++$count;
		}

		return $count;
	}

	/**
	 * Format shipping zone data for display.
	 *
	 * @since 0.0.1
	 * @param array $zone_data Zone data.
	 * @return array Formatted zone data.
	 */
	private function format_shipping_zone_data( $zone_data ) {
		$zone    = new WC_Shipping_Zone( $zone_data['zone_id'] );
		$methods = $zone->get_shipping_methods();

		$method_names = array();
		foreach ( $methods as $method ) {
			$method_names[] = $method->get_method_title();
		}

		return array(
			'id'            => $zone_data['zone_id'],
			'name'          => $zone_data['zone_name'],
			'regions'       => $zone->get_formatted_location(),
			'methods'       => ! empty( $method_names ) ? implode( ', ', $method_names ) : __( 'No methods', 'jharudar-for-woocommerce' ),
			'methods_count' => count( $methods ),
			'edit_url'      => admin_url( 'admin.php?page=wc-settings&tab=shipping&zone_id=' . $zone_data['zone_id'] ),
		);
	}

	/**
	 * Delete shipping zones.
	 *
	 * @since 0.0.1
	 * @param array $zone_ids Zone IDs to delete.
	 * @return array Result data.
	 */
	public function delete_shipping_zones( $zone_ids ) {
		$zone_ids = jharudar_sanitize_ids( $zone_ids );
		$deleted  = 0;
		$failed   = 0;
		$skipped  = 0;

		foreach ( $zone_ids as $zone_id ) {
			// Cannot delete "Rest of the World" (zone 0).
			if ( 0 === $zone_id ) {
				++$skipped;
				continue;
			}

			$zone = new WC_Shipping_Zone( $zone_id );

			if ( ! $zone || ! $zone->get_id() ) {
				++$failed;
				continue;
			}

			// Log activity before deletion.
			jharudar_log_activity( 'delete', 'shipping_zone', $zone_id );

			// Delete the zone.
			$zone->delete();
			++$deleted;
		}

		return array(
			'deleted' => $deleted,
			'failed'  => $failed,
			'skipped' => $skipped,
		);
	}

	/**
	 * Get shipping classes.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return array Shipping classes data.
	 */
	public function get_shipping_classes( $filters = array() ) {
		$defaults = array(
			'filter_type' => '',
			'limit'       => 50,
			'offset'      => 0,
		);

		$filters = wp_parse_args( $filters, $defaults );

		$shipping_classes = WC()->shipping()->get_shipping_classes();

		$formatted_classes = array();
		$index             = 0;

		foreach ( $shipping_classes as $class ) {
			// Handle pagination.
			if ( $index < $filters['offset'] ) {
				++$index;
				continue;
			}

			if ( count( $formatted_classes ) >= $filters['limit'] ) {
				break;
			}

			// Filter for unused classes.
			if ( 'unused' === $filters['filter_type'] ) {
				if ( $this->is_shipping_class_used( $class->term_id ) ) {
					++$index;
					continue;
				}
			}

			$formatted_classes[] = $this->format_shipping_class_data( $class );
			++$index;
		}

		return array(
			'classes' => $formatted_classes,
			'total'   => $this->count_shipping_classes( $filters ),
		);
	}

	/**
	 * Count shipping classes matching filters.
	 *
	 * @since 0.0.1
	 * @param array $filters Filter parameters.
	 * @return int Count.
	 */
	private function count_shipping_classes( $filters ) {
		$shipping_classes = WC()->shipping()->get_shipping_classes();

		if ( 'unused' !== $filters['filter_type'] ) {
			return count( $shipping_classes );
		}

		$count = 0;
		foreach ( $shipping_classes as $class ) {
			if ( ! $this->is_shipping_class_used( $class->term_id ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Check if a shipping class is used by any products.
	 *
	 * @since 0.0.1
	 * @param int $class_id Shipping class term ID.
	 * @return bool True if used.
	 */
	private function is_shipping_class_used( $class_id ) {
		return self::check_shipping_class_used( $class_id );
	}

	/**
	 * Check if a shipping class is used by any products (static version).
	 *
	 * @since 0.0.1
	 * @param int $class_id Shipping class ID.
	 * @return bool True if used.
	 */
	public static function check_shipping_class_used( $class_id ) {
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_shipping_class',
					'field'    => 'term_id',
					'terms'    => $class_id,
				),
			),
		);

		$query = new WP_Query( $args );
		return $query->found_posts > 0;
	}

	/**
	 * Format shipping class data for display.
	 *
	 * @since 0.0.1
	 * @param WP_Term $shipping_class Shipping class term.
	 * @return array Formatted class data.
	 */
	private function format_shipping_class_data( $shipping_class ) {
		return array(
			'id'          => $shipping_class->term_id,
			'name'        => $shipping_class->name,
			'slug'        => $shipping_class->slug,
			'description' => $shipping_class->description,
			'count'       => $shipping_class->count,
			'edit_url'    => admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ),
		);
	}

	/**
	 * Delete shipping classes.
	 *
	 * @since 0.0.1
	 * @param array $class_ids Class IDs to delete.
	 * @return array Result data.
	 */
	public function delete_shipping_classes( $class_ids ) {
		$class_ids = jharudar_sanitize_ids( $class_ids );
		$deleted   = 0;
		$failed    = 0;

		foreach ( $class_ids as $class_id ) {
			$term = get_term( $class_id, 'product_shipping_class' );

			if ( ! $term || is_wp_error( $term ) ) {
				++$failed;
				continue;
			}

			// Log activity.
			jharudar_log_activity( 'delete', 'shipping_class', $class_id );

			$result = wp_delete_term( $class_id, 'product_shipping_class' );

			if ( $result && ! is_wp_error( $result ) ) {
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
	 * Get shipping statistics.
	 *
	 * @since 0.0.1
	 * @return array Stats data.
	 */
	public static function get_statistics() {
		$zones            = WC_Shipping_Zones::get_zones();
		$shipping_classes = WC()->shipping()->get_shipping_classes();

		// Count empty zones.
		$empty_zones = 0;
		foreach ( $zones as $zone_data ) {
			$zone    = new WC_Shipping_Zone( $zone_data['zone_id'] );
			$methods = $zone->get_shipping_methods();
			if ( empty( $methods ) ) {
				++$empty_zones;
			}
		}

		// Check Rest of the World.
		$rest_world = new WC_Shipping_Zone( 0 );
		if ( empty( $rest_world->get_shipping_methods() ) ) {
			++$empty_zones;
		}

		// Count unused shipping classes.
		$unused_classes = 0;
		foreach ( $shipping_classes as $class ) {
			if ( ! self::check_shipping_class_used( $class->term_id ) ) {
				++$unused_classes;
			}
		}

		return array(
			'total_zones'    => count( $zones ) + 1, // +1 for Rest of the World.
			'empty_zones'    => $empty_zones,
			'total_classes'  => count( $shipping_classes ),
			'unused_classes' => $unused_classes,
		);
	}

	/**
	 * AJAX handler: Get shipping zones.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_shipping_zones() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'filter_type' => isset( $_POST['filter_type'] ) ? sanitize_key( $_POST['filter_type'] ) : '',
			'limit'       => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'      => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_shipping_zones( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete shipping zones.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_shipping_zones() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$zone_ids = isset( $_POST['zone_ids'] ) ? array_map( 'absint', (array) $_POST['zone_ids'] ) : array();

		if ( empty( $zone_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No shipping zones selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->delete_shipping_zones( $zone_ids );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Get shipping classes.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_shipping_classes() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$filters = array(
			'filter_type' => isset( $_POST['filter_type'] ) ? sanitize_key( $_POST['filter_type'] ) : '',
			'limit'       => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'      => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$result = $this->get_shipping_classes( $filters );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete shipping classes.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_shipping_classes() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$class_ids = isset( $_POST['class_ids'] ) ? array_map( 'absint', (array) $_POST['class_ids'] ) : array();

		if ( empty( $class_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No shipping classes selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->delete_shipping_classes( $class_ids );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Get shipping stats.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_shipping_stats() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$stats = self::get_statistics();
		wp_send_json_success( $stats );
	}
}
