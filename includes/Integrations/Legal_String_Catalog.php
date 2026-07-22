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
			'withdrawal_button_label'      => __( 'Request return / withdrawal', 'eu-withdrawal-for-woocommerce' ),
			'withdrawal_request_heading'   => __( 'Return or withdrawal request', 'eu-withdrawal-for-woocommerce' ),
			'withdrawal_intro'             => __( 'Enter your order details to begin the return or withdrawal process.', 'eu-withdrawal-for-woocommerce' ),
			'withdrawal_intro_days'        => __( 'You can request a return or withdrawal within %d days of order completion.', 'eu-withdrawal-for-woocommerce' ),
			'full_name'                    => __( 'Full name', 'eu-withdrawal-for-woocommerce' ),
			'email'                        => __( 'Email', 'eu-withdrawal-for-woocommerce' ),
			'order_number'                 => __( 'Order number', 'eu-withdrawal-for-woocommerce' ),
			'select_order'                 => __( 'Select an order', 'eu-withdrawal-for-woocommerce' ),
			'select_order_placeholder'     => __( 'Choose an eligible order…', 'eu-withdrawal-for-woocommerce' ),
			'no_eligible_orders'           => __( 'You have no orders eligible for return or withdrawal right now.', 'eu-withdrawal-for-woocommerce' ),
			'guest_lookup_fallback'        => __( 'You can still look up an order with the order number and billing email used at checkout.', 'eu-withdrawal-for-woocommerce' ),
			'phone'                        => __( 'Phone', 'eu-withdrawal-for-woocommerce' ),
			'optional'                     => __( '(optional)', 'eu-withdrawal-for-woocommerce' ),
			'reason_for_withdrawal'        => __( 'Reason', 'eu-withdrawal-for-woocommerce' ),
			'reason_photos'                => __( 'Photos', 'eu-withdrawal-for-woocommerce' ),
			'reason_photos_hint'           => __( 'Add up to 5 photos (JPG, PNG, GIF, or WebP, max 5 MB each).', 'eu-withdrawal-for-woocommerce' ),
			'reason_photos_kept'           => __( '%d photo(s) already attached. Choose new files only if you want to replace them.', 'eu-withdrawal-for-woocommerce' ),
			'continue'                     => __( 'Continue', 'eu-withdrawal-for-woocommerce' ),
			'details_heading'              => __( 'What would you like to do?', 'eu-withdrawal-for-woocommerce' ),
			'details_intro'                => __( 'Order #%1$s — %2$d day(s) remaining in the return window.', 'eu-withdrawal-for-woocommerce' ),
			'request_type_legend'          => __( 'Request type', 'eu-withdrawal-for-woocommerce' ),
			'return_choice_desc'           => __( 'Send the product back and follow the courier instructions.', 'eu-withdrawal-for-woocommerce' ),
			'refund_choice_desc'           => __( 'Get your money back (withdrawal / refund).', 'eu-withdrawal-for-woocommerce' ),
			'select_products'              => __( 'Select products', 'eu-withdrawal-for-woocommerce' ),
			'no_products'                  => __( 'No products remain available for return on this order.', 'eu-withdrawal-for-woocommerce' ),
			'quantity'                     => __( 'Qty', 'eu-withdrawal-for-woocommerce' ),
			'refund_account_heading'       => __( 'Where should we send the refund?', 'eu-withdrawal-for-woocommerce' ),
			'iban'                         => __( 'IBAN', 'eu-withdrawal-for-woocommerce' ),
			'account_holder'               => __( 'Account holder name', 'eu-withdrawal-for-woocommerce' ),
			'courier_heading'              => __( 'Return shipping', 'eu-withdrawal-for-woocommerce' ),
			'courier_fallback'             => __( 'The store will contact you with return shipping instructions.', 'eu-withdrawal-for-woocommerce' ),
			'confirm_heading'              => __( 'Confirm your request', 'eu-withdrawal-for-woocommerce' ),
			'confirm_intro'                => __( 'Please review your details before submitting.', 'eu-withdrawal-for-woocommerce' ),
			'back'                         => __( 'Back', 'eu-withdrawal-for-woocommerce' ),
			'confirm_withdrawal'           => __( 'Submit request', 'eu-withdrawal-for-woocommerce' ),
			'success_heading'              => __( 'Request received', 'eu-withdrawal-for-woocommerce' ),
			'success_intro'                => __( 'Your return / withdrawal request has been submitted successfully.', 'eu-withdrawal-for-woocommerce' ),
			'date'                         => __( 'Date', 'eu-withdrawal-for-woocommerce' ),
			'time'                         => __( 'Time', 'eu-withdrawal-for-woocommerce' ),
			'request_number'               => __( 'Request number', 'eu-withdrawal-for-woocommerce' ),
			'email_confirmation_heading'   => __( 'Return / withdrawal confirmation', 'eu-withdrawal-for-woocommerce' ),
			'email_confirmation_intro'     => __( 'We have received your declaration. Please keep this email as proof of submission.', 'eu-withdrawal-for-woocommerce' ),
			'your_details'                 => __( 'Your details', 'eu-withdrawal-for-woocommerce' ),
			'products'                     => __( 'Products', 'eu-withdrawal-for-woocommerce' ),
			'email_legal_disclaimer'       => __( 'This confirmation attests only to the receipt of the declaration. You will receive further instructions shortly.', 'eu-withdrawal-for-woocommerce' ),
			'my_account_menu'              => __( 'Returns & withdrawals', 'eu-withdrawal-for-woocommerce' ),
			'my_account_heading'           => __( 'Returns & withdrawals', 'eu-withdrawal-for-woocommerce' ),
			'my_account_intro'             => __( 'Select an eligible order to start a return or withdrawal request.', 'eu-withdrawal-for-woocommerce' ),
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
