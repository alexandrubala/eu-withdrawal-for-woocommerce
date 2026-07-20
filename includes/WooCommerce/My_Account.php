<?php
/**
 * WooCommerce My Account endpoint for returns / withdrawals.
 *
 * @package EUWithdrawal\WooCommerce
 */

namespace EUWithdrawal\WooCommerce;

use EUWithdrawal\Integrations\Legal_String_Catalog;
use EUWithdrawal\PublicArea\Frontend;
use EUWithdrawal\PublicArea\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * Class My_Account
 */
final class My_Account {

	/**
	 * Endpoint slug.
	 */
	public const ENDPOINT = 'eu-withdrawal';

	/**
	 * Register My Account hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render_endpoint' ) );
		add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_var' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ), 20 );
	}

	/**
	 * Register the rewrite endpoint.
	 *
	 * @return void
	 */
	public function add_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Register the query var for the endpoint.
	 *
	 * @param array<string, string> $vars Query vars.
	 * @return array<string, string>
	 */
	public function add_query_var( array $vars ): array {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	/**
	 * Insert menu item into My Account navigation.
	 *
	 * @param array<string, string> $items Menu items.
	 * @return array<string, string>
	 */
	public function add_menu_item( array $items ): array {
		$new_items = array();

		foreach ( $items as $key => $label ) {
			$new_items[ $key ] = $label;

			// Place after Orders when possible.
			if ( 'orders' === $key ) {
				$new_items[ self::ENDPOINT ] = Legal_String_Catalog::translate( 'my_account_menu' );
			}
		}

		if ( ! isset( $new_items[ self::ENDPOINT ] ) ) {
			$new_items[ self::ENDPOINT ] = Legal_String_Catalog::translate( 'my_account_menu' );
		}

		return $new_items;
	}

	/**
	 * Enqueue public assets on the My Account endpoint.
	 *
	 * @return void
	 */
	public function maybe_enqueue(): void {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}

		if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( self::ENDPOINT ) ) {
			return;
		}

		Shortcode::mark_as_used();
		( new Frontend() )->enqueue_assets();
	}

	/**
	 * Render the My Account endpoint content.
	 *
	 * @return void
	 */
	public function render_endpoint(): void {
		echo '<div class="eu-withdrawal-my-account">';
		echo '<h2>' . esc_html( Legal_String_Catalog::translate( 'my_account_heading' ) ) . '</h2>';
		echo '<p class="eu-withdrawal-my-account__intro">' . esc_html( Legal_String_Catalog::translate( 'my_account_intro' ) ) . '</p>';
		echo Shortcode::render_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'show_button' => 'no',
				'label'       => '',
			)
		);
		echo '</div>';
	}

	/**
	 * Flush rewrite rules once after activation / version bump.
	 *
	 * @return void
	 */
	public static function maybe_flush_rewrites(): void {
		$flag = get_option( 'eu_withdrawal_flush_rewrites', '' );

		if ( '1' !== $flag ) {
			return;
		}

		flush_rewrite_rules();
		delete_option( 'eu_withdrawal_flush_rewrites' );
	}
}
