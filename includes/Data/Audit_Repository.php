<?php
/**
 * Append-only data access for the immutable audit log.
 *
 * @package EUWithdrawal\Data
 */

namespace EUWithdrawal\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Class Audit_Repository
 *
 * This repository intentionally exposes only insert and read helpers.
 * No update or delete operations are provided to preserve immutability.
 */
final class Audit_Repository {

	/**
	 * Insert a new audit log entry.
	 *
	 * @param array<string, mixed> $data Row data keyed by column name.
	 * @return int Inserted row ID, or 0 on failure.
	 */
	public function insert( array $data ): int {
		global $wpdb;

		$tables = Schema::get_table_names();
		$now    = current_time( 'mysql' );

		$row = array(
			'request_uuid'   => (string) ( $data['request_uuid'] ?? '' ),
			'order_id'       => (int) ( $data['order_id'] ?? 0 ),
			'customer_email' => (string) ( $data['customer_email'] ?? '' ),
			'ip_address'     => (string) ( $data['ip_address'] ?? '' ),
			'user_agent'     => (string) ( $data['user_agent'] ?? '' ),
			'payload_hash'   => (string) ( $data['payload_hash'] ?? '' ),
			'security_hash'  => (string) ( $data['security_hash'] ?? '' ),
			'previous_hash'  => $this->nullable_string( $data['previous_hash'] ?? null ),
			'recorded_at'    => (string) ( $data['recorded_at'] ?? $now ),
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
		);

		$result = $wpdb->insert( $tables['audit_log'], $row, $formats );

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Return the security hash of the most recent audit entry for chain linking.
	 *
	 * @return string|null
	 */
	public function get_latest_security_hash(): ?string {
		global $wpdb;

		$tables = Schema::get_table_names();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$hash = $wpdb->get_var(
			"SELECT security_hash FROM {$tables['audit_log']} ORDER BY id DESC LIMIT 1"
		);

		if ( ! is_string( $hash ) || '' === $hash ) {
			return null;
		}

		return $hash;
	}

	/**
	 * Fetch the audit log entry for a request UUID.
	 *
	 * @param string $request_uuid Request UUID.
	 * @return array<string, mixed>|null
	 */
	public function get_by_request_uuid( string $request_uuid ): ?array {
		if ( '' === $request_uuid ) {
			return null;
		}

		global $wpdb;

		$tables = Schema::get_table_names();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$tables['audit_log']} WHERE request_uuid = %s ORDER BY id DESC LIMIT 1",
				$request_uuid
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
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
