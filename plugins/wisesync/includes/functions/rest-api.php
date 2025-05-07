<?php
/**
 * Sync REST API Functions
 *
 * Handles WiseSync REST API settings and operations
 *
 * @package WiseSync
 * @since 1.0.0
 */

/**
 * Sync Register REST API Route.
 *
 * @param string $route_name Route name.
 * @param string $callback Callback function.
 * @param string $args Route arguments.
 *
 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/ For more information.
 *
 * @return bool True if the route was registered, false otherwise.
 */
function sync_register_rest_route( $route_name, $callback, $args ) {
	global $sync_rest_api;

	if ( ! $sync_rest_api->is_rest ) {
		return false;
	}

	return $sync_rest_api->register_rest_route( $route_name, $callback, $args );
}
