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
		// Check if Request Is Ajax.
		if ( ! wp_doing_ajax() ) {
			return;
		}

		$this->is_ajax = true;

		// Check if Request Is Post or Get.
		if ( ! isset( $_REQUEST['action'] ) || empty( $_REQUEST['action'] ) ) {
			return;
		}

		// Check Nonce.
		// if ( ! isset( $_REQUEST['_ajax_nonce'] ) || empty( $_REQUEST['_ajax_nonce'] ) || ! wp_verify_nonce( $_REQUEST['_ajax_nonce'], 'sync_ajax' ) ) {
		// return;
		// }

		// Set Ajax Action.
		$this->ajax_action_name = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
	}

	/**
	 * Register Ajax actions
	 *
	 * @param string $action_name Action name.
	 * @param string $callback Callback function.
	 * @param string $action_type Action for (in, out, both).
	 * @param bool   $options_capability Only admin.
	 *
	 * @since 1.0.0
	 */
	public function register_ajax_actions( $action_name, $callback, $action_type = false, $options_capability = false ) {

		error_log( 'Register Ajax Action: ' . $action_name );
		error_log( 'Register Ajax Action by Get: ' . $this->ajax_action_name );
		if ( ! $this->is_ajax || $action_name !== $this->ajax_action_name ) {
			return;
		}

		// Set all to $ajax_action in array.
		$this->ajax_action = array(
			'callback'           => $callback,
			'action'             => $action_name,
			'options_capability' => $options_capability,
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

		$ajax_request_data = array( $_REQUEST );

		call_user_func_array( $this->ajax_action['callback'], array( $ajax_request_data ) );
	}
}
