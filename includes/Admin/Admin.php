<?php
/**
 * Admin dashboard orchestrator: menu, assets, list and detail routing.
 *
 * @package EUWithdrawal\Admin
 */

namespace EUWithdrawal\Admin;

use EUWithdrawal\Data\Audit_Repository;
use EUWithdrawal\Data\Event_Repository;
use EUWithdrawal\Data\Withdrawal_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 */
final class Admin {

	/**
	 * Admin page slug.
	 */
	public const PAGE_SLUG = 'eu-withdrawals';

	/**
	 * Nonce action prefix for status changes.
	 */
	public const STATUS_NONCE_ACTION = 'eu_withdrawal_status_change';

	/**
	 * Withdrawal requests repository.
	 *
	 * @var Withdrawal_Repository
	 */
	private Withdrawal_Repository $withdrawal_repository;

	/**
	 * Events repository.
	 *
	 * @var Event_Repository
	 */
	private Event_Repository $event_repository;

	/**
	 * Audit log repository.
	 *
	 * @var Audit_Repository
	 */
	private Audit_Repository $audit_repository;

	/**
	 * Detail page renderer.
	 *
	 * @var Withdrawal_Detail_Page
	 */
	private Withdrawal_Detail_Page $detail_page;

	/**
	 * Constructor.
	 *
	 * @param Withdrawal_Repository $withdrawal_repository Withdrawal persistence.
	 * @param Event_Repository      $event_repository      Event persistence.
	 * @param Audit_Repository      $audit_repository      Audit log persistence.
	 */
	public function __construct(
		Withdrawal_Repository $withdrawal_repository,
		Event_Repository $event_repository,
		Audit_Repository $audit_repository
	) {
		$this->withdrawal_repository = $withdrawal_repository;
		$this->event_repository      = $event_repository;
		$this->audit_repository      = $audit_repository;
		$this->detail_page           = new Withdrawal_Detail_Page(
			$withdrawal_repository,
			$event_repository,
			$audit_repository
		);
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_status_change' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the WooCommerce submenu page.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Contract Withdrawals', EU_WITHDRAWAL_TEXT_DOMAIN ),
			__( 'Retrageri contract', EU_WITHDRAWAL_TEXT_DOMAIN ),
			EU_WITHDRAWAL_CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle manual status change submissions from the detail page.
	 *
	 * @return void
	 */
	public function handle_status_change(): void {
		if ( ! isset( $_POST['eu_withdrawal_change_status'] ) ) {
			return;
		}

		if ( ! current_user_can( EU_WITHDRAWAL_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', EU_WITHDRAWAL_TEXT_DOMAIN ) );
		}

		$request_id = isset( $_POST['request_id'] ) ? absint( wp_unslash( $_POST['request_id'] ) ) : 0;
		$new_status = isset( $_POST['new_status'] ) ? sanitize_key( wp_unslash( $_POST['new_status'] ) ) : '';

		if ( $request_id <= 0 ) {
			return;
		}

		check_admin_referer( self::STATUS_NONCE_ACTION . '_' . $request_id, 'eu_withdrawal_status_nonce' );

		$result = $this->detail_page->change_status( $request_id, $new_status );

		$redirect_args = array(
			'page'       => self::PAGE_SLUG,
			'action'     => 'view',
			'request_id' => $request_id,
		);

		if ( is_wp_error( $result ) ) {
			$redirect_args['status_error'] = 1;
		} else {
			$redirect_args['status_updated'] = 1;
		}

		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Enqueue admin dashboard assets on plugin and order screens.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$is_plugin_page = ( 'woocommerce_page_' . self::PAGE_SLUG ) === $hook_suffix;
		$is_order_page    = in_array( $hook_suffix, array( 'post.php', 'post-new.php', 'woocommerce_page_wc-orders' ), true );

		if ( ! $is_plugin_page && ! $is_order_page ) {
			return;
		}

		wp_enqueue_style(
			'eu-withdrawal-admin',
			EU_WITHDRAWAL_URL . 'assets/css/admin-dashboard.css',
			array(),
			EU_WITHDRAWAL_VERSION
		);

		wp_enqueue_script(
			'eu-withdrawal-admin',
			EU_WITHDRAWAL_URL . 'assets/js/admin-dashboard.js',
			array(),
			EU_WITHDRAWAL_VERSION,
			true
		);

		wp_localize_script(
			'eu-withdrawal-admin',
			'euWithdrawalAdmin',
			array(
				'i18n' => array(
					'confirmReject'   => __( 'Are you sure you want to reject this withdrawal request?', EU_WITHDRAWAL_TEXT_DOMAIN ),
					'confirmProcessed'=> __( 'Are you sure you want to mark this request as processed?', EU_WITHDRAWAL_TEXT_DOMAIN ),
					'confirmRefunded' => __( 'Are you sure you want to mark this request as refunded?', EU_WITHDRAWAL_TEXT_DOMAIN ),
					'confirmPending'  => __( 'Are you sure you want to set this request back to pending?', EU_WITHDRAWAL_TEXT_DOMAIN ),
					'confirmGeneric'  => __( 'Are you sure you want to change the status of this request?', EU_WITHDRAWAL_TEXT_DOMAIN ),
				),
			)
		);
	}

	/**
	 * Render the list or detail admin page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( EU_WITHDRAWAL_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', EU_WITHDRAWAL_TEXT_DOMAIN ) );
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';

		if ( 'view' === $action ) {
			$request_id = isset( $_GET['request_id'] ) ? absint( wp_unslash( $_GET['request_id'] ) ) : 0;
			$this->detail_page->render( $request_id );
			return;
		}

		$this->render_list_page();
	}

	/**
	 * Render the withdrawals list table page.
	 *
	 * @return void
	 */
	private function render_list_page(): void {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

		$list_table = new Withdrawals_List_Table( $this->withdrawal_repository );
		$list_table->prepare_items();

		echo '<div class="wrap eu-wd-admin">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Contract Withdrawals', EU_WITHDRAWAL_TEXT_DOMAIN ) . '</h1>';
		$list_table->render_export_buttons();
		echo '<hr class="wp-header-end">';

		$list_table->render_status_views();

		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="' . esc_attr( self::PAGE_SLUG ) . '">';
		$list_table->search_box( __( 'Search Requests', EU_WITHDRAWAL_TEXT_DOMAIN ), 'eu-withdrawal-search' );
		$list_table->display();
		echo '</form>';
		echo '</div>';
	}
}
