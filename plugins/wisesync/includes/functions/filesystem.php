<?php
/**
 * WiseSync File System Functions
 *
 * This file contains functions for file system operations in the WiseSync plugin.
 *
 * @package   WiseSync
 * @since     1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the global instance of Sync_Filesystem.
 *
 * @return Sync\Sync_Filesystem
 */
function sync_get_filesystem() {
	global $sync_filesystem;
	
	if ( ! isset( $sync_filesystem ) ) {
		$sync_filesystem = new Sync\Sync_Filesystem();
	}
	
	return $sync_filesystem;
}

/**
 * Create directory if it doesn't exist, and ensure it's writable.
 *
 * @param string $dir_path Directory path.
 * @return bool True on success, false on failure.
 */
function sync_create_dir( $dir_path ) {
	return sync_get_filesystem()->maybe_create_dir( $dir_path );
}

/**
 * Check if a file or directory exists.
 *
 * @param string $path Path to the file or directory.
 * @return bool True if file exists, false otherwise.
 */
function sync_exists( $path ) {
	return sync_get_filesystem()->exists( $path );
}

/**
 * Check if path is a directory.
 *
 * @param string $path Path to check.
 * @return bool True if path is a directory, false otherwise.
 */
function sync_is_dir( $path ) {
	return sync_get_filesystem()->is_dir( $path );
}

/**
 * Get contents of a file.
 *
 * @param string $file_path Path to the file.
 * @return string|false File contents on success, false on failure.
 */
function sync_file_get_contents( $file_path ) {
	return sync_get_filesystem()->get_contents( $file_path );
}

/**
 * Write contents to a file.
 *
 * @param string $file_path Path to the file.
 * @param string $contents  File contents.
 * @return bool True on success, false on failure.
 */
function sync_file_put_contents( $file_path, $contents ) {
	return sync_get_filesystem()->put_contents( $file_path, $contents );
}

/**
 * Delete a file.
 *
 * @param string $file_path Path to the file.
 * @return bool True on success, false on failure.
 */
function sync_file_delete( $file_path ) {
	return sync_get_filesystem()->delete( $file_path );
}

/**
 * Move/rename a file.
 *
 * @param string $source      Source file path.
 * @param string $destination Destination file path.
 * @return bool True on success, false on failure.
 */
function sync_file_move( $source, $destination ) {
	return sync_get_filesystem()->move( $source, $destination );
}

/**
 * Copy a file.
 *
 * @param string $source      Source file path.
 * @param string $destination Destination file path.
 * @return bool True on success, false on failure.
 */
function sync_file_copy( $source, $destination ) {
	return sync_get_filesystem()->copy( $source, $destination );
}

/**
 * Check if a file is readable.
 *
 * @param string $file_path Path to the file.
 * @return bool True if file is readable, false otherwise.
 */
function sync_file_is_readable( $file_path ) {
	return sync_get_filesystem()->is_readable( $file_path );
}

/**
 * Check if a file is writable.
 *
 * @param string $file_path Path to the file.
 * @return bool True if file is writable, false otherwise.
 */
function sync_file_is_writable( $file_path ) {
	return sync_get_filesystem()->is_writable( $file_path );
}

/**
 * List files in a directory.
 *
 * @param string $directory Directory path.
 * @param bool   $include_hidden Whether to include hidden files.
 * @param bool   $recursive Whether to recursively scan the directory.
 * @return array|false List of files on success, false on failure.
 */
function sync_list_dir( $directory, $include_hidden = true, $recursive = false ) {
	return sync_get_filesystem()->dirlist( $directory, $include_hidden, $recursive );
}

/**
 * Create a new file with unique filename.
 *
 * @param string $dir_path Directory path.
 * @param string $prefix   Filename prefix.
 * @return string|false Path to the new file on success, false on failure.
 */
function sync_file_create_temp( $dir_path = '', $prefix = '' ) {
	return sync_get_filesystem()->create_temp_file( $dir_path, $prefix );
}

/**
 * Get file modification time.
 *
 * @param string $file_path Path to the file.
 * @return int|false File modification time on success, false on failure.
 */
function sync_file_mtime( $file_path ) {
	return sync_get_filesystem()->mtime( $file_path );
}

/**
 * Get file size.
 *
 * @param string $file_path Path to the file.
 * @return int|false File size in bytes on success, false on failure.
 */
function sync_file_size( $file_path ) {
	return sync_get_filesystem()->size( $file_path );
}

/**
 * Check if upload directory is available.
 *
 * @return bool True if upload directory is available, false otherwise.
 */
function sync_is_upload_dir_available() {
	return sync_get_filesystem()->is_upload_dir_available();
}

/**
 * Get path in upload directory.
 *
 * @param string $path Relative path inside upload directory.
 * @return string|false Absolute path on success, false on failure.
 */
function sync_get_upload_path( $path = '' ) {
	return sync_get_filesystem()->get_upload_path( $path );
}

/**
 * Get URL for a file in upload directory.
 *
 * @param string $path Relative path inside upload directory.
 * @return string|false URL on success, false on failure.
 */
function sync_get_upload_url( $path = '' ) {
	return sync_get_filesystem()->get_upload_url( $path );
}

/**
 * Check if a directory is empty.
 *
 * @param string $dir_path Directory path.
 * @return bool True if directory is empty or doesn't exist, false otherwise.
 */
function sync_is_dir_empty( $dir_path ) {
	return sync_get_filesystem()->is_dir_empty( $dir_path );
}

/**
 * Append content to a file.
 *
 * @param string $file_path Path to the file.
 * @param string $content   Content to append.
 * @return bool True on success, false on failure.
 */
function sync_file_append_contents( $file_path, $content ) {
	return sync_get_filesystem()->append_contents( $file_path, $content );
}

/**
 * Delete a directory and its contents.
 *
 * @param string $dir_path Directory path.
 * @return bool True on success, false on failure.
 */
function sync_delete_directory( $dir_path ) {
	return sync_get_filesystem()->delete_directory( $dir_path );
}
