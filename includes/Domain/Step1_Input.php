<?php
/**
 * Data transfer object for the multi-step withdrawal / return form.
 *
 * @package EUWithdrawal\Domain
 */

namespace EUWithdrawal\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Class Step1_Input
 */
final class Step1_Input {

	/**
	 * Customer full name.
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * Customer email address.
	 *
	 * @var string
	 */
	public string $email;

	/**
	 * WooCommerce order number as entered by the customer.
	 *
	 * @var string
	 */
	public string $order_number;

	/**
	 * Optional phone number.
	 *
	 * @var string
	 */
	public string $phone;

	/**
	 * Optional withdrawal reason.
	 *
	 * @var string
	 */
	public string $reason;

	/**
	 * Resolved WooCommerce order ID (set after validation).
	 *
	 * @var int
	 */
	public int $order_id;

	/**
	 * Request type: return | refund.
	 *
	 * @var string
	 */
	public string $request_type;

	/**
	 * Selected product snapshots for persistence.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $selected_products;

	/**
	 * Refund IBAN (when applicable).
	 *
	 * @var string
	 */
	public string $refund_iban;

	/**
	 * Refund account holder name.
	 *
	 * @var string
	 */
	public string $refund_account_name;

	/**
	 * Courier / return notes captured at submission.
	 *
	 * @var string
	 */
	public string $courier_notes;

	/**
	 * Constructor.
	 *
	 * @param string                           $name                Customer name.
	 * @param string                           $email               Customer email.
	 * @param string                           $order_number        Order number.
	 * @param string                           $phone               Optional phone.
	 * @param string                           $reason              Optional reason.
	 * @param int                              $order_id            Resolved order ID.
	 * @param string                           $request_type        return|refund.
	 * @param array<int, array<string, mixed>> $selected_products   Product snapshots.
	 * @param string                           $refund_iban         IBAN.
	 * @param string                           $refund_account_name Account holder.
	 * @param string                           $courier_notes       Courier notes.
	 */
	public function __construct(
		string $name,
		string $email,
		string $order_number,
		string $phone = '',
		string $reason = '',
		int $order_id = 0,
		string $request_type = '',
		array $selected_products = array(),
		string $refund_iban = '',
		string $refund_account_name = '',
		string $courier_notes = ''
	) {
		$this->name                = $name;
		$this->email               = $email;
		$this->order_number        = $order_number;
		$this->phone               = $phone;
		$this->reason              = $reason;
		$this->order_id            = $order_id;
		$this->request_type        = $request_type;
		$this->selected_products   = $selected_products;
		$this->refund_iban         = $refund_iban;
		$this->refund_account_name = $refund_account_name;
		$this->courier_notes       = $courier_notes;
	}

	/**
	 * Convert the DTO to a storable array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'name'                => $this->name,
			'email'               => $this->email,
			'order_number'        => $this->order_number,
			'phone'               => $this->phone,
			'reason'              => $this->reason,
			'order_id'            => $this->order_id,
			'request_type'        => $this->request_type,
			'selected_products'   => $this->selected_products,
			'refund_iban'         => $this->refund_iban,
			'refund_account_name' => $this->refund_account_name,
			'courier_notes'       => $this->courier_notes,
		);
	}

	/**
	 * Rehydrate a DTO from stored array data.
	 *
	 * @param array<string, mixed> $data Stored payload.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$products = $data['selected_products'] ?? array();

		if ( ! is_array( $products ) ) {
			$products = array();
		}

		return new self(
			(string) ( $data['name'] ?? '' ),
			(string) ( $data['email'] ?? '' ),
			(string) ( $data['order_number'] ?? '' ),
			(string) ( $data['phone'] ?? '' ),
			(string) ( $data['reason'] ?? '' ),
			(int) ( $data['order_id'] ?? 0 ),
			(string) ( $data['request_type'] ?? '' ),
			$products,
			(string) ( $data['refund_iban'] ?? '' ),
			(string) ( $data['refund_account_name'] ?? '' ),
			(string) ( $data['courier_notes'] ?? '' )
		);
	}

	/**
	 * Return a copy with updated fields.
	 *
	 * @param array<string, mixed> $overrides Field overrides.
	 * @return self
	 */
	public function with( array $overrides ): self {
		$data = array_merge( $this->to_array(), $overrides );

		return self::from_array( $data );
	}
}
