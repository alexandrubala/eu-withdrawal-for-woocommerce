<?php
/**
 * Single withdrawal request detail view with timeline and status controls.
 *
 * @package EUWithdrawal\Admin
 */

namespace EUWithdrawal\Admin;

use EUWithdrawal\Data\Audit_Repository;
use EUWithdrawal\Data\Event_Repository;
use EUWithdrawal\Data\Withdrawal_Repository;
use EUWithdrawal\Domain\Event_Type;
use EUWithdrawal\Domain\Withdrawal_Status;
use EUWithdrawal\WooCommerce\Hpos_Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Class Withdrawal_Detail_Page
 */
final class Withdrawal_Detail_Page {

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
	}

	/**
	 * Render the detail page for a withdrawal request.
	 *
	 * @param int $request_id Request ID.
	 * @return void
	 */
	public function render( int $request_id ): void {
		$request = $this->withdrawal_repository->find_by_id( $request_id );

		if ( null === $request ) {
			echo '<div class="wrap eu-wd-admin">';
			echo '<h1>' . esc_html__( 'Withdrawal Request', 'eu-withdrawal-for-woocommerce' ) . '</h1>';
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Withdrawal request not found.', 'eu-withdrawal-for-woocommerce' ) . '</p></div>';
			echo '</div>';
			return;
		}

		$events     = $this->event_repository->find_by_request_id( $request_id );
		$audit      = $this->audit_repository->get_by_request_uuid( (string) ( $request['uuid'] ?? '' ) );
		$products   = $this->decode_products( (string) ( $request['products_json'] ?? '' ) );
		$list_url   = admin_url( 'admin.php?page=' . Admin::PAGE_SLUG );
		$order_id   = (int) ( $request['order_id'] ?? 0 );
		$order_url  = Hpos_Compatibility::get_order_edit_url( $order_id );
		$status     = (string) ( $request['status'] ?? Withdrawal_Status::PENDING );

		echo '<div class="wrap eu-wd-admin eu-wd-detail">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Withdrawal Request', 'eu-withdrawal-for-woocommerce' ) . ' #' . esc_html( (string) $request_id ) . '</h1>';
		echo ' <span class="eu-wd-status eu-wd-status--' . esc_attr( $status ) . '">' . esc_html( Withdrawal_Status::label( $status ) ) . '</span>';
		echo '<hr class="wp-header-end">';

		if ( isset( $_GET['status_updated'] ) && 1 === absint( wp_unslash( $_GET['status_updated'] ) ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Status updated successfully.', 'eu-withdrawal-for-woocommerce' ) . '</p></div>';
		}

		if ( isset( $_GET['status_error'] ) && 1 === absint( wp_unslash( $_GET['status_error'] ) ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Could not update the request status.', 'eu-withdrawal-for-woocommerce' ) . '</p></div>';
		}

		printf(
			'<p><a href="%1$s">&larr; %2$s</a></p>',
			esc_url( $list_url ),
			esc_html__( 'Back to all requests', 'eu-withdrawal-for-woocommerce' )
		);

		echo '<div class="eu-wd-detail__grid">';

		echo '<div class="eu-wd-detail__main">';

		$this->render_section(
			__( 'Customer Details', 'eu-withdrawal-for-woocommerce' ),
			array(
				__( 'Name', 'eu-withdrawal-for-woocommerce' )  => (string) ( $request['customer_name'] ?? '' ),
				__( 'Email', 'eu-withdrawal-for-woocommerce' ) => (string) ( $request['customer_email'] ?? '' ),
				__( 'Phone', 'eu-withdrawal-for-woocommerce' ) => (string) ( $request['customer_phone'] ?? '—' ),
			)
		);

		$this->render_section(
			__( 'Order', 'eu-withdrawal-for-woocommerce' ),
			array(
				__( 'Order Number', 'eu-withdrawal-for-woocommerce' ) => $order_id > 0 && '' !== $order_url
					? sprintf(
						'<a href="%1$s">%2$s</a>',
						esc_url( $order_url ),
						esc_html( (string) ( $request['order_number'] ?? '#' . $order_id ) )
					)
					: (string) ( $request['order_number'] ?? '—' ),
				__( 'Submitted', 'eu-withdrawal-for-woocommerce' ) => $this->format_datetime( (string) ( $request['submitted_at'] ?? '' ) ),
			),
			true
		);

		if ( ! empty( $request['reason'] ) ) {
			$this->render_section(
				__( 'Reason', 'eu-withdrawal-for-woocommerce' ),
				array(
					'' => nl2br( esc_html( (string) $request['reason'] ) ),
				),
				true
			);
		}

		$this->render_products_section( $products );
		$this->render_timeline( $events );

		echo '</div>';

		echo '<div class="eu-wd-detail__sidebar">';

		$this->render_status_form( $request_id, $status );
		$this->render_audit_section( $audit, (string) ( $request['uuid'] ?? '' ) );

		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Change withdrawal status and record an audit event.
	 *
	 * @param int    $request_id Request ID.
	 * @param string $new_status Target status slug.
	 * @return true|\WP_Error
	 */
	public function change_status( int $request_id, string $new_status ) {
		if ( ! Withdrawal_Status::is_valid( $new_status ) ) {
			return new \WP_Error( 'invalid_status', __( 'Invalid status.', 'eu-withdrawal-for-woocommerce' ) );
		}

		$request = $this->withdrawal_repository->find_by_id( $request_id );

		if ( null === $request ) {
			return new \WP_Error( 'not_found', __( 'Withdrawal request not found.', 'eu-withdrawal-for-woocommerce' ) );
		}

		$old_status = (string) ( $request['status'] ?? '' );

		if ( $old_status === $new_status ) {
			return true;
		}

		$updated = $this->withdrawal_repository->update_status( $request_id, $new_status );

		if ( ! $updated ) {
			return new \WP_Error( 'update_failed', __( 'Could not update status.', 'eu-withdrawal-for-woocommerce' ) );
		}

		$user_id = get_current_user_id();

		$this->event_repository->insert(
			array(
				'request_id' => $request_id,
				'event_type' => Event_Type::STATUS_CHANGED,
				'actor_type' => 'admin',
				'actor_id'   => $user_id > 0 ? $user_id : null,
				'message'    => sprintf(
					/* translators: 1: old status label, 2: new status label */
					__( 'Status changed from %1$s to %2$s.', 'eu-withdrawal-for-woocommerce' ),
					Withdrawal_Status::label( $old_status ),
					Withdrawal_Status::label( $new_status )
				),
				'meta_json'  => wp_json_encode(
					array(
						'old_status' => $old_status,
						'new_status' => $new_status,
					)
				),
			)
		);

		return true;
	}

	/**
	 * Render a labelled detail section.
	 *
	 * @param string               $title      Section title.
	 * @param array<string, string> $fields    Label => value pairs.
	 * @param bool                 $allow_html Whether values may contain HTML.
	 * @return void
	 */
	private function render_section( string $title, array $fields, bool $allow_html = false ): void {
		echo '<div class="eu-wd-card">';
		echo '<h2>' . esc_html( $title ) . '</h2>';
		echo '<table class="widefat striped eu-wd-detail-table">';

		foreach ( $fields as $label => $value ) {
			echo '<tr>';
			if ( '' !== $label ) {
				echo '<th scope="row">' . esc_html( $label ) . '</th>';
			}
			echo '<td' . ( '' === $label ? ' colspan="2"' : '' ) . '>';
			echo $allow_html ? wp_kses_post( $value ) : esc_html( $value );
			echo '</td>';
			echo '</tr>';
		}

		echo '</table>';
		echo '</div>';
	}

	/**
	 * Render the products section from stored JSON.
	 *
	 * @param array<int, array<string, mixed>> $products Product rows.
	 * @return void
	 */
	private function render_products_section( array $products ): void {
		echo '<div class="eu-wd-card">';
		echo '<h2>' . esc_html__( 'Products', 'eu-withdrawal-for-woocommerce' ) . '</h2>';

		if ( empty( $products ) ) {
			echo '<p>' . esc_html__( 'No products recorded.', 'eu-withdrawal-for-woocommerce' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Product', 'eu-withdrawal-for-woocommerce' ) . '</th>';
		echo '<th>' . esc_html__( 'SKU', 'eu-withdrawal-for-woocommerce' ) . '</th>';
		echo '<th>' . esc_html__( 'Qty', 'eu-withdrawal-for-woocommerce' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $products as $product ) {
			echo '<tr>';
			echo '<td>' . esc_html( (string) ( $product['name'] ?? '' ) ) . '</td>';
			echo '<td>' . esc_html( (string) ( $product['sku'] ?? '—' ) ) . '</td>';
			echo '<td>' . esc_html( (string) ( $product['quantity'] ?? '' ) ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	/**
	 * Render the event timeline.
	 *
	 * @param array<int, array<string, mixed>> $events Event rows.
	 * @return void
	 */
	private function render_timeline( array $events ): void {
		echo '<div class="eu-wd-card">';
		echo '<h2>' . esc_html__( 'Timeline', 'eu-withdrawal-for-woocommerce' ) . '</h2>';

		if ( empty( $events ) ) {
			echo '<p>' . esc_html__( 'No events recorded yet.', 'eu-withdrawal-for-woocommerce' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<ol class="eu-wd-timeline">';

		foreach ( $events as $event ) {
			$event_type = (string) ( $event['event_type'] ?? '' );
			$created_at = $this->format_datetime( (string) ( $event['created_at'] ?? '' ) );
			$actor      = $this->format_actor( $event );

			echo '<li class="eu-wd-timeline__item eu-wd-timeline__item--' . esc_attr( $event_type ) . '">';
			echo '<div class="eu-wd-timeline__marker" aria-hidden="true"></div>';
			echo '<div class="eu-wd-timeline__content">';
			echo '<div class="eu-wd-timeline__header">';
			echo '<strong>' . esc_html( Event_Type::label( $event_type ) ) . '</strong>';
			echo '<time datetime="' . esc_attr( (string) ( $event['created_at'] ?? '' ) ) . '">' . esc_html( $created_at ) . '</time>';
			echo '</div>';
			echo '<p class="eu-wd-timeline__message">' . esc_html( (string) ( $event['message'] ?? '' ) ) . '</p>';

			if ( '' !== $actor ) {
				echo '<p class="eu-wd-timeline__actor">' . esc_html( $actor ) . '</p>';
			}

			echo '</div>';
			echo '</li>';
		}

		echo '</ol>';
		echo '</div>';
	}

	/**
	 * Render the manual status change form.
	 *
	 * @param int    $request_id Request ID.
	 * @param string $status     Current status.
	 * @return void
	 */
	private function render_status_form( int $request_id, string $status ): void {
		echo '<div class="eu-wd-card">';
		echo '<h2>' . esc_html__( 'Change Status', 'eu-withdrawal-for-woocommerce' ) . '</h2>';
		echo '<form method="post" class="eu-wd-status-form" data-current-status="' . esc_attr( $status ) . '">';
		wp_nonce_field( Admin::STATUS_NONCE_ACTION . '_' . $request_id, 'eu_withdrawal_status_nonce' );
		echo '<input type="hidden" name="request_id" value="' . esc_attr( (string) $request_id ) . '">';
		echo '<input type="hidden" name="eu_withdrawal_change_status" value="1">';

		echo '<label for="eu-wd-new-status" class="screen-reader-text">' . esc_html__( 'New status', 'eu-withdrawal-for-woocommerce' ) . '</label>';
		echo '<select name="new_status" id="eu-wd-new-status" class="widefat">';

		foreach ( Withdrawal_Status::all() as $status_option ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $status_option ),
				selected( $status, $status_option, false ),
				esc_html( Withdrawal_Status::label( $status_option ) )
			);
		}

		echo '</select>';
		printf(
			'<button type="submit" class="button button-primary eu-wd-status-submit" style="margin-top: 10px;">%s</button>',
			esc_html__( 'Update Status', 'eu-withdrawal-for-woocommerce' )
		);
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render audit hash details (read-only proof).
	 *
	 * @param array<string, mixed>|null $audit        Audit row.
	 * @param string                    $request_uuid Request UUID.
	 * @return void
	 */
	private function render_audit_section( ?array $audit, string $request_uuid ): void {
		echo '<div class="eu-wd-card eu-wd-audit">';
		echo '<h2>' . esc_html__( 'Audit Proof', 'eu-withdrawal-for-woocommerce' ) . '</h2>';

		if ( null === $audit ) {
			echo '<p>' . esc_html__( 'No audit record found for this request.', 'eu-withdrawal-for-woocommerce' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<dl class="eu-wd-audit__list">';
		echo '<dt>' . esc_html__( 'Request UUID', 'eu-withdrawal-for-woocommerce' ) . '</dt>';
		echo '<dd><code>' . esc_html( $request_uuid ) . '</code></dd>';

		echo '<dt>' . esc_html__( 'Payload Hash', 'eu-withdrawal-for-woocommerce' ) . '</dt>';
		echo '<dd><code class="eu-wd-hash">' . esc_html( (string) ( $audit['payload_hash'] ?? '' ) ) . '</code></dd>';

		echo '<dt>' . esc_html__( 'Security Hash', 'eu-withdrawal-for-woocommerce' ) . '</dt>';
		echo '<dd><code class="eu-wd-hash">' . esc_html( (string) ( $audit['security_hash'] ?? '' ) ) . '</code></dd>';

		if ( ! empty( $audit['previous_hash'] ) ) {
			echo '<dt>' . esc_html__( 'Previous Hash', 'eu-withdrawal-for-woocommerce' ) . '</dt>';
			echo '<dd><code class="eu-wd-hash">' . esc_html( (string) $audit['previous_hash'] ) . '</code></dd>';
		}

		echo '<dt>' . esc_html__( 'Recorded At', 'eu-withdrawal-for-woocommerce' ) . '</dt>';
		echo '<dd>' . esc_html( $this->format_datetime( (string) ( $audit['recorded_at'] ?? '' ) ) ) . '</dd>';
		echo '</dl>';
		echo '</div>';
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

	/**
	 * Format a MySQL datetime for display.
	 *
	 * @param string $datetime MySQL datetime string.
	 * @return string
	 */
	private function format_datetime( string $datetime ): string {
		if ( '' === $datetime || '0000-00-00 00:00:00' === $datetime ) {
			return '—';
		}

		$timestamp = strtotime( $datetime );

		if ( false === $timestamp ) {
			return $datetime;
		}

		return wp_date(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			$timestamp
		);
	}

	/**
	 * Build a human-readable actor label for an event.
	 *
	 * @param array<string, mixed> $event Event row.
	 * @return string
	 */
	private function format_actor( array $event ): string {
		$actor_type = (string) ( $event['actor_type'] ?? '' );
		$actor_id   = isset( $event['actor_id'] ) ? (int) $event['actor_id'] : 0;

		if ( 'customer' === $actor_type ) {
			return __( 'Actor: Customer', 'eu-withdrawal-for-woocommerce' );
		}

		if ( 'admin' === $actor_type && $actor_id > 0 ) {
			$user = get_userdata( $actor_id );

			if ( $user instanceof \WP_User ) {
				return sprintf(
					/* translators: %s: admin display name */
					__( 'Actor: %s', 'eu-withdrawal-for-woocommerce' ),
					$user->display_name
				);
			}
		}

		if ( 'system' === $actor_type ) {
			return __( 'Actor: System', 'eu-withdrawal-for-woocommerce' );
		}

		return '';
	}
}
