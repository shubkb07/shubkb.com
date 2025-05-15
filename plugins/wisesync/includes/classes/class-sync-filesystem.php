<?php
/**
 * WiseSync File System Class
 *
 * Wraps the WordPress file system API to provide a consistent interface for file operations.
 * This class is used to handle file operations in a way that is compatible with the WordPress environment.
 * It abstracts the underlying file system operations and provides methods for reading, writing, and deleting files.
 * It also includes methods for checking if a file exists and getting the contents of a file.
 *
 * @package   WiseSync
 * @since     1.0.0
 */

namespace Sync;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WiseSync File System Class
 *
 * This class provides a consistent interface for file operations in the WordPress environment.
 * Compatible with both standard WordPress and VIP environments.
 *
 * @since 1.0.0
 */
class Sync_Filesystem {
	/**
	 * WordPress Filesystem instance.
	 *
	 * @var \WP_Filesystem_Base
	 */
	private $wp_filesystem;

	/**
	 * Whether the filesystem is properly initialized.
	 *
	 * @var bool
	 */
	private $is_initialized = false;

	/**
	 * Allowed directory paths for file operations.
	 *
	 * @var array
	 */
	private $allowed_paths = array();

	/**
	 * Constructor.
	 *
	 * Initializes the WordPress filesystem.
	 */
	public function __construct() {
		$this->initialize_filesystem();
		$this->set_allowed_paths();
	}

	/**
	 * Initialize the WordPress filesystem.
	 *
	 * @return bool True if filesystem is initialized successfully, false otherwise.
	 */
	private function initialize_filesystem() {
		// Include the filesystem functions if not already included.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Initialize the WordPress filesystem.
		if ( ! WP_Filesystem() ) {
			return false;
		}

		global $wp_filesystem;
		
		if ( ! $wp_filesystem || is_wp_error( $wp_filesystem ) ) {
			return false;
		}

		$this->wp_filesystem  = $wp_filesystem;
		$this->is_initialized = true;
		
		return true;
	}

	/**
	 * Set allowed directory paths for file operations.
	 */
	private function set_allowed_paths() {
		// Get the upload directory.
		$upload_dir = wp_upload_dir();
		
		// Add allowed paths.
		$this->allowed_paths = array(
			$upload_dir['basedir'], // Upload directory.
			get_temp_dir(),         // Temporary directory.
		);
	}

