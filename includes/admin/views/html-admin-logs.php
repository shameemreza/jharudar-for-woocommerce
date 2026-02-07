<?php
/**
 * Activity Log view.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render activity logs page.
 *
 * @since 0.0.1
 * @return void
 */
function jharudar_render_logs_page() {
	// Handle clear logs action.
	if ( isset( $_POST['jharudar_clear_logs'] ) ) {
		// Verify nonce.
		if ( ! isset( $_POST['jharudar_logs_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jharudar_logs_nonce'] ) ), 'jharudar_logs_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'jharudar-for-woocommerce' ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to clear logs.', 'jharudar-for-woocommerce' ) );
		}

		$jharudar_logger = new Jharudar_Logger();
		$jharudar_logger->clear_logs();

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Activity logs cleared successfully.', 'jharudar-for-woocommerce' ) . '</p></div>';
	}

	// Get filter values.
	$jharudar_filter_action = isset( $_GET['filter_action'] ) ? sanitize_key( wp_unslash( $_GET['filter_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$jharudar_filter_type   = isset( $_GET['filter_object_type'] ) ? sanitize_key( wp_unslash( $_GET['filter_object_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$jharudar_date_from     = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$jharudar_date_to       = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	// Pagination.
	$jharudar_per_page = 20;
	$jharudar_page     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$jharudar_offset   = ( $jharudar_page - 1 ) * $jharudar_per_page;

	// Get logs.
	$jharudar_logger      = new Jharudar_Logger();
	$jharudar_args        = array(
		'action'      => $jharudar_filter_action,
		'object_type' => $jharudar_filter_type,
		'date_from'   => $jharudar_date_from,
		'date_to'     => $jharudar_date_to,
		'limit'       => $jharudar_per_page,
		'offset'      => $jharudar_offset,
		'order'       => 'DESC',
	);
	$jharudar_logs        = $jharudar_logger->get_logs( $jharudar_args );
	$jharudar_total_logs  = $jharudar_logger->get_count(
		array(
			'action'      => $jharudar_filter_action,
			'object_type' => $jharudar_filter_type,
			'date_from'   => $jharudar_date_from,
			'date_to'     => $jharudar_date_to,
		)
	);
	$jharudar_total_pages = ceil( $jharudar_total_logs / $jharudar_per_page );

	// Available actions for filter.
	$jharudar_actions = array(
		'delete'          => __( 'Deleted', 'jharudar-for-woocommerce' ),
		'bulk_delete'     => __( 'Bulk Deleted', 'jharudar-for-woocommerce' ),
		'trash'           => __( 'Trashed', 'jharudar-for-woocommerce' ),
		'restore'         => __( 'Restored', 'jharudar-for-woocommerce' ),
		'anonymize'       => __( 'Anonymized', 'jharudar-for-woocommerce' ),
		'export'          => __( 'Exported', 'jharudar-for-woocommerce' ),
		'clean_transient' => __( 'Cleaned Transients', 'jharudar-for-woocommerce' ),
		'clean_orphan'    => __( 'Cleaned Orphaned Data', 'jharudar-for-woocommerce' ),
		'optimize'        => __( 'Optimized', 'jharudar-for-woocommerce' ),
	);

	// Available object types for filter.
	$jharudar_types = array(
		'product'      => __( 'Product', 'jharudar-for-woocommerce' ),
		'order'        => __( 'Order', 'jharudar-for-woocommerce' ),
		'customer'     => __( 'Customer', 'jharudar-for-woocommerce' ),
		'coupon'       => __( 'Coupon', 'jharudar-for-woocommerce' ),
		'subscription' => __( 'Subscription', 'jharudar-for-woocommerce' ),
		'membership'   => __( 'Membership', 'jharudar-for-woocommerce' ),
		'booking'      => __( 'Booking', 'jharudar-for-woocommerce' ),
		'category'     => __( 'Category', 'jharudar-for-woocommerce' ),
		'tag'          => __( 'Tag', 'jharudar-for-woocommerce' ),
		'attribute'    => __( 'Attribute', 'jharudar-for-woocommerce' ),
		'transient'    => __( 'Transient', 'jharudar-for-woocommerce' ),
		'session'      => __( 'Session', 'jharudar-for-woocommerce' ),
		'database'     => __( 'Database', 'jharudar-for-woocommerce' ),
	);
	?>

	<div class="jharudar-logs">
		<div class="jharudar-logs-header">
			<h2><?php esc_html_e( 'Activity Log', 'jharudar-for-woocommerce' ); ?></h2>
			<p><?php esc_html_e( 'View all cleanup operations performed by Jharudar.', 'jharudar-for-woocommerce' ); ?></p>
		</div>

		<div class="jharudar-logs-filters">
			<form method="get" class="jharudar-filter-form">
				<input type="hidden" name="page" value="jharudar" />
				<input type="hidden" name="tab" value="logs" />

				<div class="jharudar-filters">
					<div class="jharudar-filter-group">
						<label for="filter_action"><?php esc_html_e( 'Action', 'jharudar-for-woocommerce' ); ?></label>
						<select id="filter_action" name="filter_action" class="jharudar-select2 jharudar-filter-select">
							<option value=""><?php esc_html_e( 'All Actions', 'jharudar-for-woocommerce' ); ?></option>
							<?php foreach ( $jharudar_actions as $jharudar_key => $jharudar_label ) : ?>
								<option value="<?php echo esc_attr( $jharudar_key ); ?>" <?php selected( $jharudar_filter_action, $jharudar_key ); ?>>
									<?php echo esc_html( $jharudar_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="jharudar-filter-group">
						<label for="filter_object_type"><?php esc_html_e( 'Type', 'jharudar-for-woocommerce' ); ?></label>
						<select id="filter_object_type" name="filter_object_type" class="jharudar-select2 jharudar-filter-select">
							<option value=""><?php esc_html_e( 'All Types', 'jharudar-for-woocommerce' ); ?></option>
							<?php foreach ( $jharudar_types as $jharudar_key => $jharudar_label ) : ?>
								<option value="<?php echo esc_attr( $jharudar_key ); ?>" <?php selected( $jharudar_filter_type, $jharudar_key ); ?>>
									<?php echo esc_html( $jharudar_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="jharudar-filter-group">
						<label for="date_from"><?php esc_html_e( 'Date From', 'jharudar-for-woocommerce' ); ?></label>
						<input type="date" id="date_from" name="date_from" value="<?php echo esc_attr( $jharudar_date_from ); ?>" />
					</div>

					<div class="jharudar-filter-group">
						<label for="date_to"><?php esc_html_e( 'Date To', 'jharudar-for-woocommerce' ); ?></label>
						<input type="date" id="date_to" name="date_to" value="<?php echo esc_attr( $jharudar_date_to ); ?>" />
					</div>

					<div class="jharudar-filter-group jharudar-filter-group-end">
						<button type="submit" class="button"><?php esc_html_e( 'Filter', 'jharudar-for-woocommerce' ); ?></button>
						<?php if ( $jharudar_filter_action || $jharudar_filter_type || $jharudar_date_from || $jharudar_date_to ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=jharudar&tab=logs' ) ); ?>" class="button"><?php esc_html_e( 'Clear', 'jharudar-for-woocommerce' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			</form>
		</div>

		<div class="jharudar-logs-actions">
			<span class="jharudar-logs-count">
				<?php
				printf(
					/* translators: %s: Number of log entries. */
					esc_html( _n( '%s entry', '%s entries', $jharudar_total_logs, 'jharudar-for-woocommerce' ) ),
					esc_html( number_format_i18n( $jharudar_total_logs ) )
				);
				?>
			</span>

			<?php if ( $jharudar_total_logs > 0 ) : ?>
				<form method="post" class="jharudar-inline-form">
					<?php wp_nonce_field( 'jharudar_logs_action', 'jharudar_logs_nonce' ); ?>
					<button type="submit" name="jharudar_clear_logs" class="button" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear all logs? This action cannot be undone.', 'jharudar-for-woocommerce' ) ); ?>');">
						<?php esc_html_e( 'Clear All Logs', 'jharudar-for-woocommerce' ); ?>
					</button>
				</form>
			<?php endif; ?>
		</div>

		<?php if ( empty( $jharudar_logs ) ) : ?>
			<div class="jharudar-no-logs">
				<p><?php esc_html_e( 'No activity logs found.', 'jharudar-for-woocommerce' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th class="column-timestamp"><?php esc_html_e( 'Date', 'jharudar-for-woocommerce' ); ?></th>
						<th class="column-user"><?php esc_html_e( 'User', 'jharudar-for-woocommerce' ); ?></th>
						<th class="column-action"><?php esc_html_e( 'Action', 'jharudar-for-woocommerce' ); ?></th>
						<th class="column-type"><?php esc_html_e( 'Type', 'jharudar-for-woocommerce' ); ?></th>
						<th class="column-details"><?php esc_html_e( 'Details', 'jharudar-for-woocommerce' ); ?></th>
						<th class="column-ip"><?php esc_html_e( 'IP Address', 'jharudar-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $jharudar_logs as $jharudar_log ) : ?>
						<tr>
							<td class="column-timestamp">
								<?php echo esc_html( jharudar_format_date( $jharudar_log['timestamp'] ) ); ?>
							</td>
							<td class="column-user">
								<?php
								if ( ! empty( $jharudar_log['user_login'] ) ) {
									$jharudar_user_link = get_edit_user_link( $jharudar_log['user_id'] );
									if ( $jharudar_user_link ) {
										printf(
											'<a href="%s">%s</a>',
											esc_url( $jharudar_user_link ),
											esc_html( $jharudar_log['user_login'] )
										);
									} else {
										echo esc_html( $jharudar_log['user_login'] );
									}
								} else {
									esc_html_e( 'System', 'jharudar-for-woocommerce' );
								}
								?>
							</td>
							<td class="column-action">
								<?php echo esc_html( Jharudar_Logger::get_action_label( $jharudar_log['action'] ) ); ?>
							</td>
							<td class="column-type">
								<?php echo esc_html( Jharudar_Logger::get_object_type_label( $jharudar_log['object_type'] ) ); ?>
							</td>
							<td class="column-details">
								<?php
								if ( ! empty( $jharudar_log['object_id'] ) ) {
									printf(
										/* translators: %s: Object ID. */
										esc_html__( 'ID: %s', 'jharudar-for-woocommerce' ),
										esc_html( $jharudar_log['object_id'] )
									);
								}
								if ( ! empty( $jharudar_log['meta']['count'] ) ) {
									printf(
										/* translators: %s: Count of items. */
										esc_html__( '%s items', 'jharudar-for-woocommerce' ),
										esc_html( $jharudar_log['meta']['count'] )
									);
								}
								?>
							</td>
							<td class="column-ip">
								<?php echo esc_html( $jharudar_log['ip_address'] ?? '' ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $jharudar_total_pages > 1 ) : ?>
				<?php
				$jharudar_base_url = add_query_arg(
					array(
						'page'               => 'jharudar',
						'tab'                => 'logs',
						'filter_action'      => $jharudar_filter_action,
						'filter_object_type' => $jharudar_filter_type,
						'date_from'          => $jharudar_date_from,
						'date_to'            => $jharudar_date_to,
					),
					admin_url( 'admin.php' )
				);
				?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php
							printf(
								/* translators: %s: Number of items. */
								esc_html( _n( '%s item', '%s items', $jharudar_total_logs, 'jharudar-for-woocommerce' ) ),
								esc_html( number_format_i18n( $jharudar_total_logs ) )
							);
							?>
						</span>
						<span class="pagination-links">
							<?php if ( $jharudar_page > 1 ) : ?>
								<a class="first-page button" href="<?php echo esc_url( add_query_arg( 'paged', 1, $jharudar_base_url ) ); ?>"><span aria-hidden="true">&laquo;</span></a>
								<a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $jharudar_page - 1, $jharudar_base_url ) ); ?>"><span aria-hidden="true">&lsaquo;</span></a>
							<?php else : ?>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>
							<?php endif; ?>

							<span class="paging-input">
								<span class="tablenav-paging-text"><?php echo esc_html( number_format_i18n( $jharudar_page ) ); ?> / <span class="total-pages"><?php echo esc_html( number_format_i18n( $jharudar_total_pages ) ); ?></span></span>
							</span>

							<?php if ( $jharudar_page < $jharudar_total_pages ) : ?>
								<a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $jharudar_page + 1, $jharudar_base_url ) ); ?>"><span aria-hidden="true">&rsaquo;</span></a>
								<a class="last-page button" href="<?php echo esc_url( add_query_arg( 'paged', $jharudar_total_pages, $jharudar_base_url ) ); ?>"><span aria-hidden="true">&raquo;</span></a>
							<?php else : ?>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>
							<?php endif; ?>
						</span>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}

// Render the page.
jharudar_render_logs_page();
