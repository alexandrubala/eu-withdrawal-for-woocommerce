<?php
/**
 * Sanitization helpers for public form submissions.
 *
 * @package EUWithdrawal\Utils
 */

namespace EUWithdrawal\Utils;

use EUWithdrawal\Domain\Request_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Class Sanitizer
 */
final class Sanitizer {

	/**
	 * Sanitize Step 1 form fields from a request array.
	 *
	 * @param array<string, mixed> $source Raw input (typically $_POST).
	 * @return array<string, string>
	 */
	public static function step1_fields( array $source ): array {
		return array(
			'name'         => sanitize_text_field( wp_unslash( (string) ( $source['name'] ?? '' ) ) ),
			'email'        => sanitize_email( wp_unslash( (string) ( $source['email'] ?? '' ) ) ),
			'order_number' => sanitize_text_field( wp_unslash( (string) ( $source['order_number'] ?? '' ) ) ),
			'order_id'     => (string) absint( $source['order_id'] ?? 0 ),
			'phone'        => sanitize_text_field( wp_unslash( (string) ( $source['phone'] ?? '' ) ) ),
			'reason'       => sanitize_textarea_field( wp_unslash( (string) ( $source['reason'] ?? '' ) ) ),
		);
	}

	/**
	 * Sanitize details step fields (type, products, IBAN).
	 *
	 * @param array<string, mixed> $source Raw input.
	 * @return array<string, mixed>
	 */
	public static function details_fields( array $source ): array {
		$type = sanitize_key( wp_unslash( (string) ( $source['request_type'] ?? '' ) ) );

		if ( ! Request_Type::is_valid( $type ) ) {
			$type = '';
		}

		$item_keys = array();
		if ( isset( $source['product_items'] ) && is_array( $source['product_items'] ) ) {
			foreach ( $source['product_items'] as $key ) {
				$item_keys[] = sanitize_text_field( wp_unslash( (string) $key ) );
			}
		}

		$quantities = array();
		if ( isset( $source['product_qty'] ) && is_array( $source['product_qty'] ) ) {
			foreach ( $source['product_qty'] as $key => $qty ) {
				$clean_key = sanitize_text_field( wp_unslash( (string) $key ) );
				$quantities[ $clean_key ] = max( 1, absint( $qty ) );
			}
		}

		$iban = strtoupper( preg_replace( '/\s+/', '', wp_unslash( (string) ( $source['refund_iban'] ?? '' ) ) ) ?? '' );
		$iban = sanitize_text_field( $iban );

		return array(
			'request_type'        => $type,
			'product_items'       => $item_keys,
			'product_qty'         => $quantities,
			'refund_iban'         => $iban,
			'refund_account_name' => sanitize_text_field( wp_unslash( (string) ( $source['refund_account_name'] ?? '' ) ) ),
			'reason'              => sanitize_textarea_field( wp_unslash( (string) ( $source['reason'] ?? '' ) ) ),
		);
	}

	/**
	 * Sanitize a session token from request data.
	 *
	 * @param array<string, mixed> $source Raw input.
	 * @return string
	 */
	public static function session_token( array $source ): string {
		return sanitize_text_field( wp_unslash( (string) ( $source['session_token'] ?? '' ) ) );
	}

	/**
	 * Basic IBAN format validation (structure only, not full checksum for all countries).
	 *
	 * @param string $iban IBAN string without spaces.
	 * @return bool
	 */
	public static function is_valid_iban( string $iban ): bool {
		$iban = strtoupper( preg_replace( '/\s+/', '', $iban ) ?? '' );

		if ( ! preg_match( '/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban ) ) {
			return false;
		}

		// Move first 4 chars to end and convert letters to numbers for mod-97 check.
		$rearranged = substr( $iban, 4 ) . substr( $iban, 0, 4 );
		$numeric    = '';

		$len = strlen( $rearranged );
		for ( $i = 0; $i < $len; $i++ ) {
			$char = $rearranged[ $i ];
			if ( ctype_alpha( $char ) ) {
				$numeric .= (string) ( ord( $char ) - 55 );
			} else {
				$numeric .= $char;
			}
		}

		// Compute mod 97 in chunks to avoid big-int issues.
		$checksum = 0;
		$nlen     = strlen( $numeric );
		for ( $i = 0; $i < $nlen; $i++ ) {
			$checksum = ( ( $checksum * 10 ) + (int) $numeric[ $i ] ) % 97;
		}

		return 1 === $checksum;
	}
}
