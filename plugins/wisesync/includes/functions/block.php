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

		// Register the block normally first
		register_block_type( $block_json );
	}

	// Add filter to control block visibility
	add_filter( 'allowed_block_types_all', 'sync_allowed_block_types_all', 99, 2 );
}

/**
 * Check if we're currently editing a footer template in the Site Editor
 * This version doesn't rely on get_current_screen()
 *
 * @return boolean Whether we're editing a footer template
 */
function is_footer_template_editing() {
	// Check if we're in admin
	if ( ! is_admin() ) {
		return false;
	}

	// Check if it's a REST API request for template parts
	$rest_url = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
	if ( strpos( $rest_url, 'wp-json/wp/v2/template-parts' ) !== false &&
		strpos( $rest_url, 'footer' ) !== false ) {
		return true;
	}

	// Check URL parameters for footer editing
	$current_url = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
	if ( strpos( $current_url, 'site-editor.php' ) !== false &&
		strpos( $current_url, 'footer' ) !== false ) {
		return true;
	}

	return false;
}

/**
 * Only allow our block in the Site Editor footer template.
 *
 * @param bool|string[]           $allowed_blocks       Array of block slugs or `true` to allow all.
 * @param WP_Block_Editor_Context $block_editor_context The current block editor context.
 * @return bool|string[]                                Filtered allowed blocks.
 */
function sync_allowed_block_types_all( $allowed_blocks, $block_editor_context ) {
	$post       = isset( $block_editor_context->post ) ? $block_editor_context->post : null;
	$block_name = 'sync/cookie-banner';

	if ( ! $post ) {
		// No post in context - remove our block to be safe
		if ( true === $allowed_blocks ) {
			$all_blocks     = WP_Block_Type_Registry::get_instance()->get_all_registered();
			$allowed_blocks = array_keys( $all_blocks );
		}
		return array_diff( $allowed_blocks, array( $block_name ) );
	}

	// Convert true to array of blocks if needed
	if ( true === $allowed_blocks ) {
		$all_blocks     = WP_Block_Type_Registry::get_instance()->get_all_registered();
		$allowed_blocks = array_keys( $all_blocks );
	}

	// We're in a footer template part
	if ( $post->post_type === 'wp_template_part' && strpos( $post->post_name, 'footer' ) !== false ) {
		// Check if our block already exists in the content
		if ( strpos( $post->post_content, '"name":"' . $block_name . '"' ) !== false ||
			strpos( $post->post_content, '<!-- wp:' . $block_name . ' ' ) !== false ) {
			// Block already exists, remove it from allowed blocks
			return array_diff( $allowed_blocks, array( $block_name ) );
		}

		// We're in footer and block doesn't exist yet, make sure it's allowed
		if ( ! in_array( $block_name, $allowed_blocks ) ) {
			$allowed_blocks[] = $block_name;
		}
		return $allowed_blocks;
	} else {
		// Not in footer template, remove our block
		return array_diff( $allowed_blocks, array( $block_name ) );
	}
}

/**
 * Alternative approach: Modify block.json during registration
 * This function runs before register_block_type and modifies the block metadata
 */
function modify_block_registration( $metadata ) {
	// Only modify our specific block
	if ( isset( $metadata['name'] ) && $metadata['name'] === 'sync/cookie-banner' ) {
		// Set parent to limit where this block can be used
		// This is a powerful way to restrict blocks to specific parent blocks or contexts
		$metadata['parent'] = array( 'core/template-part/footer' );

		// Allow only one instance
		if ( ! isset( $metadata['supports'] ) ) {
			$metadata['supports'] = array();
		}
		$metadata['supports']['multiple'] = false;
	}
	return $metadata;
}
add_filter( 'block_type_metadata', 'modify_block_registration', 10, 1 );

/**
 * Add server-side rendering to prevent multiple instances
 */
function register_block_render_callbacks() {
	$block_name = 'sync/cookie-banner';

	// Check if the block is registered
	if ( WP_Block_Type_Registry::get_instance()->is_registered( $block_name ) ) {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( $block_name );

		// Add a render callback that prevents multiple instances
		$block->render_callback = function ( $attributes, $content, $block ) {
			static $has_rendered = false;

			if ( $has_rendered ) {
				return ''; // Return empty if already rendered once
			}

			$has_rendered = true;
			return $content; // Return the content for the first instance
		};
	}
}
add_action( 'init', 'register_block_render_callbacks', 20 );
