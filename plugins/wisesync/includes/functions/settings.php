<?php
/**
 * WiseSync Settings Functions
 *
 * Handles WiseSync settings and operations
 *
 * @package WiseSync
 * @since 1.0.0
 */

use Sync\Sync_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Initialize settings page.
 *
 * @param string $menu_slug Menu slug.
 * @param string $menu_name Menu name.
 * @param int    $position Menu position.
 * @param bool   $create_sync_menu Create sync menu.
 * @param string $settings_level Settings level.
 *
 * @since 1.0.0
 */
function sync_add_wp_settings_menu( $menu_slug, $menu_name, $position = 100, $create_sync_menu = true, $settings_level = 'site' ) {

	/**
	 * Sync Settings Class
	 *
	 * @global Sync_Settings
	 */
	global $sync_settings;

	$sync_settings->add_wp_menu( $menu_slug, $menu_name, $position, $create_sync_menu, $settings_level );
}

/**
 * Add Sync Menu.
 *
 * @param string       $wp_menu_slug WP Menu slug.
 * @param string       $menu_name Menu name.
 * @param string       $settings_callback Settings callback.
 * @param string|false $menu_slug Menu slug.
 * @param string|null  $icon_url Icon URL.
 * @param int|null     $position Menu position.
 * @param bool         $sub_menu_support Sub menu support.
 */
function sync_add_sync_menu( $wp_menu_slug, $menu_name, $settings_callback, $menu_slug = false, $icon_url = null, $position = null, $sub_menu_support = false ) {
	global $sync_settings;

	$sync_settings->add_sync_menus( $wp_menu_slug, $menu_name, $settings_callback, $menu_slug, $icon_url, $position, $sub_menu_support );
}

/**
 * Add Sync Sub Menu.
 *
 * @param string   $parent_menu_slug Parent menu slug.
 * @param string   $menu_name Menu name.
 * @param string   $settings_callback Settings callback.
 * @param string   $menu_slug Menu slug.
 * @param int|null $position Menu position.
 */
function sync_add_sync_sub_menu( $parent_menu_slug, $menu_name, $settings_callback, $menu_slug, $position = null ) {
	global $sync_settings;

	return $sync_settings->add_sync_sub_menus( $parent_menu_slug, $menu_name, $settings_callback, $menu_slug, $position );
}

/**
 * Create a single AJAX settings page.
 *
 * @param array $page_details Page details.
 * @param array $settings_array Settings array.
 * @param bool  $refresh Refresh flag.
 */
function sync_create_single_ajax_settings_page( $page_details, $settings_array, $refresh = false ) {
	global $sync_settings;


	return $sync_settings->create_single_ajax_settings_page( $page_details, $settings_array, $refresh );
}

/**
 * Create each AJAX settings page.
 *
 * @param array $page_details Page details.
 * @param array $settings_array Settings array.
 */
function sync_create_each_ajax_settings_page( $page_details, $settings_array ) {
	global $sync_settings;

	return $sync_settings->create_each_ajax_settings_page( $page_details, $settings_array );
}

add_action(
	'sync_add_settings_page',
	function () {
		sync_add_wp_settings_menu(
			'settings',
			__( 'WiseSync Settings', 'wisesync' ),
			10,
			array(
				'menu_name' => 'Cat',
				'icon_url'  => null,
				'callback'  => 'sync_register_settings_tools',
			),
			'site'
		);

		// Example usage of sync_add_sync_menu with sub-menus.
		sync_add_sync_menu(
			'settings',
			__( 'Sync Dashboard', 'wisesync' ),
			'sync_register_settings_tools',
			'dashboard',
			null,
			20,
			array(
				'menu_name' => 'Pika',
				'menu_slug' => 'pika',
			) 
		);
		sync_add_sync_sub_menu( 'settings', 'dashboard', 'sync_register_settings_tools', __( 'Sync Logs', 'wisesync' ), 'logs', 30 );
		sync_add_sync_sub_menu( 'settings', 'dashboard', 'sync_register_settings_tools', __( 'Sync Settings', 'wisesync' ), 'settings', 40 );

		// Example usage of sync_add_sync_menu without sub-menus.
		sync_add_sync_menu( 'settings', __( 'Sync Reports', 'wisesync' ), 'sync_register_settings_tools', 'reports', null, 50 );

		// Another example with sub-menus.
		sync_add_sync_menu(
			'settings',
			__( 'Advanced Sync', 'wisesync' ),
			'sync_register_settings_tools',
			'advanced',
			null,
			60,
			array(
				'menu_name' => 'WpW',
				'menu_slug' => 'wow',
			) 
		);
		sync_add_sync_sub_menu( 'settings', 'advanced', 'sync_register_settings_tools', __( 'Sync Tools', 'wisesync' ), 'tools', 70 );
		sync_add_sync_sub_menu( 'settings', 'advanced', 'sync_register_settings_tools', __( 'Sync Diagnostics', 'wisesync' ), 'diagnostics', 80 );
	}
);

