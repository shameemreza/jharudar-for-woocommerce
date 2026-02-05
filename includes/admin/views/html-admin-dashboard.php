<?php
/**
 * Dashboard view.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get database health stats with caching.
 *
 * @since 0.0.1
 * @return array Database health stats.
 */
function jharudar_get_database_health_stats() {
	$jharudar_cache_key = 'jharudar_db_health_stats';
	$jharudar_stats     = get_transient( $jharudar_cache_key );

	if ( false === $jharudar_stats ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$jharudar_transients = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'"
		);

		$jharudar_orphaned = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.ID IS NULL"
		);

		$jharudar_revisions = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
				'revision'
			)
		);

		$jharudar_autodrafts = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s",
				'auto-draft'
			)
		);
		// phpcs:enable

		$jharudar_stats = array(
			'transients'  => (int) $jharudar_transients,
			'orphaned'    => (int) $jharudar_orphaned,
			'revisions'   => (int) $jharudar_revisions,
			'autodrafts'  => (int) $jharudar_autodrafts,
		);

		// Cache for 5 minutes.
		set_transient( $jharudar_cache_key, $jharudar_stats, 5 * MINUTE_IN_SECONDS );
	}

	return $jharudar_stats;
}

/**
 * Render dashboard page.
 *
 * @since 0.0.1
 * @return void
 */
