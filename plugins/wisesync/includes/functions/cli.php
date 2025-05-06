<?php
/**
 * WiseSync CLI Functions
 *
 * Handles WiseSync CLI settings and operations
 *
 * @package WiseSync
 * @since 1.0.0
 */

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
function sync_register_cli_command( $command_name, $callback, $args ) {
	global $sync_cli;

	return $sync_cli->register_wp_cli_command( $command_name, $callback, $args );
}
