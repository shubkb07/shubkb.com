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
 * Requires PHP:      8.3
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

// Load Plugin Files.
require_once WSYNC_PLUGIN_DIR . 'autoload.php';
