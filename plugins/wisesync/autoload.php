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

error_log( 'WiseSync Autoloading...');

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
function wisesync_autoload( $class_name ) {
	// Check if the class name starts with the plugin prefix.
	if ( strpos( $class_name, 'WiseSync' ) !== 0 ) {
		return;
	}

	// Replace the namespace separator with directory separator.
	$class_name = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );

	// Construct the file path.
	$file_path = WSYNC_PLUGIN_DIR . '/includes/classes/' . $class_name . '.php';

	// Check if the file exists and include it.
	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}

spl_autoload_register( 'wisesync_autoload' );
