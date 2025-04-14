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
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Autoloads classes for the WiseSync plugin.
 *
 * @param string $class_name The name of the class to autoload.
 */
function wisesync_autoload( $class_name ) {

}
