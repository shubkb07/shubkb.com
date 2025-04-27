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
		if ( is_array( $create_sync_menu ) && isset( $create_sync_menu['menu_name'] ) && ! empty( $create_sync_menu['menu_name'] ) && is_string( $create_sync_menu['menu_name'] ) ) {
			$this->sync_menus[ $menu_slug ][ $menu_slug ] = array(
				'menu_name' => $create_sync_menu['menu_name'],
				'icon_url'  => isset( $create_sync_menu['icon_url'] ) ? $create_sync_menu['icon_url'] : null,
				'position'  => -1,
				'sub_menu'  => false,
			);
		}
	}

	/**
	 * Add Sync Menus.
	 *
	 * @param string      $wp_menu_slug      WP Menu slug.
	 * @param string      $menu_name         Menu name.
	 * @param string|bool $menu_slug         Menu slug (optional).
	 * @param string|null $icon_url          Icon URL (optional).
	 * @param int|null    $position          Menu position (optional).
	 * @param bool|array  $sub_menu_support  Whether sub-menu support is enabled (optional).
	 */
	public function add_sync_menus( $wp_menu_slug, $menu_name, $menu_slug = false, $icon_url = null, $position = null, $sub_menu_support = false ) {
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
	}

	/**
	 * Add Sync Sub Menus.
	 *
	 * @param string   $wp_menu_slug WP Menu slug.
	 * @param string   $parent_menu_slug Parent menu slug.
	 * @param string   $menu_name Menu name.
	 * @param string   $menu_slug Menu slug.
	 * @param int|null $position Menu position.
	 */
	public function add_sync_sub_menus( $wp_menu_slug, $parent_menu_slug, $menu_name, $menu_slug, $position = null ) {
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
	}

	/**
	 * Initialize settings.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_page() {

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

		$this->init_settings_pages();
	}

	/**
	 * Initialize settings pages.
	 *
	 * @since 1.0.0
	 */
	private function init_settings_pages() {

		foreach ( $this->menus as $menu_slug => $menu ) {

			if ( ! is_network_admin() && ! in_array( $menu['settings_level'], array( 'site', 'both' ), true ) ) {
				return; // Only show on site admin.
			} elseif ( is_network_admin() && ! in_array( $menu['settings_level'], array( 'network', 'both' ), true ) ) {
				return; // Only show on network admin.
			}

			if ( $menu['create_sync_menu'] ) {
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
				$menu['menu_name'],
				$menu['menu_name'],
				'manage_options',
				'sync' === $menu_slug ? 'sync' : 'sync-' . $menu_slug,
				array( $this, 'settings_page' ),
				$menu['position']
			);
		}
	}

	/**
	 * Process icon.
	 *
	 * @param string $icon_url Icon URL.
	 *
	 * @since 1.0.0
	 */
	public function process_icon( $icon_url ) {}

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
			foreach ( $current_sync_menu as $menu_slug => $menu ) {
				?>
	<li class="sync-menu-item <?php echo $menu_slug === $default_menu_slug ? 'sync-active' : ''; ?>">
	<a href="#<?php echo esc_attr( 'sync-' . $menu_slug ); ?>" class="sync-menu-link" data-slug="<?php echo esc_attr( $menu_slug ); ?>">
				<?php $this->process_icon( $menu['icon_url'] ); ?>
		<span class="sync-menu-text"><?php echo esc_html( $menu['menu_name'] ); ?></span>
	</a>
				<?php
				if ( isset( $menu['sub_menu'] ) && is_array( $menu['sub_menu'] ) && ! empty( $menu['sub_menu'] ) ) {
					?>
		<ul class="sync-submenu">
					<?php
					foreach ( $menu['sub_menu'] as $sub_menu_slug => $sub_menu ) {
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
		<div id="sync-dynamic-content"></div>
	</main>
</div>

<!-- Hidden templates for ALL menu and submenu pages -->
<div id="sync-page-templates" style="display: none;">
		<?php
		// Generate hidden templates for ALL menu pages, including the default/first one.
		if ( isset( $this->sync_menus[ $current_settings_page ] ) && is_array( $this->sync_menus[ $current_settings_page ] ) ) {
			$current_sync_menu = $this->sync_menus[ $current_settings_page ];

			foreach ( $current_sync_menu as $menu_slug => $menu ) {
				$page_data = array(
					'name' => $menu['menu_name'],
					'slug' => $menu_slug,
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
						<?php echo wp_kses_post( $menu_content ); ?>
		</template>
						<?php
				}
	
				// Process submenu pages.
				if ( isset( $menu['sub_menu'] ) && is_array( $menu['sub_menu'] ) && ! empty( $menu['sub_menu'] ) ) {
					foreach ( $menu['sub_menu'] as $sub_menu_slug => $sub_menu ) {
						$sub_page_data = array(
							'name'        => $sub_menu['menu_name'],
							'parent_slug' => $menu_slug,
							'slug'        => $sub_menu_slug,
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
								<?php echo wp_kses_post( $submenu_content ); ?>
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
	 * @return string HTML for the settings page
	 */
	public function create_single_ajax_settings_page( $page_details, $settings_array, $submit_button_text = 'Save Changes', $refresh = false ) {
		// Validate page details.
		$slug        = isset( $page_details['slug'] ) ? sanitize_title( $page_details['slug'] ) : 'sync-settings';
		$name        = isset( $page_details['name'] ) ? sanitize_text_field( $page_details['name'] ) : 'Settings';
		$parent_slug = isset( $page_details['parent_slug'] ) ? sanitize_title( $page_details['parent_slug'] ) : '';
		
		// Create nonce key.
		$nonce_key = 'sync_setting_' . ( $parent_slug ? $parent_slug . '_' : '' ) . $slug;
		$nonce     = wp_create_nonce( $nonce_key );
		
		// Start output buffering to collect HTML.
		ob_start();
		
		// Begin container.
		echo '<div class="sync-settings-container sync-single-form" data-refresh="' . ( $refresh ? 'true' : 'false' ) . '">';
		
		// Page header.
		echo '<div class="sync-settings-header">';
		echo '<h2>' . esc_html( $name ) . '</h2>';
		echo '</div>';
		
		// Begin form.
		echo '<form class="sync-settings-form" id="sync-form-' . esc_attr( $slug ) . '" data-slug="' . esc_attr( $slug ) . '">';
		
		// Add nonce field.
		echo '<input type="hidden" name="sync_nonce" value="' . esc_attr( $nonce ) . '">';
		echo '<input type="hidden" name="sync_nonce_key" value="' . esc_attr( $nonce_key ) . '">';
		echo '<input type="hidden" name="sync_action" value="save_' . esc_attr( $slug ) . '_settings">';
		
		// Process settings array to generate form elements.
		echo esc_html( $this->generate_settings_html( $settings_array ) );
		
		// Add submit button.
		echo '<div class="sync-form-footer">';
		echo '<button type="submit" class="sync-button sync-primary-button sync-submit-button">';
		echo '<span class="dashicons dashicons-saved"></span> ' . esc_html( $submit_button_text );
		echo '</button>';
		echo '<div class="sync-form-message"></div>';
		echo '</div>';
		
		// End form.
		echo '</form>';
		
		// End container.
		echo '</div>';
		
		// Return HTML.
		return ob_get_clean();
	}

	/**
	 * Create a settings page where each setting is saved individually via Ajax upon change
	 * 
	 * @param array $page_details   Page information (slug, name, parent_slug).
	 * @param array $settings_array Settings array structure.
	 * 
	 * @return string HTML for the settings page.
	 */
	public function create_each_ajax_settings_page( $page_details, $settings_array ) {
		// Validate page details.
		$slug        = isset( $page_details['slug'] ) ? sanitize_title( $page_details['slug'] ) : 'sync-settings';
		$name        = isset( $page_details['name'] ) ? sanitize_text_field( $page_details['name'] ) : 'Settings';
		$parent_slug = isset( $page_details['parent_slug'] ) ? sanitize_title( $page_details['parent_slug'] ) : '';
		
		// Create nonce key.
		$nonce_key = 'sync_setting_' . ( $parent_slug ? $parent_slug . '_' : '' ) . $slug;
		$nonce     = wp_create_nonce( $nonce_key );
		
		// Start output buffering to collect HTML.
		ob_start();
		
		// Begin container.
		echo '<div class="sync-settings-container sync-each-setting" data-slug="' . esc_attr( $slug ) . '">';
		
		// Page header.
		echo '<div class="sync-settings-header">';
		echo '<h2>' . esc_html( $name ) . '</h2>';
		echo '</div>';
		
		// Add hidden nonce fields.
		echo '<input type="hidden" id="sync-nonce-' . esc_attr( $slug ) . '" name="sync_nonce" value="' . esc_attr( $nonce ) . '">';
		echo '<input type="hidden" id="sync-nonce-key-' . esc_attr( $slug ) . '" name="sync_nonce_key" value="' . esc_attr( $nonce_key ) . '">';
		echo '<input type="hidden" id="sync-action-' . esc_attr( $slug ) . '" name="sync_action" value="save_' . esc_attr( $slug ) . '_setting">';
		
		// Process settings array to generate form elements with auto-save capability.
		echo esc_html( $this->generate_settings_html( $settings_array, true ) );
		
		// Add global message area.
		echo '<div class="sync-settings-message-area"></div>';
		
		// End container.
		echo '</div>';
		
		// Return HTML.
		return ob_get_clean();
	}

	/**
	 * Generate HTML from settings array
	 * 
	 * @param array $settings_array Settings structure.
	 * @param bool  $auto_save      Whether to enable auto-save for inputs.
	 * 
	 * @return string Generated HTML
	 */
	private function generate_settings_html( $settings_array, $auto_save = false ) {
		ob_start();
		
		foreach ( $settings_array as $type => $settings ) {
			switch ( $type ) {
				case 'flex':
					echo esc_html( $this->generate_flex_container( $settings, $auto_save ) );
					break;
					
				case 'p':
					echo '<p class="sync-text">' . esc_html( $settings ) . '</p>';
					break;
					
				case 'h1':
				case 'h2':
				case 'h3':
				case 'h4':
				case 'h5':
				case 'h6':
					// Correcting unescaped output issues.
					// Escaping $type variable.
					echo '<' . esc_attr( $type ) . ' class="sync-heading sync-' . esc_attr( $type ) . '">' . esc_html( $settings ) . '</' . esc_attr( $type ) . '>';
					break;
					
				case 'span':
					echo '<span class="sync-span">' . esc_html( $settings ) . '</span>';
					break;
					
				case 'icon':
					echo '<span class="dashicons dashicons-' . esc_attr( $settings ) . '"></span>';
					break;
					
				case 'break':
					$count = isset( $settings['count'] ) ? intval( $settings['count'] ) : 1;
					for ( $i = 0; $i < $count; $i++ ) {
						echo '<br>';
					}
					break;
					
				// Input elements handled in other methods.
				default:
					if ( strpos( $type, 'input_' ) === 0 ) {
						echo esc_html( $this->generate_input( substr( $type, 6 ), $settings, $auto_save ) );
					}
					break;
			}
		}
		
		return ob_get_clean();
	}

	/**
	 * Generate a flex container
	 * 
	 * @param array $settings  Flex container settings.
	 * @param bool  $auto_save Whether to enable auto-save for inputs.
	 * 
	 * @return string Generated HTML
	 */
	private function generate_flex_container( $settings, $auto_save = false ) {
		// Default flex settings.
		$direction     = isset( $settings['direction'] ) ? $settings['direction'] : 'column';
		$align_items   = isset( $settings['align']['item'] ) ? $settings['align']['item'] : 'stretch';
		$align_content = isset( $settings['align']['content'] ) ? $settings['align']['content'] : 'flex-start';
		
		ob_start();
		
		// Open flex container.
		echo '<div class="sync-flex" style="flex-direction: ' . esc_attr( $direction ) . '; align-items: ' . esc_attr( $align_items ) . '; justify-content: ' . esc_attr( $align_content ) . ';">';
		
		// Process content inside the flex container.
		if ( isset( $settings['content'] ) && is_array( $settings['content'] ) ) {
			echo esc_html( $this->generate_settings_html( $settings['content'], $auto_save ) );
		}
		
		// Close flex container.
		echo '</div>';
		
		return ob_get_clean();
	}

	/**
	 * Generate different input types
	 * 
	 * @param string $type      Input type (text, textarea, radio, etc.).
	 * @param array  $settings  Input settings.
	 * @param bool   $auto_save Whether to enable auto-save.
	 * 
	 * @return string Generated HTML
	 */
	private function generate_input( $type, $settings, $auto_save = false ) {
		// Common attributes.
		$name        = isset( $settings['name'] ) ? $settings['name'] : '';
		$value       = isset( $settings['value'] ) ? $settings['value'] : '';
		$placeholder = isset( $settings['place_holder'] ) ? $settings['place_holder'] : '';
		$label       = isset( $settings['label'] ) ? $settings['label'] : '';
		$required    = isset( $settings['required'] ) && $settings['required'] ? 'required' : '';
		$description = isset( $settings['description'] ) ? $settings['description'] : '';
		
		// Auto-save attribute.
		$auto_save_attr = $auto_save ? 'data-autosave="true"' : '';
		
		ob_start();
		
		// Wrapper with common classes.
		echo '<div class="sync-input-wrapper sync-' . esc_attr( $type ) . '-wrapper">';
		
		// Label if provided.
		if ( ! empty( $label ) ) {
			echo '<label for="sync-' . esc_attr( $name ) . '" class="sync-input-label">' . esc_html( $label ) . '</label>';
		}
		
		switch ( $type ) {
			case 'text':
				echo '<input type="text" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="sync-input sync-text-input" ' . esc_attr( $required ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				break;
				
			case 'textarea':
				echo '<textarea id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="sync-input sync-textarea" ' . esc_attr( $required ) . ' ' . esc_attr( $auto_save_attr ) . '>' . esc_textarea( $value ) . '</textarea>';
				break;
				
			case 'radio':
				if ( isset( $settings['options'] ) && is_array( $settings['options'] ) ) {
					echo '<div class="sync-radio-group">';
					foreach ( $settings['options'] as $option ) {
						$option_value = is_array( $option ) ? $option['value'] : $option;
						$option_label = is_array( $option ) ? $option['label'] : $option;
						$checked      = $value === $option_value ? 'checked' : '';
						
						echo '<label class="sync-radio-label">';
						echo '<input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $option_value ) . '" ' . esc_attr( $checked ) . ' ' . esc_attr( $auto_save_attr ) . '>';
						echo '<span class="sync-radio-text">' . esc_html( $option_label ) . '</span>';
						echo '</label>';
					}
					echo '</div>';
				}
				break;
				
			case 'toggle':
				$checked = $value ? 'checked' : '';
				echo '<label class="sync-toggle">';
				echo '<input type="checkbox" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="1" ' . esc_attr( $checked ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				echo '<span class="sync-toggle-slider"></span>';
				echo '</label>';
				break;
				
			case 'checkbox':
				$checked = $value ? 'checked' : '';
				echo '<label class="sync-checkbox-label">';
				echo '<input type="checkbox" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="1" ' . esc_attr( $checked ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				echo '<span class="sync-checkbox-text">' . esc_html( $label ) . '</span>';
				echo '</label>';
				break;
				
			case 'dropdown':
				// Escaping $required and $auto_save_attr variables.
				echo '<select id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" class="sync-dropdown" ' . esc_attr( $required ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				
				if ( ! empty( $placeholder ) ) {
					echo '<option value="" ' . ( empty( $value ) ? 'selected' : '' ) . ' disabled>' . esc_html( $placeholder ) . '</option>';
				}
				
				if ( isset( $settings['options'] ) && is_array( $settings['options'] ) ) {
					foreach ( $settings['options'] as $option ) {
						$option_value = is_array( $option ) ? $option['value'] : $option;
						$option_label = is_array( $option ) ? $option['label'] : $option;
						$selected     = $value === $option_value ? 'selected' : '';
						
						echo '<option value="' . esc_attr( $option_value ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $option_label ) . '</option>';
					}
				}
				
				echo '</select>';
				break;
				
			case 'date':
				echo '<input type="date" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="sync-input sync-date-input" ' . esc_attr( $required ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				break;
				
			case 'time':
				echo '<input type="time" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="sync-input sync-time-input" ' . esc_attr( $required ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				break;
				
			case 'datetime':
				echo '<input type="datetime-local" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="sync-input sync-datetime-input" ' . esc_attr( $required ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				break;
				
			case 'number':
				// Escaping attibutes like $min, $max, $step, $required, and $auto_save_attr variables for all security.
				$min  = isset( $settings['min'] ) ? 'min="' . esc_attr( $settings['min'] ) . '"' : '';
				$max  = isset( $settings['max'] ) ? 'max="' . esc_attr( $settings['max'] ) . '"' : '';
				$step = isset( $settings['step'] ) ? 'step="' . esc_attr( $settings['step'] ) . '"' : '';
				
				echo '<input type="number" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" ' . esc_attr( $min ) . ' ' . esc_attr( $max ) . ' ' . esc_attr( $step ) . ' placeholder="' . esc_attr( $placeholder ) . '" class="sync-input sync-number-input" ' . esc_attr( $required ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				break;
				
			case 'password':
				echo '<input type="password" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="sync-input sync-password-input" ' . esc_attr( $required ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				break;
				
			case 'email':
				echo '<input type="email" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="sync-input sync-email-input" ' . esc_attr( $required ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				break;
				
			case 'url':
				echo '<input type="url" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="sync-input sync-url-input" ' . esc_attr( $required ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				break;
				
			case 'color':
				echo '<input type="color" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="sync-input sync-color-input" ' . esc_attr( $required ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				break;
				
			case 'range':
				$min  = isset( $settings['min'] ) ? 'min="' . esc_attr( $settings['min'] ) . '"' : '';
				$max  = isset( $settings['max'] ) ? 'max="' . esc_attr( $settings['max'] ) . '"' : '';
				$step = isset( $settings['step'] ) ? 'step="' . esc_attr( $settings['step'] ) . '"' : '';
				
				echo '<input type="range" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" ' . esc_attr( $min ) . ' ' . esc_attr( $max ) . ' ' . esc_attr( $step ) . ' class="sync-input sync-range-input" ' . esc_attr( $required ) . ' ' . esc_attr( $auto_save_attr ) . '>';
				
				// Add value display for range input.
				echo '<span class="sync-range-value">' . esc_html( $value ) . '</span>';
				break;
				
			case 'button':
				$button_text  = isset( $settings['text'] ) ? $settings['text'] : 'Button';
				$button_type  = isset( $settings['button_type'] ) ? $settings['button_type'] : 'button';
				$button_class = isset( $settings['class'] ) ? $settings['class'] : 'sync-button';
				$icon         = isset( $settings['icon'] ) ? '<span class="dashicons dashicons-' . esc_attr( $settings['icon'] ) . '"></span> ' : '';
				
				// Escaping $icon variable.
				echo '<button type="' . esc_attr( $button_type ) . '" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $button_class ) . '">' . esc_html( $icon ) . esc_html( $button_text ) . '</button>';
				break;
				
			case 'data':
				// Custom data input (key-value pairs).
				echo '<div class="sync-data-input-container" data-name="' . esc_attr( $name ) . '">';
				echo '<table class="sync-data-table">';
				echo '<thead><tr><th>Key</th><th>Value</th><th></th></tr></thead>';
				echo '<tbody>';
				
				// Add existing data pairs.
				if ( ! empty( $value ) && is_array( $value ) ) {
					foreach ( $value as $key => $val ) {
						echo '<tr class="sync-data-row">';
						echo '<td><input type="text" class="sync-data-key" value="' . esc_attr( $key ) . '"></td>';
						echo '<td><input type="text" class="sync-data-value" value="' . esc_attr( $val ) . '"></td>';
						echo '<td><button type="button" class="sync-data-remove"><span class="dashicons dashicons-trash"></span></button></td>';
						echo '</tr>';
					}
				}
				
				// Add empty row for new entries.
				echo '<tr class="sync-data-row">';
				echo '<td><input type="text" class="sync-data-key" placeholder="Key"></td>';
				echo '<td><input type="text" class="sync-data-value" placeholder="Value"></td>';
				echo '<td><button type="button" class="sync-data-remove"><span class="dashicons dashicons-trash"></span></button></td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				
				// Add hidden input to store JSON data.
				echo '<input type="hidden" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_json_encode( $value ) ) . '" ' . esc_attr( $auto_save_attr ) . '>';
				
				// Add button.
				echo '<button type="button" class="sync-button sync-data-add">';
				echo '<span class="dashicons dashicons-plus"></span> Add New Entry';
				echo '</button>';
				echo '</div>';
				break;
				
			case 'file':
				// WordPress media uploader field.
				$file_url = is_array( $value ) && isset( $value['url'] ) ? $value['url'] : $value;
				$file_id  = is_array( $value ) && isset( $value['id'] ) ? $value['id'] : '';
				
				echo '<div class="sync-file-upload-container">';
				echo '<input type="text" id="sync-' . esc_attr( $name ) . '-url" class="sync-file-url" value="' . esc_attr( $file_url ) . '" placeholder="' . esc_attr( $placeholder ) . '" readonly>';
				echo '<input type="hidden" id="sync-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $file_id ) . '" ' . esc_attr( $auto_save_attr ) . '>';
				echo '<button type="button" class="sync-button sync-file-upload-button">Select File</button>';
				echo '<button type="button" class="sync-button sync-file-remove-button" ' . ( empty( $file_url ) ? 'style="display:none;"' : '' ) . '>Remove</button>';
				echo '</div>';
				break;
		}
		
		// Description if provided.
		if ( ! empty( $description ) ) {
			echo '<p class="sync-input-description">' . esc_html( $description ) . '</p>';
		}
		
		// Individual message area for auto-save inputs.
		if ( $auto_save ) {
			echo '<div class="sync-input-message" data-for="' . esc_attr( $name ) . '"></div>';
		}
		
		echo '</div>'; // Close wrapper.
		
		return ob_get_clean();
	}
}
