<?php
/**
 * Sync Mu-Plugin
 *
 * @package WiseSync
 * @since 1.0.0
 */

// Exit early if ABSPATH not defined.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sync_mu_file_notice( $admin_notice_callbacks ) {
	global $sync_plugin;
	// Append Call back.
	$admin_notice_callbacks[] = array( $sync_plugin, 'show_admin_notice_mu_plugin' );
	return $admin_notice_callbacks;
}

// Load Main Mu-Plugin File.
if ( file_exists( WP_PLUGIN_DIR . 'wisesync/includes/load/mu-plugin.php' ) ) {
	require_once WP_PLUGIN_DIR . '/wisesync/includes/load/mu-plugin.php';
} else {

	// Show Admin Notice, to fix Path to Mu Plugin Load File.
	add_filter( 'sync_add_dmin_notice', 'sync_mu_file_notice' );
}
