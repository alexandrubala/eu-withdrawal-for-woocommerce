<?php
/**
 * Plugin settings helper (return window, courier, IBAN policy).
 *
 * @package EUWithdrawal\Services
 */

namespace EUWithdrawal\Services;

use EUWithdrawal\Integrations\Legal_String_Catalog;

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
	 * Form fields the shop can show or hide (stored as field_{name}).
	 */
	public const FIELD_TOGGLES = array(
		'reason',
		'product_opened',
		'details',
		'photos',
		'phone',
		'sku',
		'summary',
		'request_box',
	);

	/**
	 * Upper bound for reason photos per request.
	 */
	public const MAX_PHOTOS_LIMIT = 5;

	/**
	 * Length of the reason_label database column.
	 */
	public const REASON_MAX_LENGTH = 200;

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		$defaults = array(
			'return_days'           => 14,
			'courier_name'          => '',
			'courier_instructions'  => '',
			'return_address'        => '',
			'require_iban'          => 'always',
			'refund_note'           => '',
			'max_photos'            => self::MAX_PHOTOS_LIMIT,
			'reason_options'        => '',
			'custom_texts'          => array(),
		);

		foreach ( self::FIELD_TOGGLES as $field ) {
			$defaults[ 'field_' . $field ] = '1';
		}

		return $defaults;
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
	 * Whether a configurable form field is shown.
	 *
	 * @param string $field Field name from {@see self::FIELD_TOGGLES}.
	 * @return bool
	 */
	public static function field_enabled( string $field ): bool {
		return '0' !== (string) self::get( 'field_' . $field, '1' );
	}

	/**
	 * Maximum reason photos per request (1–5).
	 *
	 * @return int
	 */
	public static function max_photos(): int {
		return min( self::MAX_PHOTOS_LIMIT, max( 1, (int) self::get( 'max_photos', self::MAX_PHOTOS_LIMIT ) ) );
	}

	/**
	 * Shop overrides for catalog texts, keyed by catalog key.
	 *
	 * @return array<string, string>
	 */
	public static function custom_texts(): array {
		$texts = self::get( 'custom_texts', array() );

		return is_array( $texts ) ? $texts : array();
	}

	/**
	 * Return reasons offered to the customer.
	 *
	 * @return array<int, string>
	 */
	public static function reason_options(): array {
		$options = self::parse_reason_options( (string) self::get( 'reason_options', '' ) );

		if ( ! empty( $options ) ) {
			return $options;
		}

		foreach ( Legal_String_Catalog::DEFAULT_REASON_KEYS as $key ) {
			$options[] = Legal_String_Catalog::translate( $key );
		}

		return $options;
	}

	/**
	 * Return the matching reason from the current list, or an empty string.
	 *
	 * @param string $value Submitted value.
	 * @return string
	 */
	public static function match_reason_option( string $value ): string {
		$value = sanitize_text_field( $value );

		if ( '' === $value ) {
			return '';
		}

		foreach ( self::reason_options() as $option ) {
			if ( sanitize_text_field( $option ) === $value ) {
				return $option;
			}
		}

		return '';
	}

	/**
	 * Split the reason setting into clean, unique, column-sized lines.
	 *
	 * @param string $raw One option per line.
	 * @return array<int, string>
	 */
	private static function parse_reason_options( string $raw ): array {
		$options = array();

		foreach ( preg_split( '/\R/u', $raw ) ?: array() as $line ) {
			$line = mb_substr( sanitize_text_field( $line ), 0, self::REASON_MAX_LENGTH );

			if ( '' !== $line ) {
				$options[] = $line;
			}
		}

		return array_values( array_unique( $options ) );
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

		$address = trim( (string) self::get( 'return_address', '' ) );
		if ( '' !== $address ) {
			// Keep the label on its own line so multi-line addresses stay left-aligned and readable.
			$parts[] = sprintf(
				/* translators: %s: return address (may contain multiple lines) */
				__( 'Return address: %s', 'eu-withdrawal-for-woocommerce' ),
				"\n" . $address
			);
		}

		$instructions = trim( (string) self::get( 'courier_instructions', '' ) );
		if ( '' !== $instructions ) {
			$parts[] = $instructions;
		}

		// Single blank line between sections; templates render with nl2br (do not also use CSS pre-wrap).
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
	 * @param array<string, mixed> $settings Settings to save (already unslashed).
	 * @return array<string, string> Rejected texts, keyed by catalog key.
	 */
	public static function update( array $settings ): array {
		$current = self::all();
		$clean   = self::defaults();

		$clean['return_days'] = max( 1, absint( $settings['return_days'] ?? 14 ) );
		$clean['courier_name'] = sanitize_text_field( (string) ( $settings['courier_name'] ?? '' ) );
		$clean['courier_instructions'] = sanitize_textarea_field( (string) ( $settings['courier_instructions'] ?? '' ) );
		$clean['return_address'] = sanitize_textarea_field( (string) ( $settings['return_address'] ?? '' ) );
		$clean['refund_note'] = sanitize_textarea_field( (string) ( $settings['refund_note'] ?? '' ) );

		$iban = sanitize_key( (string) ( $settings['require_iban'] ?? 'always' ) );
		$clean['require_iban'] = in_array( $iban, array( 'always', 'non_card', 'never' ), true ) ? $iban : 'always';

		foreach ( self::FIELD_TOGGLES as $field ) {
			$key = 'field_' . $field;

			// An unticked box must reach this point as an explicit "0"; a missing key keeps the stored value.
			$clean[ $key ] = array_key_exists( $key, $settings )
				? ( '1' === (string) $settings[ $key ] ? '1' : '0' )
				: (string) $current[ $key ];
		}

		$max_photos = trim( (string) ( $settings['max_photos'] ?? $current['max_photos'] ) );
		$clean['max_photos'] = '' === $max_photos
			? self::MAX_PHOTOS_LIMIT
			: min( self::MAX_PHOTOS_LIMIT, max( 1, absint( $max_photos ) ) );

		$clean['reason_options'] = implode(
			"\n",
			self::parse_reason_options( (string) ( $settings['reason_options'] ?? $current['reason_options'] ) )
		);

		$previous_texts = self::custom_texts();
		$rejected       = array();

		$clean['custom_texts'] = $previous_texts;

		if ( isset( $settings['custom_texts'] ) && is_array( $settings['custom_texts'] ) ) {
			$clean['custom_texts'] = self::clean_custom_texts( $settings['custom_texts'], $previous_texts, $rejected );
		}

		update_option( self::OPTION_KEY, $clean, false );

		return $rejected;
	}

	/**
	 * Validate submitted catalog texts.
	 *
	 * Empty or default-identical values are dropped so the translated default
	 * keeps applying. A text with the wrong placeholders is refused and the
	 * previously saved value stays.
	 *
	 * @param array<string, mixed>  $submitted Submitted texts keyed by catalog key.
	 * @param array<string, string> $previous  Currently saved texts.
	 * @param array<string, string> $rejected  Receives refused texts.
	 * @return array<string, string>
	 */
	private static function clean_custom_texts( array $submitted, array $previous, array &$rejected ): array {
		$defaults = Legal_String_Catalog::all();
		$clean    = array();

		foreach ( $defaults as $key => $default ) {
			if ( ! array_key_exists( $key, $submitted ) ) {
				if ( isset( $previous[ $key ] ) ) {
					$clean[ $key ] = $previous[ $key ];
				}
				continue;
			}

			// Not sanitize_text_field(): it deletes "%" followed by two hex characters, such as "%da".
			$text = wp_strip_all_tags( wp_check_invalid_utf8( (string) $submitted[ $key ] ), true );

			if ( '' === $text || $text === $default ) {
				continue;
			}

			if ( Legal_String_Catalog::has_matching_placeholders( $key, $text ) ) {
				$clean[ $key ] = $text;
				continue;
			}

			$rejected[ $key ] = $text;

			if ( isset( $previous[ $key ] ) ) {
				$clean[ $key ] = $previous[ $key ];
			}
		}

		return $clean;
	}
}
