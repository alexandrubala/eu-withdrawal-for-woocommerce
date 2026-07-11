<?php
/**
 * PSR-4 autoloader for the plugin core (no Composer required in production).
 *
 * @package EUWithdrawal
 */

namespace EUWithdrawal;

defined( 'ABSPATH' ) || exit;

/**
 * Class Autoloader
 */
final class Autoloader {

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'autoload' ) );
	}

	/**
	 * Autoload plugin classes.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function autoload( string $class ): void {
		$prefix = 'EUWithdrawal\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );
		$relative_path  = str_replace( '\\', '/', $relative_class ) . '.php';

		// PublicArea maps to Public/ — "Public" alone is a reserved PHP keyword.
		$relative_path = str_replace( 'PublicArea/', 'Public/', $relative_path );
		$file          = EU_WITHDRAWAL_PATH . 'includes/' . $relative_path;

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
