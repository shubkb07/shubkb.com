<?php
/**
 * Blocks
 *
 * Helper functions for registering blocks.
 *
 * @package WISESYNC
 * @since 1.0.0
 */

if ( ! function_exists( 'register_sync_blocks_category' ) ) {

	/**
	 * Registers custom Gutenberg block category.
	 *
	 * @param array $categories Existing categories.
	 *
	 * @return array Modified categories.
	 */
	function register_sync_blocks_category( $categories ) {

		return array_merge(
			array(
				array(
					'slug'  => 'sync-blocks',
					'title' => __( 'Sync Blocks', 'wisesync' ),
				),
				array(
					'slug'  => 'sync-advance-blocks',
					'title' => __( 'Sync Advance Blocks', 'wisesync' ),
				),
			),
			$categories
		);
	}
}

/**
 * Block Directory Registration Function
 *
 * This function is used to register a block type with a specific directory.
 *
 * @param string $dir The directory where the block is located.
 *
 * @return void
 */
function register_sync_block_type( $dir ) {
	// Check If the directory exists.
	if ( ! file_exists( $dir ) ) {
		return;
	}

	// Get the subdirectory names.
	$subdirs = glob( $dir . '/*', GLOB_ONLYDIR );
	if ( ! $subdirs ) {
		return;
	}

	// Loop through each subdirectory.
	foreach ( $subdirs as $subdir ) {
		// Get the block.json file.
		$block_json = $subdir . '/block.json';
		if ( ! file_exists( $block_json ) ) {
			continue;
		}

		// Register the block normally first.
		register_block_type( $block_json );
	}
}
