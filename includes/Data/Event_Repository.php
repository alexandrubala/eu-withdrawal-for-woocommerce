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

		$result = $wpdb->insert( $tables['events'], $row, $formats );

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
