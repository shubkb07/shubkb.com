<?php
/**
 * Sync Settings Class
 *
 * Handles sync settings and operations
 *
 * @package WiseSync
 * @since 1.0.0
 */

namespace Sync;

/**
 * Sync Settings Class
 *
 * @since 1.0.0
 */
class Sync_Settings {

	/**
	 * Menus array.
	 *
	 * @var array
	 */
	private $menus = array();

	/**
	 * Sync Menus Array.
	 *
	 * @var array
	 */
	private $sync_menus = array();

	/**
	 * Sync Widgets Array.
	 *
	 * @var array
	 */
	private $sync_widgets = array();

	/**
	 * Sync Ajax Instance.
	 *
	 * @var array
	 */
	private $sync_ajax_instance = null;

	/**
	 * Sync Settings constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $sync_ajax;

		// Register actions for both regular and AJAX requests.
		add_action( 'admin_menu', array( $this, 'init_settings_page' ) );
		add_action( 'network_admin_menu', array( $this, 'init_settings_page' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'init_widgets' ) );

		if ( isset( $sync_ajax ) && $sync_ajax->is_ajax ) {
			add_action( 'wp_loaded', array( $this, 'init_settings_page' ) );
		}

		add_action( 'admin_notices', array( $this, 'init_admin_notices' ) );
	}

	/**
	 * Generate MU-Plugins and Drop-ins.
	 *
	 * @param string $file_to_generate The file to generate.
	 * @param array  $data             The data to interpolate into the file.
	 *
	 * @return void
	 * 
	 * @throws \Exception If the template file is not found.
	 */
	public function add_plugin_setting_files( $file_to_generate, $data = array() ) {

		global $sync_filesystem;

		// Files Array.
		$files_array = array(
			'advcache'  => array(
				'PATH'   => WP_PLUGIN_DIR,
				'FILE'   => 'advanced-cache.php',
				'ACCEPT' => 'adv_cache',
			),
			'objcache'  => array(
				'PATH'   => WP_PLUGIN_DIR,
				'FILE'   => 'object-cache.php',
				'ACCEPT' => 'obj_cache',
			),
			'sunrise'   => array(
				'PATH'   => WP_PLUGIN_DIR,
				'FILE'   => 'sunrise.php',
				'ACCEPT' => 'sunrise',
			),
			'muplugins' => array(
				'PATH'   => WPMU_PLUGIN_DIR,
				'FILE'   => 'sync.php',
				'ACCEPT' => 'mu_plugin',
			),
		);

		// $file_to_generate is not string, empty or not exists in $files_array, then return.
		if ( ! is_string( $file_to_generate ) || empty( $file_to_generate ) || ! array_key_exists( $file_to_generate, $files_array ) ) {
			return;
		}

		// If data is not array, then return.
		if ( ! is_array( $data ) ) {
			return;
		}

		if ( ! $sync_filesystem->exists( WSYNC_PLUGIN_DIR . 'assets/template/load-settings-template.php' ) ) {
			// Throw Template Not Present Error, with Suggesting to Install Plugin Again.
			throw new \Exception( 'Template file not found. Please install the plugin again.' );
		}

		$current_file_to_generate = $files_array[ $file_to_generate ];

		// Convert data to JSON.
		$current_file_to_generate['FILE_DATA'] = $data;

		// Create Constant.
		$current_file_to_generate['CONSTANT'] = 'WSYNC_' . strtoupper( $current_file_to_generate['ACCEPT'] );

		// Now Creating an Template File Data.
		$template_file = $sync_filesystem->get_contents( WSYNC_PLUGIN_DIR . 'assets/template/load-settings-template.php' );

		// Remove 'phpcs:disable' line and normalize empty comment lines and Remove consecutive empty comment lines, leaving just one.
		$template_file = preg_replace( '/(\s*\n\s*\*\s*\n)\s*\*\s*\n/', '$1', preg_replace( '/\s*\n\s*\*\s*phpcs\:disable\s*\n/', "\n", $template_file ) );

		// Load Plugin Header.
		$implementation_array = array_merge( get_plugin_data( WSYNC_LOAD_DIR . $current_file_to_generate['FILE'] ), $current_file_to_generate );

		$implementation_array['Description'] = preg_replace( '/<cite>.*?<\/cite>/s', '', $implementation_array['Description'] );

		// Interpolate the template file with the data.
		$interpolated_content = $this->interpolate_array_to_text( $implementation_array, $template_file );

		$sync_filesystem->put_contents( $current_file_to_generate['PATH'] . '/' . $current_file_to_generate['FILE'], $interpolated_content, 'sync_' . $current_file_to_generate['ACCEPT'] . '_access' );
	}

	/**
	 * Replaces all occurrences of {{ARRAY_KEY}} in the text with corresponding values from the array.
	 *
	 * @param array  $interpolation_array The array containing keys and values for interpolation.
	 * @param string $text_to_interpolate The text containing placeholders in {{KEY}} format.
	 *
	 * @return string The interpolated text with all placeholders replaced
	 */
	private function interpolate_array_to_text( $interpolation_array, $text_to_interpolate ) {
		// Validate inputs.
		if ( ! is_array( $interpolation_array ) || ! is_string( $text_to_interpolate ) ) {
			return $text_to_interpolate;
		}
	
		// Process each key in the interpolation array.
		foreach ( $interpolation_array as $key => $value ) {
			// Convert any non-string values to strings.
			if ( ! is_string( $value ) ) {
				// Handle arrays and objects by converting to JSON.
				if ( is_array( $value ) || is_object( $value ) ) {
					$value = wp_json_encode( $value );
				} else {
					$value = (string) $value;
				}
			}
		
			// Create the placeholder pattern with the current key.
			$placeholder = '{{' . $key . '}}';
		
			// Replace all occurrences of the placeholder with its value.
			$text_to_interpolate = str_replace( $placeholder, $value, $text_to_interpolate );
		}
	
		return $text_to_interpolate;
	}

