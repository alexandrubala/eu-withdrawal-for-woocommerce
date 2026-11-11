<?php
/**
 * Admin settings page for return window, courier and IBAN policy.
 *
 * @package EUWithdrawal\Admin
 */

namespace EUWithdrawal\Admin;

use EUWithdrawal\Integrations\Legal_String_Catalog;
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
	 * Transient prefix for refused texts, shown once after the save redirect.
	 */
	private const TEXT_ERRORS_TRANSIENT = 'eu_withdrawal_text_errors_';

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

		// WordPress slashes $_POST; without this every save adds backslashes before quotes.
		$post = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in Settings::update().

		$settings = array(
			'return_days'          => $post['return_days'] ?? 14,
			'courier_name'         => $post['courier_name'] ?? '',
			'courier_instructions' => $post['courier_instructions'] ?? '',
			'return_address'       => $post['return_address'] ?? '',
			'require_iban'         => $post['require_iban'] ?? 'always',
			'refund_note'          => $post['refund_note'] ?? '',
			'max_photos'           => $post['max_photos'] ?? '',
			'reason_options'       => $post['reason_options'] ?? '',
			'custom_texts'         => isset( $post['custom_texts'] ) && is_array( $post['custom_texts'] ) ? $post['custom_texts'] : array(),
		);

		foreach ( Settings::FIELD_TOGGLES as $field ) {
			$settings[ 'field_' . $field ] = isset( $post[ 'field_' . $field ] ) ? '1' : '0';
		}

		$rejected = Settings::update( $settings );

		if ( ! empty( $rejected ) ) {
			set_transient( self::TEXT_ERRORS_TRANSIENT . get_current_user_id(), $rejected, 5 * MINUTE_IN_SECONDS );
		}

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

		$text_errors = get_transient( self::TEXT_ERRORS_TRANSIENT . get_current_user_id() );
		$text_errors = is_array( $text_errors ) ? $text_errors : array();

		if ( ! empty( $text_errors ) ) {
			delete_transient( self::TEXT_ERRORS_TRANSIENT . get_current_user_id() );
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Some texts were not saved. Check the fields marked below.', 'eu-withdrawal-for-woocommerce' ) . '</p></div>';
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

		echo '<tr><th scope="row"><label for="eu-wd-return-address">' . esc_html__( 'Return address', 'eu-withdrawal-for-woocommerce' ) . '</label></th>';
		echo '<td><textarea name="return_address" id="eu-wd-return-address" rows="3" class="large-text">' . esc_textarea( (string) $settings['return_address'] ) . '</textarea></td></tr>';

		echo '<tr><th scope="row"><label for="eu-wd-courier-instructions">' . esc_html__( 'Return shipping instructions', 'eu-withdrawal-for-woocommerce' ) . '</label></th>';
		echo '<td><textarea name="courier_instructions" id="eu-wd-courier-instructions" rows="5" class="large-text">' . esc_textarea( (string) $settings['courier_instructions'] ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Shown when the customer chooses to return products. No courier plugin is required — paste instructions, pickup details, or a tracking link.', 'eu-withdrawal-for-woocommerce' ) . '</p></td></tr>';

		echo '</table>';

		$this->render_field_settings( $settings );
		$this->render_text_settings( $text_errors );

		submit_button( __( 'Save settings', 'eu-withdrawal-for-woocommerce' ) );
		echo '</form></div>';
	}

	/**
	 * Render the "Form fields" section.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @return void
	 */
	private function render_field_settings( array $settings ): void {
		$fields = array(
			'reason'         => __( 'Return reason', 'eu-withdrawal-for-woocommerce' ),
			'product_opened' => __( 'Product opened', 'eu-withdrawal-for-woocommerce' ),
			'details'        => __( 'Additional details', 'eu-withdrawal-for-woocommerce' ),
			'photos'         => __( 'Photos', 'eu-withdrawal-for-woocommerce' ),
			'phone'          => __( 'Phone', 'eu-withdrawal-for-woocommerce' ),
			'sku'            => __( 'Product code (SKU)', 'eu-withdrawal-for-woocommerce' ),
			'summary'        => __( 'Customer data summary', 'eu-withdrawal-for-woocommerce' ),
			'request_box'    => __( 'The "Refund / withdrawal" box with "What would you like to do?"', 'eu-withdrawal-for-woocommerce' ),
		);

		echo '<h2 class="title">' . esc_html__( 'Form fields', 'eu-withdrawal-for-woocommerce' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'A hidden field is not displayed, validated or saved. The return reason, product opened, additional details and photos always stay optional for the customer.', 'eu-withdrawal-for-woocommerce' ) . '</p>';
		echo '<table class="form-table" role="presentation">';

		foreach ( $fields as $field => $label ) {
			$name = 'field_' . $field;

			printf(
				'<tr><th scope="row">%1$s</th><td><label><input type="checkbox" name="%2$s" value="1"%3$s> %4$s</label></td></tr>',
				esc_html( $label ),
				esc_attr( $name ),
				checked( '0' !== (string) $settings[ $name ], true, false ),
				esc_html__( 'Show in the form', 'eu-withdrawal-for-woocommerce' )
			);
		}

		echo '<tr><th scope="row"><label for="eu-wd-max-photos">' . esc_html__( 'Maximum number of photos', 'eu-withdrawal-for-woocommerce' ) . '</label></th>';
		printf(
			'<td><input name="max_photos" type="number" min="1" max="%1$d" id="eu-wd-max-photos" value="%2$d" class="small-text"></td></tr>',
			(int) Settings::MAX_PHOTOS_LIMIT,
			(int) Settings::max_photos()
		);

		$default_reasons = array();
		foreach ( Legal_String_Catalog::DEFAULT_REASON_KEYS as $key ) {
			$default_reasons[] = Legal_String_Catalog::translate( $key );
		}

		echo '<tr><th scope="row"><label for="eu-wd-reason-options">' . esc_html__( 'Return reason options', 'eu-withdrawal-for-woocommerce' ) . '</label></th>';
		printf(
			'<td><textarea name="reason_options" id="eu-wd-reason-options" rows="6" class="large-text" placeholder="%1$s">%2$s</textarea>',
			esc_attr( implode( "\n", $default_reasons ) ),
			esc_textarea( (string) $settings['reason_options'] )
		);
		echo '<p class="description">' . esc_html__( 'One option per line. Leave empty to use the default list.', 'eu-withdrawal-for-woocommerce' ) . '</p></td></tr>';

		echo '</table>';
	}

	/**
	 * Render the "Form texts" section, one field per catalog key, grouped by stage.
	 *
	 * @param array<string, string> $text_errors Texts refused by the last save.
	 * @return void
	 */
	private function render_text_settings( array $text_errors ): void {
		$defaults = Legal_String_Catalog::all();
		$custom   = Settings::custom_texts();

		echo '<h2 class="title">' . esc_html__( 'Form texts', 'eu-withdrawal-for-woocommerce' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Leave a field empty to use the default text, shown in gray. Keep placeholders such as %d or %1$s exactly as in the default text.', 'eu-withdrawal-for-woocommerce' ) . '</p>';

		foreach ( Legal_String_Catalog::groups() as $group ) {
			$has_error = ! empty( array_intersect( $group['keys'], array_keys( $text_errors ) ) );

			echo '<details' . ( $has_error ? ' open' : '' ) . ' style="margin-top:12px;">';
			echo '<summary style="cursor:pointer;font-weight:600;">' . esc_html( $group['label'] ) . '</summary>';
			echo '<table class="form-table" role="presentation">';

			foreach ( $group['keys'] as $key ) {
				if ( isset( $defaults[ $key ] ) ) {
					$this->render_text_field( $key, $defaults[ $key ], (string) ( $custom[ $key ] ?? '' ), $text_errors[ $key ] ?? null );
				}
			}

			echo '</table></details>';
		}
	}

	/**
	 * Render one catalog text field.
	 *
	 * @param string      $key      Catalog key.
	 * @param string      $default  Default text.
	 * @param string      $value    Saved override.
	 * @param string|null $rejected Text refused by the last save.
	 * @return void
	 */
	private function render_text_field( string $key, string $default, string $value, ?string $rejected ): void {
		$id = 'eu-wd-text-' . $key;

		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $default ) . '</label></th><td>';

		if ( mb_strlen( $default ) > 70 ) {
			printf(
				'<textarea name="custom_texts[%1$s]" id="%2$s" rows="2" class="large-text" placeholder="%3$s">%4$s</textarea>',
				esc_attr( $key ),
				esc_attr( $id ),
				esc_attr( $default ),
				esc_textarea( $value )
			);
		} else {
			printf(
				'<input type="text" name="custom_texts[%1$s]" id="%2$s" class="large-text" placeholder="%3$s" value="%4$s">',
				esc_attr( $key ),
				esc_attr( $id ),
				esc_attr( $default ),
				esc_attr( $value )
			);
		}

		if ( in_array( $key, array( 'withdrawal_button_label', 'confirm_withdrawal' ), true ) ) {
			echo '<p class="description">' . esc_html__( 'This text must clearly say that you are withdrawing from the contract or order.', 'eu-withdrawal-for-woocommerce' ) . '</p>';
		}

		if ( null !== $rejected ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Not saved: the placeholders must be the same as in the default text.', 'eu-withdrawal-for-woocommerce' ) . ' ' . esc_html__( 'Refused text:', 'eu-withdrawal-for-woocommerce' ) . ' <code>' . esc_html( $rejected ) . '</code></p></div>';
		}

		echo '</td></tr>';
	}
}
