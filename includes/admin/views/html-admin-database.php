<?php
/**
 * Database admin view.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current sub-tab.
$jharudar_current_subtab  = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'transients'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$jharudar_allowed_subtabs = array( 'transients', 'sessions', 'orphaned', 'table_analysis', 'optimize', 'tools' );
if ( ! in_array( $jharudar_current_subtab, $jharudar_allowed_subtabs, true ) ) {
	$jharudar_current_subtab = 'transients';
}

// Get database statistics.
$jharudar_db_stats = Jharudar_Database::get_statistics();
?>

<div class="jharudar-database-page">
	<!-- Sub-tabs -->
	<div class="jharudar-subtabs">
		<a href="<?php echo esc_url( jharudar_admin_url( 'database', array( 'subtab' => 'transients' ) ) ); ?>"
		   class="jharudar-subtab <?php echo 'transients' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Transients', 'jharudar-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( jharudar_admin_url( 'database', array( 'subtab' => 'sessions' ) ) ); ?>"
		   class="jharudar-subtab <?php echo 'sessions' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Sessions', 'jharudar-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( jharudar_admin_url( 'database', array( 'subtab' => 'orphaned' ) ) ); ?>"
		   class="jharudar-subtab <?php echo 'orphaned' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Orphaned Data', 'jharudar-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( jharudar_admin_url( 'database', array( 'subtab' => 'table_analysis' ) ) ); ?>"
		   class="jharudar-subtab <?php echo 'table_analysis' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Table Analysis', 'jharudar-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( jharudar_admin_url( 'database', array( 'subtab' => 'optimize' ) ) ); ?>"
		   class="jharudar-subtab <?php echo 'optimize' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Optimize Tables', 'jharudar-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( jharudar_admin_url( 'database', array( 'subtab' => 'tools' ) ) ); ?>"
		   class="jharudar-subtab <?php echo 'tools' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Tools', 'jharudar-for-woocommerce' ); ?>
		</a>
	</div>

	<?php if ( 'sessions' === $jharudar_current_subtab ) : ?>
		<!-- Sessions Section -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Sessions & Embed Caches', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Clean expired WooCommerce customer sessions and oEmbed cache data stored in the database.', 'jharudar-for-woocommerce' ); ?>
				</p>
			</div>

			<!-- Quick Stats -->
			<div class="jharudar-quick-stats">
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number" id="jharudar-expired-sessions">
						<?php echo esc_html( number_format_i18n( $jharudar_db_stats['expired_sessions'] ) ); ?>
					</span>
					<span class="stat-label"><?php esc_html_e( 'Expired Sessions', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat">
					<span class="stat-number" id="jharudar-oembed-caches">
						<?php echo esc_html( number_format_i18n( $jharudar_db_stats['oembed_caches'] ) ); ?>
					</span>
					<span class="stat-label"><?php esc_html_e( 'oEmbed Caches', 'jharudar-for-woocommerce' ); ?></span>
				</div>
			</div>

			<div class="notice notice-info inline">
				<p>
					<?php esc_html_e( 'WooCommerce sessions store cart and checkout data for logged-in and guest users. Expired sessions are no longer needed. oEmbed caches store rendered previews of embedded content (YouTube, Twitter, etc.).', 'jharudar-for-woocommerce' ); ?>
				</p>
			</div>

			<div class="jharudar-database-tools-grid">
				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Clean Expired Sessions', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description">
						<?php esc_html_e( 'Removes expired WooCommerce sessions from the woocommerce_sessions table. Active customer sessions are not affected.', 'jharudar-for-woocommerce' ); ?>
					</p>
					<button type="button" class="button button-primary" id="jharudar-clean-sessions">
						<?php esc_html_e( 'Clean Expired Sessions', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>

				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Clean oEmbed Caches', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description">
						<?php esc_html_e( 'Removes cached oEmbed data from postmeta. WordPress will re-fetch embed previews on next page view.', 'jharudar-for-woocommerce' ); ?>
					</p>
					<button type="button" class="button button-primary" id="jharudar-clean-oembed">
						<?php esc_html_e( 'Clean oEmbed Caches', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-database-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> <?php esc_html_e( 'items processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>
		</div>

	<?php elseif ( 'orphaned' === $jharudar_current_subtab ) : ?>
		<!-- Orphaned Data Section -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Orphaned & Duplicate Data Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Remove metadata without valid parent records and duplicate meta entries. This helps keep your database lean and fast.', 'jharudar-for-woocommerce' ); ?>
				</p>
			</div>

			<!-- Quick Stats -->
			<div class="jharudar-quick-stats">
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number" id="jharudar-orphaned-postmeta-count">
						<?php echo esc_html( number_format_i18n( $jharudar_db_stats['orphaned_postmeta'] ) ); ?>
					</span>
					<span class="stat-label"><?php esc_html_e( 'Orphaned Post Meta', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number" id="jharudar-orphaned-usermeta-count">
						<?php echo esc_html( number_format_i18n( $jharudar_db_stats['orphaned_usermeta'] ) ); ?>
					</span>
					<span class="stat-label"><?php esc_html_e( 'Orphaned User Meta', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number" id="jharudar-orphaned-termmeta-count">
						<?php echo esc_html( number_format_i18n( $jharudar_db_stats['orphaned_termmeta'] ) ); ?>
					</span>
					<span class="stat-label"><?php esc_html_e( 'Orphaned Term Meta', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number" id="jharudar-orphaned-commentmeta-count">
						<?php echo esc_html( number_format_i18n( $jharudar_db_stats['orphaned_commentmeta'] ) ); ?>
					</span>
					<span class="stat-label"><?php esc_html_e( 'Orphaned Comment Meta', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number" id="jharudar-orphaned-order-itemmeta-count">
						<?php echo esc_html( number_format_i18n( $jharudar_db_stats['orphaned_order_itemmeta'] ) ); ?>
					</span>
					<span class="stat-label"><?php esc_html_e( 'Orphaned Order Item Meta', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number" id="jharudar-orphaned-relationships-count">
						<?php echo esc_html( number_format_i18n( $jharudar_db_stats['orphaned_relationships'] ) ); ?>
					</span>
					<span class="stat-label"><?php esc_html_e( 'Orphaned Relationships', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number" id="jharudar-duplicate-postmeta-count">
						<?php echo esc_html( number_format_i18n( $jharudar_db_stats['duplicate_postmeta'] ) ); ?>
					</span>
					<span class="stat-label"><?php esc_html_e( 'Duplicate Post Meta', 'jharudar-for-woocommerce' ); ?></span>
				</div>
			</div>

			<div class="notice notice-warning inline">
				<p>
					<?php esc_html_e( 'Before running these tools, we strongly recommend taking a full database backup. Although these operations are designed to be safe, they permanently remove data.', 'jharudar-for-woocommerce' ); ?>
				</p>
			</div>

			<div class="jharudar-database-tools-grid">
				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Clean Orphaned Post Meta', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Removes post meta entries where the related post no longer exists.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary jharudar-clean-orphaned-meta" data-meta-type="post">
						<?php esc_html_e( 'Clean Orphaned Post Meta', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>

				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Clean Orphaned User Meta', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Removes user meta entries where the related user no longer exists.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary jharudar-clean-orphaned-meta" data-meta-type="user">
						<?php esc_html_e( 'Clean Orphaned User Meta', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>

				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Clean Orphaned Term Meta', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Removes term meta entries where the related term no longer exists.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary jharudar-clean-orphaned-meta" data-meta-type="term">
						<?php esc_html_e( 'Clean Orphaned Term Meta', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>

				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Clean Orphaned Comment Meta', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Removes comment meta entries where the related comment no longer exists.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary jharudar-clean-orphaned-meta" data-meta-type="comment">
						<?php esc_html_e( 'Clean Orphaned Comment Meta', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>

				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Clean Orphaned Order Item Meta', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Removes order item meta entries where the related order item no longer exists.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary jharudar-clean-orphaned-meta" data-meta-type="order_item">
						<?php esc_html_e( 'Clean Orphaned Order Item Meta', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>

				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Clean Orphaned Relationships', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Removes term relationships that no longer have a valid post or term assignment.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary jharudar-clean-orphaned-meta" data-meta-type="relationship">
						<?php esc_html_e( 'Clean Orphaned Relationships', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>

				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Clean Duplicate Post Meta', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Removes duplicate postmeta rows where the same post_id, meta_key, and meta_value appear more than once. Keeps the original entry.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary" id="jharudar-clean-duplicate-meta">
						<?php esc_html_e( 'Clean Duplicate Meta', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-database-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> <?php esc_html_e( 'rows processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>
		</div>

	<?php elseif ( 'table_analysis' === $jharudar_current_subtab ) : ?>
		<!-- Table Analysis Section -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Database Size & Table Analysis', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Analyze your database tables to understand size distribution, identify large autoloaded options, and detect orphaned tables from uninstalled plugins.', 'jharudar-for-woocommerce' ); ?>
				</p>
			</div>

			<!-- Database Size Stats (populated via AJAX) -->
			<div class="jharudar-quick-stats">
				<div class="jharudar-quick-stat">
					<span class="stat-number" id="jharudar-db-total-size">&mdash;</span>
					<span class="stat-label"><?php esc_html_e( 'Database Size', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat">
					<span class="stat-number" id="jharudar-db-total-tables">&mdash;</span>
					<span class="stat-label"><?php esc_html_e( 'Tables', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number" id="jharudar-db-overhead">&mdash;</span>
					<span class="stat-label"><?php esc_html_e( 'Overhead', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat">
					<span class="stat-number" id="jharudar-db-autoload-size">&mdash;</span>
					<span class="stat-label"><?php esc_html_e( 'Autoload Size', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number" id="jharudar-db-orphaned-tables">&mdash;</span>
					<span class="stat-label"><?php esc_html_e( 'Orphaned Tables', 'jharudar-for-woocommerce' ); ?></span>
				</div>
			</div>

			<div class="notice notice-info inline">
				<p><?php esc_html_e( 'Click "Load Analysis" to query the database for table sizes, autoload data, and orphaned tables. This may take a few seconds on large databases.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<p>
				<button type="button" class="button button-primary" id="jharudar-load-table-analysis">
					<?php esc_html_e( 'Load Analysis', 'jharudar-for-woocommerce' ); ?>
				</button>
			</p>

			<!-- Table List (populated via AJAX) -->
			<div id="jharudar-table-analysis-results" class="jharudar-hidden">
				<h4><?php esc_html_e( 'All Tables', 'jharudar-for-woocommerce' ); ?></h4>
				<table class="wp-list-table widefat fixed striped" id="jharudar-tables-list">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Table', 'jharudar-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Engine', 'jharudar-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Rows', 'jharudar-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Size', 'jharudar-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Overhead', 'jharudar-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>

				<h4 class="jharudar-section-heading"><?php esc_html_e( 'Large Autoloaded Options (Top 50)', 'jharudar-for-woocommerce' ); ?></h4>
				<table class="wp-list-table widefat fixed striped" id="jharudar-large-options-list">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Option Name', 'jharudar-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Size', 'jharudar-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Autoload', 'jharudar-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>

				<div id="jharudar-orphaned-tables-section" class="jharudar-hidden">
					<h4 class="jharudar-section-heading"><?php esc_html_e( 'Orphaned Tables (Unknown Plugin Tables)', 'jharudar-for-woocommerce' ); ?></h4>
					<div class="notice notice-warning inline">
						<p><?php esc_html_e( 'These tables do not belong to WordPress core, WooCommerce, or Action Scheduler. They may have been left by uninstalled plugins. Verify before deleting.', 'jharudar-for-woocommerce' ); ?></p>
					</div>
					<div id="jharudar-orphaned-tables-list"></div>
					<p>
						<button type="button" class="button" id="jharudar-delete-orphaned-tables" disabled>
							<?php esc_html_e( 'Delete Selected Orphaned Tables', 'jharudar-for-woocommerce' ); ?>
						</button>
					</p>
				</div>
			</div>
		</div>

	<?php elseif ( 'optimize' === $jharudar_current_subtab ) : ?>
		<!-- Optimize Tables Section -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Optimize & Repair Tables', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Optimize tables to reclaim unused space and defragment data files. Repair tables if you suspect corruption.', 'jharudar-for-woocommerce' ); ?>
				</p>
			</div>

			<div class="notice notice-info inline">
				<p><?php esc_html_e( 'OPTIMIZE TABLE reclaims unused space and defragments the data file. REPAIR TABLE attempts to fix corrupted tables. Both are safe for InnoDB and MyISAM engines.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<div class="jharudar-database-tools-grid">
				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Optimize All Tables', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Runs OPTIMIZE TABLE on all WordPress and WooCommerce tables to reclaim disk space and improve query performance.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary" id="jharudar-optimize-all-tables">
						<?php esc_html_e( 'Optimize All Tables', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>

				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Repair All Tables', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Runs REPAIR TABLE on all WordPress and WooCommerce tables. Use this if you suspect data corruption or encounter database errors.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary" id="jharudar-repair-all-tables">
						<?php esc_html_e( 'Repair All Tables', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-database-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> <?php esc_html_e( 'tables processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>
		</div>

	<?php elseif ( 'tools' === $jharudar_current_subtab ) : ?>
		<!-- Tools Section -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'WooCommerce Repair Tools', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'These tools help fix common WooCommerce data integrity issues. Use them when analytics reports look incorrect or customer data is out of sync.', 'jharudar-for-woocommerce' ); ?>
				</p>
			</div>

			<div class="notice notice-warning inline">
				<p>
					<?php esc_html_e( 'These operations may take a while on stores with many orders or customers. We recommend running them during low-traffic periods and having a recent database backup.', 'jharudar-for-woocommerce' ); ?>
				</p>
			</div>

			<div class="jharudar-database-tools-grid">
				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Regenerate Customer Lookup Table', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Truncates and rebuilds the wc_customer_lookup table used by WooCommerce Analytics. Fixes missing or incorrect customer data in reports.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary" id="jharudar-regenerate-customer-lookup">
						<?php esc_html_e( 'Regenerate Customer Lookup', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>

				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Repair Order Stats', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Truncates and rebuilds the wc_order_stats table. Fixes incorrect revenue totals, order counts, and other WooCommerce Analytics data.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary" id="jharudar-repair-order-stats">
						<?php esc_html_e( 'Repair Order Stats', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-database-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> <?php esc_html_e( 'records processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>
		</div>

	<?php else : ?>
		<!-- Transients Section (Default) -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Transients Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Transients are temporary cached values stored in the options table by WordPress core, WooCommerce, and other plugins. Cleaning them can reduce database size and improve performance.', 'jharudar-for-woocommerce' ); ?>
				</p>
			</div>

			<!-- Quick Stats -->
			<div class="jharudar-quick-stats">
				<div class="jharudar-quick-stat">
					<span class="stat-number" id="jharudar-transients-total">
						<?php echo esc_html( number_format_i18n( $jharudar_db_stats['transients_total'] ) ); ?>
					</span>
					<span class="stat-label"><?php esc_html_e( 'Total Transients', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number" id="jharudar-transients-expired">
						<?php echo esc_html( number_format_i18n( $jharudar_db_stats['expired_transients'] ) ); ?>
					</span>
					<span class="stat-label"><?php esc_html_e( 'Expired', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat">
					<span class="stat-number" id="jharudar-transients-wc-total">
						<?php echo esc_html( number_format_i18n( $jharudar_db_stats['wc_transients_total'] ) ); ?>
					</span>
					<span class="stat-label"><?php esc_html_e( 'WooCommerce', 'jharudar-for-woocommerce' ); ?></span>
				</div>
			</div>

			<div class="notice notice-info inline">
				<p>
					<?php esc_html_e( 'Total Transients includes caches from WordPress core, WooCommerce, and all active plugins. These tools only remove cached data — no orders, products, or customer records are affected. Caches are regenerated automatically as needed.', 'jharudar-for-woocommerce' ); ?>
				</p>
			</div>

			<div class="jharudar-database-tools-grid">
				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Clean Expired Transients', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Removes only expired transients. Active caches from WordPress and plugins remain intact. This is the safest option.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary" id="jharudar-clean-transients">
						<?php esc_html_e( 'Clean Expired Transients', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>

				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Clear WooCommerce Transients', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Removes WooCommerce-specific transients used for product, cart, and report caching. Safe to run after major catalog changes.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary" id="jharudar-clean-wc-transients">
						<?php esc_html_e( 'Clear WooCommerce Transients', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>

				<div class="jharudar-database-tool">
					<h4><?php esc_html_e( 'Clean All Transients', 'jharudar-for-woocommerce' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Removes all transients including active ones. Everything will be regenerated on the next page load. Use this for a full cache reset.', 'jharudar-for-woocommerce' ); ?></p>
					<button type="button" class="button button-primary" id="jharudar-clean-all-transients">
						<?php esc_html_e( 'Clean All Transients', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-database-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> <?php esc_html_e( 'items processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
