<?php
/**
 * Sync Ajax Class
 *
 * Handles sync Ajax settings and operations
 *
 * @package WiseSync
 * @since 1.0.0
 */

namespace Sync;

/**
 * Sync Ajax Class
 *
 * This class provides methods to handle AJAX requests securely and efficiently.
 * It includes nonce verification, request sanitization, and dynamic action registration.
 *
 * @since 1.0.0
 */
class Sync_Ajax {

	/**
	 * Whether the request is an Ajax request
	 *
	 * @var bool
	 */
	public $is_ajax = false;

	/**
	 * Current Ajax Action Name
	 *
	 * @var string
	 */
	public $ajax_action_name = '';

	/**
	 * Current Ajax Action Details
	 *
	 * @var array
	 */
	public $ajax_action = '';

	/**
	 * Constructor
	 *
	 * Initializes the class and checks if the current request is an AJAX request.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && ! empty( $_REQUEST['action'] ) ) {
			$this->is_ajax          = true;
			$this->ajax_action_name = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
		}
	}

	/**
	 * Register Ajax actions
	 *
	 * Registers a callback function for a specific AJAX action. Supports both authenticated and unauthenticated users.
	 *
	 * @param string $action_name Action name.
	 * @param string $callback Callback function.
	 * @param string $nonce_action Nonce action for verification.
	 * @param string $nonce_key Nonce key used in the request.
	 * @param string $action_type Specifies if the action is for authenticated ('in'), unauthenticated ('out'), or both (false).
	 * @param bool   $options_capability Restrict to admin users if true.
	 * @param array  $args Additional arguments to pass to the callback.
	 *
	 * @since 1.0.0
	 */
	public function register_ajax_action( $action_name, $callback, $nonce_action, $nonce_key = '_ajax_nonce', $action_type = false, $options_capability = false, $args = array() ) {
		if ( ! $this->is_ajax || $action_name !== $this->ajax_action_name ) {
			return;
		}

		$this->ajax_action = array(
			'callback'           => $callback,
			'action'             => $action_name,
			'options_capability' => $options_capability,
			'nonce_action'       => $nonce_action,
			'nonce_key'          => $nonce_key,
			'args'               => $args,
		);

		if ( 'in' === $action_type ) {
			add_action( 'wp_ajax_' . $action_name, array( $this, 'ajax_callback' ) );
		} elseif ( 'out' === $action_type && false === $options_capability ) {
			add_action( 'wp_ajax_nopriv_' . $action_name, array( $this, 'ajax_callback' ) );
		} else {
			add_action( 'wp_ajax_' . $action_name, array( $this, 'ajax_callback' ) );
			if ( false === $options_capability ) {
				add_action( 'wp_ajax_nopriv_' . $action_name, array( $this, 'ajax_callback' ) );
			}
		}
	}

	/**
	 * Handle Ajax Callback
	 *
	 * Parses the AJAX request, verifies the nonce, and calls the registered callback function.
	 *
	 * @return void
	 */
	public function ajax_callback() {
		if ( $this->ajax_action['options_capability'] && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'error' => __( 'Unauthorized access', 'wisesync' ) ), 403 );
		}

		if ( ! wp_verify_nonce( $this->get_nonce_value( $_REQUEST, $this->ajax_action['nonce_key'] ), $this->ajax_action['nonce_action'] ) ) {
			wp_send_json_error( array( 'error' => __( 'Invalid nonce', 'wisesync' ) ), 403 );
		}

		$ajax_request_data = array(
			'req'    => $this->sanitize_array( $_REQUEST ),
			'action' => $this->ajax_action,
			'args'   => $this->ajax_action['args'],
		);

		apply_filters( 'sync_ajax_request_data', $ajax_request_data );

		call_user_func_array( $this->ajax_action['callback'], array( $ajax_request_data ) );
	}

	/**
	 * Get Nonce Value
	 *
	 * Retrieves the nonce value from the request array. Supports nested keys using dot notation.
	 *
	 * @param array  $request_array Request array.
	 * @param string $nonce_key Nonce key.
	 *
	 * @return string|false Nonce value or false if not found.
	 */
	public function get_nonce_value( $request_array, $nonce_key ) {
		if ( strpos( $nonce_key, '.' ) !== false ) {
			$keys = explode( '.', $nonce_key );
			foreach ( $keys as $key ) {
				if ( ! isset( $request_array[ $key ] ) ) {
					return false;
				}
				$request_array = $request_array[ $key ];
			}
			return $request_array;
		}
		return isset( $request_array[ $nonce_key ] ) ? $request_array[ $nonce_key ] : false;
	}

	/**
	 * Sanitize Array
	 *
	 * Recursively sanitizes an array. Supports custom sanitization rules using dot notation.
	 *
	 * @param array $array_data The array to sanitize.
	 * @param array $rules      Custom sanitization rules.
	 *
	 * @return array Sanitized array.
	 */
	public function sanitize_array( $array_data, $rules = array() ) {
		$processed_rules = array();
		foreach ( $rules as $path => $functions ) {
			$segments = explode( '.', $path );
			$current  = &$processed_rules;
			$last_key = array_pop( $segments );
			foreach ( $segments as $segment ) {
				if ( ! isset( $current[ $segment ] ) ) {
					$current[ $segment ] = array();
				}
				$current = &$current[ $segment ];
			}
			$current[ $last_key ] = $functions;
		}
		return $this->sanitize_array_recursive( $array_data, $processed_rules );
	}

	/**
	 * Recursive Sanitization
	 *
	 * Internal function to recursively sanitize data.
	 *
	 * @param mixed $data  Data to sanitize.
	 * @param array $rules Sanitization rules.
	 *
	 * @return mixed Sanitized data.
	 */
	private function sanitize_array_recursive( $data, $rules = array() ) {
		if ( is_string( $data ) ) {
			$decoded = json_decode( wp_unslash( $data ), true );
			if ( is_array( $decoded ) && json_last_error() === JSON_ERROR_NONE ) {
				return $this->sanitize_array_recursive( $decoded, $rules );
			}
			return sanitize_text_field( wp_unslash( $data ) );
		}
		if ( ! is_array( $data ) ) {
			return sanitize_text_field( wp_unslash( $data ) );
		}
		$result = array();
		foreach ( $data as $key => $value ) {
			$result[ $key ] = $this->sanitize_array_recursive( $value, isset( $rules[ $key ] ) ? $rules[ $key ] : array() );
		}
		return $result;
	}
}
