<?php
/**
 * EU Withdrawal for WooCommerce – legal withdrawal flow for EU distance contracts.
 *
 * @package EUWithdrawal
 *
 * @wordpress-plugin
 * Plugin Name:       EU Withdrawal for WooCommerce
 * Plugin URI:        https://github.com/alexandrubala/eu-withdrawal-for-woocommerce
 * Description:       EU Directive 2023/2673 Art. 11a compliant withdrawal flow for WooCommerce stores.
 * Version:           1.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Alexandru Bala
 * Author URI:        https://github.com/alexandrubala
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       eu-withdrawal-for-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 8.0
 * WC tested up to:   9.0
 */

defined( 'ABSPATH' ) || exit;

define( 'EU_WITHDRAWAL_VERSION', '1.1.0' );
define( 'EU_WITHDRAWAL_PATH', plugin_dir_path( __FILE__ ) );
define( 'EU_WITHDRAWAL_URL', plugin_dir_url( __FILE__ ) );
define( 'EU_WITHDRAWAL_FILE', __FILE__ );
define( 'EU_WITHDRAWAL_BASENAME', plugin_basename( __FILE__ ) );
define( 'EU_WITHDRAWAL_CAPABILITY', 'manage_eu_withdrawals' );
define( 'EU_WITHDRAWAL_DB_VERSION', '1.1.0' );

require_once EU_WITHDRAWAL_PATH . 'includes/Autoloader.php';

EUWithdrawal\Autoloader::register();

register_activation_hook( EU_WITHDRAWAL_FILE, array( 'EUWithdrawal\Activator', 'activate' ) );
register_deactivation_hook( EU_WITHDRAWAL_FILE, array( 'EUWithdrawal\Deactivator', 'deactivate' ) );

/**
 * Bootstrap the plugin singleton.
 *
 * @return EUWithdrawal\Plugin
 */
function eu_withdrawal_for_woocommerce_init(): EUWithdrawal\Plugin {
	return EUWithdrawal\Plugin::instance();
}

eu_withdrawal_for_woocommerce_init();
