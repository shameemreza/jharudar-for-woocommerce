<?php
/**
 * Autoloader for Jharudar classes.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloader class.
 *
 * Handles automatic loading of plugin classes based on naming conventions.
 *
 * @since 0.0.1
 */
class Jharudar_Autoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private static $include_path = '';

	/**
	 * Initialize the autoloader.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public static function init() {
		if ( '' === self::$include_path ) {
			self::$include_path = JHARUDAR_PLUGIN_DIR . 'includes/';
		}

		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload classes.
	 *
	 * @since 0.0.1
	 * @param string $class_name The class name to load.
	 * @return void
	 */
	public static function autoload( $class_name ) {
		// Only autoload Jharudar classes.
		if ( 0 !== strpos( $class_name, 'Jharudar' ) ) {
			return;
		}

		$file = self::get_file_path( $class_name );

		if ( $file && is_readable( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * Get the file path for a class.
	 *
	 * @since 0.0.1
	 * @param string $class_name The class name.
	 * @return string|false The file path or false if not found.
	 */
	private static function get_file_path( $class_name ) {
		// Convert class name to file name.
		$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

		// Define possible locations.
		$locations = array(
			self::$include_path,
			self::$include_path . 'admin/',
			self::$include_path . 'modules/core/',
			self::$include_path . 'modules/extensions/',
			self::$include_path . 'modules/store/',
			self::$include_path . 'modules/database/',
			self::$include_path . 'modules/wordpress/',
			self::$include_path . 'background/',
			self::$include_path . 'export/',
			self::$include_path . 'gdpr/',
			self::$include_path . 'automation/',
			self::$include_path . 'tools/',
			self::$include_path . 'cli/',
			self::$include_path . 'api/',
			self::$include_path . 'logging/',
		);

		// Check each location.
		foreach ( $locations as $location ) {
			$file = $location . $file_name;
			if ( file_exists( $file ) ) {
				return $file;
			}
		}

		return false;
	}
}
