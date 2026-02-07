<?php
/**
 * Products admin view.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get filter options.
$jharudar_categories     = jharudar_get_product_categories();
$jharudar_product_types  = jharudar_get_product_types();
$jharudar_statuses       = array(
	'publish' => __( 'Published', 'jharudar-for-woocommerce' ),
	'draft'   => __( 'Draft', 'jharudar-for-woocommerce' ),
	'pending' => __( 'Pending', 'jharudar-for-woocommerce' ),
	'private' => __( 'Private', 'jharudar-for-woocommerce' ),
	'trash'   => __( 'Trash', 'jharudar-for-woocommerce' ),
);
$jharudar_stock_statuses = array(
	'instock'     => __( 'In Stock', 'jharudar-for-woocommerce' ),
	'outofstock'  => __( 'Out of Stock', 'jharudar-for-woocommerce' ),
	'onbackorder' => __( 'On Backorder', 'jharudar-for-woocommerce' ),
);

// Get current sub-tab.
$jharudar_current_subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<div class="jharudar-products-page">
	<div class="jharudar-subtabs">
		<a href="<?php echo esc_url( jharudar_admin_url( 'products', array( 'subtab' => 'all' ) ) ); ?>" 
			class="jharudar-subtab <?php echo 'all' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'All Products', 'jharudar-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( jharudar_admin_url( 'products', array( 'subtab' => 'orphaned' ) ) ); ?>" 
			class="jharudar-subtab <?php echo 'orphaned' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Orphaned Images', 'jharudar-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( jharudar_admin_url( 'products', array( 'subtab' => 'duplicates' ) ) ); ?>" 
			class="jharudar-subtab <?php echo 'duplicates' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Duplicates', 'jharudar-for-woocommerce' ); ?>
		</a>
	</div>

	<?php if ( 'orphaned' === $jharudar_current_subtab ) : ?>
		<!-- Orphaned Images Section -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Orphaned Product Images', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Find and remove images that are no longer attached to any product.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<div class="jharudar-actions-bar">
				<div class="jharudar-bulk-actions">
					<label>
						<input type="checkbox" id="jharudar-select-all-images" />
						<?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?>
					</label>
					<span class="jharudar-selected-count hidden">
						<span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?>
					</span>
				</div>
				<div class="jharudar-actions-right">
					<button type="button" class="button" id="jharudar-scan-orphaned-images">
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'Scan for Orphaned Images', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button button-primary" id="jharudar-delete-orphaned-images" disabled>
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<div class="jharudar-results-container" id="jharudar-orphaned-images-results">
				<div class="jharudar-empty-state">
					<span class="dashicons dashicons-format-gallery"></span>
					<p><?php esc_html_e( 'Click "Scan for Orphaned Images" to find unused product images.', 'jharudar-for-woocommerce' ); ?></p>
				</div>
			</div>
		</div>

	<?php elseif ( 'duplicates' === $jharudar_current_subtab ) : ?>
		<!-- Duplicate Products Section -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Duplicate Product Detection', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Find duplicate products by name, SKU, or slug. Review the groups and choose which copies to keep or delete.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

		<div class="jharudar-filters">
			<div class="jharudar-filter-group">
				<label for="jharudar-duplicate-match-by"><?php esc_html_e( 'Match By', 'jharudar-for-woocommerce' ); ?></label>
				<select id="jharudar-duplicate-match-by" class="jharudar-select">
					<option value="name"><?php esc_html_e( 'Exact Name', 'jharudar-for-woocommerce' ); ?></option>
					<option value="normalized_name"><?php esc_html_e( 'Normalized Name (case-insensitive)', 'jharudar-for-woocommerce' ); ?></option>
					<option value="sku"><?php esc_html_e( 'Exact SKU', 'jharudar-for-woocommerce' ); ?></option>
					<option value="slug"><?php esc_html_e( 'Slug (catches product-2 copies)', 'jharudar-for-woocommerce' ); ?></option>
				</select>
			</div>
			<div class="jharudar-filter-group">
				<label for="jharudar-duplicate-status-filter"><?php esc_html_e( 'Status', 'jharudar-for-woocommerce' ); ?></label>
				<select id="jharudar-duplicate-status-filter" class="jharudar-select">
					<option value=""><?php esc_html_e( 'All Statuses', 'jharudar-for-woocommerce' ); ?></option>
					<option value="publish"><?php esc_html_e( 'Published', 'jharudar-for-woocommerce' ); ?></option>
					<option value="draft"><?php esc_html_e( 'Draft', 'jharudar-for-woocommerce' ); ?></option>
					<option value="pending"><?php esc_html_e( 'Pending', 'jharudar-for-woocommerce' ); ?></option>
					<option value="private"><?php esc_html_e( 'Private', 'jharudar-for-woocommerce' ); ?></option>
				</select>
			</div>
			<div class="jharudar-filter-group jharudar-filter-actions">
				<label>&nbsp;</label>
				<button type="button" class="button button-primary" id="jharudar-scan-duplicates">
					<span class="dashicons dashicons-search"></span>
					<?php esc_html_e( 'Scan for Duplicates', 'jharudar-for-woocommerce' ); ?>
				</button>
			</div>
		</div>

			<div id="jharudar-duplicate-stats" style="display:none;">
				<div class="jharudar-quick-stats">
					<div class="jharudar-quick-stat jharudar-warning-stat">
						<span class="stat-number" id="jharudar-dup-group-count">0</span>
						<span class="stat-label"><?php esc_html_e( 'Duplicate Sets', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-danger-stat">
						<span class="stat-number" id="jharudar-dup-product-count">0</span>
						<span class="stat-label"><?php esc_html_e( 'Total Duplicate Products', 'jharudar-for-woocommerce' ); ?></span>
					</div>
				</div>
			</div>

		<div class="jharudar-actions-bar" id="jharudar-duplicate-actions-bar" style="display:none;">
			<div class="jharudar-bulk-actions">
				<span class="jharudar-selected-count">
					<span class="count" id="jharudar-dup-selected-count">0</span> <?php esc_html_e( 'selected for deletion', 'jharudar-for-woocommerce' ); ?>
				</span>
			</div>
			<div class="jharudar-actions-right">
				<label class="jharudar-checkbox-label">
					<input type="checkbox" id="jharudar-dup-delete-images" />
					<?php esc_html_e( 'Delete product images', 'jharudar-for-woocommerce' ); ?>
				</label>
				<label class="jharudar-checkbox-label">
					<input type="checkbox" id="jharudar-dup-setup-redirects" checked />
					<?php esc_html_e( 'Add 301 redirects', 'jharudar-for-woocommerce' ); ?>
				</label>
				<select id="jharudar-dup-delete-action" class="jharudar-select" style="width: auto; min-width: 140px;">
					<option value="delete"><?php esc_html_e( 'Delete Permanently', 'jharudar-for-woocommerce' ); ?></option>
					<option value="trash"><?php esc_html_e( 'Move to Trash', 'jharudar-for-woocommerce' ); ?></option>
					<option value="draft"><?php esc_html_e( 'Set as Draft', 'jharudar-for-woocommerce' ); ?></option>
				</select>
				<button type="button" class="button" id="jharudar-export-duplicates" disabled>
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export Selected', 'jharudar-for-woocommerce' ); ?>
				</button>
				<button type="button" class="button button-primary" id="jharudar-delete-duplicates" disabled>
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Process Selected', 'jharudar-for-woocommerce' ); ?>
				</button>
			</div>
		</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-duplicates-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'products processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<div class="jharudar-results-container" id="jharudar-duplicates-results">
				<div class="jharudar-empty-state">
					<span class="dashicons dashicons-search"></span>
					<p><?php esc_html_e( 'Choose how to match duplicates above, then click "Scan for Duplicates" to search your catalog.', 'jharudar-for-woocommerce' ); ?></p>
					<p class="description"><?php esc_html_e( 'Products sharing the same name, SKU, or slug will be grouped together so you can review and clean them up.', 'jharudar-for-woocommerce' ); ?></p>
				</div>
			</div>
		</div>

	<?php else : ?>
		<!-- All Products Section -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Product Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Filter and select products to delete. Always export before deleting to keep a backup.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<!-- Filters -->
			<div class="jharudar-filters">
				<div class="jharudar-filter-group">
					<label for="jharudar-filter-category"><?php esc_html_e( 'Category', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-category" class="jharudar-select">
						<option value=""><?php esc_html_e( 'All Categories', 'jharudar-for-woocommerce' ); ?></option>
						<?php foreach ( $jharudar_categories as $jharudar_cat ) : ?>
							<option value="<?php echo esc_attr( $jharudar_cat->term_id ); ?>">
								<?php echo esc_html( $jharudar_cat->name ); ?> (<?php echo esc_html( $jharudar_cat->count ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="jharudar-filter-group">
					<label for="jharudar-filter-status"><?php esc_html_e( 'Status', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-status" class="jharudar-select">
						<option value=""><?php esc_html_e( 'All Statuses', 'jharudar-for-woocommerce' ); ?></option>
						<?php foreach ( $jharudar_statuses as $jharudar_status_key => $jharudar_status_label ) : ?>
							<option value="<?php echo esc_attr( $jharudar_status_key ); ?>"><?php echo esc_html( $jharudar_status_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="jharudar-filter-group">
					<label for="jharudar-filter-stock"><?php esc_html_e( 'Stock Status', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-stock" class="jharudar-select">
						<option value=""><?php esc_html_e( 'All Stock Statuses', 'jharudar-for-woocommerce' ); ?></option>
						<?php foreach ( $jharudar_stock_statuses as $jharudar_stock_key => $jharudar_stock_label ) : ?>
							<option value="<?php echo esc_attr( $jharudar_stock_key ); ?>"><?php echo esc_html( $jharudar_stock_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="jharudar-filter-group">
					<label for="jharudar-filter-type"><?php esc_html_e( 'Product Type', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-type" class="jharudar-select">
						<option value=""><?php esc_html_e( 'All Types', 'jharudar-for-woocommerce' ); ?></option>
						<?php foreach ( $jharudar_product_types as $jharudar_type_key => $jharudar_type_label ) : ?>
							<option value="<?php echo esc_attr( $jharudar_type_key ); ?>"><?php echo esc_html( $jharudar_type_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="jharudar-filter-group">
					<label for="jharudar-filter-date-after"><?php esc_html_e( 'Created After', 'jharudar-for-woocommerce' ); ?></label>
					<input type="date" id="jharudar-filter-date-after" class="jharudar-date-input" />
				</div>

				<div class="jharudar-filter-group">
					<label for="jharudar-filter-date-before"><?php esc_html_e( 'Created Before', 'jharudar-for-woocommerce' ); ?></label>
					<input type="date" id="jharudar-filter-date-before" class="jharudar-date-input" />
				</div>

				<div class="jharudar-filter-group jharudar-filter-actions">
					<label>&nbsp;</label>
					<button type="button" class="button button-primary" id="jharudar-filter-products">
						<?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button" id="jharudar-reset-filters">
						<?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Actions Bar -->
			<div class="jharudar-actions-bar">
				<div class="jharudar-bulk-actions">
					<label>
						<input type="checkbox" id="jharudar-select-all-products" />
						<?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?>
					</label>
					<span class="jharudar-selected-count hidden">
						<span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?>
					</span>
				</div>
			<div class="jharudar-actions-right">
				<label class="jharudar-checkbox-label">
					<input type="checkbox" id="jharudar-delete-images" />
					<?php esc_html_e( 'Delete product images', 'jharudar-for-woocommerce' ); ?>
				</label>
				<select id="jharudar-product-delete-action" class="jharudar-select" style="width: auto; min-width: 140px;">
					<option value="trash"><?php esc_html_e( 'Move to Trash', 'jharudar-for-woocommerce' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete Permanently', 'jharudar-for-woocommerce' ); ?></option>
				</select>
				<button type="button" class="button" id="jharudar-export-products" disabled>
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export Selected', 'jharudar-for-woocommerce' ); ?>
				</button>
				<button type="button" class="button button-primary" id="jharudar-delete-products" disabled>
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Process Selected', 'jharudar-for-woocommerce' ); ?>
				</button>
				<?php
				$jharudar_product_trash_count = Jharudar_Products::count_trashed();
				if ( $jharudar_product_trash_count > 0 ) :
					?>
					<button type="button" class="button jharudar-empty-trash-btn" data-module="products" data-count="<?php echo esc_attr( $jharudar_product_trash_count ); ?>">
						<span class="dashicons dashicons-trash"></span>
						<?php
						printf(
							/* translators: %s: number of trashed items. */
							esc_html__( 'Empty Trash (%s)', 'jharudar-for-woocommerce' ),
							esc_html( number_format_i18n( $jharudar_product_trash_count ) )
						);
						?>
					</button>
				<?php endif; ?>
			</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-products-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'products processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Results Table -->
			<div class="jharudar-results-container" id="jharudar-products-results">
				<div class="jharudar-empty-state">
					<span class="dashicons dashicons-archive"></span>
					<p><?php esc_html_e( 'Use the filters above and click "Filter" to find products.', 'jharudar-for-woocommerce' ); ?></p>
				</div>
			</div>

			<!-- Pagination -->
			<div class="jharudar-pagination hidden" id="jharudar-products-pagination">
				<button type="button" class="button" id="jharudar-load-more-products">
					<?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?>
				</button>
				<span class="jharudar-showing">
					<?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span>
				</span>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="jharudar-modal-overlay" id="jharudar-delete-modal">
	<div class="jharudar-modal">
		<div class="jharudar-modal-header">
			<h3 id="jharudar-product-modal-title"><?php esc_html_e( 'Confirm Deletion', 'jharudar-for-woocommerce' ); ?></h3>
		</div>
		<div class="jharudar-modal-body">
			<p id="jharudar-product-modal-description"><?php esc_html_e( 'You are about to permanently delete the selected products. This action cannot be undone.', 'jharudar-for-woocommerce' ); ?></p>
			<p class="jharudar-delete-summary"></p>

			<div class="jharudar-dependency-warnings" id="jharudar-dependency-warnings" style="display:none;">
				<div class="notice notice-warning inline" style="margin: 10px 0;">
					<p><strong><?php esc_html_e( 'Warning: Related data found', 'jharudar-for-woocommerce' ); ?></strong></p>
					<ul class="jharudar-warning-list"></ul>
				</div>
			</div>

			<div class="jharudar-modal-options">
				<label class="jharudar-checkbox-label">
					<input type="checkbox" id="jharudar-confirm-backup" />
					<?php esc_html_e( 'I have exported a backup of these products', 'jharudar-for-woocommerce' ); ?>
				</label>
			</div>

			<div class="jharudar-modal-input" id="jharudar-product-confirm-input-wrapper">
				<label for="jharudar-confirm-delete-input">
					<?php esc_html_e( 'Type DELETE to confirm:', 'jharudar-for-woocommerce' ); ?>
				</label>
				<input type="text" id="jharudar-confirm-delete-input" autocomplete="off" />
			</div>
		</div>
		<div class="jharudar-modal-footer">
			<button type="button" class="button" id="jharudar-cancel-delete">
				<?php esc_html_e( 'Cancel', 'jharudar-for-woocommerce' ); ?>
			</button>
			<button type="button" class="button button-primary button-danger" id="jharudar-confirm-delete" disabled>
				<?php esc_html_e( 'Confirm', 'jharudar-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>

<!-- Duplicate Action Confirmation Modal -->
<div class="jharudar-modal-overlay" id="jharudar-dup-delete-modal">
	<div class="jharudar-modal">
		<div class="jharudar-modal-header">
			<h3 id="jharudar-dup-modal-title"><?php esc_html_e( 'Confirm Duplicate Deletion', 'jharudar-for-woocommerce' ); ?></h3>
		</div>
		<div class="jharudar-modal-body">
			<p id="jharudar-dup-modal-description"><?php esc_html_e( 'You are about to permanently delete the selected duplicate products. This action cannot be undone.', 'jharudar-for-woocommerce' ); ?></p>
			<p class="jharudar-delete-summary"></p>

			<div class="jharudar-redirect-notice" id="jharudar-dup-redirect-notice" style="display:none;">
				<div class="notice notice-info inline" style="margin: 10px 0;">
					<p><span class="dashicons dashicons-admin-links" style="vertical-align: middle;"></span> <?php esc_html_e( '301 redirects will be created from deleted product URLs to the kept product in each group.', 'jharudar-for-woocommerce' ); ?></p>
				</div>
			</div>

			<div class="jharudar-modal-options">
				<label class="jharudar-checkbox-label">
					<input type="checkbox" id="jharudar-confirm-dup-backup" />
					<?php esc_html_e( 'I have exported a backup of these products', 'jharudar-for-woocommerce' ); ?>
				</label>
			</div>

			<div class="jharudar-modal-input" id="jharudar-dup-confirm-input-wrapper">
				<label for="jharudar-confirm-dup-delete-input">
					<?php esc_html_e( 'Type DELETE to confirm:', 'jharudar-for-woocommerce' ); ?>
				</label>
				<input type="text" id="jharudar-confirm-dup-delete-input" autocomplete="off" />
			</div>
		</div>
		<div class="jharudar-modal-footer">
			<button type="button" class="button" id="jharudar-cancel-dup-delete">
				<?php esc_html_e( 'Cancel', 'jharudar-for-woocommerce' ); ?>
			</button>
			<button type="button" class="button button-primary button-danger" id="jharudar-confirm-dup-delete" disabled>
				<?php esc_html_e( 'Confirm', 'jharudar-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>
