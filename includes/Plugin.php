<?php
/**
 * Singleton orchestrator for the plugin.
 *
 * @package EUWithdrawal
 */

namespace EUWithdrawal;

use EUWithdrawal\Blocks\Block_Registry;
use EUWithdrawal\Integrations\Polylang;
use EUWithdrawal\Integrations\Wpml;
use EUWithdrawal\PublicArea\Ajax;
use EUWithdrawal\PublicArea\Frontend;
use EUWithdrawal\PublicArea\Shortcode;
use EUWithdrawal\Admin\Admin;
use EUWithdrawal\Admin\Export_Controller;
use EUWithdrawal\Admin\Order_Meta_Box;
use EUWithdrawal\Data\Audit_Repository;
use EUWithdrawal\Data\Event_Repository;
use EUWithdrawal\Data\Withdrawal_Repository;
use EUWithdrawal\REST\Rest_Bootstrap;
use EUWithdrawal\Security\Audit_Hash;
use EUWithdrawal\Services\Email_Service;
use EUWithdrawal\Services\Export_Service;
use EUWithdrawal\Services\Order_Validator;
use EUWithdrawal\Services\Session_Token_Service;
use EUWithdrawal\Services\Withdrawal_Service;
use EUWithdrawal\WooCommerce\Hpos_Compatibility;
use EUWithdrawal\WooCommerce\Refund_Integration;

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
	 * Admin module instances.
	 *
	 * @var array<string, object>
	 */
	private array $admin_modules = array();

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
		$this->bootstrap_rest_api();
		$this->bootstrap_admin_area();
		$this->bootstrap_woocommerce_integrations();
		$this->bootstrap_blocks();
		$this->bootstrap_integrations();
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
	 * Instantiate and register REST API endpoints.
	 *
	 * @return void
	 */
	private function bootstrap_rest_api(): void {
		$rest_bootstrap = new Rest_Bootstrap( new Withdrawal_Repository() );
		$rest_bootstrap->register_hooks();
	}

	/**
	 * Instantiate and register admin dashboard components.
	 *
	 * @return void
	 */
	private function bootstrap_admin_area(): void {
		if ( ! is_admin() ) {
			return;
		}

		$withdrawal_repository = new Withdrawal_Repository();
		$event_repository      = new Event_Repository();
		$audit_repository      = new Audit_Repository();
		$export_service        = new Export_Service( $withdrawal_repository );

		$this->admin_modules = array(
			'admin'             => new Admin( $withdrawal_repository, $event_repository, $audit_repository ),
			'export_controller' => new Export_Controller( $export_service ),
			'order_meta_box'    => new Order_Meta_Box( $withdrawal_repository ),
		);

		$this->admin_modules['admin']->register_hooks();
		$this->admin_modules['export_controller']->register_hooks();
		$this->admin_modules['order_meta_box']->register_hooks();
	}

	/**
	 * Instantiate and register WooCommerce integration hooks.
	 *
	 * @return void
	 */
	private function bootstrap_woocommerce_integrations(): void {
		$refund_integration = new Refund_Integration(
			new Withdrawal_Repository(),
			new Event_Repository()
		);

		$refund_integration->register_hooks();
	}

	/**
	 * Instantiate and register Gutenberg blocks.
	 *
	 * @return void
	 */
	private function bootstrap_blocks(): void {
		$block_registry = new Block_Registry();
		$block_registry->register_hooks();
	}

	/**
	 * Instantiate multilingual integration hooks.
	 *
	 * @return void
	 */
	private function bootstrap_integrations(): void {
		( new Wpml() )->register_hooks();
		( new Polylang() )->register_hooks();
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
				'eu-withdrawal-for-woocommerce'
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
