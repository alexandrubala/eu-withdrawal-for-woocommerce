<?php
/**
 * Plugin activation routines.
 *
 * @package EUWithdrawal
 */

namespace EUWithdrawal;

use EUWithdrawal\Data\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Class Activator
 */
final class Activator {

	/**
	 * Run activation tasks.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( ! self::is_woocommerce_active() ) {
			deactivate_plugins( EU_WITHDRAWAL_BASENAME );
			wp_die(
				esc_html__(
					'EU Withdrawal for WooCommerce requires WooCommerce. Please activate WooCommerce and try again.',
					EU_WITHDRAWAL_TEXT_DOMAIN
				),
				esc_html__( 'Missing dependency', EU_WITHDRAWAL_TEXT_DOMAIN ),
				array( 'back_link' => true )
			);
		}

		self::add_capabilities();
		Schema::create_tables();
		self::ensure_audit_secret();

		update_option( 'eu_withdrawal_db_version', EU_WITHDRAWAL_DB_VERSION );

		flush_rewrite_rules();
	}

	/**
	 * Check whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}

	/**
	 * Grant the custom capability to relevant roles.
	 *
	 * @return void
	 */
	private static function add_capabilities(): void {
		$roles = array( 'administrator', 'shop_manager' );

		foreach ( $roles as $role_slug ) {
			$role = get_role( $role_slug );

			if ( $role ) {
				$role->add_cap( EU_WITHDRAWAL_CAPABILITY );
			}
		}
	}

	/**
	 * Generate the site-specific audit secret used for HMAC chain hashing.
	 *
	 * @return void
	 */
	private static function ensure_audit_secret(): void {
		if ( get_option( 'eu_withdrawal_audit_secret' ) ) {
			return;
		}

		update_option(
			'eu_withdrawal_audit_secret',
			wp_generate_password( 64, true, true ),
			false
		);
	}
}
