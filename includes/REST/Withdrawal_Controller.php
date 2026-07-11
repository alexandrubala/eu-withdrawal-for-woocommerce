<?php
/**
 * Public REST endpoint for a single withdrawal request by UUID.
 *
 * @package EUWithdrawal\REST
 */

namespace EUWithdrawal\REST;

use EUWithdrawal\Data\Withdrawal_Repository;
use EUWithdrawal\Domain\Withdrawal_Status;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class Withdrawal_Controller
 */
final class Withdrawal_Controller {

	/**
	 * Route base relative to the REST namespace.
	 */
	private const ROUTE = '/withdrawals/(?P<uuid>[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})';

	/**
	 * Withdrawal requests repository.
	 *
	 * @var Withdrawal_Repository
	 */
	private Withdrawal_Repository $withdrawal_repository;

	/**
	 * Constructor.
	 *
	 * @param Withdrawal_Repository $withdrawal_repository Withdrawal persistence.
	 */
	public function __construct( Withdrawal_Repository $withdrawal_repository ) {
		$this->withdrawal_repository = $withdrawal_repository;
	}

	/**
	 * Register the single-withdrawal route.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			Rest_Bootstrap::REST_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'uuid' => array(
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => array( $this, 'validate_uuid' ),
					),
				),
			)
		);
	}

	/**
	 * Return public withdrawal status details for a UUID.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function get_item( WP_REST_Request $request ) {
		$uuid    = (string) $request->get_param( 'uuid' );
		$withdrawal = $this->withdrawal_repository->find_by_uuid( $uuid );

		if ( null === $withdrawal ) {
			return new \WP_Error(
				'eu_withdrawal_not_found',
				__( 'Withdrawal request not found.', 'eu-withdrawal-for-woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$status = (string) ( $withdrawal['status'] ?? Withdrawal_Status::PENDING );

		$data = array(
			'uuid'          => (string) ( $withdrawal['uuid'] ?? '' ),
			'status'        => $status,
			'status_label'  => Withdrawal_Status::label( $status ),
			'order_number'  => (string) ( $withdrawal['order_number'] ?? '' ),
			'submitted_at'  => (string) ( $withdrawal['submitted_at'] ?? '' ),
			'updated_at'    => (string) ( $withdrawal['updated_at'] ?? '' ),
		);

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Validate a UUID route parameter.
	 *
	 * @param mixed $value Raw parameter value.
	 * @return bool
	 */
	public function validate_uuid( $value ): bool {
		return is_string( $value ) && wp_is_uuid( $value );
	}
}
