<?php
/**
 * Plugin deactivation routines.
 *
 * @package EUWithdrawal
 */

namespace EUWithdrawal;

defined( 'ABSPATH' ) || exit;

/**
 * Class Deactivator
 */
final class Deactivator {

	/**
	 * Run deactivation tasks.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
