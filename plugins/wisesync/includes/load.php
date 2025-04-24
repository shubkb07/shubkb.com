<?php
/**
 * Load WiseSync Plugin
 *
 * This file is responsible for loading the WiseSync plugin.
 *
 * @package WISESYNC
 * @since   1.0.0
 */

use Sync\Sync_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register Blocks Category.
add_filter( 'block_categories_all', 'register_sync_blocks_category' );

// Load Plugin Blocks.
add_action(
	'init',
	function () {
		register_sync_block_type( WSYNC_PLUGIN_DIR . 'blocks/build/' );
	}
);

// Call settings class.
$settings = new Sync_Settings();
