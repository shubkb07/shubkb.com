<?php
/**
 * WiseSync Settings Functions
 *
 * Handles WiseSync settings and operations
 *
 * @package WiseSync
 * @since 1.0.0
 */

use Sync\Sync_Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Sync Settings Class.
 *
 * @global Sync_Settings $sync_settings
 */
$sync_settings = new Sync_Settings();

/**
 * Initialize settings page.
 *
 * @since 1.0.0
 */
function sync_add_wp_settings_menu( $menu_slug, $menu_name, $position = 100, $create_sync_menu = true, $settings_level = 'site' ) {

    /**
     * Sync Settings Class
     *
     * @global Sync_Settings
     */
    global $sync_settings;

    error_log ( 'sync_add_wp_settings_menu called' );

    $sync_settings->add_wp_menu( $menu_slug, $menu_name, $position, $create_sync_menu, $settings_level );
}

add_action( 'sync_add_settings_page', function () {
    sync_add_wp_settings_menu( 'settings', __( 'WiseSync Settings', 'wisesync' ), 10, true, 'site' );
});
