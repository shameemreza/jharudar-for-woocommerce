<?php
/**
 * Logger class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger class.
 *
 * Handles activity logging for all cleanup operations.
 *
 * @since 0.0.1
 */
class Jharudar_Logger {

	/**
	 * Option name for storing logs.
	 *
	 * @var string
	 */
	private $option_name = 'jharudar_activity_log';

	/**
	 * Maximum number of log entries to keep.
	 *
	 * @var int
	 */
	private $max_entries = 1000;

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		add_action( 'jharudar_daily_maintenance', array( $this, 'cleanup_old_logs' ) );
	}

	/**
	 * Log an activity.
	 *
	 * @since 0.0.1
	 * @param string $action      Action performed.
	 * @param string $object_type Object type (product, order, customer, etc.).
	 * @param int    $object_id   Object ID.
	 * @param array  $meta        Additional metadata.
	 * @return bool True on success.
	 */
	public function log( $action, $object_type = '', $object_id = 0, $meta = array() ) {
		$user = wp_get_current_user();

		$entry = array(
			'id'          => uniqid( 'jharudar_', true ),
			'timestamp'   => current_time( 'mysql' ),
			'user_id'     => $user->ID,
			'user_login'  => $user->user_login,
			'action'      => sanitize_key( $action ),
			'object_type' => sanitize_key( $object_type ),
			'object_id'   => absint( $object_id ),
			'meta'        => $this->sanitize_meta( $meta ),
			'ip_address'  => $this->get_user_ip(),
		);

		$logs   = $this->get_logs();
		$logs[] = $entry;

		// Trim to max entries.
		if ( count( $logs ) > $this->max_entries ) {
			$logs = array_slice( $logs, -$this->max_entries );
		}

		update_option( $this->option_name, $logs, false );

		/**
		 * Fires after an activity is logged.
		 *
		 * @since 0.0.1
		 * @param array $entry The log entry.
		 */
		do_action( 'jharudar_activity_logged', $entry );

		return true;
	}

	/**
	 * Get all logs.
	 *
	 * @since 0.0.1
	 * @param array $args Query arguments.
	 * @return array Logs.
	 */
	public function get_logs( $args = array() ) {
		$defaults = array(
			'action'      => '',
			'object_type' => '',
			'user_id'     => 0,
			'date_from'   => '',
			'date_to'     => '',
			'limit'       => 0,
			'offset'      => 0,
			'order'       => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );
		$logs = get_option( $this->option_name, array() );

		if ( ! is_array( $logs ) ) {
			return array();
		}

		// Apply filters.
		if ( ! empty( $args['action'] ) ) {
			$logs = array_filter(
				$logs,
				function ( $log ) use ( $args ) {
					return $log['action'] === $args['action'];
				}
			);
		}

		if ( ! empty( $args['object_type'] ) ) {
			$logs = array_filter(
				$logs,
				function ( $log ) use ( $args ) {
					return $log['object_type'] === $args['object_type'];
				}
			);
		}

		if ( ! empty( $args['user_id'] ) ) {
			$logs = array_filter(
				$logs,
				function ( $log ) use ( $args ) {
					return (int) $log['user_id'] === (int) $args['user_id'];
				}
			);
		}

		if ( ! empty( $args['date_from'] ) ) {
			$date_from = strtotime( $args['date_from'] );
			$logs      = array_filter(
				$logs,
				function ( $log ) use ( $date_from ) {
					return strtotime( $log['timestamp'] ) >= $date_from;
				}
			);
		}

		if ( ! empty( $args['date_to'] ) ) {
			$date_to = strtotime( $args['date_to'] . ' 23:59:59' );
			$logs    = array_filter(
				$logs,
				function ( $log ) use ( $date_to ) {
					return strtotime( $log['timestamp'] ) <= $date_to;
				}
			);
		}

		// Sort.
		if ( 'ASC' === strtoupper( $args['order'] ) ) {
			usort(
				$logs,
				function ( $a, $b ) {
					return strtotime( $a['timestamp'] ) - strtotime( $b['timestamp'] );
				}
			);
		} else {
			usort(
				$logs,
				function ( $a, $b ) {
					return strtotime( $b['timestamp'] ) - strtotime( $a['timestamp'] );
				}
			);
		}

		// Apply pagination.
		if ( $args['limit'] > 0 ) {
			$logs = array_slice( $logs, $args['offset'], $args['limit'] );
		}

		return array_values( $logs );
	}

	/**
	 * Get a single log entry.
	 *
	 * @since 0.0.1
	 * @param string $log_id Log ID.
	 * @return array|null Log entry or null if not found.
	 */
	public function get_log( $log_id ) {
		$logs = $this->get_logs();

		foreach ( $logs as $log ) {
			if ( $log['id'] === $log_id ) {
				return $log;
			}
		}

		return null;
	}

	/**
	 * Get log count.
	 *
	 * @since 0.0.1
	 * @param array $args Filter arguments.
	 * @return int Log count.
	 */
	public function get_count( $args = array() ) {
		$args['limit'] = 0;
		return count( $this->get_logs( $args ) );
	}

	/**
	 * Delete a log entry.
	 *
	 * @since 0.0.1
	 * @param string $log_id Log ID.
	 * @return bool True on success.
	 */
	public function delete_log( $log_id ) {
		$logs = get_option( $this->option_name, array() );

		if ( ! is_array( $logs ) ) {
			return false;
		}

		$logs = array_filter(
			$logs,
			function ( $log ) use ( $log_id ) {
				return $log['id'] !== $log_id;
			}
		);

		update_option( $this->option_name, array_values( $logs ), false );

		return true;
	}

	/**
	 * Clear all logs.
	 *
	 * @since 0.0.1
	 * @return bool True on success.
	 */
	public function clear_logs() {
		delete_option( $this->option_name );
		return true;
	}

	/**
	 * Cleanup old logs based on retention setting.
	 *
	 * @since 0.0.1
	 * @return int Number of deleted entries.
	 */
	public function cleanup_old_logs() {
		$retention_days = jharudar_get_setting( 'log_retention_days', 30 );
		$cutoff_date    = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		$logs    = get_option( $this->option_name, array() );
		$deleted = 0;

		if ( ! is_array( $logs ) ) {
			return 0;
		}

		$logs = array_filter(
			$logs,
			function ( $log ) use ( $cutoff_date, &$deleted ) {
				if ( strtotime( $log['timestamp'] ) < strtotime( $cutoff_date ) ) {
					$deleted++;
					return false;
				}
				return true;
			}
		);

		if ( $deleted > 0 ) {
			update_option( $this->option_name, array_values( $logs ), false );
		}

		return $deleted;
	}

	/**
	 * Sanitize metadata.
	 *
	 * @since 0.0.1
	 * @param array $meta Metadata to sanitize.
	 * @return array Sanitized metadata.
	 */
	private function sanitize_meta( $meta ) {
		if ( ! is_array( $meta ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $meta as $key => $value ) {
			$key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_meta( $value );
			} elseif ( is_numeric( $value ) ) {
				$sanitized[ $key ] = $value;
			} else {
				$sanitized[ $key ] = sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Get user IP address.
	 *
	 * @since 0.0.1
	 * @return string IP address.
	 */
	private function get_user_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		// Handle multiple IPs (from proxies).
		if ( strpos( $ip, ',' ) !== false ) {
			$ips = explode( ',', $ip );
			$ip  = trim( $ips[0] );
		}

		return $ip;
	}

	/**
	 * Export logs to array.
	 *
	 * @since 0.0.1
	 * @param array $args Filter arguments.
	 * @return array Logs for export.
	 */
	public function export( $args = array() ) {
		return $this->get_logs( $args );
	}

	/**
	 * Get action label.
	 *
	 * @since 0.0.1
	 * @param string $action Action key.
	 * @return string Action label.
	 */
	public static function get_action_label( $action ) {
		$labels = array(
			'delete'          => __( 'Deleted', 'jharudar-for-woocommerce' ),
			'bulk_delete'     => __( 'Bulk Deleted', 'jharudar-for-woocommerce' ),
			'trash'           => __( 'Trashed', 'jharudar-for-woocommerce' ),
			'restore'         => __( 'Restored', 'jharudar-for-woocommerce' ),
			'anonymize'       => __( 'Anonymized', 'jharudar-for-woocommerce' ),
			'export'          => __( 'Exported', 'jharudar-for-woocommerce' ),
			'clean_transient' => __( 'Cleaned Transients', 'jharudar-for-woocommerce' ),
			'clean_orphan'    => __( 'Cleaned Orphaned Data', 'jharudar-for-woocommerce' ),
			'optimize'        => __( 'Optimized', 'jharudar-for-woocommerce' ),
			'schedule'        => __( 'Scheduled', 'jharudar-for-woocommerce' ),
		);

		return isset( $labels[ $action ] ) ? $labels[ $action ] : ucfirst( str_replace( '_', ' ', $action ) );
	}

	/**
	 * Get object type label.
	 *
	 * @since 0.0.1
	 * @param string $object_type Object type key.
	 * @return string Object type label.
	 */
	public static function get_object_type_label( $object_type ) {
		$labels = array(
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

		return isset( $labels[ $object_type ] ) ? $labels[ $object_type ] : ucfirst( str_replace( '_', ' ', $object_type ) );
	}
}