	/**
	 * Core file editing functionality for both wp-config.php and .htaccess
	 *
	 * @param string $file_path     Path to the file to edit.
	 * @param string $code          The code block to add or remove.
	 * @param string $action        The action to perform ("add" or "remove").
	 * @param string $start_marker  The start marker string.
	 * @param string $end_marker    The end marker string.
	 * @param string $context       The filesystem context.
	 * @param bool   $create_file   Whether to create the file if it doesn't exist.
	 * @param array  $options       Additional options for specific file types.
	 * @return bool|string True on success, error message on failure
	 */
	private function edit_file( $file_path, $code, $action, $start_marker, $end_marker, $context, $create_file = false, $options = array() ) {
		global $sync_filesystem;

		// Get the file basename for error messages.
		$file_basename = basename( $file_path );

		// Validate action parameter.
		$action = 'remove' === strtolower( $action ) ? 'remove' : 'add';
	
		// Check if file exists and is readable/writable.
		if ( ! $sync_filesystem->exists( $file_path ) ) {
			// For add action, we'll create the file if requested.
			if ( 'add' === $action && $create_file ) {
				if ( false === $sync_filesystem->put_contents( $file_path, '', $context ) ) {
					return "Error: Could not create {$file_basename} at {$file_path}";
				}
			} else {
				return "Error: {$file_basename} not found at {$file_path}";
			}
		}
	
		if ( ! $sync_filesystem->is_readable( $file_path ) ) {
			return "Error: {$file_basename} is not readable";
		}
	
		if ( ! $sync_filesystem->is_writable( $file_path ) ) {
			return "Error: {$file_basename} is not writable";
		}
	
		// Read file content.
		$file_content = $sync_filesystem->get_contents( $file_path );
		if ( false === $file_content ) {
			return "Error: Failed to read {$file_basename}";
		}
	
		// Normalize the code by removing extra whitespace for comparison purposes.
		$normalized_code = preg_replace( '/\s+/', ' ', trim( $code ) );
	
		// Initialize variables for specific comparison strategies.
		$code_signature         = '';
		$code_define_matches    = array();
		$code_rules_match       = array();
		$code_extracts          = array();
		$has_rules              = false;
		$define_check_pattern   = '';
		$extracted_code_pattern = '';
	
		// Set up the appropriate comparison strategy based on file type.
		if ( isset( $options['file_type'] ) && 'php' === $options['file_type'] ) {
			// Extract actual code content between quotes for more accurate PHP code comparison.
			$extracted_code_pattern = '/[\'"]([^\'"]*)[\'"]|value:\s*(\w+)|([A-Z_]+)\s*,\s*(\w+)/';
			preg_match_all( $extracted_code_pattern, $code, $code_extracts );
		
			// Extract constant name and value separately for define() comparison.
			$define_pattern = '/define\s*\(\s*[\'"]([A-Z_]+)[\'"].*?(true|false|\d+|[\'"][^\'"]*[\'"])\s*\)/i';
			preg_match( $define_pattern, $code, $code_define_matches );
		
			// Build code signature for comparison.
			if ( ! empty( $code_extracts[0] ) ) {
				foreach ( $code_extracts[0] as $extract ) {
					$code_signature .= $extract;
				}
				$code_signature = preg_replace( '/\s+/', '', $code_signature );
			}
		} elseif ( isset( $options['file_type'] ) && 'htaccess' === $options['file_type'] ) {
			// For special htaccess rule comparison.
			$rule_pattern = '/^(?:\s*RewriteRule\s+\^\(?([^\s]+)\)?\$?\s+([^\s]+))|(?:\s*RewriteCond\s+%\{([^\}]+)\}\s+([^\s]+))/i';
			$has_rules    = preg_match_all( $rule_pattern, $code, $code_rules_match );
		}
	
		// Check for existing markers and remove both if only one exists or in wrong order.
		$start_pos = strpos( $file_content, $start_marker );
		$end_pos   = strpos( $file_content, $end_marker );
	
		// Check if markers exist but are in wrong order or only one exists.
		if ( ( false === $start_pos && false !== $end_pos ) || 
		( false !== $start_pos && false === $end_pos ) ||
		( false !== $start_pos && false !== $end_pos && $start_pos > $end_pos ) ) {
		
			// Remove both markers wherever they are found.
			$file_content = str_replace( $start_marker . "\n", '', $file_content );
			$file_content = str_replace( $end_marker . "\n", '', $file_content );
			$file_content = str_replace( $start_marker, '', $file_content );
			$file_content = str_replace( $end_marker, '', $file_content );
		}
	
		// Find and remove matching code lines (accounting for whitespace differences).
		$lines      = explode( "\n", $file_content );
		$new_lines  = array();
		$code_found = false;
	
		foreach ( $lines as $line ) {
			$skip_line = false;
		
			// Normalize this line for comparison.
			$normalized_line = preg_replace( '/\s+/', ' ', trim( $line ) );
		
			// Check if this line matches our code (ignoring whitespace differences).
			if ( $normalized_line === $normalized_code ) {
				$code_found = true;
				$skip_line  = true;
			} elseif ( isset( $options['file_type'] ) && 'php' === $options['file_type'] && ! empty( $extracted_code_pattern ) ) {
				// PHP-specific comparisons.
			
				// If basic comparison fails, try signature-based comparison.
				preg_match_all( $extracted_code_pattern, $line, $line_extracts );
				$line_signature = '';
			
				if ( ! empty( $line_extracts[0] ) ) {
					foreach ( $line_extracts[0] as $extract ) {
						$line_signature .= $extract;
					}
					$line_signature = preg_replace( '/\s+/', '', $line_signature );
				}
			
				// Special handling for define statements.
				if ( ! empty( $code_define_matches ) ) {
					preg_match( $define_pattern, $line, $line_define_matches );
				
					// If we have define matches for both the code and the current line.
					if ( ! empty( $code_define_matches ) && ! empty( $line_define_matches ) ) {
						// Compare constant name and value directly.
						if ( $code_define_matches[1] === $line_define_matches[1] ) {
							// Same constant name, compare values with normalization.
							$code_value = preg_replace( '/\s+|value:\s*/', '', $code_define_matches[2] );
							$line_value = preg_replace( '/\s+|value:\s*/', '', $line_define_matches[2] );
						
							if ( $code_value === $line_value ) {
								$code_found = true;
								$skip_line  = true;
							}
						}
					}
				}
			
				// If the core content matches (despite spacing differences), remove it.
				if ( ! $skip_line && ! empty( $line_signature ) && ! empty( $code_signature ) && $line_signature === $code_signature ) {
					$code_found = true;
					$skip_line  = true;
				}
			} elseif ( isset( $options['file_type'] ) && 'htaccess' === $options['file_type'] && $has_rules ) {
				// .htaccess-specific comparisons.
			
				// Special handling for RewriteRule and RewriteCond.
				$line_rules_match = array();
				if ( preg_match( $rule_pattern, $line, $line_rules_match ) ) {
					// Compare the key parts of rules (patterns and substitutions).
					foreach ( $code_rules_match[0] as $code_rule_index => $code_rule ) {
						$code_pattern = ! empty( $code_rules_match[1][ $code_rule_index ] ) ? 
						$code_rules_match[1][ $code_rule_index ] : 
						$code_rules_match[3][ $code_rule_index ];
					
						$code_subst = ! empty( $code_rules_match[2][ $code_rule_index ] ) ? 
						$code_rules_match[2][ $code_rule_index ] : 
						$code_rules_match[4][ $code_rule_index ];
					
						$line_pattern = ! empty( $line_rules_match[1] ) ? 
						$line_rules_match[1] : 
						$line_rules_match[3];
					
						$line_subst = ! empty( $line_rules_match[2] ) ? 
						$line_rules_match[2] : 
						$line_rules_match[4];
					
						// If both pattern and substitution match (ignoring whitespace).
						if ( trim( $code_pattern ) === trim( $line_pattern ) && 
						trim( $code_subst ) === trim( $line_subst ) ) {
							$code_found = true;
							$skip_line  = true;
							break;
						}
					}
				}
			}
		
			// Add the line to our new content if we're not skipping it.
			if ( ! $skip_line ) {
				$new_lines[] = $line;
			}
		}
	
		// Only rebuild the file if we found and removed matching code.
		if ( $code_found ) {
			$file_content = implode( "\n", $new_lines );
		}
	
		// If action is remove, just save the file without adding the code back.
		if ( 'remove' === $action ) {
			// Write the file back.
			if ( false === $sync_filesystem->put_contents( $file_path, $file_content, $context ) ) {
				return "Error: Failed to write to {$file_basename}";
			}
			return true;
		}
	
		// For "add" action, proceed to add the markers and code.
	
		// Check for existing marker block.
		$start_pos = strpos( $file_content, $start_marker );
		$end_pos   = strpos( $file_content, $end_marker );
	
		if ( false !== $start_pos && false !== $end_pos && $start_pos < $end_pos ) {
			// Markers exist and are in correct order.
		
			// Check if the code already exists inside the markers.
			$marked_block            = substr( $file_content, $start_pos, $end_pos - $start_pos );
			$normalized_marked_block = preg_replace( '/\s+/', ' ', $marked_block );
			$already_exists          = false;
		
			// Simple normalization check first.
			if ( false !== strpos( $normalized_marked_block, $normalized_code ) ) {
				$already_exists = true;
			} elseif ( isset( $options['file_type'] ) && 'php' === $options['file_type'] ) {
				// PHP-specific existence checks inside marker block.
			
				// Special check for define statements inside the marked block.
				if ( ! empty( $code_define_matches ) ) {
					$define_check_pattern = '/define\s*\(\s*[\'"]' . preg_quote( $code_define_matches[1], '/' ) . '[\'"].*?(true|false|\d+|[\'"][^\'"]*[\'"])\s*\)/i';
					if ( preg_match( $define_check_pattern, $marked_block, $marker_define_match ) ) {
						// Found the same constant being defined, check if values match.
						$code_value   = preg_replace( '/\s+|value:\s*/', '', $code_define_matches[2] );
						$marker_value = preg_replace( '/\s+|value:\s*/', '', $marker_define_match[1] );
					
						if ( $code_value === $marker_value ) {
							$already_exists = true;
						}
					}
				}
			
				// If define check didn't find it, try extract-based comparison.
				if ( ! $already_exists && ! empty( $extracted_code_pattern ) ) {
					// Extract content within quotes from the marker block for comparison.
					preg_match_all( $extracted_code_pattern, $marked_block, $block_extracts );
				
					// Iterate through each potential match in the block.
					if ( ! empty( $block_extracts[0] ) && ! empty( $code_extracts[0] ) ) {
						foreach ( $block_extracts[0] as $key => $extract ) {
							// Reconstruct a potential full line to check against our signature.
							$block_line_signature = '';
							$max_elements         = count( $code_extracts[0] );
							$block_extracts_count = count( $block_extracts[0] );
						
							for ( $i = 0; $i < $max_elements && ( $key + $i ) < $block_extracts_count; $i++ ) {
								$block_line_signature .= $block_extracts[0][ $key + $i ];
							}
							$block_line_signature = preg_replace( '/\s+/', '', $block_line_signature );
						
							if ( $block_line_signature === $code_signature ) {
								$already_exists = true;
								break;
							}
						}
					}
				}
			} elseif ( isset( $options['file_type'] ) && 'htaccess' === $options['file_type'] && $has_rules ) {
				// .htaccess-specific existence checks inside marker block.
			
				// Check for rule matches inside the marker block.
				$marked_lines = explode( "\n", $marked_block );
			
				foreach ( $marked_lines as $marked_line ) {
					$line_rules_match = array();
					if ( preg_match( $rule_pattern, $marked_line, $line_rules_match ) ) {
						// Compare the key parts of rules (patterns and substitutions).
						foreach ( $code_rules_match[0] as $code_rule_index => $code_rule ) {
							$code_pattern = ! empty( $code_rules_match[1][ $code_rule_index ] ) ? 
							$code_rules_match[1][ $code_rule_index ] : 
							$code_rules_match[3][ $code_rule_index ];
						
							$code_subst = ! empty( $code_rules_match[2][ $code_rule_index ] ) ? 
							$code_rules_match[2][ $code_rule_index ] : 
							$code_rules_match[4][ $code_rule_index ];
						
							$line_pattern = ! empty( $line_rules_match[1] ) ? 
							$line_rules_match[1] : 
							$line_rules_match[3];
						
							$line_subst = ! empty( $line_rules_match[2] ) ? 
							$line_rules_match[2] : 
							$line_rules_match[4];
						
							// If both pattern and substitution match (ignoring whitespace).
							if ( trim( $code_pattern ) === trim( $line_pattern ) && 
							trim( $code_subst ) === trim( $line_subst ) ) {
								$already_exists = true;
								break 2; // Exit both loops.
							}
						}
					}
				}
			}
		
			// Only add if code doesn't already exist in the marker block.
			if ( ! $already_exists ) {
				// Insert code before end marker.
				$before       = substr( $file_content, 0, $end_pos );
				$after        = substr( $file_content, $end_pos );
				$file_content = $before . $code . "\n" . $after;
			}
		} elseif ( isset( $options['insertion_point'] ) && 'php' === $options['insertion_point'] ) {
			// Special handling for PHP files (after <?php tag).
			$php_pos = strpos( $file_content, '<?php' );
			
			if ( false === $php_pos ) {
				return "Error: <?php tag not found in {$file_basename}";
			}
			
				// Find the position right after the <?php line.
				$line_end = strpos( $file_content, "\n", $php_pos );
			if ( false === $line_end ) {
				$line_end = strlen( $file_content );
			}
			
				$before = substr( $file_content, 0, $line_end + 1 );
				$after  = substr( $file_content, $line_end + 1 );
			
				// Add the new block with markers.
				$new_block    = $start_marker . "\n" . $code . "\n" . $end_marker . "\n";
				$file_content = $before . $new_block . $after;
		} else {
			// Default behavior for other files (append to end).
			
			// First ensure the file ends with a newline.
			if ( ! empty( $file_content ) && "\n" !== substr( $file_content, -1 ) ) {
				$file_content .= "\n";
			}
			
			// Add the new block with markers.
			$new_block     = $start_marker . "\n" . $code . "\n" . $end_marker . "\n";
			$file_content .= $new_block;
		}
	
		// Write the file back.
		if ( false === $sync_filesystem->put_contents( $file_path, $file_content, $context ) ) {
			return "Error: Failed to write to {$file_basename}";
		}
	
		return true;
	}

	/**
	 * Edit wp-config.php file to add or remove code blocks between sync markers
	 *
	 * @param string $code   The code block to add or remove.
	 * @param string $action The action to perform ("add" or "remove").
	 * @return bool|string   True on success, error message on failure
	 */
	public function edit_wp_config( $code, $action = 'add' ) {
		$config_path  = ABSPATH . 'wp-config.php';
		$start_marker = '/* Sync Edit Start */';
		$end_marker   = '/* Sync Edit End */';
		$context      = 'sync_wp_config_access';
		$options      = array(
			'file_type'       => 'php',
			'insertion_point' => 'php',
		);
	
		return $this->edit_file( $config_path, $code, $action, $start_marker, $end_marker, $context, false, $options );
	}

