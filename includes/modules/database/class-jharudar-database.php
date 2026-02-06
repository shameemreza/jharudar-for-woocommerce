<?php
/**
 * Database optimization module class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database optimization module.
 *
 * Handles transients, sessions, orphaned data, duplicate meta,
 * table analysis, optimization, and repair operations.
 *
 * @since 0.0.1
 */
class Jharudar_Database {

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
		add_action( 'wp_ajax_jharudar_get_database_stats', array( $this, 'ajax_get_database_stats' ) );
		add_action( 'wp_ajax_jharudar_clean_transients', array( $this, 'ajax_clean_transients' ) );
		add_action( 'wp_ajax_jharudar_clean_wc_transients', array( $this, 'ajax_clean_wc_transients' ) );
		add_action( 'wp_ajax_jharudar_clean_all_transients', array( $this, 'ajax_clean_all_transients' ) );
		add_action( 'wp_ajax_jharudar_clean_oembed_caches', array( $this, 'ajax_clean_oembed_caches' ) );
		add_action( 'wp_ajax_jharudar_clean_sessions', array( $this, 'ajax_clean_sessions' ) );
		add_action( 'wp_ajax_jharudar_clean_orphaned_meta', array( $this, 'ajax_clean_orphaned_meta' ) );
		add_action( 'wp_ajax_jharudar_clean_duplicate_meta', array( $this, 'ajax_clean_duplicate_meta' ) );
		add_action( 'wp_ajax_jharudar_regenerate_customer_lookup', array( $this, 'ajax_regenerate_customer_lookup' ) );
		add_action( 'wp_ajax_jharudar_repair_order_stats', array( $this, 'ajax_repair_order_stats' ) );
		add_action( 'wp_ajax_jharudar_get_table_analysis', array( $this, 'ajax_get_table_analysis' ) );
		add_action( 'wp_ajax_jharudar_optimize_tables', array( $this, 'ajax_optimize_tables' ) );
		add_action( 'wp_ajax_jharudar_repair_tables', array( $this, 'ajax_repair_tables' ) );
		add_action( 'wp_ajax_jharudar_delete_orphaned_tables', array( $this, 'ajax_delete_orphaned_tables' ) );
		add_action( 'wp_ajax_jharudar_toggle_autoload', array( $this, 'ajax_toggle_autoload' ) );
	}

	/**
	 * Get database statistics for the Database tab.
	 *
	 * @since 0.0.1
	 * @return array
	 */
	public static function get_statistics() {
		global $wpdb;

		$cache_key = 'jharudar_database_stats';
		$stats     = get_transient( $cache_key );

		if ( false === $stats ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// LIKE patterns via esc_like + prepare.
			$transient_like      = $wpdb->esc_like( '_transient_' ) . '%';
			$site_transient_like = $wpdb->esc_like( '_site_transient_' ) . '%';
			$timeout_like        = $wpdb->esc_like( '_transient_timeout_' ) . '%';
			$wc_transient_like   = $wpdb->esc_like( '_transient_wc_' ) . '%';
			$wc_site_like        = $wpdb->esc_like( '_site_transient_wc_' ) . '%';
			$oembed_like         = $wpdb->esc_like( '_oembed_' ) . '%';

			// All transients (including site transients).
			$total_transients = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
					$transient_like,
					$site_transient_like
				)
			);

			// Expired transients (have a timeout row whose value is in the past).
			$expired_transients = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} AS o
					WHERE o.option_name LIKE %s
					AND CAST(o.option_value AS UNSIGNED) < %d",
					$timeout_like,
					time()
				)
			);

			// WooCommerce-specific transients.
			$wc_transients = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
					$wc_transient_like,
					$wc_site_like
				)
			);

			// Orphaned meta counts.
			$postmeta_orphans = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE p.ID IS NULL"
			);

			$usermeta_orphans = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->usermeta} um
				LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID
				WHERE u.ID IS NULL"
			);

			$termmeta_orphans = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
				LEFT JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
				WHERE t.term_id IS NULL"
			);

			$commentmeta_orphans = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->commentmeta} cm
				LEFT JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
				WHERE c.comment_ID IS NULL"
			);

			// oEmbed caches.
			$oembed_caches = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
					$oembed_like
				)
			);

			// Expired WooCommerce sessions.
			$expired_sessions = 0;

			// Duplicate postmeta count (same post_id + meta_key + meta_value).
			$duplicate_postmeta = (int) $wpdb->get_var(
				"SELECT COUNT(*) - COUNT(DISTINCT post_id, meta_key, meta_value) FROM {$wpdb->postmeta}"
			);

			$order_itemmeta_orphans = 0;
			$relationships_orphans  = 0;

			// WooCommerce tables may not exist on non-WooCommerce sites, so check first.
			$tables = $wpdb->get_col( 'SHOW TABLES' );

			$sessions_table = $wpdb->prefix . 'woocommerce_sessions';

			if ( in_array( $sessions_table, $tables, true ) ) {
				$sessions_table_esc = esc_sql( $sessions_table );
				$expired_sessions   = (int) $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is esc_sql'd above.
						"SELECT COUNT(*) FROM `{$sessions_table_esc}` WHERE session_expiry < %d",
						time()
					)
				);
			}

			$order_items_table    = esc_sql( $wpdb->prefix . 'woocommerce_order_items' );
			$order_itemmeta_table = esc_sql( $wpdb->prefix . 'woocommerce_order_itemmeta' );

			if (
				in_array( $wpdb->prefix . 'woocommerce_order_items', $tables, true )
				&& in_array( $wpdb->prefix . 'woocommerce_order_itemmeta', $tables, true )
			) {
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are esc_sql'd above.
				$order_itemmeta_orphans = (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM `{$order_itemmeta_table}` im
					LEFT JOIN `{$order_items_table}` oi ON im.order_item_id = oi.order_item_id
					WHERE oi.order_item_id IS NULL"
				);
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}

			$term_relationships_esc = esc_sql( $wpdb->term_relationships );
			$term_taxonomy_esc      = esc_sql( $wpdb->term_taxonomy );

			if (
				in_array( $wpdb->term_relationships, $tables, true )
				&& in_array( $wpdb->term_taxonomy, $tables, true )
			) {
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are esc_sql'd above.
				$relationships_orphans = (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM `{$term_relationships_esc}` tr
					LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
					LEFT JOIN `{$term_taxonomy_esc}` tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					WHERE p.ID IS NULL OR tt.term_taxonomy_id IS NULL"
				);
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}

			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$stats = array(
				'transients_total'        => $total_transients,
				'expired_transients'      => $expired_transients,
				'wc_transients_total'     => $wc_transients,
				'oembed_caches'           => $oembed_caches,
				'expired_sessions'        => $expired_sessions,
				'orphaned_postmeta'       => $postmeta_orphans,
				'orphaned_usermeta'       => $usermeta_orphans,
				'orphaned_termmeta'       => $termmeta_orphans,
				'orphaned_commentmeta'    => $commentmeta_orphans,
				'orphaned_order_itemmeta' => $order_itemmeta_orphans,
				'orphaned_relationships'  => $relationships_orphans,
				'duplicate_postmeta'      => $duplicate_postmeta,
			);

			set_transient( $cache_key, $stats, 10 * MINUTE_IN_SECONDS );
		}

		return $stats;
	}

	/**
	 * Clean expired transients using core WordPress API.
	 *
	 * @since 0.0.1
	 * @return int Estimated number of transients affected.
	 */
	public function clean_transients() {
		global $wpdb;

		$transient_like      = $wpdb->esc_like( '_transient_' ) . '%';
		$site_transient_like = $wpdb->esc_like( '_site_transient_' ) . '%';

		// Count transients before cleanup.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$before = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$transient_like,
				$site_transient_like
			)
		);
		// phpcs:enable

		if ( function_exists( 'delete_expired_transients' ) ) {
			delete_expired_transients();
		} elseif ( class_exists( 'WC_Cache_Helper' ) && method_exists( 'WC_Cache_Helper', 'delete_expired_transients' ) ) {
			WC_Cache_Helper::delete_expired_transients();
		}

		// Re-count after cleanup.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$after = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$transient_like,
				$site_transient_like
			)
		);
		// phpcs:enable

		$deleted = max( 0, $before - $after );

		jharudar_log_activity( 'clean', 'transients', $deleted );

		delete_transient( 'jharudar_database_stats' );
		delete_transient( 'jharudar_db_health_stats' );

		return $deleted;
	}

	/**
	 * Clean WooCommerce-specific transients.
	 *
	 * @since 0.0.1
	 * @return int Number of transients deleted.
	 */
	public function clean_wc_transients() {
		global $wpdb;

		$wc_transient_like = $wpdb->esc_like( '_transient_wc_' ) . '%';
		$wc_site_like      = $wpdb->esc_like( '_site_transient_wc_' ) . '%';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wc_transient_like,
				$wc_site_like
			)
		);
		// phpcs:enable

		if ( class_exists( 'WC_Cache_Helper' ) ) {
			WC_Cache_Helper::invalidate_cache_group( 'wc_session_id' );
			WC_Cache_Helper::invalidate_cache_group( 'counts' );
		}

		jharudar_log_activity( 'clean', 'wc_transients', $deleted );

		delete_transient( 'jharudar_database_stats' );
		delete_transient( 'jharudar_db_health_stats' );

		return $deleted;
	}

	/**
	 * Clean ALL transients from the database (expired and active).
	 *
	 * @since 0.0.1
	 * @return int Number of transients deleted.
	 */
	public function clean_all_transients() {
		global $wpdb;

		$timeout_like      = $wpdb->esc_like( '_transient_timeout_' ) . '%';
		$site_timeout_like = $wpdb->esc_like( '_site_transient_timeout_' ) . '%';
		$transient_like    = $wpdb->esc_like( '_transient_' ) . '%';
		$site_like         = $wpdb->esc_like( '_site_transient_' ) . '%';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Delete all transient timeout entries first.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$timeout_like,
				$site_timeout_like
			)
		);

		// Delete all transient value entries.
		$deleted = (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$transient_like,
				$site_like
			)
		);

		// phpcs:enable

		if ( class_exists( 'WC_Cache_Helper' ) ) {
			WC_Cache_Helper::invalidate_cache_group( 'wc_session_id' );
			WC_Cache_Helper::invalidate_cache_group( 'counts' );
		}

		jharudar_log_activity( 'clean', 'all_transients', $deleted );

		delete_transient( 'jharudar_database_stats' );
		delete_transient( 'jharudar_db_health_stats' );

		return $deleted;
	}

	/**
	 * Clean oEmbed caches from postmeta.
	 *
	 * @since 0.0.1
	 * @return int Number of oEmbed cache entries deleted.
	 */
	public function clean_oembed_caches() {
		global $wpdb;

		$oembed_like = $wpdb->esc_like( '_oembed_' ) . '%';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
				$oembed_like
			)
		);
		// phpcs:enable

		jharudar_log_activity( 'clean', 'oembed_caches', $deleted );
		delete_transient( 'jharudar_database_stats' );

		return $deleted;
	}

	/**
	 * Clean expired WooCommerce sessions.
	 *
	 * @since 0.0.1
	 * @return int Number of sessions deleted.
	 */
	public function clean_sessions() {
		global $wpdb;

		$sessions_table = $wpdb->prefix . 'woocommerce_sessions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$all_tables = $wpdb->get_col( 'SHOW TABLES' );

		if ( ! in_array( $sessions_table, $all_tables, true ) ) {
			return 0;
		}

		$sessions_table_esc = esc_sql( $sessions_table );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = (int) $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is esc_sql'd.
				"DELETE FROM `{$sessions_table_esc}` WHERE session_expiry < %d",
				time()
			)
		);
		// phpcs:enable

		if ( class_exists( 'WC_Cache_Helper' ) ) {
			WC_Cache_Helper::invalidate_cache_group( 'wc_session_id' );
		}

		jharudar_log_activity( 'clean', 'sessions', $deleted );
		delete_transient( 'jharudar_database_stats' );

		return $deleted;
	}

	/**
	 * Clean duplicate postmeta entries.
	 *
	 * Keeps the row with the lowest meta_id for each unique
	 * (post_id, meta_key, meta_value) combination.
	 *
	 * @since 0.0.1
	 * @return int Number of duplicate rows deleted.
	 */
	public function clean_duplicate_meta() {
		global $wpdb;

		$batch_size = max( 50, jharudar_get_batch_size() );
		$total      = 0;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		do {
			$deleted = (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE pm1 FROM {$wpdb->postmeta} pm1
					INNER JOIN {$wpdb->postmeta} pm2
					ON pm1.post_id = pm2.post_id
					AND pm1.meta_key = pm2.meta_key
					AND pm1.meta_value = pm2.meta_value
					AND pm1.meta_id > pm2.meta_id
					LIMIT %d",
					$batch_size
				)
			);
			$total  += $deleted;
		} while ( $deleted && $deleted >= $batch_size );
		// phpcs:enable

		jharudar_log_activity( 'clean', 'duplicate_meta', $total );
		delete_transient( 'jharudar_database_stats' );

		return $total;
	}

	/**
	 * Get table analysis data for all database tables.
	 *
	 * @since 0.0.1
	 * @return array Table analysis results.
	 */
	public static function get_table_analysis() {
		global $wpdb;

		$db_name = DB_NAME;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT
					TABLE_NAME AS table_name,
					ENGINE AS engine,
					TABLE_ROWS AS row_count,
					DATA_LENGTH AS data_size,
					INDEX_LENGTH AS index_size,
					DATA_FREE AS overhead
				FROM information_schema.TABLES
				WHERE TABLE_SCHEMA = %s
				ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC',
				$db_name
			),
			ARRAY_A
		);
		// phpcs:enable

		if ( ! $results ) {
			return array(
				'tables'     => array(),
				'total_size' => 0,
				'total_rows' => 0,
				'overhead'   => 0,
			);
		}

		$total_size = 0;
		$total_rows = 0;
		$overhead   = 0;
		$tables     = array();

		$prefix = $wpdb->prefix;

		foreach ( $results as $row ) {
			$data_size  = (int) $row['data_size'];
			$index_size = (int) $row['index_size'];
			$row_oh     = (int) $row['overhead'];

			$total_size += $data_size + $index_size;
			$total_rows += (int) $row['row_count'];
			$overhead   += $row_oh;

			$tables[] = array(
				'name'       => $row['table_name'],
				'engine'     => $row['engine'],
				'rows'       => (int) $row['row_count'],
				'data_size'  => $data_size,
				'index_size' => $index_size,
				'total_size' => $data_size + $index_size,
				'overhead'   => $row_oh,
				'is_wp'      => 0 === strpos( $row['table_name'], $prefix ),
			);
		}

		return array(
			'tables'     => $tables,
			'total_size' => $total_size,
			'total_rows' => $total_rows,
			'overhead'   => $overhead,
		);
	}

	/**
	 * Get large autoloaded options that may slow site performance.
	 *
	 * @since 0.0.1
	 * @return array List of large options with name, size, and autoload status.
	 */
	public static function get_large_options() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, LENGTH(option_value) AS size, autoload
				FROM {$wpdb->options}
				WHERE autoload = 'yes'
				ORDER BY LENGTH(option_value) DESC
				LIMIT %d",
				50
			),
			ARRAY_A
		);

		$autoload_total = (int) $wpdb->get_var(
			"SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes'"
		);
		// phpcs:enable

		return array(
			'options'        => $results ? $results : array(),
			'autoload_total' => $autoload_total,
		);
	}

	/**
	 * Detect tables that may belong to inactive or uninstalled plugins.
	 *
	 * @since 0.0.1
	 * @return array List of potentially orphaned tables.
	 */
	public static function get_orphaned_tables() {
		global $wpdb;

		$prefix = $wpdb->prefix;

		// Known WordPress core tables.
		$core_tables = array(
			$prefix . 'commentmeta',
			$prefix . 'comments',
			$prefix . 'links',
			$prefix . 'options',
			$prefix . 'postmeta',
			$prefix . 'posts',
			$prefix . 'termmeta',
			$prefix . 'terms',
			$prefix . 'term_relationships',
			$prefix . 'term_taxonomy',
			$prefix . 'usermeta',
			$prefix . 'users',
		);

		// Known WooCommerce tables.
		$wc_tables = array(
			$prefix . 'woocommerce_sessions',
			$prefix . 'woocommerce_api_keys',
			$prefix . 'woocommerce_attribute_taxonomies',
			$prefix . 'woocommerce_downloadable_product_permissions',
			$prefix . 'woocommerce_order_items',
			$prefix . 'woocommerce_order_itemmeta',
			$prefix . 'woocommerce_tax_rates',
			$prefix . 'woocommerce_tax_rate_locations',
			$prefix . 'woocommerce_shipping_zones',
			$prefix . 'woocommerce_shipping_zone_locations',
			$prefix . 'woocommerce_shipping_zone_methods',
			$prefix . 'woocommerce_payment_tokens',
			$prefix . 'woocommerce_payment_tokenmeta',
			$prefix . 'woocommerce_log',
			$prefix . 'wc_product_meta_lookup',
			$prefix . 'wc_customer_lookup',
			$prefix . 'wc_category_lookup',
			$prefix . 'wc_order_stats',
			$prefix . 'wc_order_product_lookup',
			$prefix . 'wc_order_tax_lookup',
			$prefix . 'wc_order_coupon_lookup',
			$prefix . 'wc_reserved_stock',
			$prefix . 'wc_rate_limits',
			$prefix . 'wc_webhooks',
			$prefix . 'wc_download_log',
			$prefix . 'wc_admin_notes',
			$prefix . 'wc_admin_note_actions',
			$prefix . 'wc_orders',
			$prefix . 'wc_orders_meta',
			$prefix . 'wc_order_operational_data',
			$prefix . 'wc_order_addresses',
		);

		// Known Action Scheduler tables.
		$as_tables = array(
			$prefix . 'actionscheduler_actions',
			$prefix . 'actionscheduler_claims',
			$prefix . 'actionscheduler_groups',
			$prefix . 'actionscheduler_logs',
		);

		$known_tables = array_merge( $core_tables, $wc_tables, $as_tables );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$all_tables = $wpdb->get_col( 'SHOW TABLES' );

		$orphaned = array();

		foreach ( $all_tables as $table ) {
			if ( 0 !== strpos( $table, $prefix ) ) {
				continue;
			}

			if ( ! in_array( $table, $known_tables, true ) ) {
				$orphaned[] = $table;
			}
		}

		return $orphaned;
	}

	/**
	 * Optimize database tables.
	 *
	 * @since 0.0.1
	 * @param array $table_names List of table names to optimize.
	 * @return array Result with 'optimized' count and 'message'.
	 */
	public function optimize_tables( $table_names ) {
		global $wpdb;

		$prefix = $wpdb->prefix;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$all       = $wpdb->get_col( 'SHOW TABLES' );
		$optimized = 0;

		foreach ( $table_names as $table ) {
			// Only allow tables with our prefix that actually exist.
			if ( 0 !== strpos( $table, $prefix ) || ! in_array( $table, $all, true ) ) {
				continue;
			}

			$safe_table = esc_sql( $table );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is esc_sql'd and validated against SHOW TABLES.
			$wpdb->query( "OPTIMIZE TABLE `{$safe_table}`" );
			// phpcs:enable
			++$optimized;
		}

		jharudar_log_activity( 'optimize', 'tables', $optimized );

		return array(
			'optimized' => $optimized,
			'message'   => sprintf(
				/* translators: %d: number of tables optimized */
				__( '%d table(s) optimized successfully.', 'jharudar-for-woocommerce' ),
				$optimized
			),
		);
	}

	/**
	 * Repair database tables.
	 *
	 * @since 0.0.1
	 * @param array $table_names List of table names to repair.
	 * @return array Result with 'repaired' count and 'message'.
	 */
	public function repair_tables( $table_names ) {
		global $wpdb;

		$prefix = $wpdb->prefix;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$all      = $wpdb->get_col( 'SHOW TABLES' );
		$repaired = 0;

		foreach ( $table_names as $table ) {
			if ( 0 !== strpos( $table, $prefix ) || ! in_array( $table, $all, true ) ) {
				continue;
			}

			$safe_table = esc_sql( $table );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is esc_sql'd and validated against SHOW TABLES.
			$wpdb->query( "REPAIR TABLE `{$safe_table}`" );
			// phpcs:enable
			++$repaired;
		}

		jharudar_log_activity( 'repair', 'tables', $repaired );

		return array(
			'repaired' => $repaired,
			'message'  => sprintf(
				/* translators: %d: number of tables repaired */
				__( '%d table(s) repaired successfully.', 'jharudar-for-woocommerce' ),
				$repaired
			),
		);
	}

	/**
	 * Delete orphaned tables left by uninstalled plugins.
	 *
	 * @since 0.0.1
	 * @param array $table_names Specific table names to delete.
	 * @return array Result with 'deleted' count and 'message'.
	 */
	public function delete_orphaned_tables( $table_names ) {
		global $wpdb;

		$prefix   = $wpdb->prefix;
		$orphaned = self::get_orphaned_tables();
		$deleted  = 0;

		foreach ( $table_names as $table ) {
			// Only delete tables that are truly orphaned and have our prefix.
			if ( 0 !== strpos( $table, $prefix ) || ! in_array( $table, $orphaned, true ) ) {
				continue;
			}

			$safe_table = esc_sql( $table );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is esc_sql'd and validated against orphaned list.
			$wpdb->query( "DROP TABLE IF EXISTS `{$safe_table}`" );
			// phpcs:enable
			++$deleted;
		}

		jharudar_log_activity( 'delete', 'orphaned_tables', $deleted );

		return array(
			'deleted' => $deleted,
			'message' => sprintf(
				/* translators: %d: number of tables deleted */
				__( '%d orphaned table(s) deleted.', 'jharudar-for-woocommerce' ),
				$deleted
			),
		);
	}

	/**
	 * Toggle the autoload status of an option.
	 *
	 * @since 0.0.1
	 * @param string $option_name Option name to toggle.
	 * @param string $autoload    New autoload value: 'yes' or 'no'.
	 * @return bool Whether the update succeeded.
	 */
	public function toggle_autoload( $option_name, $autoload ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$wpdb->options,
			array( 'autoload' => $autoload ),
			array( 'option_name' => $option_name ),
			array( '%s' ),
			array( '%s' )
		);
		// phpcs:enable

		if ( false !== $result ) {
			jharudar_log_activity( 'toggle_autoload', $option_name, 'yes' === $autoload ? 1 : 0 );
			wp_cache_flush();
		}

		return false !== $result;
	}

	/**
	 * Clean orphaned meta/relationships.
	 *
	 * @since 0.0.1
	 * @param string $type Meta type.
	 * @return int Number of rows deleted.
	 */
	public function clean_orphaned_meta( $type ) {
		global $wpdb;

		$batch_size = max( 50, jharudar_get_batch_size() );
		$total      = 0;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		switch ( $type ) {
			case 'post':
				do {
					$deleted = (int) $wpdb->query(
						$wpdb->prepare(
							"DELETE pm FROM {$wpdb->postmeta} pm
							LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
							WHERE p.ID IS NULL
							LIMIT %d",
							$batch_size
						)
					);
					$total  += $deleted;
				} while ( $deleted && $deleted >= $batch_size );
				break;

			case 'user':
				do {
					$deleted = (int) $wpdb->query(
						$wpdb->prepare(
							"DELETE um FROM {$wpdb->usermeta} um
							LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID
							WHERE u.ID IS NULL
							LIMIT %d",
							$batch_size
						)
					);
					$total  += $deleted;
				} while ( $deleted && $deleted >= $batch_size );
				break;

			case 'term':
				do {
					$deleted = (int) $wpdb->query(
						$wpdb->prepare(
							"DELETE tm FROM {$wpdb->termmeta} tm
							LEFT JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
							WHERE t.term_id IS NULL
							LIMIT %d",
							$batch_size
						)
					);
					$total  += $deleted;
				} while ( $deleted && $deleted >= $batch_size );
				break;

			case 'comment':
				do {
					$deleted = (int) $wpdb->query(
						$wpdb->prepare(
							"DELETE cm FROM {$wpdb->commentmeta} cm
							LEFT JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
							WHERE c.comment_ID IS NULL
							LIMIT %d",
							$batch_size
						)
					);
					$total  += $deleted;
				} while ( $deleted && $deleted >= $batch_size );
				break;

			case 'order_item':
				$order_itemmeta_raw = $wpdb->prefix . 'woocommerce_order_itemmeta';
				$order_items_raw    = $wpdb->prefix . 'woocommerce_order_items';
				$order_itemmeta_esc = esc_sql( $order_itemmeta_raw );
				$order_items_esc    = esc_sql( $order_items_raw );

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$existing = $wpdb->get_col( 'SHOW TABLES' );

				if ( ! in_array( $order_itemmeta_raw, $existing, true ) || ! in_array( $order_items_raw, $existing, true ) ) {
					return 0;
				}

				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are esc_sql'd and validated.
				do {
					$deleted = (int) $wpdb->query(
						$wpdb->prepare(
							"DELETE im FROM `{$order_itemmeta_esc}` im
							LEFT JOIN `{$order_items_esc}` oi ON im.order_item_id = oi.order_item_id
							WHERE oi.order_item_id IS NULL
							LIMIT %d",
							$batch_size
						)
					);
					$total  += $deleted;
				} while ( $deleted && $deleted >= $batch_size );
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				break;

			case 'relationship':
				$tr_esc = esc_sql( $wpdb->term_relationships );
				$tt_esc = esc_sql( $wpdb->term_taxonomy );

				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are esc_sql'd from $wpdb properties.
				do {
					$deleted = (int) $wpdb->query(
						$wpdb->prepare(
							"DELETE tr FROM `{$tr_esc}` tr
							LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
							LEFT JOIN `{$tt_esc}` tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
							WHERE p.ID IS NULL OR tt.term_taxonomy_id IS NULL
							LIMIT %d",
							$batch_size
						)
					);
					$total  += $deleted;
				} while ( $deleted && $deleted >= $batch_size );
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				break;

			default:
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				return 0;
		}

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		jharudar_log_activity( 'clean', 'orphaned_' . $type . '_meta', $total );

		delete_transient( 'jharudar_database_stats' );

		return $total;
	}

	/**
	 * AJAX handler: Get database stats.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_database_stats() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) )
			);
		}

		delete_transient( 'jharudar_database_stats' );

		wp_send_json_success( self::get_statistics() );
	}

	/**
	 * AJAX handler: Clean expired transients.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_clean_transients() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) )
			);
		}

		$deleted = $this->clean_transients();

		wp_send_json_success(
			array(
				'deleted' => $deleted,
				'message' => sprintf(
					/* translators: %d: number of transients deleted */
					__( 'Expired transients cleaned. Approximately %d entries removed.', 'jharudar-for-woocommerce' ),
					$deleted
				),
			)
		);
	}

	/**
	 * AJAX handler: Clean WooCommerce transients.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_clean_wc_transients() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) )
			);
		}

		$deleted = $this->clean_wc_transients();

		wp_send_json_success(
			array(
				'deleted' => $deleted,
				'message' => sprintf(
					/* translators: %d: number of WooCommerce transients deleted */
					__( 'WooCommerce transients cleaned. %d entries removed.', 'jharudar-for-woocommerce' ),
					$deleted
				),
			)
		);
	}

	/**
	 * AJAX handler: Clean all transients.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_clean_all_transients() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) )
			);
		}

		$deleted = $this->clean_all_transients();

		wp_send_json_success(
			array(
				'deleted' => $deleted,
				'message' => sprintf(
					/* translators: %d: number of transients deleted */
					__( 'All transients cleaned. %d entries removed. WordPress and plugins will regenerate caches as needed.', 'jharudar-for-woocommerce' ),
					$deleted
				),
			)
		);
	}

	/**
	 * AJAX handler: Clean orphaned meta.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_clean_orphaned_meta() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) )
			);
		}

		$type = isset( $_POST['meta_type'] ) ? sanitize_key( wp_unslash( $_POST['meta_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $type ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid meta type.', 'jharudar-for-woocommerce' ) )
			);
		}

		$allowed_types = array( 'post', 'user', 'term', 'comment', 'order_item', 'relationship' );

		if ( ! in_array( $type, $allowed_types, true ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Unsupported meta type.', 'jharudar-for-woocommerce' ) )
			);
		}

		$deleted = $this->clean_orphaned_meta( $type );

		wp_send_json_success(
			array(
				'deleted' => $deleted,
				'message' => sprintf(
					/* translators: 1: meta type, 2: number of rows deleted */
					__( 'Orphaned %1$s meta cleaned. %2$d rows removed.', 'jharudar-for-woocommerce' ),
					$type,
					$deleted
				),
			)
		);
	}

	/**
	 * Regenerate WooCommerce customer lookup table.
	 *
	 * @since 0.0.1
	 * @return array Result with 'processed' count and 'message'.
	 */
	public function regenerate_customer_lookup() {
		global $wpdb;

		$lookup_table = $wpdb->prefix . 'wc_customer_lookup';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $lookup_table )
		);
		// phpcs:enable

		if ( ! $table_exists ) {
			return array(
				'processed' => 0,
				'message'   => __( 'The wc_customer_lookup table does not exist. WooCommerce may not be installed properly.', 'jharudar-for-woocommerce' ),
			);
		}

		$safe_table = esc_sql( $lookup_table );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is esc_sql'd and verified via SHOW TABLES.
		$wpdb->query( "TRUNCATE TABLE `{$safe_table}`" );
		// phpcs:enable

		$processed = 0;

		if ( class_exists( '\\Automattic\\WooCommerce\\Admin\\API\\Reports\\Customers\\DataStore' ) ) {
			// This is a WooCommerce core filter, intentionally not prefixed.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$customer_roles = apply_filters( 'woocommerce_analytics_import_customer_roles', array( 'customer' ) );

			$user_query = new \WP_User_Query(
				array(
					'fields'   => 'ID',
					'role__in' => $customer_roles,
				)
			);

			$user_ids = $user_query->get_results();

			if ( ! empty( $user_ids ) ) {
				foreach ( $user_ids as $user_id ) {
					\Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::update_registered_customer( (int) $user_id );
					++$processed;
				}
			}
		}

		if ( class_exists( 'WC_Cache_Helper' ) && method_exists( 'WC_Cache_Helper', 'get_transient_version' ) ) {
			WC_Cache_Helper::get_transient_version( 'customers', true );
		}

		jharudar_log_activity( 'regenerate', 'customer_lookup', $processed );
		delete_transient( 'jharudar_database_stats' );

		return array(
			'processed' => $processed,
			'message'   => sprintf(
				/* translators: %d: number of customers re-indexed */
				__( 'Customer lookup table regenerated. %d customers re-indexed.', 'jharudar-for-woocommerce' ),
				$processed
			),
		);
	}

	/**
	 * Repair WooCommerce order stats by regenerating the lookup table.
	 *
	 * @since 0.0.1
	 * @return array Result with 'processed' count and 'message'.
	 */
	public function repair_order_stats() {
		global $wpdb;

		$stats_table = $wpdb->prefix . 'wc_order_stats';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $stats_table )
		);
		// phpcs:enable

		if ( ! $table_exists ) {
			return array(
				'processed' => 0,
				'message'   => __( 'The wc_order_stats table does not exist. WooCommerce Analytics may not be active.', 'jharudar-for-woocommerce' ),
			);
		}

		$processed  = 0;
		$safe_table = esc_sql( $stats_table );

		if ( class_exists( '\\Automattic\\WooCommerce\\Admin\\API\\Reports\\Orders\\Stats\\DataStore' ) ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is esc_sql'd and verified via SHOW TABLES.
			$wpdb->query( "TRUNCATE TABLE `{$safe_table}`" );
			// phpcs:enable

			$order_statuses = wc_get_order_statuses();
			$status_keys    = array_keys( $order_statuses );

			$orders = wc_get_orders(
				array(
					'limit'  => -1,
					'return' => 'ids',
					'status' => $status_keys,
				)
			);

			if ( ! empty( $orders ) ) {
				foreach ( $orders as $order_id ) {
					$order = wc_get_order( $order_id );
					if ( ! $order ) {
						continue;
					}
					\Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::update( $order );
					++$processed;
				}
			}
		}

		if ( class_exists( 'WC_Cache_Helper' ) ) {
			WC_Cache_Helper::invalidate_cache_group( 'counts' );
		}

		jharudar_log_activity( 'repair', 'order_stats', $processed );
		delete_transient( 'jharudar_database_stats' );

		return array(
			'processed' => $processed,
			'message'   => sprintf(
				/* translators: %d: number of orders re-synced */
				__( 'Order stats repaired. %d orders re-synced.', 'jharudar-for-woocommerce' ),
				$processed
			),
		);
	}

	/**
	 * AJAX handler: Regenerate customer lookup table.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_regenerate_customer_lookup() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) )
			);
		}

		$result = $this->regenerate_customer_lookup();

		wp_send_json_success(
			array(
				'deleted' => $result['processed'],
				'message' => $result['message'],
			)
		);
	}

	/**
	 * AJAX handler: Repair order stats.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_repair_order_stats() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) )
			);
		}

		$result = $this->repair_order_stats();

		wp_send_json_success(
			array(
				'deleted' => $result['processed'],
				'message' => $result['message'],
			)
		);
	}

	/**
	 * AJAX handler: Clean oEmbed caches.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_clean_oembed_caches() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$deleted = $this->clean_oembed_caches();

		wp_send_json_success(
			array(
				'deleted' => $deleted,
				'message' => sprintf(
					/* translators: %d: number of oEmbed entries deleted */
					__( 'oEmbed caches cleaned. %d entries removed.', 'jharudar-for-woocommerce' ),
					$deleted
				),
			)
		);
	}

	/**
	 * AJAX handler: Clean expired sessions.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_clean_sessions() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$deleted = $this->clean_sessions();

		wp_send_json_success(
			array(
				'deleted' => $deleted,
				'message' => sprintf(
					/* translators: %d: number of sessions deleted */
					__( 'Expired sessions cleaned. %d session(s) removed.', 'jharudar-for-woocommerce' ),
					$deleted
				),
			)
		);
	}

	/**
	 * AJAX handler: Clean duplicate meta.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_clean_duplicate_meta() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$deleted = $this->clean_duplicate_meta();

		wp_send_json_success(
			array(
				'deleted' => $deleted,
				'message' => sprintf(
					/* translators: %d: number of duplicate rows deleted */
					__( 'Duplicate meta cleaned. %d duplicate row(s) removed.', 'jharudar-for-woocommerce' ),
					$deleted
				),
			)
		);
	}

	/**
	 * AJAX handler: Get table analysis.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_get_table_analysis() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$analysis      = self::get_table_analysis();
		$large_options = self::get_large_options();
		$orphaned      = self::get_orphaned_tables();

		wp_send_json_success(
			array(
				'tables'          => $analysis['tables'],
				'total_size'      => $analysis['total_size'],
				'total_rows'      => $analysis['total_rows'],
				'overhead'        => $analysis['overhead'],
				'large_options'   => $large_options['options'],
				'autoload_total'  => $large_options['autoload_total'],
				'orphaned_tables' => $orphaned,
			)
		);
	}

	/**
	 * AJAX handler: Optimize tables.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_optimize_tables() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$tables = isset( $_POST['tables'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['tables'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $tables ) ) {
			wp_send_json_error( array( 'message' => __( 'No tables selected.', 'jharudar-for-woocommerce' ) ) );
		}

		// Sentinel value: optimize all tables with the site prefix.
		if ( in_array( '_all_', $tables, true ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$tables = $wpdb->get_col( 'SHOW TABLES' );
		}

		$result = $this->optimize_tables( $tables );

		wp_send_json_success(
			array(
				'deleted' => $result['optimized'],
				'message' => $result['message'],
			)
		);
	}

	/**
	 * AJAX handler: Repair tables.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_repair_tables() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$tables = isset( $_POST['tables'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['tables'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $tables ) ) {
			wp_send_json_error( array( 'message' => __( 'No tables selected.', 'jharudar-for-woocommerce' ) ) );
		}

		// Sentinel value: repair all tables with the site prefix.
		if ( in_array( '_all_', $tables, true ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$tables = $wpdb->get_col( 'SHOW TABLES' );
		}

		$result = $this->repair_tables( $tables );

		wp_send_json_success(
			array(
				'deleted' => $result['repaired'],
				'message' => $result['message'],
			)
		);
	}

	/**
	 * AJAX handler: Delete orphaned tables.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_delete_orphaned_tables() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$tables = isset( $_POST['tables'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['tables'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $tables ) ) {
			wp_send_json_error( array( 'message' => __( 'No tables selected.', 'jharudar-for-woocommerce' ) ) );
		}

		$result = $this->delete_orphaned_tables( $tables );

		wp_send_json_success(
			array(
				'deleted' => $result['deleted'],
				'message' => $result['message'],
			)
		);
	}

	/**
	 * AJAX handler: Toggle option autoload.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function ajax_toggle_autoload() {
		check_ajax_referer( 'jharudar_admin_nonce', 'nonce' );

		if ( ! jharudar_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jharudar-for-woocommerce' ) ) );
		}

		$option_name = isset( $_POST['option_name'] ) ? sanitize_text_field( wp_unslash( $_POST['option_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$autoload    = isset( $_POST['autoload'] ) ? sanitize_key( wp_unslash( $_POST['autoload'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $option_name ) || ! in_array( $autoload, array( 'yes', 'no' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'jharudar-for-woocommerce' ) ) );
		}

		$success = $this->toggle_autoload( $option_name, $autoload );

		if ( $success ) {
			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: 1: option name, 2: autoload status */
						__( 'Autoload for "%1$s" set to "%2$s".', 'jharudar-for-woocommerce' ),
						$option_name,
						$autoload
					),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update autoload status.', 'jharudar-for-woocommerce' ) ) );
		}
	}
}
