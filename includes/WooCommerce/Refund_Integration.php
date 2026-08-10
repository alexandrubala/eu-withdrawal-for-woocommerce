<?php
/**
 * Automatically sync withdrawal status when a WooCommerce refund is issued.
 *
 * @package EUWithdrawal\WooCommerce
 */

namespace EUWithdrawal\WooCommerce;

use EUWithdrawal\Data\Event_Repository;
use EUWithdrawal\Data\Withdrawal_Repository;
use EUWithdrawal\Domain\Event_Type;
use EUWithdrawal\Domain\Withdrawal_Status;
use EUWithdrawal\Services\Email_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class Refund_Integration
 */
final class Refund_Integration {

	/**
	 * Withdrawal requests repository.
	 *
	 * @var Withdrawal_Repository
	 */
	private Withdrawal_Repository $withdrawal_repository;

	/**
	 * Events repository.
	 *
	 * @var Event_Repository
	 */
	private Event_Repository $event_repository;

	/**
	 * Email service.
	 *
	 * @var Email_Service
	 */
	private Email_Service $email_service;

	/**
	 * Constructor.
	 *
	 * @param Withdrawal_Repository $withdrawal_repository Withdrawal persistence.
	 * @param Event_Repository      $event_repository      Event persistence.
	 * @param Email_Service         $email_service         Customer emails.
	 */
	public function __construct(
		Withdrawal_Repository $withdrawal_repository,
		Event_Repository $event_repository,
		Email_Service $email_service
	) {
		$this->withdrawal_repository = $withdrawal_repository;
		$this->event_repository      = $event_repository;
		$this->email_service         = $email_service;
	}

	/**
	 * Register WooCommerce refund hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_order_refunded', array( $this, 'on_order_refunded' ), 10, 2 );
	}

	/**
	 * Mark the linked withdrawal as refunded when WooCommerce processes a refund.
	 *
	 * @param int $order_id  WooCommerce order ID.
	 * @param int $refund_id WooCommerce refund ID.
	 * @return void
	 */
	public function on_order_refunded( int $order_id, int $refund_id ): void {
		if ( $order_id <= 0 ) {
			return;
		}

		$request = $this->withdrawal_repository->find_by_order_id( $order_id );

		if ( null === $request ) {
			return;
		}

		$request_id = (int) ( $request['id'] ?? 0 );
		$old_status = (string) ( $request['status'] ?? '' );

		if ( Withdrawal_Status::REFUNDED === $old_status ) {
			return;
		}

		$updated = $this->withdrawal_repository->update_status( $request_id, Withdrawal_Status::REFUNDED );

		if ( ! $updated ) {
			return;
		}

		$this->event_repository->insert(
			array(
				'request_id' => $request_id,
				'event_type' => Event_Type::REFUND_COMPLETED,
				'actor_type' => 'system',
				'message'    => sprintf(
					/* translators: %d: WooCommerce refund ID */
					__( 'WooCommerce refund #%d completed. Withdrawal marked as refunded.', 'eu-withdrawal-for-woocommerce' ),
					$refund_id
				),
				'meta_json'  => wp_json_encode(
					array(
						'order_id'   => $order_id,
						'refund_id'  => $refund_id,
						'old_status' => $old_status,
						'new_status' => Withdrawal_Status::REFUNDED,
					)
				),
			)
		);

		$sent = $this->email_service->send_customer_status_update(
			(string) ( $request['customer_email'] ?? '' ),
			array(
				'request_uuid'  => (string) ( $request['uuid'] ?? '' ),
				'order_number'  => (string) ( $request['order_number'] ?? '' ),
				'customer_name' => (string) ( $request['customer_name'] ?? '' ),
				'status'        => Withdrawal_Status::REFUNDED,
			)
		);

		if ( ! $sent ) {
			$this->event_repository->insert(
				array(
					'request_id' => $request_id,
					'event_type' => Event_Type::EMAIL_FAILED,
					'actor_type' => 'system',
					'message'    => __( 'Customer status update email could not be sent.', 'eu-withdrawal-for-woocommerce' ),
				)
			);
		}
	}
}
