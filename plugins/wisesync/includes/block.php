<?php
/**
 * Blocks
 *
 * Register Block Categories and Blocks.
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

add_action( 'block_categories_all', 'register_sync_blocks_category', 10, 2 );
