<?php
/**
 * Registers the public withdrawal shortcode.
 *
 * @package EUWithdrawal\PublicArea
 */

namespace EUWithdrawal\PublicArea;

use EUWithdrawal\Integrations\Legal_String_Catalog;
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
	 * Gutenberg block name.
	 */
	public const BLOCK_NAME = 'eu-withdrawal/withdrawal-button';

	/**
	 * Whether the shortcode or block was rendered on the current request.
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
	 * Mark the withdrawal flow as present on the current request.
	 *
	 * @return void
	 */
	public static function mark_as_used(): void {
		self::$used = true;
	}

	/**
	 * Check if the shortcode or block is used on the current page.
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

		if ( has_shortcode( $post->post_content, self::TAG ) ) {
			return true;
		}

		return function_exists( 'has_block' ) && has_block( self::BLOCK_NAME, $post );
	}

	/**
	 * Render the initial withdrawal button and flow container.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'label' => Legal_String_Catalog::translate( 'withdrawal_button_label' ),
			),
			is_array( $atts ) ? $atts : array(),
			self::TAG
		);

		return self::render_html( $atts );
	}

	/**
	 * Shared HTML renderer for the shortcode and Gutenberg block.
	 *
	 * @param array<string, string> $atts Render attributes.
	 * @return string
	 */
	public static function render_html( array $atts ): string {
		self::mark_as_used();

		if ( '' === ( $atts['label'] ?? '' ) ) {
			$atts['label'] = Legal_String_Catalog::translate( 'withdrawal_button_label' );
		}

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