/**
 * Dashboard Settings Page
 *
 * Dynamically generate content for all menus and submenus.
 *
 * @param string $content HTML content.
 * @param array  $page_details Page details.
 */
function sync_register_settings_dashboard( $content, $page_details ) {
	$settings_array = array(
		'html'   => array(
			'flex' => array(
				'direction' => 'column',
				'align'     => array(
					'item'    => 'center',
					'content' => 'flex-start',
				),
				'content'   => array(
					'p'            => 'This is the dashboard settings page.',
					'input_text'   => array(
						'name'         => 'dashboard_input',
						'value'        => '',
						'place_holder' => 'Enter dashboard value',
						'sync_option'  => 'input_dashbaord',
						'regex'        => '^[a-zA-Z0-9]+$',
					),
					'input_toggle' => array(
						'name'        => 'dashboard_toggle',
						'value'       => false,
						'sync_option' => 'input_dashboard_toggle',
					),
				),
			),
		),
		'submit' => array(
			'seprate'        => 'seo_setup',
			'return_html'    => true,
			'should_refresh' => true,
		),
	);

	return sync_create_each_ajax_settings_page( $page_details, $settings_array );
}

/**
 * Tools Settings Page
 *
 * Dynamically generate content for all menus and submenus.
 *
 * @param string $content HTML content.
 * @param array  $page_details Page details.
 */
