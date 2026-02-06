<?php
/**
 * Orders admin view.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get filter options.
$jharudar_order_statuses  = jharudar_get_order_statuses();
$jharudar_payment_methods = Jharudar_Orders::get_payment_methods();
$jharudar_order_stats     = Jharudar_Orders::get_statistics();
?>

<div class="jharudar-orders-page">
	<div class="jharudar-module-content">
		<div class="jharudar-module-header">
			<h3><?php esc_html_e( 'Order Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Filter and select orders to delete or anonymize. Export before deleting to keep a backup. Anonymization removes personal data while keeping order records intact.', 'jharudar-for-woocommerce' ); ?></p>
		</div>

		<!-- Quick Stats -->
		<div class="jharudar-quick-stats">
			<div class="jharudar-quick-stat">
				<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_order_stats['total'] ) ); ?></span>
				<span class="stat-label"><?php esc_html_e( 'Total Orders', 'jharudar-for-woocommerce' ); ?></span>
			</div>
			<div class="jharudar-quick-stat">
				<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_order_stats['processing'] ) ); ?></span>
				<span class="stat-label"><?php esc_html_e( 'Processing', 'jharudar-for-woocommerce' ); ?></span>
			</div>
			<div class="jharudar-quick-stat jharudar-success-stat">
				<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_order_stats['completed'] ) ); ?></span>
				<span class="stat-label"><?php esc_html_e( 'Completed', 'jharudar-for-woocommerce' ); ?></span>
			</div>
			<div class="jharudar-quick-stat jharudar-warning-stat">
				<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_order_stats['on_hold'] ) ); ?></span>
				<span class="stat-label"><?php esc_html_e( 'On Hold', 'jharudar-for-woocommerce' ); ?></span>
			</div>
			<div class="jharudar-quick-stat jharudar-danger-stat">
				<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_order_stats['cancelled'] + $jharudar_order_stats['failed'] ) ); ?></span>
				<span class="stat-label"><?php esc_html_e( 'Cancelled/Failed', 'jharudar-for-woocommerce' ); ?></span>
			</div>
		</div>

		<!-- HPOS Notice -->
		<?php if ( jharudar_is_hpos_enabled() ) : ?>
		<div class="notice notice-info inline">
			<p><?php esc_html_e( 'High-Performance Order Storage (HPOS) is enabled. Orders are stored in custom tables for better performance.', 'jharudar-for-woocommerce' ); ?></p>
		</div>
		<?php endif; ?>

		<!-- Filters -->
		<div class="jharudar-filters">
			<div class="jharudar-filter-group">
				<label for="jharudar-filter-order-status"><?php esc_html_e( 'Status', 'jharudar-for-woocommerce' ); ?></label>
				<select id="jharudar-filter-order-status" class="jharudar-select">
					<option value=""><?php esc_html_e( 'All Statuses', 'jharudar-for-woocommerce' ); ?></option>
					<?php foreach ( $jharudar_order_statuses as $jharudar_status_key => $jharudar_status_label ) : ?>
						<option value="<?php echo esc_attr( str_replace( 'wc-', '', $jharudar_status_key ) ); ?>">
							<?php echo esc_html( $jharudar_status_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="jharudar-filter-group">
				<label for="jharudar-filter-payment-method"><?php esc_html_e( 'Payment Method', 'jharudar-for-woocommerce' ); ?></label>
				<select id="jharudar-filter-payment-method" class="jharudar-select">
					<option value=""><?php esc_html_e( 'All Payment Methods', 'jharudar-for-woocommerce' ); ?></option>
					<?php foreach ( $jharudar_payment_methods as $jharudar_method_key => $jharudar_method_label ) : ?>
						<option value="<?php echo esc_attr( $jharudar_method_key ); ?>">
							<?php echo esc_html( $jharudar_method_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="jharudar-filter-group">
				<label for="jharudar-filter-order-date-after"><?php esc_html_e( 'Date From', 'jharudar-for-woocommerce' ); ?></label>
				<input type="date" id="jharudar-filter-order-date-after" class="jharudar-date-input" />
			</div>

			<div class="jharudar-filter-group">
				<label for="jharudar-filter-order-date-before"><?php esc_html_e( 'Date To', 'jharudar-for-woocommerce' ); ?></label>
				<input type="date" id="jharudar-filter-order-date-before" class="jharudar-date-input" />
			</div>

			<div class="jharudar-filter-group jharudar-filter-actions">
				<label>&nbsp;</label>
				<button type="button" class="button button-primary" id="jharudar-filter-orders">
					<?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?>
				</button>
				<button type="button" class="button" id="jharudar-reset-order-filters">
					<?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?>
				</button>
			</div>
		</div>

		<!-- Actions Bar -->
		<div class="jharudar-actions-bar">
			<div class="jharudar-bulk-actions">
				<label>
					<input type="checkbox" id="jharudar-select-all-orders" />
					<?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?>
				</label>
				<span class="jharudar-selected-count hidden">
					<span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?>
				</span>
			</div>
			<div class="jharudar-actions-right">
				<button type="button" class="button" id="jharudar-export-orders" disabled>
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export Selected', 'jharudar-for-woocommerce' ); ?>
				</button>
				<button type="button" class="button" id="jharudar-anonymize-orders" disabled>
					<span class="dashicons dashicons-hidden"></span>
					<?php esc_html_e( 'Anonymize Selected', 'jharudar-for-woocommerce' ); ?>
				</button>
				<button type="button" class="button button-primary" id="jharudar-delete-orders" disabled>
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Delete Selected', 'jharudar-for-woocommerce' ); ?>
				</button>
			</div>
		</div>

		<!-- Progress Bar -->
		<div class="jharudar-progress-wrapper" id="jharudar-orders-progress">
			<div class="jharudar-progress-bar">
				<div class="jharudar-progress-fill"></div>
			</div>
			<div class="jharudar-progress-text">
				<span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'orders processed', 'jharudar-for-woocommerce' ); ?>
			</div>
		</div>

		<!-- Results Table -->
		<div class="jharudar-results-container" id="jharudar-orders-results">
			<div class="jharudar-empty-state">
				<span class="dashicons dashicons-cart"></span>
				<p><?php esc_html_e( 'Use the filters above and click "Filter" to find orders.', 'jharudar-for-woocommerce' ); ?></p>
			</div>
		</div>

		<!-- Pagination -->
		<div class="jharudar-pagination hidden" id="jharudar-orders-pagination">
			<button type="button" class="button" id="jharudar-load-more-orders">
				<?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?>
			</button>
			<span class="jharudar-showing">
				<?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span>
			</span>
		</div>
	</div>
</div>

<!-- Delete Confirmation Modal -->
<div class="jharudar-modal-overlay" id="jharudar-order-delete-modal">
	<div class="jharudar-modal">
		<h3><?php esc_html_e( 'Confirm Order Deletion', 'jharudar-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'You are about to permanently delete the selected orders. This action cannot be undone. All order data, items, and notes will be removed.', 'jharudar-for-woocommerce' ); ?></p>
		<p class="jharudar-delete-summary"></p>
		
		<div class="jharudar-modal-options">
			<label class="jharudar-checkbox-label">
				<input type="checkbox" id="jharudar-confirm-order-backup" />
				<?php esc_html_e( 'I have exported a backup of these orders', 'jharudar-for-woocommerce' ); ?>
			</label>
		</div>

		<div class="jharudar-modal-input">
			<label for="jharudar-confirm-order-delete-input">
				<?php esc_html_e( 'Type DELETE to confirm:', 'jharudar-for-woocommerce' ); ?>
			</label>
			<input type="text" id="jharudar-confirm-order-delete-input" autocomplete="off" />
		</div>

		<div class="jharudar-modal-actions">
			<button type="button" class="button" id="jharudar-cancel-order-delete">
				<?php esc_html_e( 'Cancel', 'jharudar-for-woocommerce' ); ?>
			</button>
			<button type="button" class="button button-primary button-danger" id="jharudar-confirm-order-delete" disabled>
				<?php esc_html_e( 'Delete Permanently', 'jharudar-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>

<!-- Anonymize Confirmation Modal -->
<div class="jharudar-modal-overlay" id="jharudar-order-anonymize-modal">
	<div class="jharudar-modal">
		<h3><?php esc_html_e( 'Confirm Order Anonymization', 'jharudar-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'You are about to anonymize personal data in the selected orders. Customer names, addresses, emails, phone numbers, and IP addresses will be removed. Order totals and items will be preserved.', 'jharudar-for-woocommerce' ); ?></p>
		<p class="jharudar-anonymize-summary"></p>

		<div class="jharudar-modal-input">
			<label for="jharudar-confirm-anonymize-input">
				<?php esc_html_e( 'Type ANONYMIZE to confirm:', 'jharudar-for-woocommerce' ); ?>
			</label>
			<input type="text" id="jharudar-confirm-anonymize-input" autocomplete="off" />
		</div>

		<div class="jharudar-modal-actions">
			<button type="button" class="button" id="jharudar-cancel-anonymize">
				<?php esc_html_e( 'Cancel', 'jharudar-for-woocommerce' ); ?>
			</button>
			<button type="button" class="button button-primary" id="jharudar-confirm-anonymize" disabled>
				<?php esc_html_e( 'Anonymize Orders', 'jharudar-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>
