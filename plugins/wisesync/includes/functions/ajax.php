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
 * @param String   $action Action name.
 * @param String   $for    Logged or not Logged Users.
 * @param Callback $callback Callback function.
 */
function sync_register_ajax_action( $action, $for, $callback ) {
	global $sync_ajax;

	if ( ! $sync_ajax->is_ajax || ! $sync_ajax->is_action( $action ) ) {
		return;
	}
}
