<?php
/**
 * Enqueues public-facing CSS and JavaScript assets.
 *
 * @package EUWithdrawal\PublicArea
 */

namespace EUWithdrawal\PublicArea;

defined( 'ABSPATH' ) || exit;

/**
 * Class Frontend
 */
final class Frontend {

	/**
	 * Nonce action shared with AJAX handlers.
	 */
	public const NONCE_ACTION = 'eu_withdrawal_public';

	/**
	 * Register asset hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'enqueue_assets' ), 1 );
	}

	/**
	 * Enqueue assets when the shortcode / My Account flow is present.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! Shortcode::is_used() ) {
			return;
		}

		if ( wp_script_is( 'eu-withdrawal-public', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style(
			'eu-withdrawal-public',
			EU_WITHDRAWAL_URL . 'assets/css/public-withdrawal.css',
			array(),
			EU_WITHDRAWAL_VERSION
		);

		wp_enqueue_script(
			'eu-withdrawal-public',
			EU_WITHDRAWAL_URL . 'assets/js/public-withdrawal.js',
			array(),
			EU_WITHDRAWAL_VERSION,
			true
		);

		wp_localize_script(
			'eu-withdrawal-public',
			'euWithdrawalPublic',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'nonceAction' => self::NONCE_ACTION,
				'i18n'        => array(
					'genericError'  => __( 'Something went wrong. Please try again.', 'eu-withdrawal-for-woocommerce' ),
					'networkError'  => __( 'Network error. Please check your connection and try again.', 'eu-withdrawal-for-woocommerce' ),
					'serverError'   => __( 'The server returned an unexpected response. Please try again or contact the store.', 'eu-withdrawal-for-woocommerce' ),
					'sessionExpired'=> __( 'Your session has expired. Please start again.', 'eu-withdrawal-for-woocommerce' ),
					'selectProduct' => __( 'Please select at least one product.', 'eu-withdrawal-for-woocommerce' ),
					'loading'       => __( 'Processing…', 'eu-withdrawal-for-woocommerce' ),
					'tooManyPhotos' => __( 'You can upload a maximum of 5 photos.', 'eu-withdrawal-for-woocommerce' ),
					'photoTooLarge' => __( 'Each photo must be at most 5 MB.', 'eu-withdrawal-for-woocommerce' ),
					'photoType'     => __( 'Only JPG, PNG, GIF, or WebP images are allowed.', 'eu-withdrawal-for-woocommerce' ),
				),
				'maxPhotos'   => 5,
				'maxPhotoBytes' => 5242880,
			)
		);
	}
}