	/**
	 * Edit .htaccess file to add or remove code blocks between sync markers
	 *
	 * @param string $code   The code block to add or remove.
	 * @param string $action The action to perform ("add" or "remove").
	 * @param string $path   Optional custom path to .htaccess file. Default is site root.
	 * @return bool|string   True on success, error message on failure
	 */
	public function edit_htaccess( $code, $action = 'add', $path = '' ) {
		$htaccess_path = empty( $path ) ? ABSPATH . '.htaccess' : $path;
		$start_marker  = '# Sync Edit Start';
		$end_marker    = '# Sync Edit End';
		$context       = 'sync_htaccess_access';
		$options       = array(
			'file_type' => 'htaccess',
		);
	
		return $this->edit_file( $htaccess_path, $code, $action, $start_marker, $end_marker, $context, true, $options );
	}

	/**
	 * Init Admin Notices.
	 */
	public function init_admin_notices() {
		$admin_notice_callbacks = array();
		$admin_notice_callbacks = apply_filters( 'sync_add_admin_notice', $admin_notice_callbacks );
		foreach ( $admin_notice_callbacks as $admin_notice_callback ) {
			call_user_func( $admin_notice_callback );
		}
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
	public function generate_admin_notice( $message, $title = '', $status = 'success', $is_dismissible = true, $icon = false ) {

		if ( ! is_string( $message ) ) {
			throw new \InvalidArgumentException( 'generate_admin_notice(): $message must be a string.' );
		}

		$allowed = array( 'error', 'warning', 'success', 'info' );
		$status  = preg_replace( '/^notice-/', '', $status );
		if ( ! in_array( $status, $allowed, true ) ) {
			$status = 'success';
		}

		// Define color schemes for different statuses.
		$color_schemes = array(
			'success' => array(
				'icon_bg'        => '#50c878',
				'icon_color'     => '#ffffff',
				'text_color'     => '#333',
				'subtitle_color' => '#666',
				'default_icon'   => 'dashicons-yes',
			),
			'error'   => array(
				'icon_bg'        => '#dc3545',
				'icon_color'     => '#ffffff',
				'text_color'     => '#333',
				'subtitle_color' => '#666',
				'default_icon'   => 'dashicons-warning',
			),
			'warning' => array(
				'icon_bg'        => '#ffc107',
				'icon_color'     => '#000000',
				'text_color'     => '#333',
				'subtitle_color' => '#666',
				'default_icon'   => 'dashicons-info',
			),
			'info'    => array(
				'icon_bg'        => '#2196f3',
				'icon_color'     => '#ffffff',
				'text_color'     => '#333',
				'subtitle_color' => '#666',
				'default_icon'   => 'dashicons-info',
			),
		);
		$scheme        = $color_schemes[ $status ];

		// Determine icon.
		$icon_content = '';
		if ( false !== $icon ) {
			if ( true === $icon ) {
				$icon = $scheme['default_icon'];
			}
			if ( is_string( $icon ) ) {
				if ( 0 === strpos( $icon, 'dashicons-' ) ) {
					// Dashicons handling.
					$icon_content = sprintf(
						'<span class="dashicons %s" style="font-size: 0.9em; color: %s;"></span>',
						esc_attr( $icon ),
						esc_attr( $scheme['icon_color'] )
					);
				} else {
					// Text or custom icon.
					$icon_content = sprintf(
						'<span style="font-size: 1em; color: %s;">%s</span>',
						esc_attr( $scheme['icon_color'] ),
						esc_html( $icon )
					);
				}
			}
			$icon_content = '<span class="sync-admin-notice-icon" style="
         background-color:' . esc_attr( $scheme['icon_bg'] ) . ';
         color:' . esc_attr( $scheme['icon_color'] ) . ';"
>' . $icon_content . '</span>';
		}

		// Build title HTML.
		$title_html = '';
		if ( $title && is_string( $title ) ) {
			$title_html = sprintf(
				'<h1 style="margin: 0; padding: 0; font-size: 1.5em; color: %s; font-weight: bold;">%s</h1>',
				esc_attr( $scheme['text_color'] ),
				esc_html( $title )
			);
		}

		$message_html = sprintf(
			'<p style="margin: 0.5em 0 0; padding: 0; color: %s; font-size: 1.4em;">%s</p>',
			esc_attr( $scheme['subtitle_color'] ),
			wp_kses_post( $message )
		);

		$full_notice_html = sprintf(
			'<div class="sync-admin-notice">
				<div class="sync-admin-notice-item" style="margin-right: 10px;">%s</div>
				<div class="sync-admin-notice-item">
					%s
					%s
				</div>
			</div>',
			$icon_content,
			$title_html,
			$message_html
		);

		wp_admin_notice(
			$full_notice_html,
			array(
				'type'           => $status,
				'dismissible'    => (bool) $is_dismissible,
				'paragraph_wrap' => false, // Prevent additional wrapping.
			)
		);
	}


	/**
	 * Add WP menu.
	 *
	 * @param string     $menu_slug Menu slug.
	 * @param string     $menu_name Menu name.
	 * @param int        $position Menu position.
	 * @param bool|array $create_sync_menu Create sync menu.
	 * @param string     $settings_level Settings level (site, network, both).
	 *
	 * @since 1.0.0
	 */
	public function add_wp_menu( $menu_slug, $menu_name, $position = 100, $create_sync_menu = false, $settings_level = 'site' ) {

		if ( empty( $menu_slug ) || ! is_string( $menu_slug ) || strpos( $menu_slug, 'sync' ) !== false || ! preg_match( '/^[a-z][a-z0-9_-]*$/', $menu_slug ) ) {
			return;
		}

		if ( empty( $menu_name ) || ! is_string( $menu_name ) ) {
			return;
		}

		if ( ! is_numeric( $position ) || $position < 0 ) {
			return;
		}

		if ( ! in_array( $settings_level, array( 'site', 'network', 'both' ), true ) ) {
			return;
		}

		$this->menus[ $menu_slug ] = array(
			'menu_name'        => $menu_name,
			'position'         => $position,
			'create_sync_menu' => false === $create_sync_menu ? false : true,
			'settings_level'   => $settings_level,
		);

		// Check if, $create_sync_menu is array and have keys menu_name (required) which is not empty and string, and icon_url (optional, default null, else string and null can be set in array), position will always be -1 and sub_menu will always be false.
		if ( is_array( $create_sync_menu ) && isset( $create_sync_menu['menu_name'] ) && ! empty( $create_sync_menu['menu_name'] ) && is_string( $create_sync_menu['menu_name'] ) && isset( $create_sync_menu['callback'] ) && is_callable( $create_sync_menu['callback'] ) ) {
			$this->sync_menus[ $menu_slug ][ $menu_slug ] = array(
				'menu_name' => $create_sync_menu['menu_name'],
				'icon_url'  => isset( $create_sync_menu['icon_url'] ) ? $create_sync_menu['icon_url'] : null,
				'position'  => -1,
				'sub_menu'  => false,
			);

			add_filter( 'sync_register_menu_' . $menu_slug, $create_sync_menu['callback'], 10, 2 );
		}
	}

	/**
	 * Add Sync Menus.
	 *
	 * @param string      $wp_menu_slug      WP Menu slug.
	 * @param string      $menu_name         Menu name.
	 * @param callback    $settings_callback  Settings callback.
	 * @param string|bool $menu_slug         Menu slug (optional).
	 * @param string|null $icon_url          Icon URL (optional).
	 * @param int|null    $position          Menu position (optional).
	 * @param bool|array  $sub_menu_support  Whether sub-menu support is enabled (optional).
	 */
	public function add_sync_menus( $wp_menu_slug, $menu_name, $settings_callback, $menu_slug = false, $icon_url = null, $position = null, $sub_menu_support = false ) {
		// Validate inputs.
		if ( empty( $wp_menu_slug ) || ( ! is_string( $wp_menu_slug ) && isset( $this->menus[ $wp_menu_slug ] ) && $this->menus[ $wp_menu_slug ]['create_sync_menu'] ) ) {
			return;
		}

		if ( empty( $menu_name ) || ! is_string( $menu_name ) ) {
			return;
		}

		if ( false !== $menu_slug && is_string( $menu_slug ) && strpos( $menu_slug, 'sync' ) === true && ! preg_match( '/^[a-z][a-z0-9_-]*$/', $menu_slug ) ) {
			return;
		}

		if ( null !== $position && ( ! is_numeric( $position ) || 0 > $position ) ) {
			return;
		}

		// $sub_menu_support must be false or array, if array, then must have key menu_name and menu_slug, both must be string and not empty, both are required, else return early.
		if ( ! ( false === $sub_menu_support || ( is_array( $sub_menu_support ) && isset( $sub_menu_support['menu_name'] ) && ! empty( $sub_menu_support['menu_name'] ) && is_string( $sub_menu_support['menu_name'] ) && isset( $sub_menu_support['menu_slug'] ) && ! empty( $sub_menu_support['menu_slug'] ) && is_string( $sub_menu_support['menu_slug'] ) ) ) ) {
			return;
		}

		if ( ! isset( $this->sync_menus[ $wp_menu_slug ] ) ) {
			$this->sync_menus[ $wp_menu_slug ] = array();
		}

		$this->sync_menus[ $wp_menu_slug ][ $menu_slug ] = array(
			'menu_name' => $menu_name,
			'icon_url'  => $icon_url,
			'position'  => $position,
			'sub_menu'  => $sub_menu_support ? array(
				$sub_menu_support['menu_slug'] => array(
					'menu_name' => $sub_menu_support['menu_name'],
					'position'  => -1,
				),
			) : false,
		);

		if ( $sub_menu_support ) {
			add_filter( 'sync_register_menu_' . $menu_slug . '_sub_' . $sub_menu_support['menu_slug'], $settings_callback, 10, 2 );
		} else {
			add_filter( 'sync_register_menu_' . $menu_slug, $settings_callback, 10, 2 );
		}
	}

	/**
	 * Add Sync Sub Menus.
	 *
	 * @param string   $wp_menu_slug WP Menu slug.
	 * @param string   $parent_menu_slug Parent menu slug.
	 * @param callback $settings_callback Settings callback.
	 * @param string   $menu_name Menu name.
	 * @param string   $menu_slug Menu slug.
	 * @param int|null $position Menu position.
	 */
	public function add_sync_sub_menus( $wp_menu_slug, $parent_menu_slug, $settings_callback, $menu_name, $menu_slug, $position = null ) {
		// Validate inputs.
		if ( empty( $wp_menu_slug ) || ( ! is_string( $wp_menu_slug ) && isset( $this->menus[ $wp_menu_slug ] ) && ! isset( $this->sync_menus[ $wp_menu_slug ] ) ) ) {
			return;
		}

		if ( empty( $parent_menu_slug ) || ( ! is_string( $parent_menu_slug ) && ! isset( $this->sync_menus[ $wp_menu_slug ][ $parent_menu_slug ] ) ) ) {
			return;
		}

		if ( ! isset( $this->sync_menus[ $wp_menu_slug ][ $parent_menu_slug ]['sub_menu'] ) || false === $this->sync_menus[ $wp_menu_slug ][ $parent_menu_slug ]['sub_menu'] ) {
			return; // Parent menu slug does not support sub-menus.
		}

		if ( empty( $menu_name ) || ! is_string( $menu_name ) ) {
			return;
		}

		if ( false !== $menu_slug && is_string( $menu_slug ) && strpos( $menu_slug, 'sync' ) === true && ! preg_match( '/^[a-z][a-z0-9_-]*$/', $menu_slug ) ) {
			return;
		}

		if ( null !== $position && ( ! is_numeric( $position ) || 0 > $position ) ) {
			return;
		}

		$this->sync_menus[ $wp_menu_slug ][ $parent_menu_slug ]['sub_menu'][ $menu_slug ] = array(
			'menu_name' => $menu_name,
			'position'  => $position,
		);

		add_filter( 'sync_register_menu_' . $parent_menu_slug . '_sub_' . $menu_slug, $settings_callback, 10, 2 );
	}

	/**
	 * Initialize settings.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_page() {

		global $sync_ajax;

		add_menu_page( 'Sync', 'Sync', 'manage_options', 'sync', '', 'dashicons-sort', is_network_admin() ? 23 : 63 );
		$this->menus['sync'] = array(
			'menu_name'        => 'Sync',
			'position'         => -1,
			'create_sync_menu' => false,
			'settings_level'   => 'both',
		);

		/**
		 * Sync Settings Page Hook
		 */
		do_action( 'sync_add_settings_page' );

		/**
		 * Sync Settings Page Filter
		 */
		apply_filters( 'sync_settings_page', $this->menus );

		if ( $sync_ajax->is_ajax ) {
			$this->init_ajax_response();
			return;
		}

		$this->init_settings_pages();
	}

