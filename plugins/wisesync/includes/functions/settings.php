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
 * Edit wp-config.php file to add or remove code blocks between sync markers
 *
 * @param string $code   The code block to add or remove.
 * @param string $action The action to perform ("add" or "remove").
 *
 * @return bool|string   True on success, error message on failure
 */
function sync_edit_wp_config( $code, $action = 'add' ) {
	global $sync_settings;

	return $sync_settings->edit_wp_config( $code, $action );
}

/**
 * Edit .htaccess file to add or remove code blocks between sync markers
 *
 * @param string $code   The code block to add or remove.
 * @param string $action The action to perform ("add" or "remove").
 * @param string $path   Optional custom path to .htaccess file. Default is site root.
 * @return bool|string   True on success, error message on failure
 */
function edit_htaccess( $code, $action = 'add', $path = '' ) {
	global $sync_settings;

	return $sync_settings->edit_htaccess( $code, $action, $path );
}

/**
 * Output an admin notice with optional title and styled icon,
 *
 * @param string       $message        The notice message (may include basic HTML).
 * @param string       $title          Optional title, rendered in an <h2>.
 * @param string       $status         One of 'error', 'warning', 'success' or 'info'
 *                                     (you may also pass 'notice-error', etc.; shorthand is normalized).
 * @param bool         $is_dismissible Whether the notice is dismissible. Default true.
 * @param false|string $icon           False for no icon, or a string:
 *                                     – If it starts with 'dashicons-', renders that Dashicon.
 *                                     – Otherwise escaped and rendered as text/emoji.
 * @return void
 * @throws \InvalidArgumentException If $message is not a string.
 */
function sync_generate_admin_notice( $message, $title = '', $status = 'success', $is_dismissible = true, $icon = false ) {
	global $sync_settings;

	$sync_settings->generate_admin_notice( $message, $title, $status, $is_dismissible, $icon );
}

/**
 * Initialize settings page.
 *
 * @param string     $menu_slug Menu slug.
 * @param string     $menu_name Menu name.
 * @param int        $position Menu position.
 * @param bool|array $create_sync_menu Create sync menu.
 * @param string     $settings_level Settings level.
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
 * @param bool|array   $sub_menu_support Sub menu support.
 */
function sync_add_sync_menu( $wp_menu_slug, $menu_name, $settings_callback, $menu_slug = false, $icon_url = null, $position = null, $sub_menu_support = false ) {
	global $sync_settings;

	$sync_settings->add_sync_menus( $wp_menu_slug, $menu_name, $settings_callback, $menu_slug, $icon_url, $position, $sub_menu_support );
}

/**
 * Add Sync Sub Menu.
 *
 * @param string   $wp_menu_slug WP Menu slug.
 * @param string   $parent_menu_slug Parent menu slug.
 * @param callback $settings_callback Settings callback.
 * @param string   $menu_name Menu name.
 * @param string   $menu_slug Menu slug.
 * @param int|null $position Menu position.
 */
function sync_add_sync_sub_menu( $wp_menu_slug, $parent_menu_slug, $settings_callback, $menu_name, $menu_slug, $position = null ) {
	global $sync_settings;

	return $sync_settings->add_sync_sub_menus( $wp_menu_slug, $parent_menu_slug, $settings_callback, $menu_name, $menu_slug, $position );
}


/**
 * Register Widget Settings for WordPress Widgets.
 *
 * @param string $widget_slug Widget slug.
 * @param string $widget_name Widget name.
 * @param string $widget_callback Widget callback.
 *
 * @return bool True if the widget settings were registered, false otherwise.
 */
function sync_register_widget_settings( $widget_slug, $widget_name, $widget_callback ) {
	global $sync_settings;

	return $sync_settings->register_widget_settings( $widget_slug, $widget_name, $widget_callback );
}

/**
 * Register Sync Widget.
 *
 * @param array $settings_array Settings Array.
 * @param bool  $return_html Return HTML flag.
 *
 * @return void|string HTML Code of Widget.
 */
function sync_create_widget_settings( $settings_array, $return_html = false ) {
	global $sync_settings;

	return $sync_settings->create_widget_settings( $settings_array, $return_html );
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
