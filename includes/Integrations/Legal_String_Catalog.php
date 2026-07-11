<?php
/**
 * Catalog of customer-facing legal strings for multilingual integrations.
 *
 * @package EUWithdrawal\Integrations
 */

namespace EUWithdrawal\Integrations;

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
	 * Registered legal string keys and their default translations.
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		return array(
			'withdrawal_button_label'      => __( 'Request withdrawal', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'withdrawal_request_heading'   => __( 'Withdrawal request', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'withdrawal_intro'             => __( 'Enter your order details to begin the EU withdrawal process.', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'full_name'                    => __( 'Full name', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'email'                        => __( 'Email', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'order_number'                 => __( 'Order number', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'phone'                        => __( 'Phone', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'optional'                     => __( '(optional)', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'reason_for_withdrawal'        => __( 'Reason for withdrawal', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'continue'                     => __( 'Continue', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'confirm_heading'              => __( 'Confirm your withdrawal', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'confirm_intro'                => __( 'Please review your details before submitting the withdrawal request.', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'back'                         => __( 'Back', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'confirm_withdrawal'           => __( 'Confirm withdrawal', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'success_heading'              => __( 'Withdrawal request received', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'success_intro'                => __( 'Your withdrawal request has been submitted successfully.', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'date'                         => __( 'Date', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'time'                         => __( 'Time', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'request_number'               => __( 'Request number', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'email_confirmation_heading'   => __( 'Withdrawal request confirmation', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'email_confirmation_intro'     => __( 'We have received your withdrawal declaration. Please keep this email as proof of submission.', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'your_details'                 => __( 'Your details', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'products'                     => __( 'Products', EU_WITHDRAWAL_TEXT_DOMAIN ),
			'email_legal_disclaimer'       => __( 'This confirmation attests only to the receipt of the withdrawal declaration. You will receive return instructions shortly.', EU_WITHDRAWAL_TEXT_DOMAIN ),
		);
	}

	/**
	 * Resolve a legal string, applying multilingual filters when active.
	 *
	 * @param string      $key      String identifier from {@see self::all()}.
	 * @param string|null $fallback Optional override when the key is absent.
	 * @return string
	 */
	public static function translate( string $key, ?string $fallback = null ): string {
		$strings = self::all();
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
}
