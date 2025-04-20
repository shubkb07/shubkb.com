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

		// Register the block type.
		register_block_type( $block_json );
	}

	add_filter( 'allowed_block_types_all', 'sync_allowed_block_types_all', 10, 2 );
}

/**
 * Only allow our block in the Site Editor.
 *
 * @param bool|string[]           $allowed_blocks       Array of block slugs or `true` to allow all.
 * @param WP_Block_Editor_Context $block_editor_context The current block editor context.
 * @return bool|string[]                                Filtered allowed blocks.
 */
function sync_allowed_block_types_all( $allowed_blocks, $block_editor_context ) {

	// Get current template being edited.
	$current_template = null;

	error_log( 'Current post type: ' . $block_editor_context->post->post_type );
	error_log( 'Current post ID: ' . $block_editor_context->post->ID );
	error_log( 'Current post name: ' . $block_editor_context->post->post_name );
	error_log( 'Current post type: ' . $block_editor_context->post->post_type );
	error_log( 'Current post status: ' . $block_editor_context->post->post_status );
	error_log( 'Context' . print_r( $block_editor_context->post, true ) );

	if ( true === $allowed_blocks ) {
		$all_blocks     = WP_Block_Type_Registry::get_instance()->get_all_registered();
		$allowed_blocks = array_keys( $all_blocks );
	}

	error_log( 'Current Blocks: ' . print_r( $allowed_blocks, true ) );

	if ( isset( $block_editor_context->post ) ) {
		// Get the current template name if we're in the template editor.
		if ( 'wp_template_part' === $block_editor_context->post->post_type ) {
			$current_template = $block_editor_context->post->post_name;
		}
	}

	// If we're not in the footer template, remove our block from allowed blocks.
	if ( 'footer' !== $current_template ) {
		if ( is_array( $allowed_blocks ) ) {
			$key = array_search( 'sync/cookie-banner', $allowed_blocks, true );
			// If the block is found, remove it from the allowed blocks.
			if ( false !== $key ) {
				unset( $allowed_blocks[ $key ] );
			}
		}
	}

	error_log( 'Blocks After: ' . print_r( $allowed_blocks, true ) );

	return $allowed_blocks;
}
