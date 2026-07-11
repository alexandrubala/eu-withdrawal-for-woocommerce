<?php
/**
 * Registers Gutenberg blocks for the withdrawal flow.
 *
 * @package EUWithdrawal\Blocks
 */

namespace EUWithdrawal\Blocks;

use EUWithdrawal\PublicArea\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * Class Block_Registry
 */
final class Block_Registry {

	/**
	 * Register block-related hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'render_block', array( $this, 'mark_block_used' ), 10, 2 );
	}

	/**
	 * Register compiled block types from the build directory.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		$build_dir = EU_WITHDRAWAL_PATH . 'blocks/withdrawal-button/build';

		if ( ! is_readable( $build_dir . '/block.json' ) ) {
			return;
		}

		register_block_type( $build_dir );
	}

	/**
	 * Flag the withdrawal flow as present when the block renders (FSE-safe).
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Block data.
	 * @return string
	 */
	public function mark_block_used( string $block_content, array $block ): string {
		if ( ( $block['blockName'] ?? '' ) === Shortcode::BLOCK_NAME ) {
			Shortcode::mark_as_used();
		}

		return $block_content;
	}
}
