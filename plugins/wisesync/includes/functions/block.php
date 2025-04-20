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

		// For cookie-banner block, only register if we're in the right context.
		$block_data = json_decode( file_get_contents( $block_json ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( isset( $block_data['name'] ) && 'sync/cookie-banner' === $block_data['name'] ) {
			// Only register in the Site Editor and specifically in the footer template.
			if ( is_footer_template_editing() ) {
				register_block_type( $block_json );
			}
		} else {
			// Register other blocks normally.
			register_block_type( $block_json );
		}
	}
}

/**
 * Check if we're currently editing a footer template in the Site Editor
 *
 * @return boolean Whether we're editing a footer template
 */
function is_footer_template_editing() {
	// Check if we're in admin.
	if ( ! is_admin() ) {
		return false;
	}

	// Check for Site Editor or Template Part editor screen.
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'site-editor', 'appearance_page_gutenberg-edit-site' ), true ) ) {
		return false;
	}

	// Try to determine if we're editing a footer template.
	// This is tricky because the information isn't always available.
	$current_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

	// Look for 'footer' in the URL parameters which often indicates editing a footer template.
	if ( strpos( $current_url, 'footer' ) !== false ) {
		return true;
	}

	// As a fallback, we'll check for a transient that we'll set when a footer is being edited.
	// (You would need additional code to set this transient when a footer is opened for editing).
	return (bool) get_transient( 'is_editing_footer_template' );
}

/**
 * Alternative approach: Check for footer template in editor context.
 * This function hooks into block editor context to determine if we're editing a footer.
 *
 * @param array                   $editor_settings The editor settings.
 * @param WP_Block_Editor_Context $block_editor_context The block editor context.
 *
 * @return array Modified editor settings.
 */
function set_footer_template_editing_status( $editor_settings, $block_editor_context ) {
	$post = isset( $block_editor_context->post ) ? $block_editor_context->post : null;

	if ( $post && 'wp_template_part' === $post->post_type && false !== strpos( $post->post_name, 'footer' ) ) {
		// We're editing a footer template part.
		set_transient( 'is_editing_footer_template', true, HOUR_IN_SECONDS );

		// Also dynamically register our block if it wasn't registered yet.
		$block_type = WP_Block_Type_Registry::get_instance();
		if ( ! $block_type->is_registered( 'sync/cookie-banner' ) ) {
			// Find and register the block here.
			$plugin_dir = plugin_dir_path( __FILE__ );
			$block_json = $plugin_dir . 'blocks/cookie-banner/block.json';
			if ( file_exists( $block_json ) ) {
				register_block_type( $block_json );
			}
		}
	} else {
		delete_transient( 'is_editing_footer_template' );
	}

	return $editor_settings;
}
add_filter( 'block_editor_settings_all', 'set_footer_template_editing_status', 10, 2 );

/**
 * Unregister our block except in specific contexts to ensure it's completely hidden
 */
function maybe_unregister_cookie_banner_block() {
	// Skip if we're in the footer template editing context.
	if ( is_footer_template_editing() ) {
		return;
	}

	// Unregister the block everywhere else.
	$block_type = WP_Block_Type_Registry::get_instance();
	if ( $block_type->is_registered( 'sync/cookie-banner' ) ) {
		unregister_block_type( 'sync/cookie-banner' );
	}
}

add_action( 'init', 'maybe_unregister_cookie_banner_block', 20 );
