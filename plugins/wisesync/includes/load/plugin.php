<?php
/**
 * Load the Plugin.
 *
 * @package   WiseSync
 * @since     1.0.0
 */

/**
 * Activation Hook Function.
 */
function sync_activation() {
	do_action( 'sync_activation' );
}

/**
 * Deactivation Hook Function.
 */
function sync_deactivation() {
	do_action( 'sync_deactivation' );
}

// Call the activation function on plugin activation.
register_activation_hook( __FILE__, 'sync_activation' );

// Call the deactivation function on plugin deactivation.
register_deactivation_hook( __FILE__, 'sync_deactivation' );
