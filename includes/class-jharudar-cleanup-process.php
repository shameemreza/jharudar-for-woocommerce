<?php
/**
 * Cleanup Process class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cleanup Process class.
 *
 * Handles the actual cleanup operations.
 *
 * @since 0.0.1
 */
class Jharudar_Cleanup_Process extends Jharudar_Background_Process {

	/**
	 * Action name.
	 *
	 * @var string
	 */
	protected $action = 'cleanup_process';

	/**
	 * Process a single item.
	 *
	 * @since 0.0.1
	 * @param mixed $item Item to process.
	 * @param array $data Full batch data.
	 * @return bool True on success.
	 */
	protected function task( $item, $data ) {
		$cleanup_type = isset( $data['cleanup_type'] ) ? $data['cleanup_type'] : '';
		$action       = isset( $data['action'] ) ? $data['action'] : 'delete';

		switch ( $cleanup_type ) {
			case 'product':
				return $this->cleanup_product( $item, $action );

			case 'order':
				return $this->cleanup_order( $item, $action );

			case 'customer':
				return $this->cleanup_customer( $item, $action );

			case 'coupon':
				return $this->cleanup_coupon( $item, $action );

			case 'subscription':
				return $this->cleanup_subscription( $item, $action );

			case 'membership':
				return $this->cleanup_membership( $item, $action );

			case 'booking':
				return $this->cleanup_booking( $item, $action );

			case 'category':
			case 'tag':
			case 'attribute':
				return $this->cleanup_taxonomy( $item, $data );

			default:
				/**
				 * Fires for custom cleanup types.
				 *
				 * @since 0.0.1
				 * @param mixed  $item         Item to process.
				 * @param array  $data         Full batch data.
				 * @param string $cleanup_type Cleanup type.
				 */
				do_action( 'jharudar_cleanup_item', $item, $data, $cleanup_type );
				return true;
		}
	}

	/**
	 * Cleanup a product.
	 *
	 * @since 0.0.1
	 * @param int    $product_id Product ID.
	 * @param string $action     Action to perform.
	 * @return bool True on success.
	 */
	protected function cleanup_product( $product_id, $action ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return false;
		}

		// Log the activity.
		jharudar_log_activity( $action, 'product', $product_id );

		if ( 'trash' === $action ) {
			$product->set_status( 'trash' );
			$product->save();
		} else {
			// Delete product and its data.
			$product->delete( true );
		}

