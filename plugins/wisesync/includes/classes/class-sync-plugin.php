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
	public function show_admin_notice_mu_plugin(): void {
		sync_generate_admin_notice( 'Mu Plugin Path Not Correct', 'Error', 'info', true, '!' );
	}
}
