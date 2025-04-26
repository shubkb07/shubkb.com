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
function sync_add_settings_menu() {}
