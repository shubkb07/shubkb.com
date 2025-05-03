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
			'flex' => array(
				'direction' => 'row',
				'align'     => array(
					'item'    => 'flex-start',
					'content' => 'center',
				),
				'content'   => array(
					'p'           => 'This is the tools settings page.',
					'input_radio' => array(
						'name'        => 'tools_radio',
						'value'       => 'option1',
						'options'     => array( 'option1', 'option2', 'option3' ),
						'sync_option' => 'input_tools_radio',
					),
					'input_data'  => array(
						'name'         => 'tools_data',
						'value'        => '',
						'place_holder' => 'Enter data value',
						'sync_option'  => 'input_tools_data',
					),
					'break'       => array(
						'count' => 1,
					),
				),
			),
		),
		'submit' => array(
			'seprate'        => 'link_tools_setup',
			'return_html'    => true,
			'should_refresh' => true,
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
