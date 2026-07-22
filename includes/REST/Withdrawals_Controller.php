<?php
/**
 * Protected REST endpoint for listing withdrawal requests.
 *
 * @package EUWithdrawal\REST
 */

namespace EUWithdrawal\REST;

use EUWithdrawal\Data\Withdrawal_Repository;
use EUWithdrawal\Domain\Withdrawal_Status;
use EUWithdrawal\Services\Attachment_Uploader;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class Withdrawals_Controller
 */
final class Withdrawals_Controller {

	/**
	 * Route base relative to the REST namespace.
	 */
	private const ROUTE = '/withdrawals';

	/**
	 * Default page size.
	 */
	private const DEFAULT_PER_PAGE = 20;

	/**
	 * Maximum page size.
	 */
	private const MAX_PER_PAGE = 100;

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
	 * Register the withdrawals list route.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			Rest_Bootstrap::REST_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_collection_params(),
			)
		);
	}

	/**
	 * Check whether the current user may list withdrawals.
	 *
	 * @return bool
	 */
	public function check_permission(): bool {
		return current_user_can( EU_WITHDRAWAL_CAPABILITY );
	}

	/**
	 * Return a paginated list of withdrawal requests.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function get_items( WP_REST_Request $request ) {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( self::MAX_PER_PAGE, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$status   = sanitize_key( (string) $request->get_param( 'status' ) );
		$search   = sanitize_text_field( (string) $request->get_param( 'search' ) );
		$orderby  = sanitize_key( (string) $request->get_param( 'orderby' ) );
		$order    = sanitize_key( (string) $request->get_param( 'order' ) );

		if ( '' !== $status && ! Withdrawal_Status::is_valid( $status ) ) {
			return new \WP_Error(
				'eu_withdrawal_invalid_status',
				__( 'Invalid status filter.', 'eu-withdrawal-for-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$result = $this->withdrawal_repository->query(
			array(
				'status'   => $status,
				'search'   => $search,
				'orderby'  => $orderby,
				'order'    => $order,
				'per_page' => $per_page,
				'offset'   => ( $page - 1 ) * $per_page,
			)
		);

		$items = array_map( array( $this, 'prepare_item' ), $result['items'] );
		$total = (int) $result['total'];

		$response = new WP_REST_Response(
			array(
				'items'       => $items,
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / $per_page ),
			),
			200
		);

		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) (int) ceil( $total / $per_page ) );

		return $response;
	}

	/**
	 * Define accepted collection query parameters.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_collection_params(): array {
		return array(
			'page'     => array(
				'default'           => 1,
				'type'              => 'integer',
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'default'           => self::DEFAULT_PER_PAGE,
				'type'              => 'integer',
				'minimum'           => 1,
				'maximum'           => self::MAX_PER_PAGE,
				'sanitize_callback' => 'absint',
			),
			'status'   => array(
				'default'           => '',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			),
			'search'   => array(
				'default'           => '',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'orderby'  => array(
				'default'           => 'submitted_at',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			),
			'order'    => array(
				'default'           => 'desc',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			),
		);
	}

	/**
	 * Format a withdrawal row for the REST response.
	 *
	 * @param array<string, mixed> $item Raw database row.
	 * @return array<string, mixed>
	 */
	private function prepare_item( array $item ): array {
		$status = (string) ( $item['status'] ?? Withdrawal_Status::PENDING );

		return array(
			'id'             => (int) ( $item['id'] ?? 0 ),
			'uuid'           => (string) ( $item['uuid'] ?? '' ),
			'order_id'       => (int) ( $item['order_id'] ?? 0 ),
			'order_number'   => (string) ( $item['order_number'] ?? '' ),
			'customer_name'  => (string) ( $item['customer_name'] ?? '' ),
			'customer_email' => (string) ( $item['customer_email'] ?? '' ),
			'customer_phone' => (string) ( $item['customer_phone'] ?? '' ),
			'status'         => $status,
			'status_label'   => Withdrawal_Status::label( $status ),
			'reason'         => (string) ( $item['reason'] ?? '' ),
			'attachments'    => Attachment_Uploader::decode_ids( (string) ( $item['attachments_json'] ?? '' ) ),
			'products'       => $this->decode_products( (string) ( $item['products_json'] ?? '' ) ),
			'locale'         => (string) ( $item['locale'] ?? '' ),
			'submitted_at'   => (string) ( $item['submitted_at'] ?? '' ),
			'created_at'     => (string) ( $item['created_at'] ?? '' ),
			'updated_at'     => (string) ( $item['updated_at'] ?? '' ),
		);
	}

	/**
	 * Decode products JSON into an array.
	 *
	 * @param string $json Stored products JSON.
	 * @return array<int, array<string, mixed>>
	 */
	private function decode_products( string $json ): array {
		if ( '' === $json ) {
			return array();
		}

		$decoded = json_decode( $json, true );

		return is_array( $decoded ) ? $decoded : array();
	}
}
