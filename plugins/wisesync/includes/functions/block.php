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
}

/**
 * Only allow our block in the Site Editor.
 *
 * @param bool|string[]           $allowed_block_types  Array of block slugs or `true` to allow all.
 * @param WP_Block_Editor_Context $editor_context       The current block editor context.
 * @return bool|string[]                                Filtered allowed blocks.
 */
function sync_allowed_block_types_all( $allowed_block_types, $editor_context ) {
	// Change this to the name you used in your block.json "name" property.
	$our_block = 'sync/cookie-bannera';

	$url  = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER[ 'HTTPS' ] ? 'https://' : 'http://';
	$url .= $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
	error_log( 'URL: ' . $url );
	error_log( 'allowed_block_types_all' );
	error_log( strval( get_current_screen() ) );
	error_log( strval( 'name' ) );
	error_log( $editor_context->name );

	// 1) If we're in the Site Editor (Full Site Editing), leave everything allowed.
	if ( 'core/edit-site' === $editor_context->name ) {
		return true;
	}

	// 2) Otherwise (post or page editors), strip out our block.
	// If WP gave us `true` (meaning “all blocks”), grab the registry first.
	if ( true === $allowed_block_types ) {
		if ( ! class_exists( 'WP_Block_Type_Registry' ) ) {
			return $allowed_block_types; // bail if something’s very wrong.
		}
		$registered          = WP_Block_Type_Registry::get_instance()->get_all_registered();
		$allowed_block_types = array_keys( $registered );
	}

	// Remove our block from the allowed list.
	if ( is_array( $allowed_block_types ) ) {
		$allowed_block_types = array_diff( $allowed_block_types, array( $our_block ) );
	}

	return $allowed_block_types;
}

add_filter( 'allowed_block_types_all', 'sync_allowed_block_types_all', 10, 2 );
