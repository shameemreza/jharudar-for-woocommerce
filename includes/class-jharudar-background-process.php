<?php
/**
 * Background Process base class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Background Process class.
 *
 * Handles background processing of cleanup tasks using Action Scheduler.
 *
 * @since 0.0.1
 */
abstract class Jharudar_Background_Process {

	/**
	 * Action name prefix.
	 *
	 * @var string
	 */
	protected $prefix = 'jharudar';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	protected $action = 'background_process';

	/**
	 * Batch size.
	 *
	 * @var int
	 */
	protected $batch_size = 50;

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->batch_size = jharudar_get_batch_size();

		add_action( $this->get_hook_name(), array( $this, 'handle' ) );
	}

	/**
	 * Get the hook name for this process.
	 *
	 * @since 0.0.1
	 * @return string Hook name.
	 */
	protected function get_hook_name() {
		return $this->prefix . '_' . $this->action;
	}

	/**
	 * Dispatch a new batch.
	 *
	 * @since 0.0.1
	 * @param array $data Data to process.
	 * @return int|bool Action ID or false on failure.
	 */
	public function dispatch( $data = array() ) {
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return false;
		}

		// Create a unique batch ID.
		$batch_id = uniqid( $this->prefix . '_batch_', true );

		// Store batch data.
		set_transient( $batch_id, $data, DAY_IN_SECONDS );

		// Schedule the action.
		$action_id = as_enqueue_async_action(
			$this->get_hook_name(),
			array(
				'batch_id' => $batch_id,
			),
			$this->prefix
		);

		return $action_id;
	}

	/**
	 * Schedule a batch for later.
	 *
	 * @since 0.0.1
	 * @param int   $timestamp Timestamp to run.
	 * @param array $data      Data to process.
	 * @return int|bool Action ID or false on failure.
	 */
	public function schedule( $timestamp, $data = array() ) {
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			return false;
		}

		// Create a unique batch ID.
		$batch_id = uniqid( $this->prefix . '_batch_', true );

		// Store batch data.
		set_transient( $batch_id, $data, DAY_IN_SECONDS );

		// Schedule the action.
		$action_id = as_schedule_single_action(
			$timestamp,
			$this->get_hook_name(),
			array(
				'batch_id' => $batch_id,
			),
			$this->prefix
		);

		return $action_id;
	}

	/**
	 * Handle the background process.
	 *
	 * @since 0.0.1
	 * @param string $batch_id Batch ID.
	 * @return void
	 */
	public function handle( $batch_id ) {
		// Get batch data.
		$data = get_transient( $batch_id );

		if ( false === $data ) {
			return;
		}

		// Get items to process.
		$items = isset( $data['items'] ) ? $data['items'] : array();

		if ( empty( $items ) ) {
			// Clean up.
			delete_transient( $batch_id );
			$this->complete( $data );
			return;
		}

		// Process batch.
		$batch = array_splice( $items, 0, $this->batch_size );

		foreach ( $batch as $item ) {
			$this->task( $item, $data );
		}

		// Check if more items remain.
		if ( ! empty( $items ) ) {
			// Update remaining items.
			$data['items'] = $items;
			$data['processed'] = isset( $data['processed'] ) ? $data['processed'] + count( $batch ) : count( $batch );
			set_transient( $batch_id, $data, DAY_IN_SECONDS );

			// Schedule next batch.
			$this->dispatch_next( $batch_id );
		} else {
			// Clean up and complete.
			delete_transient( $batch_id );
			$data['processed'] = isset( $data['processed'] ) ? $data['processed'] + count( $batch ) : count( $batch );
			$this->complete( $data );
		}
	}

	/**
	 * Dispatch the next batch.
	 *
	 * @since 0.0.1
	 * @param string $batch_id Batch ID.
	 * @return int|bool Action ID or false.
	 */
	protected function dispatch_next( $batch_id ) {
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return false;
		}

		return as_enqueue_async_action(
			$this->get_hook_name(),
			array(
				'batch_id' => $batch_id,
			),
			$this->prefix
		);
	}

	/**
	 * Process a single item.
	 *
	 * @since 0.0.1
	 * @param mixed $item Item to process.
	 * @param array $data Full batch data.
	 * @return bool True on success.
	 */
	abstract protected function task( $item, $data );

	/**
	 * Called when process is complete.
	 *
	 * @since 0.0.1
	 * @param array $data Batch data.
	 * @return void
	 */
	protected function complete( $data ) {
		/**
		 * Fires when background process completes.
		 *
		 * @since 0.0.1
		 * @param array  $data   Batch data.
		 * @param string $action Action name.
		 */
		do_action( 'jharudar_background_process_complete', $data, $this->action );

		// Send email notification if enabled.
		if ( jharudar_get_setting( 'email_notifications', false ) ) {
			$this->send_completion_email( $data );
		}
	}

	/**
	 * Send completion email notification.
	 *
	 * @since 0.0.1
	 * @param array $data Batch data.
	 * @return bool True if email sent.
	 */
	protected function send_completion_email( $data ) {
		$email = jharudar_get_setting( 'notification_email', get_option( 'admin_email' ) );

		if ( empty( $email ) ) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s: Site name. */
			__( '[%s] Jharudar Cleanup Complete', 'jharudar-for-woocommerce' ),
			get_bloginfo( 'name' )
		);

		$processed = isset( $data['processed'] ) ? $data['processed'] : 0;
		$type      = isset( $data['type'] ) ? $data['type'] : __( 'items', 'jharudar-for-woocommerce' );

		$message = sprintf(
			/* translators: 1: Number of items, 2: Item type. */
			__( 'Jharudar has finished processing %1$d %2$s.', 'jharudar-for-woocommerce' ),
			$processed,
			$type
		);

		$message .= "\n\n";
		$message .= sprintf(
			/* translators: %s: Admin URL. */
			__( 'View the activity log: %s', 'jharudar-for-woocommerce' ),
			admin_url( 'admin.php?page=jharudar&tab=logs' )
		);

		return wp_mail( $email, $subject, $message );
	}

	/**
	 * Cancel all pending batches for this process.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function cancel() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( $this->get_hook_name() );
		}
	}

	/**
	 * Check if process is running.
	 *
	 * @since 0.0.1
	 * @return bool True if running.
	 */
	public function is_running() {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return false;
		}

		return as_has_scheduled_action( $this->get_hook_name() );
	}

	/**
	 * Get number of pending actions.
	 *
	 * @since 0.0.1
	 * @return int Number of pending actions.
	 */
	public function get_pending_count() {
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return 0;
		}

		$actions = as_get_scheduled_actions(
			array(
				'hook'   => $this->get_hook_name(),
				'status' => \ActionScheduler_Store::STATUS_PENDING,
			),
			'ids'
		);

		return count( $actions );
	}
}
