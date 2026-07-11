<?php
/**
 * AJAX endpoints for the public two-step withdrawal flow.
 *
 * @package EUWithdrawal\PublicArea
 */

namespace EUWithdrawal\PublicArea;

use EUWithdrawal\Domain\Step1_Input;
use EUWithdrawal\Services\Order_Validator;
use EUWithdrawal\Services\Session_Token_Service;
use EUWithdrawal\Services\Uuid_Generator;
use EUWithdrawal\Services\Withdrawal_Service;
use EUWithdrawal\Utils\Sanitizer;
use EUWithdrawal\Utils\Template_Loader;

defined( 'ABSPATH' ) || exit;

/**
 * Class Ajax
 */
final class Ajax {

	/**
	 * Maximum requests per IP per hour.
	 */
	private const RATE_LIMIT_MAX = 10;

	/**
	 * Rate-limit window in seconds (1 hour).
	 */
	private const RATE_LIMIT_WINDOW = 3600;

	/**
	 * Transient prefix for IP rate limiting.
	 */
	private const RATE_LIMIT_PREFIX = 'eu_wd_rate_';

	/**
	 * Order validator service.
	 *
	 * @var Order_Validator
	 */
	private Order_Validator $order_validator;

	/**
	 * Session token service.
	 *
	 * @var Session_Token_Service
	 */
	private Session_Token_Service $session_service;

	/**
	 * Withdrawal submission service.
	 *
	 * @var Withdrawal_Service
	 */
	private Withdrawal_Service $withdrawal_service;

	/**
	 * Constructor.
	 *
	 * @param Order_Validator       $order_validator       Order validator.
	 * @param Session_Token_Service $session_service       Session storage.
	 * @param Withdrawal_Service    $withdrawal_service    Submission orchestrator.
	 */
	public function __construct(
		Order_Validator $order_validator,
		Session_Token_Service $session_service,
		Withdrawal_Service $withdrawal_service
	) {
		$this->order_validator    = $order_validator;
		$this->session_service    = $session_service;
		$this->withdrawal_service = $withdrawal_service;
	}