	/**
	 * Initialize Ajax response.
	 *
	 * @since 1.0.0
	 */
	private function init_ajax_response() {

		global $sync_ajax;

		// Get Action Name.
		$action_name = $sync_ajax->ajax_action_name;

		// Check if it start with 'sync_save_' and end with '_settings'.
		if ( strpos( $action_name, 'sync_save_' ) !== 0 || substr( $action_name, -strlen( '_settings' ) ) !== '_settings' ) {
			return;
		}

		// Trim the action name from the start 'sync_save_' and end '_settings'.
		$action_name = substr( $action_name, strlen( 'sync_save_' ), -strlen( '_settings' ) );

		// Divide action name in slug and parent slug.
		$action_name_parts = explode( '_sub_', $action_name );

		if ( count( $action_name_parts ) > 2 ) {
			return; // Invalid action name.
		}

		if ( count( $action_name_parts ) === 2 ) {
			$parent_slug = $action_name_parts[0];
			$slug        = $action_name_parts[1];
		} else {
			$parent_slug = '';
			$slug        = $action_name_parts[0];
		}

		$page_data = array(
			'name'    => $action_name,
			'slug'    => $slug,
			'puspose' => 'menu_submit',
		);

		// Append the parent slug if it exists.
		if ( ! empty( $parent_slug ) ) {
			$page_data['parent_slug'] = $parent_slug;
		}

		$full_slug = $parent_slug ? $parent_slug . '_' . $slug : $slug;

		// Create nonce key.
		$nonce_action = 'sync_setting_' . $full_slug;

		$filter_name = 'sync_register_menu_' . $action_name;

		$this->sync_ajax_instance                = apply_filters( $filter_name, array(), $page_data );
		$this->sync_ajax_instance['filter_name'] = $filter_name;

		sync_register_ajax_action(
			$sync_ajax->ajax_action_name,
			array( $this, 'settings_submit_handler' ),
			$nonce_action,
			'_sync_nonce',
			'in',
			true
		);
	}

	/**
	 * Settings Submit Handler.
	 *
	 * @param array $sync_req Sync request data.
	 */
	public function settings_submit_handler( $sync_req ) {

		// Grab our AJAX instance config.
		$instance = $this->sync_ajax_instance;
		$inputs   = isset( $instance['inputs'] ) ? $instance['inputs'] : array();

		// Build page-details prefix.
		$pd          = $instance['page_details'];
		$slug        = sanitize_key( $pd['slug'] );
		$parent_slug = ! empty( $pd['parent_slug'] ) ? sanitize_key( $pd['parent_slug'] ) : '';
		$prefix      = $parent_slug ? "{$parent_slug}_{$slug}" : $slug;

		// 1) Validation loop.
		foreach ( $inputs as $input ) {
			$name  = $input['name'];
			$value = isset( $sync_req['req'][ $name ] ) ? $sync_req['req'][ $name ] : null;

			// Required?
			if ( ! empty( $input['required'] ) && ( null === $value || '' === trim( $value ) ) ) {
				sync_send_json(
					array(
						'success' => false,
						// translators: %s is the name of the field that is required.
						'message' => sprintf( __( 'Field "%s" is required.', 'wisesync' ), $name ),
						'field'   => $name,
					),
					400
				);
			}

			// Regex.
			if ( ! empty( $input['regex'] ) && is_string( $input['regex'] ) ) {
				if ( ! preg_match( $input['regex'], (string) $value ) ) {
					sync_send_json(
						array(
							'success' => false,
							// translators: %s is the name of the field that is invalid.
							'message' => sprintf( __( 'Field "%s" is invalid.', 'wisesync' ), $name ),
							'field'   => $name,
						),
						400
					);
				}
			}
		}

		// 2) Save settings.
		if ( is_string( $instance['seprate'] ) && $instance['seprate'] ) {
			// Separate options per input.
			foreach ( $inputs as $input ) {
				$name        = $input['name'];
				$sync_option = $input['sync_option'];
				$raw_value   = isset( $sync_req['req'][ $name ] ) ? $sync_req['req'][ $name ] : '';

				update_option( sanitize_key( "{$instance['seprate']}_{$sync_option}" ), $raw_value );
			}
		} else {
			// One JSON-blob option.
			$data = array();
			foreach ( $inputs as $input ) {
				$name                 = $input['name'];
				$sync_option          = $input['sync_option'];
				$raw_value            = isset( $sync_req['req'][ $name ] ) ? $sync_req['req'][ $name ] : '';
				$data[ $sync_option ] = $raw_value;
			}
			$json = wp_json_encode( $data );
			update_option( sanitize_key( $prefix ), $json );
		}

		// Call the callback function if it exists.
		if ( isset( $instance['callback'] ) && is_callable( $instance['callback'] ) ) {
			$instance['prefix'] = $prefix;
			call_user_func_array( $instance['callback'], array( $sync_req, $instance ) );
		}

		// 3) Build and send response.
		$response = array(
			'action'         => $sync_req['action']['action'],
			'return_html'    => (bool) $instance['return_html'],
			'should_refresh' => (bool) $instance['should_refresh'],
			'page_details'   => $pd,
		);

		if ( $instance['return_html'] ) {
			$load_pd            = $pd;
			$load_pd['puspose'] = 'menu_load';
			$html_filter_name   = 'sync_register_menu_' . $slug;
			$response['html']   = apply_filters( $instance['filter_name'], '', $load_pd );
		}

		sync_send_json( $response );
	}


	/**
	 * Initialize settings pages.
	 *
	 * @since 1.0.0
	 */
	private function init_settings_pages() {

		foreach ( $this->menus as $menu_slug => $current_menu ) {

			if ( ! is_network_admin() && ! in_array( $current_menu['settings_level'], array( 'site', 'both' ), true ) ) {
				return; // Only show on site admin.
			} elseif ( is_network_admin() && ! in_array( $current_menu['settings_level'], array( 'network', 'both' ), true ) ) {
				return; // Only show on network admin.
			}

			if ( $current_menu['create_sync_menu'] ) {
				if ( isset( $this->sync_menus[ $menu_slug ] ) && is_array( $this->sync_menus[ $menu_slug ] ) ) {
					$has_valid_sub_menu = false;
					foreach ( $this->sync_menus[ $menu_slug ] as $sub_menu ) {
						if ( false !== $menu_slug || ( isset( $sub_menu['sub_menu'] ) && ! empty( $sub_menu['sub_menu'] ) ) ) {
							$has_valid_sub_menu = true;
							break;
						}
					}

					if ( ! $has_valid_sub_menu ) {
						continue; // Skip adding this menu if no valid sub-menu exists.
					}
				}
			}

			add_submenu_page(
				'sync',
				$current_menu['menu_name'],
				$current_menu['menu_name'],
				'manage_options',
				'sync' === $menu_slug ? 'sync' : 'sync-' . $menu_slug,
				array( $this, 'settings_page' ),
				$current_menu['position']
			);
		}
	}

	/**
	 * Process icon.
	 *
	 * @param string $icon_url    Icon URL.
	 * @param bool   $return_html Whether to return the HTML instead of echoing it.
	 *
	 * @since 1.0.0
	 */
	public function process_icon( $icon_url, $return_html = false ) {
		if ( $return_html ) {
			ob_start();
		}

		if ( $return_html ) {
			return ob_get_clean();
		}
	}