function jharudar_render_dashboard_page() {
	$jharudar_product_count  = wp_count_posts( 'product' );
	$jharudar_total_products = isset( $jharudar_product_count->publish ) ? (int) $jharudar_product_count->publish : 0;

	$jharudar_order_count  = wp_count_posts( 'shop_order' );
	$jharudar_total_orders = 0;
	if ( $jharudar_order_count ) {
		foreach ( $jharudar_order_count as $jharudar_status => $jharudar_count ) {
			if ( 'auto-draft' !== $jharudar_status ) {
				$jharudar_total_orders += (int) $jharudar_count;
			}
		}
	}

	$jharudar_customer_count = count(
		get_users(
			array(
				'role'   => 'customer',
				'fields' => 'ID',
			)
		)
	);

	$jharudar_coupon_count  = wp_count_posts( 'shop_coupon' );
	$jharudar_total_coupons = isset( $jharudar_coupon_count->publish ) ? (int) $jharudar_coupon_count->publish : 0;

	$jharudar_db_stats = jharudar_get_database_health_stats();
	?>

	<div class="jharudar-dashboard">
		<div class="jharudar-dashboard-stats">
			<div class="jharudar-stat-box">
				<span class="dashicons dashicons-archive"></span>
				<div class="jharudar-stat-content">
					<span class="jharudar-stat-number"><?php echo esc_html( number_format_i18n( $jharudar_total_products ) ); ?></span>
					<span class="jharudar-stat-label"><?php esc_html_e( 'Products', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=jharudar&tab=products' ) ); ?>" class="jharudar-stat-link">
					<?php esc_html_e( 'Manage', 'jharudar-for-woocommerce' ); ?>
				</a>
			</div>

			<div class="jharudar-stat-box">
				<span class="dashicons dashicons-cart"></span>
				<div class="jharudar-stat-content">
					<span class="jharudar-stat-number"><?php echo esc_html( number_format_i18n( $jharudar_total_orders ) ); ?></span>
					<span class="jharudar-stat-label"><?php esc_html_e( 'Orders', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=jharudar&tab=orders' ) ); ?>" class="jharudar-stat-link">
					<?php esc_html_e( 'Manage', 'jharudar-for-woocommerce' ); ?>
				</a>
			</div>

			<div class="jharudar-stat-box">
				<span class="dashicons dashicons-groups"></span>
				<div class="jharudar-stat-content">
					<span class="jharudar-stat-number"><?php echo esc_html( number_format_i18n( $jharudar_customer_count ) ); ?></span>
					<span class="jharudar-stat-label"><?php esc_html_e( 'Customers', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=jharudar&tab=customers' ) ); ?>" class="jharudar-stat-link">
					<?php esc_html_e( 'Manage', 'jharudar-for-woocommerce' ); ?>
				</a>
			</div>

			<div class="jharudar-stat-box">
				<span class="dashicons dashicons-tickets-alt"></span>
				<div class="jharudar-stat-content">
					<span class="jharudar-stat-number"><?php echo esc_html( number_format_i18n( $jharudar_total_coupons ) ); ?></span>
					<span class="jharudar-stat-label"><?php esc_html_e( 'Coupons', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=jharudar&tab=coupons' ) ); ?>" class="jharudar-stat-link">
					<?php esc_html_e( 'Manage', 'jharudar-for-woocommerce' ); ?>
				</a>
			</div>
		</div>

		<div class="jharudar-dashboard-sections">
			<div class="jharudar-dashboard-section">
				<h3><?php esc_html_e( 'Quick Actions', 'jharudar-for-woocommerce' ); ?></h3>
				<div class="jharudar-quick-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=jharudar&tab=database' ) ); ?>" class="jharudar-quick-action">
						<span class="dashicons dashicons-database"></span>
						<span><?php esc_html_e( 'Optimize Database', 'jharudar-for-woocommerce' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=jharudar&tab=taxonomy' ) ); ?>" class="jharudar-quick-action">
						<span class="dashicons dashicons-tag"></span>
						<span><?php esc_html_e( 'Clean Taxonomy', 'jharudar-for-woocommerce' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=jharudar&tab=store' ) ); ?>" class="jharudar-quick-action">
						<span class="dashicons dashicons-store"></span>
						<span><?php esc_html_e( 'Store Data', 'jharudar-for-woocommerce' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=jharudar&tab=gdpr' ) ); ?>" class="jharudar-quick-action">
						<span class="dashicons dashicons-shield"></span>
						<span><?php esc_html_e( 'GDPR Tools', 'jharudar-for-woocommerce' ); ?></span>
					</a>
				</div>
			</div>

			<div class="jharudar-dashboard-section">
				<h3><?php esc_html_e( 'Database Health', 'jharudar-for-woocommerce' ); ?></h3>
				<div class="jharudar-health-overview">
					<div class="jharudar-health-item">
						<span class="jharudar-health-label"><?php esc_html_e( 'Transients', 'jharudar-for-woocommerce' ); ?></span>
						<span class="jharudar-health-value"><?php echo esc_html( number_format_i18n( $jharudar_db_stats['transients'] ) ); ?></span>
					</div>
					<div class="jharudar-health-item">
						<span class="jharudar-health-label"><?php esc_html_e( 'Orphaned Meta', 'jharudar-for-woocommerce' ); ?></span>
						<span class="jharudar-health-value <?php echo $jharudar_db_stats['orphaned'] > 0 ? 'jharudar-warning' : ''; ?>">
							<?php echo esc_html( number_format_i18n( $jharudar_db_stats['orphaned'] ) ); ?>
						</span>
					</div>
					<div class="jharudar-health-item">
						<span class="jharudar-health-label"><?php esc_html_e( 'Revisions', 'jharudar-for-woocommerce' ); ?></span>
						<span class="jharudar-health-value"><?php echo esc_html( number_format_i18n( $jharudar_db_stats['revisions'] ) ); ?></span>
					</div>
					<div class="jharudar-health-item">
						<span class="jharudar-health-label"><?php esc_html_e( 'Auto-drafts', 'jharudar-for-woocommerce' ); ?></span>
						<span class="jharudar-health-value"><?php echo esc_html( number_format_i18n( $jharudar_db_stats['autodrafts'] ) ); ?></span>
					</div>
				</div>
			</div>

			<?php if ( jharudar()->is_extension_active( 'subscriptions' ) || jharudar()->is_extension_active( 'memberships' ) || jharudar()->is_extension_active( 'bookings' ) ) : ?>
			<div class="jharudar-dashboard-section">
				<h3><?php esc_html_e( 'Active Extensions', 'jharudar-for-woocommerce' ); ?></h3>
				<div class="jharudar-extensions-list">
					<?php if ( jharudar()->is_extension_active( 'subscriptions' ) ) : ?>
					<div class="jharudar-extension-item">
						<span class="dashicons dashicons-update"></span>
						<span><?php esc_html_e( 'WooCommerce Subscriptions', 'jharudar-for-woocommerce' ); ?></span>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=jharudar&tab=subscriptions' ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Manage', 'jharudar-for-woocommerce' ); ?>
						</a>
					</div>
					<?php endif; ?>

					<?php if ( jharudar()->is_extension_active( 'memberships' ) ) : ?>
					<div class="jharudar-extension-item">
						<span class="dashicons dashicons-groups"></span>
						<span><?php esc_html_e( 'WooCommerce Memberships', 'jharudar-for-woocommerce' ); ?></span>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=jharudar&tab=memberships' ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Manage', 'jharudar-for-woocommerce' ); ?>
						</a>
					</div>
					<?php endif; ?>

					<?php if ( jharudar()->is_extension_active( 'bookings' ) ) : ?>
					<div class="jharudar-extension-item">
						<span class="dashicons dashicons-calendar-alt"></span>
						<span><?php esc_html_e( 'WooCommerce Bookings', 'jharudar-for-woocommerce' ); ?></span>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=jharudar&tab=bookings' ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Manage', 'jharudar-for-woocommerce' ); ?>
						</a>
					</div>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<div class="jharudar-dashboard-footer">
			<div class="jharudar-info-box">
				<h4><?php esc_html_e( 'About Jharudar', 'jharudar-for-woocommerce' ); ?></h4>
				<p>
					<?php esc_html_e( 'Jharudar means sweeper or cleaner in Bengali and Hindi. Like a diligent sweeper who keeps spaces clean and organized, this plugin helps you maintain a tidy WooCommerce store by removing unwanted data safely and efficiently.', 'jharudar-for-woocommerce' ); ?>
				</p>
			</div>
		</div>
	</div>
	<?php
}

// Render the page.
jharudar_render_dashboard_page();
