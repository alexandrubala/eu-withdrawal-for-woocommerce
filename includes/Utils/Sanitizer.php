<?php
/**
 * Sanitization helpers for public form submissions.
 *
 * @package EUWithdrawal\Utils
 */

namespace EUWithdrawal\Utils;

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
			'phone'        => sanitize_text_field( wp_unslash( (string) ( $source['phone'] ?? '' ) ) ),
			'reason'       => sanitize_textarea_field( wp_unslash( (string) ( $source['reason'] ?? '' ) ) ),
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
}