	/**
	 * Settings page.
	 *
	 * @since 1.0.0
	 */
	public function settings_page() {
		global $plugin_page;

		// Remove sync- from the plugin_page to get the actual menu slug.
		$current_settings_page = str_replace( 'sync-', '', $plugin_page );

		// Get the first menu item dynamically.
		$default_menu_slug = '';
		if ( isset( $this->sync_menus[ $current_settings_page ] ) && is_array( $this->sync_menus[ $current_settings_page ] ) ) {
			// Get the first key from the menu array.
			$keys              = array_keys( $this->sync_menus[ $current_settings_page ] );
			$default_menu_slug = reset( $keys );
		}
		?>
		<div class="sync-container">
			<!-- Main navigation with logo and mobile menu toggle -->
			<header class="sync-header">
				<div class="sync-logo">
					<span class="sync-logo-icon">S</span>
					<span class="sync-logo-text">SYNC</span>
					<span class="sync-tagline">SYNC the Web</span>
				</div>
				<button class="sync-mobile-toggle" id="sync-mobile-toggle">
					<span class="dashicons dashicons-menu-alt"></span>
				</button>
			</header>

			<?php
			if ( $this->menus[ $current_settings_page ]['create_sync_menu'] && isset( $this->sync_menus[ $current_settings_page ] ) && is_array( $this->sync_menus[ $current_settings_page ] ) ) {
				?>
				<!-- Side navigation -->
				<nav class="sync-sidebar" id="sync-sidebar">
					<ul class="sync-menu">
						<?php
						$current_sync_menu = $this->sync_menus[ $current_settings_page ];
						foreach ( $current_sync_menu as $menu_slug => $current_menu ) {
							?>
							<li class="sync-menu-item <?php echo esc_attr( $menu_slug === $default_menu_slug ? 'sync-active' : '' ); ?>">
								<a href="#<?php echo esc_attr( 'sync-' . $menu_slug ); ?>" class="sync-menu-link" data-slug="<?php echo esc_attr( $menu_slug ); ?>">
									<?php $this->process_icon( $current_menu['icon_url'] ); ?>
									<span class="sync-menu-text"><?php echo esc_html( $current_menu['menu_name'] ); ?></span>
								</a>
								<?php
								if ( isset( $current_menu['sub_menu'] ) && is_array( $current_menu['sub_menu'] ) && ! empty( $current_menu['sub_menu'] ) ) {
									?>
									<ul class="sync-submenu">
										<?php
										foreach ( $current_menu['sub_menu'] as $sub_menu_slug => $sub_menu ) {
											?>
											<li class="sync-submenu-item">
												<a href="#<?php echo esc_attr( 'sync-' . $menu_slug . '-' . $sub_menu_slug ); ?>" class="sync-submenu-link" data-parent="<?php echo esc_attr( $menu_slug ); ?>" data-slug="<?php echo esc_attr( $sub_menu_slug ); ?>"><?php echo esc_html( $sub_menu['menu_name'] ); ?></a>
											</li>
											<?php
										}
										?>
									</ul>
									<?php
								}
								?>
							</li>
							<?php
						}
						?>
					</ul>
				</nav>

				<?php
			}
			?>

			<!-- Main content area - Dynamic -->
			<main class="sync-content">
				<div class="sync-content-header">
					<h1 class="sync-page-title">
						<?php
						// Set the default page title based on the first menu.
						if ( ! empty( $default_menu_slug ) && isset( $current_sync_menu[ $default_menu_slug ]['menu_name'] ) ) {
							echo esc_html( $current_sync_menu[ $default_menu_slug ]['menu_name'] );
						} else {
							echo 'Dashboard';
						}
						?>
					</h1>
				</div>

				<!-- Dynamic content container - Now empty to be filled by JS -->
				<div id="sync-dynamic-content" class="sync-dynamic-content"></div>
			</main>
		</div>

		<!-- Hidden templates for ALL menu and submenu pages -->
		<div id="sync-page-templates" style="display: none;">
			<?php
			// Generate hidden templates for ALL menu pages, including the default/first one.
			if ( isset( $this->sync_menus[ $current_settings_page ] ) && is_array( $this->sync_menus[ $current_settings_page ] ) ) {
				$current_sync_menu = $this->sync_menus[ $current_settings_page ];

				foreach ( $current_sync_menu as $menu_slug => $current_menu ) {
					$page_data = array(
						'name'    => $current_menu['menu_name'],
						'slug'    => $menu_slug,
						'puspose' => 'menu_load',
					);

					// Apply filter for this menu page.
					$menu_content = apply_filters(
						"sync_register_menu_{$menu_slug}",
						'', // Empty string as first parameter.
						$page_data // Page data as second parameter.
					);

					if ( ! empty( $menu_content ) ) {
						?>
						<template id="sync-page-<?php echo esc_attr( $menu_slug ); ?>">
							<?php $this->sanitize_form_output( $menu_content ); ?>
						</template>
						<?php
					}

					// Process submenu pages.
					if ( isset( $current_menu['sub_menu'] ) && is_array( $current_menu['sub_menu'] ) && ! empty( $current_menu['sub_menu'] ) ) {
						foreach ( $current_menu['sub_menu'] as $sub_menu_slug => $sub_menu ) {
							$sub_page_data = array(
								'name'        => $sub_menu['menu_name'],
								'parent_slug' => $menu_slug,
								'slug'        => $sub_menu_slug,
								'puspose'     => 'menu_load',
							);

							// Apply filter for this submenu page.
							$submenu_content = apply_filters(
								"sync_register_menu_{$menu_slug}_sub_{$sub_menu_slug}",
								'', // Empty string as first parameter.
								$sub_page_data // Page data as second parameter.
							);

							if ( ! empty( $submenu_content ) ) {
								?>
								<template id="sync-subpage-<?php echo esc_attr( $menu_slug ); ?>-<?php echo esc_attr( $sub_menu_slug ); ?>">
									<?php $this->sanitize_form_output( $submenu_content ); ?>
								</template>
								<?php
							}
						}
					}
				}
			}
			?>
		</div>

		<?php
	}

	/**
	 * Initialize WordPress Dashboard Wigget.
	 */
	public function init_widgets() {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! isset( $this->sync_widgets ) || empty( $this->sync_widgets ) ) {
			return;
		}

