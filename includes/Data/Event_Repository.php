<?php
/**
 * Insert-only data access for withdrawal lifecycle events.
 *
 * @package EUWithdrawal\Data
 */

namespace EUWithdrawal\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Class Event_Repository
 */
final class Event_Repository {

	/**
	 * Insert a new withdrawal event row.
	 *
	 * @param array<string, mixed> $data Row data keyed by column name.
	 * @return int Inserted row ID, or 0 on failure.
	 */
	public function insert( array $data ): int {
		global $wpdb;

		$tables = Schema::get_table_names();
		$now    = current_time( 'mysql' );

		$row = array(
			'request_id' => (int) ( $data['request_id'] ?? 0 ),
			'event_type' => (string) ( $data['event_type'] ?? '' ),
			'actor_type' => (string) ( $data['actor_type'] ?? 'system' ),
			'message'    => (string) ( $data['message'] ?? '' ),
			'meta_json'  => $this->nullable_string( $data['meta_json'] ?? null ),
			'created_at' => (string) ( $data['created_at'] ?? $now ),
		);

		$formats = array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		);

		if ( isset( $data['actor_id'] ) && null !== $data['actor_id'] ) {
			$row['actor_id']    = (int) $data['actor_id'];
			$formats[]          = '%d';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert( $tables['events'], $row, $formats );

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Fetch all events for a withdrawal request, oldest first.
	 *
	 * @param int $request_id Withdrawal request ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function find_by_request_id( int $request_id ): array {
		if ( $request_id <= 0 ) {
			return array();
		}

		global $wpdb;

		$tables = Schema::get_table_names();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$tables['events']} WHERE request_id = %d ORDER BY created_at ASC, id ASC",
				$request_id
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
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
