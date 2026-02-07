<?php
/**
 * Extensions admin view.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Determine which extensions are active.
$jharudar_ext_subscriptions = Jharudar_Subscriptions::is_active();
$jharudar_ext_memberships   = Jharudar_Memberships::is_active();
$jharudar_ext_bookings      = Jharudar_Bookings::is_active();
$jharudar_ext_appointments  = Jharudar_Appointments::is_active();
$jharudar_ext_vendors       = Jharudar_Vendors::is_active();

$jharudar_any_ext_active = $jharudar_ext_subscriptions || $jharudar_ext_memberships || $jharudar_ext_bookings || $jharudar_ext_appointments || $jharudar_ext_vendors;

// Get current sub-tab.
$jharudar_ext_subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// Auto-select first active extension if no subtab specified.
if ( empty( $jharudar_ext_subtab ) && $jharudar_any_ext_active ) {
	if ( $jharudar_ext_subscriptions ) {
		$jharudar_ext_subtab = 'subscriptions';
	} elseif ( $jharudar_ext_memberships ) {
		$jharudar_ext_subtab = 'memberships';
	} elseif ( $jharudar_ext_bookings ) {
		$jharudar_ext_subtab = 'bookings';
	} elseif ( $jharudar_ext_appointments ) {
		$jharudar_ext_subtab = 'appointments';
	} elseif ( $jharudar_ext_vendors ) {
		$jharudar_ext_subtab = 'vendors';
	}
}

?>

<div class="jharudar-extensions-page">

	<?php if ( ! $jharudar_any_ext_active ) : ?>
		<!-- No Extensions Active -->
		<div class="jharudar-module-content">
			<div class="jharudar-empty-state">
				<span class="dashicons dashicons-admin-plugins"></span>
				<h3><?php esc_html_e( 'No Supported Extensions Found', 'jharudar-for-woocommerce' ); ?></h3>
				<p><?php esc_html_e( 'Jharudar supports cleanup for the following WooCommerce extensions. Install and activate any of them to unlock their cleanup tools.', 'jharudar-for-woocommerce' ); ?></p>
			</div>

			<div class="jharudar-extensions-info">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Extension', 'jharudar-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Status', 'jharudar-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Cleanup Features', 'jharudar-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'WooCommerce Subscriptions', 'jharudar-for-woocommerce' ); ?></strong></td>
							<td><span class="jharudar-status jharudar-status-cancelled"><?php esc_html_e( 'Not Active', 'jharudar-for-woocommerce' ); ?></span></td>
							<td><?php esc_html_e( 'Delete cancelled, expired, and old subscriptions with renewal orders.', 'jharudar-for-woocommerce' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'WooCommerce Memberships', 'jharudar-for-woocommerce' ); ?></strong></td>
							<td><span class="jharudar-status jharudar-status-cancelled"><?php esc_html_e( 'Not Active', 'jharudar-for-woocommerce' ); ?></span></td>
							<td><?php esc_html_e( 'Delete cancelled, expired, and paused memberships by plan.', 'jharudar-for-woocommerce' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'WooCommerce Bookings', 'jharudar-for-woocommerce' ); ?></strong></td>
							<td><span class="jharudar-status jharudar-status-cancelled"><?php esc_html_e( 'Not Active', 'jharudar-for-woocommerce' ); ?></span></td>
							<td><?php esc_html_e( 'Delete bookings by status and date range, including past bookings.', 'jharudar-for-woocommerce' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'WooCommerce Appointments', 'jharudar-for-woocommerce' ); ?></strong></td>
							<td><span class="jharudar-status jharudar-status-cancelled"><?php esc_html_e( 'Not Active', 'jharudar-for-woocommerce' ); ?></span></td>
							<td><?php esc_html_e( 'Delete appointments by status, staff, and date range.', 'jharudar-for-woocommerce' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'WooCommerce Product Vendors', 'jharudar-for-woocommerce' ); ?></strong></td>
							<td><span class="jharudar-status jharudar-status-cancelled"><?php esc_html_e( 'Not Active', 'jharudar-for-woocommerce' ); ?></span></td>
							<td><?php esc_html_e( 'Delete vendor data and commission records.', 'jharudar-for-woocommerce' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

	<?php else : ?>
		<!-- Sub-tabs for Active Extensions -->
		<div class="jharudar-subtabs">
			<?php if ( $jharudar_ext_subscriptions ) : ?>
				<a href="<?php echo esc_url( jharudar_admin_url( 'extensions', array( 'subtab' => 'subscriptions' ) ) ); ?>"
					class="jharudar-subtab <?php echo 'subscriptions' === $jharudar_ext_subtab ? 'active' : ''; ?>">
					<?php esc_html_e( 'Subscriptions', 'jharudar-for-woocommerce' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $jharudar_ext_memberships ) : ?>
				<a href="<?php echo esc_url( jharudar_admin_url( 'extensions', array( 'subtab' => 'memberships' ) ) ); ?>"
					class="jharudar-subtab <?php echo 'memberships' === $jharudar_ext_subtab ? 'active' : ''; ?>">
					<?php esc_html_e( 'Memberships', 'jharudar-for-woocommerce' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $jharudar_ext_bookings ) : ?>
				<a href="<?php echo esc_url( jharudar_admin_url( 'extensions', array( 'subtab' => 'bookings' ) ) ); ?>"
					class="jharudar-subtab <?php echo 'bookings' === $jharudar_ext_subtab ? 'active' : ''; ?>">
					<?php esc_html_e( 'Bookings', 'jharudar-for-woocommerce' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $jharudar_ext_appointments ) : ?>
				<a href="<?php echo esc_url( jharudar_admin_url( 'extensions', array( 'subtab' => 'appointments' ) ) ); ?>"
					class="jharudar-subtab <?php echo 'appointments' === $jharudar_ext_subtab ? 'active' : ''; ?>">
					<?php esc_html_e( 'Appointments', 'jharudar-for-woocommerce' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $jharudar_ext_vendors ) : ?>
				<a href="<?php echo esc_url( jharudar_admin_url( 'extensions', array( 'subtab' => 'vendors' ) ) ); ?>"
					class="jharudar-subtab <?php echo 'vendors' === $jharudar_ext_subtab ? 'active' : ''; ?>">
					<?php esc_html_e( 'Product Vendors', 'jharudar-for-woocommerce' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( 'subscriptions' === $jharudar_ext_subtab && $jharudar_ext_subscriptions ) : ?>
			<?php
			$jharudar_sub_stats    = Jharudar_Subscriptions::get_statistics();
			$jharudar_sub_module   = new Jharudar_Subscriptions();
			$jharudar_sub_statuses = $jharudar_sub_module->get_statuses();
			?>
			<!-- Subscriptions Section -->
			<div class="jharudar-module-content">
				<div class="jharudar-module-header">
					<h3><?php esc_html_e( 'Subscription Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Find and remove cancelled, expired, or old subscriptions. Always export before deleting to keep a backup.', 'jharudar-for-woocommerce' ); ?></p>
				</div>
				<div class="notice notice-warning inline">
					<p><strong><?php esc_html_e( 'Heads up:', 'jharudar-for-woocommerce' ); ?></strong>
					<?php esc_html_e( 'Deleting a subscription does not automatically remove its renewal orders, switch orders, or parent order. Those orders will remain in your store. Associated scheduled actions (renewal retries, status changes) are cleaned up automatically.', 'jharudar-for-woocommerce' ); ?></p>
				</div>

				<div class="jharudar-quick-stats">
					<div class="jharudar-quick-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_sub_stats['total'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Total', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-success-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_sub_stats['active'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Active', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-warning-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_sub_stats['on_hold'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'On Hold', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-danger-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_sub_stats['cancelled'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Cancelled', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-danger-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_sub_stats['expired'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Expired', 'jharudar-for-woocommerce' ); ?></span>
					</div>
				</div>

				<div class="jharudar-filters">
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-sub-status"><?php esc_html_e( 'Status', 'jharudar-for-woocommerce' ); ?></label>
						<select id="jharudar-filter-sub-status" class="jharudar-select">
							<option value=""><?php esc_html_e( 'All Statuses', 'jharudar-for-woocommerce' ); ?></option>
							<?php foreach ( $jharudar_sub_statuses as $jharudar_status_key => $jharudar_status_label ) : ?>
								<option value="<?php echo esc_attr( str_replace( 'wc-', '', $jharudar_status_key ) ); ?>"><?php echo esc_html( $jharudar_status_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-sub-date-after"><?php esc_html_e( 'Created After', 'jharudar-for-woocommerce' ); ?></label>
						<input type="date" id="jharudar-filter-sub-date-after" class="jharudar-date-input" />
					</div>
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-sub-date-before"><?php esc_html_e( 'Created Before', 'jharudar-for-woocommerce' ); ?></label>
						<input type="date" id="jharudar-filter-sub-date-before" class="jharudar-date-input" />
					</div>
					<div class="jharudar-filter-group jharudar-filter-actions">
						<label>&nbsp;</label>
						<button type="button" class="button button-primary" id="jharudar-filter-subscriptions"><?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?></button>
						<button type="button" class="button" id="jharudar-reset-sub-filters"><?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?></button>
					</div>
				</div>

				<div class="jharudar-actions-bar">
					<div class="jharudar-bulk-actions">
						<label><input type="checkbox" id="jharudar-select-all-subscriptions" /> <?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?></label>
						<span class="jharudar-selected-count hidden"><span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-actions-right">
					<label class="jharudar-checkbox-label">
						<input type="checkbox" id="jharudar-delete-renewals" />
						<?php esc_html_e( 'Also delete renewal orders', 'jharudar-for-woocommerce' ); ?>
					</label>
					<select id="jharudar-ext-action-subscriptions" class="jharudar-select jharudar-ext-delete-action" style="width: auto; min-width: 140px;">
						<option value="trash"><?php esc_html_e( 'Move to Trash', 'jharudar-for-woocommerce' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete Permanently', 'jharudar-for-woocommerce' ); ?></option>
					</select>
					<button type="button" class="button" id="jharudar-export-subscriptions" disabled><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export Selected', 'jharudar-for-woocommerce' ); ?></button>
					<button type="button" class="button button-primary" id="jharudar-delete-subscriptions" disabled><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Process Selected', 'jharudar-for-woocommerce' ); ?></button>
					<?php
					$jharudar_sub_trash_count = Jharudar_Subscriptions::count_trashed();
					if ( $jharudar_sub_trash_count > 0 ) :
						?>
						<button type="button" class="button jharudar-empty-trash-btn" data-module="subscriptions" data-count="<?php echo esc_attr( $jharudar_sub_trash_count ); ?>">
							<span class="dashicons dashicons-trash"></span>
							<?php
							printf(
								/* translators: %s: number of trashed items. */
								esc_html__( 'Empty Trash (%s)', 'jharudar-for-woocommerce' ),
								esc_html( number_format_i18n( $jharudar_sub_trash_count ) )
							);
							?>
						</button>
					<?php endif; ?>
				</div>
				</div>

				<div class="jharudar-progress-wrapper" id="jharudar-subscriptions-progress">
					<div class="jharudar-progress-bar"><div class="jharudar-progress-fill"></div></div>
					<div class="jharudar-progress-text"><span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'subscriptions processed', 'jharudar-for-woocommerce' ); ?></div>
				</div>

				<div class="jharudar-results-container" id="jharudar-subscriptions-results">
					<div class="jharudar-loading"><span class="spinner is-active"></span> <?php esc_html_e( 'Loading...', 'jharudar-for-woocommerce' ); ?></div>
				</div>

				<div class="jharudar-pagination hidden" id="jharudar-subscriptions-pagination">
					<button type="button" class="button" id="jharudar-load-more-subscriptions"><?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?></button>
					<span class="jharudar-showing"><?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span></span>
				</div>
			</div>

		<?php elseif ( 'memberships' === $jharudar_ext_subtab && $jharudar_ext_memberships ) : ?>
			<?php
			$jharudar_mem_stats    = Jharudar_Memberships::get_statistics();
			$jharudar_mem_module   = new Jharudar_Memberships();
			$jharudar_mem_statuses = $jharudar_mem_module->get_statuses();
			$jharudar_mem_plans    = $jharudar_mem_module->get_plans();
			?>
			<!-- Memberships Section -->
			<div class="jharudar-module-content">
				<div class="jharudar-module-header">
					<h3><?php esc_html_e( 'Membership Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Find and remove cancelled, expired, or paused memberships. Always export before deleting to keep a backup.', 'jharudar-for-woocommerce' ); ?></p>
				</div>
				<div class="notice notice-warning inline">
					<p><strong><?php esc_html_e( 'Heads up:', 'jharudar-for-woocommerce' ); ?></strong>
					<?php esc_html_e( 'Deleting a membership removes the user\'s access and profile fields, but does not cancel any linked subscription. If WooCommerce Subscriptions is also active, the underlying subscription may continue billing unless cancelled separately.', 'jharudar-for-woocommerce' ); ?></p>
				</div>

				<div class="jharudar-quick-stats">
					<div class="jharudar-quick-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_mem_stats['total'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Total', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-success-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_mem_stats['active'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Active', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-danger-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_mem_stats['expired'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Expired', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-danger-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_mem_stats['cancelled'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Cancelled', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-warning-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_mem_stats['paused'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Paused', 'jharudar-for-woocommerce' ); ?></span>
					</div>
				</div>

				<div class="jharudar-filters">
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-mem-status"><?php esc_html_e( 'Status', 'jharudar-for-woocommerce' ); ?></label>
						<select id="jharudar-filter-mem-status" class="jharudar-select">
							<option value=""><?php esc_html_e( 'All Statuses', 'jharudar-for-woocommerce' ); ?></option>
							<?php foreach ( $jharudar_mem_statuses as $jharudar_status_key => $jharudar_status_data ) : ?>
								<option value="<?php echo esc_attr( str_replace( 'wcm-', '', $jharudar_status_key ) ); ?>">
									<?php echo esc_html( is_array( $jharudar_status_data ) ? $jharudar_status_data['label'] : $jharudar_status_data ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php if ( ! empty( $jharudar_mem_plans ) ) : ?>
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-mem-plan"><?php esc_html_e( 'Plan', 'jharudar-for-woocommerce' ); ?></label>
						<select id="jharudar-filter-mem-plan" class="jharudar-select">
							<option value=""><?php esc_html_e( 'All Plans', 'jharudar-for-woocommerce' ); ?></option>
							<?php foreach ( $jharudar_mem_plans as $jharudar_plan_id => $jharudar_plan_name ) : ?>
								<option value="<?php echo esc_attr( $jharudar_plan_id ); ?>"><?php echo esc_html( $jharudar_plan_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-mem-date-after"><?php esc_html_e( 'Created After', 'jharudar-for-woocommerce' ); ?></label>
						<input type="date" id="jharudar-filter-mem-date-after" class="jharudar-date-input" />
					</div>
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-mem-date-before"><?php esc_html_e( 'Created Before', 'jharudar-for-woocommerce' ); ?></label>
						<input type="date" id="jharudar-filter-mem-date-before" class="jharudar-date-input" />
					</div>
					<div class="jharudar-filter-group jharudar-filter-actions">
						<label>&nbsp;</label>
						<button type="button" class="button button-primary" id="jharudar-filter-memberships"><?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?></button>
						<button type="button" class="button" id="jharudar-reset-mem-filters"><?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?></button>
					</div>
				</div>

				<div class="jharudar-actions-bar">
					<div class="jharudar-bulk-actions">
						<label><input type="checkbox" id="jharudar-select-all-memberships" /> <?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?></label>
						<span class="jharudar-selected-count hidden"><span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-actions-right">
					<select id="jharudar-ext-action-memberships" class="jharudar-select jharudar-ext-delete-action" style="width: auto; min-width: 140px;">
						<option value="trash"><?php esc_html_e( 'Move to Trash', 'jharudar-for-woocommerce' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete Permanently', 'jharudar-for-woocommerce' ); ?></option>
					</select>
					<button type="button" class="button" id="jharudar-export-memberships" disabled><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export Selected', 'jharudar-for-woocommerce' ); ?></button>
					<button type="button" class="button button-primary" id="jharudar-delete-memberships" disabled><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Process Selected', 'jharudar-for-woocommerce' ); ?></button>
					<?php
					$jharudar_mem_trash_count = Jharudar_Memberships::count_trashed();
					if ( $jharudar_mem_trash_count > 0 ) :
						?>
						<button type="button" class="button jharudar-empty-trash-btn" data-module="memberships" data-count="<?php echo esc_attr( $jharudar_mem_trash_count ); ?>">
							<span class="dashicons dashicons-trash"></span>
							<?php
							printf(
								/* translators: %s: number of trashed items. */
								esc_html__( 'Empty Trash (%s)', 'jharudar-for-woocommerce' ),
								esc_html( number_format_i18n( $jharudar_mem_trash_count ) )
							);
							?>
						</button>
					<?php endif; ?>
				</div>
				</div>

				<div class="jharudar-progress-wrapper" id="jharudar-memberships-progress">
					<div class="jharudar-progress-bar"><div class="jharudar-progress-fill"></div></div>
					<div class="jharudar-progress-text"><span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'memberships processed', 'jharudar-for-woocommerce' ); ?></div>
				</div>

				<div class="jharudar-results-container" id="jharudar-memberships-results">
					<div class="jharudar-loading"><span class="spinner is-active"></span> <?php esc_html_e( 'Loading...', 'jharudar-for-woocommerce' ); ?></div>
				</div>

				<div class="jharudar-pagination hidden" id="jharudar-memberships-pagination">
					<button type="button" class="button" id="jharudar-load-more-memberships"><?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?></button>
					<span class="jharudar-showing"><?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span></span>
				</div>
			</div>

		<?php elseif ( 'bookings' === $jharudar_ext_subtab && $jharudar_ext_bookings ) : ?>
			<?php
			$jharudar_bk_stats    = Jharudar_Bookings::get_statistics();
			$jharudar_bk_module   = new Jharudar_Bookings();
			$jharudar_bk_statuses = $jharudar_bk_module->get_statuses();
			?>
			<!-- Bookings Section -->
			<div class="jharudar-module-content">
				<div class="jharudar-module-header">
					<h3><?php esc_html_e( 'Booking Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Find and remove old, cancelled, or past bookings. Always export before deleting to keep a backup.', 'jharudar-for-woocommerce' ); ?></p>
				</div>
				<div class="notice notice-warning inline">
					<p><strong><?php esc_html_e( 'Heads up:', 'jharudar-for-woocommerce' ); ?></strong>
					<?php esc_html_e( 'Deleting a booking removes it from your store and clears related cache. If a booking was the only item in an order, the linked order may also be deleted. Google Calendar events synced for deleted bookings are cleaned up automatically.', 'jharudar-for-woocommerce' ); ?></p>
				</div>

				<div class="jharudar-quick-stats">
					<div class="jharudar-quick-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_bk_stats['total'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Total', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-success-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_bk_stats['confirmed'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Confirmed', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_bk_stats['complete'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Complete', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-danger-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_bk_stats['cancelled'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Cancelled', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-warning-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_bk_stats['past'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Past', 'jharudar-for-woocommerce' ); ?></span>
					</div>
				</div>

				<div class="jharudar-filters">
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-bk-status"><?php esc_html_e( 'Status', 'jharudar-for-woocommerce' ); ?></label>
						<select id="jharudar-filter-bk-status" class="jharudar-select">
							<option value=""><?php esc_html_e( 'All Statuses', 'jharudar-for-woocommerce' ); ?></option>
							<?php foreach ( $jharudar_bk_statuses as $jharudar_status_key => $jharudar_status_label ) : ?>
								<option value="<?php echo esc_attr( $jharudar_status_key ); ?>"><?php echo esc_html( $jharudar_status_label ); ?></option>
							<?php endforeach; ?>
							<option value="past"><?php esc_html_e( 'Past Bookings Only', 'jharudar-for-woocommerce' ); ?></option>
						</select>
					</div>
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-bk-date-after"><?php esc_html_e( 'Created After', 'jharudar-for-woocommerce' ); ?></label>
						<input type="date" id="jharudar-filter-bk-date-after" class="jharudar-date-input" />
					</div>
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-bk-date-before"><?php esc_html_e( 'Created Before', 'jharudar-for-woocommerce' ); ?></label>
						<input type="date" id="jharudar-filter-bk-date-before" class="jharudar-date-input" />
					</div>
					<div class="jharudar-filter-group jharudar-filter-actions">
						<label>&nbsp;</label>
						<button type="button" class="button button-primary" id="jharudar-filter-bookings"><?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?></button>
						<button type="button" class="button" id="jharudar-reset-bk-filters"><?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?></button>
					</div>
				</div>

				<div class="jharudar-actions-bar">
					<div class="jharudar-bulk-actions">
						<label><input type="checkbox" id="jharudar-select-all-bookings" /> <?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?></label>
						<span class="jharudar-selected-count hidden"><span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-actions-right">
					<label class="jharudar-checkbox-label">
						<input type="checkbox" id="jharudar-delete-booking-orders" />
						<?php esc_html_e( 'Also delete linked orders', 'jharudar-for-woocommerce' ); ?>
					</label>
					<select id="jharudar-ext-action-bookings" class="jharudar-select jharudar-ext-delete-action" style="width: auto; min-width: 140px;">
						<option value="trash"><?php esc_html_e( 'Move to Trash', 'jharudar-for-woocommerce' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete Permanently', 'jharudar-for-woocommerce' ); ?></option>
					</select>
					<button type="button" class="button" id="jharudar-export-bookings" disabled><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export Selected', 'jharudar-for-woocommerce' ); ?></button>
					<button type="button" class="button button-primary" id="jharudar-delete-bookings" disabled><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Process Selected', 'jharudar-for-woocommerce' ); ?></button>
					<?php
					$jharudar_bk_trash_count = Jharudar_Bookings::count_trashed();
					if ( $jharudar_bk_trash_count > 0 ) :
						?>
						<button type="button" class="button jharudar-empty-trash-btn" data-module="bookings" data-count="<?php echo esc_attr( $jharudar_bk_trash_count ); ?>">
							<span class="dashicons dashicons-trash"></span>
							<?php
							printf(
								/* translators: %s: number of trashed items. */
								esc_html__( 'Empty Trash (%s)', 'jharudar-for-woocommerce' ),
								esc_html( number_format_i18n( $jharudar_bk_trash_count ) )
							);
							?>
						</button>
					<?php endif; ?>
				</div>
				</div>

				<div class="jharudar-progress-wrapper" id="jharudar-bookings-progress">
					<div class="jharudar-progress-bar"><div class="jharudar-progress-fill"></div></div>
					<div class="jharudar-progress-text"><span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'bookings processed', 'jharudar-for-woocommerce' ); ?></div>
				</div>

				<div class="jharudar-results-container" id="jharudar-bookings-results">
					<div class="jharudar-loading"><span class="spinner is-active"></span> <?php esc_html_e( 'Loading...', 'jharudar-for-woocommerce' ); ?></div>
				</div>

				<div class="jharudar-pagination hidden" id="jharudar-bookings-pagination">
					<button type="button" class="button" id="jharudar-load-more-bookings"><?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?></button>
					<span class="jharudar-showing"><?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span></span>
				</div>
			</div>

		<?php elseif ( 'appointments' === $jharudar_ext_subtab && $jharudar_ext_appointments ) : ?>
			<?php
			$jharudar_apt_stats    = Jharudar_Appointments::get_statistics();
			$jharudar_apt_module   = new Jharudar_Appointments();
			$jharudar_apt_statuses = $jharudar_apt_module->get_statuses();
			?>
			<!-- Appointments Section -->
			<div class="jharudar-module-content">
				<div class="jharudar-module-header">
					<h3><?php esc_html_e( 'Appointment Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Find and remove old, cancelled, or past appointments. Always export before deleting to keep a backup.', 'jharudar-for-woocommerce' ); ?></p>
				</div>

				<div class="jharudar-quick-stats">
					<div class="jharudar-quick-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_apt_stats['total'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Total', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-success-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_apt_stats['confirmed'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Confirmed', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_apt_stats['complete'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Complete', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-danger-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_apt_stats['cancelled'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Cancelled', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-warning-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_apt_stats['past'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Past', 'jharudar-for-woocommerce' ); ?></span>
					</div>
				</div>

				<div class="jharudar-filters">
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-apt-status"><?php esc_html_e( 'Status', 'jharudar-for-woocommerce' ); ?></label>
						<select id="jharudar-filter-apt-status" class="jharudar-select">
							<option value=""><?php esc_html_e( 'All Statuses', 'jharudar-for-woocommerce' ); ?></option>
							<?php foreach ( $jharudar_apt_statuses as $jharudar_status_key => $jharudar_status_label ) : ?>
								<option value="<?php echo esc_attr( $jharudar_status_key ); ?>"><?php echo esc_html( $jharudar_status_label ); ?></option>
							<?php endforeach; ?>
							<option value="past"><?php esc_html_e( 'Past Appointments Only', 'jharudar-for-woocommerce' ); ?></option>
						</select>
					</div>
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-apt-date-after"><?php esc_html_e( 'Created After', 'jharudar-for-woocommerce' ); ?></label>
						<input type="date" id="jharudar-filter-apt-date-after" class="jharudar-date-input" />
					</div>
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-apt-date-before"><?php esc_html_e( 'Created Before', 'jharudar-for-woocommerce' ); ?></label>
						<input type="date" id="jharudar-filter-apt-date-before" class="jharudar-date-input" />
					</div>
					<div class="jharudar-filter-group jharudar-filter-actions">
						<label>&nbsp;</label>
						<button type="button" class="button button-primary" id="jharudar-filter-appointments"><?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?></button>
						<button type="button" class="button" id="jharudar-reset-apt-filters"><?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?></button>
					</div>
				</div>

				<div class="jharudar-actions-bar">
					<div class="jharudar-bulk-actions">
						<label><input type="checkbox" id="jharudar-select-all-appointments" /> <?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?></label>
						<span class="jharudar-selected-count hidden"><span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-actions-right">
					<select id="jharudar-ext-action-appointments" class="jharudar-select jharudar-ext-delete-action" style="width: auto; min-width: 140px;">
						<option value="trash"><?php esc_html_e( 'Move to Trash', 'jharudar-for-woocommerce' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete Permanently', 'jharudar-for-woocommerce' ); ?></option>
					</select>
					<button type="button" class="button" id="jharudar-export-appointments" disabled><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export Selected', 'jharudar-for-woocommerce' ); ?></button>
					<button type="button" class="button button-primary" id="jharudar-delete-appointments" disabled><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Process Selected', 'jharudar-for-woocommerce' ); ?></button>
					<?php
					$jharudar_apt_trash_count = Jharudar_Appointments::count_trashed();
					if ( $jharudar_apt_trash_count > 0 ) :
						?>
						<button type="button" class="button jharudar-empty-trash-btn" data-module="appointments" data-count="<?php echo esc_attr( $jharudar_apt_trash_count ); ?>">
							<span class="dashicons dashicons-trash"></span>
							<?php
							printf(
								/* translators: %s: number of trashed items. */
								esc_html__( 'Empty Trash (%s)', 'jharudar-for-woocommerce' ),
								esc_html( number_format_i18n( $jharudar_apt_trash_count ) )
							);
							?>
						</button>
					<?php endif; ?>
				</div>
				</div>

				<div class="jharudar-progress-wrapper" id="jharudar-appointments-progress">
					<div class="jharudar-progress-bar"><div class="jharudar-progress-fill"></div></div>
					<div class="jharudar-progress-text"><span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'appointments processed', 'jharudar-for-woocommerce' ); ?></div>
				</div>

				<div class="jharudar-results-container" id="jharudar-appointments-results">
					<div class="jharudar-loading"><span class="spinner is-active"></span> <?php esc_html_e( 'Loading...', 'jharudar-for-woocommerce' ); ?></div>
				</div>

				<div class="jharudar-pagination hidden" id="jharudar-appointments-pagination">
					<button type="button" class="button" id="jharudar-load-more-appointments"><?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?></button>
					<span class="jharudar-showing"><?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span></span>
				</div>
			</div>

		<?php elseif ( 'vendors' === $jharudar_ext_subtab && $jharudar_ext_vendors ) : ?>
			<?php $jharudar_vendor_stats = Jharudar_Vendors::get_statistics(); ?>
			<!-- Vendors Section -->
			<div class="jharudar-module-content">
				<div class="jharudar-module-header">
					<h3><?php esc_html_e( 'Product Vendors Cleanup', 'jharudar-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Manage and remove vendor data including empty vendor profiles and commission records.', 'jharudar-for-woocommerce' ); ?></p>
				</div>
				<div class="notice notice-warning inline">
					<p><strong><?php esc_html_e( 'Heads up:', 'jharudar-for-woocommerce' ); ?></strong>
					<?php esc_html_e( 'Deleting a vendor unassigns all their products (products remain in your store but lose their vendor association), removes commission records, and clears vendor cache. Vendor admin users keep their accounts but may retain elevated permissions. Historical order data referencing the vendor is preserved for accounting purposes.', 'jharudar-for-woocommerce' ); ?></p>
				</div>

				<div class="jharudar-quick-stats">
					<div class="jharudar-quick-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_vendor_stats['total'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Total Vendors', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat jharudar-warning-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_vendor_stats['no_products'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'No Products', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-quick-stat">
						<span class="stat-number"><?php echo esc_html( number_format_i18n( $jharudar_vendor_stats['no_commissions'] ) ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'No Commissions', 'jharudar-for-woocommerce' ); ?></span>
					</div>
				</div>

				<div class="jharudar-filters">
					<div class="jharudar-filter-group">
						<label for="jharudar-filter-vendor-type"><?php esc_html_e( 'Filter By', 'jharudar-for-woocommerce' ); ?></label>
						<select id="jharudar-filter-vendor-type" class="jharudar-select">
							<option value=""><?php esc_html_e( 'All Vendors', 'jharudar-for-woocommerce' ); ?></option>
							<option value="no_products"><?php esc_html_e( 'Vendors With No Products', 'jharudar-for-woocommerce' ); ?></option>
							<option value="no_commissions"><?php esc_html_e( 'Vendors With No Commissions', 'jharudar-for-woocommerce' ); ?></option>
						</select>
					</div>
					<div class="jharudar-filter-group jharudar-filter-actions">
						<label>&nbsp;</label>
						<button type="button" class="button button-primary" id="jharudar-filter-vendors"><?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?></button>
						<button type="button" class="button" id="jharudar-reset-vendor-filters"><?php esc_html_e( 'Reset', 'jharudar-for-woocommerce' ); ?></button>
					</div>
				</div>

				<div class="jharudar-actions-bar">
					<div class="jharudar-bulk-actions">
						<label><input type="checkbox" id="jharudar-select-all-vendors" /> <?php esc_html_e( 'Select All', 'jharudar-for-woocommerce' ); ?></label>
						<span class="jharudar-selected-count hidden"><span class="count">0</span> <?php esc_html_e( 'selected', 'jharudar-for-woocommerce' ); ?></span>
					</div>
					<div class="jharudar-actions-right">
						<button type="button" class="button" id="jharudar-delete-vendor-commissions" disabled><span class="dashicons dashicons-editor-removeformatting"></span> <?php esc_html_e( 'Delete Commissions', 'jharudar-for-woocommerce' ); ?></button>
						<button type="button" class="button button-primary" id="jharudar-delete-vendors" disabled><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete Selected', 'jharudar-for-woocommerce' ); ?></button>
					</div>
				</div>

				<div class="jharudar-progress-wrapper" id="jharudar-vendors-progress">
					<div class="jharudar-progress-bar"><div class="jharudar-progress-fill"></div></div>
					<div class="jharudar-progress-text"><span class="processed">0</span> / <span class="total">0</span> <?php esc_html_e( 'vendors processed', 'jharudar-for-woocommerce' ); ?></div>
				</div>

				<div class="jharudar-results-container" id="jharudar-vendors-results">
					<div class="jharudar-loading"><span class="spinner is-active"></span> <?php esc_html_e( 'Loading...', 'jharudar-for-woocommerce' ); ?></div>
				</div>

				<div class="jharudar-pagination hidden" id="jharudar-vendors-pagination">
					<button type="button" class="button" id="jharudar-load-more-vendors"><?php esc_html_e( 'Load More', 'jharudar-for-woocommerce' ); ?></button>
					<span class="jharudar-showing"><?php esc_html_e( 'Showing', 'jharudar-for-woocommerce' ); ?> <span class="shown">0</span> <?php esc_html_e( 'of', 'jharudar-for-woocommerce' ); ?> <span class="total">0</span></span>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="jharudar-modal-overlay" id="jharudar-ext-delete-modal">
	<div class="jharudar-modal">
		<div class="jharudar-modal-header">
			<h3 id="jharudar-ext-modal-title"><?php esc_html_e( 'Confirm Deletion', 'jharudar-for-woocommerce' ); ?></h3>
		</div>
		<div class="jharudar-modal-body">
			<p id="jharudar-ext-modal-description"><?php esc_html_e( 'You are about to permanently delete the selected items. This action cannot be undone.', 'jharudar-for-woocommerce' ); ?></p>
			<p class="jharudar-delete-summary"></p>

			<div class="jharudar-modal-options">
				<label class="jharudar-checkbox-label">
					<input type="checkbox" id="jharudar-confirm-ext-backup" />
					<?php esc_html_e( 'I have exported a backup of these items', 'jharudar-for-woocommerce' ); ?>
				</label>
			</div>

			<div class="jharudar-modal-input" id="jharudar-ext-confirm-input-wrapper">
				<label for="jharudar-confirm-ext-delete-input">
					<?php esc_html_e( 'Type DELETE to confirm:', 'jharudar-for-woocommerce' ); ?>
				</label>
				<input type="text" id="jharudar-confirm-ext-delete-input" autocomplete="off" />
			</div>
		</div>
		<div class="jharudar-modal-footer">
			<button type="button" class="button" id="jharudar-cancel-ext-delete">
				<?php esc_html_e( 'Cancel', 'jharudar-for-woocommerce' ); ?>
			</button>
			<button type="button" class="button button-primary button-danger" id="jharudar-confirm-ext-delete" disabled>
				<?php esc_html_e( 'Confirm', 'jharudar-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>
