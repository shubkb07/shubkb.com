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

// Call settings class.
$settings = new Sync_Settings();

/**
 * Initialize settings page.
 *
 * @since 1.0.0
 */
function sync_add_wp_settings_menu( $menu_slug, $menu_name, $position = 100, $create_sync_menu = true, $settings_level = 'site' ) {

    /**
     * Sync Settings Class
     *
     * @var \Sync\Sync_Settings
     */
    global $settings;

    $settings->add_wp_menu( $menu_slug, $menu_name, $position, $create_sync_menu, $settings_level );
}

sync_add_wp_settings_menu( 'wisesync-settings', __( 'WiseSync Settings', 'wisesync' ), 10, true, 'site' );
