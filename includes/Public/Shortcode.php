<?php
/**
 * Registers the public withdrawal shortcode.
 *
 * @package EUWithdrawal\PublicArea
 */

namespace EUWithdrawal\PublicArea;

use EUWithdrawal\Utils\Template_Loader;

defined( 'ABSPATH' ) || exit;

/**
 * Class Shortcode
 */
final class Shortcode {

	/**
	 * Shortcode tag name.
	 */
	public const TAG = 'eu_withdrawal_button';

	/**
	 * Whether the shortcode was rendered on the current request.
	 *
	 * @var bool
	 */
	private static bool $used = false;

	/**
	 * Register shortcode hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Check if the shortcode is used on the current page.
	 *
	 * @return bool
	 */
	public static function is_used(): bool {
		if ( self::$used ) {
			return true;
		}

		if ( ! is_singular() ) {
			return false;
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		return has_shortcode( $post->post_content, self::TAG );
	}

	/**
	 * Render the initial withdrawal button and flow container.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts = array() ): string {
		self::$used = true;

		$atts = shortcode_atts(
			array(
				'label' => __( 'Request withdrawal', EU_WITHDRAWAL_TEXT_DOMAIN ),
			),
			$atts,
			self::TAG
		);

		$step1_html = Template_Loader::load( 'step-1-form.php' );

		ob_start();
		?>
		<div class="eu-withdrawal" id="eu-withdrawal-app">
			<button type="button" class="eu-withdrawal__trigger button">
				<?php echo esc_html( $atts['label'] ); ?>
			</button>
			<div class="eu-withdrawal__flow" hidden>
				<div class="eu-withdrawal__step eu-withdrawal__step--1">
					<?php echo $step1_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output is escaped internally. ?>
				</div>
				<div class="eu-withdrawal__step eu-withdrawal__step--2" hidden></div>
				<div class="eu-withdrawal__step eu-withdrawal__step--3" hidden></div>
			</div>
			<div class="eu-withdrawal__messages" role="alert" aria-live="polite" hidden></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
