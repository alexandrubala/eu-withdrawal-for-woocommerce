<?php
/**
 * Cryptographic hashing for immutable audit log entries.
 *
 * @package EUWithdrawal\Security
 */

namespace EUWithdrawal\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Class Audit_Hash
 */
final class Audit_Hash {

	/**
	 * WordPress option key for the HMAC secret.
	 */
	private const SECRET_OPTION = 'eu_withdrawal_audit_secret';

	/**
	 * Generate payload and security hashes for an audit record.
	 *
	 * @param array<string, mixed> $data         Auditable payload (will be JSON-encoded).
	 * @param string               $uuid         Request UUID.
	 * @param string               $submitted_at MySQL datetime of submission.
	 * @return array{payload_hash: string, security_hash: string}
	 */
	public function generate( array $data, string $uuid, string $submitted_at ): array {
		$payload_hash = hash( 'sha256', (string) wp_json_encode( $data ) );

		$secret = (string) get_option( self::SECRET_OPTION, '' );

		if ( '' === $secret ) {
			$secret = wp_generate_password( 64, true, true );
			update_option( self::SECRET_OPTION, $secret, false );
		}

		$message       = $payload_hash . $uuid . $submitted_at;
		$security_hash = hash_hmac( 'sha256', $message, $secret );

		return array(
			'payload_hash'  => $payload_hash,
			'security_hash' => $security_hash,
		);
	}
}
