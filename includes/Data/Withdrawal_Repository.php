<?php
/**
 * Data access for withdrawal requests.
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
			'uuid'                => (string) ( $data['uuid'] ?? '' ),
			'order_id'            => (int) ( $data['order_id'] ?? 0 ),
			'order_number'        => (string) ( $data['order_number'] ?? '' ),
			'customer_name'       => (string) ( $data['customer_name'] ?? '' ),
			'customer_email'      => (string) ( $data['customer_email'] ?? '' ),
			'customer_phone'      => $this->nullable_string( $data['customer_phone'] ?? null ),
			'products_json'       => $this->nullable_string( $data['products_json'] ?? null ),
			'reason'              => $this->nullable_string( $data['reason'] ?? null ),
			'request_type'        => (string) ( $data['request_type'] ?? 'refund' ),
			'refund_iban'         => $this->nullable_string( $data['refund_iban'] ?? null ),
			'refund_account_name' => $this->nullable_string( $data['refund_account_name'] ?? null ),
			'courier_notes'       => $this->nullable_string( $data['courier_notes'] ?? null ),
			'status'              => (string) ( $data['status'] ?? 'pending' ),
			'locale'              => (string) ( $data['locale'] ?? '' ),
			'submitted_at'        => (string) ( $data['submitted_at'] ?? $now ),
			'created_at'          => (string) ( $data['created_at'] ?? $now ),
			'updated_at'          => (string) ( $data['updated_at'] ?? $now ),
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
			'%s',
			'%s',
			'%s',
			'%s',
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert( $tables['requests'], $row, $formats );

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Find a withdrawal request by primary key.
	 *
	 * @param int $id Request ID.
	 * @return array<string, mixed>|null
	 */
	public function find_by_id( int $id ): ?array {
		if ( $id <= 0 ) {
			return null;
		}

		global $wpdb;

		$tables = Schema::get_table_names();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$tables['requests']} WHERE id = %d LIMIT 1",
				$id
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Find a withdrawal request by its public UUID.
	 *
	 * @param string $uuid Request UUID.
	 * @return array<string, mixed>|null
	 */
	public function find_by_uuid( string $uuid ): ?array {
		$uuid = sanitize_text_field( $uuid );

		if ( '' === $uuid || ! wp_is_uuid( $uuid ) ) {
			return null;
		}

		global $wpdb;

		$tables = Schema::get_table_names();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$tables['requests']} WHERE uuid = %s LIMIT 1",
				$uuid
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Find the most recent withdrawal request for an order.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return array<string, mixed>|null
	 */
	public function find_by_order_id( int $order_id ): ?array {
		$requests = $this->find_all_by_order_id( $order_id, false );

		if ( empty( $requests ) ) {
			return null;
		}

		return $requests[ count( $requests ) - 1 ];
	}

	/**
	 * Find all withdrawal requests for an order, oldest first.
	 *
	 * @param int  $order_id         WooCommerce order ID.
	 * @param bool $exclude_rejected When true, omit rejected requests (they free stock for a new request).
	 * @return array<int, array<string, mixed>>
	 */
	public function find_all_by_order_id( int $order_id, bool $exclude_rejected = true ): array {
		if ( $order_id <= 0 ) {
			return array();
		}

		global $wpdb;

		$tables = Schema::get_table_names();

		if ( $exclude_rejected ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$tables['requests']} WHERE order_id = %d AND status != %s ORDER BY id ASC",
					$order_id,
					'rejected'
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$tables['requests']} WHERE order_id = %d ORDER BY id ASC",
					$order_id
				),
				ARRAY_A
			);
		}

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Query withdrawal requests with pagination, filtering, and sorting.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public function query( array $args = array() ): array {
		global $wpdb;

		$tables = Schema::get_table_names();
		$table  = $tables['requests'];

		$status   = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$search   = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$orderby  = isset( $args['orderby'] ) ? sanitize_key( (string) $args['orderby'] ) : 'submitted_at';
		$order    = isset( $args['order'] ) ? strtoupper( (string) $args['order'] ) : 'DESC';
		$per_page = max( 1, (int) ( $args['per_page'] ?? 20 ) );
		$offset   = max( 0, (int) ( $args['offset'] ?? 0 ) );

		$allowed_orderby = array(
			'id'            => 'id',
			'customer_name' => 'customer_name',
			'status'        => 'status',
			'submitted_at'  => 'submitted_at',
		);

		if ( ! isset( $allowed_orderby[ $orderby ] ) ) {
			$orderby = 'submitted_at';
		}

		$order = 'ASC' === $order ? 'ASC' : 'DESC';

		$where  = array( '1=1' );
		$values = array();

		if ( '' !== $status ) {
			$where[]  = 'status = %s';
			$values[] = $status;
		}

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '( customer_name LIKE %s OR customer_email LIKE %s OR uuid LIKE %s OR order_number LIKE %s )';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$list_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$allowed_orderby[ $orderby ]} {$order} LIMIT %d OFFSET %d";

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$values ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$items = $wpdb->get_results(
				$wpdb->prepare( $list_sql, ...array_merge( $values, array( $per_page, $offset ) ) ),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $count_sql );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$items = $wpdb->get_results(
				$wpdb->prepare( $list_sql, $per_page, $offset ),
				ARRAY_A
			);
		}

		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Update the status of a withdrawal request.
	 *
	 * @param int    $id     Request ID.
	 * @param string $status New status slug.
	 * @return bool
	 */
	public function update_status( int $id, string $status ): bool {
		if ( $id <= 0 || '' === $status ) {
			return false;
		}

		global $wpdb;

		$tables = Schema::get_table_names();
		$now    = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$tables['requests'],
			array(
				'status'     => $status,
				'updated_at' => $now,
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $result;
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
