<?php
/**
 * Data transfer object for Step 1 withdrawal form input.
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
	 * Constructor.
	 *
	 * @param string $name         Customer name.
	 * @param string $email        Customer email.
	 * @param string $order_number Order number.
	 * @param string $phone        Optional phone.
	 * @param string $reason       Optional reason.
	 * @param int    $order_id     Resolved order ID.
	 */
	public function __construct(
		string $name,
		string $email,
		string $order_number,
		string $phone = '',
		string $reason = '',
		int $order_id = 0
	) {
		$this->name         = $name;
		$this->email        = $email;
		$this->order_number = $order_number;
		$this->phone        = $phone;
		$this->reason       = $reason;
		$this->order_id     = $order_id;
	}

	/**
	 * Convert the DTO to a storable array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'name'         => $this->name,
			'email'        => $this->email,
			'order_number' => $this->order_number,
			'phone'        => $this->phone,
			'reason'       => $this->reason,
			'order_id'     => $this->order_id,
		);
	}

	/**
	 * Rehydrate a DTO from stored array data.
	 *
	 * @param array<string, mixed> $data Stored payload.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			(string) ( $data['name'] ?? '' ),
			(string) ( $data['email'] ?? '' ),
			(string) ( $data['order_number'] ?? '' ),
			(string) ( $data['phone'] ?? '' ),
			(string) ( $data['reason'] ?? '' ),
			(int) ( $data['order_id'] ?? 0 )
		);
	}
}
