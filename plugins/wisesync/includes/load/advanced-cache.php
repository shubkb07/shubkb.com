<?php
/**
 * WiseSync Advanced Cache
 *
 * Plugin Name:       WiseSync Advanced Cache
 * Description:       A smart caching plugin for WordPress, designed to optimize performance and enhance user experience. It intelligently caches pages, handles cache purging, and provides advanced features for efficient content delivery.
 * Plugin URI:        https://shubkb.com
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Author:            Shubham Kumar Bansal <shub@shubkb.com>
 * Author URI:        https://shubkb.com
 * License:           Apache License 2.0
 * License URI:       https://www.apache.org/licenses/LICENSE-2.0
 *
 * @package WiseSync
 * @since 1.0.0
 **/

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access.' );
}

// Define cache constants if not already defined.
if ( ! defined( 'SYNC_CACHE_LOADED' ) ) {
	define( 'SYNC_CACHE_LOADED', true );
}

/**
 * Configuration JSON - This will be replaced during setup.
 * Do not modify this directly - the plugin will update it.
 */
$sync_cache_config = '{
	"cache_enabled": true,
	"cache_path": "{{DATA_STORAGE_PATH}}",
	"fallback_path": "wp-content/cache/sync-cache",
	"cache_lifetime": 86400,
	"cache_exclude_urls": [
		"/wp-admin/",
		"/wp-login.php",
		"/cart",
		"/checkout",
		"/my-account"
	],
	"cache_exclude_cookies": [
		"wp-postpass_",
		"wordpress_logged_in_",
		"comment_author_",
		"woocommerce_items_in_cart"
	],
	"cache_exclude_user_agents": [
		"bot",
		"crawler",
		"spider"
	],
	"cache_mobile": true,
	"cache_tablet": true,
	"separate_mobile_cache": true,
	"cache_logged_in_users": false,
	"cache_ssl": true,
	"cache_404": false,
	"cache_query_strings": false,
	"allowed_query_strings": [
		"s",
		"p",
		"lang"
	],
	"cache_rest_api": false,
	"cache_ajax": false,
	"cache_feed": false,
	"purge_on_post_edit": true,
	"purge_on_comment": true,
	"purge_schedule": "daily",
	"enable_in_dev_mode": false,
	"enable_logging": true,
	"debug_mode": false,
	"minify_html": true,
	"minify_css": true,
	"minify_js": true,
	"combine_css": true,
	"combine_js": true,
	"lazy_load": true,
	"cdn_enabled": false,
	"cdn_url": "{{CDN_URL}}",
	"cdn_includes": [
		".jpg",
		".jpeg",
		".png",
		".gif",
		".webp",
		".svg",
		".css",
		".js"
	],
	"warmup_method": "auto",
	"iops_protection": true,
	"max_files_per_second": 100,
	"admin_roles_manage_cache": [
		"administrator",
		"editor"
	],
	"cache_analytics": true,
	"analytics_sampling_rate": 10,
	"preload_homepage": true,
	"preload_public_posts": true,
	"preload_public_taxonomies": true
}';

// Parse configuration.
$sync_config = json_decode( $sync_cache_config, true );

// Bail if configuration is invalid.
if ( ! is_array( $sync_config ) ) {
	// Log error if enabled.
	error_log( 'SyncCache: Invalid configuration JSON.' );
	return;
}

/**
 * SyncCache main class.
 */
class SyncCache {
	/**
	 * Configuration array.
	 *
	 * @var array
	 */
	private $config = array();

	/**
	 * Cache path.
	 *
	 * @var string
	 */
	private $cache_path = '';

	/**
	 * Request URI.
	 *
	 * @var string
	 */
	private $request_uri = '';

	/**
	 * Request method.
	 *
	 * @var string
	 */
	private $request_method = '';

	/**
	 * Is mobile device.
	 *
	 * @var bool
	 */
	private $is_mobile = false;

	/**
	 * Is tablet device.
	 *
	 * @var bool
	 */
	private $is_tablet = false;

	/**
	 * Is user logged in.
	 *
	 * @var bool
	 */
	private $is_logged_in = false;

	/**
	 * User role.
	 *
	 * @var string
	 */
	private $user_role = 'guest';

	/**
	 * User language.
	 *
	 * @var string
	 */
	private $user_language = 'en';

	/**
	 * Cache file path.
	 *
	 * @var string
	 */
	private $cache_file = '';

