<?php
/**
 * Load WiseSync Plugin
 *
 * This file is responsible for loading the WiseSync plugin.
 *
 * @package WISESYNC
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Load Global WiseSync Plugin Classes.
require_once WSYNC_PLUGIN_DIR . 'includes/load/classes.php';

// Register Blocks Category.
add_filter( 'block_categories_all', 'register_sync_blocks_category' );

// Load Plugin Blocks.
add_action(
	'init',
	function () {
		register_sync_block_type( WSYNC_PLUGIN_DIR . 'blocks/build/' );
	}
);
