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

	/**
	 * Build an admin edit URL for a WooCommerce order (HPOS-aware).
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return string Empty string when order ID is invalid.
	 */
	public static function get_order_edit_url( int $order_id ): string {
		if ( $order_id <= 0 ) {
			return '';
		}

		if (
			class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
		) {
			return admin_url( 'admin.php?page=wc-orders&action=edit&id=' . absint( $order_id ) );
		}

		return admin_url( 'post.php?post=' . absint( $order_id ) . '&action=edit' );
	}

	/**
	 * Screen IDs used for order edit meta boxes (classic + HPOS).
	 *
	 * @return array<int, string>
	 */
	public static function get_order_screen_ids(): array {
		$screens = array( 'shop_order' );

		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		}

		return array_values( array_unique( $screens ) );
	}
}
