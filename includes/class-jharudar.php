<?php
/**
 * Main Jharudar class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Jharudar class.
 *
 * The main plugin class that initializes all components.
 *
 * @since 0.0.1
 */
final class Jharudar {

	/**
	 * Single instance of the class.
	 *
	 * @var Jharudar|null
	 */
	private static $instance = null;

	/**
	 * Admin instance.
	 *
	 * @var Jharudar_Admin|null
	 */
	public $admin = null;

	/**
	 * Products module instance.
	 *
	 * @var Jharudar_Products|null
	 */
	public $products = null;

	/**
	 * Orders module instance.
	 *
	 * @var Jharudar_Orders|null
	 */
	public $orders = null;

	/**
	 * Customers module instance.
	 *
	 * @var Jharudar_Customers|null
	 */
	public $customers = null;

	/**
	 * Automation instance.
	 *
	 * @var Jharudar_Automation|null
	 */
	public $automation = null;

	/**
	 * Coupons module instance.
	 *
	 * @var Jharudar_Coupons|null
	 */
	public $coupons = null;

	/**
	 * Taxonomy module instance.
	 *
	 * @var Jharudar_Taxonomy|null
	 */
	public $taxonomy = null;

	/**
	 * Tax Rates module instance.
	 *
	 * @var Jharudar_Tax_Rates|null
	 */
	public $tax_rates = null;

	/**
	 * Shipping module instance.
	 *
	 * @var Jharudar_Shipping|null
	 */
	public $shipping = null;

	/**
	 * Database module instance.
	 *
	 * @var Jharudar_Database|null
	 */
	public $database = null;

	/**
	 * Subscriptions module instance.
	 *
	 * @var Jharudar_Subscriptions|null
	 */
	public $subscriptions = null;

	/**
	 * Memberships module instance.
	 *
	 * @var Jharudar_Memberships|null
	 */
	public $memberships = null;

	/**
	 * Bookings module instance.
	 *
	 * @var Jharudar_Bookings|null
	 */
	public $bookings = null;

	/**
	 * Appointments module instance.
	 *
	 * @var Jharudar_Appointments|null
	 */
	public $appointments = null;

	/**
	 * Vendors module instance.
	 *
	 * @var Jharudar_Vendors|null
	 */
	public $vendors = null;

	/**
	 * Get the single instance of the class.
	 *
	 * @since 0.0.1
	 * @return Jharudar
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Private to prevent direct instantiation.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is not allowed.', 'jharudar-for-woocommerce' ), '0.0.1' );
	}

	/**
	 * Prevent unserializing.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing is not allowed.', 'jharudar-for-woocommerce' ), '0.0.1' );
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function init() {
		// Initialize modules.
		$this->init_modules();

		// Initialize admin.
		if ( is_admin() ) {
			$this->admin = new Jharudar_Admin();
		}

		/**
		 * Fires after Jharudar has been initialized.
		 *
		 * @since 0.0.1
		 */
		do_action( 'jharudar_init' );
	}

	/**
	 * Initialize modules.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private function init_modules() {
		// Core modules.
		$this->products  = new Jharudar_Products();
		$this->orders    = new Jharudar_Orders();
		$this->customers = new Jharudar_Customers();
		$this->coupons   = new Jharudar_Coupons();
		$this->taxonomy  = new Jharudar_Taxonomy();
		$this->tax_rates = new Jharudar_Tax_Rates();
		$this->shipping  = new Jharudar_Shipping();

		// Database module.
		$this->database = new Jharudar_Database();

		// Extension modules (conditionally loaded).
		$this->subscriptions = new Jharudar_Subscriptions();
		$this->memberships   = new Jharudar_Memberships();
		$this->bookings      = new Jharudar_Bookings();
		$this->appointments  = new Jharudar_Appointments();
		$this->vendors       = new Jharudar_Vendors();

		// Cache helper (registers AJAX handler and auto-clear hooks).
		Jharudar_Cache::init();

		// Automation hooks.
		$this->automation = new Jharudar_Automation();

		/**
		 * Fires after Jharudar modules have been initialized.
		 *
		 * @since 0.0.1
		 */
		do_action( 'jharudar_modules_init' );
	}

	/**
	 * Admin initialization.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function admin_init() {
		// Check version and run upgrades if needed.
		$this->maybe_upgrade();

		/**
		 * Fires after Jharudar admin has been initialized.
		 *
		 * @since 0.0.1
		 */
		do_action( 'jharudar_admin_init' );
	}

	/**
	 * Check version and run upgrades if needed.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private function maybe_upgrade() {
		$current_version = get_option( 'jharudar_version', '' );

		if ( version_compare( $current_version, JHARUDAR_VERSION, '<' ) ) {
			$this->upgrade( $current_version );
			update_option( 'jharudar_version', JHARUDAR_VERSION );
		}
	}

	/**
	 * Run upgrade routines.
	 *
	 * @since 0.0.1
	 * @param string $from_version The version upgrading from.
	 * @return void
	 */
	private function upgrade( $from_version ) {
		/**
		 * Fires when Jharudar is upgraded.
		 *
		 * @since 0.0.1
		 * @param string $from_version The version being upgraded from.
		 * @param string $to_version   The version being upgraded to.
		 */
		do_action( 'jharudar_upgrade', $from_version, JHARUDAR_VERSION );
	}

	/**
	 * Get plugin settings.
	 *
	 * @since 0.0.1
	 * @param string $key           Optional. Setting key to retrieve.
	 * @param mixed  $default_value Optional. Default value if setting not found.
	 * @return mixed Setting value or all settings.
	 */
	public function get_setting( $key = '', $default_value = null ) {
		$settings = get_option( 'jharudar_settings', array() );

		if ( '' === $key ) {
			return $settings;
		}

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default_value;
	}

	/**
	 * Update plugin setting.
	 *
	 * @since 0.0.1
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool True if updated, false otherwise.
	 */
	public function update_setting( $key, $value ) {
		$settings         = get_option( 'jharudar_settings', array() );
		$settings[ $key ] = $value;
		return update_option( 'jharudar_settings', $settings );
	}

	/**
	 * Check if a WooCommerce extension is active.
	 *
	 * @since 0.0.1
	 * @param string $extension Extension name (subscriptions, memberships, bookings, appointments, vendors).
	 * @return bool True if active, false otherwise.
	 */
	public function is_extension_active( $extension ) {
		$extensions = array(
			'subscriptions' => 'WC_Subscriptions',
			'memberships'   => 'wc_memberships',
			'bookings'      => 'WC_Bookings',
			'appointments'  => 'WC_Appointments',
			'vendors'       => 'WC_Product_Vendors',
		);

		if ( ! isset( $extensions[ $extension ] ) ) {
			return false;
		}

		$check = $extensions[ $extension ];

		// Check for class or function.
		if ( 'wc_memberships' === $check ) {
			return function_exists( $check );
		}

		return class_exists( $check );
	}

	/**
	 * Get the plugin URL.
	 *
	 * @since 0.0.1
	 * @param string $path Optional. Path relative to plugin URL.
	 * @return string Plugin URL.
	 */
	public function plugin_url( $path = '' ) {
		return JHARUDAR_PLUGIN_URL . ltrim( $path, '/' );
	}

	/**
	 * Get the plugin path.
	 *
	 * @since 0.0.1
	 * @param string $path Optional. Path relative to plugin directory.
	 * @return string Plugin path.
	 */
	public function plugin_path( $path = '' ) {
		return JHARUDAR_PLUGIN_DIR . ltrim( $path, '/' );
	}
}
