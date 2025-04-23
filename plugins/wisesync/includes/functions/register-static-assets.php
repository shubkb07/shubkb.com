<?php
/**
 * Register Static Assets
 *
 * Helper functions for registering static assets.
 *
 * @package WISESYNC
 * @since 1.0.0
 */

/**
 * Register Static Assets
 *
 * This function registers static assets for the plugin.
 *
 * @package WISESYNC
 * @since 1.0.0
 */
function sync_register_static_assets() {
	$styles_files = array(
		'admin' => WSYNC_PLUGIN_CSS_URL . 'admin.css',
	);

	$scripts_files = array(
		'event' => WSYNC_PLUGIN_JS_URL . 'event.js',
		'admin' => WSYNC_PLUGIN_JS_URL . 'admin.js',
	);

	// Register styles.
	foreach ( $styles_files as $handle => $src ) {
		wp_register_style(
			$handle,
			$src,
			array(),
			WSYNC_VERSION,
			'all'
		);
	}

	// Register scripts.
	foreach ( $scripts_files as $handle => $src ) {
		wp_register_script(
			$handle,
			$src,
			array(),
			WSYNC_VERSION,
			true
		);
	}
}

add_action( 'admin_enqueue_scripts', 'sync_register_static_assets' );
add_action( 'wp_enqueue_scripts', 'sync_register_static_assets' );

/**
 * Admin Enqueue Static Assets.
 *
 * This function enqueues static assets for the admin area.
 */
function sync_load_admin_static_assets() {

	// Add styles for admin area.
	wp_enqueue_style( 'admin' );

	// Add scripts for admin area.
	wp_enqueue_script( 'event' );
	wp_enqueue_script( 'admin' );
}

add_action( 'admin_enqueue_scripts', 'sync_load_admin_static_assets' );

/**
 * Frontend Enqueue Static Assets.
 *
 * This function enqueues static assets for the frontend.
 */
function sync_load_frontend_static_assets() {
	wp_enqueue_script( 'event' );
}

add_action( 'wp_enqueue_scripts', 'sync_load_frontend_static_assets' );
