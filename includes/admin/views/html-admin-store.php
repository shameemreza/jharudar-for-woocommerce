<?php
/**
 * Store Data admin view.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current sub-tab.
$jharudar_current_subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'tax-rates'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// Get stats and helpers.
$jharudar_tax_stats      = Jharudar_Tax_Rates::get_statistics();
$jharudar_shipping_stats = Jharudar_Shipping::get_statistics();

// Get tax rate filters.
$jharudar_tax_rates_module = new Jharudar_Tax_Rates();
$jharudar_countries        = $jharudar_tax_rates_module->get_countries();
$jharudar_tax_classes      = $jharudar_tax_rates_module->get_tax_classes();
?>

<div class="jharudar-store-page">
	<!-- Sub-tabs -->
	<div class="jharudar-subtabs">
		<a href="<?php echo esc_url( jharudar_admin_url( 'store', array( 'subtab' => 'tax-rates' ) ) ); ?>" 
		   class="jharudar-subtab <?php echo 'tax-rates' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Tax Rates', 'jharudar-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( jharudar_admin_url( 'store', array( 'subtab' => 'shipping-zones' ) ) ); ?>" 
		   class="jharudar-subtab <?php echo 'shipping-zones' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Shipping Zones', 'jharudar-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( jharudar_admin_url( 'store', array( 'subtab' => 'shipping-classes' ) ) ); ?>" 
		   class="jharudar-subtab <?php echo 'shipping-classes' === $jharudar_current_subtab ? 'active' : ''; ?>">
			<?php esc_html_e( 'Shipping Classes', 'jharudar-for-woocommerce' ); ?>
		</a>
	</div>

	<?php if ( 'shipping-zones' === $jharudar_current_subtab ) : ?>
		<!-- Shipping Zones Section -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Shipping Zones Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Manage and remove shipping zones. Note: The "Rest of the World" zone cannot be deleted.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<!-- Quick Stats -->
			<div class="jharudar-quick-stats">
				<div class="jharudar-quick-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_shipping_stats['total_zones'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total Zones', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_shipping_stats['empty_zones'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Empty Zones', 'jharudar-for-woocommerce' ); ?></span>
				</div>
			</div>

			<!-- Filters -->
			<div class="jharudar-filters">
				<div class="jharudar-filter-group">
					<label for="jharudar-filter-zone-type"><?php esc_html_e( 'Filter By', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-zone-type" class="jharudar-select">
						<option value=""><?php esc_html_e( 'All Zones', 'jharudar-for-woocommerce' ); ?></option>
						<option value="empty"><?php esc_html_e( 'Empty Zones (no methods)', 'jharudar-for-woocommerce' ); ?></option>
					</select>
				</div>

				<div class="jharudar-filter-group jharudar-filter-actions">
					<label>&nbsp;</label>
					<button type="button" class="button button-primary" id="jharudar-filter-zones">
						<?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button" id="jharudar-reset-zone-filters">
						<?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Actions Bar -->
			<div class="jharudar-actions-bar">
				<div class="jharudar-bulk-actions">
					<label>
						<input type="checkbox" id="jharudar-select-all-zones" />
						<?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?>
					</label>
					<span class="jharudar-selected-count hidden">
						<span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?>
					</span>
				</div>
				<div class="jharudar-actions-right">
					<button type="button" class="button button-primary" id="jharudar-delete-zones" disabled>
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-zones-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'zones processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Results Table -->
			<div class="jharudar-results-container" id="jharudar-zones-results">
				<div class="jharudar-loading">
					<span class="spinner is-active"></span> <?php esc_html_e( 'Loading...', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Pagination -->
			<div class="jharudar-pagination hidden" id="jharudar-zones-pagination">
				<button type="button" class="button" id="jharudar-load-more-zones">
					<?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?>
				</button>
				<span class="jharudar-showing">
					<?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span>
				</span>
			</div>
		</div>

	<?php elseif ( 'shipping-classes' === $jharudar_current_subtab ) : ?>
		<!-- Shipping Classes Section -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Shipping Classes Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Find and remove unused shipping classes that are not assigned to any products.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<!-- Quick Stats -->
			<div class="jharudar-quick-stats">
				<div class="jharudar-quick-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_shipping_stats['total_classes'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total Classes', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat jharudar-warning-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_shipping_stats['unused_classes'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Unused Classes', 'jharudar-for-woocommerce' ); ?></span>
				</div>
			</div>

			<!-- Filters -->
			<div class="jharudar-filters">
				<div class="jharudar-filter-group">
					<label for="jharudar-filter-class-type"><?php esc_html_e( 'Filter By', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-class-type" class="jharudar-select">
						<option value=""><?php esc_html_e( 'All Classes', 'jharudar-for-woocommerce' ); ?></option>
						<option value="unused"><?php esc_html_e( 'Unused Classes', 'jharudar-for-woocommerce' ); ?></option>
					</select>
				</div>

				<div class="jharudar-filter-group jharudar-filter-actions">
					<label>&nbsp;</label>
					<button type="button" class="button button-primary" id="jharudar-filter-shipping-classes">
						<?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button" id="jharudar-reset-class-filters">
						<?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Actions Bar -->
			<div class="jharudar-actions-bar">
				<div class="jharudar-bulk-actions">
					<label>
						<input type="checkbox" id="jharudar-select-all-shipping-classes" />
						<?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?>
					</label>
					<span class="jharudar-selected-count hidden">
						<span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?>
					</span>
				</div>
				<div class="jharudar-actions-right">
					<button type="button" class="button button-primary" id="jharudar-delete-shipping-classes" disabled>
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-shipping-classes-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'classes processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Results Table -->
			<div class="jharudar-results-container" id="jharudar-shipping-classes-results">
				<div class="jharudar-loading">
					<span class="spinner is-active"></span> <?php esc_html_e( 'Loading...', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Pagination -->
			<div class="jharudar-pagination hidden" id="jharudar-shipping-classes-pagination">
				<button type="button" class="button" id="jharudar-load-more-shipping-classes">
					<?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?>
				</button>
				<span class="jharudar-showing">
					<?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span>
				</span>
			</div>
		</div>

	<?php else : ?>
		<!-- Tax Rates Section (Default) -->
		<div class="jharudar-module-content">
			<div class="jharudar-module-header">
				<h3><?php esc_html_e( 'Tax Rates Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Manage and delete tax rates. Always export before deleting to keep a backup of your tax configuration.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<!-- Quick Stats -->
			<div class="jharudar-quick-stats">
				<div class="jharudar-quick-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_tax_stats['total'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total Tax Rates', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_tax_stats['countries'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Countries', 'jharudar-for-woocommerce' ); ?></span>
				</div>
				<div class="jharudar-quick-stat">
					<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_tax_stats['classes'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Tax Classes', 'jharudar-for-woocommerce' ); ?></span>
				</div>
			</div>

			<!-- Filters -->
			<div class="jharudar-filters">
				<?php if ( ! empty( $jharudar_countries ) ) : ?>
				<div class="jharudar-filter-group">
					<label for="jharudar-filter-tax-country"><?php esc_html_e( 'Country', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-tax-country" class="jharudar-select">
						<option value=""><?php esc_html_e( 'All Countries', 'jharudar-for-woocommerce' ); ?></option>
						<?php foreach ( $jharudar_countries as $jharudar_country_code => $jharudar_country_name ) : ?>
							<option value="<?php echo esc_attr( $jharudar_country_code ); ?>"><?php echo esc_html( $jharudar_country_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>

				<div class="jharudar-filter-group">
					<label for="jharudar-filter-tax-class"><?php esc_html_e( 'Tax Class', 'jharudar-for-woocommerce' ); ?></label>
					<select id="jharudar-filter-tax-class" class="jharudar-select">
						<option value=""><?php esc_html_e( 'All Tax Classes', 'jharudar-for-woocommerce' ); ?></option>
						<?php foreach ( $jharudar_tax_classes as $jharudar_class_slug => $jharudar_class_name ) : ?>
							<option value="<?php echo esc_attr( $jharudar_class_slug ); ?>"><?php echo esc_html( $jharudar_class_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="jharudar-filter-group jharudar-filter-actions">
					<label>&nbsp;</label>
					<button type="button" class="button button-primary" id="jharudar-filter-tax-rates">
						<?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button" id="jharudar-reset-tax-filters">
						<?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Actions Bar -->
			<div class="jharudar-actions-bar">
				<div class="jharudar-bulk-actions">
					<label>
						<input type="checkbox" id="jharudar-select-all-tax-rates" />
						<?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?>
					</label>
					<span class="jharudar-selected-count hidden">
						<span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?>
					</span>
				</div>
				<div class="jharudar-actions-right">
					<button type="button" class="button" id="jharudar-export-tax-rates" disabled>
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Export Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
					<button type="button" class="button button-primary" id="jharudar-delete-tax-rates" disabled>
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Selected', 'jharudar-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="jharudar-progress-wrapper" id="jharudar-tax-rates-progress">
				<div class="jharudar-progress-bar">
					<div class="jharudar-progress-fill"></div>
				</div>
				<div class="jharudar-progress-text">
					<span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'tax rates processed', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Results Table -->
			<div class="jharudar-results-container" id="jharudar-tax-rates-results">
				<div class="jharudar-loading">
					<span class="spinner is-active"></span> <?php esc_html_e( 'Loading...', 'jharudar-for-woocommerce' ); ?>
				</div>
			</div>

			<!-- Pagination -->
			<div class="jharudar-pagination hidden" id="jharudar-tax-rates-pagination">
				<button type="button" class="button" id="jharudar-load-more-tax-rates">
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
<div class="jharudar-modal-overlay" id="jharudar-store-delete-modal">
	<div class="jharudar-modal">
		<h3><?php esc_html_e( 'Confirm Deletion', 'jharudar-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'You are about to permanently delete the selected items. This action cannot be undone.', 'jharudar-for-woocommerce' ); ?></p>
		<p class="jharudar-delete-summary"></p>
		
		<div class="jharudar-modal-options">
			<label class="jharudar-checkbox-label">
				<input type="checkbox" id="jharudar-confirm-store-backup" />
				<?php esc_html_e( 'I have exported a backup of these items', 'jharudar-for-woocommerce' ); ?>
			</label>
		</div>

		<div class="jharudar-modal-input">
			<label for="jharudar-confirm-store-delete-input">
				<?php esc_html_e( 'Type DELETE to confirm:', 'jharudar-for-woocommerce' ); ?>
			</label>
			<input type="text" id="jharudar-confirm-store-delete-input" autocomplete="off" />
		</div>

		<div class="jharudar-modal-actions">
			<button type="button" class="button" id="jharudar-cancel-store-delete">
				<?php esc_html_e( 'Cancel', 'jharudar-for-woocommerce' ); ?>
			</button>
			<button type="button" class="button button-primary button-danger" id="jharudar-confirm-store-delete" disabled>
				<?php esc_html_e( 'Delete Permanently', 'jharudar-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>
