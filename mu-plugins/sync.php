<?php
/**
 * Sync Mu-Plugin
 * 
 * Plugin Name:       WiseSync Plugin Mu Plugin
 * Plugin URI:        https://shubkb.com
 * Description:       All-in-one solution for WordPress users to use everything WiseSync has to offer to make them successful on the web.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Author:            Shubham Kumar Bansal <shub@shubkb.com>
 * Author URI:        https://shubkb.com
 * License:           Apache License 2.0
 * License URI:       https://www.apache.org/licenses/LICENSE-2.0
 *
 * @package WiseSync
 * @since 1.0.0
 */

// Exit early if ABSPATH not defined.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sync Mu-Plugin Class
 *
 * @param array $admin_notice_callbacks Admin notice callbacks.
 *
 * @return array Modified admin notice callbacks.
 */
function sync_mu_file_notice( $admin_notice_callbacks ) {
	global $sync_plugin;
	// Append Call back.
	$admin_notice_callbacks[] = array( $sync_plugin, 'show_admin_notice_mu_plugin' );
	return $admin_notice_callbacks;
}

// Path to Mu-Plugin Load File.
$sync_my_plugin_path = WP_PLUGIN_DIR . '/wisesync/includes/load/mu-plugin.php';

// Load Main Mu-Plugin File.
if ( file_exists( $sync_my_plugin_path ) ) {
	require_once $sync_my_plugin_path;
} else {

	// Show Admin Notice, to fix Path to Mu Plugin Load File.
	add_filter( 'sync_add_dmin_notice', 'sync_mu_file_notice' );
}
