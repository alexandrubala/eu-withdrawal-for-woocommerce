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
			'withdrawal_button_label'      => __( 'Request withdrawal', 'eu-withdrawal-for-woocommerce' ),
			'withdrawal_request_heading'   => __( 'Withdrawal request', 'eu-withdrawal-for-woocommerce' ),
			'withdrawal_intro'             => __( 'Enter your order details to begin the EU withdrawal process.', 'eu-withdrawal-for-woocommerce' ),
			'full_name'                    => __( 'Full name', 'eu-withdrawal-for-woocommerce' ),
			'email'                        => __( 'Email', 'eu-withdrawal-for-woocommerce' ),
			'order_number'                 => __( 'Order number', 'eu-withdrawal-for-woocommerce' ),
			'phone'                        => __( 'Phone', 'eu-withdrawal-for-woocommerce' ),
			'optional'                     => __( '(optional)', 'eu-withdrawal-for-woocommerce' ),
			'reason_for_withdrawal'        => __( 'Reason for withdrawal', 'eu-withdrawal-for-woocommerce' ),
			'continue'                     => __( 'Continue', 'eu-withdrawal-for-woocommerce' ),
			'confirm_heading'              => __( 'Confirm your withdrawal', 'eu-withdrawal-for-woocommerce' ),
			'confirm_intro'                => __( 'Please review your details before submitting the withdrawal request.', 'eu-withdrawal-for-woocommerce' ),
			'back'                         => __( 'Back', 'eu-withdrawal-for-woocommerce' ),
			'confirm_withdrawal'           => __( 'Confirm withdrawal', 'eu-withdrawal-for-woocommerce' ),
			'success_heading'              => __( 'Withdrawal request received', 'eu-withdrawal-for-woocommerce' ),
			'success_intro'                => __( 'Your withdrawal request has been submitted successfully.', 'eu-withdrawal-for-woocommerce' ),
			'date'                         => __( 'Date', 'eu-withdrawal-for-woocommerce' ),
			'time'                         => __( 'Time', 'eu-withdrawal-for-woocommerce' ),
			'request_number'               => __( 'Request number', 'eu-withdrawal-for-woocommerce' ),
			'email_confirmation_heading'   => __( 'Withdrawal request confirmation', 'eu-withdrawal-for-woocommerce' ),
			'email_confirmation_intro'     => __( 'We have received your withdrawal declaration. Please keep this email as proof of submission.', 'eu-withdrawal-for-woocommerce' ),
			'your_details'                 => __( 'Your details', 'eu-withdrawal-for-woocommerce' ),
			'products'                     => __( 'Products', 'eu-withdrawal-for-woocommerce' ),
			'email_legal_disclaimer'       => __( 'This confirmation attests only to the receipt of the withdrawal declaration. You will receive return instructions shortly.', 'eu-withdrawal-for-woocommerce' ),
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