function sync_register_settings_tools( $content, $page_details ) {
	$settings_array = array(
		'html'   => array(
			// Heading with custom class, style, and conditional display.
			'h2'    => array(
				'text'         => 'Section Title',
				'class'        => 'section-title',
				'style'        => 'margin-bottom:1rem;',
				'on_condition' => 'toggle_example',
			),
	
			// Paragraph with custom styling.
			'p'     => array(
				'text'         => 'This paragraph appears only if the toggle is on.',
				'class'        => 'intro-text',
				'style'        => 'font-style:italic;',
				'on_condition' => 'toggle_example',
			),

			// Break line.
			'break' => array(
				'count' => 2,
				'style' => 'margin-bottom:1rem;',
				'class' => 'gaping',
			),
	
			// Flex container holding a row of inputs.
			'flex'  => array(
				'direction'    => 'row',
				'align'        => array(
					'item'    => 'center',
					'content' => 'space-between',
				),
				'class'        => 'flex-row',
				'style'        => 'gap:1rem;',
				'on_condition' => 'checkbox_example',
				'content'      => array(
	
					// Text input with regex and error/success messages.
					'input_text'     => array(
						'name'                          => 'text_example',
						'sync_option'                   => 'input_text_example',
						'label'                         => 'Text Field',
						'value'                         => 'default',
						'place_holder'                  => 'Enter at least 6 chars',
						'required'                      => true,
						'regex'                         => '/^.{6,}$/',
						'description'                   => 'Must be 6 or more characters.',
						'class'                         => 'wide-input',
						'style'                         => 'width:100%;',
						'on_condition'                  => 'toggle_example',
						'regex_message_error_content'   => array(
							'p' => array(
								'text'  => 'Too short!',
								'class' => 'error-message',
							),
						),
						'regex_message_success_content' => array(
							'p' => array(
								'text'  => 'Looks good!',
								'class' => 'success-message',
							),
						),
					),
	
					// Textarea.
					'input_textarea' => array(
						'name'         => 'textarea_example',
						'sync_option'  => 'input_textarea_example',
						'label'        => 'Description',
						'value'        => 'Some long text...',
						'place_holder' => 'Type here...',
						'description'  => 'You can write multiple lines.',
						'class'        => 'tall-textarea',
						'style'        => 'height:4rem;',
					),
	
					// Radio buttons.
					'input_radio'    => array(
						'name'        => 'radio_example',
						'sync_option' => 'input_radio_example',
						'value'       => 'opt2',
						'options'     => array(
							array(
								'value' => 'opt1',
								'label' => 'Option 1',
							),
							array(
								'value' => 'opt2',
								'label' => 'Option 2',
							),
						),
						'label'       => 'Choose One',
						'class'       => 'radio-group',
					),
	
					// Toggle (checkbox styled).
					'input_toggle'   => array(
						'name'        => 'toggle_example',
						'sync_option' => 'input_toggle_example',
						'value'       => 1,
						'label'       => 'Enable Feature',
						'description' => 'Turn this on to enable.',
					),
	
					// Simple checkbox.
					'input_checkbox' => array(
						'sync_option' => 'input_checkbox_example',
						'name'        => 'checkbox_example',
						'value'       => 0,
						'label'       => 'Show Flex',
					),
	
					// Dropdown select.
					'input_dropdown' => array(
						'name'        => 'dropdown_example',
						'sync_option' => 'input_dropdown_example',
						'value'       => 'b',
						'placeholder' => 'Select an option',
						'options'     => array(
							array(
								'value' => 'a',
								'label' => 'Alpha',
							),
							array(
								'value' => 'b',
								'label' => 'Beta',
							),
							array(
								'value' => 'c',
								'label' => 'Gamma',
							),
						),
					),
	
					// Date, Time, and DateTime.
					'input_date'     => array(
						'name'        => 'date_example',
						'sync_option' => 'input_date_example',
						'value'       => '2025-05-03',
					),
					'input_time'     => array(
						'name'        => 'time_example',
						'sync_option' => 'input_time_example',
						'value'       => '14:30',
					),
					'input_datetime' => array(
						'name'        => 'datetime_example',
						'sync_option' => 'input_datetime_example',
						'value'       => '2025-05-03T14:30',
					),
	
					// Number input.
					'input_number'   => array(
						'name'        => 'number_example',
						'sync_option' => 'input_number_example',
						'value'       => 5,
						'min'         => 1,
						'max'         => 10,
						'step'        => 1,
					),
	
					// Password.
					'input_password' => array(
						'name'         => 'password_example',
						'sync_option'  => 'input_password_example',
						'value'        => '',
						'place_holder' => 'Enter password',
					),
	
					// Email.
					'input_email'    => array(
						'name'        => 'email_example',
						'sync_option' => 'input_email_example',
						'value'       => 'user@example.com',
					),
	
					// URL.
					'input_url'      => array(
						'name'        => 'url_example',
						'sync_option' => 'input_url_example',
						'value'       => 'https://example.com',
					),
	
					// Color picker.
					'input_color'    => array(
						'name'        => 'color_example',
						'sync_option' => 'input_color_example',
						'value'       => '#ff0000',
					),
	
					// Range slider.
					'input_range'    => array(
						'name'        => 'range_example',
						'sync_option' => 'input_range_example',
						'value'       => 50,
						'min'         => 0,
						'max'         => 100,
						'step'        => 5,
					),
	
					// Button.
					'input_button'   => array(
						'name'        => 'button_example',
						'sync_option' => 'input_button_example',
						'text'        => 'Click Me',
						'button_type' => 'button',
						'class'       => 'sync-button-primary',
						'icon'        => 'admin-generic',
					),
	
					// Data table.
					'input_data'     => array(
						'name'        => 'data_example',
						'sync_option' => 'input_data_example',
						'value'       => array(
							'key1' => 'value1',
							'key2' => 'value2',
						),
					),
	
					// File upload.
					'input_file'     => array(
						'name'         => 'file_example',
						'sync_option'  => 'input_file_example',
						'value'        => array(
							'id'  => 0,
							'url' => '',
						),
						'place_holder' => 'No file selected',
					),
	
				),
			),
		),
		'submit' => array(
			'seprate'        => 'test_action',
			'return_html'    => true,
			'should_refresh' => false,
		),
	);  
	return sync_create_single_ajax_settings_page( $page_details, $settings_array );
}

/**
 * Default Show Function
 *
 * This function is called when no specific settings are defined for a menu.
 *
 * @param string $html_content HTML content.
 * @param array  $page_details Page details.
 */
function default_show( $html_content, $page_details ) {
	return 'Page Details' . wp_json_encode( $page_details );
}
