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
$jharudar_categories   = jharudar_get_product_categories();
$jharudar_product_types = jharudar_get_product_types();
$jharudar_statuses     = array(
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
					<span class="jharudar-selected-count" style="display:none;">
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
						<?php foreach ( $jharudar_categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->term_id ); ?>">
								<?php echo esc_html( $cat->name ); ?> (<?php echo esc_html( $cat->count ); ?>)
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
					<span class="jharudar-selected-count" style="display:none;">
						<span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?>
					</span>
				</div>
				<div class="jharudar-actions-right">
					<label class="jharudar-checkbox-label">
						<input type="checkbox" id="jharudar-delete-images" />
						<?php esc_html_e( 'Delete product images', 'jharudar-for-woocommerce' ); ?>
					</label>
					<button type="button" class="button" id="jharudar-export-products" disabled>
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Export Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button button-primary" id="jharudar-delete-products" disabled>
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
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
			<div class="jharudar-pagination" id="jharudar-products-pagination" style="display:none;">
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
		<h3><?php esc_html_e( 'Confirm Deletion', 'jharudar-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'You are about to permanently delete the selected products. This action cannot be undone.', 'jharudar-for-woocommerce' ); ?></p>
		<p class="jharudar-delete-summary"></p>
		
		<div class="jharudar-modal-options">
			<label class="jharudar-checkbox-label">
				<input type="checkbox" id="jharudar-confirm-backup" />
				<?php esc_html_e( 'I have exported a backup of these products', 'jharudar-for-woocommerce' ); ?>
			</label>
		</div>

		<div class="jharudar-modal-input">
			<label for="jharudar-confirm-delete-input">
				<?php esc_html_e( 'Type DELETE to confirm:', 'jharudar-for-woocommerce' ); ?>
			</label>
			<input type="text" id="jharudar-confirm-delete-input" autocomplete="off" />
		</div>

		<div class="jharudar-modal-actions">
			<button type="button" class="button" id="jharudar-cancel-delete">
				<?php esc_html_e( 'Cancel', 'jharudar-for-woocommerce' ); ?>
			</button>
			<button type="button" class="button button-primary button-danger" id="jharudar-confirm-delete" disabled>
				<?php esc_html_e( 'Delete Permanently', 'jharudar-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>
