<?php
/**
 * Valid withdrawal request status values.
 *
 * @package EUWithdrawal\Domain
 */

namespace EUWithdrawal\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Class Withdrawal_Status
 */
final class Withdrawal_Status {

	public const PENDING   = 'pending';
	public const PROCESSED = 'processed';
	public const REJECTED  = 'rejected';
	public const REFUNDED  = 'refunded';

	/**
	 * All valid status slugs.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array(
			self::PENDING,
			self::PROCESSED,
			self::REJECTED,
			self::REFUNDED,
		);
	}

	/**
	 * Human-readable label for a status slug.
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	public static function label( string $status ): string {
		$labels = array(
			self::PENDING   => __( 'Pending', EU_WITHDRAWAL_TEXT_DOMAIN ),
			self::PROCESSED => __( 'Processed', EU_WITHDRAWAL_TEXT_DOMAIN ),
			self::REJECTED  => __( 'Rejected', EU_WITHDRAWAL_TEXT_DOMAIN ),
			self::REFUNDED  => __( 'Refunded', EU_WITHDRAWAL_TEXT_DOMAIN ),
		);

		return $labels[ $status ] ?? ucfirst( $status );
	}

	/**
	 * Check whether a status slug is valid.
	 *
	 * @param string $status Status slug.
	 * @return bool
	 */
	public static function is_valid( string $status ): bool {
		return in_array( $status, self::all(), true );
	}
}
