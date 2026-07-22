<?php
/**
 * Database schema definitions for custom plugin tables.
 *
 * @package EUWithdrawal\Data
 */

namespace EUWithdrawal\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Class Schema
 */
final class Schema {

	/**
	 * Return table names with the WordPress table prefix.
	 *
	 * @return array<string, string>
	 */
	public static function get_table_names(): array {
		global $wpdb;

		return array(
			'requests'  => $wpdb->prefix . 'eu_withdrawal_requests',
			'audit_log' => $wpdb->prefix . 'eu_withdrawal_audit_log',
			'events'    => $wpdb->prefix . 'eu_withdrawal_events',
		);
	}

	/**
	 * Create or upgrade custom tables via dbDelta.
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$tables  = self::get_table_names();
		$charset = $wpdb->get_charset_collate();

		$sql_requests = "CREATE TABLE {$tables['requests']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL,
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			order_number varchar(50) NOT NULL DEFAULT '',
			customer_name varchar(200) NOT NULL DEFAULT '',
			customer_email varchar(200) NOT NULL DEFAULT '',
			customer_phone varchar(50) NULL,
			products_json longtext NULL,
			reason text NULL,
			attachments_json longtext NULL,
			request_type varchar(20) NOT NULL DEFAULT 'refund',
			refund_iban varchar(50) NULL,
			refund_account_name varchar(200) NULL,
			courier_notes text NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			locale varchar(10) NOT NULL DEFAULT '',
			submitted_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			KEY order_id (order_id),
			KEY status (status),
			KEY request_type (request_type),
			KEY customer_email (customer_email),
			KEY submitted_at (submitted_at)
		) $charset;";

		$sql_audit_log = "CREATE TABLE {$tables['audit_log']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_uuid char(36) NOT NULL DEFAULT '',
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			customer_email varchar(200) NOT NULL DEFAULT '',
			ip_address varchar(45) NOT NULL DEFAULT '',
			user_agent varchar(500) NOT NULL DEFAULT '',
			payload_hash char(64) NOT NULL DEFAULT '',
			security_hash char(64) NOT NULL DEFAULT '',
			previous_hash char(64) NULL,
			recorded_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY request_uuid (request_uuid),
			KEY order_id (order_id),
			KEY recorded_at (recorded_at)
		) $charset;";

		$sql_events = "CREATE TABLE {$tables['events']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_id bigint(20) unsigned NOT NULL DEFAULT 0,
			event_type varchar(50) NOT NULL DEFAULT '',
			actor_type varchar(20) NOT NULL DEFAULT 'system',
			actor_id bigint(20) unsigned NULL,
			message text NOT NULL,
			meta_json longtext NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY request_id (request_id),
			KEY event_type (event_type),
			KEY created_at (created_at)
		) $charset;";

		dbDelta( $sql_requests );
		dbDelta( $sql_audit_log );
		dbDelta( $sql_events );
	}

	/**
	 * Upgrade schema when the stored DB version is older than the plugin constant.
	 *
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$installed = (string) get_option( 'eu_withdrawal_db_version', '' );

		if ( $installed === EU_WITHDRAWAL_DB_VERSION ) {
			return;
		}

		self::create_tables();
		update_option( 'eu_withdrawal_db_version', EU_WITHDRAWAL_DB_VERSION );
		update_option( 'eu_withdrawal_flush_rewrites', '1' );
	}
}
