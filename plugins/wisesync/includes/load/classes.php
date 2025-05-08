<?php
/**
 * Load Global WiseSync Plugin Classes.
 *
 * @package   WISESYNC
 * @since    1.0.0
 */

use Sync\{Sync_Settings, Sync_Ajax, Sync_CLI, Sync_Filesystem};

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
 
$sync_ajax       = new Sync_Ajax();
$sync_settings   = new Sync_Settings();
$sync_cli        = new Sync_CLI();
$sync_filesystem = new Sync_Filesystem();
