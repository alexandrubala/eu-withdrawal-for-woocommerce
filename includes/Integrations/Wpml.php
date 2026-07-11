<?php
/**
 * WPML String Translation integration for legal labels.
 *
 * @package EUWithdrawal\Integrations
 */

namespace EUWithdrawal\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Class Wpml
 */
final class Wpml {

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
	 * Whether WPML is active.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	/**
	 * Register legal strings with WPML String Translation.
	 *
	 * @return void
	 */
	public function register_strings(): void {
		foreach ( Legal_String_Catalog::all() as $name => $string ) {
			do_action(
				'wpml_register_single_string',
				Legal_String_Catalog::CONTEXT,
				$name,
				$string
			);
		}
	}

	/**
	 * Translate a registered legal string via WPML.
	 *
	 * @param string $value Default string value.
	 * @param string $name  String identifier.
	 * @return string
	 */
	public function translate_string( string $value, string $name ): string {
		$translated = apply_filters(
			'wpml_translate_single_string',
			$value,
			Legal_String_Catalog::CONTEXT,
			$name
		);

		return is_string( $translated ) && '' !== $translated ? $translated : $value;
	}
}
