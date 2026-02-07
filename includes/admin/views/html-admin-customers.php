<?php
/**
 * Customers admin view.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get customer statistics.
$jharudar_stats = Jharudar_Customers::get_statistics();
?>

<div class="jharudar-customers-page">
	<div class="jharudar-module-page">
		<div class="jharudar-module-main">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Customer Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Find and remove inactive or zero-order customers. Export before deleting to keep a backup. Administrator and shop manager accounts are protected.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<!-- Quick Stats -->
			<div class="jharudar-quick-stats">
				<div class="jharudar-quick-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_stats['total'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total Customers', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_stats['zero_orders'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Zero Orders', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_stats['inactive'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Inactive (12+ months)', 'jharudar-for-woocommerce' ); ?></span>
				</div>
			</div>

			<!-- Filters -->
			<div class="jharudar-filters">
				<div class="jharudar-filter-group">
					<label for="jharudar-filter-customer-type"><?php esc_html_e( 'Filter Type', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-customer-type" class="jharudar-select">
						<option value=""><?php esc_html_e( 'All Customers', 'jharudar-for-woocommerce' ); ?></option>
						<option value="zero_orders"><?php esc_html_e( 'Zero Orders', 'jharudar-for-woocommerce' ); ?></option>
						<option value="inactive"><?php esc_html_e( 'Inactive Customers', 'jharudar-for-woocommerce' ); ?></option>
					</select>
				</div>

				<div class="jharudar-filter-group jharudar-inactive-months-filter hidden">
					<label for="jharudar-filter-inactive-months"><?php esc_html_e( 'Inactive Period', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-inactive-months" class="jharudar-select">
						<option value="3"><?php esc_html_e( '3+ months', 'jharudar-for-woocommerce' ); ?></option>
						<option value="6"><?php esc_html_e( '6+ months', 'jharudar-for-woocommerce' ); ?></option>
						<option value="12" selected><?php esc_html_e( '12+ months', 'jharudar-for-woocommerce' ); ?></option>
						<option value="24"><?php esc_html_e( '24+ months', 'jharudar-for-woocommerce' ); ?></option>
					</select>
				</div>

				<div class="jharudar-filter-group">
					<label for="jharudar-filter-customer-date"><?php esc_html_e( 'Registered Before', 'jharudar-for-woocommerce' ); ?></label>
					<input type="date" id="jharudar-filter-customer-date" class="jharudar-date-input" />
				</div>

				<div class="jharudar-filter-group jharudar-filter-actions">
					<label>&nbsp;</label>
					<button type="button" class="button button-primary" id="jharudar-filter-customers">
						<?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button" id="jharudar-reset-customer-filters">
						<?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Actions Bar -->
			<div class="jharudar-actions-bar">
				<div class="jharudar-bulk-actions">
					<label>
						<input type="checkbox" id="jharudar-select-all-customers" />
						<?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?>
					</label>
					<span class="jharudar-selected-count hidden">
						<span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?>
					</span>
				</div>
				<div class="jharudar-actions-right">
					<button type="button" class="button" id="jharudar-export-customers" disabled>
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Export Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button" id="jharudar-anonymize-customers" disabled>
						<span class="dashicons dashicons-hidden"></span>
						<?php esc_html_e( 'Anonymize Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button button-primary" id="jharudar-delete-customers" disabled>
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-customers-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'customers processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Results Table -->
			<div class="jharudar-results-container" id="jharudar-customers-results">
				<div class="jharudar-empty-state">
					<span class="dashicons dashicons-groups"></span>
					<p><?php esc_html_e( 'Use the filters above and click "Filter" to find customers.', 'jharudar-for-woocommerce' ); ?></p>
				</div>
			</div>

			<!-- Pagination -->
			<div class="jharudar-pagination hidden" id="jharudar-customers-pagination">
				<button type="button" class="button" id="jharudar-load-more-customers">
					<?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?>
				</button>
				<span class="jharudar-showing">
					<?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span>
				</span>
			</div>
		</div>

		<div class="jharudar-module-sidebar">
			<div class="jharudar-sidebar-box">
				<h4><?php esc_html_e( 'About Customer Cleanup', 'jharudar-for-woocommerce' ); ?></h4>
				<p><?php esc_html_e( 'Removing inactive customers can help improve site performance and reduce database size. However, always consider GDPR and data retention requirements before deleting customer data.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<div class="jharudar-sidebar-box">
				<h4><?php esc_html_e( 'Protected Accounts', 'jharudar-for-woocommerce' ); ?></h4>
				<p><?php esc_html_e( 'Administrator and Shop Manager accounts are automatically excluded from cleanup operations to prevent accidental deletion.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<div class="jharudar-sidebar-box">
				<h4><?php esc_html_e( 'Anonymization', 'jharudar-for-woocommerce' ); ?></h4>
				<p><?php esc_html_e( 'As an alternative to deletion, you can anonymize customer data. This removes personal information while keeping the account for historical purposes.', 'jharudar-for-woocommerce' ); ?></p>
			</div>
		</div>
	</div>
</div>

<!-- Delete Confirmation Modal -->
<div class="jharudar-modal-overlay" id="jharudar-customer-delete-modal">
	<div class="jharudar-modal">
		<div class="jharudar-modal-header">
			<h3><?php esc_html_e( 'Confirm Customer Deletion', 'jharudar-for-woocommerce' ); ?></h3>
		</div>
		<div class="jharudar-modal-body">
			<p><?php esc_html_e( 'You are about to permanently delete the selected customer accounts. This action cannot be undone. Customer order history will be preserved but attributed to "Guest".', 'jharudar-for-woocommerce' ); ?></p>
			<p class="jharudar-delete-summary"></p>

			<div class="jharudar-modal-options">
				<label class="jharudar-checkbox-label">
					<input type="checkbox" id="jharudar-confirm-customer-backup" />
					<?php esc_html_e( 'I have exported a backup of these customers', 'jharudar-for-woocommerce' ); ?>
				</label>
			</div>

			<div class="jharudar-modal-input">
				<label for="jharudar-confirm-customer-delete-input">
					<?php esc_html_e( 'Type DELETE to confirm:', 'jharudar-for-woocommerce' ); ?>
				</label>
				<input type="text" id="jharudar-confirm-customer-delete-input" autocomplete="off" />
			</div>
		</div>
		<div class="jharudar-modal-footer">
			<button type="button" class="button" id="jharudar-cancel-customer-delete">
				<?php esc_html_e( 'Cancel', 'jharudar-for-woocommerce' ); ?>
			</button>
			<button type="button" class="button button-primary button-danger" id="jharudar-confirm-customer-delete" disabled>
				<?php esc_html_e( 'Delete Permanently', 'jharudar-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>

<!-- Anonymize Confirmation Modal -->
<div class="jharudar-modal-overlay" id="jharudar-customer-anonymize-modal">
	<div class="jharudar-modal">
		<div class="jharudar-modal-header">
			<h3><?php esc_html_e( 'Confirm Customer Anonymization', 'jharudar-for-woocommerce' ); ?></h3>
		</div>
		<div class="jharudar-modal-body">
			<p><?php esc_html_e( 'You are about to anonymize personal data for the selected customers. Names, addresses, emails, and phone numbers will be replaced with anonymized placeholders. Order history will be preserved.', 'jharudar-for-woocommerce' ); ?></p>
			<p class="jharudar-anonymize-summary"></p>

			<div class="jharudar-modal-input">
				<label for="jharudar-confirm-customer-anonymize-input">
					<?php esc_html_e( 'Type ANONYMIZE to confirm:', 'jharudar-for-woocommerce' ); ?>
				</label>
				<input type="text" id="jharudar-confirm-customer-anonymize-input" autocomplete="off" />
			</div>
		</div>
		<div class="jharudar-modal-footer">
			<button type="button" class="button" id="jharudar-cancel-customer-anonymize">
				<?php esc_html_e( 'Cancel', 'jharudar-for-woocommerce' ); ?>
			</button>
			<button type="button" class="button button-primary" id="jharudar-confirm-customer-anonymize" disabled>
				<?php esc_html_e( 'Anonymize Customers', 'jharudar-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>