		foreach ( $this->sync_widgets as $widget_slug => $widget_data ) {
			// Register the widget.
			wp_add_dashboard_widget( $widget_slug, $widget_data['name'], $widget_data['callback'] );
		}
	}

	/**
	 * Register Widget Settings.
	 *
	 * @param string $widget_slug Widget slug.
	 * @param string $widget_name Widget name.
	 * @param string $widget_callback Widget callback.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function register_widget_settings( $widget_slug, $widget_name, $widget_callback ) {

		if ( empty( $widget_slug ) || ! is_string( $widget_slug ) || ! preg_match( '/^[a-z][a-z0-9_-]*$/', $widget_slug ) ) {
			return false;
		}

		if ( empty( $widget_name ) || ! is_string( $widget_name ) ) {
			return false;
		}

		if ( empty( $widget_callback ) || ! is_callable( $widget_callback ) ) {
			return false;
		}

		$this->sync_widgets[ $widget_slug ] = array(
			'name'     => $widget_name,
			'callback' => $widget_callback,
		);

		return true;
	}

	/**
	 * No form settings.
	 *
	 * @param array $settings_array Settings array structure.
	 * @param bool  $return_html    Whether to return the HTML instead of echoing it.
	 *
	 * @return string|array HTML for the settings page or an array of settings submit.
	 */
	public function create_widget_settings( $settings_array, $return_html = false ) {
		if ( isset( $settings_array['html'] ) && is_array( $settings_array['html'] ) ) {
			// Start output buffering to collect HTML.
			if ( $return_html ) {
				ob_start();
			}

			// Generate a container for the widget settings.
			?>
			<div class="sync-widget-container">
				<?php $this->generate_settings_html( $settings_array['html'] ); ?>
			</div>
			<?php

			// Return the HTML if requested.
			if ( $return_html ) {
				return ob_get_clean();
			}
		}
	}

	/**
	 * Create a settings page with a single form that submits all settings at once via Ajax
	 * 
	 * @param array  $page_details       Page information (slug, name, parent_slug).
	 * @param array  $settings_array     Settings array structure.
	 * @param string $submit_button_text Text for the submit button.
	 * @param bool   $refresh            Whether to refresh the page after successful Ajax.
	 * 
	 * @return string|array HTML for the settings page or an array of settings submit.
	 */
	public function create_single_ajax_settings_page( $page_details, $settings_array, $submit_button_text = 'Save Changes', $refresh = false ) {

		// Validate page details.
		$slug          = isset( $page_details['slug'] ) ? sanitize_title( $page_details['slug'] ) : 'sync';
		$name          = isset( $page_details['name'] ) ? sanitize_text_field( $page_details['name'] ) : 'Sync';
		$parent_slug   = isset( $page_details['parent_slug'] ) ? sanitize_title( $page_details['parent_slug'] ) : '';
		$full_slug     = $parent_slug ? $parent_slug . '_' . $slug : $slug;
		$full_slug_adv = $parent_slug ? $parent_slug . '_sub_' . $slug : $slug;

		if ( isset( $page_details['puspose'] ) && 'menu_load' === $page_details['puspose'] ) {

			// Create nonce key.
			$nonce_action = 'sync_setting_' . $full_slug;
			$nonce        = wp_create_nonce( $nonce_action );

			// Get saved values, if present and replace the default values.
			$settings_array = $this->get_settings_values( $settings_array );

			// Start output buffering to collect HTML.
			ob_start();

			// Begin container.
			?>
			<div class="sync-settings-container sync-single-form" data-refresh="<?php echo esc_attr( $refresh ? 'true' : 'false' ); ?>">

				<form class="sync-settings-form" id="sync-form-<?php echo esc_attr( $slug ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>">

					<input type="hidden" name="_sync_nonce" value="<?php echo esc_attr( $nonce ); ?>">
					<input type="hidden" name="action" value="sync_save_<?php echo esc_attr( $full_slug_adv ); ?>_settings">
					<input type="hidden" name="sync_form_type" value="single">

					<?php $this->generate_settings_html( $settings_array['html'] ); ?>

					<div class="sync-form-footer">
						<button type="button" class="sync-button sync-primary-button sync-submit-button" disabled>
							<span class="dashicons dashicons-saved"></span><?php echo esc_html( $submit_button_text ); ?>
						</button>
						<div class="sync-form-message"></div>
					</div>
				</form>
			</div>
			<?php

			return ob_get_clean();
		} elseif ( isset( $page_details['puspose'] ) && 'menu_submit' === $page_details['puspose'] ) {

			if ( empty( $settings_array ) || ! is_array( $settings_array ) || ! $this->validate_input_sync_options( $settings_array ) ) {
				sync_send_json(
					array(
						'status'  => 'error',
						'message' => __( 'Invalid settings array.', 'wisesync' ),
					)
				);
			}

			return $this->generate_settings_submit_array( $settings_array, $page_details );
		}
	}

	/**
	 * Create a settings page where each setting is saved individually via Ajax upon change
	 * 
	 * @param array $page_details   Page information (slug, name, parent_slug).
	 * @param array $settings_array Settings array structure.
	 * 
	 * @return string|array HTML for the settings page or an array of settings submit.
	 */
	public function create_each_ajax_settings_page( $page_details, $settings_array ) {

		// Validate page details.
		$slug          = isset( $page_details['slug'] ) ? sanitize_title( $page_details['slug'] ) : 'sync-settings';
		$name          = isset( $page_details['name'] ) ? sanitize_text_field( $page_details['name'] ) : 'Settings';
		$parent_slug   = isset( $page_details['parent_slug'] ) ? sanitize_title( $page_details['parent_slug'] ) : '';
		$full_slug     = $parent_slug ? $parent_slug . '_' . $slug : $slug;
		$full_slug_adv = $parent_slug ? $parent_slug . '_sub_' . $slug : $slug;

		if ( isset( $page_details['puspose'] ) && 'menu_load' === $page_details['puspose'] ) {
			// Create nonce key.
			$nonce_action = 'sync_setting_' . $full_slug;
			$nonce        = wp_create_nonce( $nonce_action );

			// Get saved values, if present and replace the default values.
			$settings_array = $this->get_settings_values( $settings_array );

			// Start output buffering to collect HTML.
			ob_start();

			// Begin container.
			?>
			<div class="sync-settings-container sync-each-setting" data-slug="<?php echo esc_attr( $slug ); ?>">

				<input type="hidden" id="sync-nonce-<?php echo esc_attr( $slug ); ?>" name="_sync_nonce" value="<?php echo esc_attr( $nonce ); ?>">
				<input type="hidden" id="sync-action" name="action" value="sync_save_<?php echo esc_attr( $full_slug_adv ); ?>_settings">
				<input type="hidden" name="sync_form_type" value="ajax">

				<?php $this->generate_settings_html( $settings_array, true ); ?>
				<div class="sync-settings-message-area"></div>
			</div>

			<?php
			return ob_get_clean();
		} elseif ( isset( $page_details['puspose'] ) && 'menu_submit' === $page_details['puspose'] ) {

			if ( empty( $settings_array ) || ! is_array( $settings_array ) || ! $this->validate_input_sync_options( $settings_array ) ) {
				sync_send_json(
					array(
						'status'  => 'error',
						'message' => __( 'Invalid settings array.', 'wisesync' ),
					)
				);
			}

			return $this->generate_settings_submit_array( $settings_array, $page_details );
		}
	}

	/**
	 * Get settings values.
	 *
	 * If Values are present in the database, replace the default values with the saved values.
	 *
	 * @param array $settings_array Settings array structure.
	 *
	 * @return array Updated Settings values Array.
	 */
	private function get_settings_values( $settings_array ) {
		// No settings array, return empty array.
		if ( empty( $settings_array ) || ! is_array( $settings_array ) ) {
			return array();
		}

		// Get submit configuration if available.
		$seprate = false;
		if ( isset( $settings_array['submit']['seprate'] ) ) {
			$seprate = $settings_array['submit']['seprate'];
		}

		// Process the HTML input settings.
		if ( isset( $settings_array['html'] ) && is_array( $settings_array['html'] ) ) {
			$this->process_settings_values( $settings_array['html'], $seprate );
		}

		return $settings_array;
	}

	/**
	 * Process settings values recursively.
	 *
	 * @param array       $settings Reference to settings array to update.
	 * @param string|bool $seprate  Whether settings are stored separately.
	 */
	private function process_settings_values( &$settings, $seprate ) {
		foreach ( $settings as $type => &$value ) {
			if ( is_array( $value ) ) {
				// If this is an input field, update its value from the database.
				if ( is_string( $type ) && 0 === strpos( $type, 'input_' ) ) {
					if ( isset( $value['name'] ) && isset( $value['sync_option'] ) ) {
						$option_name = $value['sync_option'];
						$db_value    = null;

						if ( is_string( $seprate ) && $seprate ) {
							// Each input stored as separate option.
							$db_value = get_option( sanitize_key( "{$seprate}_{$option_name}" ) );
							if ( null !== $db_value ) {
								$value['value'] = $db_value;
							}
						} else {
							// All inputs stored in a single JSON-encoded option.
							$parent_slug = isset( $value['parent_slug'] ) ? $value['parent_slug'] . '_' : '';
							$slug        = isset( $value['slug'] ) ? $value['slug'] : '';
							$full_slug   = $parent_slug . $slug;

							// If neither is set, try to determine from context.
							if ( empty( $full_slug ) ) {
								$full_slug = sanitize_key( str_replace( 'input_', '', $type ) );
							}

							$json_data = get_option( sanitize_key( $full_slug ) );
							if ( $json_data ) {
								$data = json_decode( $json_data, true );
								if ( is_array( $data ) && isset( $data[ $option_name ] ) ) {
									$value['value'] = $data[ $option_name ];
								}
							}
						}
					}
				}

				// Process any nested fields or containers.
				$this->process_settings_values( $value, $seprate );
			}
		}
	}

	/**
	 * Generate settings submit array.
	 * 
	 * @param array $settings_array Settings array structure.
	 * @param array $page_details   Page information (slug, name, parent_slug).
	 * 
	 * @return array Settings submit array.
	 */
	private function generate_settings_submit_array( $settings_array, $page_details ) {
		$inputs = array();

		// Only walk through the design/html portion.
		$design = isset( $settings_array['html'] ) && is_array( $settings_array['html'] )
			? $settings_array['html']
			: $settings_array;

		/**
		 * Recursively extract inputs from the design array
		 *
		 * @param array $data The data to extract inputs from.
		 *
		 * @uses $extract_inputs, $inputs
		 *
		 * @return void
		 */
		$extract_inputs = function ( $data ) use ( &$extract_inputs, &$inputs ) {
			foreach ( $data as $key => $value ) {
				if ( is_array( $value ) ) {
					if ( is_string( $key ) && 0 === strpos( $key, 'input_' ) ) {
						$entry = array();

						if ( isset( $value['name'] ) && is_string( $value['name'] ) ) {
							$entry['name'] = sanitize_key( $value['name'] );
						}
						if ( isset( $value['sync_option'] ) && is_string( $value['sync_option'] ) ) {
							$entry['sync_option'] = sanitize_key( $value['sync_option'] );
						}
						if ( isset( $value['regex'] ) && is_string( $value['regex'] ) ) {
							$entry['regex'] = $value['regex'];
						}
						if ( isset( $value['required'] ) && is_bool( $value['required'] ) ) {
							$entry['required'] = $value['required'];
						}
						if ( isset( $value['on_condition'] ) && is_string( $value['on_condition'] ) ) {
							$entry['on_condition'] = sanitize_key( $value['on_condition'] );
						}

						if ( ! empty( $entry ) ) {
							$inputs[] = $entry;
						}
					}

					// Recurse.
					$extract_inputs( $value );
				}
			}
		};

		$extract_inputs( $design );

		// check if callback is set in submit and is callable.
		if ( isset( $settings_array['submit']['callback'] ) && is_callable( $settings_array['submit']['callback'] ) ) {
			$settings_array['submit']['callback']( $inputs );
		} else {
			$settings_array['submit']['callback'] = null;
		}

		// Build final submit array.
		$submit                 = $settings_array['submit'];
		$submit['inputs']       = $inputs;
		$submit['page_details'] = $page_details;
		return $submit;
	}

	/**
	 * Generate HTML from settings array
	 * 
	 * @param array $settings_array Settings structure.
	 * @param bool  $auto_save      Whether to enable auto-save for inputs.
	 * @param bool  $return_html    Whether to return the HTML instead of echoing it.
	 *
	 * @return string|void Generated HTML
	 */
	public function generate_settings_html( $settings_array, $auto_save = false, $return_html = false ) {

		if ( $return_html ) {
			ob_start();
		}

		foreach ( $settings_array as $type => $settings ) {

			// Prepare custom attributes.
			$custom_class    = is_array( $settings ) && ! empty( $settings['class'] ) ? ' ' . esc_attr( $settings['class'] ) : '';
			$custom_style    = is_array( $settings ) && ! empty( $settings['style'] ) ? ' style="' . esc_attr( $settings['style'] ) . '"' : '';
			$conditional_att = is_array( $settings ) && ! empty( $settings['on_condition'] ) ? ' data-conditional-target="' . esc_attr( $settings['on_condition'] ) . '"' : '';

			switch ( $type ) {

				case 'flex':
					$this->generate_flex_container( $settings, $auto_save );
					break;

				case 'p':
					$text = is_array( $settings ) && isset( $settings['text'] ) ? $settings['text'] : $settings;
					?>
					<p class="sync-text<?php echo esc_attr( $custom_class ); ?>" <?php echo wp_kses_data( $custom_style . $conditional_att ); ?>>
						<?php echo esc_html( $text ); ?>
					</p>
					<?php
					break;

				case 'h1':
				case 'h2':
				case 'h3':
				case 'h4':
				case 'h5':
				case 'h6':
					$text = is_array( $settings ) && isset( $settings['text'] ) ? $settings['text'] : $settings;
					?>
					<<?php echo esc_attr( $type ); ?> class="sync-heading sync-<?php echo esc_attr( $type ); ?><?php echo esc_attr( $custom_class ); ?>" <?php echo wp_kses_data( $custom_style . $conditional_att ); ?>>
						<?php echo esc_html( $text ); ?>
					</<?php echo esc_attr( $type ); ?>>
					<?php
					break;

				case 'span':
					$text = is_array( $settings ) && isset( $settings['text'] ) ? $settings['text'] : $settings;
					?>
					<span class="sync-span<?php echo esc_attr( $custom_class ); ?>" <?php echo wp_kses_data( $custom_style . $conditional_att ); ?>>
						<?php echo esc_html( $text ); ?>
					</span>
					<?php
					break;

				case 'icon':
					$icon = is_array( $settings ) && isset( $settings['icon'] ) ? $settings['icon'] : $settings;
					?>
					<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?><?php echo esc_attr( $custom_class ); ?>" <?php echo wp_kses_data( $custom_style . $conditional_att ); ?>></span>
					<?php
					break;

				case 'break':
					$count = isset( $settings['count'] ) ? intval( $settings['count'] ) : 1;
					for ( $i = 0; $i < $count; $i++ ) {
						?>
						<br class="<?php echo esc_attr( $custom_class ); ?>" <?php echo wp_kses_data( $custom_style . $conditional_att ); ?>>
						<?php
					}
					break;

				default:
					if ( strpos( $type, 'input_' ) === 0 ) {
						$this->generate_input( substr( $type, 6 ), $settings, $auto_save );
					}
					break;
			}
		}

		if ( $return_html ) {
			return ob_get_clean();
		}
	}

	/**
	 * Generate a flex container
	 * 
	 * @param array $settings    Flex container settings.
	 * @param bool  $auto_save   Whether to enable auto-save for inputs.
	 * @param bool  $return_html Whether to return the HTML instead of echoing it.
	 * 
	 * @return string|void Generated HTML
	 */
	public function generate_flex_container( $settings, $auto_save = false, $return_html = false ) {
		// Default flex settings.
		$direction     = isset( $settings['direction'] ) ? $settings['direction'] : 'column';
		$align_items   = isset( $settings['align']['item'] ) ? $settings['align']['item'] : 'stretch';
		$align_content = isset( $settings['align']['content'] ) ? $settings['align']['content'] : 'flex-start';

		// Custom class and conditional.
		$custom_class    = ! empty( $settings['class'] ) ? ' ' . esc_attr( $settings['class'] ) : '';
		$conditional_att = ! empty( $settings['on_condition'] ) ? ' data-conditional-target="' . esc_attr( $settings['on_condition'] ) . '"' : '';

		// Build style attribute.
		$style_string = 'flex-direction:' . esc_attr( $direction ) . '; align-items:' . esc_attr( $align_items ) . '; justify-content:' . esc_attr( $align_content ) . ';';
		if ( ! empty( $settings['style'] ) ) {
			$style_string .= ' ' . esc_attr( $settings['style'] );
		}
		$custom_style = ' style="' . esc_attr( $style_string ) . '"';

		if ( $return_html ) {
			ob_start();
		}
		?>
		<div class="sync-flex<?php echo esc_attr( $custom_class ); ?>" <?php echo wp_kses_data( $custom_style . $conditional_att ); ?>>
			<?php
			if ( isset( $settings['content'] ) && is_array( $settings['content'] ) ) {
				$this->generate_settings_html( $settings['content'], $auto_save );
			}
			?>
		</div>
		<?php
		if ( $return_html ) {
			return ob_get_clean();
		}
	}

	/**
	 * Generate different input types
	 * 
	 * @param string $type        Input type (text, textarea, radio, etc.).
	 * @param array  $settings    Input settings.
	 * @param bool   $auto_save   Whether to enable auto-save.
	 * @param bool   $return_html Whether to return the HTML instead of echoing it.
	 * 
	 * @return string|void Generated HTML
	 */
	public function generate_input( $type, $settings, $auto_save = false, $return_html = false ) {
		// Common attributes.
		$name        = isset( $settings['name'] ) ? $settings['name'] : '';
		$value       = isset( $settings['value'] ) ? $settings['value'] : '';
		$placeholder = isset( $settings['place_holder'] ) ? $settings['place_holder'] : '';
		$label       = isset( $settings['label'] ) ? $settings['label'] : '';
		$required    = ! empty( $settings['required'] ) ? ' required' : '';
		$description = isset( $settings['description'] ) ? $settings['description'] : '';

		// Wrapper custom attributes.
		$wrapper_class = 'sync-input-wrapper sync-' . esc_attr( $type ) . '-wrapper';
		if ( ! empty( $settings['class'] ) ) {
			$wrapper_class .= ' ' . esc_attr( $settings['class'] );
		}
		$conditional_att = ! empty( $settings['on_condition'] ) ? ' data-conditional-target="' . esc_attr( $settings['on_condition'] ) . '"' : '';
		$style_attr      = ! empty( $settings['style'] ) ? ' style="' . esc_attr( $settings['style'] ) . '"' : '';

		if ( $return_html ) {
			ob_start();
		}
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>" <?php echo wp_kses_data( $style_attr . $conditional_att ); ?>>

			<?php if ( ! empty( $label ) ) : ?>
				<label for="sync-field-<?php echo esc_attr( $name ); ?>" class="sync-input-label"><?php echo esc_html( $label ); ?></label>
			<?php endif; ?>

			<?php
			switch ( $type ) :

				case 'text':
					?>
					<input
						type="text"
						id="sync-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						class="sync-input sync-text-input" <?php echo esc_attr( $required ); ?>
						data-default-value="<?php echo esc_attr( $value ); ?>"
						<?php
						if ( $auto_save ) :
							?>
						data-autosave="true" <?php endif; ?>
						<?php
						if ( isset( $settings['regex'] ) ) :
							?>
						data-regex-match="<?php echo esc_attr( $settings['regex'] ); ?>" <?php endif; ?>>
					<?php
					break;

				case 'textarea':
					?>
					<textarea
						id="sync-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						class="sync-input sync-textarea" <?php echo esc_attr( $required ); ?>
						data-default-value="<?php echo esc_attr( $value ); ?>"
						<?php
						if ( $auto_save ) :
							?>
						data-autosave="true" <?php endif; ?>
						<?php
						if ( isset( $settings['regex'] ) ) :
							?>
						data-regex-match="<?php echo esc_attr( $settings['regex'] ); ?>" <?php endif; ?>><?php echo esc_textarea( $value ); ?></textarea>
					<?php
					break;

				case 'radio':
					if ( ! empty( $settings['options'] ) && is_array( $settings['options'] ) ) :
						?>
						<div class="sync-radio-group">
							<?php
							foreach ( $settings['options'] as $option ) :
								$opt_value = is_array( $option ) ? $option['value'] : $option;
								$opt_label = is_array( $option ) ? $option['label'] : $option;
								$checked   = ( $value === $opt_value ) ? ' checked' : '';
								?>
								<label class="sync-radio-label">
									<input
										type="radio"
										name="<?php echo esc_attr( $name ); ?>"
										value="<?php echo esc_attr( $opt_value ); ?>" <?php echo esc_attr( $checked ); ?>
										data-default-value="<?php echo esc_attr( $value ); ?>"
										<?php
										if ( $auto_save ) :
											?>
										data-autosave="true" <?php endif; ?>
										<?php
										if ( isset( $settings['regex'] ) ) :
											?>
										data-regex-match="<?php echo esc_attr( $settings['regex'] ); ?>" <?php endif; ?>>
									<span class="sync-radio-text"><?php echo esc_html( $opt_label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
						<?php
					endif;
					break;

				case 'toggle':
					$checked = $value ? ' checked' : '';
					?>
					<label class="sync-toggle">
						<input
							type="checkbox"
							id="sync-field-<?php echo esc_attr( $name ); ?>"
							name="<?php echo esc_attr( $name ); ?>"
							value="1" <?php echo esc_attr( $checked ); ?>
							data-default-value="<?php echo esc_attr( $value ); ?>"
							<?php
							if ( $auto_save ) :
								?>
							data-autosave="true" <?php endif; ?>>
						<span class="sync-toggle-slider"></span>
					</label>
					<?php
					break;

				case 'checkbox':
					$checked = $value ? ' checked' : '';
					?>
					<label class="sync-checkbox-label">
						<input
							type="checkbox"
							id="sync-field-<?php echo esc_attr( $name ); ?>"
							name="<?php echo esc_attr( $name ); ?>"
							value="1" <?php echo esc_attr( $checked ); ?>
							data-default-value="<?php echo esc_attr( $value ); ?>"
							<?php
							if ( $auto_save ) :
								?>
							data-autosave="true" <?php endif; ?>>
						<span class="sync-checkbox-text"><?php echo esc_html( $label ); ?></span>
					</label>
					<?php
					break;

				case 'dropdown':
					?>
					<select
						id="sync-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						class="sync-dropdown" <?php echo esc_attr( $required ); ?>
						data-default-value="<?php echo esc_attr( $value ); ?>"
						<?php
						if ( $auto_save ) :
							?>
						data-autosave="true" <?php endif; ?>>
						<?php if ( $placeholder ) : ?>
							<option value="" disabled selected><?php echo esc_html( $placeholder ); ?></option>
						<?php endif; ?>
						<?php
						if ( ! empty( $settings['options'] ) && is_array( $settings['options'] ) ) :
							foreach ( $settings['options'] as $option ) :
								$opt_value = is_array( $option ) ? $option['value'] : $option;
								$opt_label = is_array( $option ) ? $option['label'] : $option;
								$selected  = ( $value === $opt_value ) ? ' selected' : '';
								?>
								<option value="<?php echo esc_attr( $opt_value ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $opt_label ); ?></option>
								<?php
							endforeach;
						endif;
						?>
					</select>
					<?php
					break;

				case 'date':
				case 'time':
				case 'datetime':
					$input_type = 'datetime' === $type ? 'datetime-local' : $type;
					?>
					<input
						type="<?php echo esc_attr( $input_type ); ?>"
						id="sync-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						class="sync-input sync-<?php echo esc_attr( $type ); ?>-input" <?php echo esc_attr( $required ); ?>
						data-default-value="<?php echo esc_attr( $value ); ?>"
						<?php
						if ( $auto_save ) :
							?>
						data-autosave="true" <?php endif; ?>>
					<?php
					break;

				case 'number':
					$min  = isset( $settings['min'] ) ? ' min="' . esc_attr( $settings['min'] ) . '"' : '';
					$max  = isset( $settings['max'] ) ? ' max="' . esc_attr( $settings['max'] ) . '"' : '';
					$step = isset( $settings['step'] ) ? ' step="' . esc_attr( $settings['step'] ) . '"' : '';
					?>
					<input
						type="number"
						id="sync-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>" <?php echo wp_kses_data( $min . $max . $step ); ?>
						class="sync-input sync-number-input" <?php echo esc_attr( $required ); ?>
						data-default-value="<?php echo esc_attr( $value ); ?>"
						<?php
						if ( $auto_save ) :
							?>
						data-autosave="true" <?php endif; ?>>
					<?php
					break;

				case 'password':
					?>
					<input
						type="password"
						id="sync-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						class="sync-input sync-password-input" <?php echo esc_attr( $required ); ?>
						data-default-value="<?php echo esc_attr( $value ); ?>"
						<?php
						if ( $auto_save ) :
							?>
						data-autosave="true" <?php endif; ?>>
					<?php
					break;

				case 'email':
					?>
					<input
						type="email"
						id="sync-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						class="sync-input sync-email-input" <?php echo esc_attr( $required ); ?>
						data-default-value="<?php echo esc_attr( $value ); ?>"
						<?php
						if ( $auto_save ) :
							?>
						data-autosave="true" <?php endif; ?>>
					<?php
					break;

				case 'url':
					?>
					<input
						type="url"
						id="sync-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						class="sync-input sync-url-input" <?php echo esc_attr( $required ); ?>
						data-default-value="<?php echo esc_attr( $value ); ?>"
						<?php
						if ( $auto_save ) :
							?>
						data-autosave="true" <?php endif; ?>>
					<?php
					break;

				case 'color':
					?>
					<input
						type="color"
						id="sync-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						class="sync-input sync-color-input" <?php echo esc_attr( $required ); ?>
						data-default-value="<?php echo esc_attr( $value ); ?>"
						<?php
						if ( $auto_save ) :
							?>
						data-autosave="true" <?php endif; ?>>
					<?php
					break;

				case 'range':
					$min  = isset( $settings['min'] ) ? ' min="' . esc_attr( $settings['min'] ) . '"' : '';
					$max  = isset( $settings['max'] ) ? ' max="' . esc_attr( $settings['max'] ) . '"' : '';
					$step = isset( $settings['step'] ) ? ' step="' . esc_attr( $settings['step'] ) . '"' : '';
					?>
					<input
						type="range"
						id="sync-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>" <?php echo wp_kses_data( $min . $max . $step ); ?>
						class="sync-input sync-range-input" <?php echo esc_attr( $required ); ?>
						data-default-value="<?php echo esc_attr( $value ); ?>"
						<?php
						if ( $auto_save ) :
							?>
						data-autosave="true" <?php endif; ?>>
					<span class="sync-range-value"><?php echo esc_html( $value ); ?></span>
					<?php
					break;

				case 'button':
					$button_text = isset( $settings['text'] ) ? $settings['text'] : 'Button';
					$button_type = isset( $settings['button_type'] ) ? $settings['button_type'] : 'button';
					$button_cls  = isset( $settings['class'] ) ? esc_attr( $settings['class'] ) : 'sync-button';
					$icon_html   = isset( $settings['icon'] ) ? '<span class="dashicons dashicons-' . esc_attr( $settings['icon'] ) . '"></span>' : '';
					?>
					<button
						type="<?php echo esc_attr( $button_type ); ?>"
						id="sync-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						class="<?php echo esc_attr( $button_cls ); ?>"
						<?php
						if ( $auto_save ) :
							?>
						data-autosave="true" <?php endif; ?>>
						<?php echo wp_kses_post( $icon_html . esc_html( $button_text ) ); ?>
					</button>
					<?php
					break;

				case 'data':
					?>
					<div class="sync-data-input-container" data-name="<?php echo esc_attr( $name ); ?>">
						<table class="sync-data-table">
							<thead>
								<tr>
									<th>Key</th>
									<th>Value</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php
								if ( is_array( $value ) ) :
									foreach ( $value as $k => $v ) :
										?>
										<tr class="sync-data-row">
											<td><input type="text" class="sync-data-key" value="<?php echo esc_attr( $k ); ?>"></td>
											<td><input type="text" class="sync-data-value" value="<?php echo esc_attr( $v ); ?>"></td>
											<td><button type="button" class="sync-data-remove"><span class="dashicons dashicons-trash"></span></button></td>
										</tr>
										<?php
									endforeach;
								endif;
								?>
								<tr class="sync-data-row">
									<td><input type="text" class="sync-data-key" placeholder="Key"></td>
									<td><input type="text" class="sync-data-value" placeholder="Value"></td>
									<td><button type="button" class="sync-data-remove"><span class="dashicons dashicons-trash"></span></button></td>
								</tr>
							</tbody>
						</table>
						<input
							type="hidden"
							id="sync-field-<?php echo esc_attr( $name ); ?>"
							name="<?php echo esc_attr( $name ); ?>"
							value="<?php echo esc_attr( wp_json_encode( $value ) ); ?>"
							data-default-value="<?php echo esc_attr( wp_json_encode( $value ) ); ?>"
							<?php
							if ( $auto_save ) :
								?>
							data-autosave="true" <?php endif; ?>>
						<button type="button" class="sync-button sync-data-add"><span class="dashicons dashicons-plus"></span> Add New Entry</button>
					</div>
					<?php
					break;

				case 'file':
					?>
					<?php
					$file_url = is_array( $value ) && ! empty( $value['url'] ) ? $value['url'] : '';
					$file_id  = is_array( $value ) && ! empty( $value['id'] ) ? $value['id'] : '';
					?>
					<div class="sync-file-upload-container">
						<input type="text" id="sync-field-<?php echo esc_attr( $name ); ?>-url" class="sync-file-url" value="<?php echo esc_attr( $file_url ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" readonly>
						<input
							type="hidden"
							id="sync-field-<?php echo esc_attr( $name ); ?>"
							name="<?php echo esc_attr( $name ); ?>"
							value="<?php echo esc_attr( $file_id ); ?>"
							data-default-value="<?php echo esc_attr( $file_id ); ?>"
							<?php
							if ( $auto_save ) :
								?>
							data-autosave="true" <?php endif; ?>>
						<button type="button" class="sync-button sync-file-upload-button">Select File</button>
						<button type="button" class="sync-button sync-file-remove-button" <?php echo empty( $file_url ) ? ' style="display:none;"' : ''; ?>>Remove</button>
					</div>
					<?php
					break;

			endswitch;
			?>

			<?php if ( ! empty( $description ) ) : ?>
				<p class="sync-input-description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>

			<?php if ( $auto_save ) : ?>
				<div class="sync-input-message" data-for="<?php echo esc_attr( $name ); ?>"></div>
			<?php endif; ?>

			<?php if ( ! empty( $settings['regex_message_error_content'] ) && is_array( $settings['regex_message_error_content'] ) ) : ?>
				<div class="hidden" id="sync-field-<?php echo esc_attr( $name ); ?>-error">
					<?php $this->generate_settings_html( $settings['regex_message_error_content'], false ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $settings['regex_message_success_content'] ) && is_array( $settings['regex_message_success_content'] ) ) : ?>
				<div class="hidden" id="sync-field-<?php echo esc_attr( $name ); ?>-success">
					<?php $this->generate_settings_html( $settings['regex_message_success_content'], false ); ?>
				</div>
			<?php endif; ?>

		</div>
		<?php
		if ( $return_html ) {
			return ob_get_clean();
		}
	}


	/**
	 * Sanitize form output
	 *
	 * @param string $html HTML to sanitize.
	 * @param bool   $return_html Whether to return the HTML instead of echoing it.
	 */
	public function sanitize_form_output( $html, $return_html = false ) {
		if ( $return_html ) {
			ob_start();
		}
		$allowed_tags = array(
			'form'     => array(
				'class'  => true,
				'id'     => true,
				'method' => true,
				'action' => true,
				'data-*' => true,
			),
			'input'    => array(
				'type'        => true,
				'name'        => true,
				'value'       => true,
				'class'       => true,
				'id'          => true,
				'placeholder' => true,
				'required'    => true,
				'data-*'      => true,
			),
			'button'   => array(
				'type'  => true,
				'class' => true,
				'id'    => true,
				'name'  => true,
				'value' => true,
			),
			'div'      => array(
				'class'  => true,
				'id'     => true,
				'data-*' => true,
			),
			'span'     => array(
				'class' => true,
				'id'    => true,
			),
			'label'    => array(
				'for'   => true,
				'class' => true,
			),
			'select'   => array(
				'name'     => true,
				'class'    => true,
				'id'       => true,
				'required' => true,
			),
			'option'   => array(
				'value'    => true,
				'selected' => true,
			),
			'textarea' => array(
				'name'        => true,
				'class'       => true,
				'id'          => true,
				'placeholder' => true,
				'required'    => true,
			),
		);

		// Add more allowed tags as needed.
		$allowed_tags      = array_merge( $allowed_tags, wp_kses_allowed_html( 'post' ) );
		$allowed_tags['*'] = array(
			'class'  => true,
			'id'     => true,
			'style'  => true,
			'data-*' => true,
		);

		// Filter to allow custom tags.
		$allowed_tags = apply_filters( 'sync_allowed_html', $allowed_tags );

		// Sanitize the HTML.
		echo wp_kses( $html, $allowed_tags );
		if ( $return_html ) {
			return ob_get_clean();
		}
	}

	/**
	 * Validate input sync options
	 *
	 * @param array $settings_array Data to validate.
	 * 
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_input_sync_options( $settings_array ) {
		// Must be an array.
		if ( ! is_array( $settings_array ) ) {
			return false;
		}

		// Top-level structure: html/design and submit must exist.
		if ( ! isset( $settings_array['html'] ) || ! is_array( $settings_array['html'] ) ) {
			return false;
		}
		if ( ! isset( $settings_array['submit'] ) || ! is_array( $settings_array['submit'] ) ) {
			return false;
		}

		// Submit array keys must present and have valid value: 'seprate' (string or false), 'return_html' (bool), 'should_refresh' (bool).
		$submit = $settings_array['submit'];
		if ( ! array_key_exists( 'seprate', $submit ) || ! ( is_string( $submit['seprate'] ) || false === $submit['seprate'] ) ) {
			return false;
		}
		if ( ! isset( $submit['return_html'] ) || ! is_bool( $submit['return_html'] ) ) {
			return false;
		}
		if ( ! isset( $submit['should_refresh'] ) || ! is_bool( $submit['should_refresh'] ) ) {
			return false;
		}

		// Recursively find every input_<…> and enforce unique, string sync_option.
		$sync_options = array();

		/**
		 * Recursively walk through the settings array to find all input_<…> keys.
		 *
		 * @param array $data The data to walk through.
		 *
		 * @uses $walk, $sync_options
		 *
		 * @return bool True if all input_<…> keys are valid, false otherwise.
		 */
		$walk = function ( $data ) use ( &$walk, &$sync_options ) {
			foreach ( $data as $key => $value ) {
				if ( is_array( $value ) ) {
					if ( is_string( $key ) && 0 === strpos( $key, 'input_' ) ) {
						if ( ! isset( $value['sync_option'] ) || ! is_string( $value['sync_option'] ) ) {
							return false;
						}
						if ( in_array( $value['sync_option'], $sync_options, true ) ) {
							return false;
						}
						$sync_options[] = $value['sync_option'];
					}
					if ( false === $walk( $value ) ) {
						return false;
					}
				}
			}
			return true;
		};

		return (bool) $walk( $settings_array['html'] );
	}
}
