<?php
/**
 * Sync Settings Class
 *
 * Handles sync settings and operations
 *
 * @package WiseSync
 * @since 1.0.0
 */

namespace WiseSync;

/**
 * Sync Settings Class
 *
 * @since 1.0.0
 */
class Sync_Settings {
	/**
	 * Sync Settings constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'init_settings_page' ) );
	}

	/**
	 * Initialize settings.
	 *
	 * @since 1.0.0
	 */
	public function init_settings() {
		add_menu_page( 'Sync', 'Sync', 'manage_options', 'sync-settings-menu', array( $this, 'settings_page' ), 'dashicons-sort', 23 );
	}

	/**
	 * Settings page.
	 *
	 * @since 1.0.0
	 */
	public function settings_page() {
		echo 'Loading settings page...';
	}

	/**
	 * Sync settings.
	 *
	 * @since 1.0.0
	 */
	public function register_setting() {
	}
}
