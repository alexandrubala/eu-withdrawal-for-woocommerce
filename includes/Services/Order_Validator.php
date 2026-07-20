<?php
/**
 * Validates WooCommerce orders for the withdrawal / return window.
 *
 * @package EUWithdrawal\Services
 */

namespace EUWithdrawal\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Validator
 */
final class Order_Validator {

	/**
	 * Validate order number and billing email combination within the return window.
	 *
	 * @param string $order_number Order number entered by the customer.
	 * @param string $email        Customer email address.
	 * @return \WC_Order|null Matching order object or null when invalid.
	 */
	public function validate( string $order_number, string $email ): ?\WC_Order {
		$order_number = trim( $order_number );
		$email        = strtolower( trim( $email ) );

		if ( '' === $order_number || '' === $email ) {
			return null;
		}

		$order = $this->find_order_by_number( $order_number );

		if ( ! $order instanceof \WC_Order ) {
			return null;
		}

		$billing_email = strtolower( trim( (string) $order->get_billing_email() ) );

		if ( $billing_email !== $email ) {
			return null;
		}

		if ( ! $this->is_within_return_window( $order ) ) {
			return null;
		}

		return $order;
	}

	/**
	 * Validate that a logged-in customer owns the order and it is eligible.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @param int $user_id  WordPress user ID.
	 * @return \WC_Order|null
	 */
	public function validate_for_customer( int $order_id, int $user_id ): ?\WC_Order {
		if ( $order_id <= 0 || $user_id <= 0 ) {
			return null;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return null;
		}

		if ( (int) $order->get_customer_id() !== $user_id ) {
			return null;
		}

		if ( ! $this->is_within_return_window( $order ) ) {
			return null;
		}

		return $order;
	}

	/**
	 * Eligible orders for a logged-in customer (within return window).
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<int, \WC_Order>
	 */
	public function get_eligible_orders_for_customer( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return array();
		}

		$order_ids = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => 50,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'return'      => 'ids',
				'status'      => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
			)
		);

		$eligible = array();

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( (int) $order_id );

			if ( $order instanceof \WC_Order && $this->is_within_return_window( $order ) ) {
				$eligible[] = $order;
			}
		}

		return $eligible;
	}

	/**
	 * Whether the order is still inside the configured return / withdrawal window.
	 *
	 * Uses date_completed when available, otherwise date_created.
	 *
	 * @param \WC_Order $order WooCommerce order.
	 * @return bool
	 */
	public function is_within_return_window( \WC_Order $order ): bool {
		$days = Settings::return_days();
		$date = $order->get_date_completed();

		if ( ! $date ) {
			$date = $order->get_date_created();
		}

		if ( ! $date ) {
			return false;
		}

		$order_ts  = $date->getTimestamp();
		$deadline  = $order_ts + ( $days * DAY_IN_SECONDS );
		$now       = current_time( 'timestamp' );

		return $now <= $deadline;
	}

	/**
	 * Days remaining in the return window (0 when expired).
	 *
	 * @param \WC_Order $order WooCommerce order.
	 * @return int
	 */
	public function days_remaining( \WC_Order $order ): int {
		$days = Settings::return_days();
		$date = $order->get_date_completed() ?: $order->get_date_created();

		if ( ! $date ) {
			return 0;
		}

		$deadline = $date->getTimestamp() + ( $days * DAY_IN_SECONDS );
		$remaining = (int) ceil( ( $deadline - current_time( 'timestamp' ) ) / DAY_IN_SECONDS );

		return max( 0, $remaining );
	}

	/**
	 * Locate an order by its display number (HPOS-compatible).
	 *
	 * @param string $order_number Order number to search for.
	 * @return \WC_Order|null
	 */
	private function find_order_by_number( string $order_number ): ?\WC_Order {
		if ( is_numeric( $order_number ) ) {
			$candidate = wc_get_order( absint( $order_number ) );

			if ( $candidate instanceof \WC_Order && $this->order_number_matches( $candidate, $order_number ) ) {
				return $candidate;
			}
		}

		$order_ids = wc_get_orders(
			array(
				'limit'  => 5,
				'return' => 'ids',
				'status' => array_keys( wc_get_order_statuses() ),
				's'      => $order_number,
			)
		);

		foreach ( $order_ids as $order_id ) {
			$candidate = wc_get_order( $order_id );

			if ( $candidate instanceof \WC_Order && $this->order_number_matches( $candidate, $order_number ) ) {
				return $candidate;
			}
		}

		return null;
	}

	/**
	 * Check whether the order's public number matches the user input.
	 *
	 * @param \WC_Order $order        WooCommerce order.
	 * @param string    $order_number Expected order number.
	 * @return bool
	 */
	private function order_number_matches( \WC_Order $order, string $order_number ): bool {
		$display_number = (string) $order->get_order_number();

		return $display_number === $order_number || (string) $order->get_id() === $order_number;
	}
}
