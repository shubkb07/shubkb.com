<?php
/**
 * WiseSync Autoload
 *
 * Autoloads classes and Options for the WiseSync plugin.
 *
 * @package WISESYNC
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Load Composer autoload if available.
if ( file_exists( WSYNC_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
	require_once WSYNC_PLUGIN_DIR . '/vendor/autoload.php';
}

// Load all function files exist in functions directory.
$functions_dir = WSYNC_PLUGIN_DIR . '/includes/functions/';
$functions     = glob( $functions_dir . '*.php' );
foreach ( $functions as $function_file ) {
	require_once $function_file;
}

/**
 * Autoloads classes for the WiseSync plugin.
 *
 * @param string $class_name The name of the class to autoload.
 */
function sync_autoload( $class_name ) {

	$class_prefix = 'Sync\\';
	if ( strpos( $class_name, $class_prefix ) !== 0 ) {
		return; // Not a class we want to autoload.
	}

	// Remove the prefix from the class name.
	$class_name = substr( $class_name, strlen( $class_prefix ) );

	// Convert the class name to lowercase.
	$class_name = strtolower( $class_name );

	// Replace underscores with dashes.
	$class_name = str_replace( '_', '-', $class_name );

	// Check if class file exists.
	if ( file_exists( WP_PLUGIN_DIR . 'wisesync/includes/classes/class-' . $class_name . '.php' ) ) {
		$class_file = WP_PLUGIN_DIR . 'wisesync/includes/classes/class-' . $class_name . '.php';
	} elseif ( file_exists( WSYNC_PLUGIN_DIR . 'includes/classes/class-' . $class_name . '.php' ) ) {
		$class_file = WSYNC_PLUGIN_DIR . 'includes/classes/class-' . $class_name . '.php';
	} else {
		return; // Class file does not exist.
	}

	require_once $class_file;
}

spl_autoload_register( 'sync_autoload' );
