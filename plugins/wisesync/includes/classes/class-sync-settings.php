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
		<div id="sync-dashboard" class="sync-dashboard">
  <nav class="sync-sidebar" aria-label="Sync Dashboard Sidebar">
    <ul class="sync-menu">
      <li class="sync-menu-item active">
        <button type="button"
                class="sync-menu-link"
                data-sync-target="dashboard"
                aria-controls="sync-content-dashboard"
                aria-expanded="true">
          Dashboard
        </button>
      </li>
      <li class="sync-menu-item has-submenu">
        <button type="button"
                class="sync-menu-link"
                data-sync-target="settings"
                aria-controls="sync-submenu-settings"
                aria-expanded="false">
          Settings
        </button>
        <ul id="sync-submenu-settings" class="sync-submenu" hidden>
          <li class="sync-submenu-item">
            <button type="button"
                    class="sync-submenu-link"
                    data-sync-target="general"
                    aria-controls="sync-content-general"
                    aria-expanded="false">
              General
            </button>
          </li>
          <li class="sync-submenu-item">
            <button type="button"
                    class="sync-submenu-link"
                    data-sync-target="advanced"
                    aria-controls="sync-content-advanced"
                    aria-expanded="false">
              Advanced
            </button>
          </li>
        </ul>
      </li>
      <!-- add more top-level items here -->
    </ul>
  </nav>

  <main class="sync-main">
    <section id="sync-content-dashboard" class="sync-content" data-sync-content="dashboard">
      <h1 class="sync-heading">Welcome to Sync Dashboard</h1>
      <p>Select a menu item to see details here.</p>
    </section>

    <section id="sync-content-settings" class="sync-content" data-sync-content="settings" hidden>
      <h1 class="sync-heading">Settings Overview</h1>
      <p>Choose a sub-section from the sidebar.</p>
    </section>

    <section id="sync-content-general" class="sync-content" data-sync-content="general" hidden>
      <h1 class="sync-heading">General Settings</h1>
      <p>General settings content…</p>
    </section>

    <section id="sync-content-advanced" class="sync-content" data-sync-content="advanced" hidden>
      <h1 class="sync-heading">Advanced Settings</h1>
      <p>Advanced settings content…</p>
    </section>
  </main>
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
