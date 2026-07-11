<?php
/**
 * Stores Step 1 input in short-lived transients keyed by session token.
 *
 * @package EUWithdrawal\Services
 */

namespace EUWithdrawal\Services;

use EUWithdrawal\Domain\Step1_Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class Session_Token_Service
 */
final class Session_Token_Service {

	/**
	 * Transient key prefix.
	 */
	private const TRANSIENT_PREFIX = 'eu_wd_sess_';

	/**
	 * Session lifetime in seconds (30 minutes).
	 */
	private const TTL = 1800;

	/**
	 * Persist Step 1 data and return a unique session token.
	 *
	 * @param Step1_Input $input Validated step 1 payload.
	 * @return string Session token (UUID v4).
	 */
	public function create( Step1_Input $input ): string {
		$token = Uuid_Generator::generate();

		set_transient(
			self::TRANSIENT_PREFIX . $token,
			$input->to_array(),
			self::TTL
		);

		return $token;
	}

	/**
	 * Retrieve stored Step 1 data by session token.
	 *
	 * @param string $token Session token.
	 * @return Step1_Input|null
	 */
	public function get( string $token ): ?Step1_Input {
		$token = sanitize_text_field( $token );

		if ( '' === $token ) {
			return null;
		}

		$data = get_transient( self::TRANSIENT_PREFIX . $token );

		if ( false === $data || ! is_array( $data ) ) {
			return null;
		}

		return Step1_Input::from_array( $data );
	}

	/**
	 * Remove a session transient after successful completion.
	 *
	 * @param string $token Session token.
	 * @return void
	 */
	public function delete( string $token ): void {
		$token = sanitize_text_field( $token );

		if ( '' !== $token ) {
			delete_transient( self::TRANSIENT_PREFIX . $token );
		}
	}
}
