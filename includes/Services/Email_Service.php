<?php
/**
 * Customer and shop notification emails.
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

		return $this->send_html_mail( $to, $subject, $body );
	}

	/**
	 * Notify the customer that their request status changed (processed / refunded).
	 *
	 * @param string               $to            Recipient email address.
	 * @param array<string, mixed> $template_args Variables for the email template.
	 * @return bool
	 */
	public function send_customer_status_update( string $to, array $template_args ): bool {
		if ( ! is_email( $to ) ) {
			return false;
		}

		$status = (string) ( $template_args['status'] ?? '' );

		if ( ! in_array( $status, array( 'processed', 'refunded' ), true ) ) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s: order number */
			__( 'Update on your return / withdrawal – order %s', 'eu-withdrawal-for-woocommerce' ),
			(string) ( $template_args['order_number'] ?? '' )
		);

		$body = Template_Loader::load_email( 'customer-status-update.php', $template_args );

		if ( '' === $body ) {
			return false;
		}

		return $this->send_html_mail( $to, $subject, $body );
	}

	/**
	 * Notify the shop about a new withdrawal request.
	 *
	 * Uses the same recipient(s) as WooCommerce "New order" emails.
	 *
	 * @param array<string, mixed> $template_args Variables for the email template.
	 * @return bool True when at least one recipient was emailed successfully.
	 */
	public function send_admin_notification( array $template_args ): bool {
		$recipients = $this->get_shop_notification_recipients();

		if ( empty( $recipients ) ) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s: order number */
			__( 'New withdrawal request – order %s', 'eu-withdrawal-for-woocommerce' ),
			(string) ( $template_args['order_number'] ?? '' )
		);

		$body = Template_Loader::load_email( 'admin-notification.php', $template_args );

		if ( '' === $body ) {
			return false;
		}

		$sent = false;

		foreach ( $recipients as $to ) {
			if ( $this->send_html_mail( $to, $subject, $body ) ) {
				$sent = true;
			}
		}

		return $sent;
	}

	/**
	 * Recipients configured for WooCommerce new-order notifications.
	 *
	 * @return array<int, string>
	 */
	public function get_shop_notification_recipients(): array {
		$raw = '';

		if ( function_exists( 'WC' ) && WC() && WC()->mailer() ) {
			$emails = WC()->mailer()->get_emails();

			if ( isset( $emails['WC_Email_New_Order'] ) && is_object( $emails['WC_Email_New_Order'] ) && method_exists( $emails['WC_Email_New_Order'], 'get_recipient' ) ) {
				$raw = (string) $emails['WC_Email_New_Order']->get_recipient();
			}
		}

		if ( '' === trim( $raw ) ) {
			$settings = get_option( 'woocommerce_new_order_settings', array() );

			if ( is_array( $settings ) && ! empty( $settings['recipient'] ) ) {
				$raw = (string) $settings['recipient'];
			}
		}

		if ( '' === trim( $raw ) ) {
			$raw = (string) get_option( 'admin_email', '' );
		}

		$recipients = array();

		foreach ( preg_split( '/[,;\s]+/', $raw ) ?: array() as $email ) {
			$email = sanitize_email( (string) $email );

			if ( is_email( $email ) ) {
				$recipients[] = $email;
			}
		}

		return array_values( array_unique( $recipients ) );
	}

	/**
	 * Send an HTML email with a From header.
	 *
	 * @param string $to      Recipient.
	 * @param string $subject Subject.
	 * @param string $body    HTML body.
	 * @return bool
	 */
	private function send_html_mail( string $to, string $subject, string $body ): bool {
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
