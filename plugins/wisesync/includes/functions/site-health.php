<?php
/**
 * WiseSync Site Health Functions
 *
 * This file contains functions for site health checks in the WiseSync plugin.
 *
 * @package   WiseSync
 * @since     1.0.0
 */

/**
 * Register a new section in the Site Health tab via wrapper.
 *
 * @param string      $slug The section slug.
 * @param string      $name The section name.
 * @param string|bool $description Optional description, false by default.
 * @return void
 */
function sync_register_site_health_section( $slug, $name, $description = false ) {
	global $sync_site_health;
	if ( $sync_site_health instanceof \Sync\Sync_Site_Health ) {
		$sync_site_health->register_site_health_section( $slug, $name, $description );
	}
}

/**
 * Register a table section within a registered section via wrapper.
 *
 * @param string      $section_slug The parent section slug.
 * @param string      $name The table section name.
 * @param array       $section_data Key-value pairs for the table.
 * @param string|bool $description Optional description, false by default.
 * @param string|bool $section_for Optional category (Performance, Security), false by default.
 * @param string|bool $status Optional status (Good, Recommended, Critical, Should be improved), false by default.
 * @return void
 */
function sync_register_site_health_table_section( $section_slug, $name, $section_data, $description = false, $section_for = false, $status = false ) {
	global $sync_site_health;
	if ( $sync_site_health instanceof \Sync\Sync_Site_Health ) {
		$sync_site_health->register_site_health_table_section( $section_slug, $name, $section_data, $description, $section_for, $status );
	}
}

/**
 * Register a log section within a registered section via wrapper.
 *
 * @param string      $section_slug The parent section slug.
 * @param string      $name The log section name.
 * @param string      $section_data The log content.
 * @param string|bool $description Optional description, false by default.
 * @param string|bool $section_for Optional category (Performance, Security), false by default.
 * @param bool        $separate_copy_button Whether to show a separate copy button.
 * @return void
 */
function sync_register_site_health_log_section( $section_slug, $name, $section_data, $description = false, $section_for = false, $separate_copy_button = false ) {
	global $sync_site_health;
	if ( $sync_site_health instanceof \Sync\Sync_Site_Health ) {
		$sync_site_health->register_site_health_log_section( $section_slug, $name, $section_data, $description, $section_for, $separate_copy_button );
	}
}
