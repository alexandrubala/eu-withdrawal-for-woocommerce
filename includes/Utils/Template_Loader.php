<?php
/**
 * Template loader with child-theme override support.
 *
 * @package EUWithdrawal\Utils
 */

namespace EUWithdrawal\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Class Template_Loader
 */
final class Template_Loader {

	/**
	 * Render a public template and return its HTML.
	 *
	 * Theme override path: {child-theme}/eu-withdrawal/{template}.
	 *
	 * @param string               $template Template file name relative to templates/public/.
	 * @param array<string, mixed> $args     Variables exposed inside the template.
	 * @return string
	 */
	public static function load( string $template, array $args = array() ): string {
		$template = ltrim( $template, '/' );

		if ( ! empty( $args ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Controlled template args.
			extract( $args, EXTR_SKIP );
		}

		$theme_template  = locate_template( 'eu-withdrawal/' . $template );
		$plugin_template = EU_WITHDRAWAL_PATH . 'templates/public/' . $template;

		if ( $theme_template && is_readable( $theme_template ) ) {
			$file = $theme_template;
		} elseif ( is_readable( $plugin_template ) ) {
			$file = $plugin_template;
		} else {
			return '';
		}

		ob_start();
		include $file;

		return (string) ob_get_clean();
	}

	/**
	 * Render an email template and return its HTML.
	 *
	 * Theme override path: {child-theme}/eu-withdrawal/emails/{template}.
	 *
	 * @param string               $template Template file name relative to templates/emails/.
	 * @param array<string, mixed> $args     Variables exposed inside the template.
	 * @return string
	 */
	public static function load_email( string $template, array $args = array() ): string {
		$template = ltrim( $template, '/' );

		if ( ! empty( $args ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Controlled template args.
			extract( $args, EXTR_SKIP );
		}

		$theme_template  = locate_template( 'eu-withdrawal/emails/' . $template );
		$plugin_template = EU_WITHDRAWAL_PATH . 'templates/emails/' . $template;

		if ( $theme_template && is_readable( $theme_template ) ) {
			$file = $theme_template;
		} elseif ( is_readable( $plugin_template ) ) {
			$file = $plugin_template;
		} else {
			return '';
		}

		ob_start();
		include $file;

		return (string) ob_get_clean();
	}
}
