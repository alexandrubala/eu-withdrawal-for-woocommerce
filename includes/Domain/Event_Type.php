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
			self::REQUEST_SUBMITTED => __( 'Request submitted', EU_WITHDRAWAL_TEXT_DOMAIN ),
			self::EMAIL_FAILED      => __( 'Email failed', EU_WITHDRAWAL_TEXT_DOMAIN ),
			self::STATUS_CHANGED    => __( 'Status changed', EU_WITHDRAWAL_TEXT_DOMAIN ),
			self::REFUND_COMPLETED  => __( 'Refund completed', EU_WITHDRAWAL_TEXT_DOMAIN ),
		);

		return $labels[ $event_type ] ?? ucfirst( str_replace( '_', ' ', $event_type ) );
	}
}
