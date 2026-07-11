<?php
/**
 * Singleton orchestrator for the plugin.
 *
 * @package EUWithdrawal
 */

namespace EUWithdrawal;

use EUWithdrawal\PublicArea\Ajax;
use EUWithdrawal\PublicArea\Frontend;
use EUWithdrawal\PublicArea\Shortcode;
use EUWithdrawal\Data\Audit_Repository;
use EUWithdrawal\Data\Event_Repository;
use EUWithdrawal\Data\Withdrawal_Repository;
use EUWithdrawal\Security\Audit_Hash;
use EUWithdrawal\Services\Email_Service;
use EUWithdrawal\Services\Order_Validator;
use EUWithdrawal\Services\Session_Token_Service;
use EUWithdrawal\Services\Withdrawal_Service;
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
	 * Public-facing module instances.
	 *
	 * @var array<string, object>
	 */
	private array $public_modules = array();

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

		$this->bootstrap_public_area();
	}

	/**
	 * Instantiate and register public-facing components.
	 *
	 * @return void
	 */
	private function bootstrap_public_area(): void {
		$session_service    = new Session_Token_Service();
		$order_validator    = new Order_Validator();
		$withdrawal_service = new Withdrawal_Service(
			new Withdrawal_Repository(),
			new Audit_Repository(),
			new Event_Repository(),
			new Audit_Hash(),
			new Email_Service()
		);

		$this->public_modules = array(
			'frontend'  => new Frontend(),
			'shortcode' => new Shortcode(),
			'ajax'      => new Ajax( $order_validator, $session_service, $withdrawal_service ),
		);

		$this->public_modules['frontend']->register_hooks();
		$this->public_modules['shortcode']->register_hooks();
		$this->public_modules['ajax']->register_hooks();
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
