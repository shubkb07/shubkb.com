<?php
/**
 * SyncCache Advanced Cache Class
 *
 * This class handles the creation and management of the advanced-cache.php drop-in.
 *
 * @package SyncCache
 */

namespace Sync;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access.' );
}

/**
 * Sync_Advanced_Cache class.
 */
class Sync_Advanced_Cache {
	/**
	 * Configuration options.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Constructor.
	 *
	 * @param array $options Configuration options.
	 */
	public function __construct( $options = array() ) {
		$this->options = $options;
		
		// Register hooks.
		add_action( 'activate_sync-cache/sync-cache.php', array( $this, 'activate' ) );
		add_action( 'deactivate_sync-cache/sync-cache.php', array( $this, 'deactivate' ) );
		add_action( 'admin_init', array( $this, 'check_advanced_cache' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'update_option_sync_cache_settings', array( $this, 'update_advanced_cache' ), 10, 2 );
		add_filter( 'plugin_action_links_sync-cache/sync-cache.php', array( $this, 'plugin_action_links' ) );
		
		// Post update hooks.
		add_action( 'transition_post_status', array( $this, 'on_post_status_change' ), 10, 3 );
		add_action( 'save_post', array( $this, 'on_post_save' ) );
		add_action( 'edit_post', array( $this, 'on_post_edit' ) );
		add_action( 'delete_post', array( $this, 'on_post_delete' ) );
		add_action( 'wp_trash_post', array( $this, 'on_post_trash' ) );
		add_action( 'clean_post_cache', array( $this, 'on_post_clean_cache' ) );
		
		// Comment hooks.
		add_action( 'wp_insert_comment', array( $this, 'on_comment_change' ) );
		add_action( 'edit_comment', array( $this, 'on_comment_change' ) );
		add_action( 'delete_comment', array( $this, 'on_comment_change' ) );
		add_action( 'wp_set_comment_status', array( $this, 'on_comment_status' ), 10, 2 );
		
		// Term and taxonomy hooks.
		add_action( 'create_term', array( $this, 'on_term_change' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'on_term_change' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'on_term_change' ), 10, 3 );
		
		// User hooks.
		add_action( 'profile_update', array( $this, 'on_user_change' ) );
		add_action( 'user_register', array( $this, 'on_user_change' ) );
		add_action( 'deleted_user', array( $this, 'on_user_change' ) );
		
		// Specific plugin integrations.
		add_action( 'woocommerce_product_object_updated_props', array( $this, 'on_woocommerce_product_update' ), 10, 2 );
		add_action( 'woocommerce_update_product', array( $this, 'on_woocommerce_product_update' ) );
		add_action( 'woocommerce_update_product_variation', array( $this, 'on_woocommerce_product_update' ) );
		
		// Add purge cache button to admin bar.
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_purge' ), 999 );
		
		// Setup cache stats.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		
		// Register cache cleanup cron.
		add_action( 'sync_cache_cleanup', array( $this, 'do_cache_cleanup' ) );
		
		// Register cache preload cron.
		add_action( 'sync_cache_preload', array( $this, 'do_cache_preload' ) );
		
		// Handle manual cache purge.
		add_action( 'admin_post_sync_cache_purge', array( $this, 'handle_manual_purge' ) );
		add_action( 'wp_ajax_sync_cache_purge', array( $this, 'ajax_purge_cache' ) );
	}

	/**
	 * Plugin activation hook.
	 */
	public function activate() {
		// Create the advanced-cache.php file.
		$this->create_advanced_cache();
		
		// Add WP_CACHE constant to wp-config.php if not already there.
		$this->add_wp_cache_constant();
		
		// Schedule cleanup cron.
		if ( ! wp_next_scheduled( 'sync_cache_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'sync_cache_cleanup' );
		}
		
		// Schedule preload cron if enabled.
		if ( ! empty( $this->options['preload_enabled'] ) && ! wp_next_scheduled( 'sync_cache_preload' ) ) {
			wp_schedule_event( time(), 'daily', 'sync_cache_preload' );
		}
	}

	/**
	 * Plugin deactivation hook.
	 */
	public function deactivate() {
		// Remove the advanced-cache.php file.
		$this->remove_advanced_cache();
		
		// Optionally remove WP_CACHE constant from wp-config.php.
		$this->remove_wp_cache_constant();
		
		// Clear scheduled cron jobs.
		wp_clear_scheduled_hook( 'sync_cache_cleanup' );
		wp_clear_scheduled_hook( 'sync_cache_preload' );
	}

	/**
	 * Create the advanced-cache.php file.
	 *
	 * @return bool Whether the file was created.
	 */
	public function create_advanced_cache() {
		// Get the source file.
		$source_file = plugin_dir_path( __FILE__ ) . 'advanced-cache.php';
		if ( ! file_exists( $source_file ) ) {
			return false;
		}
		
		// Get content.
		$content = file_get_contents( $source_file );
		if ( ! $content ) {
			return false;
		}
		
		// Replace configuration placeholders.
		$content = $this->replace_config_placeholders( $content );
		
		// Get the destination path.
		$destination = WP_CONTENT_DIR . '/advanced-cache.php';
		
		// Write the file.
		$result = file_put_contents( $destination, $content );
		
		return false !== $result;
	}

	/**
	 * Replace configuration placeholders in the advanced-cache.php file.
	 *
	 * @param string $content File content.
	 * @return string Updated content.
	 */
	private function replace_config_placeholders( $content ) {
		// Get settings.
		$settings = get_option( 'sync_cache_settings', array() );
		
		// Create configuration array.
		$config = array(
			'cache_enabled'             => true,
			'cache_path'                => WP_CONTENT_DIR . '/cache/sync-cache',
			'fallback_path'             => 'wp-content/cache/sync-cache',
			'cache_lifetime'            => isset( $settings['cache_lifetime'] ) ? (int) $settings['cache_lifetime'] : 86400,
			'cache_exclude_urls'        => isset( $settings['exclude_urls'] ) ? explode( "\n", $settings['exclude_urls'] ) : array(
				'/wp-admin/',
				'/wp-login.php',
				'/cart',
				'/checkout',
				'/my-account',
			),
			'cache_exclude_cookies'     => isset( $settings['exclude_cookies'] ) ? explode( "\n", $settings['exclude_cookies'] ) : array(
				'wp-postpass_',
				'wordpress_logged_in_',
				'comment_author_',
				'woocommerce_items_in_cart',
			),
			'cache_exclude_user_agents' => isset( $settings['exclude_user_agents'] ) ? explode( "\n", $settings['exclude_user_agents'] ) : array(
				'bot',
				'crawler',
				'spider',
			),
			'cache_mobile'              => isset( $settings['cache_mobile'] ) ? (bool) $settings['cache_mobile'] : true,
			'cache_tablet'              => isset( $settings['cache_tablet'] ) ? (bool) $settings['cache_tablet'] : true,
			'separate_mobile_cache'     => isset( $settings['separate_mobile_cache'] ) ? (bool) $settings['separate_mobile_cache'] : true,
			'cache_logged_in_users'     => isset( $settings['cache_logged_in_users'] ) ? (bool) $settings['cache_logged_in_users'] : false,
			'cache_ssl'                 => isset( $settings['cache_ssl'] ) ? (bool) $settings['cache_ssl'] : true,
			'cache_404'                 => isset( $settings['cache_404'] ) ? (bool) $settings['cache_404'] : false,
			'cache_query_strings'       => isset( $settings['cache_query_strings'] ) ? (bool) $settings['cache_query_strings'] : false,
			'allowed_query_strings'     => isset( $settings['allowed_query_strings'] ) ? explode( "\n", $settings['allowed_query_strings'] ) : array(
				's',
				'p',
				'lang',
			),
			'cache_rest_api'            => isset( $settings['cache_rest_api'] ) ? (bool) $settings['cache_rest_api'] : false,
			'cache_ajax'                => isset( $settings['cache_ajax'] ) ? (bool) $settings['cache_ajax'] : false,
			'cache_feed'                => isset( $settings['cache_feed'] ) ? (bool) $settings['cache_feed'] : false,
			'purge_on_post_edit'        => isset( $settings['purge_on_post_edit'] ) ? (bool) $settings['purge_on_post_edit'] : true,
			'purge_on_comment'          => isset( $settings['purge_on_comment'] ) ? (bool) $settings['purge_on_comment'] : true,
			'purge_schedule'            => isset( $settings['purge_schedule'] ) ? $settings['purge_schedule'] : 'daily',
			'enable_in_dev_mode'        => isset( $settings['enable_in_dev_mode'] ) ? (bool) $settings['enable_in_dev_mode'] : false,
			'enable_logging'            => isset( $settings['enable_logging'] ) ? (bool) $settings['enable_logging'] : true,
			'debug_mode'                => isset( $settings['debug_mode'] ) ? (bool) $settings['debug_mode'] : false,
			'minify_html'               => isset( $settings['minify_html'] ) ? (bool) $settings['minify_html'] : true,
			'minify_css'                => isset( $settings['minify_css'] ) ? (bool) $settings['minify_css'] : true,
			'minify_js'                 => isset( $settings['minify_js'] ) ? (bool) $settings['minify_js'] : true,
			'combine_css'               => isset( $settings['combine_css'] ) ? (bool) $settings['combine_css'] : true,
			'combine_js'                => isset( $settings['combine_js'] ) ? (bool) $settings['combine_js'] : true,
			'lazy_load'                 => isset( $settings['lazy_load'] ) ? (bool) $settings['lazy_load'] : true,
			'cdn_enabled'               => isset( $settings['cdn_enabled'] ) ? (bool) $settings['cdn_enabled'] : false,
			'cdn_url'                   => isset( $settings['cdn_url'] ) ? $settings['cdn_url'] : '{{CDN_URL}}',
			'cdn_includes'              => isset( $settings['cdn_includes'] ) ? explode( "\n", $settings['cdn_includes'] ) : array(
				'.jpg',
				'.jpeg',
				'.png',
				'.gif',
				'.webp',
				'.svg',
				'.css',
				'.js',
			),
			'warmup_method'             => isset( $settings['warmup_method'] ) ? $settings['warmup_method'] : 'auto',
			'iops_protection'           => isset( $settings['iops_protection'] ) ? (bool) $settings['iops_protection'] : true,
			'max_files_per_second'      => isset( $settings['max_files_per_second'] ) ? (int) $settings['max_files_per_second'] : 100,
			'admin_roles_manage_cache'  => isset( $settings['admin_roles_manage_cache'] ) ? $settings['admin_roles_manage_cache'] : array(
				'administrator',
				'editor',
			),
			'cache_analytics'           => isset( $settings['cache_analytics'] ) ? (bool) $settings['cache_analytics'] : true,
			'analytics_sampling_rate'   => isset( $settings['analytics_sampling_rate'] ) ? (int) $settings['analytics_sampling_rate'] : 10,
			'preload_homepage'          => isset( $settings['preload_homepage'] ) ? (bool) $settings['preload_homepage'] : true,
			'preload_public_posts'      => isset( $settings['preload_public_posts'] ) ? (bool) $settings['preload_public_posts'] : true,
			'preload_public_taxonomies' => isset( $settings['preload_public_taxonomies'] ) ? (bool) $settings['preload_public_taxonomies'] : true,
		);
		
		// Clean up arrays (remove empty entries).
		foreach ( $config as $key => $value ) {
			if ( is_array( $value ) ) {
				$config[ $key ] = array_filter( array_map( 'trim', $value ) );
			}
		}
		
		// JSON encode the configuration.
		$config_json = json_encode( $config, JSON_PRETTY_PRINT );
		
		// Replace configuration placeholder.
		$content = str_replace( 
			'$sync_cache_config = \'{', 
			'$sync_cache_config = \'' . substr( $config_json, 1, -1 ), 
			$content 
		);
		
		return $content;
	}

	/**
	 * Remove the advanced-cache.php file.
	 *
	 * @return bool Whether the file was removed.
	 */
	public function remove_advanced_cache() {
		$file = WP_CONTENT_DIR . '/advanced-cache.php';
		
		// Only remove if it's our file.
		if ( file_exists( $file ) ) {
			// Check if it's our file.
			$content = file_get_contents( $file );
			if ( strpos( $content, 'SyncCache' ) !== false ) {
				@unlink( $file );
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Add WP_CACHE constant to wp-config.php.
	 *
	 * @return bool Whether the constant was added.
	 */
	public function add_wp_cache_constant() {
		// Skip if constant is already defined.
		if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
			return true;
		}
		
		// Get wp-config.php path.
		$config_file = $this->get_wp_config_path();
		if ( ! $config_file ) {
			return false;
		}
		
		// Get wp-config.php content.
		$config_content = file_get_contents( $config_file );
		if ( ! $config_content ) {
			return false;
		}
		
		// Check if WP_CACHE is defined but set to false.
		if ( preg_match( '/define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,\s*(?:false|0|\'0\'|"0")\s*\)/i', $config_content ) ) {
			// Replace false with true.
			$config_content = preg_replace(
				'/define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,\s*(?:false|0|\'0\'|"0")\s*\)/i',
				'define(\'WP_CACHE\', true)',
				$config_content
			);
		} else {
			// Add constant after opening PHP tag.
			$config_content = preg_replace(
				'/^<\?php/',
				"<?php\n\n// Added by SyncCache\ndefine('WP_CACHE', true);",
				$config_content
			);
		}
		
		// Write updated content back to file.
		$result = file_put_contents( $config_file, $config_content );
		
		return false !== $result;
	}

	/**
	 * Remove WP_CACHE constant from wp-config.php.
	 *
	 * @return bool Whether the constant was removed.
	 */
	public function remove_wp_cache_constant() {
		// Get wp-config.php path.
		$config_file = $this->get_wp_config_path();
		if ( ! $config_file ) {
			return false;
		}
		
		// Get wp-config.php content.
		$config_content = file_get_contents( $config_file );
		if ( ! $config_content ) {
			return false;
		}
		
		// Remove WP_CACHE constant.
		$updated_content = preg_replace(
			'/\s*\/\/\s*Added by SyncCache\s*\n\s*define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,\s*(?:true|false|0|1|\'0\'|\'1\'|"0"|"1")\s*\);\s*\n?/i',
			"\n",
			$config_content
		);
		
		// If no change, try another pattern (no comment).
		if ( $updated_content === $config_content ) {
			$updated_content = preg_replace(
				'/\s*define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,\s*(?:true|false|0|1|\'0\'|\'1\'|"0"|"1")\s*\);\s*\n?/i',
				"\n",
				$config_content
			);
		}
		
		// Write updated content back to file.
		$result = file_put_contents( $config_file, $updated_content );
		
		return false !== $result;
	}

	/**
	 * Get wp-config.php path.
	 *
	 * @return string|bool Path to wp-config.php or false if not found.
	 */
	private function get_wp_config_path() {
		// First, try the standard location.
		$config_file = ABSPATH . 'wp-config.php';
		
		if ( file_exists( $config_file ) ) {
			return $config_file;
		}
		
		// Then, try one directory up from ABSPATH.
		$config_file = dirname( ABSPATH ) . '/wp-config.php';
		
		if ( file_exists( $config_file ) ) {
			return $config_file;
		}
		
		return false;
	}

	/**
	 * Check if advanced-cache.php exists and is up to date.
	 */
	public function check_advanced_cache() {
		// Only run in admin.
		if ( ! is_admin() ) {
			return;
		}
		
		// Check if WP_CACHE is defined.
		if ( ! defined( 'WP_CACHE' ) || ! WP_CACHE ) {
			// Store error message.
			update_option( 'sync_cache_error', 'wp_cache_not_enabled' );
			return;
		}
		
		// Check if advanced-cache.php exists.
		$advanced_cache_file = WP_CONTENT_DIR . '/advanced-cache.php';
		if ( ! file_exists( $advanced_cache_file ) ) {
			// Store error message.
			update_option( 'sync_cache_error', 'advanced_cache_missing' );
			return;
		}
		
		// Check if it's our file.
		$content = file_get_contents( $advanced_cache_file );
		if ( strpos( $content, 'SyncCache' ) === false ) {
			// Store error message.
			update_option( 'sync_cache_error', 'advanced_cache_not_ours' );
			return;
		}
		
		// Check if SYNC_CACHE_LOADED is defined.
		if ( ! defined( 'SYNC_CACHE_LOADED' ) ) {
			// Store error message.
			update_option( 'sync_cache_error', 'advanced_cache_not_loaded' );
			return;
		}
		
		// All good, clear any errors.
		delete_option( 'sync_cache_error' );
	}

	/**
	 * Display admin notices for cache issues.
	 */
	public function admin_notices() {
		// Only show to admins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Get error message.
		$error = get_option( 'sync_cache_error' );
		
		if ( ! $error ) {
			return;
		}
		
		// Prepare fix URL.
		$fix_url = wp_nonce_url( admin_url( 'admin-post.php?action=sync_cache_fix_setup' ), 'sync_cache_fix_setup' );
		
		// Display appropriate error message.
		switch ( $error ) {
			case 'wp_cache_not_enabled':
				?>
				<div class="notice notice-error">
					<p>
						<strong>SyncCache:</strong> 
						<?php esc_html_e( 'WP_CACHE constant is not enabled in wp-config.php.', 'sync-cache' ); ?>
						<a href="<?php echo esc_url( $fix_url ); ?>"><?php esc_html_e( 'Fix it automatically', 'sync-cache' ); ?></a>
					</p>
				</div>
				<?php
				break;
				
			case 'advanced_cache_missing':
				?>
				<div class="notice notice-error">
					<p>
						<strong>SyncCache:</strong> 
						<?php esc_html_e( 'The advanced-cache.php file is missing.', 'sync-cache' ); ?>
						<a href="<?php echo esc_url( $fix_url ); ?>"><?php esc_html_e( 'Fix it automatically', 'sync-cache' ); ?></a>
					</p>
				</div>
				<?php
				break;
				
			case 'advanced_cache_not_ours':
				?>
				<div class="notice notice-error">
					<p>
						<strong>SyncCache:</strong> 
						<?php esc_html_e( 'The advanced-cache.php file is not managed by SyncCache. You may have another caching plugin active.', 'sync-cache' ); ?>
						<a href="<?php echo esc_url( $fix_url ); ?>"><?php esc_html_e( 'Replace with SyncCache version', 'sync-cache' ); ?></a>
					</p>
				</div>
				<?php
				break;
				
			case 'advanced_cache_not_loaded':
				?>
				<div class="notice notice-error">
					<p>
						<strong>SyncCache:</strong> 
						<?php esc_html_e( 'The advanced-cache.php file exists but is not being loaded. Please check your server configuration.', 'sync-cache' ); ?>
						<a href="<?php echo esc_url( $fix_url ); ?>"><?php esc_html_e( 'Try to fix it automatically', 'sync-cache' ); ?></a>
					</p>
				</div>
				<?php
				break;
		}
	}

	/**
	 * Update advanced-cache.php when settings are changed.
	 *
	 * @param mixed $old_value Old option value.
	 * @param mixed $new_value New option value.
	 */
	public function update_advanced_cache( $old_value, $new_value ) {
		// Recreate the advanced-cache.php file with new settings.
		$this->create_advanced_cache();
		
		// Check for cache method changes that require purging.
		$purge_needed = false;
		
		// Settings that require purging when changed.
		$purge_settings = array(
			'cache_mobile',
			'cache_tablet',
			'separate_mobile_cache',
			'cache_logged_in_users',
			'minify_html',
			'minify_css',
			'minify_js',
			'combine_css',
			'combine_js',
			'lazy_load',
		);
		
		foreach ( $purge_settings as $setting ) {
			if ( isset( $old_value[ $setting ], $new_value[ $setting ] ) && $old_value[ $setting ] !== $new_value[ $setting ] ) {
				$purge_needed = true;
				break;
			}
		}
		
		if ( $purge_needed ) {
			// Purge the entire cache.
			$this->purge_all_cache();
		}
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function plugin_action_links( $links ) {
		// Add settings link.
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=sync_cache_settings' ) . '">' . __( 'Settings', 'sync-cache' ) . '</a>';
		array_unshift( $links, $settings_link );
		
		// Add purge cache link.
		$purge_link = '<a href="' . wp_nonce_url( admin_url( 'admin-post.php?action=sync_cache_purge' ), 'sync_cache_purge' ) . '">' . __( 'Purge Cache', 'sync-cache' ) . '</a>';
		array_unshift( $links, $purge_link );
		
		return $links;
	}

	/**
	 * Handle post status changes.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function on_post_status_change( $new_status, $old_status, $post ) {
		// Skip if post is not published or being published.
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}
		
		// Skip if this is an autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		// Skip if setting is disabled.
		if ( empty( $this->options['purge_on_post_edit'] ) ) {
			return;
		}
		
		// Purge cache for this post.
		$this->purge_post_cache( $post->ID );
	}

	/**
	 * Handle post save.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_post_save( $post_id ) {
		// Skip if this is an autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		// Skip if this is a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		
		// Skip if setting is disabled.
		if ( empty( $this->options['purge_on_post_edit'] ) ) {
			return;
		}
		
		// Purge cache for this post.
		$this->purge_post_cache( $post_id );
	}

	/**
	 * Handle post edit.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_post_edit( $post_id ) {
		// Skip revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		// Skip if setting is disabled.
		if ( empty( $this->options['purge_on_post_edit'] ) ) {
			return;
		}
		
		// Purge cache for this post.
		$this->purge_post_cache( $post_id );
	}

	/**
	 * Handle post deletion.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_post_delete( $post_id ) {
		// Skip if setting is disabled.
		if ( empty( $this->options['purge_on_post_edit'] ) ) {
			return;
		}
		
		// Purge cache for this post.
		$this->purge_post_cache( $post_id );
		
		// Also purge home and archive pages.
		$this->purge_home_cache();
		$this->purge_archives();
	}

	/**
	 * Handle post trash.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_post_trash( $post_id ) {
		// Skip if setting is disabled.
		if ( empty( $this->options['purge_on_post_edit'] ) ) {
			return;
		}
		
		// Purge cache for this post.
		$this->purge_post_cache( $post_id );
		
		// Also purge home and archive pages.
		$this->purge_home_cache();
		$this->purge_archives();
	}

	/**
	 * Handle post cache cleaning.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_post_clean_cache( $post_id ) {
		// Skip if setting is disabled.
		if ( empty( $this->options['purge_on_post_edit'] ) ) {
			return;
		}
		
		// Purge cache for this post.
		$this->purge_post_cache( $post_id );
	}

	/**
	 * Handle comment changes.
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function on_comment_change( $comment_id ) {
		// Skip if setting is disabled.
		if ( empty( $this->options['purge_on_comment'] ) ) {
			return;
		}
		
		// Get the comment.
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return;
		}
		
		// Purge cache for the post this comment belongs to.
		$this->purge_post_cache( $comment->comment_post_ID );
	}

	/**
	 * Handle comment status changes.
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $status     Comment status.
	 */
	public function on_comment_status( $comment_id, $status ) {
		// Skip if setting is disabled.
		if ( empty( $this->options['purge_on_comment'] ) ) {
			return;
		}
		
		// Get the comment.
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return;
		}
		
		// Purge cache for the post this comment belongs to.
		$this->purge_post_cache( $comment->comment_post_ID );
	}

	/**
	 * Handle term changes.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy.
	 */
	public function on_term_change( $term_id, $tt_id, $taxonomy ) {
		// Skip private taxonomies.
		$taxonomy_obj = get_taxonomy( $taxonomy );
		if ( ! $taxonomy_obj || ! $taxonomy_obj->public ) {
			return;
		}
		
		// Purge archive page for this term.
		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}
		
		// Purge term archive.
		$term_link = get_term_link( $term );
		if ( ! is_wp_error( $term_link ) ) {
			$this->purge_url_cache( $term_link );
		}
		
		// Also purge home page, as term changes may affect it.
		$this->purge_home_cache();
	}

	/**
	 * Handle user changes.
	 *
	 * @param int $user_id User ID.
	 */
	public function on_user_change( $user_id ) {
		// Get user.
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		
		// Check if the user is an author.
		if ( ! count_user_posts( $user_id ) ) {
			return;
		}
		
		// Purge author archive.
		$author_link = get_author_posts_url( $user_id );
		$this->purge_url_cache( $author_link );
	}

	/**
	 * Handle WooCommerce product updates.
	 *
	 * @param WC_Product      $product Product object.
	 * @param array|WC_Object $props   Updated properties.
	 */
	public function on_woocommerce_product_update( $product, $props = array() ) {
		// Get product ID.
		$product_id = is_object( $product ) ? $product->get_id() : $product;
		
		// Purge product cache.
		$this->purge_post_cache( $product_id );
		
		// Purge shop page.
		$shop_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : -1;
		if ( $shop_page_id > 0 ) {
			$this->purge_post_cache( $shop_page_id );
		}
		
		// Purge product category archives.
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_link = get_term_link( $term );
				if ( ! is_wp_error( $term_link ) ) {
					$this->purge_url_cache( $term_link );
				}
			}
		}
	}

	/**
	 * Add purge cache button to admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function add_admin_bar_purge( $wp_admin_bar ) {
		// Only show to users who can manage cache.
		if ( ! $this->current_user_can_manage_cache() ) {
			return;
		}
		
		// Add the main menu item.
		$wp_admin_bar->add_node(
			array(
				'id'    => 'sync-cache',
				'title' => __( 'SyncCache', 'sync-cache' ),
				'href'  => admin_url( 'options-general.php?page=sync_cache_settings' ),
			) 
		);
		
		// Add purge all cache.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'sync-cache',
				'id'     => 'sync-cache-purge-all',
				'title'  => __( 'Purge All Cache', 'sync-cache' ),
				'href'   => wp_nonce_url( admin_url( 'admin-post.php?action=sync_cache_purge' ), 'sync_cache_purge' ),
			) 
		);
		
		// If viewing a singular post, add purge this page.
		if ( is_singular() ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'sync-cache',
					'id'     => 'sync-cache-purge-page',
					'title'  => __( 'Purge This Page', 'sync-cache' ),
					'href'   => wp_nonce_url( admin_url( 'admin-post.php?action=sync_cache_purge&post_id=' . get_the_ID() ), 'sync_cache_purge' ),
				) 
			);
		}
	}

	/**
	 * Add admin menu items.
	 */
	public function add_admin_menu() {
		// Only show to users who can manage cache.
		if ( ! $this->current_user_can_manage_cache() ) {
			return;
		}
		
		// Add settings page.
		add_options_page(
			__( 'SyncCache Settings', 'sync-cache' ),
			__( 'SyncCache', 'sync-cache' ),
			'manage_options',
			'sync_cache_settings',
			array( $this, 'render_settings_page' )
		);
		
		// Add dashboard page for stats.
		add_dashboard_page(
			__( 'SyncCache Statistics', 'sync-cache' ),
			__( 'Cache Statistics', 'sync-cache' ),
			'manage_options',
			'sync_cache_stats',
			array( $this, 'render_stats_page' )
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page.
	 */
	public function admin_enqueue_scripts( $hook ) {
		// Only load on our settings pages.
		if ( 'settings_page_sync_cache_settings' !== $hook && 'dashboard_page_sync_cache_stats' !== $hook ) {
			return;
		}
		
		// Enqueue necessary scripts.
		wp_enqueue_style( 'sync-cache-admin', plugin_dir_url( __FILE__ ) . 'assets/css/sync-admin.css', array(), '1.0.0' );
		wp_enqueue_script( 'sync-cache-admin', plugin_dir_url( __FILE__ ) . 'assets/js/sync-admin.js', array( 'jquery' ), '1.0.0', true );
		
		// Add localization data.
		wp_localize_script(
			'sync-cache-admin',
			'sync_cache',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'sync_cache_ajax' ),
				'purge_text' => __( 'Purging Cache...', 'sync-cache' ),
				'done_text'  => __( 'Cache Purged!', 'sync-cache' ),
			) 
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		// Implement settings page content here.
		include plugin_dir_path( __FILE__ ) . 'templates/settings-page.php';
	}

	/**
	 * Render stats page.
	 */
	public function render_stats_page() {
		// Implement stats page content here.
		include plugin_dir_path( __FILE__ ) . 'templates/stats-page.php';
	}

	/**
	 * Handle manual cache purge.
	 */
	public function handle_manual_purge() {
		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'sync_cache_purge' ) ) {
			wp_die( __( 'Security check failed.', 'sync-cache' ) );
		}
		
		// Verify user capabilities.
		if ( ! $this->current_user_can_manage_cache() ) {
			wp_die( __( 'You do not have permission to purge the cache.', 'sync-cache' ) );
		}
		
		// Check if we're purging a specific post.
		if ( isset( $_GET['post_id'] ) ) {
			$post_id = intval( $_GET['post_id'] );
			$this->purge_post_cache( $post_id );
			
			// Redirect back to the post.
			wp_safe_redirect( get_permalink( $post_id ) );
			exit;
		}
		
		// Purge all cache.
		$this->purge_all_cache();
		
		// Redirect back to the referer.
		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}

