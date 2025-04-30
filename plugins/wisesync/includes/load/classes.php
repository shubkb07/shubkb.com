<?php
/**
 * Load Global WiseSync Plugin Classes.
 *
 * @package   WISESYNC
 * @since    1.0.0
 */

use Sync\{Sync_Settings, Sync_Ajax};

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
 
$sync_ajax     = new Sync_Ajax();
$sync_settings = new Sync_Settings();

/**
 * Sync Register Ajax Action.
 *
 * @param Array $p Array of parameters.
 *
 * @return void
 */
function sync_test( $p ) {
	// Test function for AJAX.
	error_log(print_r($p, true));
	wp_send_json(
		array(
			'status' => 'success',
			'data'   => $p,
		)
	);
	wp_die();
}

$sync_ajax->register_ajax_actions( 'sync_test', 'sync_test' );
