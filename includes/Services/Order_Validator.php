<?php
/**
 * Validates that a WooCommerce order number matches the provided email.
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
	 * Validate order number and billing email combination.
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

		return $order;
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