	/**
	 * Register AJAX action hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		$actions = array(
			'eu_withdrawal_refresh_nonce',
			'eu_withdrawal_step1',
			'eu_withdrawal_confirm',
		);

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( $this, str_replace( 'eu_withdrawal_', 'handle_', $action ) ) );
			add_action( 'wp_ajax_nopriv_' . $action, array( $this, str_replace( 'eu_withdrawal_', 'handle_', $action ) ) );
		}
	}

	/**
	 * Return a fresh nonce to bypass full-page cache (WP Rocket, etc.).
	 *
	 * @return void
	 */
	public function handle_refresh_nonce(): void {
		if ( ! $this->check_rate_limit() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Too many requests. Please wait and try again later.', EU_WITHDRAWAL_TEXT_DOMAIN ),
				),
				429
			);
		}

		wp_send_json_success(
			array(
				'nonce' => wp_create_nonce( Frontend::NONCE_ACTION ),
			)
		);
	}

	/**
	 * Process Step 1: validate order, store session, return Step 2 HTML.
	 *
	 * @return void
	 */
	public function handle_step1(): void {
		if ( ! $this->verify_request( true ) ) {
			return;
		}

		$fields = Sanitizer::step1_fields( $_POST );

		if ( ! $this->validate_step1_required( $fields ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please fill in all required fields.', EU_WITHDRAWAL_TEXT_DOMAIN ),
				),
				422
			);
		}

		if ( ! is_email( $fields['email'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please enter a valid email address.', EU_WITHDRAWAL_TEXT_DOMAIN ),
				),
				422
			);
		}

		$order = $this->order_validator->validate( $fields['order_number'], $fields['email'] );

		if ( ! $order instanceof \WC_Order ) {
			wp_send_json_error(
				array(
					'message' => __( 'We could not find an order matching that number and email address.', EU_WITHDRAWAL_TEXT_DOMAIN ),
				),
				404
			);
		}

		$input = new Step1_Input(
			$fields['name'],
			$fields['email'],
			$fields['order_number'],
			$fields['phone'],
			$fields['reason'],
			$order->get_id()
		);

		$token = $this->session_service->create( $input );

		$html = Template_Loader::load(
			'step-2-confirm.php',
			array(
				'input'         => $input,
				'session_token' => $token,
			)
		);

		wp_send_json_success(
			array(
				'html'          => $html,
				'session_token' => $token,
			)
		);
	}

	/**
	 * Process Step 2 final confirmation and persist the withdrawal request.
	 *
	 * @return void
	 */
	public function handle_confirm(): void {
		if ( ! $this->verify_request( true ) ) {
			return;
		}

		$token = Sanitizer::session_token( $_POST );
		$input = $this->session_service->get( $token );

		if ( ! $input instanceof Step1_Input ) {
			wp_send_json_error(
				array(
					'message' => __( 'Your session has expired. Please start again.', EU_WITHDRAWAL_TEXT_DOMAIN ),
				),
				410
			);
		}

		$request_uuid = Uuid_Generator::generate();
		$submitted_at = current_time( 'mysql' );

		$request_id = $this->withdrawal_service->submit(
			$input,
			$request_uuid,
			$submitted_at,
			$this->get_client_ip(),
			$this->get_user_agent()
		);

		if ( 0 === $request_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'We could not save your withdrawal request. Please try again.', EU_WITHDRAWAL_TEXT_DOMAIN ),
				),
				500
			);
		}

		/**
		 * Fires after a withdrawal request has been persisted successfully.
		 *
		 * @param Step1_Input $input        Step 1 form data.
		 * @param string      $request_uuid Generated request UUID.
		 * @param string      $submitted_at MySQL datetime of submission.
		 * @param int         $request_id   Inserted request row ID.
		 */
		do_action( 'eu_withdrawal_request_submitted', $input, $request_uuid, $submitted_at, $request_id );

		$this->session_service->delete( $token );

		$html = Template_Loader::load(
			'step-3-success.php',
			array(
				'request_uuid' => $request_uuid,
				'submitted_at' => $submitted_at,
			)
		);

		wp_send_json_success(
			array(
				'html'         => $html,
				'request_uuid' => $request_uuid,
			)
		);
	}

	/**
	 * Verify nonce and rate limit for incoming AJAX requests.
	 *
	 * @param bool $apply_rate_limit Whether to increment the rate-limit counter.
	 * @return bool
	 */
	private function verify_request( bool $apply_rate_limit ): bool {
		check_ajax_referer( Frontend::NONCE_ACTION, 'nonce' );

		if ( $apply_rate_limit && ! $this->check_rate_limit() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Too many requests. Please wait and try again later.', EU_WITHDRAWAL_TEXT_DOMAIN ),
				),
				429
			);

			return false;
		}

		return true;
	}

	/**
	 * Enforce a simple per-IP hourly rate limit via transients.
	 *
	 * @return bool True when the request is allowed.
	 */
	private function check_rate_limit(): bool {
		$ip = $this->get_client_ip();

		if ( '' === $ip ) {
			return true;
		}

		$key     = self::RATE_LIMIT_PREFIX . md5( $ip );
		$count   = (int) get_transient( $key );
		$count  += 1;

		set_transient( $key, $count, self::RATE_LIMIT_WINDOW );

		return $count <= self::RATE_LIMIT_MAX;
	}

	/**
	 * Resolve the client IP address.
	 *
	 * @return string
	 */
	private function get_client_ip(): string {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}

	/**
	 * Resolve the client user agent string.
	 *
	 * @return string
	 */
	private function get_user_agent(): string {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) );
	}

	/**
	 * Validate required Step 1 fields.
	 *
	 * @param array<string, string> $fields Sanitized fields.
	 * @return bool
	 */
	private function validate_step1_required( array $fields ): bool {
		foreach ( array( 'name', 'email', 'order_number' ) as $required ) {
			if ( '' === $fields[ $required ] ) {
				return false;
			}
		}

		return true;
	}
}
