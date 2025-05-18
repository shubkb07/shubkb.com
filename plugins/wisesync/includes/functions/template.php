<?php
/**
 * WiseSync Template Functions
 *
 * This file contains template functions for the WiseSync plugin.
 *
 * @package   WiseSync
 * @since     1.0.0
 */

/**
 * Register a single template.
 *
 * @param string $template_type Template type (post_type, taxonomy, meta_box, user_role, theme_template, theme_part).
 * @param array  $template_data Template configuration data.
 * @param bool   $active        Whether to activate template immediately.
 * @return int|false Template post ID on success, false on failure.
 */
function sync_register_template( $template_type, $template_data, $active = false ) {
	global $sync_template;
	return $sync_template->register_template( $template_type, $template_data, $active );
}

/**
 * Register a template group.
 *
 * @param string       $group_name Group name.
 * @param array|string $templates  Array of templates or JSON string.
 * @param bool         $active     Whether to activate group immediately.
 * @return int|false Template group post ID on success, false on failure.
 */
function sync_register_template_group( $group_name, $templates, $active = false ) {
	global $sync_template;
	return $sync_template->register_template_group( $group_name, $templates, $active );
}

/**
 * Activate a template or template group.
 *
 * @param int $template_id Template or template group post ID.
 * @return bool True on success, false on failure.
 */
function sync_activate_template( $template_id ) {
	global $sync_template;
	return $sync_template->activate_template( $template_id );
}

/**
 * Deactivate a template or template group.
 *
 * @param int $template_id Template or template group post ID.
 * @return bool True on success, false on failure.
 */
function sync_deactivate_template( $template_id ) {
	global $sync_template;
	return $sync_template->deactivate_template( $template_id );
}

/**
 * Delete a template or template group.
 *
 * @param int  $template_id Template or template group post ID.
 * @param bool $force       Whether to force deletion of active templates.
 * @return bool True on success, false on failure.
 */
function sync_delete_template( $template_id, $force = false ) {
	global $sync_template;
	return $sync_template->delete_template( $template_id, $force );
}

/**
 * Get all templates of a specific type.
 *
 * @param string $template_type Template type.
 * @param bool   $active_only   Whether to return only active templates.
 * @return array Array of template posts.
 */
function sync_get_templates( $template_type = '', $active_only = false ) {
	global $sync_template;
	return $sync_template->get_templates( $template_type, $active_only );
}

/**
 * Get all template groups.
 *
 * @param bool $active_only Whether to return only active groups.
 * @return array Array of template group posts.
 */
function sync_get_template_groups( $active_only = false ) {
	global $sync_template;
	return $sync_template->get_template_groups( $active_only );
}

/**
 * Check if a template is active.
 *
 * @param int $template_id Template post ID.
 * @return bool True if active, false otherwise.
 */
function sync_is_template_active( $template_id ) {
	global $sync_template;
	return $sync_template->is_template_active( $template_id );
}

/**
 * Get template configuration data.
 *
 * @param int $template_id Template post ID.
 * @return array|false Template data on success, false on failure.
 */
function sync_get_template_data( $template_id ) {
	global $sync_template;
	return $sync_template->get_template_data( $template_id );
}

/**
 * Update template configuration data.
 *
 * @param int   $template_id   Template post ID.
 * @param array $template_data New template data.
 * @return bool True on success, false on failure.
 */
function sync_update_template_data( $template_id, $template_data ) {
	global $sync_template;
	return $sync_template->update_template_data( $template_id, $template_data );
}
