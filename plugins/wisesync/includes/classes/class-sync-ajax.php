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
	 * Get Current Ajax Action Name
	 *
	 * @var array
	 */
	public $ajax_action_name = '';

	/**
	 * Get Current Ajax Action
	 *
	 * @var array
	 */
	public $ajax_action = '';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// Check if Request Is Post or Get.
		if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && ! empty( $_REQUEST['action'] ) ) {

			// Is Ajax.
			$this->is_ajax = true;

			// Set Ajax Action.
			$this->ajax_action_name = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Register Ajax actions
	 *
	 * @param string $action_name Action name.
	 * @param string $callback Callback function.
	 * @param string $nonce_action Nonce action.
	 * @param string $nonce_key Nonce key.
	 * @param string $action_type Action for (in, out, both/false).
	 * @param bool   $options_capability Only admin.
	 *
	 * @since 1.0.0
	 */
	public function register_ajax_action( $action_name, $callback, $nonce_action, $nonce_key = '_ajax_nonce', $action_type = false, $options_capability = false ) {

		if ( ! $this->is_ajax || $action_name !== $this->ajax_action_name ) {
			return;
		}

		// Set all to $ajax_action in array.
		$this->ajax_action = array(
			'callback'           => $callback,
			'action'             => $action_name,
			'options_capability' => $options_capability,
			'nonce_action'       => $nonce_action,
			'nonce_key'          => $nonce_key,
		);

		// Set as per action_type.
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
	 * A function to parse the Ajax request and call the callback function.
	 *
	 * @return void
	 */
	public function ajax_callback() {

		if ( $this->ajax_action['options_capability'] ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
		}

		$_REQUEST[ $this->ajax_action['nonce_key'] ] = wp_create_nonce( $this->ajax_action['nonce_action'] );

		if ( ! wp_verify_nonce( $this->get_nonce_value( $_REQUEST, $this->ajax_action['nonce_key'] ), $this->ajax_action['nonce_action'] ) ) {
			wp_send_json_error( array( 'error' => __( 'Invalid nonce', 'wisesync' ) ) );
		}

		// Loop through $_REQUEST, Check if its stringigy JSON then decode it.

		$ajax_request_data = array( 'req' => $this->sanitize_array( $_REQUEST ), 'action' => $this->ajax_action );

		call_user_func_array( $this->ajax_action['callback'], array( $ajax_request_data ) );
	}

	/**
	 * Function to get Nonce Value from Request.
	 *
	 * @param array  $request_array Request array.
	 * @param string $nonce_key Nonce key.
	 *
	 * @return string Nonce value.
	 */
	public function get_nonce_value( $request_array, $nonce_key ) {
		if ( strpos( $nonce_key, '.' ) !== false ) {
			$keys = explode( '.', $nonce_key );
			
			// Navigate through the nested array.
			foreach ( $keys as $key ) {
				if ( ! isset( $request_array[ $key ] ) || (
					! is_array( $request_array[ $key ] ) && 
					! is_string( $request_array[ $key ] ) && 
					! is_numeric( $request_array[ $key ] )
				) ) {
					return false;
				}
				$request_array = $request_array[ $key ];
			}
			return $request_array;
		} else {
			// Simple key.
			return isset( $request_array[ $this->ajax_action['nonce_key'] ] ) ? $request_array[ $this->ajax_action['nonce_key'] ] : false;
		}
	}

	/**
	 * Optimized function to sanitize array recursively with support for nested arrays, 
	 * JSON strings, and custom sanitization rules using dot notation.
	 *
	 * @param array $array_data The array to sanitize.
	 * @param array $rules      Custom sanitization rules with dot notation paths as keys and array of functions as values.
	 * @return array Sanitized array
	 */
	public function sanitize_array( $array_data, $rules = array() ) {
		// Pre-process rules for faster lookups.
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
	
		// Process the array recursively.
		return $this->sanitize_array_recursive( $array_data, $processed_rules );
	}

	/**
	 * Internal recursive function to process array sanitization
	 *
	 * @param mixed $data Data to sanitize.
	 * @param array $rules Processed rules structure.
	 * @param array $path Current path (for internal tracking).
	 * @return mixed Sanitized data
	 */
	private function sanitize_array_recursive( $data, $rules = array(), $path = array() ) {
		// Handle JSON strings.
		if ( is_string( $data ) ) {
			$decoded = json_decode( wp_unslash( $data ), true );
			if ( is_array( $decoded ) && json_last_error() === JSON_ERROR_NONE ) {
				return $this->sanitize_array_recursive( $decoded, $rules, $path );
			} if ( is_string( $data ) ) {
				return wp_unslash( $data );
			} elseif ( is_bool( $data ) ) {
				return $data;
			}
		}
	
		// Handle non-array data.
		if ( ! is_array( $data ) ) {
			return sanitize_text_field( wp_unslash( $data ) );
		}
	
		$result = array();
		foreach ( $data as $key => $value ) {
			$current_path  = array_merge( $path, array( $key ) );
			$current_rules = isset( $rules[ $key ] ) ? $rules[ $key ] : array();
		
			// Process nested value.
			$processed_value = $this->sanitize_array_recursive( $value, $current_rules, $current_path );
		
			// Check if there are specific rules for this exact path+key combination.
			if ( isset( $rules[''] ) && '' === $key ) {
				$functions = $rules[''];
				foreach ( $functions as $function ) {
					if ( function_exists( $function ) ) {
						$processed_value = call_user_func( $function, $processed_value );
					}
				}
			} elseif ( isset( $rules[ $key ] ) && isset( $rules[ $key ][''] ) && ! is_array( $processed_value ) ) {
				// This handles leaf nodes with specific rules.
				$functions = $rules[ $key ][''];
				foreach ( $functions as $function ) {
					if ( function_exists( $function ) ) {
						$processed_value = call_user_func( $function, $processed_value );
					}
				}
			}
		
			$result[ $key ] = $processed_value;
		}
	
		return $result;
	}

	/**
	 * Enhanced sanitize array function that supports deep path rules with call_user_func_array
	 * This is the recommended main function to use for array sanitization
	 *
	 * @param array $array_data The array to sanitize.
	 * @param array $rules Custom sanitization rules with dot notation paths as keys and array of functions as values.
	 * @return array Sanitized array
	 */
	public function sanitize_array_with_rules( $array_data, $rules = array() ) {
		// First do default sanitization.
		$sanitized = $this->sanitize_array( $array_data );
	
		// Then apply custom rules.
		foreach ( $rules as $path => $functions ) {
			// Skip empty paths.
			if ( empty( $path ) ) {
				continue;
			}
		
			// Navigate to the target using the path.
			$path_parts = explode( '.', $path );
			$target     = &$sanitized;
			$found      = true;
		
			foreach ( $path_parts as $i => $part ) {
				if ( ! is_array( $target ) || ! isset( $target[ $part ] ) ) {
					$found = false;
					break;
				}

				if ( $i < count( $path_parts ) - 1 ) {
					$target = &$target[ $part ];
				} else {
					// Apply custom sanitization at the final path.
					$value = $target[ $part ];
					foreach ( $functions as $function ) {
						if ( function_exists( $function ) ) {
							$value = call_user_func( $function, $value );
						}
					}
					$target[ $part ] = $value;
				}
			}
		}
	
		return $sanitized;
	}
}