	/**
	 * Handle AJAX cache purge.
	 */
	public function ajax_purge_cache() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sync_cache_ajax' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sync-cache' ) ) );
		}
		
		// Verify user capabilities.
		if ( ! $this->current_user_can_manage_cache() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to purge the cache.', 'sync-cache' ) ) );
		}
		
		// Check if we're purging a specific post.
		if ( isset( $_POST['post_id'] ) ) {
			$post_id = intval( $_POST['post_id'] );
			$success = $this->purge_post_cache( $post_id );
			
			if ( $success ) {
				wp_send_json_success( array( 'message' => __( 'Cache purged for this post.', 'sync-cache' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to purge cache.', 'sync-cache' ) ) );
			}
		}
		
		// Purge all cache.
		$success = $this->purge_all_cache();
		
		if ( $success ) {
			wp_send_json_success( array( 'message' => __( 'All cache purged successfully.', 'sync-cache' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to purge cache.', 'sync-cache' ) ) );
		}
	}

	/**
	 * Check if current user can manage cache.
	 *
	 * @return bool Whether current user can manage cache.
	 */
	private function current_user_can_manage_cache() {
		// Administrators can always manage cache.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		
		// Get allowed roles.
		$allowed_roles = isset( $this->options['admin_roles_manage_cache'] ) ? $this->options['admin_roles_manage_cache'] : array( 'administrator', 'editor' );
		
		// Check if user has any of the allowed roles.
		$user = wp_get_current_user();
		if ( $user && ! empty( $user->roles ) ) {
			foreach ( $user->roles as $role ) {
				if ( in_array( $role, $allowed_roles, true ) ) {
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Purge all cache.
	 *
	 * @return bool Whether the cache was purged.
	 */
	public function purge_all_cache() {
		// Get the cache directory.
		$cache_path = isset( $this->options['cache_path'] ) ? $this->options['cache_path'] : null;
		
		if ( empty( $cache_path ) || '{{DATA_STORAGE_PATH}}' === $cache_path ) {
			// Use fallback path.
			$fallback_path = isset( $this->options['fallback_path'] ) ? $this->options['fallback_path'] : 'wp-content/cache/sync-cache';
			
			// Try to use WP_CONTENT_DIR if available.
			if ( defined( 'WP_CONTENT_DIR' ) ) {
				$cache_path = WP_CONTENT_DIR . '/cache/sync-cache';
			} else {
				$cache_path = ABSPATH . $fallback_path;
			}
		}
		
		// Skip statistics directory if it exists.
		$stats_dir = $cache_path . '/stats';
		
		// Check if the directory exists.
		if ( ! is_dir( $cache_path ) ) {
			return false;
		}
		
		// Get all items in the directory except stats.
		$items = glob( $cache_path . '/*' );
		
		// Remove each item.
		foreach ( $items as $item ) {
			// Skip stats directory.
			if ( $item === $stats_dir ) {
				continue;
			}
			
			$this->recursive_rmdir( $item );
		}
		
		// Purge CDN if configured.
		$this->purge_cdn();
		
		// Log cache purge.
		$this->log( 'Purged all cache' );
		
		return true;
	}

	/**
	 * Purge cache for a specific post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool Whether the cache was purged.
	 */
	public function purge_post_cache( $post_id ) {
		// Get post.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}
		
		// Purge the post URL.
		$permalink = get_permalink( $post_id );
		$this->purge_url_cache( $permalink );
		
		// Purge pagination URLs.
		$this->purge_pagination_urls( $permalink );
		
		// Purge feeds.
		$this->purge_feeds();
		
		// Purge home page.
		$this->purge_home_cache();
		
		// Purge category archives.
		$categories = get_the_category( $post_id );
		if ( $categories ) {
			foreach ( $categories as $category ) {
				$category_link = get_category_link( $category->term_id );
				$this->purge_url_cache( $category_link );
				$this->purge_pagination_urls( $category_link );
			}
		}
		
		// Purge tag archives.
		$tags = get_the_tags( $post_id );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$tag_link = get_tag_link( $tag->term_id );
				$this->purge_url_cache( $tag_link );
				$this->purge_pagination_urls( $tag_link );
			}
		}
		
		// Purge author archive.
		$author_link = get_author_posts_url( $post->post_author );
		$this->purge_url_cache( $author_link );
		$this->purge_pagination_urls( $author_link );
		
		// Purge date archives.
		$this->purge_date_archives( $post );
		
		// Log cache purge.
		$this->log( 'Purged cache for post: ' . $post_id . ' - ' . $post->post_title );
		
		return true;
	}

	/**
	 * Purge cache for a specific URL.
	 *
	 * @param string $url URL to purge.
	 * @return bool Whether the cache was purged.
	 */
	public function purge_url_cache( $url ) {
		// Skip if URL is empty.
		if ( empty( $url ) ) {
			return false;
		}
		
		// Parse URL.
		$parsed_url = parse_url( $url );
		
		// Get the path.
		$path = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '/';
		
		// Clean up path.
		$path = ltrim( $path, '/' );
		
		// If it's the root, use "home".
		if ( empty( $path ) ) {
			$path = 'home';
		}
		
		// Get domain.
		$domain = isset( $parsed_url['host'] ) ? $parsed_url['host'] : $_SERVER['HTTP_HOST'];
		$domain = str_replace( ':', '-', $domain ); // Replace colon with dash for Windows compatibility.
		
		// Get cache path.
		$cache_path = $this->get_cache_path();
		
		// Build the cache directory path.
		$cache_dir = $cache_path . '/' . $domain;
		
		// Check if the directory exists.
		if ( ! is_dir( $cache_dir ) ) {
			return false;
		}
		
		// Purge for all device types.
		$device_types = array( 'desktop', 'mobile', 'tablet' );
		
		foreach ( $device_types as $device ) {
			$device_path = $cache_dir . '/' . $device . '/' . $path;
			$this->recursive_rmdir( $device_path );
		}
		
		// Also purge any user role specific caches.
		$user_cache_path = $cache_dir . '/users/';
		if ( is_dir( $user_cache_path ) ) {
			$role_dirs = glob( $user_cache_path . '*' );
			foreach ( $role_dirs as $role_dir ) {
				$role_path = $role_dir . '/' . $path;
				$this->recursive_rmdir( $role_path );
			}
		}
		
		// Also purge any language specific caches.
		$lang_pattern = $cache_dir . '/lang-*/';
		$lang_dirs    = glob( $lang_pattern );
		if ( $lang_dirs ) {
			foreach ( $lang_dirs as $lang_dir ) {
				$lang_path = $lang_dir . '/' . $path;
				$this->recursive_rmdir( $lang_path );
			}
		}
		
		return true;
	}

	/**
	 * Purge pagination URLs.
	 *
	 * @param string $url Base URL.
	 * @return bool Whether the cache was purged.
	 */
	public function purge_pagination_urls( $url ) {
		// Parse URL.
		$parsed_url = parse_url( $url );
		
		// Get the path.
		$path = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '/';
		
		// Purge common pagination URLs.
		for ( $i = 1; $i <= 5; $i++ ) {
			$paginated_url = $path . 'page/' . $i . '/';
			$this->purge_url_cache( $paginated_url );
		}
		
		return true;
	}

	/**
	 * Purge home page cache.
	 *
	 * @return bool Whether the cache was purged.
	 */
	public function purge_home_cache() {
		// Purge home URL.
		$home_url = home_url( '/' );
		$this->purge_url_cache( $home_url );
		
		// Purge pagination.
		$this->purge_pagination_urls( $home_url );
		
		return true;
	}

	/**
	 * Purge date archives.
	 *
	 * @param WP_Post $post Post object.
	 * @return bool Whether the cache was purged.
	 */
	public function purge_date_archives( $post ) {
		// Get the post date.
		$year  = get_the_time( 'Y', $post );
		$month = get_the_time( 'm', $post );
		$day   = get_the_time( 'd', $post );
		
		// Year archive.
		$year_link = get_year_link( $year );
		$this->purge_url_cache( $year_link );
		$this->purge_pagination_urls( $year_link );
		
		// Month archive.
		$month_link = get_month_link( $year, $month );
		$this->purge_url_cache( $month_link );
		$this->purge_pagination_urls( $month_link );
		
		// Day archive.
		$day_link = get_day_link( $year, $month, $day );
		$this->purge_url_cache( $day_link );
		$this->purge_pagination_urls( $day_link );
		
		return true;
	}

	/**
	 * Purge feed URLs.
	 *
	 * @return bool Whether the cache was purged.
	 */
	public function purge_feeds() {
		// Main feed.
		$feed_url = get_feed_link();
		$this->purge_url_cache( $feed_url );
		
		// Comments feed.
		$comments_feed_url = get_feed_link( 'comments_' );
		$this->purge_url_cache( $comments_feed_url );
		
		return true;
	}

	/**
	 * Purge all archive pages.
	 *
	 * @return bool Whether the cache was purged.
	 */
	public function purge_archives() {
		// Category archives.
		$categories = get_categories();
		if ( $categories ) {
			foreach ( $categories as $category ) {
				$category_link = get_category_link( $category->term_id );
				$this->purge_url_cache( $category_link );
				$this->purge_pagination_urls( $category_link );
			}
		}
		
		// Tag archives.
		$tags = get_tags();
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$tag_link = get_tag_link( $tag->term_id );
				$this->purge_url_cache( $tag_link );
				$this->purge_pagination_urls( $tag_link );
			}
		}
		
		// Author archives.
		$authors = get_users( array( 'who' => 'authors' ) );
		if ( $authors ) {
			foreach ( $authors as $author ) {
				$author_link = get_author_posts_url( $author->ID );
				$this->purge_url_cache( $author_link );
				$this->purge_pagination_urls( $author_link );
			}
		}
		
		return true;
	}

	/**
	 * Purge CDN cache if configured.
	 *
	 * @return bool Whether the CDN cache was purged.
	 */
	public function purge_cdn() {
		// Check if CDN is enabled.
		if ( empty( $this->options['cdn_enabled'] ) ) {
			return false;
		}
		
		// Check if we have a CDN URL.
		$cdn_url = isset( $this->options['cdn_url'] ) ? $this->options['cdn_url'] : '';
		if ( empty( $cdn_url ) || '{{CDN_URL}}' === $cdn_url ) {
			return false;
		}
		
		// Get CDN provider from URL (simple detection).
		$cdn_provider = '';
		if ( strpos( $cdn_url, 'cloudfront.net' ) !== false ) {
			$cdn_provider = 'cloudfront';
		} elseif ( strpos( $cdn_url, 'cloudflare.com' ) !== false ) {
			$cdn_provider = 'cloudflare';
		} elseif ( strpos( $cdn_url, 'akamai' ) !== false ) {
			$cdn_provider = 'akamai';
		} elseif ( strpos( $cdn_url, 'fastly.net' ) !== false ) {
			$cdn_provider = 'fastly';
		}
		
		// Log CDN purge.
		$this->log( 'CDN purge attempted for: ' . $cdn_provider );
		
		// In a real implementation, you'd have API calls to purge each CDN type.
		// This is just a placeholder.
		
		return true;
	}

	/**
	 * Do cache cleanup.
	 */
	public function do_cache_cleanup() {
		// Get cache path.
		$cache_path = $this->get_cache_path();
		
		// Check if the directory exists.
		if ( ! is_dir( $cache_path ) ) {
			return;
		}
		
		// Get cache lifetime.
		$lifetime = isset( $this->options['cache_lifetime'] ) ? (int) $this->options['cache_lifetime'] : 86400;
		
		// Get all files.
		$files = $this->get_all_cache_files( $cache_path );
		
		// Current time.
		$now = time();
		
		// Delete expired files.
		$deleted_count = 0;
		foreach ( $files as $file ) {
			// Skip directories and non-HTML files.
			if ( is_dir( $file ) || pathinfo( $file, PATHINFO_EXTENSION ) !== 'html' ) {
				continue;
			}
			
			// Get modification time.
			$mtime = filemtime( $file );
			
			// Delete if expired.
			if ( ( $now - $mtime ) > $lifetime ) {
				if ( @unlink( $file ) ) {
					++$deleted_count;
				}
			}
		}
		
		// Log cleanup.
		$this->log( 'Cache cleanup: deleted ' . $deleted_count . ' expired files' );
	}

	/**
	 * Get all cache files recursively.
	 *
	 * @param string $dir Directory to scan.
	 * @return array Array of file paths.
	 */
	private function get_all_cache_files( $dir ) {
		$files = array();
		
		// Skip if directory doesn't exist.
		if ( ! is_dir( $dir ) ) {
			return $files;
		}
		
		// Get all items.
		$items = scandir( $dir );
		
		foreach ( $items as $item ) {
			// Skip dots.
			if ( $item === '.' || $item === '..' ) {
				continue;
			}
			
			$path = $dir . '/' . $item;
			
			// Skip stats directory.
			if ( is_dir( $path ) && basename( $path ) === 'stats' ) {
				continue;
			}
			
			// Add files.
			if ( is_file( $path ) ) {
				$files[] = $path;
			} elseif ( is_dir( $path ) ) {
				// Recursively get files from subdirectories.
				$files = array_merge( $files, $this->get_all_cache_files( $path ) );
			}
		}
		
		return $files;
	}

	/**
	 * Do cache preload.
	 */
	public function do_cache_preload() {
		// Check if preloading is enabled.
		if ( empty( $this->options['preload_homepage'] ) && empty( $this->options['preload_public_posts'] ) && empty( $this->options['preload_public_taxonomies'] ) ) {
			return;
		}
		
		// URLs to preload.
		$urls = array();
		
		// Add home page.
		if ( ! empty( $this->options['preload_homepage'] ) ) {
			$urls[] = home_url( '/' );
		}
		
		// Add public posts.
		if ( ! empty( $this->options['preload_public_posts'] ) ) {
			// Get latest public posts.
			$posts = get_posts(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => 10,
				) 
			);
			
			foreach ( $posts as $post ) {
				$urls[] = get_permalink( $post->ID );
			}
			
			// Get latest public pages.
			$pages = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'posts_per_page' => 10,
				) 
			);
			
			foreach ( $pages as $page ) {
				$urls[] = get_permalink( $page->ID );
			}
		}
		
		// Add public taxonomies.
		if ( ! empty( $this->options['preload_public_taxonomies'] ) ) {
			// Get categories.
			$categories = get_categories();
			foreach ( $categories as $category ) {
				$urls[] = get_category_link( $category->term_id );
			}
			
			// Get tags.
			$tags = get_tags();
			foreach ( $tags as $tag ) {
				$urls[] = get_tag_link( $tag->term_id );
			}
		}
		
		// Preload URLs.
		$this->preload_urls( $urls );
	}

	/**
	 * Preload a list of URLs.
	 *
	 * @param array $urls URLs to preload.
	 */
	private function preload_urls( $urls ) {
		// Skip if no URLs.
		if ( empty( $urls ) ) {
			return;
		}
		
		// Get site URL.
		$site_url = site_url();
		
		// Get warmup method.
		$warmup_method = isset( $this->options['warmup_method'] ) ? $this->options['warmup_method'] : 'auto';
		
		// Use background processing for 'auto' or 'background' methods.
		if ( $warmup_method === 'auto' || $warmup_method === 'background' ) {
			// Schedule URLs for background processing.
			foreach ( $urls as $url ) {
				wp_schedule_single_event( time() + mt_rand( 10, 300 ), 'sync_cache_preload_url', array( $url ) );
			}
			
			// Log preload.
			$this->log( 'Scheduled ' . count( $urls ) . ' URLs for background preloading' );
		} else {
			// Direct preloading.
			$count = 0;
			foreach ( $urls as $url ) {
				// Make a request to the URL.
				$response = wp_remote_get(
					$url,
					array(
						'timeout'     => 10,
						'redirection' => 5,
						'sslverify'   => false,
						'user-agent'  => 'SyncCache Preloader',
					) 
				);
				
				// Skip if error.
				if ( is_wp_error( $response ) ) {
					continue;
				}
				
				// Skip if not 200 OK.
				if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
					continue;
				}
				
				++$count;
			}
			
			// Log preload.
			$this->log( 'Preloaded ' . $count . ' URLs directly' );
		}
	}

	/**
	 * Get cache path.
	 *
	 * @return string Cache path.
	 */
	private function get_cache_path() {
		// Get cache path from options.
		$cache_path = isset( $this->options['cache_path'] ) ? $this->options['cache_path'] : null;
		
		if ( empty( $cache_path ) || '{{DATA_STORAGE_PATH}}' === $cache_path ) {
			// Use fallback path.
			$fallback_path = isset( $this->options['fallback_path'] ) ? $this->options['fallback_path'] : 'wp-content/cache/sync-cache';
			
			// Try to use WP_CONTENT_DIR if available.
			if ( defined( 'WP_CONTENT_DIR' ) ) {
				$cache_path = WP_CONTENT_DIR . '/cache/sync-cache';
			} else {
				$cache_path = ABSPATH . $fallback_path;
			}
		}
		
		return rtrim( $cache_path, '/' );
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $dir Directory path.
	 * @return bool Whether the directory was deleted.
	 */
	private function recursive_rmdir( $dir ) {
		// Skip if not a directory.
		if ( is_file( $dir ) ) {
			@unlink( $dir );
			return true;
		}
		
		if ( ! is_dir( $dir ) ) {
			return false;
		}
		
		// Get all items.
		$items = scandir( $dir );
		
		foreach ( $items as $item ) {
			// Skip dots.
			if ( $item === '.' || $item === '..' ) {
				continue;
			}
			
			$path = $dir . '/' . $item;
			
			if ( is_dir( $path ) ) {
				// Recursively delete subdirectory.
				$this->recursive_rmdir( $path );
			} else {
				// Delete file.
				@unlink( $path );
			}
		}
		
		// Delete directory.
		@rmdir( $dir );
		
		return true;
	}

	/**
	 * Log message.
	 *
	 * @param string $message Message to log.
	 */
	private function log( $message ) {
		// Skip if logging is disabled.
		if ( empty( $this->options['enable_logging'] ) ) {
			return;
		}
		
		// Get log file path.
		$log_dir = $this->get_cache_path() . '/logs';
		
		// Create log directory if it doesn't exist.
		if ( ! is_dir( $log_dir ) ) {
			@mkdir( $log_dir, 0755, true );
		}
		
		// Get log file path.
		$log_file = $log_dir . '/cache-' . date( 'Y-m-d' ) . '.log';
		
		// Append to log file.
		@file_put_contents(
			$log_file,
			date( 'Y-m-d H:i:s' ) . ' - ' . $message . "\n",
			FILE_APPEND
		);
	}
}