	/**
	 * Cache key.
	 *
	 * @var string
	 */
	private $cache_key = '';

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration array.
	 */
	public function __construct( $config ) {
		$this->config         = $config;
		$this->request_uri    = $this->get_request_uri();
		$this->request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

		// Set cache path.
		$this->set_cache_path();

		// Check if caching is enabled for this environment.
		if ( ! $this->should_enable_cache() ) {
			return;
		}

		// Detect device type.
		$this->detect_device();

		// Detect user status.
		$this->detect_user_status();

		// Detect user language.
		$this->detect_language();

		// Try to serve cached page.
		$this->maybe_serve_cached_page();
	}

	/**
	 * Set cache path based on configuration.
	 */
	private function set_cache_path() {
		$path = $this->config['cache_path'] ?? null;
		
		if ( empty( $path ) || '{{DATA_STORAGE_PATH}}' === $path ) {
			// Use fallback path.
			$path = $this->config['fallback_path'] ?? 'wp-content/cache/sync-cache';
			
			// Try to use WP_CONTENT_DIR if available.
			if ( defined( 'WP_CONTENT_DIR' ) ) {
				$path = WP_CONTENT_DIR . '/cache/sync-cache';
			} else {
				// Determine if we're in the root or in wp-content.
				$script_path = str_replace( '\\', '/', dirname( $_SERVER['SCRIPT_FILENAME'] ) );
				if ( strpos( $script_path, 'wp-content' ) !== false ) {
					$path = dirname( $script_path ) . '/cache/sync-cache';
				} else {
					$path = $script_path . '/wp-content/cache/sync-cache';
				}
			}
		}

		// Ensure path has trailing slash.
		$path = rtrim( $path, '/' ) . '/';
		
		$this->cache_path = $path;
	}

	/**
	 * Determine if we should enable caching for this environment.
	 *
	 * @return bool Whether caching should be enabled.
	 */
	private function should_enable_cache() {
		// Check if caching is enabled in config.
		if ( empty( $this->config['cache_enabled'] ) ) {
			return false;
		}

		// Check for VIP Go environment.
		if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
			// Always cache on VIP Go.
			return true;
		}

		// Check if we're in development mode.
		$is_dev_environment = false;