		return true;
	}

	/**
	 * Cleanup an order.
	 *
	 * @since 0.0.1
	 * @param int    $order_id Order ID.
	 * @param string $action   Action to perform.
	 * @return bool True on success.
	 */
	protected function cleanup_order( $order_id, $action ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		// Log the activity.
		jharudar_log_activity( $action, 'order', $order_id );

		if ( 'anonymize' === $action ) {
			$this->anonymize_order( $order );
		} elseif ( 'trash' === $action ) {
			$order->set_status( 'trash' );
			$order->save();
		} else {
			$order->delete( true );
		}

		return true;
	}

	/**
	 * Anonymize an order.
	 *
	 * @since 0.0.1
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	protected function anonymize_order( $order ) {
		$anonymized_data = array(
			'billing_first_name'  => __( 'Anonymized', 'jharudar-for-woocommerce' ),
			'billing_last_name'   => '',
			'billing_company'     => '',
			'billing_address_1'   => '',
			'billing_address_2'   => '',
			'billing_city'        => '',
			'billing_postcode'    => '',
			'billing_state'       => '',
			'billing_country'     => '',
			'billing_email'       => 'anonymized@example.com',
			'billing_phone'       => '',
			'shipping_first_name' => __( 'Anonymized', 'jharudar-for-woocommerce' ),
			'shipping_last_name'  => '',
			'shipping_company'    => '',
			'shipping_address_1'  => '',
			'shipping_address_2'  => '',
			'shipping_city'       => '',
			'shipping_postcode'   => '',
			'shipping_state'      => '',
			'shipping_country'    => '',
		);

		foreach ( $anonymized_data as $key => $value ) {
			$method = 'set_' . $key;
			if ( method_exists( $order, $method ) ) {
				$order->$method( $value );
			}
		}

		// Remove customer IP.
		$order->set_customer_ip_address( '' );
		$order->set_customer_user_agent( '' );

		// Add note about anonymization.
		$order->add_order_note( __( 'Order anonymized by Jharudar.', 'jharudar-for-woocommerce' ) );

		$order->save();
	}

	/**
	 * Cleanup a customer.
	 *
	 * @since 0.0.1
	 * @param int    $customer_id Customer ID.
	 * @param string $action      Action to perform.
	 * @return bool True on success.
	 */
	protected function cleanup_customer( $customer_id, $action ) {
		$user = get_user_by( 'id', $customer_id );

		if ( ! $user ) {
			return false;
		}

		// Log the activity.
		jharudar_log_activity( $action, 'customer', $customer_id );

		if ( 'anonymize' === $action ) {
			$this->anonymize_customer( $customer_id );
		} else {
			// Reassign posts to admin.
			$admin_id = 1;
			wp_delete_user( $customer_id, $admin_id );
		}

		return true;
	}

	/**
	 * Anonymize a customer.
	 *
	 * @since 0.0.1
	 * @param int $customer_id Customer ID.
	 * @return void
	 */
	protected function anonymize_customer( $customer_id ) {
		$anonymous_email = 'anonymized-' . $customer_id . '@example.com';

		wp_update_user(
			array(
				'ID'           => $customer_id,
				'user_email'   => $anonymous_email,
				'display_name' => __( 'Anonymized User', 'jharudar-for-woocommerce' ),
				'first_name'   => '',
				'last_name'    => '',
			)
		);

		// Clear customer meta.
		$meta_keys = array(
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_postcode',
			'billing_state',
			'billing_country',
			'billing_phone',
			'billing_email',
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_postcode',
			'shipping_state',
			'shipping_country',
		);

		foreach ( $meta_keys as $key ) {
			delete_user_meta( $customer_id, $key );
		}
	}

	/**
	 * Cleanup a coupon.
	 *
	 * @since 0.0.1
	 * @param int    $coupon_id Coupon ID.
	 * @param string $action    Action to perform.
	 * @return bool True on success.
	 */
	protected function cleanup_coupon( $coupon_id, $action ) {
		$coupon = new WC_Coupon( $coupon_id );

		if ( ! $coupon->get_id() ) {
			return false;
		}

		// Log the activity.
		jharudar_log_activity( $action, 'coupon', $coupon_id );

		if ( 'trash' === $action ) {
			wp_trash_post( $coupon_id );
		} else {
			$coupon->delete( true );
		}

		return true;
	}

	/**
	 * Cleanup a subscription.
	 *
	 * @since 0.0.1
	 * @param int    $subscription_id Subscription ID.
	 * @param string $action          Action to perform.
	 * @return bool True on success.
	 */
	protected function cleanup_subscription( $subscription_id, $action ) {
		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return false;
		}

		$subscription = wcs_get_subscription( $subscription_id );

		if ( ! $subscription ) {
			return false;
		}

		// Log the activity.
		jharudar_log_activity( $action, 'subscription', $subscription_id );

		if ( 'trash' === $action ) {
			$subscription->set_status( 'trash' );
			$subscription->save();
		} else {
			$subscription->delete( true );
		}

		return true;
	}

	/**
	 * Cleanup a membership.
	 *
	 * @since 0.0.1
	 * @param int    $membership_id Membership ID.
	 * @param string $action        Action to perform.
	 * @return bool True on success.
	 */
	protected function cleanup_membership( $membership_id, $action ) {
		if ( ! function_exists( 'wc_memberships_get_user_membership' ) ) {
			return false;
		}

		$membership = wc_memberships_get_user_membership( $membership_id );

		if ( ! $membership ) {
			return false;
		}

		// Log the activity.
		jharudar_log_activity( $action, 'membership', $membership_id );

		if ( 'trash' === $action ) {
			wp_trash_post( $membership_id );
		} else {
			wp_delete_post( $membership_id, true );
		}

		return true;
	}

	/**
	 * Cleanup a booking.
	 *
	 * @since 0.0.1
	 * @param int    $booking_id Booking ID.
	 * @param string $action     Action to perform.
	 * @return bool True on success.
	 */
	protected function cleanup_booking( $booking_id, $action ) {
		if ( ! function_exists( 'get_wc_booking' ) ) {
			return false;
		}

		$booking = get_wc_booking( $booking_id );

		if ( ! $booking ) {
			return false;
		}

		// Log the activity.
		jharudar_log_activity( $action, 'booking', $booking_id );

		if ( 'trash' === $action ) {
			wp_trash_post( $booking_id );
		} else {
			wp_delete_post( $booking_id, true );
		}

		return true;
	}

	/**
	 * Cleanup a taxonomy term.
	 *
	 * @since 0.0.1
	 * @param int   $term_id Term ID.
	 * @param array $data    Batch data.
	 * @return bool True on success.
	 */
	protected function cleanup_taxonomy( $term_id, $data ) {
		$taxonomy = isset( $data['taxonomy'] ) ? $data['taxonomy'] : '';

		if ( empty( $taxonomy ) ) {
			return false;
		}

		// Log the activity.
		jharudar_log_activity( 'delete', $data['cleanup_type'], $term_id );

		$result = wp_delete_term( $term_id, $taxonomy );

		return ! is_wp_error( $result );
	}

	/**
	 * Called when process is complete.
	 *
	 * @since 0.0.1
	 * @param array $data Batch data.
	 * @return void
	 */
	protected function complete( $data ) {
		$cleanup_type = isset( $data['cleanup_type'] ) ? $data['cleanup_type'] : 'items';
		$processed    = isset( $data['processed'] ) ? $data['processed'] : 0;

		// Log bulk completion.
		jharudar_log_activity(
			'bulk_delete',
			$cleanup_type,
			0,
			array(
				'count' => $processed,
			)
		);

		parent::complete( $data );
	}
}
