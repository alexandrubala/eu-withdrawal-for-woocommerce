<?php
/**
 * WooCommerce HPOS (custom order tables) compatibility declaration.
 *
 * @package EUWithdrawal\WooCommerce
 */

namespace EUWithdrawal\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Class Hpos_Compatibility
 */
final class Hpos_Compatibility {

	/**
	 * Declare compatibility with WooCommerce custom order tables.
	 *
	 * Hooked on `before_woocommerce_init`.
	 *
	 * @return void
	 */
	public static function declare_compatibility(): void {
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			return;
		}

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			EU_WITHDRAWAL_FILE,
			true
		);
	}
}
