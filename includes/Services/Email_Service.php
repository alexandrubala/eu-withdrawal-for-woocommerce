<?php
/**
 * Customer confirmation emails (durable medium).
 *
 * @package EUWithdrawal\Services
 */

namespace EUWithdrawal\Services;

use EUWithdrawal\Utils\Template_Loader;

defined( 'ABSPATH' ) || exit;

/**
 * Class Email_Service
 */
final class Email_Service {

	/**
	 * Send the withdrawal confirmation email to the customer.
	 *
	 * @param string               $to           Recipient email address.
	 * @param array<string, mixed> $template_args Variables for the email template.
	 * @return bool
	 */
	public function send_customer_confirmation( string $to, array $template_args ): bool {
		if ( ! is_email( $to ) ) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s: withdrawal request UUID */
			__( 'Withdrawal request confirmation – %s', 'eu-withdrawal-for-woocommerce' ),
			(string) ( $template_args['request_uuid'] ?? '' )
		);

		$body = Template_Loader::load_email( 'customer-confirmation.php', $template_args );

		if ( '' === $body ) {
			return false;
		}

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		$from_name  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$from_email = get_option( 'admin_email' );

		if ( is_email( $from_email ) ) {
			$headers[] = sprintf( 'From: %s <%s>', $from_name, $from_email );
		}

		return (bool) wp_mail( $to, $subject, $body, $headers );
	}
}
