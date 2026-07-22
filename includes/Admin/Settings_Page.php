<?php
/**
 * Admin settings page for return window, courier and IBAN policy.
 *
 * @package EUWithdrawal\Admin
 */

namespace EUWithdrawal\Admin;

use EUWithdrawal\Services\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings_Page
 */
final class Settings_Page {

	/**
	 * Settings page slug.
	 */
	public const PAGE_SLUG = 'eu-withdrawal-settings';

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_save' ) );
	}

	/**
	 * Register submenu under WooCommerce.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Return / Withdrawal Settings', 'eu-withdrawal-for-woocommerce' ),
			__( 'Retururi – setări', 'eu-withdrawal-for-woocommerce' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Persist settings form submission.
	 *
	 * @return void
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['eu_withdrawal_save_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'eu-withdrawal-for-woocommerce' ) );
		}

		check_admin_referer( 'eu_withdrawal_settings_save', 'eu_withdrawal_settings_nonce' );

		Settings::update(
			array(
				'return_days'          => $_POST['return_days'] ?? 14,
				'courier_name'         => $_POST['courier_name'] ?? '',
				'courier_phone'        => $_POST['courier_phone'] ?? '',
				'courier_instructions' => $_POST['courier_instructions'] ?? '',
				'return_address'       => $_POST['return_address'] ?? '',
				'require_iban'         => $_POST['require_iban'] ?? 'always',
				'refund_note'          => $_POST['refund_note'] ?? '',
			)
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'updated' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the settings form.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'eu-withdrawal-for-woocommerce' ) );
		}

		$settings = Settings::all();

		echo '<div class="wrap eu-wd-admin">';
		echo '<h1>' . esc_html__( 'Return / Withdrawal Settings', 'eu-withdrawal-for-woocommerce' ) . '</h1>';

		if ( isset( $_GET['updated'] ) && 1 === absint( wp_unslash( $_GET['updated'] ) ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'eu-withdrawal-for-woocommerce' ) . '</p></div>';
		}

		echo '<form method="post" action="">';
		wp_nonce_field( 'eu_withdrawal_settings_save', 'eu_withdrawal_settings_nonce' );
		echo '<input type="hidden" name="eu_withdrawal_save_settings" value="1">';

		echo '<table class="form-table" role="presentation">';

		echo '<tr><th scope="row"><label for="eu-wd-return-days">' . esc_html__( 'Return / withdrawal window (days)', 'eu-withdrawal-for-woocommerce' ) . '</label></th>';
		echo '<td><input name="return_days" type="number" min="1" max="365" id="eu-wd-return-days" value="' . esc_attr( (string) (int) $settings['return_days'] ) . '" class="small-text">';
		echo '<p class="description">' . esc_html__( 'Number of days after order completion (or creation) during which customers can submit a return or withdrawal. Orders older than this window are not queried.', 'eu-withdrawal-for-woocommerce' ) . '</p></td></tr>';

		echo '<tr><th scope="row"><label for="eu-wd-require-iban">' . esc_html__( 'Collect bank account (IBAN)', 'eu-withdrawal-for-woocommerce' ) . '</label></th>';
		echo '<td><select name="require_iban" id="eu-wd-require-iban">';
		$iban_options = array(
			'always'   => __( 'Always for refunds', 'eu-withdrawal-for-woocommerce' ),
			'non_card' => __( 'Only when payment was not by card', 'eu-withdrawal-for-woocommerce' ),
			'never'    => __( 'Never', 'eu-withdrawal-for-woocommerce' ),
		);
		foreach ( $iban_options as $value => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $value ),
				selected( (string) $settings['require_iban'], $value, false ),
				esc_html( $label )
			);
		}
		echo '</select></td></tr>';

		echo '<tr><th scope="row"><label for="eu-wd-refund-note">' . esc_html__( 'Refund note for customers', 'eu-withdrawal-for-woocommerce' ) . '</label></th>';
		echo '<td><textarea name="refund_note" id="eu-wd-refund-note" rows="3" class="large-text">' . esc_textarea( (string) $settings['refund_note'] ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Shown when the customer chooses a refund (e.g. how and when money is returned).', 'eu-withdrawal-for-woocommerce' ) . '</p></td></tr>';

		echo '<tr><th scope="row"><label for="eu-wd-courier-name">' . esc_html__( 'Courier name', 'eu-withdrawal-for-woocommerce' ) . '</label></th>';
		echo '<td><input name="courier_name" type="text" id="eu-wd-courier-name" value="' . esc_attr( (string) $settings['courier_name'] ) . '" class="regular-text"></td></tr>';

		echo '<tr><th scope="row"><label for="eu-wd-courier-phone">' . esc_html__( 'Courier phone', 'eu-withdrawal-for-woocommerce' ) . '</label></th>';
		echo '<td><input name="courier_phone" type="text" id="eu-wd-courier-phone" value="' . esc_attr( (string) $settings['courier_phone'] ) . '" class="regular-text"></td></tr>';

		echo '<tr><th scope="row"><label for="eu-wd-return-address">' . esc_html__( 'Return address', 'eu-withdrawal-for-woocommerce' ) . '</label></th>';
		echo '<td><textarea name="return_address" id="eu-wd-return-address" rows="3" class="large-text">' . esc_textarea( (string) $settings['return_address'] ) . '</textarea></td></tr>';

		echo '<tr><th scope="row"><label for="eu-wd-courier-instructions">' . esc_html__( 'Return shipping instructions', 'eu-withdrawal-for-woocommerce' ) . '</label></th>';
		echo '<td><textarea name="courier_instructions" id="eu-wd-courier-instructions" rows="5" class="large-text">' . esc_textarea( (string) $settings['courier_instructions'] ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Shown when the customer chooses to return products. No courier plugin is required — paste instructions, pickup details, or a tracking link.', 'eu-withdrawal-for-woocommerce' ) . '</p></td></tr>';

		echo '</table>';

		submit_button( __( 'Save settings', 'eu-withdrawal-for-woocommerce' ) );
		echo '</form></div>';
	}
}
