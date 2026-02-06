<?php
/**
 * Taxonomy admin view.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current sub-tab.
$jharudar_current_subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'categories'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// Get taxonomy statistics.
$jharudar_taxonomy_stats = Jharudar_Taxonomy::get_statistics();
?>

<div class="jharudar-taxonomy-page">
	<!-- Sub-tabs -->
	<div class="jharudar-subtabs">
		<a href="<?php echo esc_url( jharudar_admin_url( 'taxonomy', array( 'subtab' => 'categories' ) ) ); ?>" 
		   class="jharudar-subtab <?php echo 'categories' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Categories', 'jharudar-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( jharudar_admin_url( 'taxonomy', array( 'subtab' => 'tags' ) ) ); ?>" 
		   class="jharudar-subtab <?php echo 'tags' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Tags', 'jharudar-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( jharudar_admin_url( 'taxonomy', array( 'subtab' => 'attributes' ) ) ); ?>" 
		   class="jharudar-subtab <?php echo 'attributes' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Attributes', 'jharudar-for-woocommerce' ); ?>
		</a>
	</div>

	<?php if ( 'tags' === $jharudar_current_subtab ) : ?>
		<!-- Tags Section -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Product Tags Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Find and remove unused product tags that are not assigned to any products.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<!-- Quick Stats -->
			<div class="jharudar-quick-stats">
				<div class="jharudar-quick-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_taxonomy_stats['total_tags'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total Tags', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_taxonomy_stats['unused_tags'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Unused Tags', 'jharudar-for-woocommerce' ); ?></span>
				</div>
			</div>

			<!-- Filters -->
			<div class="jharudar-filters">
				<div class="jharudar-filter-group">
					<label for="jharudar-filter-tag-type"><?php esc_html_e( 'Filter By', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-tag-type" class="jharudar-select">
						<option value=""><?php esc_html_e( 'All Tags', 'jharudar-for-woocommerce' ); ?></option>
						<option value="unused"><?php esc_html_e( 'Unused Tags (0 products)', 'jharudar-for-woocommerce' ); ?></option>
					</select>
				</div>

				<div class="jharudar-filter-group jharudar-filter-actions">
					<label>&nbsp;</label>
					<button type="button" class="button button-primary" id="jharudar-filter-tags">
						<?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button" id="jharudar-reset-tag-filters">
						<?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Actions Bar -->
			<div class="jharudar-actions-bar">
				<div class="jharudar-bulk-actions">
					<label>
						<input type="checkbox" id="jharudar-select-all-tags" />
						<?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?>
					</label>
					<span class="jharudar-selected-count hidden">
						<span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?>
					</span>
				</div>
				<div class="jharudar-actions-right">
					<button type="button" class="button button-primary" id="jharudar-delete-tags" disabled>
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-tags-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'tags processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Results Table -->
			<div class="jharudar-results-container" id="jharudar-tags-results">
				<div class="jharudar-loading">
					<span class="spinner is-active"></span> <?php esc_html_e( 'Loading...', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Pagination -->
			<div class="jharudar-pagination hidden" id="jharudar-tags-pagination">
				<button type="button" class="button" id="jharudar-load-more-tags">
					<?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?>
				</button>
				<span class="jharudar-showing">
					<?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span>
				</span>
			</div>
		</div>

	<?php elseif ( 'attributes' === $jharudar_current_subtab ) : ?>
		<!-- Attributes Section -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Product Attributes Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Find and remove unused product attributes. Warning: Deleting attributes will also remove their terms.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<!-- Quick Stats -->
			<div class="jharudar-quick-stats">
				<div class="jharudar-quick-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_taxonomy_stats['total_attributes'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total Attributes', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_taxonomy_stats['unused_attributes'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Unused Attributes', 'jharudar-for-woocommerce' ); ?></span>
				</div>
			</div>

			<!-- Filters -->
			<div class="jharudar-filters">
				<div class="jharudar-filter-group">
					<label for="jharudar-filter-attribute-type"><?php esc_html_e( 'Filter By', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-attribute-type" class="jharudar-select">
						<option value=""><?php esc_html_e( 'All Attributes', 'jharudar-for-woocommerce' ); ?></option>
						<option value="unused"><?php esc_html_e( 'Unused Attributes', 'jharudar-for-woocommerce' ); ?></option>
					</select>
				</div>

				<div class="jharudar-filter-group jharudar-filter-actions">
					<label>&nbsp;</label>
					<button type="button" class="button button-primary" id="jharudar-filter-attributes">
						<?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button" id="jharudar-reset-attribute-filters">
						<?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Actions Bar -->
			<div class="jharudar-actions-bar">
				<div class="jharudar-bulk-actions">
					<label>
						<input type="checkbox" id="jharudar-select-all-attributes" />
						<?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?>
					</label>
					<span class="jharudar-selected-count hidden">
						<span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?>
					</span>
				</div>
				<div class="jharudar-actions-right">
					<button type="button" class="button button-primary" id="jharudar-delete-attributes" disabled>
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-attributes-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'attributes processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Results Table -->
			<div class="jharudar-results-container" id="jharudar-attributes-results">
				<div class="jharudar-loading">
					<span class="spinner is-active"></span> <?php esc_html_e( 'Loading...', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Pagination -->
			<div class="jharudar-pagination hidden" id="jharudar-attributes-pagination">
				<button type="button" class="button" id="jharudar-load-more-attributes">
					<?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?>
				</button>
				<span class="jharudar-showing">
					<?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span>
				</span>
			</div>
		</div>

	<?php else : ?>
		<!-- Categories Section (Default) -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Product Categories Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Find and remove empty product categories that have no products assigned.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<!-- Quick Stats -->
			<div class="jharudar-quick-stats">
				<div class="jharudar-quick-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_taxonomy_stats['total_categories'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total Categories', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_taxonomy_stats['empty_categories'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Empty Categories', 'jharudar-for-woocommerce' ); ?></span>
				</div>
			</div>

			<!-- Filters -->
			<div class="jharudar-filters">
				<div class="jharudar-filter-group">
					<label for="jharudar-filter-category-type"><?php esc_html_e( 'Filter By', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-category-type" class="jharudar-select">
						<option value=""><?php esc_html_e( 'All Categories', 'jharudar-for-woocommerce' ); ?></option>
						<option value="empty"><?php esc_html_e( 'Empty Categories (0 products)', 'jharudar-for-woocommerce' ); ?></option>
					</select>
				</div>

				<div class="jharudar-filter-group jharudar-filter-actions">
					<label>&nbsp;</label>
					<button type="button" class="button button-primary" id="jharudar-filter-categories">
						<?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button" id="jharudar-reset-category-filters">
						<?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Actions Bar -->
			<div class="jharudar-actions-bar">
				<div class="jharudar-bulk-actions">
					<label>
						<input type="checkbox" id="jharudar-select-all-categories" />
						<?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?>
					</label>
					<span class="jharudar-selected-count hidden">
						<span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?>
					</span>
				</div>
				<div class="jharudar-actions-right">
					<button type="button" class="button button-primary" id="jharudar-delete-categories" disabled>
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-categories-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'categories processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Results Table -->
			<div class="jharudar-results-container" id="jharudar-categories-results">
				<div class="jharudar-loading">
					<span class="spinner is-active"></span> <?php esc_html_e( 'Loading...', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Pagination -->
			<div class="jharudar-pagination hidden" id="jharudar-categories-pagination">
				<button type="button" class="button" id="jharudar-load-more-categories">
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
<div class="jharudar-modal-overlay" id="jharudar-taxonomy-delete-modal">
	<div class="jharudar-modal">
		<h3><?php esc_html_e( 'Confirm Deletion', 'jharudar-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'You are about to permanently delete the selected items. This action cannot be undone.', 'jharudar-for-woocommerce' ); ?></p>
		<p class="jharudar-delete-summary"></p>
		
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( 'Note: The default "Uncategorized" category cannot be deleted.', 'jharudar-for-woocommerce' ); ?></p>
		</div>

		<div class="jharudar-modal-input">
			<label for="jharudar-confirm-taxonomy-delete-input">
				<?php esc_html_e( 'Type DELETE to confirm:', 'jharudar-for-woocommerce' ); ?>
			</label>
			<input type="text" id="jharudar-confirm-taxonomy-delete-input" autocomplete="off" />
		</div>

		<div class="jharudar-modal-actions">
			<button type="button" class="button" id="jharudar-cancel-taxonomy-delete">
				<?php esc_html_e( 'Cancel', 'jharudar-for-woocommerce' ); ?>
			</button>
			<button type="button" class="button button-primary button-danger" id="jharudar-confirm-taxonomy-delete" disabled>
				<?php esc_html_e( 'Delete Permanently', 'jharudar-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>
