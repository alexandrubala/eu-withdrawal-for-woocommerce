<?php
/**
 * Request type constants (return goods vs refund / withdrawal).
 *
 * @package EUWithdrawal\Domain
 */

namespace EUWithdrawal\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Class Request_Type
 */
final class Request_Type {

	public const RETURN = 'return';
	public const REFUND = 'refund';

	/**
	 * All valid request type slugs.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array( self::RETURN, self::REFUND );
	}

	/**
	 * Whether a slug is valid.
	 *
	 * @param string $type Type slug.
	 * @return bool
	 */
	public static function is_valid( string $type ): bool {
		return in_array( $type, self::all(), true );
	}

	/**
	 * Human-readable label.
	 *
	 * @param string $type Type slug.
	 * @return string
	 */
	public static function label( string $type ): string {
		switch ( $type ) {
			case self::RETURN:
				return __( 'Return product', 'eu-withdrawal-for-woocommerce' );
			case self::REFUND:
				return __( 'Refund / withdrawal', 'eu-withdrawal-for-woocommerce' );
			default:
				return $type;
		}
	}
}
