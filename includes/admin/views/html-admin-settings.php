<?php
/**
 * Settings view.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render settings page.
 *
 * @since 0.0.1
 * @return void
 */
function jharudar_render_settings_page() {
	// Handle form submission.
	if ( isset( $_POST['jharudar_save_settings'] ) ) {
		// Verify nonce.
		if ( ! isset( $_POST['jharudar_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jharudar_settings_nonce'] ) ), 'jharudar_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'jharudar-for-woocommerce' ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'jharudar-for-woocommerce' ) );
		}

		// Sanitize and save settings.
		$jharudar_settings = array(
			'delete_data_on_uninstall'    => isset( $_POST['delete_data_on_uninstall'] ),
			'enable_activity_log'         => isset( $_POST['enable_activity_log'] ),
			'log_retention_days'          => absint( $_POST['log_retention_days'] ?? 30 ),
			'batch_size'                  => absint( $_POST['batch_size'] ?? 50 ),
			'require_confirmation'        => isset( $_POST['require_confirmation'] ),
			'require_export'              => isset( $_POST['require_export'] ),
			'email_notifications'         => isset( $_POST['email_notifications'] ),
			'notification_email'          => isset( $_POST['notification_email'] ) ? sanitize_email( wp_unslash( $_POST['notification_email'] ) ) : '',
			'auto_delete_zero_stock'      => isset( $_POST['auto_delete_zero_stock'] ),
			'auto_delete_product_images'  => isset( $_POST['auto_delete_product_images'] ),
			'skip_shared_images'          => isset( $_POST['skip_shared_images'] ),
		);

		// Validate batch size.
		if ( $jharudar_settings['batch_size'] < 10 ) {
			$jharudar_settings['batch_size'] = 10;
		} elseif ( $jharudar_settings['batch_size'] > 500 ) {
			$jharudar_settings['batch_size'] = 500;
		}

		// Validate log retention.
		if ( $jharudar_settings['log_retention_days'] < 1 ) {
			$jharudar_settings['log_retention_days'] = 1;
		} elseif ( $jharudar_settings['log_retention_days'] > 365 ) {
			$jharudar_settings['log_retention_days'] = 365;
		}

		update_option( 'jharudar_settings', $jharudar_settings );

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'jharudar-for-woocommerce' ) . '</p></div>';
	}

	// Get current settings.
	$jharudar_settings = get_option( 'jharudar_settings', array() );

	$jharudar_delete_data     = isset( $jharudar_settings['delete_data_on_uninstall'] ) ? $jharudar_settings['delete_data_on_uninstall'] : false;
	$jharudar_activity_log    = isset( $jharudar_settings['enable_activity_log'] ) ? $jharudar_settings['enable_activity_log'] : true;
	$jharudar_log_days        = isset( $jharudar_settings['log_retention_days'] ) ? $jharudar_settings['log_retention_days'] : 30;
	$jharudar_batch           = isset( $jharudar_settings['batch_size'] ) ? $jharudar_settings['batch_size'] : 50;
	$jharudar_confirmation    = isset( $jharudar_settings['require_confirmation'] ) ? $jharudar_settings['require_confirmation'] : true;
	$jharudar_export_required = isset( $jharudar_settings['require_export'] ) ? $jharudar_settings['require_export'] : false;
	$jharudar_email_notify    = isset( $jharudar_settings['email_notifications'] ) ? $jharudar_settings['email_notifications'] : false;
	$jharudar_notify_email    = isset( $jharudar_settings['notification_email'] ) ? $jharudar_settings['notification_email'] : get_option( 'admin_email' );

	// Automation settings.
	$jharudar_auto_delete_zero_stock     = isset( $jharudar_settings['auto_delete_zero_stock'] ) ? $jharudar_settings['auto_delete_zero_stock'] : false;
	$jharudar_auto_delete_product_images = isset( $jharudar_settings['auto_delete_product_images'] ) ? $jharudar_settings['auto_delete_product_images'] : false;
	$jharudar_skip_shared_images         = isset( $jharudar_settings['skip_shared_images'] ) ? $jharudar_settings['skip_shared_images'] : true;
	?>

	<div class="jharudar-settings">
		<form method="post" action="">
			<?php wp_nonce_field( 'jharudar_save_settings', 'jharudar_settings_nonce' ); ?>

			<div class="jharudar-settings-section">
				<h3><?php esc_html_e( 'General Settings', 'jharudar-for-woocommerce' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="batch_size"><?php esc_html_e( 'Batch Size', 'jharudar-for-woocommerce' ); ?></label>
						</th>
						<td>
							<input type="number" id="batch_size" name="batch_size" value="<?php echo esc_attr( $jharudar_batch ); ?>" min="10" max="500" class="small-text" />
							<p class="description">
								<?php esc_html_e( 'Number of items to process per batch during cleanup operations. Higher values are faster but use more memory.', 'jharudar-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="require_confirmation"><?php esc_html_e( 'Require Confirmation', 'jharudar-for-woocommerce' ); ?></label>
						</th>
						<td>
							<label for="require_confirmation">
								<input type="checkbox" id="require_confirmation" name="require_confirmation" value="1" <?php checked( $jharudar_confirmation ); ?> />
								<?php esc_html_e( 'Require typing DELETE to confirm bulk delete operations.', 'jharudar-for-woocommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="require_export"><?php esc_html_e( 'Require Export', 'jharudar-for-woocommerce' ); ?></label>
						</th>
						<td>
							<label for="require_export">
								<input type="checkbox" id="require_export" name="require_export" value="1" <?php checked( $jharudar_export_required ); ?> />
								<?php esc_html_e( 'Require exporting data before allowing delete operations.', 'jharudar-for-woocommerce' ); ?>
							</label>
						</td>
					</tr>
				</table>
			</div>

			<div class="jharudar-settings-section">
				<h3><?php esc_html_e( 'Activity Logging', 'jharudar-for-woocommerce' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="enable_activity_log"><?php esc_html_e( 'Enable Activity Log', 'jharudar-for-woocommerce' ); ?></label>
						</th>
						<td>
							<label for="enable_activity_log">
								<input type="checkbox" id="enable_activity_log" name="enable_activity_log" value="1" <?php checked( $jharudar_activity_log ); ?> />
								<?php esc_html_e( 'Log all cleanup operations for audit purposes.', 'jharudar-for-woocommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="log_retention_days"><?php esc_html_e( 'Log Retention', 'jharudar-for-woocommerce' ); ?></label>
						</th>
						<td>
							<input type="number" id="log_retention_days" name="log_retention_days" value="<?php echo esc_attr( $jharudar_log_days ); ?>" min="1" max="365" class="small-text" />
							<span><?php esc_html_e( 'days', 'jharudar-for-woocommerce' ); ?></span>
							<p class="description">
								<?php esc_html_e( 'How long to keep activity log entries before automatic deletion.', 'jharudar-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="jharudar-settings-section">
				<h3><?php esc_html_e( 'Notifications', 'jharudar-for-woocommerce' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="email_notifications"><?php esc_html_e( 'Email Notifications', 'jharudar-for-woocommerce' ); ?></label>
						</th>
						<td>
							<label for="email_notifications">
								<input type="checkbox" id="email_notifications" name="email_notifications" value="1" <?php checked( $jharudar_email_notify ); ?> />
								<?php esc_html_e( 'Send email notifications after cleanup operations complete.', 'jharudar-for-woocommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="notification_email"><?php esc_html_e( 'Notification Email', 'jharudar-for-woocommerce' ); ?></label>
						</th>
						<td>
							<input type="email" id="notification_email" name="notification_email" value="<?php echo esc_attr( $jharudar_notify_email ); ?>" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'Email address to receive cleanup notifications.', 'jharudar-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="jharudar-settings-section">
				<h3><?php esc_html_e( 'Cleanup Automation', 'jharudar-for-woocommerce' ); ?></h3>
				<p class="description" style="margin-bottom: 15px;">
					<?php esc_html_e( 'These settings enable automatic cleanup actions. Compatible with managed hosting environments (WordPress.com, WP Engine, Kinsta, etc.).', 'jharudar-for-woocommerce' ); ?>
				</p>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="auto_delete_zero_stock"><?php esc_html_e( 'Auto-Delete Zero Stock Products', 'jharudar-for-woocommerce' ); ?></label>
						</th>
						<td>
							<label for="auto_delete_zero_stock">
								<input type="checkbox" id="auto_delete_zero_stock" name="auto_delete_zero_stock" value="1" <?php checked( $jharudar_auto_delete_zero_stock ); ?> />
								<?php esc_html_e( 'Automatically delete products when stock reaches zero (after order completion).', 'jharudar-for-woocommerce' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Useful for stores selling unique or one-time items. Products are only deleted after the order is marked as completed.', 'jharudar-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="auto_delete_product_images"><?php esc_html_e( 'Auto-Delete Product Images', 'jharudar-for-woocommerce' ); ?></label>
						</th>
						<td>
							<label for="auto_delete_product_images">
								<input type="checkbox" id="auto_delete_product_images" name="auto_delete_product_images" value="1" <?php checked( $jharudar_auto_delete_product_images ); ?> />
								<?php esc_html_e( 'Automatically delete product images when a product is permanently deleted.', 'jharudar-for-woocommerce' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Removes the featured image and gallery images to keep your media library clean.', 'jharudar-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="skip_shared_images"><?php esc_html_e( 'Protect Shared Images', 'jharudar-for-woocommerce' ); ?></label>
						</th>
						<td>
							<label for="skip_shared_images">
								<input type="checkbox" id="skip_shared_images" name="skip_shared_images" value="1" <?php checked( $jharudar_skip_shared_images ); ?> />
								<?php esc_html_e( 'Skip images that are used by other products (recommended).', 'jharudar-for-woocommerce' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, images shared between products will not be deleted. This prevents accidentally removing images still in use.', 'jharudar-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="jharudar-settings-section">
				<h3><?php esc_html_e( 'Danger Zone', 'jharudar-for-woocommerce' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="delete_data_on_uninstall"><?php esc_html_e( 'Delete Data on Uninstall', 'jharudar-for-woocommerce' ); ?></label>
						</th>
						<td>
							<label for="delete_data_on_uninstall">
								<input type="checkbox" id="delete_data_on_uninstall" name="delete_data_on_uninstall" value="1" <?php checked( $jharudar_delete_data ); ?> />
								<?php esc_html_e( 'Delete all plugin settings and activity logs when the plugin is uninstalled.', 'jharudar-for-woocommerce' ); ?>
							</label>
							<p class="description" style="color: #d63638;">
								<?php esc_html_e( 'Warning: This will permanently remove all Jharudar settings and logs. Your WooCommerce data will not be affected.', 'jharudar-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<p class="submit">
				<button type="submit" name="jharudar_save_settings" class="button button-primary">
					<?php esc_html_e( 'Save Settings', 'jharudar-for-woocommerce' ); ?>
				</button>
			</p>
		</form>
	</div>
	<?php
}

// Render the page.
jharudar_render_settings_page();
