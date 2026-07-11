<?php
/**
 * Admin export action handler for CSV and print-friendly views.
 *
 * @package EUWithdrawal\Admin
 */

namespace EUWithdrawal\Admin;

use EUWithdrawal\Domain\Withdrawal_Status;
use EUWithdrawal\Services\Export_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class Export_Controller
 */
final class Export_Controller {

	/**
	 * Export action query parameter value for CSV downloads.
	 */
	public const ACTION_EXPORT_CSV = 'export_csv';

	/**
	 * Export action query parameter value for print-friendly HTML.
	 */
	public const ACTION_PRINT_VIEW = 'print_view';

	/**
	 * Nonce action for export requests.
	 */
	public const EXPORT_NONCE_ACTION = 'eu_withdrawal_export';

	/**
	 * Export service.
	 *
	 * @var Export_Service
	 */
	private Export_Service $export_service;

	/**
	 * Constructor.
	 *
	 * @param Export_Service $export_service Export generator.
	 */
	public function __construct( Export_Service $export_service ) {
		$this->export_service = $export_service;
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_init', array( $this, 'handle_export_request' ) );
	}

	/**
	 * Intercept export requests before the admin UI renders.
	 *
	 * @return void
	 */
	public function handle_export_request(): void {
		if ( ! isset( $_GET['eu_withdrawal_action'] ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( Admin::PAGE_SLUG !== $page ) {
			return;
		}

		if ( ! current_user_can( EU_WITHDRAWAL_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'eu-withdrawal-for-woocommerce' ) );
		}

		if ( ! isset( $_GET['eu_withdrawal_export_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['eu_withdrawal_export_nonce'] ) ), self::EXPORT_NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'eu-withdrawal-for-woocommerce' ) );
		}

		$action = sanitize_key( wp_unslash( $_GET['eu_withdrawal_action'] ) );

		$query_args = $this->build_query_args_from_request();

		if ( self::ACTION_EXPORT_CSV === $action ) {
			$this->export_service->send_csv_download( $query_args );
			exit;
		}

		if ( self::ACTION_PRINT_VIEW === $action ) {
			$this->export_service->send_print_view( $query_args );
			exit;
		}
	}

	/**
	 * Build repository query arguments from the current admin request.
	 *
	 * @return array<string, mixed>
	 */
	private function build_query_args_from_request(): array {
		$status  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$search  = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'submitted_at';
		$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'desc';

		if ( '' !== $status && ! Withdrawal_Status::is_valid( $status ) ) {
			$status = '';
		}

		return array(
			'status'  => $status,
			'search'  => $search,
			'orderby' => $orderby,
			'order'   => $order,
		);
	}
}
