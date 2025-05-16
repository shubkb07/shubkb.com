<?php
/**
 * SyncCache Create Advanced Cache
 *
 * This file is responsible for creating and setting up the advanced-cache.php drop-in.
 *
 * @package SyncCache
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access.' );
}

// Include the Sync_Advanced_Cache class.
require_once __DIR__ . '/class-advanced-cache.php';

// Get plugin options.
$options = get_option( 'sync_cache_settings', array() );

// Instantiate the class.
$sync_cache = new Sync\Sync_Advanced_Cache( $options );

// Create the advanced-cache.php file.
$sync_cache->create_advanced_cache();

// Add WP_CACHE constant to wp-config.php.
$sync_cache->add_wp_cache_constant();

// Redirect back to settings page.
wp_redirect( admin_url( 'options-general.php?page=sync_cache_settings&cache_setup=1' ) );
exit;
