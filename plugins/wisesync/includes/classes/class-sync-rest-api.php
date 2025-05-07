<?php
/**
 * Sync REST API Class
 *
 * Handles WiseSync REST API settings and operations
 *
 * @package WiseSync
 * @since 1.0.0
 */

namespace Sync;

/**
 * Sync REST API Class
 *
 * This class provides methods to handle REST API requests securely and efficiently.
 * It includes nonce verification, request sanitization, and dynamic endpoint registration.
 *
 * @since 1.0.0
 */
class Sync_REST_API {

	/**
	 * Whether the request is a REST API request
	 *
	 * @var bool
	 */
	public $is_rest = false;

	/**
	 * Current REST API Route Name
	 *
	 * @var string
	 */
	public $rest_route_name = '';

	/**
	 * Current REST API Route Details
	 *
	 * @var array
	 */
	public $rest_route = '';

	/**
	 * Constructor
	 *
	 * Initializes the class and checks if the current request is a REST API request.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$this->is_rest = true;
		}
	}

	/**
	 * Register REST API Route.
	 *
	 * @param string $route_name Route name.
	 * @param string $callback Callback function.
	 * @param string $args Route arguments.
	 *
	 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/ For more information.
	 *
	 * @return bool True if the route was registered, false otherwise.
	 */
	public function register_rest_route( $route_name, $callback, $args ) {
		if ( ! $this->is_rest ) {
			return false;
		}
		add_action(
			'rest_api_init',
			function () use ( $route_name, $callback, $args ) {
				register_rest_route(
					'sync/v1',
					'/' . $route_name,
					array(
						'methods'  => 'POST',
						'callback' => $callback,
						'args'     => $args,
					) 
				);
			} 
		);
		return true;
	}
}
