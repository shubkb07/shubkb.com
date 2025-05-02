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
	 * Menus array.
	 *
	 * @var array
	 */
	private $menus = array();

	/**
	 * Sync Menus Array.
	 *
	 * @var array
	 */
	private $sync_menus = array();

	/**
	 * Sync Ajax Instance.
	 *
	 * @var array
	 */
	private $sync_ajax_instance = null;

	/**
	 * Forms array.
	 *
	 * @var array
	 */
	private $forms = array();

	/**
	 * Settings array.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Sync Settings constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		global $sync_ajax;

		if ( $sync_ajax->is_ajax ) {
			add_action( 'wp_loaded', array( $this, 'init_settings_page' ) );
		}

		add_action( 'admin_menu', array( $this, 'init_settings_page' ) );
		add_action( 'network_admin_menu', array( $this, 'init_settings_page' ) );
		

		// Ensure sync_menus is initialized properly in the constructor.
		$this->sync_menus = array();
	}

	/**
	 * Add WP menu.
	 *
	 * @param string     $menu_slug Menu slug.
	 * @param string     $menu_name Menu name.
	 * @param int        $position Menu position.
	 * @param bool|array $create_sync_menu Create sync menu.
	 * @param string     $settings_level Settings level (site, network, both).
	 *
	 * @since 1.0.0
	 */
	public function add_wp_menu( $menu_slug, $menu_name, $position = 100, $create_sync_menu = false, $settings_level = 'site' ) {

		if ( empty( $menu_slug ) || ! is_string( $menu_slug ) || strpos( $menu_slug, 'sync' ) !== false || ! preg_match( '/^[a-z][a-z0-9_-]*$/', $menu_slug ) ) {
			return;
		}
	
		if ( empty( $menu_name ) || ! is_string( $menu_name ) ) {
			return;
		}
	
		if ( ! is_numeric( $position ) || $position < 0 ) {
			return;
		}
	
		if ( ! in_array( $settings_level, array( 'site', 'network', 'both' ), true ) ) {
			return;
		}

		$this->menus[ $menu_slug ] = array(
			'menu_name'        => $menu_name,
			'position'         => $position,
			'create_sync_menu' => false === $create_sync_menu ? false : true,
			'settings_level'   => $settings_level,
		);

		// Check if, $create_sync_menu is array and have keys menu_name (required) which is not empty and string, and icon_url (optional, default null, else string and null can be set in array), position will always be -1 and sub_menu will always be false.
		if ( is_array( $create_sync_menu ) && isset( $create_sync_menu['menu_name'] ) && ! empty( $create_sync_menu['menu_name'] ) && is_string( $create_sync_menu['menu_name'] ) && isset( $create_sync_menu['callback'] ) && is_callable( $create_sync_menu['callback'] ) ) {
			$this->sync_menus[ $menu_slug ][ $menu_slug ] = array(
				'menu_name' => $create_sync_menu['menu_name'],
				'icon_url'  => isset( $create_sync_menu['icon_url'] ) ? $create_sync_menu['icon_url'] : null,
				'position'  => -1,
				'sub_menu'  => false,
			);

			add_filter( 'sync_register_menu_' . $menu_slug, $create_sync_menu['callback'], 10, 2 );
		}
	}

	/**
	 * Add Sync Menus.
	 *
	 * @param string      $wp_menu_slug      WP Menu slug.
	 * @param string      $menu_name         Menu name.
	 * @param callback    $settings_callback  Settings callback.
	 * @param string|bool $menu_slug         Menu slug (optional).
	 * @param string|null $icon_url          Icon URL (optional).
	 * @param int|null    $position          Menu position (optional).
	 * @param bool|array  $sub_menu_support  Whether sub-menu support is enabled (optional).
	 */
	public function add_sync_menus( $wp_menu_slug, $menu_name, $settings_callback, $menu_slug = false, $icon_url = null, $position = null, $sub_menu_support = false ) {
		// Validate inputs.
		if ( empty( $wp_menu_slug ) || ( ! is_string( $wp_menu_slug ) && isset( $this->menus[ $wp_menu_slug ] ) && $this->menus[ $wp_menu_slug ]['create_sync_menu'] ) ) {
			return;
		}

		if ( empty( $menu_name ) || ! is_string( $menu_name ) ) {
			return;
		}

		if ( false !== $menu_slug && is_string( $menu_slug ) && strpos( $menu_slug, 'sync' ) === true && ! preg_match( '/^[a-z][a-z0-9_-]*$/', $menu_slug ) ) {
			return;
		}

		if ( null !== $position && ( ! is_numeric( $position ) || 0 > $position ) ) {
			return;
		}

		// $sub_menu_support must be false or array, if array, then must have key menu_name and menu_slug, both must be string and not empty, both are required, else return early.
		if ( ! ( false === $sub_menu_support || ( is_array( $sub_menu_support ) && isset( $sub_menu_support['menu_name'] ) && ! empty( $sub_menu_support['menu_name'] ) && is_string( $sub_menu_support['menu_name'] ) && isset( $sub_menu_support['menu_slug'] ) && ! empty( $sub_menu_support['menu_slug'] ) && is_string( $sub_menu_support['menu_slug'] ) ) ) ) {
			return;
		}

		if ( ! isset( $this->sync_menus[ $wp_menu_slug ] ) ) {
			$this->sync_menus[ $wp_menu_slug ] = array();
		}

		$this->sync_menus[ $wp_menu_slug ][ $menu_slug ] = array(
			'menu_name' => $menu_name,
			'icon_url'  => $icon_url,
			'position'  => $position,
			'sub_menu'  => $sub_menu_support ? array(
				$sub_menu_support['menu_slug'] => array(
					'menu_name' => $sub_menu_support['menu_name'],
					'position'  => -1,
				),
			) : false,
		);

		if ( $sub_menu_support ) {
			add_filter( 'sync_register_menu_' . $menu_slug . '_sub_' . $sub_menu_support['menu_slug'], $settings_callback, 10, 2 );
		} else {
			add_filter( 'sync_register_menu_' . $menu_slug, $settings_callback, 10, 2 );
		}
	}

	/**
	 * Add Sync Sub Menus.
	 *
	 * @param string   $wp_menu_slug WP Menu slug.
	 * @param string   $parent_menu_slug Parent menu slug.
	 * @param callback $settings_callback Settings callback.
	 * @param string   $menu_name Menu name.
	 * @param string   $menu_slug Menu slug.
	 * @param int|null $position Menu position.
	 */
	public function add_sync_sub_menus( $wp_menu_slug, $parent_menu_slug, $settings_callback, $menu_name, $menu_slug, $position = null ) {
		// Validate inputs.
		if ( empty( $wp_menu_slug ) || ( ! is_string( $wp_menu_slug ) && isset( $this->menus[ $wp_menu_slug ] ) && ! isset( $this->sync_menus[ $wp_menu_slug ] ) ) ) {
			return;
		}

		if ( empty( $parent_menu_slug ) || ( ! is_string( $parent_menu_slug ) && ! isset( $this->sync_menus[ $wp_menu_slug ][ $parent_menu_slug ] ) ) ) {
			return;
		}

		if ( ! isset( $this->sync_menus[ $wp_menu_slug ][ $parent_menu_slug ]['sub_menu'] ) || false === $this->sync_menus[ $wp_menu_slug ][ $parent_menu_slug ]['sub_menu'] ) {
			return; // Parent menu slug does not support sub-menus.
		}

		if ( empty( $menu_name ) || ! is_string( $menu_name ) ) {
			return;
		}

		if ( false !== $menu_slug && is_string( $menu_slug ) && strpos( $menu_slug, 'sync' ) === true && ! preg_match( '/^[a-z][a-z0-9_-]*$/', $menu_slug ) ) {
			return;
		}

		if ( null !== $position && ( ! is_numeric( $position ) || 0 > $position ) ) {
			return;
		}

		$this->sync_menus[ $wp_menu_slug ][ $parent_menu_slug ]['sub_menu'][ $menu_slug ] = array(
			'menu_name' => $menu_name,
			'position'  => $position,
		);

		add_filter( 'sync_register_menu_' . $parent_menu_slug . '_sub_' . $menu_slug, $settings_callback, 10, 2 );
	}

	/**
	 * Initialize settings.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_page() {

		global $sync_ajax;

		add_menu_page( 'Sync', 'Sync', 'manage_options', 'sync', false, 'dashicons-sort', is_network_admin() ? 23 : 63 );
		$this->menus['sync'] = array(
			'menu_name'        => 'Sync',
			'position'         => -1,
			'create_sync_menu' => false,
			'settings_level'   => 'both',
		);

		/**
		 * Sync Settings Page Hook
		 */
		do_action( 'sync_add_settings_page' );

		/**
		 * Sync Settings Page Filter
		 */
		apply_filters( 'sync_settings_page', $this->menus );

		if ( $sync_ajax->is_ajax ) {
			$this->init_ajax_response();
			return;
		}

		$this->init_settings_pages();
	}

	/**
	 * Initialize Ajax response.
	 *
	 * @since 1.0.0
	 */
	private function init_ajax_response() {

		global $sync_ajax;

		// Get Action Name.
		$action_name = $sync_ajax->ajax_action_name;

		// Check if it start with 'sync_save_' and end with '_settings'.
		if ( strpos( $action_name, 'sync_save_' ) !== 0 || strpos( $action_name, '_settings' ) !== ( strlen( $action_name ) - strlen( '_settings' ) ) ) {
			return;
		}

		// Trim the action name from the start 'sync_save_' and end '_settings'.
		$action_name = substr( $action_name, strlen( 'sync_save_' ), -strlen( '_settings' ) );

		// Divide action name in slug and parent slug.
		$action_name_parts = explode( '_sub_', $action_name );

		if ( count( $action_name_parts ) > 2 ) {
			return; // Invalid action name.
		}

		if ( count( $action_name_parts ) === 2 ) {
			$parent_slug = $action_name_parts[0];
			$slug        = $action_name_parts[1];
		} else {
			$parent_slug = '';
			$slug        = $action_name_parts[0];
		}

		$page_data = array(
			'name'    => $action_name,
			'slug'    => $slug,
			'puspose' => 'menu_submit',
		);

		// Append the parent slug if it exists.
		if ( ! empty( $parent_slug ) ) {
			$page_data['parent_slug'] = $parent_slug;
		}

		$full_slug = $parent_slug ? $parent_slug . '_' . $slug : $slug;

		// Create nonce key.
		$nonce_action = 'sync_setting_' . $full_slug;

		$this->sync_ajax_instance = apply_filters( 'sync_register_menu_' . $action_name, array(), $page_data );

		sync_register_ajax_action(
			$sync_ajax->ajax_action_name,
			array( $this, 'settings_submit_handler' ),
			$nonce_action,
			'_sync_nonce',
			'in',
			true
		);
	}

	/**
	 * Settings Submit Handler.
	 *
	 * @param array $sync_req Sync request data.
	 */
	public function settings_submit_handler( $sync_req ) {
		// error_log( 'Settings Submit Handler' );
		// error_log( 'Req: ' . print_r( $sync_req, true ) );
		// error_log( 'Instance: ' . print_r( $this->sync_ajax_instance, true ) );
		wp_send_json_success(
			array(
				'message'  => 'Settings submitted successfully.',
				'instance' => $this->sync_ajax_instance,
			) 
		);
	}

	/**
	 * Initialize settings pages.
	 *
	 * @since 1.0.0
	 */
	private function init_settings_pages() {

		foreach ( $this->menus as $menu_slug => $current_menu ) {

			if ( ! is_network_admin() && ! in_array( $current_menu['settings_level'], array( 'site', 'both' ), true ) ) {
				return; // Only show on site admin.
			} elseif ( is_network_admin() && ! in_array( $current_menu['settings_level'], array( 'network', 'both' ), true ) ) {
				return; // Only show on network admin.
			}

			if ( $current_menu['create_sync_menu'] ) {
				if ( isset( $this->sync_menus[ $menu_slug ] ) && is_array( $this->sync_menus[ $menu_slug ] ) ) {
					$has_valid_sub_menu = false;
					foreach ( $this->sync_menus[ $menu_slug ] as $sub_menu ) {
						if ( false !== $menu_slug || ( isset( $sub_menu['sub_menu'] ) && ! empty( $sub_menu['sub_menu'] ) ) ) {
							$has_valid_sub_menu = true;
							break;
						}
					}

					if ( ! $has_valid_sub_menu ) {
						continue; // Skip adding this menu if no valid sub-menu exists.
					}
				}
			}

			add_submenu_page(
				'sync',
				$current_menu['menu_name'],
				$current_menu['menu_name'],
				'manage_options',
				'sync' === $menu_slug ? 'sync' : 'sync-' . $menu_slug,
				array( $this, 'settings_page' ),
				$current_menu['position']
			);
		}
	}

	/**
	 * Process icon.
	 *
	 * @param string $icon_url    Icon URL.
	 * @param bool   $return_html Whether to return the HTML instead of echoing it.
	 *
	 * @since 1.0.0
	 */
	public function process_icon( $icon_url, $return_html = false ) {
		if ( $return_html ) {
			ob_start();
		}

		if ( $return_html ) {
			return ob_get_clean();
		}
	}

	/**
	 * Settings page.
	 *
	 * @since 1.0.0
	 */
	public function settings_page() {
		global $plugin_page;

		// Remove sync- from the plugin_page to get the actual menu slug.
		$current_settings_page = str_replace( 'sync-', '', $plugin_page );

		// Get the first menu item dynamically.
		$default_menu_slug = '';
		if ( isset( $this->sync_menus[ $current_settings_page ] ) && is_array( $this->sync_menus[ $current_settings_page ] ) ) {
			// Get the first key from the menu array.
			$keys              = array_keys( $this->sync_menus[ $current_settings_page ] );
			$default_menu_slug = reset( $keys );
		}
		?>
<div class="sync-container">
	<!-- Main navigation with logo and mobile menu toggle -->
	<header class="sync-header">
		<div class="sync-logo">
			<span class="sync-logo-icon">S</span>
			<span class="sync-logo-text">SYNC</span>
			<span class="sync-tagline">SYNC the Web</span>
		</div>
		<button class="sync-mobile-toggle" id="sync-mobile-toggle">
			<span class="dashicons dashicons-menu-alt"></span>
		</button>
	</header>

		<?php
		if ( $this->menus[ $current_settings_page ]['create_sync_menu'] && isset( $this->sync_menus[ $current_settings_page ] ) && is_array( $this->sync_menus[ $current_settings_page ] ) ) {
			?>
	<!-- Side navigation -->
	<nav class="sync-sidebar" id="sync-sidebar">
	<ul class="sync-menu">
			<?php
			$current_sync_menu = $this->sync_menus[ $current_settings_page ];
			foreach ( $current_sync_menu as $menu_slug => $current_menu ) {
				?>
	<li class="sync-menu-item <?php echo $menu_slug === $default_menu_slug ? 'sync-active' : ''; ?>">
	<a href="#<?php echo esc_attr( 'sync-' . $menu_slug ); ?>" class="sync-menu-link" data-slug="<?php echo esc_attr( $menu_slug ); ?>">
				<?php $this->process_icon( $current_menu['icon_url'] ); ?>
		<span class="sync-menu-text"><?php echo esc_html( $current_menu['menu_name'] ); ?></span>
	</a>
				<?php
				if ( isset( $current_menu['sub_menu'] ) && is_array( $current_menu['sub_menu'] ) && ! empty( $current_menu['sub_menu'] ) ) {
					?>
		<ul class="sync-submenu">
					<?php
					foreach ( $current_menu['sub_menu'] as $sub_menu_slug => $sub_menu ) {
						?>
				<li class="sync-submenu-item">
					<a href="#<?php echo esc_attr( 'sync-' . $menu_slug . '-' . $sub_menu_slug ); ?>" class="sync-submenu-link" data-parent="<?php echo esc_attr( $menu_slug ); ?>" data-slug="<?php echo esc_attr( $sub_menu_slug ); ?>"><?php echo esc_html( $sub_menu['menu_name'] ); ?></a>
				</li>
						<?php
					}
					?>
		</ul>
					<?php
				}
				?>
	</li>
				<?php
			}
			?>
	</ul>
	</nav>

			<?php
		}
		?>

	<!-- Main content area - Dynamic -->
	<main class="sync-content">
		<div class="sync-content-header">
			<h1 class="sync-page-title">
			<?php 
				// Set the default page title based on the first menu.
			if ( ! empty( $default_menu_slug ) && isset( $current_sync_menu[ $default_menu_slug ]['menu_name'] ) ) {
				echo esc_html( $current_sync_menu[ $default_menu_slug ]['menu_name'] );
			} else {
				echo 'Dashboard';
			}
			?>
			</h1>
		</div>

		<!-- Dynamic content container - Now empty to be filled by JS -->
		<div id="sync-dynamic-content" class="sync-dynamic-content"></div>
	</main>
</div>

<!-- Hidden templates for ALL menu and submenu pages -->
<div id="sync-page-templates" style="display: none;">
		<?php
		// Generate hidden templates for ALL menu pages, including the default/first one.
		if ( isset( $this->sync_menus[ $current_settings_page ] ) && is_array( $this->sync_menus[ $current_settings_page ] ) ) {
			$current_sync_menu = $this->sync_menus[ $current_settings_page ];

			foreach ( $current_sync_menu as $menu_slug => $current_menu ) {
				$page_data = array(
					'name'    => $current_menu['menu_name'],
					'slug'    => $menu_slug,
					'puspose' => 'menu_load',
				);
	
				// Apply filter for this menu page.
				$menu_content = apply_filters(
					"sync_register_menu_{$menu_slug}", 
					'', // Empty string as first parameter.
					$page_data // Page data as second parameter.
				);
	
				if ( ! empty( $menu_content ) ) {
					?>
		<template id="sync-page-<?php echo esc_attr( $menu_slug ); ?>">
					<?php $this->sanitize_form_output( $menu_content ); ?>
		</template>
						<?php
				}
	
				// Process submenu pages.
				if ( isset( $current_menu['sub_menu'] ) && is_array( $current_menu['sub_menu'] ) && ! empty( $current_menu['sub_menu'] ) ) {
					foreach ( $current_menu['sub_menu'] as $sub_menu_slug => $sub_menu ) {
						$sub_page_data = array(
							'name'        => $sub_menu['menu_name'],
							'parent_slug' => $menu_slug,
							'slug'        => $sub_menu_slug,
							'puspose'     => 'menu_load',
						);
			
						// Apply filter for this submenu page.
						$submenu_content = apply_filters(
							"sync_register_menu_{$menu_slug}_sub_{$sub_menu_slug}", 
							'', // Empty string as first parameter.
							$sub_page_data // Page data as second parameter.
						);

						if ( ! empty( $submenu_content ) ) {
							?>
				<template id="sync-subpage-<?php echo esc_attr( $menu_slug ); ?>-<?php echo esc_attr( $sub_menu_slug ); ?>">
							<?php $this->sanitize_form_output( $submenu_content ); ?>
				</template>
								<?php
						}
					}
				}
			}
		}
		?>
</div>

		<?php
	}

	/**
	 * Create a settings page with a single form that submits all settings at once via Ajax
	 * 
	 * @param array  $page_details       Page information (slug, name, parent_slug).
	 * @param array  $settings_array     Settings array structure.
	 * @param string $submit_button_text Text for the submit button.
	 * @param bool   $refresh            Whether to refresh the page after successful Ajax.
	 * 
	 * @return string|array HTML for the settings page or an array of settings submit.
	 */
	public function create_single_ajax_settings_page( $page_details, $settings_array, $submit_button_text = 'Save Changes', $refresh = false ) {

		// Validate page details.
		$slug          = isset( $page_details['slug'] ) ? sanitize_title( $page_details['slug'] ) : 'sync';
		$name          = isset( $page_details['name'] ) ? sanitize_text_field( $page_details['name'] ) : 'Sync';
		$parent_slug   = isset( $page_details['parent_slug'] ) ? sanitize_title( $page_details['parent_slug'] ) : '';
		$full_slug     = $parent_slug ? $parent_slug . '_' . $slug : $slug;
		$full_slug_adv = $parent_slug ? $parent_slug . '_sub_' . $slug : $slug;

		if ( isset( $page_details['puspose'] ) && 'menu_load' === $page_details['puspose'] ) {

			// Create nonce key.
			$nonce_action = 'sync_setting_' . $full_slug;
			$nonce        = wp_create_nonce( $nonce_action );

			// Start output buffering to collect HTML.
			ob_start();
			
			// Begin container.
			?>
			<div class="sync-settings-container sync-single-form" data-refresh="<?php echo esc_attr( $refresh ? 'true' : 'false' ); ?>">
			
			<form class="sync-settings-form" id="sync-form-<?php echo esc_attr( $slug ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>">
			
			<input type="hidden" name="_sync_nonce" value="<?php echo esc_attr( $nonce ); ?>">
			<input type="hidden" name="action" value="sync_save_<?php echo esc_attr( $full_slug_adv ); ?>_settings">
			<input type="hidden" name="sync_form_type" value="single">

			<?php $this->generate_settings_html( $settings_array['html'] ); ?>
			
			<div class="sync-form-footer">
			<button type="button" class="sync-button sync-primary-button sync-submit-button">
			<span class="dashicons dashicons-saved"></span><?php echo esc_html( $submit_button_text ); ?>
			</button>
			<div class="sync-form-message"></div>
			</div>
			</form>
			</div>
			<?php

			return ob_get_clean();
		} elseif ( isset( $page_details['puspose'] ) && 'menu_submit' === $page_details['puspose'] ) {

			if ( empty( $settings_array ) || ! is_array( $settings_array ) || ! $this->validate_input_sync_options( $settings_array ) ) {
				sync_send_json(
					array(
						'status'  => 'error',
						'message' => __( 'Invalid settings array.', 'wisesync' ),
					)
				);
			}

			return $this->genrate_settings_submit_array( $page_details, $settings_array );
		}
	}

	/**
	 * Create a settings page where each setting is saved individually via Ajax upon change
	 * 
	 * @param array $page_details   Page information (slug, name, parent_slug).
	 * @param array $settings_array Settings array structure.
	 * 
	 * @return string|array HTML for the settings page or an array of settings submit.
	 */
	public function create_each_ajax_settings_page( $page_details, $settings_array ) {

		// Validate page details.
		$slug          = isset( $page_details['slug'] ) ? sanitize_title( $page_details['slug'] ) : 'sync-settings';
		$name          = isset( $page_details['name'] ) ? sanitize_text_field( $page_details['name'] ) : 'Settings';
		$parent_slug   = isset( $page_details['parent_slug'] ) ? sanitize_title( $page_details['parent_slug'] ) : '';
		$full_slug     = $parent_slug ? $parent_slug . '_' . $slug : $slug;
		$full_slug_adv = $parent_slug ? $parent_slug . '_sub_' . $slug : $slug;

		if ( isset( $page_details['puspose'] ) && 'menu_load' === $page_details['puspose'] ) {
			// Create nonce key.
			$nonce_action = 'sync_setting_' . $full_slug;
			$nonce        = wp_create_nonce( $nonce_action );

			// Start output buffering to collect HTML.
			ob_start();

			// Begin container.
			?>
			<div class="sync-settings-container sync-each-setting" data-slug="<?php echo esc_attr( $slug ); ?>">

			<input type="hidden" id="sync-nonce-<?php echo esc_attr( $slug ); ?>" name="_sync_nonce" value="<?php echo esc_attr( $nonce ); ?>">
			<input type="hidden" id="sync-action" name="action" value="sync_save_<?php echo esc_attr( $full_slug_adv ); ?>_settings">
			<input type="hidden" name="sync_form_type" value="ajax">

			<?php $this->generate_settings_html( $settings_array, true ); ?>
			<div class="sync-settings-message-area"></div>
			</div>

			<?php
			return ob_get_clean();
		} elseif ( isset( $page_details['puspose'] ) && 'menu_submit' === $page_details['puspose'] ) {

			if ( empty( $settings_array ) || ! is_array( $settings_array ) || ! $this->validate_input_sync_options( $settings_array ) ) {
				sync_send_json(
					array(
						'status'  => 'error',
						'message' => __( 'Invalid settings array.', 'wisesync' ),
					)
				);
			}

			return $this->genrate_settings_submit_array( $page_details, $settings_array );
		}
	}

	/**
	 * Generate settings submit array.
	 * 
	 * @param array $page_details   Page information (slug, name, parent_slug).
	 * @param array $settings_array Settings array structure.
	 * 
	 * @return array Settings submit array.
	 */
	private function genrate_settings_submit_array( $page_details, $settings_array ) {}

	/**
	 * Generate HTML from settings array
	 * 
	 * @param array $settings_array Settings structure.
	 * @param bool  $auto_save      Whether to enable auto-save for inputs.
	 * @param bool  $return_html    Whether to return the HTML instead of echoing it.
	 * 
	 * @return string Generated HTML
	 */
	private function generate_settings_html( $settings_array, $auto_save = false, $return_html = false ) {

		if ( $return_html ) {
			ob_start();
		}
		
		foreach ( $settings_array as $type => $settings ) {
			switch ( $type ) {
				case 'flex':
					$this->generate_flex_container( $settings, $auto_save );
					break;
					
				case 'p':
					?>
					<p class="sync-text"><?php echo esc_html( $settings ); ?>'</p>
					<?php
					break;
					
				case 'h1':
				case 'h2':
				case 'h3':
				case 'h4':
				case 'h5':
				case 'h6':
					?>
					<<?php echo esc_attr( $type ); ?> class="sync-heading sync-<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $settings ); ?></<?php echo esc_html( $type ); ?>>
					<?php
					break;
					
				case 'span':
					?>
					<span class="sync-span"><?php echo esc_html( $settings ); ?></span>
					<?php
					break;
					
				case 'icon':
					?>
					<span class="dashicons dashicons-<?php echo esc_attr( $settings ); ?>"></span>
					<?php
					break;
					
				case 'break':
					$count = isset( $settings['count'] ) ? intval( $settings['count'] ) : 1;
					for ( $i = 0; $i < $count; $i++ ) {
						?>
						<br>
						<?php
					}
					break;
					
				// Input elements handled in other methods.
				default:
					if ( strpos( $type, 'input_' ) === 0 ) {
						$this->generate_input( substr( $type, 6 ), $settings, $auto_save );
					}
					break;
			}
		}
		
		if ( $return_html ) {
			return ob_get_clean();
		}
	}

	/**
	 * Generate a flex container
	 * 
	 * @param array $settings  Flex container settings.
	 * @param bool  $auto_save Whether to enable auto-save for inputs.
	 * @param bool  $return_html Whether to return the HTML instead of echoing it.
	 * 
	 * @return string Generated HTML
	 */
	private function generate_flex_container( $settings, $auto_save = false, $return_html = false ) {
		// Default flex settings.
		$direction     = isset( $settings['direction'] ) ? $settings['direction'] : 'column';
		$align_items   = isset( $settings['align']['item'] ) ? $settings['align']['item'] : 'stretch';
		$align_content = isset( $settings['align']['content'] ) ? $settings['align']['content'] : 'flex-start';
		
		if ( $return_html ) {
			ob_start();
		}
		
		?>
		<div class="sync-flex" style="flex-direction: <?php echo esc_attr( $direction ); ?>; align-items: <?php echo esc_attr( $align_items ); ?>; justify-content: <?php echo esc_attr( $align_content ); ?>;">
		<?php
		// Process content inside the flex container.
		if ( isset( $settings['content'] ) && is_array( $settings['content'] ) ) {
			$this->generate_settings_html( $settings['content'], $auto_save );
		}
		
		?>
		</div>
		<?php
		
		if ( $return_html ) {
			return ob_get_clean();
		}
	}

	/**
	 * Generate different input types
	 * 
	 * @param string $type      Input type (text, textarea, radio, etc.).
	 * @param array  $settings  Input settings.
	 * @param bool   $auto_save Whether to enable auto-save.
	 * @param bool   $return_html Whether to return the HTML instead of echoing it.
	 * 
	 * @return string Generated HTML
	 */
	private function generate_input( $type, $settings, $auto_save = false, $return_html = false ) {
		// Common attributes.
		$name        = isset( $settings['name'] ) ? $settings['name'] : '';
		$value       = isset( $settings['value'] ) ? $settings['value'] : '';
		$placeholder = isset( $settings['place_holder'] ) ? $settings['place_holder'] : '';
		$label       = isset( $settings['label'] ) ? $settings['label'] : '';
		$required    = isset( $settings['required'] ) && $settings['required'] ? 'required' : '';
		$description = isset( $settings['description'] ) ? $settings['description'] : '';
		
		// Auto-save attribute.
		$auto_save_attr = $auto_save ? 'data-autosave="true"' : '';
		
		if ( $return_html ) {
			ob_start();
		}
		
		// Wrapper with common classes.
		?>
		<div class="sync-input-wrapper sync-<?php echo esc_attr( $type ); ?>-wrapper">
		<?php
		
		// Label if provided.
		if ( ! empty( $label ) ) {
			?>
			<label for="sync-<?php echo esc_attr( $name ); ?>" class="sync-input-label"><?php echo esc_html( $label ); ?></label>
			<?php
		}
		
		switch ( $type ) {
			case 'text':
				?>
				<input type="text" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="sync-input sync-text-input" <?php echo esc_attr( $required . ' ' . $auto_save_attr ); ?>>
				<?php
				break;
				
			case 'textarea':
				?>
				<textarea id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="sync-input sync-textarea" <?php echo esc_attr( $required . ' ' . $auto_save_attr ); ?>><?php echo esc_textarea( $value ); ?></textarea>
				<?php
				break;
				
			case 'radio':
				if ( isset( $settings['options'] ) && is_array( $settings['options'] ) ) {
					?>
					<div class="sync-radio-group">
					<?php
					foreach ( $settings['options'] as $option ) {
						$option_value = is_array( $option ) ? $option['value'] : $option;
						$option_label = is_array( $option ) ? $option['label'] : $option;
						$checked      = $value === $option_value ? 'checked' : '';
						
						?>
						<label class="sync-radio-label">
						<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $option_value ); ?>" <?php echo esc_attr( $checked . ' ' . $auto_save_attr ); ?>>
						<span class="sync-radio-text"><?php echo esc_html( $option_label ); ?></span>
						</label>
						<?php
					}
					?>
					</div>
					<?php
				}
				break;
				
			case 'toggle':
				$checked = $value ? 'checked' : '';
				?>
				<label class="sync-toggle">
				<input type="checkbox" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php echo esc_attr( $checked . ' ' . $auto_save_attr ); ?>>
				<span class="sync-toggle-slider"></span>
				</label>
				<?php
				break;
				
			case 'checkbox':
				$checked = $value ? 'checked' : '';
				?>
				<label class="sync-checkbox-label">
				<input type="checkbox" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php echo esc_attr( $checked . ' ' . $auto_save_attr ); ?>>
				<span class="sync-checkbox-text"><?php echo esc_html( $label ); ?></span>
				</label>
				<?php
				break;
				
			case 'dropdown':
				?>
				<select id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="sync-dropdown" <?php echo esc_attr( $required . ' ' . $auto_save_attr ); ?>>
				<?php
				
				if ( ! empty( $placeholder ) ) {
					?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php echo ( empty( $value ) ? 'selected' : '' ); ?> disabled><?php echo esc_html( $placeholder ); ?></option>
					<?php
				}
				
				if ( isset( $settings['options'] ) && is_array( $settings['options'] ) ) {
					foreach ( $settings['options'] as $option ) {
						$option_value = is_array( $option ) ? $option['value'] : $option;
						$option_label = is_array( $option ) ? $option['label'] : $option;
						$selected     = $value === $option_value ? 'selected' : '';
						?>
						<option value="<?php echo esc_attr( $option_value ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $option_label ); ?></option>
						<?php
					}
				}
				?>
				</select>
				<?php
				break;
				
			case 'date':
				?>
				<input type="date" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="sync-input sync-date-input" <?php echo esc_attr( $required . ' ' . $auto_save_attr ); ?>>
				<?php
				break;
				
			case 'time':
				?>
				<input type="time" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="sync-input sync-time-input" <?php echo esc_attr( $required . ' ' . $auto_save_attr ); ?>>
				<?php
				break;
				
			case 'datetime':
				?>
				<input type="datetime-local" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="sync-input sync-datetime-input" <?php echo esc_attr( $required . ' ' . $auto_save_attr ); ?>>
				<?php
				break;
				
			case 'number':
				$min  = isset( $settings['min'] ) ? 'min="' . esc_attr( $settings['min'] ) . '"' : '';
				$max  = isset( $settings['max'] ) ? 'max="' . esc_attr( $settings['max'] ) . '"' : '';
				$step = isset( $settings['step'] ) ? 'step="' . esc_attr( $settings['step'] ) . '"' : '';
				?>
				<input type="number" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo wp_kses_post( $min . ' ' . $max . ' ' . $step ); ?> placeholder="<?php echo esc_attr( $placeholder ); ?>" class="sync-input sync-number-input" <?php echo esc_attr( $required . ' ' . $auto_save_attr ); ?>>
				<?php
				break;

			case 'password':
				?>
				<input type="password" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="sync-input sync-password-input" <?php echo esc_attr( $required . ' ' . $auto_save_attr ); ?>>
				<?php
				break;

			case 'email':
				?>
				<input type="email" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="sync-input sync-email-input" <?php echo esc_attr( $required . ' ' . $auto_save_attr ); ?>>
				<?php
				break;

			case 'url':
				?>
				<input type="url" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="sync-input sync-url-input" <?php echo esc_attr( $required . ' ' . $auto_save_attr ); ?>>
				<?php
				break;
				
			case 'color':
				?>
				<input type="color" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="sync-input sync-color-input" <?php echo esc_attr( $required . ' ' . $auto_save_attr ); ?>>
				<?php
				break;
				
			case 'range':
				$min  = isset( $settings['min'] ) ? 'min="' . esc_attr( $settings['min'] ) . '"' : '';
				$max  = isset( $settings['max'] ) ? 'max="' . esc_attr( $settings['max'] ) . '"' : '';
				$step = isset( $settings['step'] ) ? 'step="' . esc_attr( $settings['step'] ) . '"' : '';
				?>
				<input type="range" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo wp_kses_post( $min . ' ' . $max . ' ' . $step ); ?> class="sync-input sync-range-input" ' <?php echo esc_attr( $required . ' ' . $auto_save_attr ); ?>>
				<span class="sync-range-value"><?php echo esc_html( $value ); ?></span>
				<?php
				break;
				
			case 'button':
				$button_text  = isset( $settings['text'] ) ? $settings['text'] : 'Button';
				$button_type  = isset( $settings['button_type'] ) ? $settings['button_type'] : 'button';
				$button_class = isset( $settings['class'] ) ? $settings['class'] : 'sync-button';
				$icon         = isset( $settings['icon'] ) ? '<span class="dashicons dashicons-' . esc_attr( $settings['icon'] ) . '"></span>' : '';
				?>
				<button type="<?php echo esc_attr( $button_type ); ?>" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="<?php echo esc_attr( $button_class ); ?>">
				<?php echo wp_kses_post( $icon . esc_html( $button_text ) ); ?>
				</button>
				<?php
				break;
				
			case 'data':
				// Custom data input (key-value pairs).
				?>
				<div class="sync-data-input-container" data-name="<?php echo esc_attr( $name ); ?>">
				<table class="sync-data-table">
				<thead><tr><th>Key</th><th>Value</th><th></th></tr></thead>
				<tbody>
				<?php
				
				// Add existing data pairs.
				if ( ! empty( $value ) && is_array( $value ) ) {
					foreach ( $value as $key => $val ) {
						?>
<tr class="sync-data-row">
	<td><input type="text" class="sync-data-key" value="<?php echo esc_attr( $key ); ?>"></td>
	<td><input type="text" class="sync-data-value" value="<?php echo esc_attr( $val ); ?>"></td>
	<td><button type="button" class="sync-data-remove"><span class="dashicons dashicons-trash"></span></button></td>
</tr>
						<?php
					}
				}
				?>
<tr class="sync-data-row">
	<td><input type="text" class="sync-data-key" placeholder="<?php echo esc_attr( 'Key' ); ?>"></td>
	<td><input type="text" class="sync-data-value" placeholder="<?php echo esc_attr( 'Value' ); ?>"></td>
	<td><button type="button" class="sync-data-remove"><span class="dashicons dashicons-trash"></span></button></td>
</tr>
</tbody>
</table>
				<?php
				// Add hidden input to store JSON data.
				?>
<input type="hidden" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( wp_json_encode( $value ) ); ?>" <?php echo esc_attr( $auto_save_attr ); ?>>
				<?php
				// Add button.
				?>
<button type="button" class="sync-button sync-data-add">
	<span class="dashicons dashicons-plus"></span> <?php echo esc_html( 'Add New Entry' ); ?>
</button>
</div>
				<?php
				break;
				
			case 'file':
				// WordPress media uploader field.
				$file_url = is_array( $value ) && isset( $value['url'] ) ? $value['url'] : $value;
				$file_id  = is_array( $value ) && isset( $value['id'] ) ? $value['id'] : '';
				?>
				<div class="sync-file-upload-container">
				<input type="text" id="sync-<?php echo esc_attr( $name ); ?>-url" class="sync-file-url" value="<?php echo esc_attr( $file_url ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" readonly>
				<input type="hidden" id="sync-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $file_id ); ?>" <?php echo esc_attr( $auto_save_attr ); ?>>
				<button type="button" class="sync-button sync-file-upload-button">Select File</button>
				<button type="button" class="sync-button sync-file-remove-button" <?php echo ( empty( $file_url ) ? 'style="display:none;"' : '' ); ?>>Remove</button>
				</div>
				<?php
				break;
		}
		
		// Description if provided.
		if ( ! empty( $description ) ) {
			?>
			<p class="sync-input-description"><?php echo esc_html( $description ); ?></p>
			<?php
		}
		
		// Individual message area for auto-save inputs.
		if ( $auto_save ) {
			?>
			<div class="sync-input-message" data-for="<?php echo esc_attr( $name ); ?>"></div>
			<?php
		}
		
		?>
		</div>
		<?php

		if ( $return_html ) {
			return ob_get_clean();
		}
	}

	/**
	 * Sanitize form output
	 *
	 * @param String $html HTML to sanitize.
	 * @param bool   $return_html Whether to return the HTML instead of echoing it.
	 */
	private function sanitize_form_output( $html, $return_html = false ) {
		if ( $return_html ) {
			ob_start();
		}
		$allowed_tags = array(
			'form'     => array(
				'class'  => true,
				'id'     => true,
				'method' => true,
				'action' => true,
				'data-*' => true,
			),
			'input'    => array(
				'type'        => true,
				'name'        => true,
				'value'       => true,
				'class'       => true,
				'id'          => true,
				'placeholder' => true,
				'required'    => true,
				'data-*'      => true,
			),
			'button'   => array(
				'type'  => true,
				'class' => true,
				'id'    => true,
				'name'  => true,
				'value' => true,
			),
			'div'      => array(
				'class'  => true,
				'id'     => true,
				'data-*' => true,
			),
			'span'     => array(
				'class' => true,
				'id'    => true,
			),
			'label'    => array(
				'for'   => true,
				'class' => true,
			),
			'select'   => array(
				'name'     => true,
				'class'    => true,
				'id'       => true,
				'required' => true,
			),
			'option'   => array(
				'value'    => true,
				'selected' => true,
			),
			'textarea' => array(
				'name'        => true,
				'class'       => true,
				'id'          => true,
				'placeholder' => true,
				'required'    => true,
			),
		);

		// Add more allowed tags as needed.
		$allowed_tags      = array_merge( $allowed_tags, wp_kses_allowed_html( 'post' ) );
		$allowed_tags['*'] = array(
			'class'  => true,
			'id'     => true,
			'style'  => true,
			'data-*' => true,
		);

		// Filter to allow custom tags.
		$allowed_tags = apply_filters( 'sync_allowed_html', $allowed_tags );

		// Sanitize the HTML.
		echo wp_kses( $html, $allowed_tags );
		if ( $return_html ) {
			return ob_get_clean();
		}
	}

	/**
	 * Validate input sync options
	 *
	 * @param array $data Data to validate.
	 * 
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_input_sync_options( $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}
	
		$sync_options = array();
	
		/**
		 * Recursively walk through the array to find 'sync_option' keys.
		 *
		 * @param array $array_data The array to walk through.
		 *
		 * @uses $walk to recurse.
		 * @uses $sync_options to store found options.
		 *
		 * @return bool True if valid, false otherwise.
		 */
		$walk = function ( $array_data ) use ( &$walk, &$sync_options ) {
			foreach ( $array_data as $key => $value ) {
				if ( is_array( $value ) ) {
					// If key starts with 'input_'.
					if ( is_string( $key ) && strpos( $key, 'input_' ) === 0 ) {
						// It must contain 'sync_option'.
						if ( ! isset( $value['sync_option'] ) ) {
							return false;
						}
						$option_value = $value['sync_option'];
	
						// sync_option must be string.
						if ( ! is_string( $option_value ) ) {
							return false;
						}
	
						// Must be unique.
						if ( in_array( $option_value, $sync_options, true ) ) {
							return false;
						}
	
						$sync_options[] = $option_value;
					}
	
					// Recurse into nested array.
					if ( $walk( $value ) === false ) {
						return false;
					}
				}
			}
			return true;
		};
	
		return $walk( $data );
	}
}
