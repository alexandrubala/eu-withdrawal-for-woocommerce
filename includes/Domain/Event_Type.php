<?php
/**
 * Withdrawal lifecycle event type identifiers.
 *
 * @package EUWithdrawal\Domain
 */

namespace EUWithdrawal\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Class Event_Type
 */
final class Event_Type {

	public const REQUEST_SUBMITTED = 'request_submitted';
	public const EMAIL_FAILED      = 'email_failed';
	public const STATUS_CHANGED    = 'status_changed';
	public const REFUND_COMPLETED  = 'refund_completed';

	/**
	 * Human-readable label for an event type slug.
	 *
	 * @param string $event_type Event type slug.
	 * @return string
	 */
	public static function label( string $event_type ): string {
		$labels = array(
			self::REQUEST_SUBMITTED => __( 'Request submitted', 'eu-withdrawal-for-woocommerce' ),
			self::EMAIL_FAILED      => __( 'Email failed', 'eu-withdrawal-for-woocommerce' ),
			self::STATUS_CHANGED    => __( 'Status changed', 'eu-withdrawal-for-woocommerce' ),
			self::REFUND_COMPLETED  => __( 'Refund completed', 'eu-withdrawal-for-woocommerce' ),
		);

		return $labels[ $event_type ] ?? ucfirst( str_replace( '_', ' ', $event_type ) );
	}
}
