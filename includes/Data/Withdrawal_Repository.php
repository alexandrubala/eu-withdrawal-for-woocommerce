<?php
/**
 * Insert-only data access for withdrawal requests.
 *
 * @package EUWithdrawal\Data
 */

namespace EUWithdrawal\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Class Withdrawal_Repository
 */
final class Withdrawal_Repository {

	/**
	 * Insert a new withdrawal request row.
	 *
	 * @param array<string, mixed> $data Row data keyed by column name.
	 * @return int Inserted row ID, or 0 on failure.
	 */
	public function insert( array $data ): int {
		global $wpdb;

		$tables = Schema::get_table_names();
		$now    = current_time( 'mysql' );

		$row = array(
			'uuid'           => (string) ( $data['uuid'] ?? '' ),
			'order_id'       => (int) ( $data['order_id'] ?? 0 ),
			'order_number'   => (string) ( $data['order_number'] ?? '' ),
			'customer_name'  => (string) ( $data['customer_name'] ?? '' ),
			'customer_email' => (string) ( $data['customer_email'] ?? '' ),
			'customer_phone' => $this->nullable_string( $data['customer_phone'] ?? null ),
			'products_json'  => $this->nullable_string( $data['products_json'] ?? null ),
			'reason'         => $this->nullable_string( $data['reason'] ?? null ),
			'status'         => (string) ( $data['status'] ?? 'pending' ),
			'locale'         => (string) ( $data['locale'] ?? '' ),
			'submitted_at'   => (string) ( $data['submitted_at'] ?? $now ),
			'created_at'     => (string) ( $data['created_at'] ?? $now ),
			'updated_at'     => (string) ( $data['updated_at'] ?? $now ),
		);

		$formats = array(
			'%s',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		);

		$result = $wpdb->insert( $tables['requests'], $row, $formats );

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Normalize optional string columns for database storage.
	 *
	 * @param mixed $value Raw value.
	 * @return string|null
	 */
	private function nullable_string( mixed $value ): ?string {
		if ( null === $value ) {
			return null;
		}

		$string = (string) $value;

		return '' === $string ? null : $string;
	}
}
