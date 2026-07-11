<?php
/**
 * Singleton orchestrator for the plugin.
 *
 * @package EUWithdrawal
 */

namespace EUWithdrawal;

use EUWithdrawal\WooCommerce\Hpos_Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register core WordPress hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'before_woocommerce_init', array( Hpos_Compatibility::class, 'declare_compatibility' ) );
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), 20 );
		add_action( 'init', array( $this, 'on_init' ) );
	}

	/**
	 * Bootstrap plugin modules after dependencies are available.
	 *
	 * @return void
	 */
	public function on_plugins_loaded(): void {
		if ( ! Activator::is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}
	}

	/**
	 * General initialization (textdomain, etc.).
	 *
	 * @return void
	 */
	public function on_init(): void {
		load_plugin_textdomain(
			EU_WITHDRAWAL_TEXT_DOMAIN,
			false,
			dirname( EU_WITHDRAWAL_BASENAME ) . '/languages'
		);
	}

	/**
	 * Display an admin notice when WooCommerce is missing.
	 *
	 * @return void
	 */
	public function woocommerce_missing_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__(
				'EU Withdrawal for WooCommerce requires WooCommerce. Please activate WooCommerce to use this plugin.',
				EU_WITHDRAWAL_TEXT_DOMAIN
			)
		);
	}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Always.
	 * @return void
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}
}
