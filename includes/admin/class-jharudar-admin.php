<?php
/**
 * Admin class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 *
 * Handles all admin functionality.
 *
 * @since 0.0.1
 */
class Jharudar_Admin {

	/**
	 * Admin page hook suffix.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_notices', array( $this, 'activation_notice' ) );
		add_filter( 'plugin_action_links_' . JHARUDAR_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Add admin menu.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function add_menu() {
		// Add as submenu under WooCommerce.
		$this->hook_suffix = add_submenu_page(
			'woocommerce',
			__( 'Jharudar', 'jharudar-for-woocommerce' ),
			__( 'Jharudar', 'jharudar-for-woocommerce' ),
			'manage_woocommerce',
			'jharudar',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Get custom menu icon SVG.
	 *
	 * A broom/sweeper icon representing the cleanup functionality.
	 * This can be used if we decide to add a top-level menu in the future.
	 *
	 * @since 0.0.1
	 * @return string Base64 encoded SVG icon.
	 */
	public function get_menu_icon() {
		// Broom/sweeper icon SVG.
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L12 8"/><path d="M4.93 10.93L8.5 14.5"/><path d="M2 18L6 22"/><path d="M6 18L2 22"/><path d="M18 22L22 18"/><path d="M22 22L18 18"/><path d="M4 14L12 6L20 14"/><path d="M5 19L19 19"/><path d="M12 14L12 19"/></svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 0.0.1
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		// Only load on our admin pages.
		if ( $this->hook_suffix !== $hook_suffix ) {
			return;
		}

		// Enqueue WooCommerce admin styles.
		wp_enqueue_style( 'woocommerce_admin_styles' );

		// Enqueue SelectWoo.
		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_style( 'select2' );

		// Enqueue our admin styles.
		wp_enqueue_style(
			'jharudar-admin',
			JHARUDAR_PLUGIN_URL . 'assets/css/admin.css',
			array( 'woocommerce_admin_styles' ),
			JHARUDAR_VERSION
		);

		// Enqueue our admin scripts.
		wp_enqueue_script(
			'jharudar-admin',
			JHARUDAR_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'selectWoo', 'wp-util' ),
			JHARUDAR_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// Localize script.
		wp_localize_script(
			'jharudar-admin',
			'jharudar_admin',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'jharudar_admin_nonce' ),
				'i18n'                 => array(
					'confirm_delete'    => __( 'Are you sure you want to delete the selected items? This action cannot be undone.', 'jharudar-for-woocommerce' ),
					'processing'        => __( 'Processing...', 'jharudar-for-woocommerce' ),
					'complete'          => __( 'Complete', 'jharudar-for-woocommerce' ),
					'error'             => __( 'An error occurred. Please try again.', 'jharudar-for-woocommerce' ),
					'no_items_selected' => __( 'Please select at least one item.', 'jharudar-for-woocommerce' ),
					'type_delete'       => __( 'Type DELETE to confirm', 'jharudar-for-woocommerce' ),
				),
				'require_confirmation' => jharudar()->get_setting( 'require_confirmation', true ),
			)
		);
	}

	/**
	 * Display activation notice.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function activation_notice() {
		if ( ! get_transient( 'jharudar_activated' ) ) {
			return;
		}

		delete_transient( 'jharudar_activated' );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %s: Link to Jharudar settings page. */
					esc_html__( 'Thank you for installing Jharudar for WooCommerce. %s to get started.', 'jharudar-for-woocommerce' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=jharudar' ) ) . '">' . esc_html__( 'Visit the dashboard', 'jharudar-for-woocommerce' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add plugin action links.
	 *
	 * @since 0.0.1
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=jharudar' ) ) . '">' . esc_html__( 'Dashboard', 'jharudar-for-woocommerce' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=jharudar&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'jharudar-for-woocommerce' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add plugin row meta links.
	 *
	 * @since 0.0.1
	 * @param array  $links Plugin meta links.
	 * @param string $file  Plugin file.
	 * @return array Modified meta links.
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( JHARUDAR_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		$row_meta = array(
			'docs'    => '<a href="' . esc_url( 'https://wordpress.org/plugins/jharudar-for-woocommerce/#description' ) . '" aria-label="' . esc_attr__( 'View documentation', 'jharudar-for-woocommerce' ) . '">' . esc_html__( 'Docs', 'jharudar-for-woocommerce' ) . '</a>',
			'support' => '<a href="' . esc_url( 'https://wordpress.org/support/plugin/jharudar-for-woocommerce/' ) . '" aria-label="' . esc_attr__( 'Get support', 'jharudar-for-woocommerce' ) . '">' . esc_html__( 'Support', 'jharudar-for-woocommerce' ) . '</a>',
		);

		return array_merge( $links, $row_meta );
	}

	/**
	 * Render the admin page.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function render_page() {
		// Get current tab.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Define tabs.
		$tabs = $this->get_tabs();

		// Validate tab.
		if ( ! isset( $tabs[ $current_tab ] ) ) {
			$current_tab = 'dashboard';
		}

		?>
		<div class="wrap woocommerce jharudar-wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<hr class="wp-header-end">

			<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
				<?php
				foreach ( $tabs as $tab_id => $tab ) {
					$url   = add_query_arg( 'tab', $tab_id, admin_url( 'admin.php?page=jharudar' ) );
					$class = $current_tab === $tab_id ? 'nav-tab nav-tab-active' : 'nav-tab';
					printf(
						'<a href="%s" class="%s">%s</a>',
						esc_url( $url ),
						esc_attr( $class ),
						esc_html( $tab['label'] )
					);
				}
				?>
			</nav>

			<div class="jharudar-content">
				<?php
				$this->render_tab_content( $current_tab );
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get admin tabs.
	 *
	 * @since 0.0.1
	 * @return array Array of tabs.
	 */
	private function get_tabs() {
		$tabs = array(
			'dashboard' => array(
				'label' => __( 'Dashboard', 'jharudar-for-woocommerce' ),
			),
			'products'  => array(
				'label' => __( 'Products', 'jharudar-for-woocommerce' ),
			),
			'orders'    => array(
				'label' => __( 'Orders', 'jharudar-for-woocommerce' ),
			),
			'customers' => array(
				'label' => __( 'Customers', 'jharudar-for-woocommerce' ),
			),
			'coupons'   => array(
				'label' => __( 'Coupons', 'jharudar-for-woocommerce' ),
			),
			'taxonomy'  => array(
				'label' => __( 'Taxonomy', 'jharudar-for-woocommerce' ),
			),
		);

		// Extensions tab (always visible; shows detection status or active sub-tabs).
		$tabs['extensions'] = array(
			'label' => __( 'Extensions', 'jharudar-for-woocommerce' ),
		);

		// Add remaining tabs.
		$tabs['store']    = array(
			'label' => __( 'Store Data', 'jharudar-for-woocommerce' ),
		);
		$tabs['database'] = array(
			'label' => __( 'Database', 'jharudar-for-woocommerce' ),
		);
		$tabs['gdpr']     = array(
			'label' => __( 'GDPR', 'jharudar-for-woocommerce' ),
		);
		$tabs['logs']     = array(
			'label' => __( 'Activity Log', 'jharudar-for-woocommerce' ),
		);
		$tabs['settings'] = array(
			'label' => __( 'Settings', 'jharudar-for-woocommerce' ),
		);

		/**
		 * Filters the admin tabs.
		 *
		 * @since 0.0.1
		 * @param array $tabs Array of tabs.
		 */
		return apply_filters( 'jharudar_admin_tabs', $tabs );
	}

	/**
	 * Render tab content.
	 *
	 * @since 0.0.1
	 * @param string $tab Current tab.
	 * @return void
	 */
	private function render_tab_content( $tab ) {
		$view_file = JHARUDAR_PLUGIN_DIR . 'includes/admin/views/html-admin-' . $tab . '.php';

		if ( file_exists( $view_file ) ) {
			include $view_file;
		} else {
			$this->render_placeholder_content( $tab );
		}
	}

	/**
	 * Render placeholder content for tabs not yet implemented.
	 *
	 * @since 0.0.1
	 * @param string $tab Current tab.
	 * @return void
	 */
	private function render_placeholder_content( $tab ) {
		$tabs  = $this->get_tabs();
		$label = isset( $tabs[ $tab ]['label'] ) ? $tabs[ $tab ]['label'] : ucfirst( $tab );
		?>
		<div class="jharudar-placeholder">
			<div class="jharudar-placeholder-content">
				<span class="dashicons dashicons-admin-tools"></span>
				<h2><?php echo esc_html( $label ); ?></h2>
				<p><?php esc_html_e( 'This module is coming soon. Stay tuned for updates.', 'jharudar-for-woocommerce' ); ?></p>
			</div>
		</div>
		<?php
	}
}
