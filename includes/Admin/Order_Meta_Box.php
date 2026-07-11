<?php
/**
 * Order edit screen meta box linking orders to withdrawal requests.
 *
 * @package EUWithdrawal\Admin
 */

namespace EUWithdrawal\Admin;

use EUWithdrawal\Data\Withdrawal_Repository;
use EUWithdrawal\Domain\Withdrawal_Status;
use EUWithdrawal\WooCommerce\Hpos_Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Meta_Box
 */
final class Order_Meta_Box {

	/**
	 * Meta box ID.
	 */
	public const META_BOX_ID = 'eu-withdrawal-order';

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
	 * Register meta box hooks for classic and HPOS order screens.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ), 10, 2 );
	}

	/**
	 * Register the meta box on supported order screen IDs.
	 *
	 * @param string                    $post_type     Screen/post type identifier.
	 * @param \WP_Post|\WC_Order|null $post_or_order Post or order object.
	 * @return void
	 */
	public function register_meta_boxes( string $post_type, $post_or_order = null ): void {
		unset( $post_or_order );

		if ( ! in_array( $post_type, Hpos_Compatibility::get_order_screen_ids(), true ) ) {
			return;
		}

		add_meta_box(
			self::META_BOX_ID,
			__( 'Contract Withdrawal', EU_WITHDRAWAL_TEXT_DOMAIN ),
			array( $this, 'render' ),
			$post_type,
			'side',
			'default'
		);
	}

	/**
	 * Render the meta box contents.
	 *
	 * @param \WP_Post|\WC_Order $post_or_order Post or order object.
	 * @return void
	 */
	public function render( $post_or_order ): void {
		$order_id = $this->resolve_order_id( $post_or_order );

		if ( $order_id <= 0 ) {
			echo '<p>' . esc_html__( 'Unable to determine order ID.', EU_WITHDRAWAL_TEXT_DOMAIN ) . '</p>';
			return;
		}

		$request = $this->withdrawal_repository->find_by_order_id( $order_id );

		if ( null === $request ) {
			echo '<p>' . esc_html__( 'No withdrawal request is linked to this order.', EU_WITHDRAWAL_TEXT_DOMAIN ) . '</p>';
			return;
		}

		$request_id = (int) ( $request['id'] ?? 0 );
		$status     = (string) ( $request['status'] ?? Withdrawal_Status::PENDING );
		$detail_url = add_query_arg(
			array(
				'page'       => Admin::PAGE_SLUG,
				'action'     => 'view',
				'request_id' => $request_id,
			),
			admin_url( 'admin.php' )
		);

		echo '<div class="eu-wd-order-metabox">';
		echo '<p><strong>' . esc_html__( 'Request', EU_WITHDRAWAL_TEXT_DOMAIN ) . ':</strong> #' . esc_html( (string) $request_id ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Status', EU_WITHDRAWAL_TEXT_DOMAIN ) . ':</strong> ';
		echo '<span class="eu-wd-status eu-wd-status--' . esc_attr( $status ) . '">' . esc_html( Withdrawal_Status::label( $status ) ) . '</span></p>';
		echo '<p><strong>' . esc_html__( 'Customer', EU_WITHDRAWAL_TEXT_DOMAIN ) . ':</strong> ' . esc_html( (string) ( $request['customer_name'] ?? '' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Submitted', EU_WITHDRAWAL_TEXT_DOMAIN ) . ':</strong> ' . esc_html( $this->format_datetime( (string) ( $request['submitted_at'] ?? '' ) ) ) . '</p>';
		printf(
			'<p><a class="button button-secondary" href="%1$s">%2$s</a></p>',
			esc_url( $detail_url ),
			esc_html__( 'View Withdrawal Details', EU_WITHDRAWAL_TEXT_DOMAIN )
		);
		echo '</div>';
	}

	/**
	 * Resolve the WooCommerce order ID from a post or order object.
	 *
	 * @param \WP_Post|\WC_Order|mixed $post_or_order Post or order object.
	 * @return int
	 */
	private function resolve_order_id( $post_or_order ): int {
		if ( $post_or_order instanceof \WC_Order ) {
			return $post_or_order->get_id();
		}

		if ( $post_or_order instanceof \WP_Post ) {
			return (int) $post_or_order->ID;
		}

		return 0;
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
}
