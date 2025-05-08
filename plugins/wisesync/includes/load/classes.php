<?php
/**
 * Load Global WiseSync Plugin Classes.
 *
 * @package   WISESYNC
 * @since    1.0.0
 */

use Sync\{Sync_Settings, Sync_Ajax, Sync_CLI, Sync_Filesystem, Sync_Site_Health, Sync_Remote_Request, Sync_Post, Sync_User};

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$sync_helpers        = new Sync_Helpers();
$sync_ajax           = new Sync_Ajax();
$sync_settings       = new Sync_Settings();
$sync_cli            = new Sync_CLI();
$sync_filesystem     = new Sync_Filesystem();
$sync_site_health    = new Sync_Site_Health();
$sync_remote_request = new Sync_Remote_Request();
$sync_post           = new Sync_Post();
$sync_user           = new Sync_User();
