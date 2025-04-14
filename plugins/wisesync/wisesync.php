<?php
/**
 * WiseSync Plugin
 *
 * @package WISESYNC
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WSYNC_VERSION', '1.0.0' );
define( 'WSYNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WSYNC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WSYNC_PLUGIN_FILE', __FILE__ );
define( 'WSYNC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WSYNC_PLUGIN_NAME', 'WiseSync' );
define( 'WSYNC_PLUGIN_SLUG', 'wisesync' );
define( 'WSYNC_PLUGIN_TEXTDOMAIN', 'wisesync' );
define( 'WSYNC_PLUGIN_PREFIX', 'wisesync_' );

// Load Plugin Files.
require_once WSYNC_PLUGIN_DIR . 'includes/autoload.php';
