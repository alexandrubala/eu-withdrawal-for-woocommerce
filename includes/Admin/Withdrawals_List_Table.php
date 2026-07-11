<?php
/**
 * WP_List_Table implementation for withdrawal requests.
 *
 * @package EUWithdrawal\Admin
 */

namespace EUWithdrawal\Admin;

use EUWithdrawal\Data\Withdrawal_Repository;
use EUWithdrawal\Domain\Withdrawal_Status;
use EUWithdrawal\WooCommerce\Hpos_Compatibility;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Withdrawals_List_Table
 */
final class Withdrawals_List_Table extends \WP_List_Table {

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

		parent::__construct(
			array(
				'singular' => 'eu-withdrawal',
				'plural'   => 'eu-withdrawals',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Define table columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return array(
			'id'            => __( 'ID', 'eu-withdrawal-for-woocommerce' ),
			'uuid'          => __( 'UUID', 'eu-withdrawal-for-woocommerce' ),
			'customer_name' => __( 'Customer Name', 'eu-withdrawal-for-woocommerce' ),
			'order'         => __( 'Order', 'eu-withdrawal-for-woocommerce' ),
			'status'        => __( 'Status', 'eu-withdrawal-for-woocommerce' ),
			'submitted_at'  => __( 'Date', 'eu-withdrawal-for-woocommerce' ),
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array<string, array<int, bool|string>>
	 */
	protected function get_sortable_columns(): array {
		return array(
			'id'            => array( 'id', false ),
			'customer_name' => array( 'customer_name', false ),
			'status'        => array( 'status', false ),
			'submitted_at'  => array( 'submitted_at', true ),
		);
	}

	/**
	 * Status filter views.
	 *
	 * @return array<string, string>
	 */
	public function get_status_views(): array {
		$current_status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$base_url       = admin_url( 'admin.php?page=' . Admin::PAGE_SLUG );
		$views          = array();

		$all_label = __( 'All', 'eu-withdrawal-for-woocommerce' );
		$views['all'] = sprintf(
			'<a href="%1$s"%2$s>%3$s</a>',
			esc_url( $base_url ),
			'' === $current_status ? ' class="current"' : '',
			esc_html( $all_label )
		);

		foreach ( Withdrawal_Status::all() as $status ) {
			$url = add_query_arg( 'status', $status, $base_url );
			$views[ $status ] = sprintf(
				'<a href="%1$s"%2$s>%3$s</a>',
				esc_url( $url ),
				$current_status === $status ? ' class="current"' : '',
				esc_html( Withdrawal_Status::label( $status ) )
			);
		}

		return $views;
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$per_page     = 20;
		$current_page = max( 1, $this->get_pagenum() );
		$offset       = ( $current_page - 1 ) * $per_page;

		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'submitted_at';
		$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'desc';
		$status  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$search  = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		if ( '' !== $status && ! Withdrawal_Status::is_valid( $status ) ) {
			$status = '';
		}

		$result = $this->withdrawal_repository->query(
			array(
				'status'   => $status,
				'search'   => $search,
				'orderby'  => $orderby,
				'order'    => $order,
				'per_page' => $per_page,
				'offset'   => $offset,
			)
		);

		$this->items = $result['items'];

		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $result['total'] / $per_page ),
			)
		);
	}

	/**
	 * Default column renderer.
	 *
	 * @param array<string, mixed> $item        Row data.
	 * @param string               $column_name Column key.
	 * @return string
	 */
	protected function column_default( $item, $column_name ): string {
		return esc_html( (string) ( $item[ $column_name ] ?? '' ) );
	}

	/**
	 * Render the ID column with detail link.
	 *
	 * @param array<string, mixed> $item Row data.
	 * @return string
	 */
	protected function column_id( array $item ): string {
		$request_id = (int) ( $item['id'] ?? 0 );
		$detail_url = add_query_arg(
			array(
				'page'       => Admin::PAGE_SLUG,
				'action'     => 'view',
				'request_id' => $request_id,
			),
			admin_url( 'admin.php' )
		);

		return sprintf(
			'<a href="%1$s"><strong>#%2$d</strong></a>',
			esc_url( $detail_url ),
			$request_id
		);
	}

	/**
	 * Render the UUID column (truncated).
	 *
	 * @param array<string, mixed> $item Row data.
	 * @return string
	 */
	protected function column_uuid( array $item ): string {
		$uuid = (string) ( $item['uuid'] ?? '' );

		if ( strlen( $uuid ) <= 13 ) {
			return esc_html( $uuid );
		}

		$truncated = substr( $uuid, 0, 8 ) . '…' . substr( $uuid, -4 );

		return sprintf(
			'<code title="%1$s">%2$s</code>',
			esc_attr( $uuid ),
			esc_html( $truncated )
		);
	}

