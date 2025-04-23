<?php
/**
 * Plugin main file.
 *
 * @package   WISESYNC
 * @since    1.0.0
 *
 * Plugin Name:       WiseSync Plugin
 * Plugin URI:        https://shubkb.com
 * Description:       All-in-one solution for WordPress users to use everything WiseSync has to offer to make them successful on the web.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      8.2
 * Author:            Shubham Kumar Bansal <shub@shubkb.com>
 * Author URI:        https://shubkb.com
 * License:           Apache License 2.0
 * License URI:       https://www.apache.org/licenses/LICENSE-2.0
 * Text Domain:       wisesync
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
define( 'WSYNC_PLUGIN_ASSETS_URL', WSYNC_PLUGIN_URL . 'assets/' );
define( 'WSYNC_PLUGIN_ASSETS_DIR', WSYNC_PLUGIN_DIR . 'assets/' );
define( 'WSYNC_PLUGIN_JS_URL', WSYNC_PLUGIN_ASSETS_URL . 'js/' );
define( 'WSYNC_PLUGIN_CSS_URL', WSYNC_PLUGIN_ASSETS_URL . 'css/' );
define( 'WSYNC_PLUGIN_IMG_URL', WSYNC_PLUGIN_ASSETS_URL . 'img/' );
define( 'WSYNC_PLUGIN_JS_DIR', WSYNC_PLUGIN_ASSETS_DIR . 'js/' );
define( 'WSYNC_PLUGIN_CSS_DIR', WSYNC_PLUGIN_ASSETS_DIR . 'css/' );
define( 'WSYNC_PLUGIN_IMG_DIR', WSYNC_PLUGIN_ASSETS_DIR . 'img/' );

// Load Plugin Files.
require_once WSYNC_PLUGIN_DIR . 'autoload.php';

// Load Plugin.
require_once WSYNC_PLUGIN_DIR . 'includes/load.php';
