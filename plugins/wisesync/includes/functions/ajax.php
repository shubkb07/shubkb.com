<?php
/**
 * WiseSync Ajax Functions
 *
 * Handles WiseSync Ajax settings and operations
 *
 * @package WiseSync
 * @since 1.0.0
 */

/**
 * Sync Register Ajax Action.
 *
 * @param string $action_name Action name.
 * @param string $callback Callback function.
 * @param string $nonce_action Nonce action.
 * @param string $nonce_key Nonce key.
 * @param string $action_type Action for (in, out, both/false).
 * @param bool   $options_capability Only admin.
 */
function sync_register_ajax_action( $action_name, $callback, $nonce_action, $nonce_key = '_ajax_nonce', $action_type = false, $options_capability = false ) {
	global $sync_ajax;

	$sync_ajax->register_ajax_action( $action_name, $callback, $nonce_action, $nonce_key = '_ajax_nonce', $action_type = false, $options_capability = false );
}