	/**
	 * Render the order column with HPOS-compatible edit link.
	 *
	 * @param array<string, mixed> $item Row data.
	 * @return string
	 */
	protected function column_order( array $item ): string {
		$order_id     = (int) ( $item['order_id'] ?? 0 );
		$order_number = (string) ( $item['order_number'] ?? '' );

		if ( $order_id <= 0 ) {
			return esc_html( $order_number ?: '—' );
		}

		$edit_url = Hpos_Compatibility::get_order_edit_url( $order_id );
		$label    = $order_number ?: '#' . $order_id;

		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $edit_url ),
			esc_html( $label )
		);
	}

	/**
	 * Render the status column with badge.
	 *
	 * @param array<string, mixed> $item Row data.
	 * @return string
	 */
	protected function column_status( array $item ): string {
		$status = (string) ( $item['status'] ?? Withdrawal_Status::PENDING );

		return sprintf(
			'<span class="eu-wd-status eu-wd-status--%1$s">%2$s</span>',
			esc_attr( $status ),
			esc_html( Withdrawal_Status::label( $status ) )
		);
	}

	/**
	 * Render the submitted date column.
	 *
	 * @param array<string, mixed> $item Row data.
	 * @return string
	 */
	protected function column_submitted_at( array $item ): string {
		$submitted_at = (string) ( $item['submitted_at'] ?? '' );

		if ( '' === $submitted_at || '0000-00-00 00:00:00' === $submitted_at ) {
			return '—';
		}

		$timestamp = strtotime( $submitted_at );

		if ( false === $timestamp ) {
			return esc_html( $submitted_at );
		}

		return esc_html(
			wp_date(
				get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
				$timestamp
			)
		);
	}

	/**
	 * Render export action buttons above the list table.
	 *
	 * @return void
	 */
	public function render_export_buttons(): void {
		$csv_url   = $this->get_export_url( Export_Controller::ACTION_EXPORT_CSV );
		$print_url = $this->get_export_url( Export_Controller::ACTION_PRINT_VIEW );

		echo '<span class="eu-wd-export-actions">';

		printf(
			'<a href="%1$s" class="page-title-action">%2$s</a>',
			esc_url( $csv_url ),
			esc_html__( 'Export CSV', 'eu-withdrawal-for-woocommerce' )
		);

		printf(
			'<a href="%1$s" class="page-title-action" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $print_url ),
			esc_html__( 'Print / PDF', 'eu-withdrawal-for-woocommerce' )
		);

		echo '</span>';
	}

	/**
	 * Build an export URL preserving current list filters.
	 *
	 * @param string $action Export action slug.
	 * @return string
	 */
	private function get_export_url( string $action ): string {
		$args = array(
			'page'                         => Admin::PAGE_SLUG,
			'eu_withdrawal_action'         => $action,
			'eu_withdrawal_export_nonce'   => wp_create_nonce( Export_Controller::EXPORT_NONCE_ACTION ),
		);

		if ( isset( $_GET['status'] ) ) {
			$status = sanitize_key( wp_unslash( $_GET['status'] ) );

			if ( '' !== $status && Withdrawal_Status::is_valid( $status ) ) {
				$args['status'] = $status;
			}
		}

		if ( isset( $_REQUEST['s'] ) ) {
			$search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );

			if ( '' !== $search ) {
				$args['s'] = $search;
			}
		}

		if ( isset( $_GET['orderby'] ) ) {
			$args['orderby'] = sanitize_key( wp_unslash( $_GET['orderby'] ) );
		}

		if ( isset( $_GET['order'] ) ) {
			$args['order'] = sanitize_key( wp_unslash( $_GET['order'] ) );
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Message when no items exist.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No withdrawal requests found.', 'eu-withdrawal-for-woocommerce' );
	}

	/**
	 * Render status filter links above the table.
	 *
	 * @return void
	 */
	public function render_status_views(): void {
		$views = $this->get_status_views();

		if ( empty( $views ) ) {
			return;
		}

		echo '<ul class="subsubsub">';

		$links = array();
		foreach ( $views as $class => $html ) {
			$links[] = '<li class="' . esc_attr( $class ) . '">' . $html . '</li>';
		}

		echo wp_kses_post( implode( ' | ', $links ) );
		echo '</ul>';
	}
}
