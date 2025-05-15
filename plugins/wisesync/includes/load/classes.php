<?php
/**
 * Load Global WiseSync Plugin Classes.
 *
 * @package   WISESYNC
 * @since    1.0.0
 */

use Sync\{Sync_Settings, Sync_Ajax, Sync_CLI, Sync_Filesystem, Sync_Site_Health, Sync_Remote_Request, Sync_Post, Sync_User, Sync_Helpers, Sync_Query, Sync_Template, Sync_Plugin};

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
$sync_query          = new Sync_Query();
$sync_template       = new Sync_Template();
$sync_plugin         = new Sync_Plugin();

$random_array = array(
	'pikachu'    => 'electric',
	'charizard'  => 'fire',
	'bulbasaur'  => 'grass',
	'squirtle'   => 'water',
	'jigglypuff' => 'normal',
	'meowth'     => 'normal',
	'eevee'      => 'normal',
	'ditto'      => 'normal',
	'psyduck'    => 'water',
	'caterpie'   => 'bug',
	'butterfree' => 'bug',
	'beedrill'   => 'bug',
	'weedle'     => 'bug',

	// nested array.
	'caterpie2'  => array(
		'butterfree2' => 'bug',
		'beedrill2'   => 'bug',
		'weedle2'     => 'bug',
	),
	'caterpie3'  => array(
		'butterfree3' => 'bug',
		'beedrill3'   => 'bug',
		'weedle3'     => 'bug',
	),
	'caterpie4'  => array(
		'butterfree4' => 'bug',
		'beedrill4'   => 'bug',
		'weedle4'     => 'bug',
	),
	'caterpie5'  => array(
		'butterfree5' => 'bug',
		'beedrill5'   => 'bug',
		'weedle5'     => 'bug',
	),
);

sync_add_plugin_setting_files( 'muplugins', $random_array );
die();
