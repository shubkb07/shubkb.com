<?php
/**
 * WiseSync Helpers Functions
 *
 * This file contains helper functions for the WiseSync plugin.
 *
 * @package   WiseSync
 * @since     1.0.0
 */

/**
 * Adds a role, if it does not exist, with VIP Handler.
 *
 * This function is a wrapper for the `add_role` function, which adds a new role to WordPress.
 * While Following VIP Guidelines for Its Environment.
 *
 * @param string $role         Role name.
 * @param string $display_name Display name for role.
 * @param bool[] $capabilities List of capabilities keyed by the capability name,
 *                             e.g. array( 'edit_posts' => true, 'delete_posts' => false ).
 * @return WP_Role|void WP_Role object, if the role is added.
 */
function sync_add_role( $role, $display_name, $capabilities = array() ) {
	if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
		wpcom_vip_add_role( $role, $display_name, $capabilities );
	} else {
		return add_role( $role, $display_name, $capabilities ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.custom_role_add_role
	}
}
