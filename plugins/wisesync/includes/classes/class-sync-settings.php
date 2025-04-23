<?php
/**
 * Sync Settings Class
 *
 * Handles sync settings and operations
 *
 * @package WiseSync
 * @since 1.0.0
 */

namespace Sync;

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
	public function init_settings_page() {
		add_menu_page( 'Sync', 'Sync', 'manage_options', 'sync-settings-menu', array( $this, 'settings_page' ), 'dashicons-sort', is_network_admin() ? 23 : 63 );
	}

	/**
	 * Settings page.
	 *
	 * @since 1.0.0
	 */
	public function settings_page() {
		?>
<div class="sync-wrapper">
  <div class="sync-sidebar">
    <div class="sync-logo">Sync Panel</div>
    <button class="sync-toggle-button" onclick="sync_toggleMenu()">☰</button>
    <ul class="sync-menu">
      <li><a href="#">Dashboard</a></li>
      <li class="sync-has-submenu">
        <a href="#" onclick="sync_toggleSubMenu(event)">Settings</a>
        <ul class="sync-submenu">
          <li><a href="#">General</a></li>
          <li><a href="#">Advanced</a></li>
        </ul>
      </li>
      <li><a href="#">Logs</a></li>
    </ul>
  </div>

  <div class="sync-content">
    <h1>Welcome to Sync Dashboard</h1>
    <p>This is your inner admin panel. Add whatever you want here.</p>
  </div>
</div>

		<?php
	}

	/**
	 * Sync settings.
	 *
	 * @since 1.0.0
	 */
	public function register_setting() {
	}
}
