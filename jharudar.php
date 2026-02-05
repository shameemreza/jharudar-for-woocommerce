<?php
/**
 * Jharudar for WooCommerce
 *
 * A comprehensive data management and cleanup solution for WooCommerce stores.
 * Clean products, orders, customers, and more with safety features and GDPR compliance.
 *
 * @package           Jharudar
 * @author            Shameem Reza
 * @copyright         2026 Shameem Reza
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Jharudar for WooCommerce
 * Plugin URI:        https://github.com/shameemreza/jharudar-for-woocommerce
 * Description:       The complete store cleanup toolkit for WooCommerce. Safely clean products, orders, customers, subscriptions, memberships, bookings, and optimize your database with GDPR compliance.
 * Version:           0.0.1
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Shameem Reza
 * Author URI:        https://shameem.dev
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       jharudar-for-woocommerce
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 * WC requires at least: 8.0
 * WC tested up to:      10.4.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @var string
 */
define( 'JHARUDAR_VERSION', '0.0.1' );

/**
 * Plugin file path.
 *
 * @var string
 */
define( 'JHARUDAR_PLUGIN_FILE', __FILE__ );

/**
 * Plugin directory path.
 *
 * @var string
 */
define( 'JHARUDAR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 *
 * @var string
 */
define( 'JHARUDAR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 *
 * @var string
 */
define( 'JHARUDAR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum PHP version required.
 *
 * @var string
 */
define( 'JHARUDAR_MIN_PHP_VERSION', '8.0' );

/**
 * Minimum WordPress version required.
 *
 * @var string
 */
define( 'JHARUDAR_MIN_WP_VERSION', '6.4' );

/**
 * Minimum WooCommerce version required.
 *
 * @var string
 */
define( 'JHARUDAR_MIN_WC_VERSION', '8.0' );

/**
 * Check if the minimum requirements are met.
 *
 * @since 0.0.1
 * @return bool True if requirements are met, false otherwise.
 */
function jharudar_check_requirements() {
	$meets_requirements = true;

	// Check PHP version.
	if ( version_compare( PHP_VERSION, JHARUDAR_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'jharudar_php_version_notice' );
		$meets_requirements = false;
	}

	// Check WordPress version.
	if ( version_compare( get_bloginfo( 'version' ), JHARUDAR_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'jharudar_wp_version_notice' );
		$meets_requirements = false;
	}

	// Check if WooCommerce is active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'jharudar_wc_missing_notice' );
		$meets_requirements = false;
	} elseif ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, JHARUDAR_MIN_WC_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'jharudar_wc_version_notice' );
		$meets_requirements = false;
	}

	return $meets_requirements;
}

/**
 * Display PHP version notice.
 *
 * @since 0.0.1
 * @return void
 */
function jharudar_php_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required PHP version, 2: Current PHP version. */
				esc_html__( 'Jharudar for WooCommerce requires PHP version %1$s or higher. Your current PHP version is %2$s. Please upgrade PHP to use this plugin.', 'jharudar-for-woocommerce' ),
				esc_html( JHARUDAR_MIN_PHP_VERSION ),
				esc_html( PHP_VERSION )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Display WordPress version notice.
 *
 * @since 0.0.1
 * @return void
 */
function jharudar_wp_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required WordPress version, 2: Current WordPress version. */
				esc_html__( 'Jharudar for WooCommerce requires WordPress version %1$s or higher. Your current WordPress version is %2$s. Please upgrade WordPress to use this plugin.', 'jharudar-for-woocommerce' ),
				esc_html( JHARUDAR_MIN_WP_VERSION ),
				esc_html( get_bloginfo( 'version' ) )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Display WooCommerce missing notice.
 *
 * @since 0.0.1
 * @return void
 */
function jharudar_wc_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			esc_html_e( 'Jharudar for WooCommerce requires WooCommerce to be installed and activated. Please install and activate WooCommerce to use this plugin.', 'jharudar-for-woocommerce' );
			?>
		</p>
	</div>
	<?php
}

/**
 * Display WooCommerce version notice.
 *
 * @since 0.0.1
 * @return void
 */
function jharudar_wc_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required WooCommerce version, 2: Current WooCommerce version. */
				esc_html__( 'Jharudar for WooCommerce requires WooCommerce version %1$s or higher. Your current WooCommerce version is %2$s. Please upgrade WooCommerce to use this plugin.', 'jharudar-for-woocommerce' ),
				esc_html( JHARUDAR_MIN_WC_VERSION ),
				esc_html( WC_VERSION )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Initialize the plugin.
 *
 * @since 0.0.1
 * @return void
 */
function jharudar_init() {
	// Check requirements before initializing.
	if ( ! jharudar_check_requirements() ) {
		return;
	}

	// Load helper functions.
	require_once JHARUDAR_PLUGIN_DIR . 'includes/jharudar-functions.php';

	// Load the autoloader.
	require_once JHARUDAR_PLUGIN_DIR . 'includes/class-jharudar-autoloader.php';

	// Initialize the autoloader.
	Jharudar_Autoloader::init();

	// Initialize the main plugin class.
	Jharudar::instance();
}
add_action( 'plugins_loaded', 'jharudar_init' );

/**
 * Run activation tasks.
 *
 * @since 0.0.1
 * @return void
 */
function jharudar_activate() {
	require_once JHARUDAR_PLUGIN_DIR . 'includes/class-jharudar-activator.php';
	Jharudar_Activator::activate();
}
register_activation_hook( __FILE__, 'jharudar_activate' );

/**
 * Run deactivation tasks.
 *
 * @since 0.0.1
 * @return void
 */
function jharudar_deactivate() {
	require_once JHARUDAR_PLUGIN_DIR . 'includes/class-jharudar-deactivator.php';
	Jharudar_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'jharudar_deactivate' );

/**
 * Declare compatibility with WooCommerce features.
 *
 * @since 0.0.1
 * @return void
 */
function jharudar_declare_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		// Declare HPOS compatibility.
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);

		// Declare Cart and Checkout Blocks compatibility.
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'cart_checkout_blocks',
			__FILE__,
			true
		);
	}
}
add_action( 'before_woocommerce_init', 'jharudar_declare_compatibility' );
