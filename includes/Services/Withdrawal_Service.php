<?php
/**
 * Orchestrates withdrawal submission: persistence, audit log, and email.
 *
 * @package EUWithdrawal\Services
 */

namespace EUWithdrawal\Services;

use EUWithdrawal\Data\Audit_Repository;
use EUWithdrawal\Data\Event_Repository;
use EUWithdrawal\Data\Withdrawal_Repository;
use EUWithdrawal\Domain\Request_Type;
use EUWithdrawal\Domain\Step1_Input;
use EUWithdrawal\Security\Audit_Hash;

defined( 'ABSPATH' ) || exit;

/**
 * Class Withdrawal_Service
 */
final class Withdrawal_Service {

	/**
	 * Withdrawal requests repository.
	 *
	 * @var Withdrawal_Repository
	 */
	private Withdrawal_Repository $withdrawal_repository;

	/**
	 * Audit log repository.
	 *
	 * @var Audit_Repository
	 */
	private Audit_Repository $audit_repository;

	/**
	 * Events repository.
	 *
	 * @var Event_Repository
	 */
	private Event_Repository $event_repository;

	/**
	 * Audit hash generator.
	 *
	 * @var Audit_Hash
	 */
	private Audit_Hash $audit_hash;

	/**
	 * Email sender.
	 *
	 * @var Email_Service
	 */
	private Email_Service $email_service;

	/**
	 * Constructor.
	 *
	 * @param Withdrawal_Repository $withdrawal_repository Withdrawal persistence.
	 * @param Audit_Repository      $audit_repository      Audit log persistence.
	 * @param Event_Repository      $event_repository      Event persistence.
	 * @param Audit_Hash            $audit_hash            Hash generator.
	 * @param Email_Service         $email_service         Customer email sender.
	 */
	public function __construct(
		Withdrawal_Repository $withdrawal_repository,
		Audit_Repository $audit_repository,
		Event_Repository $event_repository,
		Audit_Hash $audit_hash,
		Email_Service $email_service
	) {
		$this->withdrawal_repository = $withdrawal_repository;
		$this->audit_repository      = $audit_repository;
		$this->event_repository      = $event_repository;
		$this->audit_hash            = $audit_hash;
		$this->email_service         = $email_service;
	}

	/**
	 * Persist a confirmed withdrawal request with audit trail and email.
	 *
	 * @param Step1_Input $input        Step 1 form data.
	 * @param string      $request_uuid Generated request UUID.
	 * @param string      $submitted_at MySQL datetime of submission.
	 * @param string      $ip_address   Client IP address.
	 * @param string      $user_agent   Client user agent.
	 * @return int Inserted request ID, or 0 on failure.
	 */
	public function submit(
		Step1_Input $input,
		string $request_uuid,
		string $submitted_at,
		string $ip_address = '',
		string $user_agent = ''
	): int {
		$products = ! empty( $input->selected_products )
			? $input->selected_products
			: $this->resolve_order_products( $input->order_id );

		$products_json = wp_json_encode( $products );
		$locale        = determine_locale();
		$request_type  = Request_Type::is_valid( $input->request_type )
			? $input->request_type
			: Request_Type::REFUND;

		$courier_notes = $input->courier_notes;
		if ( '' === $courier_notes && Request_Type::RETURN === $request_type ) {
			$courier_notes = Settings::courier_instructions_html();
		}

		$request_id = $this->withdrawal_repository->insert(
			array(
				'uuid'                => $request_uuid,
				'order_id'            => $input->order_id,
				'order_number'        => $input->order_number,
				'customer_name'       => $input->name,
				'customer_email'      => $input->email,
				'customer_phone'      => $input->phone,
				'products_json'       => $products_json,
				'reason'              => $input->reason,
				'request_type'        => $request_type,
				'refund_iban'         => $input->refund_iban,
				'refund_account_name' => $input->refund_account_name,
				'courier_notes'       => $courier_notes,
				'status'              => 'pending',
				'locale'              => $locale,
				'submitted_at'        => $submitted_at,
			)
		);

		if ( 0 === $request_id ) {
			return 0;
		}

		$audit_payload = array(
			'uuid'                => $request_uuid,
			'order_id'            => $input->order_id,
			'order_number'        => $input->order_number,
			'customer_name'       => $input->name,
			'customer_email'      => $input->email,
			'customer_phone'      => $input->phone,
			'products'            => $products,
			'reason'              => $input->reason,
			'request_type'        => $request_type,
			'refund_iban'         => $input->refund_iban,
			'refund_account_name' => $input->refund_account_name,
			'status'              => 'pending',
			'locale'              => $locale,
			'submitted_at'        => $submitted_at,
			'ip_address'          => $ip_address,
			'user_agent'          => $user_agent,
		);

		$hashes        = $this->audit_hash->generate( $audit_payload, $request_uuid, $submitted_at );
		$previous_hash = $this->audit_repository->get_latest_security_hash();

		$audit_id = $this->audit_repository->insert(
			array(
				'request_uuid'   => $request_uuid,
				'order_id'       => $input->order_id,
				'customer_email' => $input->email,
				'ip_address'     => $ip_address,
				'user_agent'     => mb_substr( $user_agent, 0, 500 ),
				'payload_hash'   => $hashes['payload_hash'],
				'security_hash'  => $hashes['security_hash'],
				'previous_hash'  => $previous_hash,
				'recorded_at'    => $submitted_at,
			)
		);

		if ( 0 === $audit_id ) {
			return 0;
		}

		$this->event_repository->insert(
			array(
				'request_id'  => $request_id,
				'event_type'  => 'request_submitted',
				'actor_type'  => 'customer',
				'message'     => __( 'Withdrawal request submitted by customer.', 'eu-withdrawal-for-woocommerce' ),
				'meta_json'   => wp_json_encode(
					array(
						'request_uuid' => $request_uuid,
						'audit_id'     => $audit_id,
						'request_type' => $request_type,
					)
				),
			)
		);

		$email_sent = $this->email_service->send_customer_confirmation(
			$input->email,
			array(
				'request_uuid'  => $request_uuid,
				'order_number'  => $input->order_number,
				'submitted_at'  => $submitted_at,
				'customer_name' => $input->name,
				'customer_email'=> $input->email,
				'customer_phone'=> $input->phone,
				'reason'        => $input->reason,
				'products'      => $products,
				'request_type'  => $request_type,
			)
		);

		if ( ! $email_sent ) {
			$this->event_repository->insert(
				array(
					'request_id' => $request_id,
					'event_type' => 'email_failed',
					'actor_type' => 'system',
					'message'    => __( 'Customer confirmation email could not be sent.', 'eu-withdrawal-for-woocommerce' ),
				)
			);
		}

		return $request_id;
	}

