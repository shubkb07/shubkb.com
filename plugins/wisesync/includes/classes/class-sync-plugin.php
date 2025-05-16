<?php
/**
 * Sync Plugin Class
 *
 * @package WiseSync
 * @since 1.0.0
 */

namespace Sync;

/**
 * Class Sync_Plugin
 *
 * @since 1.0.0
 */
class Sync_Plugin {

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Show Mu Plugin Path Not Correct Message. 
	 *
	 * @return void
	 */
	public function show_admin_notice_mu_plugin() {
		global $sync_filesystem;

		$items = array();

		// 1. Check your wp-config.php path.
		$items[] = __( 'Please verify that the path to the MU-Plugin load file is correct in your <code>wp-config.php</code>.', 'wisesync' );

		// 2. Edit the sync.php file in the right folder.
		if ( $sync_filesystem->is_vip_site() ) {
			$items[] = sprintf(
				/* translators: %1$s: filename, %2$s: folder name */
				__( 'Edit %1$s inside your %2$s folder.', 'wisesync' ),
				'<code>sync.php</code>',
				'<code>client-mu-plugins</code>'
			);
		} else {
			$items[] = sprintf(
				/* translators: %1$s: filename, %2$s: folder name */
				__( 'Edit %1$s inside your %2$s folder.', 'wisesync' ),
				'<code>sync.php</code>',
				'<code>mu-plugins</code>'
			);
		}

		// 3. Ensure the load-path variable is pointing at the right file.
		$items[] = sprintf(
			/* translators: %1$s: variable name, %2$s: path */
			__( 'Ensure %1$s is pointing to %2$s.', 'wisesync' ),
			'<code>$sync_my_plugin_path</code>',
			'<code>./includes/load/mu-plugins.php</code>'
		);

		// Build our <ul><li>…</li></ul> markup with styled bullet points.
		$message = '<ul style="list-style-type: disc; padding-left: 20px;"><li>' . implode( '</li><li>', array_map( 'wp_kses_post', $items ) ) . '</li></ul>';

		sync_generate_admin_notice(
			$message,
			__( 'Mu-Plugin Load Error', 'wisesync' ),
			'error',
			false,
			true
		);
	}
}
