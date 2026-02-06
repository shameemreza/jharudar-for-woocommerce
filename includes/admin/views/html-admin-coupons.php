<?php
/**
 * Coupons admin view.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get coupon statistics.
$jharudar_coupon_stats = Jharudar_Coupons::get_statistics();
?>

<div class="jharudar-coupons-page">
	<div class="jharudar-module-content">
		<div class="jharudar-module-header">
			<h3><?php esc_html_e( 'Coupon Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Find and remove expired, unused, or depleted coupons. Always export before deleting to keep a backup.', 'jharudar-for-woocommerce' ); ?></p>
		</div>

		<!-- Quick Stats -->
		<div class="jharudar-quick-stats">
			<div class="jharudar-quick-stat">
				<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_coupon_stats['total'] ) ); ?></span>
				<span class="stat-label"><?php esc_html_e( 'Total Coupons', 'jharudar-for-woocommerce' ); ?></span>
			</div>
			<div class="jharudar-quick-stat jharudar-danger-stat">
				<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_coupon_stats['expired'] ) ); ?></span>
				<span class="stat-label"><?php esc_html_e( 'Expired', 'jharudar-for-woocommerce' ); ?></span>
			</div>
			<div class="jharudar-quick-stat jharudar-warning-stat">
				<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_coupon_stats['unused'] ) ); ?></span>
				<span class="stat-label"><?php esc_html_e( 'Never Used', 'jharudar-for-woocommerce' ); ?></span>
			</div>
			<div class="jharudar-quick-stat">
				<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_coupon_stats['limit_reached'] ) ); ?></span>
				<span class="stat-label"><?php esc_html_e( 'Limit Reached', 'jharudar-for-woocommerce' ); ?></span>
			</div>
		</div>

		<!-- Filters -->
		<div class="jharudar-filters">
			<div class="jharudar-filter-group">
				<label for="jharudar-filter-coupon-type"><?php esc_html_e( 'Filter By', 'jharudar-for-woocommerce' ); ?></label>
				<select id="jharudar-filter-coupon-type" class="jharudar-select">
					<option value=""><?php esc_html_e( 'All Coupons', 'jharudar-for-woocommerce' ); ?></option>
					<option value="expired"><?php esc_html_e( 'Expired Coupons', 'jharudar-for-woocommerce' ); ?></option>
					<option value="unused"><?php esc_html_e( 'Never Used Coupons', 'jharudar-for-woocommerce' ); ?></option>
					<option value="usage_limit_reached"><?php esc_html_e( 'Usage Limit Reached', 'jharudar-for-woocommerce' ); ?></option>
				</select>
			</div>

			<div class="jharudar-filter-group">
				<label for="jharudar-filter-coupon-date-after"><?php esc_html_e( 'Created After', 'jharudar-for-woocommerce' ); ?></label>
				<input type="date" id="jharudar-filter-coupon-date-after" class="jharudar-date-input" />
			</div>

			<div class="jharudar-filter-group">
				<label for="jharudar-filter-coupon-date-before"><?php esc_html_e( 'Created Before', 'jharudar-for-woocommerce' ); ?></label>
				<input type="date" id="jharudar-filter-coupon-date-before" class="jharudar-date-input" />
			</div>

			<div class="jharudar-filter-group jharudar-filter-actions">
				<label>&nbsp;</label>
				<button type="button" class="button button-primary" id="jharudar-filter-coupons">
					<?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?>
				</button>
				<button type="button" class="button" id="jharudar-reset-coupon-filters">
					<?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?>
				</button>
			</div>
		</div>

		<!-- Actions Bar -->
		<div class="jharudar-actions-bar">
			<div class="jharudar-bulk-actions">
				<label>
					<input type="checkbox" id="jharudar-select-all-coupons" />
					<?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?>
				</label>
				<span class="jharudar-selected-count hidden">
					<span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?>
				</span>
			</div>
			<div class="jharudar-actions-right">
				<button type="button" class="button" id="jharudar-export-coupons" disabled>
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export Selected', 'jharudar-for-woocommerce' ); ?>
				</button>
				<button type="button" class="button button-primary" id="jharudar-delete-coupons" disabled>
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Delete Selected', 'jharudar-for-woocommerce' ); ?>
				</button>
			</div>
		</div>

		<!-- Progress Bar -->
		<div class="jharudar-progress-wrapper" id="jharudar-coupons-progress">
			<div class="jharudar-progress-bar">
				<div class="jharudar-progress-fill"></div>
			</div>
			<div class="jharudar-progress-text">
				<span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'coupons processed', 'jharudar-for-woocommerce' ); ?>
			</div>
		</div>

		<!-- Results Table -->
		<div class="jharudar-results-container" id="jharudar-coupons-results">
			<div class="jharudar-loading">
				<span class="spinner is-active"></span> <?php esc_html_e( 'Loading...', 'jharudar-for-woocommerce' ); ?>
			</div>
		</div>

		<!-- Pagination -->
		<div class="jharudar-pagination hidden" id="jharudar-coupons-pagination">
			<button type="button" class="button" id="jharudar-load-more-coupons">
				<?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?>
			</button>
			<span class="jharudar-showing">
				<?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span>
			</span>
		</div>
	</div>
</div>

<!-- Delete Confirmation Modal -->
<div class="jharudar-modal-overlay" id="jharudar-coupon-delete-modal">
	<div class="jharudar-modal">
		<h3><?php esc_html_e( 'Confirm Deletion', 'jharudar-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'You are about to permanently delete the selected coupons. This action cannot be undone.', 'jharudar-for-woocommerce' ); ?></p>
		<p class="jharudar-delete-summary"></p>
		
		<div class="jharudar-modal-options">
			<label class="jharudar-checkbox-label">
				<input type="checkbox" id="jharudar-confirm-coupon-backup" />
				<?php esc_html_e( 'I have exported a backup of these coupons', 'jharudar-for-woocommerce' ); ?>
			</label>
		</div>

		<div class="jharudar-modal-input">
			<label for="jharudar-confirm-coupon-delete-input">
				<?php esc_html_e( 'Type DELETE to confirm:', 'jharudar-for-woocommerce' ); ?>
			</label>
			<input type="text" id="jharudar-confirm-coupon-delete-input" autocomplete="off" />
		</div>

		<div class="jharudar-modal-actions">
			<button type="button" class="button" id="jharudar-cancel-coupon-delete">
				<?php esc_html_e( 'Cancel', 'jharudar-for-woocommerce' ); ?>
			</button>
			<button type="button" class="button button-primary button-danger" id="jharudar-confirm-coupon-delete" disabled>
				<?php esc_html_e( 'Delete Permanently', 'jharudar-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>
