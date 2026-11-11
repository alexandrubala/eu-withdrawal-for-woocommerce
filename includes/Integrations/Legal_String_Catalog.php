<?php
/**
 * Catalog of customer-facing legal strings for multilingual integrations.
 *
 * @package EUWithdrawal\Integrations
 */

namespace EUWithdrawal\Integrations;

use EUWithdrawal\Services\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Legal_String_Catalog
 */
final class Legal_String_Catalog {

	/**
	 * Multilingual context shared by WPML and Polylang.
	 */
	public const CONTEXT = 'EU Withdrawal';

	/**
	 * Catalog keys of the default return reasons, in display order.
	 */
	public const DEFAULT_REASON_KEYS = array(
		'reason_option_damaged',
		'reason_option_wrong_product',
		'reason_option_not_as_described',
		'reason_option_changed_mind',
		'reason_option_other',
	);

	/**
	 * Registered legal string keys and their default translations.
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		return array(
			'withdrawal_button_label'        => __( 'Request return / withdrawal', 'eu-withdrawal-for-woocommerce' ),
			'withdrawal_request_heading'     => __( 'Return or withdrawal request', 'eu-withdrawal-for-woocommerce' ),
			'withdrawal_intro'               => __( 'Enter your order details to begin the return or withdrawal process.', 'eu-withdrawal-for-woocommerce' ),
			/* translators: %d: number of days */
			'withdrawal_intro_days'          => __( 'You can request a return or withdrawal within %d days of order completion.', 'eu-withdrawal-for-woocommerce' ),
			'full_name'                      => __( 'Full name', 'eu-withdrawal-for-woocommerce' ),
			'email'                          => __( 'Email', 'eu-withdrawal-for-woocommerce' ),
			'order_number'                   => __( 'Order number', 'eu-withdrawal-for-woocommerce' ),
			'order_date'                     => __( 'Order date', 'eu-withdrawal-for-woocommerce' ),
			'select_order'                   => __( 'Select an order', 'eu-withdrawal-for-woocommerce' ),
			'select_order_placeholder'       => __( 'Choose an eligible order…', 'eu-withdrawal-for-woocommerce' ),
			/* translators: 1: order number, 2: formatted date, 3: formatted total */
			'order_option_label'             => __( 'Order #%1$s — %2$s — %3$s', 'eu-withdrawal-for-woocommerce' ),
			'no_eligible_orders'             => __( 'You have no orders eligible for return or withdrawal right now.', 'eu-withdrawal-for-woocommerce' ),
			'guest_lookup_fallback'          => __( 'You can still look up an order with the order number and billing email used at checkout.', 'eu-withdrawal-for-woocommerce' ),
			'phone'                          => __( 'Phone', 'eu-withdrawal-for-woocommerce' ),
			'optional'                       => __( '(optional)', 'eu-withdrawal-for-woocommerce' ),
			'return_reason'                  => __( 'Return reason', 'eu-withdrawal-for-woocommerce' ),
			'return_reason_placeholder'      => __( 'Choose a reason…', 'eu-withdrawal-for-woocommerce' ),
			'reason_option_damaged'          => __( 'Product damaged in transit', 'eu-withdrawal-for-woocommerce' ),
			'reason_option_wrong_product'    => __( 'I received a different product than the one ordered', 'eu-withdrawal-for-woocommerce' ),
			'reason_option_not_as_described' => __( 'The product does not match the description', 'eu-withdrawal-for-woocommerce' ),
			'reason_option_changed_mind'     => __( 'I changed my mind', 'eu-withdrawal-for-woocommerce' ),
			'reason_option_other'            => __( 'Other reason', 'eu-withdrawal-for-woocommerce' ),
			'product_opened'                 => __( 'The product has been opened', 'eu-withdrawal-for-woocommerce' ),
			'product_opened_yes'             => __( 'Yes', 'eu-withdrawal-for-woocommerce' ),
			'product_opened_no'              => __( 'No', 'eu-withdrawal-for-woocommerce' ),
			'additional_details'             => __( 'Additional details', 'eu-withdrawal-for-woocommerce' ),
			'reason_photos'                  => __( 'Photos', 'eu-withdrawal-for-woocommerce' ),
			/* translators: %d: maximum number of photos */
			'reason_photos_hint'             => __( 'Maximum photos: %d (JPG, PNG, GIF, or WebP, max 5 MB each).', 'eu-withdrawal-for-woocommerce' ),
			/* translators: %d: number of photos already uploaded */
			'reason_photos_kept'             => __( '%d photo(s) already attached. Choose new files only if you want to replace them.', 'eu-withdrawal-for-woocommerce' ),
			/* translators: %d: maximum number of photos */
			'photos_limit_error'             => __( 'Too many photos. Maximum allowed: %d.', 'eu-withdrawal-for-woocommerce' ),
			'continue'                       => __( 'Continue', 'eu-withdrawal-for-woocommerce' ),
			'details_heading'                => __( 'What would you like to do?', 'eu-withdrawal-for-woocommerce' ),
			/* translators: 1: order number, 2: days remaining */
			'details_intro'                  => __( 'Order #%1$s — %2$d day(s) remaining in the return window.', 'eu-withdrawal-for-woocommerce' ),
			'request_type_legend'            => __( 'Request type', 'eu-withdrawal-for-woocommerce' ),
			'request_type_return'            => __( 'Return product', 'eu-withdrawal-for-woocommerce' ),
			'request_type_refund'            => __( 'Refund / withdrawal', 'eu-withdrawal-for-woocommerce' ),
			'return_choice_desc'             => __( 'Send the product back and follow the courier instructions.', 'eu-withdrawal-for-woocommerce' ),
			'refund_choice_desc'             => __( 'Get your money back (withdrawal / refund).', 'eu-withdrawal-for-woocommerce' ),
			'select_products'                => __( 'Select products', 'eu-withdrawal-for-woocommerce' ),
			'no_products'                    => __( 'No products remain available for return on this order.', 'eu-withdrawal-for-woocommerce' ),
			'quantity'                       => __( 'Qty', 'eu-withdrawal-for-woocommerce' ),
			'refund_account_heading'         => __( 'Where should we send the refund?', 'eu-withdrawal-for-woocommerce' ),
			'iban'                           => __( 'IBAN', 'eu-withdrawal-for-woocommerce' ),
			'account_holder'                 => __( 'Account holder name', 'eu-withdrawal-for-woocommerce' ),
			'courier_heading'                => __( 'Return shipping', 'eu-withdrawal-for-woocommerce' ),
			'courier_fallback'               => __( 'The store will contact you with return shipping instructions.', 'eu-withdrawal-for-woocommerce' ),
			'confirm_heading'                => __( 'Confirm your request', 'eu-withdrawal-for-woocommerce' ),
			'confirm_intro'                  => __( 'Please review your details before submitting.', 'eu-withdrawal-for-woocommerce' ),
			'back'                           => __( 'Back', 'eu-withdrawal-for-woocommerce' ),
			'confirm_withdrawal'             => __( 'Submit request', 'eu-withdrawal-for-woocommerce' ),
			'success_heading'                => __( 'Request received', 'eu-withdrawal-for-woocommerce' ),
			'success_intro'                  => __( 'Your return / withdrawal request has been submitted successfully.', 'eu-withdrawal-for-woocommerce' ),
			'date'                           => __( 'Date', 'eu-withdrawal-for-woocommerce' ),
			'time'                           => __( 'Time', 'eu-withdrawal-for-woocommerce' ),
			'request_number'                 => __( 'Request number', 'eu-withdrawal-for-woocommerce' ),
			/* translators: %s: withdrawal request UUID */
			'email_subject_confirmation'     => __( 'Withdrawal request confirmation – %s', 'eu-withdrawal-for-woocommerce' ),
			/* translators: %s: order number */
			'email_subject_status'           => __( 'Update on your return / withdrawal – order %s', 'eu-withdrawal-for-woocommerce' ),
			/* translators: %s: order number */
			'email_subject_admin'            => __( 'New withdrawal request – order %s', 'eu-withdrawal-for-woocommerce' ),
			'email_confirmation_heading'     => __( 'Return / withdrawal confirmation', 'eu-withdrawal-for-woocommerce' ),
			'email_confirmation_intro'       => __( 'We have received your declaration. Please keep this email as proof of submission.', 'eu-withdrawal-for-woocommerce' ),
			'your_details'                   => __( 'Your details', 'eu-withdrawal-for-woocommerce' ),
			'products'                       => __( 'Products', 'eu-withdrawal-for-woocommerce' ),
			'email_legal_disclaimer'         => __( 'This confirmation attests only to the receipt of the declaration. You will receive further instructions shortly.', 'eu-withdrawal-for-woocommerce' ),
			'my_account_menu'                => __( 'Returns & withdrawals', 'eu-withdrawal-for-woocommerce' ),
			'my_account_heading'             => __( 'Returns & withdrawals', 'eu-withdrawal-for-woocommerce' ),
			'my_account_intro'               => __( 'Select an eligible order to start a return or withdrawal request.', 'eu-withdrawal-for-woocommerce' ),
		);
	}

	/**
	 * Catalog keys grouped by the stage of the flow where they appear.
	 *
	 * Keys missing from the map land in the details group, so every key always
	 * gets a field on the settings page.
	 *
	 * @return array<string, array{label: string, keys: array<int, string>}>
	 */
	public static function groups(): array {
		$groups = array(
			'button'     => array(
				'label' => __( 'Button', 'eu-withdrawal-for-woocommerce' ),
				'keys'  => array( 'withdrawal_button_label' ),
			),
			'step1'      => array(
				'label' => __( 'Step 1: order', 'eu-withdrawal-for-woocommerce' ),
				'keys'  => array(
					'withdrawal_request_heading',
					'withdrawal_intro',
					'withdrawal_intro_days',
					'full_name',
					'email',
					'order_number',
					'select_order',
					'select_order_placeholder',
					'order_option_label',
					'no_eligible_orders',
					'guest_lookup_fallback',
					'phone',
					'optional',
					'continue',
				),
			),
			'details'    => array(
				'label' => __( 'Request details', 'eu-withdrawal-for-woocommerce' ),
				'keys'  => array(
					'details_heading',
					'details_intro',
					'order_date',
					'request_type_legend',
					'request_type_return',
					'request_type_refund',
					'return_choice_desc',
					'refund_choice_desc',
					'select_products',
					'no_products',
					'quantity',
					'return_reason',
					'return_reason_placeholder',
					'reason_option_damaged',
					'reason_option_wrong_product',
					'reason_option_not_as_described',
					'reason_option_changed_mind',
					'reason_option_other',
					'product_opened',
					'product_opened_yes',
					'product_opened_no',
					'additional_details',
					'reason_photos',
					'reason_photos_hint',
					'reason_photos_kept',
					'photos_limit_error',
					'courier_heading',
					'courier_fallback',
					'refund_account_heading',
					'iban',
					'account_holder',
				),
			),
			'confirm'    => array(
				'label' => __( 'Confirmation', 'eu-withdrawal-for-woocommerce' ),
				'keys'  => array( 'confirm_heading', 'confirm_intro', 'back', 'confirm_withdrawal' ),
			),
			'success'    => array(
				'label' => __( 'Success', 'eu-withdrawal-for-woocommerce' ),
				'keys'  => array( 'success_heading', 'success_intro', 'date', 'time', 'request_number' ),
			),
			'emails'     => array(
				'label' => __( 'Emails', 'eu-withdrawal-for-woocommerce' ),
				'keys'  => array(
					'email_subject_confirmation',
					'email_subject_status',
					'email_subject_admin',
					'email_confirmation_heading',
					'email_confirmation_intro',
					'your_details',
					'products',
					'email_legal_disclaimer',
				),
			),
			'my_account' => array(
				'label' => __( 'My Account', 'eu-withdrawal-for-woocommerce' ),
				'keys'  => array( 'my_account_menu', 'my_account_heading', 'my_account_intro' ),
			),
		);

		$grouped = array();

		foreach ( $groups as $group ) {
			$grouped = array_merge( $grouped, $group['keys'] );
		}

		$groups['details']['keys'] = array_merge(
			$groups['details']['keys'],
			array_values( array_diff( array_keys( self::all() ), $grouped ) )
		);

		return $groups;
	}

	/**
	 * Every catalog string with shop overrides applied, before multilingual filters.
	 *
	 * Multilingual plugins must register these values, so that the text they
	 * translate is the text the shop actually shows.
	 *
	 * @return array<string, string>
	 */
	public static function effective(): array {
		return array_merge( self::all(), array_intersect_key( Settings::custom_texts(), self::all() ) );
	}

	/**
	 * Resolve a legal string: shop override, then default, then multilingual filters.
	 *
	 * @param string      $key      String identifier from {@see self::all()}.
	 * @param string|null $fallback Optional override when the key is absent.
	 * @return string
	 */
	public static function translate( string $key, ?string $fallback = null ): string {
		$strings = self::effective();
		$value   = $strings[ $key ] ?? ( $fallback ?? $key );

		/**
		 * Filter a legal string before output.
		 *
		 * WPML and Polylang integrations hook here to return translated values.
		 *
		 * @param string $value Translated or default string.
		 * @param string $key   String identifier.
		 */
		return (string) apply_filters( 'eu_withdrawal_translate_string', $value, $key );
	}

	/**
	 * Resolve a legal string and fill in its placeholders.
	 *
	 * A broken template (for example from a filter or a third-party translation)
	 * falls back to the default text instead of raising an error on the page.
	 *
	 * @param string           $key  String identifier from {@see self::all()}.
	 * @param string|int|float ...$args Placeholder values.
	 * @return string
	 */
	public static function format( string $key, ...$args ): string {
		$template = self::translate( $key );

		try {
			return sprintf( $template, ...$args );
		} catch ( \ValueError | \ArgumentCountError $e ) {
			$defaults = self::all();

			return isset( $defaults[ $key ] ) ? sprintf( $defaults[ $key ], ...$args ) : $template;
		}
	}

	/**
	 * Display label for a stored "product opened" value.
	 *
	 * @param string $value Stored value: yes | no.
	 * @return string Empty for requests without this data.
	 */
	public static function product_opened_label( string $value ): string {
		if ( 'yes' === $value ) {
			return self::translate( 'product_opened_yes' );
		}

		if ( 'no' === $value ) {
			return self::translate( 'product_opened_no' );
		}

		return '';
	}

	/**
	 * Whether a text uses exactly the same placeholders as the default for its key.
	 *
	 * @param string $key  String identifier from {@see self::all()}.
	 * @param string $text Candidate text.
	 * @return bool
	 */
	public static function has_matching_placeholders( string $key, string $text ): bool {
		$defaults = self::all();

		if ( ! isset( $defaults[ $key ] ) ) {
			return false;
		}

		$expected = self::placeholder_tokens( $defaults[ $key ], false );

		// Keys with placeholders go through sprintf(), so any stray "%" would break it.
		return $expected === self::placeholder_tokens( $text, ! empty( $expected ) );
	}

	/**
	 * Extract the sorted placeholder tokens of a text.
	 *
	 * @param string $text   Text to inspect.
	 * @param bool   $strict Follow the full sprintf() grammar, so "100% sigur" counts as a token.
	 * @return array<int, string>
	 */
	private static function placeholder_tokens( string $text, bool $strict ): array {
		if ( $strict ) {
			$text    = str_replace( '%%', '', $text );
			$pattern = '/%(?:\d+\$)?[-+ 0]*\d*(?:\.\d+)?[a-zA-Z]?/';
		} else {
			$pattern = '/%(?:\d+\$)?[bcdeEfFgGosuxX]/';
		}

		preg_match_all( $pattern, $text, $matches );

		$tokens = $matches[0];
		sort( $tokens );

		return $tokens;
	}
}
