<?php
/**
 * Plugin settings helper (return window, courier, IBAN policy).
 *
 * @package EUWithdrawal\Services
 */

namespace EUWithdrawal\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 */
final class Settings {

	/**
	 * Option key for all plugin settings.
	 */
	public const OPTION_KEY = 'eu_withdrawal_settings';

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'return_days'           => 14,
			'courier_name'          => '',
			'courier_phone'         => '',
			'courier_instructions'  => '',
			'return_address'        => '',
			'require_iban'          => 'always',
			'refund_note'           => '',
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( self::defaults(), $stored );
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback when missing.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$all = self::all();

		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}

		return $default;
	}

	/**
	 * Return window in days (minimum 1).
	 *
	 * @return int
	 */
	public static function return_days(): int {
		return max( 1, (int) self::get( 'return_days', 14 ) );
	}

	/**
	 * IBAN collection policy: always | non_card | never.
	 *
	 * @return string
	 */
	public static function require_iban(): string {
		$value = (string) self::get( 'require_iban', 'always' );

		return in_array( $value, array( 'always', 'non_card', 'never' ), true ) ? $value : 'always';
	}

	/**
	 * Whether IBAN should be collected for a given order and refund request.
	 *
	 * @param \WC_Order|null $order WooCommerce order.
	 * @return bool
	 */
	public static function should_require_iban( ?\WC_Order $order = null ): bool {
		$policy = self::require_iban();

		if ( 'never' === $policy ) {
			return false;
		}

		if ( 'always' === $policy ) {
			return true;
		}

		// non_card: require IBAN when payment method is not a card gateway.
		if ( ! $order instanceof \WC_Order ) {
			return true;
		}

		$method = strtolower( (string) $order->get_payment_method() );
		$card_methods = array(
			'stripe',
			'stripe_cc',
			'woocommerce_payments',
			'ppcp-gateway',
			'paypal',
			'square_credit_card',
			'mollie_wc_gateway_creditcard',
		);

		/**
		 * Filter known card payment method IDs that skip IBAN collection.
		 *
		 * @param array<int, string> $card_methods Payment method IDs.
		 * @param \WC_Order          $order        Order being checked.
		 */
		$card_methods = (array) apply_filters( 'eu_withdrawal_card_payment_methods', $card_methods, $order );

		if ( str_contains( $method, 'stripe' ) || str_contains( $method, 'card' ) ) {
			return false;
		}

		foreach ( $card_methods as $card_method ) {
			if ( $method === strtolower( (string) $card_method ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Build courier instructions text (filterable).
	 *
	 * @return string
	 */
	public static function courier_instructions_html(): string {
		$parts = array();

		$name = trim( (string) self::get( 'courier_name', '' ) );
		if ( '' !== $name ) {
			$parts[] = sprintf(
				/* translators: %s: courier name */
				__( 'Courier: %s', 'eu-withdrawal-for-woocommerce' ),
				$name
			);
		}

		$phone = trim( (string) self::get( 'courier_phone', '' ) );
		if ( '' !== $phone ) {
			$parts[] = sprintf(
				/* translators: %s: courier phone */
				__( 'Phone: %s', 'eu-withdrawal-for-woocommerce' ),
				$phone
			);
		}

		$address = trim( (string) self::get( 'return_address', '' ) );
		if ( '' !== $address ) {
			$parts[] = sprintf(
				/* translators: %s: return address */
				__( 'Return address: %s', 'eu-withdrawal-for-woocommerce' ),
				$address
			);
		}

		$instructions = trim( (string) self::get( 'courier_instructions', '' ) );
		if ( '' !== $instructions ) {
			$parts[] = $instructions;
		}

		$text = implode( "\n\n", $parts );

		/**
		 * Filter courier / return shipping instructions shown to customers.
		 *
		 * @param string $text Instructions text.
		 */
		return (string) apply_filters( 'eu_withdrawal_courier_instructions', $text );
	}

	/**
	 * Persist settings array.
	 *
	 * @param array<string, mixed> $settings Settings to save.
	 * @return void
	 */
	public static function update( array $settings ): void {
		$clean = self::defaults();

		$clean['return_days'] = max( 1, absint( $settings['return_days'] ?? 14 ) );
		$clean['courier_name'] = sanitize_text_field( (string) ( $settings['courier_name'] ?? '' ) );
		$clean['courier_phone'] = sanitize_text_field( (string) ( $settings['courier_phone'] ?? '' ) );
		$clean['courier_instructions'] = sanitize_textarea_field( (string) ( $settings['courier_instructions'] ?? '' ) );
		$clean['return_address'] = sanitize_textarea_field( (string) ( $settings['return_address'] ?? '' ) );
		$clean['refund_note'] = sanitize_textarea_field( (string) ( $settings['refund_note'] ?? '' ) );

		$iban = sanitize_key( (string) ( $settings['require_iban'] ?? 'always' ) );
		$clean['require_iban'] = in_array( $iban, array( 'always', 'non_card', 'never' ), true ) ? $iban : 'always';

		update_option( self::OPTION_KEY, $clean, false );
	}
}
