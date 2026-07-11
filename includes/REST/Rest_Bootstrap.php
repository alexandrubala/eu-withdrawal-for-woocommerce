<?php
/**
 * Registers the eu-withdrawal/v1 REST API namespace and routes.
 *
 * @package EUWithdrawal\REST
 */

namespace EUWithdrawal\REST;

use EUWithdrawal\Data\Withdrawal_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rest_Bootstrap
 */
final class Rest_Bootstrap {

	/**
	 * REST API namespace.
	 */
	public const REST_NAMESPACE = 'eu-withdrawal/v1';

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
	 * Register REST API hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all withdrawal REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		( new Withdrawal_Controller( $this->withdrawal_repository ) )->register_routes();
		( new Withdrawals_Controller( $this->withdrawal_repository ) )->register_routes();
	}
}
