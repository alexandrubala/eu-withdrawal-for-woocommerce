<?php
/**
 * AJAX endpoints for the public multi-step withdrawal / return flow.
 *
 * @package EUWithdrawal\PublicArea
 */

namespace EUWithdrawal\PublicArea;

use EUWithdrawal\Domain\Request_Type;
use EUWithdrawal\Domain\Step1_Input;
use EUWithdrawal\Services\Order_Validator;
use EUWithdrawal\Services\Session_Token_Service;
use EUWithdrawal\Services\Settings;
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
	 * Maximum form-step requests per IP per hour (step1 / details / confirm).
	 * Nonce refresh is excluded — it runs on every page load for cache bypass.
	 */
	private const RATE_LIMIT_MAX = 30;

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
	 * @param Order_Validator       $order_validator    Order validator.
	 * @param Session_Token_Service $session_service    Session storage.
	 * @param Withdrawal_Service    $withdrawal_service Submission orchestrator.
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
			'eu_withdrawal_details',
			'eu_withdrawal_confirm',
		);

		foreach ( $actions as $action ) {
			$method = str_replace( 'eu_withdrawal_', 'handle_', $action );
			add_action( 'wp_ajax_' . $action, array( $this, $method ) );
			add_action( 'wp_ajax_nopriv_' . $action, array( $this, $method ) );
		}
	}

	/**
	 * Return a fresh nonce to bypass full-page cache (WP Rocket, etc.).
	 *
	 * Not rate-limited: this endpoint is called on every page load and must not
	 * consume the same budget as form submission steps.
	 *
	 * @return void
	 */
	public function handle_refresh_nonce(): void {
		try {
			wp_send_json_success(
				array(
					'nonce' => wp_create_nonce( Frontend::NONCE_ACTION ),
				)
			);
		} catch ( \Throwable $e ) {
			$this->send_exception_error( $e );
		}
	}

	/**
	 * Process Step 1: validate order, store session, return details HTML.
	 *
	 * @return void
	 */
	public function handle_step1(): void {
		try {
			if ( ! $this->verify_request( true ) ) {
				return;
			}

			$fields = Sanitizer::step1_fields( $_POST );
			$order  = $this->resolve_order_from_step1( $fields );

			if ( ! $order instanceof \WC_Order ) {
				wp_send_json_error(
					array(
						'code'    => 'order_not_found',
						'message' => __( 'We could not find an eligible order matching those details. Check the order number, email, and that the return window has not expired.', 'eu-withdrawal-for-woocommerce' ),
					)
				);
			}

			$name  = $fields['name'];
			$email = $fields['email'];
			$phone = $fields['phone'];

			if ( '' === $name ) {
				$name = trim( $order->get_formatted_billing_full_name() );
			}

			if ( '' === $email ) {
				$email = (string) $order->get_billing_email();
			}

			if ( '' === $phone ) {
				$phone = (string) $order->get_billing_phone();
			}

			if ( '' === $name || '' === $email ) {
				wp_send_json_error(
					array(
						'code'    => 'missing_fields',
						'message' => __( 'Please fill in all required fields.', 'eu-withdrawal-for-woocommerce' ),
					)
				);
			}

			if ( ! is_email( $email ) ) {
				wp_send_json_error(
					array(
						'code'    => 'invalid_email',
						'message' => __( 'Please enter a valid email address.', 'eu-withdrawal-for-woocommerce' ),
					)
				);
			}

			$input = new Step1_Input(
				$name,
				$email,
				(string) $order->get_order_number(),
				$phone,
				$fields['reason'],
				$order->get_id()
			);

			$token    = $this->session_service->create( $input );
			$products = $this->withdrawal_service->resolve_order_products( $order->get_id() );
			$require_iban = Settings::should_require_iban( $order ) ? '1' : '0';

			$html = Template_Loader::load(
				'step-details.php',
				array(
					'input'           => $input,
					'session_token'   => $token,
					'order'           => $order,
					'products'        => $products,
					'require_iban'    => $require_iban,
					'courier_text'    => Settings::courier_instructions_html(),
					'refund_note'     => (string) Settings::get( 'refund_note', '' ),
					'days_remaining'  => $this->order_validator->days_remaining( $order ),
				)
			);

			wp_send_json_success(
				array(
					'html'          => $html,
					'session_token' => $token,
				)
			);
		} catch ( \Throwable $e ) {
			$this->send_exception_error( $e );
		}
	}

	/**
	 * Process details step: type, products, IBAN → confirmation HTML.
	 *
	 * @return void
	 */
	public function handle_details(): void {
		try {
			if ( ! $this->verify_request( true ) ) {
				return;
			}

			$token = Sanitizer::session_token( $_POST );
			$input = $this->session_service->get( $token );

			if ( ! $input instanceof Step1_Input ) {
				wp_send_json_error(
					array(
						'code'    => 'session_expired',
						'message' => __( 'Your session has expired. Please start again.', 'eu-withdrawal-for-woocommerce' ),
					)
				);
			}

			$fields = Sanitizer::details_fields( $_POST );

			if ( ! Request_Type::is_valid( (string) $fields['request_type'] ) ) {
				wp_send_json_error(
					array(
						'code'    => 'invalid_type',
						'message' => __( 'Please choose whether you want to return products or receive a refund.', 'eu-withdrawal-for-woocommerce' ),
					)
				);
			}

			$selected = $this->withdrawal_service->resolve_selected_products(
				$input->order_id,
				(array) $fields['product_items'],
				(array) $fields['product_qty']
			);

			if ( empty( $selected ) ) {
				wp_send_json_error(
					array(
						'code'    => 'no_products',
						'message' => __( 'Please select at least one product.', 'eu-withdrawal-for-woocommerce' ),
					)
				);
			}

			$order        = wc_get_order( $input->order_id );
			$require_iban = Settings::should_require_iban( $order instanceof \WC_Order ? $order : null );
			$iban         = (string) $fields['refund_iban'];
			$account_name = (string) $fields['refund_account_name'];

			if ( Request_Type::REFUND === $fields['request_type'] && $require_iban ) {
				if ( '' === $iban || ! Sanitizer::is_valid_iban( $iban ) ) {
					wp_send_json_error(
						array(
							'code'    => 'invalid_iban',
							'message' => __( 'Please enter a valid IBAN for the refund.', 'eu-withdrawal-for-woocommerce' ),
						)
					);
				}

				if ( '' === $account_name ) {
					wp_send_json_error(
						array(
							'code'    => 'missing_account_name',
							'message' => __( 'Please enter the account holder name.', 'eu-withdrawal-for-woocommerce' ),
						)
					);
				}
			} else {
				$iban         = Request_Type::REFUND === $fields['request_type'] ? $iban : '';
				$account_name = Request_Type::REFUND === $fields['request_type'] ? $account_name : '';
			}

			$courier_notes = '';
			if ( Request_Type::RETURN === $fields['request_type'] ) {
				$courier_notes = Settings::courier_instructions_html();
			}

			$input = $input->with(
				array(
					'request_type'        => $fields['request_type'],
					'selected_products'   => $selected,
					'refund_iban'         => $iban,
					'refund_account_name' => $account_name,
					'courier_notes'       => $courier_notes,
					'reason'              => (string) $fields['reason'],
				)
			);

			if ( ! $this->session_service->update( $token, $input ) ) {
				wp_send_json_error(
					array(
						'code'    => 'session_expired',
						'message' => __( 'Your session has expired. Please start again.', 'eu-withdrawal-for-woocommerce' ),
					)
				);
			}

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
		} catch ( \Throwable $e ) {
			$this->send_exception_error( $e );
		}
	}

	/**
	 * Process final confirmation and persist the withdrawal request.
	 *
	 * @return void
	 */
	public function handle_confirm(): void {
		try {
			if ( ! $this->verify_request( true ) ) {
				return;
			}

			$token = Sanitizer::session_token( $_POST );
			$input = $this->session_service->get( $token );

			if ( ! $input instanceof Step1_Input ) {
				wp_send_json_error(
					array(
						'code'    => 'session_expired',
						'message' => __( 'Your session has expired. Please start again.', 'eu-withdrawal-for-woocommerce' ),
					)
				);
			}

			if ( ! Request_Type::is_valid( $input->request_type ) || empty( $input->selected_products ) ) {
				wp_send_json_error(
					array(
						'code'    => 'incomplete',
						'message' => __( 'Your request is incomplete. Please start again.', 'eu-withdrawal-for-woocommerce' ),
					)
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
						'code'    => 'save_failed',
						'message' => __( 'We could not save your withdrawal request. Please try again.', 'eu-withdrawal-for-woocommerce' ),
					)
				);
			}

			/**
			 * Fires after a withdrawal request has been persisted successfully.
			 *
			 * @param Step1_Input $input        Form data.
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
					'input'        => $input,
				)
			);

			wp_send_json_success(
				array(
					'html'         => $html,
					'request_uuid' => $request_uuid,
				)
			);
		} catch ( \Throwable $e ) {
			$this->send_exception_error( $e );
		}
	}

	/**
	 * Resolve the order from guest fields or logged-in order_id.
	 *
	 * @param array<string, string> $fields Sanitized step1 fields.
	 * @return \WC_Order|null
	 */
	private function resolve_order_from_step1( array $fields ): ?\WC_Order {
		$order_id = absint( $fields['order_id'] ?? 0 );

		if ( $order_id > 0 && is_user_logged_in() ) {
			return $this->order_validator->validate_for_customer( $order_id, get_current_user_id() );
		}

		if ( '' === $fields['order_number'] || '' === $fields['email'] ) {
			return null;
		}

		return $this->order_validator->validate( $fields['order_number'], $fields['email'] );
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
					'code'    => 'rate_limited',
					'message' => __( 'Too many requests. Please wait and try again later.', 'eu-withdrawal-for-woocommerce' ),
				)
			);

			return false;
		}

		return true;
	}

	/**
	 * Log and return a JSON error for unexpected exceptions.
	 *
	 * @param \Throwable $e Exception.
	 * @return void
	 */
	private function send_exception_error( \Throwable $e ): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'EU Withdrawal AJAX error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );

		wp_send_json_error(
			array(
				'code'    => 'server_error',
				'message' => __( 'Something went wrong on the server. Please try again or contact the store.', 'eu-withdrawal-for-woocommerce' ),
			)
		);
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

		$key    = self::RATE_LIMIT_PREFIX . md5( $ip );
		$count  = (int) get_transient( $key );
		$count += 1;

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
}