	/**
	 * Check if given path is within allowed directories.
	 *
	 * @param string $path The path to check.
	 * @return bool True if path is allowed, false otherwise.
	 */
	private function is_path_allowed( $path ) {
		$real_path = realpath( $path );
		
		// If real path can't be determined, check based on string matching.
		if ( false === $real_path ) {
			$path = wp_normalize_path( $path );
			foreach ( $this->allowed_paths as $allowed_path ) {
				$allowed_path = wp_normalize_path( $allowed_path );
				if ( 0 === strpos( $path, $allowed_path ) ) {
					return true;
				}
			}
			return false;
		}
		
		// Check against real paths.
		foreach ( $this->allowed_paths as $allowed_path ) {
			$allowed_real_path = realpath( $allowed_path );
			if ( false !== $allowed_real_path && 0 === strpos( $real_path, $allowed_real_path ) ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Ensure the filesystem is initialized.
	 *
	 * @return bool True if initialized successfully, false otherwise.
	 * @throws \Exception If filesystem is not initialized.
	 */
	private function ensure_initialized() {
		if ( ! $this->is_initialized ) {
			if ( ! $this->initialize_filesystem() ) {
				throw new \Exception( 'WordPress filesystem is not initialized.' );
			}
		}
		
		return true;
	}

	/**
	 * Create directory if it doesn't exist, and ensure it's writable.
	 *
	 * @param string $dir_path Directory path.
	 * @return bool True on success, false on failure.
	 */
	public function maybe_create_dir( $dir_path ) {
		try {
			$this->ensure_initialized();
			
			// Check if directory path is allowed.
			if ( ! $this->is_path_allowed( $dir_path ) ) {
				return false;
			}
			
			// In VIP environment, we can't create directories.
			if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
				return $this->wp_filesystem->is_dir( $dir_path );
			}
			
			// Check if directory exists.
			if ( $this->wp_filesystem->is_dir( $dir_path ) ) {
				return true;
			}
			
			// Create directory.
			return $this->wp_filesystem->mkdir( $dir_path, FS_CHMOD_DIR );
			
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Check if a file exists.
	 *
	 * @param string $file_path Path to the file.
	 * @return bool True if file exists, false otherwise.
	 */
	public function exists( $file_path ) {
		try {
			$this->ensure_initialized();
			return $this->wp_filesystem->exists( $file_path );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Check if path is a directory.
	 *
	 * @param string $path Path to check.
	 * @return bool True if path is a directory, false otherwise.
	 */
	public function is_dir( $path ) {
		try {
			$this->ensure_initialized();
			return $this->wp_filesystem->is_dir( $path );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Get contents of a file.
	 *
	 * @param string $file_path Path to the file.
	 * @return string|false File contents on success, false on failure.
	 */
	public function get_contents( $file_path ) {
		try {
			$this->ensure_initialized();
			
			if ( ! $this->exists( $file_path ) ) {
				return false;
			}
			
			return $this->wp_filesystem->get_contents( $file_path );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Write contents to a file.
	 *
	 * @param string $file_path Path to the file.
	 * @param string $contents  File contents.
	 * @return bool True on success, false on failure.
	 */
	public function put_contents( $file_path, $contents, $bypass = false ) {
		try {
			$this->ensure_initialized();

			$special_files = array(
				'sync_obj_cache_access' => WP_CONTENT_DIR . '/object-cache.php',
				'sync_adv_cache_access' => WP_CONTENT_DIR . '/advanced-cache.php',
				'sync_sunrise_access'   => WP_CONTENT_DIR . '/sunrise.php',
				'sync_mu_plugin_access' => WPMU_PLUGIN_DIR . '/sync.php',
				'sync_wp_config_access' => ABSPATH . 'wp-config.php',
				'sync_htaccess_access'  => ABSPATH . '.htaccess',
			);

			if ( $bypass ) {
				// Check if bypass path match it's path in special files.
				$bypass_path = $special_files[ $bypass ];
				if ( $bypass_path !== $file_path ) {
					return false;
				}
				// else add the path to allowed paths by removing file name.
				$this->allowed_paths[] = dirname( $bypass_path );
			}

			// Check if file path is allowed.
			if ( ! $this->is_path_allowed( $file_path ) ) {
				return false;
			}

			
			// Create directory if it doesn't exist.
			$dir_path = dirname( $file_path );
			if ( ! $this->maybe_create_dir( $dir_path ) ) {
				return false;
			}
			
			// Write to file.
			return $this->wp_filesystem->put_contents( $file_path, $contents, FS_CHMOD_FILE );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Delete a file.
	 *
	 * @param string $file_path Path to the file.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $file_path ) {
		try {
			$this->ensure_initialized();
			
			// Check if file path is allowed.
			if ( ! $this->is_path_allowed( $file_path ) ) {
				return false;
			}
			
			// In VIP environment, we can't delete files.
			if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
				return false;
			}
			
			if ( ! $this->exists( $file_path ) ) {
				return true; // File doesn't exist, so it's already deleted.
			}
			
			return $this->wp_filesystem->delete( $file_path );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Move/rename a file.
	 *
	 * @param string $source      Source file path.
	 * @param string $destination Destination file path.
	 * @return bool True on success, false on failure.
	 */
	public function move( $source, $destination ) {
		try {
			$this->ensure_initialized();
			
			// Check if paths are allowed.
			if ( ! $this->is_path_allowed( $source ) || ! $this->is_path_allowed( $destination ) ) {
				return false;
			}
			
			// In VIP environment, we have limitations.
			if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
				// We can only move within allowed directories.
				$src_dir = dirname( $source );
				$dst_dir = dirname( $destination );
				
				if ( ! $this->is_dir( $dst_dir ) ) {
					return false; // Can't create destination directory in VIP.
				}
			} else {
				// Create destination directory if it doesn't exist.
				$dst_dir = dirname( $destination );
				if ( ! $this->maybe_create_dir( $dst_dir ) ) {
					return false;
				}
			}
			
			if ( ! $this->exists( $source ) ) {
				return false; // Source file doesn't exist.
			}
			
			return $this->wp_filesystem->move( $source, $destination );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Copy a file.
	 *
	 * @param string $source      Source file path.
	 * @param string $destination Destination file path.
	 * @return bool True on success, false on failure.
	 */
	public function copy( $source, $destination ) {
		try {
			$this->ensure_initialized();
			
			// Check if paths are allowed.
			if ( ! $this->is_path_allowed( $source ) || ! $this->is_path_allowed( $destination ) ) {
				return false;
			}
			
			// Create destination directory if it doesn't exist.
			$dst_dir = dirname( $destination );
			if ( ! $this->maybe_create_dir( $dst_dir ) ) {
				return false;
			}
			
			if ( ! $this->exists( $source ) ) {
				return false; // Source file doesn't exist.
			}
			
			return $this->wp_filesystem->copy( $source, $destination );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Check if a file is readable.
	 *
	 * @param string $file_path Path to the file.
	 * @return bool True if file is readable, false otherwise.
	 */
	public function is_readable( $file_path ) {
		try {
			$this->ensure_initialized();
			return $this->wp_filesystem->is_readable( $file_path );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Check if a file is writable.
	 *
	 * @param string $file_path Path to the file.
	 * @return bool True if file is writable, false otherwise.
	 */
	public function is_writable( $file_path ) {
		try {
			$this->ensure_initialized();
			
			// In VIP environment, check if path is allowed.
			if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) && ! $this->is_path_allowed( $file_path ) ) {
				return false;
			}
			
			return $this->wp_filesystem->is_writable( $file_path );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * List files in a directory.
	 *
	 * @param string $directory Directory path.
	 * @param bool   $include_hidden Whether to include hidden files.
	 * @param bool   $recursive Whether to recursively scan the directory.
	 * @return array|false List of files on success, false on failure.
	 */
	public function dirlist( $directory, $include_hidden = true, $recursive = false ) {
		try {
			$this->ensure_initialized();
			
			if ( ! $this->is_dir( $directory ) ) {
				return false;
			}
			
			return $this->wp_filesystem->dirlist( $directory, $include_hidden, $recursive );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Create a new file with unique filename.
	 *
	 * @param string $dir_path Directory path.
	 * @param string $prefix   Filename prefix.
	 * @return string|false Path to the new file on success, false on failure.
	 */
	public function create_temp_file( $dir_path = '', $prefix = '' ) {
		try {
			$this->ensure_initialized();
			
			// Use system temp directory if no directory is specified.
			if ( empty( $dir_path ) ) {
				$dir_path = get_temp_dir();
			}
			
			// Check if directory path is allowed.
			if ( ! $this->is_path_allowed( $dir_path ) ) {
				return false;
			}
			
			// Create directory if it doesn't exist.
			if ( ! $this->maybe_create_dir( $dir_path ) ) {
				return false;
			}
			
			// Create a unique filename.
			$filename  = wp_unique_filename( $dir_path, $prefix . wp_generate_password( 6, false ) );
			$file_path = trailingslashit( $dir_path ) . $filename;
			
			// Create the file.
			if ( ! $this->put_contents( $file_path, '' ) ) {
				return false;
			}
			
			return $file_path;
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Get file modification time.
	 *
	 * @param string $file_path Path to the file.
	 * @return int|false File modification time on success, false on failure.
	 */
	public function mtime( $file_path ) {
		try {
			$this->ensure_initialized();
			
			if ( ! $this->exists( $file_path ) ) {
				return false;
			}
			
			return $this->wp_filesystem->mtime( $file_path );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Get file size.
	 *
	 * @param string $file_path Path to the file.
	 * @return int|false File size in bytes on success, false on failure.
	 */
	public function size( $file_path ) {
		try {
			$this->ensure_initialized();
			
			if ( ! $this->exists( $file_path ) ) {
				return false;
			}
			
			return $this->wp_filesystem->size( $file_path );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Check if upload directory is available.
	 *
	 * @return bool True if upload directory is available, false otherwise.
	 */
	public function is_upload_dir_available() {
		$upload_dir = wp_upload_dir();
		return ! $upload_dir['error'];
	}
	
	/**
	 * Get path in upload directory.
	 *
	 * @param string $path Relative path inside upload directory.
	 * @return string|false Absolute path on success, false on failure.
	 */
	public function get_upload_path( $path = '' ) {
		$upload_dir = wp_upload_dir();
		
		if ( $upload_dir['error'] ) {
			return false;
		}
		
		$path = ltrim( $path, '/' );
		return path_join( $upload_dir['basedir'], $path );
	}
	
	/**
	 * Get URL for a file in upload directory.
	 *
	 * @param string $path Relative path inside upload directory.
	 * @return string|false URL on success, false on failure.
	 */
	public function get_upload_url( $path = '' ) {
		$upload_dir = wp_upload_dir();
		
		if ( $upload_dir['error'] ) {
			return false;
		}
		
		$path = ltrim( $path, '/' );
		return path_join( $upload_dir['baseurl'], $path );
	}
	
	/**
	 * Check if a directory is empty.
	 *
	 * @param string $dir_path Directory path.
	 * @return bool True if directory is empty or doesn't exist, false otherwise.
	 */
	public function is_dir_empty( $dir_path ) {
		try {
			$this->ensure_initialized();
			
			if ( ! $this->is_dir( $dir_path ) ) {
				return true; // Directory doesn't exist, so it's technically empty.
			}
			
			$files = $this->dirlist( $dir_path, true, false );
			
			return empty( $files );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Append content to a file.
	 *
	 * @param string $file_path Path to the file.
	 * @param string $content   Content to append.
	 * @return bool True on success, false on failure.
	 */
	public function append_contents( $file_path, $content ) {
		try {
			$this->ensure_initialized();
			
			// Check if file path is allowed.
			if ( ! $this->is_path_allowed( $file_path ) ) {
				return false;
			}
			
			// If file doesn't exist, create it.
			if ( ! $this->exists( $file_path ) ) {
				return $this->put_contents( $file_path, $content );
			}
			
			// Get existing content.
			$existing_content = $this->get_contents( $file_path );
			
			if ( false === $existing_content ) {
				return false;
			}
			
			// Append new content.
			return $this->put_contents( $file_path, $existing_content . $content );
		} catch ( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Delete a directory and its contents.
	 *
	 * @param string $dir_path Directory path.
	 * @return bool True on success, false on failure.
	 */
	public function delete_directory( $dir_path ) {
		try {
			$this->ensure_initialized();
			
			// Check if directory path is allowed.
			if ( ! $this->is_path_allowed( $dir_path ) ) {
				return false;
			}
			
			// In VIP environment, we can't delete directories.
			if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
				return false;
			}
			
			if ( ! $this->is_dir( $dir_path ) ) {
				return true; // Directory doesn't exist, so it's already deleted.
			}
			
			return $this->wp_filesystem->rmdir( $dir_path, true ); // Recursive delete.
		} catch ( \Exception $e ) {
			return false;
		}
	}
}