	/**
	 * Build rich product snapshots from a WooCommerce order.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function resolve_order_products( int $order_id ): array {
		if ( $order_id <= 0 ) {
			return array();
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return array();
		}

		$products = array();

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$products[] = $this->snapshot_item( (int) $item_id, $item );
		}

		return $products;
	}

	/**
	 * Build product snapshots for selected line items.
	 *
	 * @param int                  $order_id   Order ID.
	 * @param array<int, string>   $item_keys  Selected item IDs as strings.
	 * @param array<string, int>   $quantities Qty keyed by item ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function resolve_selected_products( int $order_id, array $item_keys, array $quantities = array() ): array {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return array();
		}

		$selected = array();

		foreach ( $item_keys as $item_key ) {
			$item_id = absint( $item_key );
			$item    = $order->get_item( $item_id );

			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$snapshot = $this->snapshot_item( $item_id, $item );
			$max_qty  = max( 1, (int) $item->get_quantity() );
			$qty      = isset( $quantities[ (string) $item_id ] ) ? (int) $quantities[ (string) $item_id ] : $max_qty;
			$snapshot['quantity'] = max( 1, min( $qty, $max_qty ) );

			$selected[] = $snapshot;
		}

		return $selected;
	}

	/**
	 * Snapshot a single order line item.
	 *
	 * @param int                      $item_id Item ID.
	 * @param \WC_Order_Item_Product   $item    Order item.
	 * @return array<string, mixed>
	 */
	private function snapshot_item( int $item_id, \WC_Order_Item_Product $item ): array {
		$product      = $item->get_product();
		$image_url    = '';
		$attributes   = array();
		$price        = (float) $item->get_total();
		$product_id   = $item->get_product_id();
		$variation_id = $item->get_variation_id();

		if ( $product instanceof \WC_Product ) {
			$image_id = $product->get_image_id();
			if ( $image_id ) {
				$image_url = (string) wp_get_attachment_image_url( (int) $image_id, 'thumbnail' );
			}

			if ( $product->is_type( 'variation' ) ) {
				foreach ( $product->get_variation_attributes() as $attr_key => $attr_value ) {
					$label = wc_attribute_label( str_replace( 'attribute_', '', $attr_key ), $product );
					$attributes[ $label ] = $attr_value;
				}
			}
		}

		foreach ( $item->get_formatted_meta_data( '' ) as $meta ) {
			$attributes[ wp_strip_all_tags( $meta->display_key ) ] = wp_strip_all_tags( $meta->display_value );
		}

		return array(
			'item_id'      => $item_id,
			'product_id'   => $product_id,
			'variation_id' => $variation_id,
			'name'         => $item->get_name(),
			'quantity'     => $item->get_quantity(),
			'sku'          => $product instanceof \WC_Product ? (string) $product->get_sku() : '',
			'price'        => $price,
			'price_html'   => wc_price( $price ),
			'image'        => $image_url,
			'attributes'   => $attributes,
		);
	}
}
