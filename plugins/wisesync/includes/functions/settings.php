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

    $sync_settings->add_wp_menu( $menu_slug, $menu_name, $position, $create_sync_menu, $settings_level );
}

/**
 * Add Sync Menu.
 *
 * @param string $wp_menu_slug WP Menu slug.
 * @param string $menu_name Menu name.
 * @param string|false $menu_slug Menu slug.
 * @param string|null $icon_url Icon URL.
 * @param int|null $position Menu position.
 */
function sync_add_sync_menu( $wp_menu_slug, $menu_name, $menu_slug = false, $icon_url = null, $position = null, $sub_menu_support = false ) {
    global $sync_settings;

    $sync_settings->add_sync_menus( $wp_menu_slug, $menu_name, $menu_slug, $icon_url, $position, $sub_menu_support );
}

/**
 * Add Sync Sub Menu.
 *
 * @param string $parent_menu_slug Parent menu slug.
 * @param string $menu_name Menu name.
 * @param string $menu_slug Menu slug.
 * @param string|null $icon_url Icon URL.
 * @param int|null $position Menu position.
 */
function sync_add_sync_sub_menu( $parent_menu_slug, $menu_name, $menu_slug, $icon_url = null, $position = null ) {
    global $sync_settings;

    $sync_settings->add_sync_sub_menus( $parent_menu_slug, $menu_name, $menu_slug, $icon_url, $position );
}

add_action( 'sync_add_settings_page', function () {
    sync_add_wp_settings_menu( 'settings', __( 'WiseSync Settings', 'wisesync' ), 10, true, 'site' );

    // Example usage of sync_add_sync_menu with sub-menus
    sync_add_sync_menu( 'settings', __( 'Sync Dashboard', 'wisesync' ), 'dashboard', null, 20, true );
    sync_add_sync_sub_menu( 'dashboard', __( 'Sync Logs', 'wisesync' ), 'logs', null, 30 );
    sync_add_sync_sub_menu( 'dashboard', __( 'Sync Settings', 'wisesync' ), 'settings', null, 40 );

    // Example usage of sync_add_sync_menu without sub-menus
    sync_add_sync_menu( 'settings', __( 'Sync Reports', 'wisesync' ), 'sync_reports', null, 50 );

    // Another example with sub-menus
    sync_add_sync_menu( 'settings', __( 'Advanced Sync', 'wisesync' ), 'advanced', null, 60, true );
    sync_add_sync_sub_menu( 'advanced', __( 'Sync Tools', 'wisesync' ), 'tools', null, 70 );
    sync_add_sync_sub_menu( 'advanced', __( 'Sync Diagnostics', 'wisesync' ), 'diagnostics', null, 80 );
});
