<?php
/**
 * Server-side render callback for the withdrawal button block.
 *
 * Delegates to the same renderer used by the [eu_withdrawal_button] shortcode.
 *
 * @package EUWithdrawal
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content (unused for dynamic blocks).
 * @var WP_Block $block      Block instance.
 */

use EUWithdrawal\PublicArea\Shortcode;

defined( 'ABSPATH' ) || exit;

$atts = array();

if ( ! empty( $attributes['label'] ) ) {
	$atts['label'] = (string) apply_filters(
		'eu_withdrawal_translate_string',
		(string) $attributes['label'],
		'withdrawal_button_label'
	);
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode renderer escapes internally.
echo Shortcode::render_html( $atts );
