<?php
/**
 * Sync CLI Class
 *
 * Handles WiseSync CLI settings and operations
 *
 * @package WiseSync
 * @since 1.0.0
 */

namespace Sync;

/**
 * Sync CLI Class
 *
 * This class provides methods to handle CLI requests securely and efficiently.
 */
class Sync_CLI {

	/**
	 * Whether the request is a CLI request
	 *
	 * @var bool
	 */
	public $is_cli = false;

	/**
	 * Current CLI Command Name
	 *
	 * @var string
	 */
	public $command = '';

	/**
	 * 
	 * CLI Constructor.
	 */
	public function __construct() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->is_cli  = true;
			$this->command = isset( $GLOBALS['argv'][1] ) ? $GLOBALS['argv'][1] : '';
		}
	}

	/**
	 * Register CLI Command.
	 *
	 * @param string $command_name Command name.
	 * @param string $callback Callback function.
	 * @param string $args Command arguments.
	 * 
	 * @see https://make.wordpress.org/cli/handbook/references/internal-api/wp-cli-add-command/ For more information.
	 *
	 * @return bool True if the command was registered, false otherwise.
	 */
	public function register_wp_cli_command( $command_name, $callback, $args ) {
		if ( $this->is_cli && $command_name === $this->command && is_callable( $callback ) ) {
			\WP_CLI::add_command( $command_name, $callback, $args );
			return true;
		} else {
			return false;
		}
	}
}
