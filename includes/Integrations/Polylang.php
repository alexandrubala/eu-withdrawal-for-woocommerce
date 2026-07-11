<?php
/**
 * Polylang string translation integration for legal labels.
 *
 * @package EUWithdrawal\Integrations
 */

namespace EUWithdrawal\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Class Polylang
 */
final class Polylang {

	/**
	 * Register integration hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! $this->is_active() ) {
			return;
		}

		add_action( 'init', array( $this, 'register_strings' ), 20 );
		add_filter( 'eu_withdrawal_translate_string', array( $this, 'translate_string' ), 10, 2 );
	}

	/**
	 * Whether Polylang is active.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return function_exists( 'pll_register_string' );
	}

	/**
	 * Register legal strings with Polylang.
	 *
	 * @return void
	 */
	public function register_strings(): void {
		foreach ( Legal_String_Catalog::all() as $name => $string ) {
			pll_register_string(
				$name,
				$string,
				Legal_String_Catalog::CONTEXT,
				false
			);
		}
	}

	/**
	 * Translate a registered legal string via Polylang.
	 *
	 * @param string $value Default string value.
	 * @param string $name  String identifier.
	 * @return string
	 */
	public function translate_string( string $value, string $name ): string {
		unset( $name );

		if ( ! function_exists( 'pll__' ) ) {
			return $value;
		}

		$translated = pll__( $value );

		return is_string( $translated ) && '' !== $translated ? $translated : $value;
	}
}
