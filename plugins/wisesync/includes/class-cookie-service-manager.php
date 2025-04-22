<?php
/**
 * Cookie Service Manager Class
 *
 * Handles service registration and permission checking for cookie consent
 *
 * @package WiseSync
 */

namespace WiseSync;

defined( 'ABSPATH' ) || exit;

/**
 * Cookie Service Manager Class
 */
class Cookie_Service_Manager {

	/**
	 * Registered services
	 *
	 * @var array
	 */
	private $registered_services = array();

	/**
	 * Singleton instance
	 *
	 * @var Cookie_Service_Manager
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance
	 *
	 * @return Cookie_Service_Manager
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue necessary scripts
	 */
	public function enqueue_scripts() {
		// Enqueue script with services data
		wp_localize_script(
			'wisesync-cookie-banner-view',
			'wisesyncCookieServices',
			array(
				'services' => $this->registered_services,
			)
		);
	}

	/**
	 * Register a cookie service
	 *
	 * @param string $service Service slug
	 * @param string $name Service name
	 * @param string $type Service type (functional, analytical, advertising, tracking)
	 * @return bool Success status
	 */
	public function register_cookie_service( $service, $name, $type ) {
		// Validate service type
		$valid_types = array( 'functional', 'analytical', 'advertising', 'tracking' );
		if ( ! in_array( $type, $valid_types ) ) {
			return false;
		}

		// Register the service
		$this->registered_services[ $service ] = array(
			'name'       => $name,
			'type'       => $type,
			'registered' => current_time( 'mysql' ),
		);

		return true;
	}

	/**
	 * Check if the given permission is granted
	 *
	 * @param string $permission Permission to check
	 * @return bool Whether permission is granted
	 */
	public function is_cookie_permission_to( $permission ) {
		// Necessary cookies always return true
		if ( $permission === 'necessary' ) {
			return true;
		}

		// Validate permission type
		$valid_permissions = array( 'functional', 'analytical', 'advertising', 'tracking' );
		if ( ! in_array( $permission, $valid_permissions ) ) {
			return false;
		}

		// Server-side can't directly read client cookies, so we'll handle this mainly through JavaScript
		// This function is provided as an API for server-side code to use with proper JavaScript fallbacks

		// Return false by default on server-side - the actual check will happen on the client
		return false;
	}

	/**
	 * Get all registered services
	 *
	 * @return array Registered services
	 */
	public function get_registered_services() {
		return $this->registered_services;
	}
}

/**
 * Helper function to register a cookie service
 *
 * @param string $service Service slug
 * @param string $name Service name
 * @param string $type Service type
 * @return bool Success status
 */
function register_cookie_service( $service, $name, $type ) {
	return Cookie_Service_Manager::instance()->register_cookie_service( $service, $name, $type );
}

/**
 * Helper function to check if a cookie permission is granted
 *
 * @param string $permission Permission to check
 * @return bool Whether permission is granted
 */
function is_sync_cookie_permission_to( $permission ) {
	return Cookie_Service_Manager::instance()->is_cookie_permission_to( $permission );
}