		// Common development environment checks.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG || 
			defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ||
			isset( $_SERVER['REMOTE_ADDR'] ) && in_array( $_SERVER['REMOTE_ADDR'], array( '127.0.0.1', '::1' ), true ) ||
			( isset( $_SERVER['HTTP_HOST'] ) && preg_match( '/local(host)?|dev(elopment)?|\.(test|dev)$/', $_SERVER['HTTP_HOST'] ) )
		) {
			$is_dev_environment = true;
		}

		// Check for specific environment constants.
		if ( ( defined( 'WP_ENVIRONMENT_TYPE' ) && in_array( WP_ENVIRONMENT_TYPE, array( 'local', 'development', 'staging' ), true ) ) ||
			( defined( 'VIP_GO_ENV' ) && in_array( VIP_GO_ENV, array( 'develop', 'preprod', 'local', 'dev' ), true ) )
		) {
			$is_dev_environment = true;
		}

		// If we're in a dev environment and dev caching is disabled.
		if ( $is_dev_environment && empty( $this->config['enable_in_dev_mode'] ) ) {
			// Check for force cache constant.
			if ( ! defined( 'SYNC_FORCE_LOCAL_CACHE' ) || ! SYNC_FORCE_LOCAL_CACHE ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get clean request URI.
	 *
	 * @return string Cleaned request URI.
	 */
	private function get_request_uri() {
		$uri = $_SERVER['REQUEST_URI'] ?? '/';
		
		// Remove query string if not caching them.
		if ( empty( $this->config['cache_query_strings'] ) && strpos( $uri, '?' ) !== false ) {
			$allowed_params = array();
			
			// Check if we have allowed query strings.
			if ( ! empty( $this->config['allowed_query_strings'] ) && is_array( $this->config['allowed_query_strings'] ) ) {
				$query_string = parse_url( $uri, PHP_URL_QUERY );
				if ( $query_string ) {
					parse_str( $query_string, $query_params );
					
					// Keep only allowed parameters.
					foreach ( $this->config['allowed_query_strings'] as $param ) {
						if ( isset( $query_params[ $param ] ) ) {
							$allowed_params[ $param ] = $query_params[ $param ];
						}
					}
				}
			}
			
			// Remove query string.
			$uri = parse_url( $uri, PHP_URL_PATH );
			
			// Add back allowed parameters if any.
			if ( ! empty( $allowed_params ) ) {
				$uri .= '?' . http_build_query( $allowed_params );
			}
		}
		
		return $uri;
	}

	/**
	 * Detect device type.
	 */
	private function detect_device() {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return;
		}

		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		
		// Check for mobile.
		if ( $this->config['cache_mobile'] && preg_match( '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $user_agent ) || preg_match( '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr( $user_agent, 0, 4 ) ) ) {
			$this->is_mobile = true;
		}
		
		// Check for tablet.
		if ( $this->config['cache_tablet'] && ! $this->is_mobile && preg_match( '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i', $user_agent ) ) {
			$this->is_tablet = true;
		}
	}

	/**
	 * Detect user login status and role.
	 */
	private function detect_user_status() {
		if ( ! empty( $_COOKIE ) ) {
			foreach ( $_COOKIE as $key => $value ) {
				if ( strpos( $key, 'wordpress_logged_in_' ) === 0 ) {
					$this->is_logged_in = true;
					
					// Try to extract user role from cookie.
					$cookie_parts = explode( '|', $value );
					if ( isset( $cookie_parts[3] ) ) {
						$this->user_role = $cookie_parts[3];
					} else {
						$this->user_role = 'subscriber'; // Default role.
					}
					
					break;
				}
			}
		}
	}

	/**
	 * Detect user language.
	 */
	private function detect_language() {
		// Check for language in URL/query params first (for WPML, Polylang, etc.).
		if ( isset( $_GET['lang'] ) ) {
			$this->user_language = sanitize_text_field( $_GET['lang'] );
			return;
		}
		
		// Check for language in cookie.
		if ( isset( $_COOKIE['pll_language'] ) ) {
			$this->user_language = sanitize_text_field( $_COOKIE['pll_language'] );
			return;
		}
		
		// Check Accept-Language header.
		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$langs = explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
			if ( ! empty( $langs ) ) {
				$lang                = explode( ';', $langs[0] );
				$this->user_language = substr( $lang[0], 0, 2 );
			}
		}
	}

	/**
	 * Check if the current request should be cached.
	 *
	 * @return bool Whether to cache the current request.
	 */
	private function should_cache_request() {
		// Don't cache non-GET requests.
		if ( $this->request_method !== 'GET' ) {
			return false;
		}

		// Don't cache if page shouldn't be cached based on URL.
		if ( $this->is_excluded_url() ) {
			return false;
		}

		// Don't cache if user is logged in and we're not caching for logged-in users.
		if ( $this->is_logged_in && empty( $this->config['cache_logged_in_users'] ) ) {
			return false;
		}

		// Don't cache REST API requests if not configured to do so.
		if ( empty( $this->config['cache_rest_api'] ) && $this->is_rest_request() ) {
			return false;
		}

		// Don't cache AJAX requests if not configured to do so.
		if ( empty( $this->config['cache_ajax'] ) && $this->is_ajax_request() ) {
			return false;
		}

		// Don't cache feed requests if not configured to do so.
		if ( empty( $this->config['cache_feed'] ) && $this->is_feed_request() ) {
			return false;
		}

		// Don't cache if user has excluded cookie.
		if ( $this->has_excluded_cookie() ) {
			return false;
		}

		// Don't cache if user agent is excluded.
		if ( $this->is_excluded_user_agent() ) {
			return false;
		}

		// Don't cache 404 pages if not configured to do so.
		if ( empty( $this->config['cache_404'] ) && $this->is_404_request() ) {
			return false;
		}

		// Don't cache if SSL is forced and we're not using SSL.
		if ( $this->config['cache_ssl'] && $this->is_ssl() && ! $this->is_ssl_request() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the current URL is excluded from caching.
	 *
	 * @return bool Whether URL is excluded.
	 */
	private function is_excluded_url() {
		if ( empty( $this->config['cache_exclude_urls'] ) || ! is_array( $this->config['cache_exclude_urls'] ) ) {
			return false;
		}

		foreach ( $this->config['cache_exclude_urls'] as $excluded_url ) {
			if ( strpos( $this->request_uri, $excluded_url ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if this is a REST API request.
	 *
	 * @return bool Whether this is a REST API request.
	 */
	private function is_rest_request() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if ( strpos( $this->request_uri, '/wp-json/' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if this is an AJAX request.
	 *
	 * @return bool Whether this is an AJAX request.
	 */
	private function is_ajax_request() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}

		if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) === 'xmlhttprequest' ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if this is a feed request.
	 *
	 * @return bool Whether this is a feed request.
	 */
	private function is_feed_request() {
		if ( strpos( $this->request_uri, '/feed/' ) !== false || strpos( $this->request_uri, '/feed' ) === strlen( $this->request_uri ) - 5 ) {
			return true;
		}

		if ( ! empty( $_GET['feed'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if user has a cookie that should exclude them from caching.
	 *
	 * @return bool Whether user has excluded cookie.
	 */
	private function has_excluded_cookie() {
		if ( empty( $this->config['cache_exclude_cookies'] ) || ! is_array( $this->config['cache_exclude_cookies'] ) ) {
			return false;
		}

		foreach ( $_COOKIE as $key => $value ) {
			foreach ( $this->config['cache_exclude_cookies'] as $excluded_cookie ) {
				if ( strpos( $key, $excluded_cookie ) === 0 ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if user agent is excluded from caching.
	 *
	 * @return bool Whether user agent is excluded.
	 */
	private function is_excluded_user_agent() {
		if ( empty( $this->config['cache_exclude_user_agents'] ) || ! is_array( $this->config['cache_exclude_user_agents'] ) ) {
			return false;
		}

		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		$user_agent = strtolower( $_SERVER['HTTP_USER_AGENT'] );

		foreach ( $this->config['cache_exclude_user_agents'] as $excluded_agent ) {
			if ( strpos( $user_agent, strtolower( $excluded_agent ) ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if this is a 404 request.
	 *
	 * @return bool Whether this is a 404 request.
	 */
	private function is_404_request() {
		// Since we're running before WordPress, we can't use is_404().
		// This is a very basic check that might need enhancement.
		$status_header = $_SERVER['REDIRECT_STATUS'] ?? null;
		return $status_header === '404';
	}

	/**
	 * Check if SSL is enabled for the site.
	 *
	 * @return bool Whether SSL is enabled.
	 */
	private function is_ssl() {
		if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if current request is via SSL.
	 *
	 * @return bool Whether request is via SSL.
	 */
	private function is_ssl_request() {
		if ( isset( $_SERVER['HTTPS'] ) && ( $_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1' ) ) {
			return true;
		}

		if ( isset( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] === '443' ) {
			return true;
		}

		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
			return true;
		}

		return false;
	}

	/**
	 * Try to serve cached page if available.
	 */
	private function maybe_serve_cached_page() {
		// Check if request should be cached.
		if ( ! $this->should_cache_request() ) {
			// If not cacheable, set up for cache creation if WordPress decides this is a 200 response.
			$this->setup_cache_creation();
			return;
		}

		// Generate cache key and file path.
		$this->generate_cache_key();
		$this->set_cache_file_path();

		// Try to serve from cache.
		if ( $this->serve_cache_file() ) {
			// Served from cache, stop execution.
			exit;
		}

		// Cache miss or invalid cache, set up for cache creation.
		$this->setup_cache_creation();
	}

	/**
	 * Generate unique cache key for the current request.
	 */
	private function generate_cache_key() {
		$key_parts = array(
			'url'      => $this->request_uri,
			'ssl'      => $this->is_ssl_request() ? 'ssl' : 'non-ssl',
			'language' => $this->user_language,
		);

		// Add device type to key if separate mobile caching is enabled.
		if ( ! empty( $this->config['separate_mobile_cache'] ) ) {
			if ( $this->is_mobile ) {
				$key_parts['device'] = 'mobile';
			} elseif ( $this->is_tablet ) {
				$key_parts['device'] = 'tablet';
			} else {
				$key_parts['device'] = 'desktop';
			}
		}

		// Add user role if caching for logged-in users.
		if ( ! empty( $this->config['cache_logged_in_users'] ) && $this->is_logged_in ) {
			$key_parts['role'] = $this->user_role;
		}

		// Generate key by imploding parts with delimiter.
		$raw_key = implode( '_', $key_parts );

		// Use MD5 for consistent key length.
		$this->cache_key = md5( $raw_key );
	}

	/**
	 * Set cache file path based on cache key.
	 */
	private function set_cache_file_path() {
		// Create cache path with URL structure.
		$url_path = $this->request_uri;
		
		// Make sure it starts with a slash for consistency.
		if ( strpos( $url_path, '/' ) !== 0 ) {
			$url_path = '/' . $url_path;
		}
		
		// Remove trailing slash if not the root.
		if ( strlen( $url_path ) > 1 && substr( $url_path, -1 ) === '/' ) {
			$url_path = rtrim( $url_path, '/' );
		}
		
		// If it's the root, use "home".
		if ( $url_path === '/' ) {
			$url_path = '/home';
		}
		
		// Remove initial slash.
		$url_path = ltrim( $url_path, '/' );
		
		// Replace remaining slashes with directory separator.
		$url_path = str_replace( '/', DIRECTORY_SEPARATOR, $url_path );
		
		// Get domain for multisite support.
		$domain = $_SERVER['HTTP_HOST'] ?? 'default';
		$domain = str_replace( ':', '-', $domain ); // Replace colon with dash for Windows compatibility.
		
		// Create the cache file path with segmentation.
		$segments = array();
		
		// Add domain first.
		$segments[] = $domain;
		
		// Add device type if separate mobile caching is enabled.
		if ( ! empty( $this->config['separate_mobile_cache'] ) ) {
			if ( $this->is_mobile ) {
				$segments[] = 'mobile';
			} elseif ( $this->is_tablet ) {
				$segments[] = 'tablet';
			} else {
				$segments[] = 'desktop';
			}
		}
		
		// Add user role if caching for logged-in users.
		if ( ! empty( $this->config['cache_logged_in_users'] ) && $this->is_logged_in ) {
			$segments[] = 'role-' . $this->user_role;
		}
		
		// Add language if multiple languages.
		if ( $this->user_language !== 'en' ) {
			$segments[] = 'lang-' . $this->user_language;
		}
		
		// Add URL path last.
		$segments[] = $url_path;
		
		// Create the directory path.
		$directory = $this->cache_path . implode( DIRECTORY_SEPARATOR, $segments );
		
		// Set the full cache file path.
		$this->cache_file = $directory . DIRECTORY_SEPARATOR . $this->cache_key . '.html';
	}

	/**
	 * Serve cached file if it exists and is valid.
	 *
	 * @return bool Whether file was served.
	 */
	private function serve_cache_file() {
		// Check if cache file exists.
		if ( ! file_exists( $this->cache_file ) ) {
			return false;
		}

		// Read the cache file.
		$cache_data = $this->read_cache_file();
		if ( false === $cache_data ) {
			return false;
		}

		// Extract cache data.
		$cache_parts = $this->extract_cache_data( $cache_data );
		if ( false === $cache_parts ) {
			return false;
		}

		// Check if cache is expired.
		if ( $this->is_cache_expired( $cache_parts['created'] ) ) {
			// Try to serve stale cache during revalidation if configured.
			if ( ! empty( $this->config['serve_stale_while_revalidate'] ) ) {
				// Set up asynchronous revalidation.
				$this->trigger_async_cache_update();
				
				// Serve the stale content.
				$this->send_cached_headers( $cache_parts['created'], true );
				echo $cache_parts['content'];
				
				// Track cache stats if enabled.
				$this->track_cache_serve( 'stale' );
				
				return true;
			}
			
			return false;
		}

		// Cache is valid, serve it.
		$this->send_cached_headers( $cache_parts['created'] );
		echo $cache_parts['content'];
		
		// Track cache stats if enabled.
		$this->track_cache_serve( 'hit' );
		
		return true;
	}

	/**
	 * Read cache file.
	 *
	 * @return string|bool Cache file content or false on failure.
	 */
	private function read_cache_file() {
		// Use file_get_contents for simplicity and atomicity.
		$content = @file_get_contents( $this->cache_file );
		
		if ( false === $content || empty( $content ) ) {
			return false;
		}
		
		return $content;
	}

	/**
	 * Extract cache data from stored format.
	 *
	 * @param string $data Raw cache data.
	 * @return array|bool Extracted cache data or false on failure.
	 */
	private function extract_cache_data( $data ) {
		// Format: <!--SYNCCACHE-CREATED:timestamp-->content
		if ( ! preg_match( '/<!--SYNCCACHE-CREATED:(\d+)-->/', $data, $matches ) ) {
			return false;
		}
		
		$created = (int) $matches[1];
		$content = preg_replace( '/<!--SYNCCACHE-CREATED:\d+-->/', '', $data, 1 );
		
		return array(
			'created' => $created,
			'content' => $content,
		);
	}

	/**
	 * Check if cache is expired.
	 *
	 * @param int $created Timestamp when cache was created.
	 * @return bool Whether cache is expired.
	 */
	private function is_cache_expired( $created ) {
		$lifetime = (int) $this->config['cache_lifetime'];
		$now      = time();
		
		return ( $now - $created ) > $lifetime;
	}

	/**
	 * Send appropriate headers for cached content.
	 *
	 * @param int  $created Timestamp when cache was created.
	 * @param bool $is_stale Whether the content is stale.
	 */
	private function send_cached_headers( $created, $is_stale = false ) {
		// Send standard cache headers.
		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'X-SyncCache: HIT' . ( $is_stale ? '-STALE' : '' ) );
		
		// Set cache control headers based on lifetime.
		$max_age = (int) $this->config['cache_lifetime'];
		$age     = time() - $created;
		
		if ( $is_stale ) {
			// For stale content, indicate it's stale but revalidating.
			header( 'Cache-Control: public, max-age=0, stale-while-revalidate=' . $max_age );
		} else {
			// For fresh content, set max-age to remaining lifetime.
			$remaining = max( 0, $max_age - $age );
			header( 'Cache-Control: public, max-age=' . $remaining );
		}
		
		// Add debug headers if in debug mode.
		if ( ! empty( $this->config['debug_mode'] ) ) {
			header( 'X-SyncCache-Created: ' . gmdate( 'D, d M Y H:i:s', $created ) . ' GMT' );
			header( 'X-SyncCache-Age: ' . $age . 's' );
			header( 'X-SyncCache-Key: ' . $this->cache_key );
			header( 'X-SyncCache-File: ' . basename( $this->cache_file ) );
		}
	}

	/**
	 * Trigger asynchronous cache update.
	 */
	private function trigger_async_cache_update() {
		// This would be implemented with a non-blocking HTTP request
		// or by scheduling a single wp-cron event.
		// For now, we'll just mark the cache file for regeneration.
		$regen_file = dirname( $this->cache_file ) . '/.regenerate';
		@file_put_contents( $regen_file, time() );
	}

	/**
	 * Track cache serve statistics.
	 *
	 * @param string $type Type of cache serve (hit, miss, stale).
	 */
	private function track_cache_serve( $type ) {
		if ( empty( $this->config['cache_analytics'] ) ) {
			return;
		}
		
		// Only track a sample of requests to reduce I/O.
		$sample_rate = (int) ( $this->config['analytics_sampling_rate'] ?? 10 );
		if ( $sample_rate < 100 && mt_rand( 1, 100 ) > $sample_rate ) {
			return;
		}
		
		// Get today's date for stats.
		$today = gmdate( 'Y-m-d' );
		
		// Stats file path.
		$stats_file = $this->cache_path . 'stats/daily-' . $today . '.json';
		$stats_dir  = dirname( $stats_file );
		
		// Ensure directory exists.
		if ( ! is_dir( $stats_dir ) ) {
			@mkdir( $stats_dir, 0755, true );
		}
		
		// Get existing stats.
		$stats = array();
		if ( file_exists( $stats_file ) ) {
			$stats_json = @file_get_contents( $stats_file );
			if ( $stats_json ) {
				$stats = json_decode( $stats_json, true ) ?: array();
			}
		}
		
		// Initialize stats structure if needed.
		if ( empty( $stats ) ) {
			$stats = array(
				'date'   => $today,
				'hits'   => 0,
				'misses' => 0,
				'stale'  => 0,
				'pages'  => array(),
				'hours'  => array_fill( 0, 24, 0 ),
			);
		}
		
		// Update stats based on type.
		if ( $type === 'hit' ) {
			++$stats['hits'];
		} elseif ( $type === 'miss' ) {
			++$stats['misses'];
		} elseif ( $type === 'stale' ) {
			++$stats['stale'];
		}
		
		// Record page hit.
		$url_key = md5( $this->request_uri );
		if ( ! isset( $stats['pages'][ $url_key ] ) ) {
			$stats['pages'][ $url_key ] = array(
				'url'   => $this->request_uri,
				'count' => 0,
			);
		}
		++$stats['pages'][ $url_key ]['count'];
		
		// Record hour hit.
		$current_hour = (int) gmdate( 'G' );
		++$stats['hours'][ $current_hour ];
		
		// Save stats back to file.
		@file_put_contents( $stats_file, json_encode( $stats ) );
	}

	/**
	 * Set up cache creation process.
	 */
	private function setup_cache_creation() {
		// Only set up cache creation if we should cache this request.
		if ( ! $this->should_cache_request() ) {
			return;
		}
		
		// Register shutdown function to save the cache.
		register_shutdown_function( array( $this, 'save_cache' ) );
		
		// Start output buffering.
		ob_start( array( $this, 'process_output' ) );
		
		// Track cache miss.
		$this->track_cache_serve( 'miss' );
	}

	/**
	 * Process output before caching.
	 *
	 * @param string $content The content to process.
	 * @return string Processed content.
	 */
	public function process_output( $content ) {
		// Check if we should cache this response.
		if ( ! $this->should_cache_response( $content ) ) {
			return $content;
		}
		
		// Apply optimizations if enabled.
		if ( ! empty( $this->config['minify_html'] ) ) {
			$content = $this->minify_html( $content );
		}
		
		// Apply CDN replacements if enabled.
		if ( ! empty( $this->config['cdn_enabled'] ) && ! empty( $this->config['cdn_url'] ) && $this->config['cdn_url'] !== '{{CDN_URL}}' ) {
			$content = $this->apply_cdn( $content );
		}
		
		// Apply lazy loading if enabled.
		if ( ! empty( $this->config['lazy_load'] ) ) {
			$content = $this->apply_lazy_loading( $content );
		}
		
		// Add cache signature.
		$content = '<!--SYNCCACHE-CREATED:' . time() . '-->' . $content;
		
		return $content;
	}

	/**
	 * Check if response should be cached.
	 *
	 * @param string $content The response content.
	 * @return bool Whether the response should be cached.
	 */
	private function should_cache_response( $content ) {
		// Skip empty content.
		if ( empty( $content ) ) {
			return false;
		}
		
		// Get HTTP response code.
		$response_code = http_response_code();
		
		// Only cache 200 OK responses by default.
		if ( $response_code !== 200 ) {
			// Cache 404 pages if configured.
			if ( $response_code === 404 && ! empty( $this->config['cache_404'] ) ) {
				return true;
			}
			return false;
		}
		
		// Check for no-cache indicators in the content.
		if ( strpos( $content, '<!--DONOTCACHEPAGE-->' ) !== false ) {
			return false;
		}
		
		return true;
	}

	/**
	 * Basic HTML minification.
	 *
	 * @param string $content HTML content.
	 * @return string Minified HTML.
	 */
	private function minify_html( $content ) {
		// Simple minification: remove whitespace and comments.
		// For production use, this would be more comprehensive.
		$search = array(
			'/\>[^\S ]+/s',  // Strip whitespace after tags
			'/[^\S ]+\</s',  // Strip whitespace before tags
			'/(\s)+/s',      // Shorten multiple whitespace sequences
			'/<!--(?!\[if).*?-->/s', // Remove HTML comments (but not IE conditional comments)
		);
		
		$replace = array(
			'>',
			'<',
			'\\1',
			'',
		);
		
		return preg_replace( $search, $replace, $content );
	}

	/**
	 * Apply CDN URLs to content.
	 *
	 * @param string $content HTML content.
	 * @return string Content with CDN URLs.
	 */
	private function apply_cdn( $content ) {
		// Get the site URL for replacement.
		$site_url = '';
		
		// Try to get it from standard WordPress constant.
		if ( defined( 'WP_SITEURL' ) ) {
			$site_url = WP_SITEURL;
		} elseif ( defined( 'WP_HOME' ) ) {
			$site_url = WP_HOME;
		} else {
			// Fallback to constructing from server variables.
			$site_url = ( $this->is_ssl_request() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'];
		}
		
		// Parse site URL.
		$parsed_site_url = parse_url( $site_url );
		$site_domain     = $parsed_site_url['host'];
		
		// Get the CDN URL.
		$cdn_url = rtrim( $this->config['cdn_url'], '/' );
		
		// Get file extensions to apply CDN to.
		$extensions = $this->config['cdn_includes'] ?? array(
			'.jpg',
			'.jpeg',
			'.png',
			'.gif',
			'.webp',
			'.svg',
			'.css',
			'.js',
			'.woff',
			'.woff2',
			'.ttf',
			'.eot',
		);
		
		// Build regex pattern for matching URLs.
		$extensions_pattern = implode(
			'|',
			array_map(
				function ( $ext ) {
					return preg_quote( $ext, '/' );
				},
				$extensions 
			) 
		);
		
		// Replace URLs in different attributes.
		$content = preg_replace(
			'/(<(?:img|script|link|source|video|audio)[^>]*(?:src|href|srcset|data-[^=]+)\s*=\s*["\'])([^"\']+)(' . $extensions_pattern . ')(["\'][^>]*>)/i',
			function ( $matches ) use ( $site_domain, $cdn_url ) {
				$prefix = $matches[1];
				$url    = $matches[2];
				$ext    = $matches[3];
				$suffix = $matches[4];
				
				// Skip external and absolute URLs.
				if ( strpos( $url, '//' ) === 0 || preg_match( '#^https?://#i', $url ) ) {
					if ( strpos( $url, $site_domain ) === false ) {
						return $matches[0]; // External URL, don't modify.
					}
				}
				
				// Apply CDN URL to local URL.
				if ( strpos( $url, '/' ) === 0 ) {
					// Absolute path, add CDN domain.
					return $prefix . $cdn_url . $url . $ext . $suffix;
				} else {
					// Relative path, replace site domain with CDN domain.
					$url_with_cdn = str_replace( $site_domain, $cdn_url, $url );
					return $prefix . $url_with_cdn . $ext . $suffix;
				}
			},
			$content
		);
		
		return $content;
	}

	/**
	 * Apply lazy loading to images.
	 *
	 * @param string $content HTML content.
	 * @return string Content with lazy loading.
	 */
	private function apply_lazy_loading( $content ) {
		// Simple lazy loading implementation using native loading="lazy".
		// For production, this would be more sophisticated.
		$content = preg_replace(
			'/<img((?!loading=)[^>]*)>/i',
			'<img$1 loading="lazy">',
			$content
		);
		
		return $content;
	}

	/**
	 * Save page to cache.
	 * This is called by the shutdown function.
	 */
	public function save_cache() {
		// Get the output buffer content.
		$content = ob_get_contents();
		
		// Don't cache if response should not be cached.
		if ( ! $this->should_cache_response( $content ) ) {
			return;
		}
		
		// Make sure we have a cache key and file path.
		if ( empty( $this->cache_key ) ) {
			$this->generate_cache_key();
		}
		
		if ( empty( $this->cache_file ) ) {
			$this->set_cache_file_path();
		}
		
		// Ensure the cache directory exists.
		$cache_dir = dirname( $this->cache_file );
		if ( ! is_dir( $cache_dir ) ) {
			if ( ! @mkdir( $cache_dir, 0755, true ) ) {
				// Failed to create directory.
				if ( ! empty( $this->config['enable_logging'] ) ) {
					error_log( 'SyncCache: Failed to create cache directory: ' . $cache_dir );
				}
				return;
			}
		}
		
		// Check IOPS protection if enabled.
		if ( ! empty( $this->config['iops_protection'] ) ) {
			$max_files = (int) ( $this->config['max_files_per_second'] ?? 100 );
			$lock_file = $this->cache_path . 'iops_lock.txt';
			
			// Try to acquire lock.
			$lock_count = 0;
			if ( file_exists( $lock_file ) ) {
				$lock_data = @file_get_contents( $lock_file );
				if ( $lock_data ) {
					list( $lock_time, $lock_count ) = explode( ':', $lock_data );
					
					// Check if lock is still valid (within the same second).
					if ( $lock_time == time() ) {
						// Check if we've reached the limit.
						if ( $lock_count >= $max_files ) {
							// Too many files, skip caching this one.
							return;
						}
					} else {
						// Lock expired, reset count.
						$lock_count = 0;
					}
				}
			}
			
			// Update lock.
			++$lock_count;
			@file_put_contents( $lock_file, time() . ':' . $lock_count );
		}
		
		// Write the cache file.
		$result = @file_put_contents( $this->cache_file, $content );
		
		if ( false === $result && ! empty( $this->config['enable_logging'] ) ) {
			error_log( 'SyncCache: Failed to write cache file: ' . $this->cache_file );
		}
	}
}

// Instantiate the cache system.
new SyncCache( $sync_config );
