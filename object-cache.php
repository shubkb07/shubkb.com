<?php
/**
 * Plugin Name: WiseSync Object Cache
 * Description: Provides a persistent object cache using Memcached or Redis, with safe fallbacks to the built-in WP_Object_Cache. Dynamically selects backend.
 * Version: 1.0.0
 * Plugin URI: https://shubkb.com
 * Author: Shubham Kumar Bansal <shub@shubkb.com>
 * Author URI: https://shubkb.com
 * License: Apache License 2.0
 * License URI: https://www.apache.org/licenses/LICENSE-2.0
 *
 * phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
 * phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed
 * phpcs:disable WordPress.WP.I18n.MissingArgDomain
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
 * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
 *
 * @package WiseSync
 * @since 1.0.0
 **/

global $sync_cache_settings;
$sync_cache_settings = empty( $sync_cache_settings ) ? array() : json_decode( $sync_cache_settings, true );

// Default settings.
$sync_cache_default_settings = array(
	'backend'                         => 'auto', // Select Cache Type - 'auto' - Auto Detect, 'memcached' - Memcache, 'redis' - Redis, or 'wordpress'/'none' - Default/Disable.
	'servers'                         => array(
		'memcached' => array( // Can be an array of 'host:port' strings or an associative array of buckets.
			'default' => array( '127.0.0.1:11211' ),
		),
		'redis'     => array(
			'host'           => '127.0.0.1',
			'port'           => 6379,
			'password'       => null,
			'database'       => 0,
			'timeout'        => 1, // seconds.
			'retry_interval' => 100, // milliseconds.
			'read_timeout'   => 1, // seconds.
		),
	),
	'global_groups'                   => array( 'users', 'userlogins', 'usermeta', 'user_meta', 'site-transient', 'site-options', 'site-lookup', 'blog-lookup', 'blog-details', 'rss', 'WP_Object_Cache_global' ),
	'ignored_groups'                  => array( 'counts', 'plugins' ), // Renamed from no_mc_groups to be generic.
	'max_ttl'                         => 2592000, // 30 days.
	'default_ttl'                     => 0, // Memcached default (0 = never expire), Redis needs explicit TTL or inherits server policy. Let's use 0 for consistency.
	'redis_serializer'                => defined( 'Redis::SERIALIZER_PHP' ) ? Redis::SERIALIZER_PHP : null, // Or Redis::SERIALIZER_IGBINARY if available/preferred.
	'redis_compression'               => false, // PHP-level compression for Redis values if true.
	'memcached_compression_threshold' => 20000,
	'memcached_compression_factor'    => 0.2,
	'debug'                           => defined( 'WP_DEBUG' ) && WP_DEBUG,
	'slow_op_microseconds'            => 0.005, // 5 ms.
);

// Apply user settings from global variable if provided.
if ( ! empty( $sync_cache_settings ) ) {
	$sync_cache_default_settings = array_merge( $sync_cache_default_settings, $sync_cache_settings );
}

// Users can define constants to override settings.
$constants_to_check = array(
	'WISESYNC_BACKEND',
	'WISESYNC_MEMCACHED_SERVERS',
	'WISESYNC_REDIS_HOST',
	'WISESYNC_REDIS_PORT',
	'WISESYNC_REDIS_PASSWORD',
	'WISESYNC_REDIS_DATABASE',
	'WISESYNC_GLOBAL_GROUPS',
	'WISESYNC_IGNORED_GROUPS',
	'WISESYNC_MAX_TTL',
	'WISESYNC_DEFAULT_TTL',
	'WISESYNC_DEBUG',
);

foreach ( $constants_to_check as $constant ) {
	if ( defined( $constant ) ) {
		$setting_key = strtolower( str_replace( 'WISESYNC_', '', $constant ) );
		if ( 'memcached_servers' === $setting_key ) {
			$sync_cache_default_settings['servers']['memcached']['default'] = constant( $constant );
		} elseif ( in_array( $setting_key, array( 'redis_host', 'redis_port', 'redis_password', 'redis_database' ) ) ) {
			$redis_setting = str_replace( 'redis_', '', $setting_key );
			$sync_cache_default_settings['servers']['redis'][ $redis_setting ] = constant( $constant );
		} else {
			$sync_cache_default_settings[ $setting_key ] = constant( $constant );
		}
	}
}

// Define a prefix for all cache keys.
if ( ! defined( 'WP_CACHE_KEY_SALT' ) ) {
	define( 'WP_CACHE_KEY_SALT', '' );
}

/**
 * WiseSync cache backend detection and initialization
 */
class WiseSync_Cache_Manager {
	/**
	 * The active backend type.
	 *
	 * @var string
	 */
	private static $active_backend = 'WordPress';
	
	/**
	 * Flag indicating if backend is available.
	 *
	 * @var bool
	 */
	private static $backend_available = false;
	
	/**
	 * Backend client info.
	 *
	 * @var string
	 */
	private static $backend_client = '';
	
	/**
	 * Settings for the cache.
	 *
	 * @var array
	 */
	private static $settings = array();
	
	/**
	 * Initialize the cache backend.
	 *
	 * @param array $settings Cache settings.
	 * @return string The selected backend.
	 */
	public static function init( $settings ) {
		self::$settings = $settings;
		
		// Auto-detect backend if not specified.
		if ( 'auto' === $settings['backend'] ) {
			self::$active_backend = self::detect_backend();
		} else {
			self::$active_backend = $settings['backend'];
		}
		
		// Initialize the selected backend.
		switch ( self::$active_backend ) {
			case 'memcached':
				self::init_memcached();
				break;
				
			case 'redis':
				self::init_redis();
				break;
				
			default:
				self::$active_backend    = 'WordPress';
				self::$backend_available = true;
				self::$backend_client    = 'WordPress Default';
				break;
		}
		
		return self::$active_backend;
	}
	
	/**
	 * Auto-detect which backend to use.
	 *
	 * @return string The detected backend.
	 */
	private static function detect_backend() {
		// First try Redis.
		if ( extension_loaded( 'redis' ) || class_exists( 'Predis\Client' ) ) {
			return 'redis';
		}
		
		// Then try Memcached.
		if ( class_exists( 'Memcached' ) ) {
			return 'memcached';
		} elseif ( class_exists( 'Memcache' ) ) {
			return 'memcached';
		}
		
		// Fallback to WordPress default.
		return 'WordPress';
	}
	
	/**
	 * Initialize Memcached backend.
	 */
	private static function init_memcached() {
		$memcached_available = false;
		$backend_client      = '';
		
		// Check if Memcached extension is available.
		if ( class_exists( 'Memcached' ) ) {
			$backend_client      = 'Memcached Extension';
			$memcached_available = true;
		} elseif ( class_exists( 'Memcache' ) ) {
			$backend_client      = 'Memcache Extension';
			$memcached_available = true;
		}
		
		self::$backend_available = $memcached_available;
		self::$backend_client    = $backend_client;
		
		if ( ! $memcached_available ) {
			self::$active_backend = 'WordPress';
		}
	}
	
	/**
	 * Initialize Redis backend.
	 */
	private static function init_redis() {
		$redis_available = false;
		$backend_client  = '';
		
		// Check if Redis extension is available.
		if ( extension_loaded( 'redis' ) ) {
			$backend_client  = 'Redis Extension';
			$redis_available = true;
		} elseif ( class_exists( 'Predis\Client' ) ) {
			$backend_client  = 'Predis Library';
			$redis_available = true;
		}
		
		self::$backend_available = $redis_available;
		self::$backend_client    = $backend_client;
		
		if ( ! $redis_available ) {
			self::$active_backend = 'WordPress';
		}
	}
	
	/**
	 * Get the active backend type.
	 *
	 * @return string
	 */
	public static function get_active_backend() {
		return self::$active_backend;
	}
	
	/**
	 * Check if backend is available.
	 *
	 * @return bool
	 */
	public static function is_backend_available() {
		return self::$backend_available;
	}
	
	/**
	 * Get backend client info.
	 *
	 * @return string
	 */
	public static function get_backend_client() {
		return self::$backend_client;
	}
	
	/**
	 * Get settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return self::$settings;
	}
}

// Initialize cache backend.
WiseSync_Cache_Manager::init( $sync_cache_default_settings );

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

/**
 * Core class that implements an object cache.
 *
 * The WordPress Object Cache provides an in-memory caching mechanism with optional
 * persistent cache backends in Redis or Memcached.
 */
class WP_Object_Cache {
// phpcs:enable Generic.Files.OneObjectStructurePerFile.MultipleFound
	/**
	 * The active backend type.
	 *
	 * @var string
	 */
	private $backend;
	
	/**
	 * Holds the cached objects.
	 *
	 * @var array
	 */
	private $cache = array();
	
	/**
	 * The backend client
	 *
	 * @var mixed
	 */
	private $client = null;
	
	/**
	 * Track if backend is connected and available
	 *
	 * @var bool
	 */
	private $backend_connected = false;
	
	/**
	 * Backend client name
	 *
	 * @var string
	 */
	private $backend_client = '';
	
	/**
	 * The amount of times the cache data was already stored in the cache.
	 *
	 * @var int
	 */
	public $cache_hits = 0;
	
	/**
	 * Amount of times the cache did not have the request in cache.
	 *
	 * @var int
	 */
	public $cache_misses = 0;
	
	/**
	 * List of global cache groups.
	 *
	 * @var array
	 */
	protected $global_groups = array();
	
	/**
	 * List of groups not saved to the persistent backend.
	 *
	 * @var array
	 */
	protected $ignored_groups = array();
	
	/**
	 * The blog prefix to prepend to keys in non-global groups.
	 *
	 * @var string
	 */
	private $blog_prefix;
	
	/**
	 * Prefix used for global groups.
	 *
	 * @var string
	 */
	private $global_prefix = '';
	
	/**
	 * Holds the value of is_multisite().
	 *
	 * @var bool
	 */
	private $multisite;
	
	/**
	 * Settings for the cache
	 *
	 * @var array
	 */
	private $settings = array();
	
	/**
	 * Cache stats for debugging
	 *
	 * @var array
	 */
	private $stats = array(
		'get'          => 0,
		'set'          => 0,
		'delete'       => 0,
		'add'          => 0,
		'replace'      => 0,
		'incr'         => 0,
		'decr'         => 0,
		'flush'        => 0,
		'get_multi'    => 0,
		'set_multi'    => 0,
		'delete_multi' => 0,
		'add_multi'    => 0,
	);
	
	/**
	 * The key salt used for cache keys
	 *
	 * @var string
	 */
	private $key_salt = '';
	
	/**
	 * Cache group operations for debugging
	 *
	 * @var array
	 */
	private $group_ops = array();
	
	/**
	 * Total time spent on cache operations
	 *
	 * @var float
	 */
	private $time_total = 0;
	
	/**
	 * Total size of cached data
	 *
	 * @var int
	 */
	private $size_total = 0;
	
	/**
	 * Threshold to log slow operations
	 *
	 * @var float
	 */
	private $slow_op_microseconds = 0.005;
	
	/**
	 * Time when operation started
	 *
	 * @var float
	 */
	private $time_start = 0;
	
	/**
	 * Sets up object properties.
	 */
	public function __construct() {
		global $blog_id, $table_prefix;
		
		$this->multisite   = is_multisite();
		$this->blog_prefix = $this->multisite ? $blog_id . ':' : '';
		
		// Set global prefix.
		if ( $this->multisite || ( defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' ) ) ) {
			$this->global_prefix = '';
		} else {
			$this->global_prefix = $table_prefix;
		}
		
		// Get settings from the manager.
		$this->settings          = WiseSync_Cache_Manager::get_settings();
		$this->backend           = WiseSync_Cache_Manager::get_active_backend();
		$this->backend_connected = WiseSync_Cache_Manager::is_backend_available();
		$this->backend_client    = WiseSync_Cache_Manager::get_backend_client();
		
		// Initialize groups.
		if ( ! empty( $this->settings['global_groups'] ) ) {
			$this->add_global_groups( $this->settings['global_groups'] );
		}
		
		if ( ! empty( $this->settings['ignored_groups'] ) ) {
			$this->add_non_persistent_groups( $this->settings['ignored_groups'] );
		}
		
		// Set key salt.
		$this->key_salt = WP_CACHE_KEY_SALT;
		
		// Initialize backend specific client.
		$this->init_backend_client();
	}
	
	/**
	 * Initialize the backend specific client
	 */
	private function init_backend_client() {
		if ( 'memcached' === $this->backend ) {
			$this->init_memcached_client();
		} elseif ( 'redis' === $this->backend ) {
			$this->init_redis_client();
		}
		
		// If backend initialization failed, fall back to WordPress.
		if ( ! $this->backend_connected ) {
			$this->backend = 'WordPress';
		}
	}
	
	/**
	 * Initialize Memcached client
	 */
	private function init_memcached_client() {
		try {
			$servers = $this->settings['servers']['memcached'];
			
			// Check if we have Memcached or Memcache.
			if ( class_exists( 'Memcached' ) ) {
				$this->client = new Memcached();
				
				// Add servers to Memcached.
				if ( is_array( $servers ) ) {
					// Handle bucket format.
					if ( isset( $servers['default'] ) && is_array( $servers['default'] ) ) {
						foreach ( $servers['default'] as $server ) {
							if ( strpos( $server, ':' ) !== false ) {
								list($host, $port) = explode( ':', $server );
								$this->client->addServer( $host, (int) $port );
							} else {
								$this->client->addServer( $server, 11211 );
							}
						}
					} else {
						// Handle simple array format.
						foreach ( $servers as $server ) {
							if ( strpos( $server, ':' ) !== false ) {
								list($host, $port) = explode( ':', $server );
								$this->client->addServer( $host, (int) $port );
							} else {
								$this->client->addServer( $server, 11211 );
							}
						}
					}
				}
				
				// Set compression threshold.
				if ( isset( $this->settings['memcached_compression_threshold'] ) ) {
					$this->client->setOption( Memcached::OPT_COMPRESSION_THRESHOLD, $this->settings['memcached_compression_threshold'] );
				}
				
				// Test connection.
				$stats                   = $this->client->getStats();
				$this->backend_connected = ! empty( $stats ) && is_array( $stats ) && count( $stats ) > 0;
				
			} elseif ( class_exists( 'Memcache' ) ) {
				$this->client = new Memcache();
				
				// Add servers to Memcache.
				if ( is_array( $servers ) ) {
					// Handle bucket format.
					if ( isset( $servers['default'] ) && is_array( $servers['default'] ) ) {
						foreach ( $servers['default'] as $server ) {
							if ( strpos( $server, ':' ) !== false ) {
								list($host, $port) = explode( ':', $server );
								$this->client->addServer( $host, (int) $port, true );
							} else {
								$this->client->addServer( $server, 11211, true );
							}
						}
					} else {
						// Handle simple array format.
						foreach ( $servers as $server ) {
							if ( strpos( $server, ':' ) !== false ) {
								list($host, $port) = explode( ':', $server );
								$this->client->addServer( $host, (int) $port, true );
							} else {
								$this->client->addServer( $server, 11211, true );
							}
						}
					}
				}
				
				// Set compression threshold.
				if ( isset( $this->settings['memcached_compression_threshold'] ) && isset( $this->settings['memcached_compression_factor'] ) ) {
					$this->client->setCompressThreshold(
						$this->settings['memcached_compression_threshold'],
						$this->settings['memcached_compression_factor']
					);
				}
				
				// Test connection.
				$stats                   = @$this->client->getStats();
				$this->backend_connected = ! empty( $stats ) && is_array( $stats ) && count( $stats ) > 0;
			}
		} catch ( Exception $e ) {
			$this->backend_connected = false;
		}
	}
	
	/**
	 * Initialize Redis client
	 */
	private function init_redis_client() {
		try {
			$config = $this->settings['servers']['redis'];
			
			// Check for native Redis extension.
			if ( extension_loaded( 'redis' ) ) {
				$this->client = new Redis();
				
				if ( isset( $config['path'] ) && ! empty( $config['path'] ) ) {
					// Connect using Unix socket.
					$this->client->connect( $config['path'] );
				} else {
					// Connect using TCP.
					$timeout        = isset( $config['timeout'] ) ? $config['timeout'] : 1;
					$retry_interval = isset( $config['retry_interval'] ) ? $config['retry_interval'] : 100;
					
					$this->client->connect(
						$config['host'],
						$config['port'],
						$timeout,
					);
					
					if ( isset( $config['read_timeout'] ) ) {
						$this->client->setOption( Redis::OPT_READ_TIMEOUT, $config['read_timeout'] );
					}
				}
				
				// Authenticate if password is provided.
				if ( ! empty( $config['password'] ) ) {
					$this->client->auth( $config['password'] );
				}
				
				// Select database.
				if ( isset( $config['database'] ) ) {
					$this->client->select( $config['database'] );
				}
				
				// Set serializer if specified.
				if ( isset( $this->settings['redis_serializer'] ) && null !== $this->settings['redis_serializer'] ) {
					$this->client->setOption( Redis::OPT_SERIALIZER, $this->settings['redis_serializer'] );
				}
				
				// Test connection.
				$this->backend_connected = $this->client->ping() === true || $this->client->ping() === '+PONG';
				
			} elseif ( class_exists( 'Predis\Client' ) ) {
				// Use Predis library if available and Redis extension is not.
				$parameters = array(
					'scheme' => 'tcp',
					'host'   => $config['host'],
					'port'   => $config['port'],
				);
				
				if ( ! empty( $config['password'] ) ) {
					$parameters['password'] = $config['password'];
				}
				
				if ( isset( $config['database'] ) ) {
					$parameters['database'] = $config['database'];
				}
				
				if ( isset( $config['timeout'] ) ) {
					$parameters['timeout'] = $config['timeout'];
				}
				
				if ( isset( $config['read_timeout'] ) ) {
					$parameters['read_write_timeout'] = $config['read_timeout'];
				}
				
				$this->client = new Predis\Client( $parameters );
				
				// Test connection.
				$this->backend_connected = $this->client->ping() === 'PONG';
			}
		} catch ( Exception $e ) {
			$this->backend_connected = false;
		}
	}

	/**
	 * Serves as a utility function to determine whether a key is valid.
	 *
	 * @param int|string $key Cache key to check for validity.
	 * @return bool Whether the key is valid.
	 */
	protected function is_valid_key( $key ) {
		if ( is_int( $key ) ) {
			return true;
		}

		if ( is_string( $key ) && trim( $key ) !== '' ) {
			return true;
		}

		$type = gettype( $key );

		if ( ! function_exists( '__' ) ) {
			wp_load_translations_early();
		}

		$message = is_string( $key )
			? __( 'Cache key must not be an empty string.' )
			/* translators: %s: The type of the given cache key. */
			: sprintf( __( 'Cache key must be an integer or a non-empty string, %s given.' ), $type );

		_doing_it_wrong(
			sprintf( '%s::%s', __CLASS__, debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 )[1]['function'] ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
			$message,
			'6.1.0'
		);

		return false;
	}

	/**
	 * Serves as a utility function to determine whether a key exists in the cache.
	 *
	 * @param int|string $key   Cache key to check for existence.
	 * @param string     $group Cache group for the key existence check.
	 * @return bool Whether the key exists in the cache for the given group.
	 */
	protected function _exists( $key, $group ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$derived_key = $this->build_key( $key, $group );
		return isset( $this->cache[ $derived_key ] );
	}

	/**
	 * Builds a key for the cached object using the blog_id, key, and group values.
	 *
	 * @param string $key   The key under which to store the value.
	 * @param string $group The group value appended to the $key.
	 * @return string The key for the cache
	 */
	protected function build_key( $key, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$prefix = $this->key_salt;
		
		if ( in_array( $group, $this->global_groups ) ) {
			$prefix .= $this->global_prefix;
		} else {
			$prefix .= $this->blog_prefix;
		}

		return preg_replace( '/\s+/', '', "$prefix:$group:$key" );
	}

	/**
	 * Adds data to the cache if it doesn't already exist.
	 *
	 * @param int|string $key    What to call the contents in the cache.
	 * @param mixed      $data   The contents to store in the cache.
	 * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
	 * @param int        $expire Optional. When to expire the cache contents, in seconds.
	 *                           Default 0 (no expiration).
	 * @return bool True on success, false if cache key and group already exist.
	 */
	public function add( $key, $data, $group = 'default', $expire = 0 ) {
		if ( wp_suspend_cache_addition() ) {
			return false;
		}

		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$derived_key = $this->build_key( $key, $group );

		if ( $this->_exists( $key, $group ) ) {
			return false;
		}

		++$this->stats['add'];
		
		return $this->set( $key, $data, $group, $expire );
	}

	/**
	 * Adds multiple values to the cache in one call.
	 *
	 * @param array  $data   Array of keys and values to be added.
	 * @param string $group  Optional. Where the cache contents are grouped. Default empty.
	 * @param int    $expire Optional. When to expire the cache contents, in seconds.
	 *                       Default 0 (no expiration).
	 * @return bool[] Array of return values, grouped by key. Each value is either
	 *                true on success, or false if cache key and group already exist.
	 */
	public function add_multiple( array $data, $group = '', $expire = 0 ) {
		$values = array();

		foreach ( $data as $key => $value ) {
			$values[ $key ] = $this->add( $key, $value, $group, $expire );
		}

		++$this->stats['add_multi'];
		
		return $values;
	}

	/**
	 * Replaces the contents in the cache, if contents already exist.
	 *
	 * @param int|string $key    What to call the contents in the cache.
	 * @param mixed      $data   The contents to store in the cache.
	 * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
	 * @param int        $expire Optional. When to expire the cache contents, in seconds.
	 *                           Default 0 (no expiration).
	 * @return bool True if contents were replaced, false if original value does not exist.
	 */
	public function replace( $key, $data, $group = 'default', $expire = 0 ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$derived_key = $this->build_key( $key, $group );

		if ( ! $this->_exists( $key, $group ) ) {
			return false;
		}

		++$this->stats['replace'];
		
		return $this->set( $key, $data, $group, $expire );
	}

	/**
	 * Sets the data contents into the cache.
	 *
	 * @param int|string $key    What to call the contents in the cache.
	 * @param mixed      $data   The contents to store in the cache.
	 * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
	 * @param int        $expire Optional. When to expire the cache contents, in seconds.
	 *                           Default 0 (no expiration).
	 * @return bool True if contents were set, false if key is invalid.
	 */
	public function set( $key, $data, $group = 'default', $expire = 0 ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$derived_key = $this->build_key( $key, $group );
		$orig_data   = $data;
		$result      = true;

		// Save to persistent cache if available and group is not ignored.
		if ( $this->backend_connected && ! in_array( $group, $this->ignored_groups ) ) {
			$this->timer_start();
			
			// Prepare and validate expiration time.
			$expire = $this->validate_expiration( $expire );
			
			if ( 'memcached' === $this->backend ) {
				$result = $this->set_in_memcached( $derived_key, $data, $expire );
			} elseif ( 'redis' === $this->backend ) {
				$result = $this->set_in_redis( $derived_key, $data, $expire );
			}
			
			$elapsed = $this->timer_stop();
			$size    = $this->get_data_size( $data );
			
			// Log operation stats for debugging.
			$this->group_ops_stats( 'set', $derived_key, $group, $size, $elapsed );
		}

		// If the set was successful or we're using WordPress backend.
		if ( $result ) {
			// Save to internal cache.
			if ( is_object( $orig_data ) ) {
				$this->cache[ $derived_key ] = clone $orig_data;
			} else {
				$this->cache[ $derived_key ] = $orig_data;
			}
		}

		++$this->stats['set'];
		
		return $result;
	}

	/**
	 * Set data in Memcached backend
	 *
	 * @param string $key    The derived cache key.
	 * @param mixed  $data   The data to cache.
	 * @param int    $expire The expiration time.
	 * @return bool Success or failure
	 */
	private function set_in_memcached( $key, $data, $expire ) {
		if ( method_exists( $this->client, 'setMulti' ) ) {
			// Using Memcached extension.
			return $this->client->set( $key, $data, $expire );
		} else {
			// Using Memcache extension.
			return $this->client->set( $key, $data, 0, $expire );
		}
	}

	/**
	 * Set data in Redis backend
	 *
	 * @param string $key    The derived cache key.
	 * @param mixed  $data   The data to cache.
	 * @param int    $expire The expiration time.
	 * @return bool Success or failure
	 */
	private function set_in_redis( $key, $data, $expire ) {
		if ( $expire > 0 ) {
			return $this->parse_redis_response( $this->client->setex( $key, $expire, $this->maybe_serialize( $data ) ) );
		} else {
			return $this->parse_redis_response( $this->client->set( $key, $this->maybe_serialize( $data ) ) );
		}
	}

	/**
	 * Sets multiple values to the cache in one call.
	 *
	 * @param array  $data   Array of key and value to be set.
	 * @param string $group  Optional. Where the cache contents are grouped. Default empty.
	 * @param int    $expire Optional. When to expire the cache contents, in seconds.
	 *                       Default 0 (no expiration).
	 * @return bool[] Array of return values, grouped by key. Each value is always true.
	 */
	public function set_multiple( array $data, $group = '', $expire = 0 ) {
		$values = array();
		foreach ( $data as $key => $value ) {
			$values[ $key ] = $this->set( $key, $value, $group, $expire );
		}

		++$this->stats['set_multi'];
	   
		return $values;
	}

	/**
	 * Retrieves the cache contents, if it exists.
	 *
	 * @param int|string $key   The key under which the cache contents are stored.
	 * @param string     $group Optional. Where the cache contents are grouped. Default 'default'.
	 * @param bool       $force Optional. Whether to force an update of the local cache
	 *                          from the persistent cache. Default false.
	 * @param bool       &$found Optional. Whether the key was found in the cache (passed by reference).
	 *                          Disambiguates a return of false, a storable value. Default null.
	 * @return mixed|false The cache contents on success, false on failure to retrieve contents.
	 */
	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$derived_key = $this->build_key( $key, $group );

		// Check internal cache first if not forced.
		if ( isset( $this->cache[ $derived_key ] ) && ! $force ) {
			$found = true;
			++$this->cache_hits;
		   
			// If it's an object, clone to avoid reference issues.
			if ( is_object( $this->cache[ $derived_key ] ) ) {
				return clone $this->cache[ $derived_key ];
			} else {
				return $this->cache[ $derived_key ];
			}
		}

		// If the group is excluded from persistent cache or backend is not available.
		if ( in_array( $group, $this->ignored_groups ) || ! $this->backend_connected ) {
			$found = false;
			++$this->cache_misses;
			return false;
		}

		// Fetch from the persistent cache backend.
		$this->timer_start();
	   
		$value = false;
		if ( 'memcached' === $this->backend ) {
			$value = $this->get_from_memcached( $derived_key );
		} elseif ( 'redis' === $this->backend ) {
			$value = $this->get_from_redis( $derived_key );
		}
	   
		$elapsed = $this->timer_stop();

		// Check if value was found.
		if ( false === $value || null === $value ) {
			$found = false;
			++$this->cache_misses;
			$this->group_ops_stats( 'get', $derived_key, $group, null, $elapsed, 'not_in_backend' );
			return false;
		} else {
			$found = true;
			++$this->cache_hits;
			$size = $this->get_data_size( $value );
			$this->group_ops_stats( 'get', $derived_key, $group, $size, $elapsed, 'found' );
		}

		// Add to the internal cache.
		$this->cache[ $derived_key ] = $value;

		// If it's an object, clone to avoid reference issues.
		if ( is_object( $value ) ) {
			$value = clone $value;
		}

		++$this->stats['get'];
	   
		return $value;
	}

	/**
	 * Get data from Memcached backend
	 *
	 * @param string $key The derived cache key.
	 * @return mixed The data or false on failure
	 */
	private function get_from_memcached( $key ) {
		if ( method_exists( $this->client, 'getMulti' ) ) {
			// Using Memcached extension.
			return $this->client->get( $key );
		} else {
			// Using Memcache extension.
			return $this->client->get( $key );
		}
	}

	/**
	 * Get data from Redis backend
	 *
	 * @param string $key The derived cache key.
	 * @return mixed The data or false on failure
	 */
	private function get_from_redis( $key ) {
		$value = $this->client->get( $key );
	   
		if ( false === $value || null === $value ) {
			return false;
		}
	   
		return $this->maybe_unserialize( $value );
	}

	/**
	 * Retrieves multiple values from the cache in one call.
	 *
	 * @param array  $keys  Array of keys under which the cache contents are stored.
	 * @param string $group Optional. Where the cache contents are grouped. Default 'default'.
	 * @param bool   $force Optional. Whether to force an update of the local cache
	 *                      from the persistent cache. Default false.
	 * @return array Array of return values, grouped by key. Each value is either
	 *               the cache contents on success, or false on failure.
	 */
	public function get_multiple( $keys, $group = 'default', $force = false ) {
		$values = array();

		// If group is ignored or backend not available, get keys individually.
		if ( in_array( $group, $this->ignored_groups ) || ! $this->backend_connected ) {
			foreach ( $keys as $key ) {
				$values[ $key ] = $this->get( $key, $group, $force );
			}
			return $values;
		}

		// Build derived keys.
		$derived_keys = array();
		foreach ( $keys as $key ) {
			$derived_keys[ $key ] = $this->build_key( $key, $group );
		}

		// Check internal cache first for non-forced requests.
		if ( ! $force ) {
			foreach ( $derived_keys as $key => $derived_key ) {
				if ( isset( $this->cache[ $derived_key ] ) ) {
					$values[ $key ] = is_object( $this->cache[ $derived_key ] ) 
						? clone $this->cache[ $derived_key ] 
						: $this->cache[ $derived_key ];
					unset( $derived_keys[ $key ] ); // Remove from list of keys to fetch.
				}
			}
		}

		// If we still have keys to fetch from persistent cache.
		if ( ! empty( $derived_keys ) ) {
			$this->timer_start();
		   
			$fetched_values = array();
			if ( 'memcached' === $this->backend ) {
				$fetched_values = $this->get_multiple_from_memcached( array_values( $derived_keys ) );
			} elseif ( 'redis' === $this->backend ) {
				$fetched_values = $this->get_multiple_from_redis( array_values( $derived_keys ) );
			}
		   
			$elapsed = $this->timer_stop();
			$this->group_ops_stats( 'get_multi', $derived_keys, $group, null, $elapsed );

			// Process fetched values.
			foreach ( $derived_keys as $key => $derived_key ) {
				if ( isset( $fetched_values[ $derived_key ] ) && false !== $fetched_values[ $derived_key ] ) {
					++$this->cache_hits;
					$this->cache[ $derived_key ] = $fetched_values[ $derived_key ];
					$values[ $key ]              = is_object( $fetched_values[ $derived_key ] ) 
						? clone $fetched_values[ $derived_key ] 
						: $fetched_values[ $derived_key ];
				} else {
					++$this->cache_misses;
					$values[ $key ] = false;
				}
			}
		}

		++$this->stats['get_multi'];
	   
		return $values;
	}

	/**
	 * Get multiple values from Memcached backend
	 *
	 * @param array $keys Array of derived cache keys.
	 * @return array Array of values
	 */
	private function get_multiple_from_memcached( $keys ) {
		$values = array();
	   
		if ( method_exists( $this->client, 'getMulti' ) ) {
			// Using Memcached extension.
			$fetched = $this->client->getMulti( $keys );
			if ( is_array( $fetched ) ) {
				$values = $fetched;
			}
		} else {
			// Using Memcache extension - doesn't have a native multi-get, so do individual gets.
			foreach ( $keys as $key ) {
				$value = $this->client->get( $key );
				if ( false !== $value ) {
					$values[ $key ] = $value;
				}
			}
		}
	   
		return $values;
	}

	/**
	 * Get multiple values from Redis backend
	 *
	 * @param array $keys Array of derived cache keys.
	 * @return array Array of values
	 */
	private function get_multiple_from_redis( $keys ) {
		$values = array();
	   
		if ( method_exists( $this->client, 'mget' ) ) {
			// Both Redis extension and Predis have mget.
			$fetched = $this->client->mget( $keys );
		   
			if ( is_array( $fetched ) ) {
				// Redis extension returns indexed array, convert to associative.
				foreach ( $keys as $i => $key ) {
					if ( isset( $fetched[ $i ] ) && false !== $fetched[ $i ] && null !== $fetched[ $i ] ) {
						$values[ $key ] = $this->maybe_unserialize( $fetched[ $i ] );
					}
				}
			}
		} else {
			// Fallback to individual gets.
			foreach ( $keys as $key ) {
				$value = $this->get_from_redis( $key );
				if ( false !== $value ) {
					$values[ $key ] = $value;
				}
			}
		}
	   
		return $values;
	}

	/**
	 * Removes the contents of the cache key in the group.
	 *
	 * @param int|string $key        What the contents in the cache are called.
	 * @param string     $group      Optional. Where the cache contents are grouped. Default 'default'.
	 * @param bool       $deprecated Optional. Unused. Default false.
	 * @return bool True on success, false if the contents were not deleted.
	 */
	public function delete( $key, $group = 'default', $deprecated = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$derived_key = $this->build_key( $key, $group );

		// Remove from internal cache.
		$existed = isset( $this->cache[ $derived_key ] );
		unset( $this->cache[ $derived_key ] );

		// If the key didn't exist in internal cache and isn't in a persistent cache group, it's a miss.
		if ( ! $existed && ( in_array( $group, $this->ignored_groups ) || ! $this->backend_connected ) ) {
			return false;
		}

		// Remove from persistent cache if appropriate.
		$result = true;
		if ( ! in_array( $group, $this->ignored_groups ) && $this->backend_connected ) {
			$this->timer_start();
		   
			if ( 'memcached' === $this->backend ) {
				$result = $this->client->delete( $derived_key );
			} elseif ( 'redis' === $this->backend ) {
				$result = (bool) $this->client->del( $derived_key );
			}
		   
			$elapsed = $this->timer_stop();
			$this->group_ops_stats( 'delete', $derived_key, $group, null, $elapsed );
		}

		++$this->stats['delete'];
	   
		return $result;
	}

	/**
	 * Deletes multiple values from the cache in one call.
	 *
	 * @param array  $keys  Array of keys to be deleted.
	 * @param string $group Optional. Where the cache contents are grouped. Default empty.
	 * @return bool[] Array of return values, grouped by key. Each value is either
	 *                true on success, or false if the contents were not deleted.
	 */
	public function delete_multiple( array $keys, $group = '' ) {
		$values = array();

		foreach ( $keys as $key ) {
			$values[ $key ] = $this->delete( $key, $group );
		}

		++$this->stats['delete_multi'];
	   
		return $values;
	}

	/**
	 * Increments numeric cache item's value.
	 *
	 * @param int|string $key    The cache key to increment.
	 * @param int        $offset Optional. The amount by which to increment the item's value.
	 *                           Default 1.
	 * @param string     $group  Optional. The group the key is in. Default 'default'.
	 * @return int|false The item's new value on success, false on failure.
	 */
	public function incr( $key, $offset = 1, $group = 'default' ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$derived_key = $this->build_key( $key, $group );
	   
		// If group is ignored or backend not available, use internal cache.
		if ( in_array( $group, $this->ignored_groups ) || ! $this->backend_connected ) {
			if ( ! isset( $this->cache[ $derived_key ] ) ) {
				return false;
			}
		   
			if ( ! is_numeric( $this->cache[ $derived_key ] ) ) {
				$this->cache[ $derived_key ] = 0;
			}
		   
			$this->cache[ $derived_key ] += $offset;
		   
			if ( $this->cache[ $derived_key ] < 0 ) {
				$this->cache[ $derived_key ] = 0;
			}
		   
			return $this->cache[ $derived_key ];
		}
	   
		// Use backend-specific increment.
		$this->timer_start();
	   
		$result = false;
		if ( 'memcached' === $this->backend ) {
			// Make sure the value exists and is numeric.
			$value = $this->get( $key, $group );
			if ( false === $value ) {
				// Create the value if it doesn't exist.
				$this->set( $key, $offset, $group );
				$result = $offset;
			} elseif ( ! is_numeric( $value ) ) {
				// Convert non-numeric to numeric.
				$this->set( $key, 0, $group );
				$result = $offset;
			} else {
				// Increment existing value.
				$result = $this->client->increment( $derived_key, $offset );
				if ( false === $result ) {
					$value += $offset;
					$this->set( $key, $value, $group );
					$result = $value;
				}
			}
		} elseif ( 'redis' === $this->backend ) {
			// Redis safely handles incrementation.
			$result = $this->client->incrBy( $derived_key, $offset );
		}
	   
		$elapsed = $this->timer_stop();
		$this->group_ops_stats( 'incr', $derived_key, $group, null, $elapsed );
	   
		// Update internal cache.
		if ( false !== $result ) {
			$this->cache[ $derived_key ] = $result;
		}
	   
		++$this->stats['incr'];
	   
		return $result;
	}

	/**
	 * Decrements numeric cache item's value.
	 *
	 * @param int|string $key    The cache key to decrement.
	 * @param int        $offset Optional. The amount by which to decrement the item's value.
	 *                           Default 1.
	 * @param string     $group  Optional. The group the key is in. Default 'default'.
	 * @return int|false The item's new value on success, false on failure.
	 */
	public function decr( $key, $offset = 1, $group = 'default' ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$derived_key = $this->build_key( $key, $group );
	   
		// If group is ignored or backend not available, use internal cache.
		if ( in_array( $group, $this->ignored_groups ) || ! $this->backend_connected ) {
			if ( ! isset( $this->cache[ $derived_key ] ) ) {
				return false;
			}
		   
			if ( ! is_numeric( $this->cache[ $derived_key ] ) ) {
				$this->cache[ $derived_key ] = 0;
			}
		   
			$this->cache[ $derived_key ] -= $offset;
		   
			if ( $this->cache[ $derived_key ] < 0 ) {
				$this->cache[ $derived_key ] = 0;
			}
		   
			return $this->cache[ $derived_key ];
		}
	   
		// Use backend-specific decrement.
		$this->timer_start();
	   
		$result = false;
		if ( 'memcached' === $this->backend ) {
			// Make sure the value exists and is numeric.
			$value = $this->get( $key, $group );
			if ( false === $value ) {
				// Create the value if it doesn't exist.
				$this->set( $key, 0, $group );
				$result = 0;
			} elseif ( ! is_numeric( $value ) ) {
				// Convert non-numeric to numeric.
				$this->set( $key, 0, $group );
				$result = 0;
			} else {
				// Decrement existing value.
				$result = $this->client->decrement( $derived_key, $offset );
				if ( false === $result ) {
					$value -= $offset;
					if ( $value < 0 ) {
						$value = 0;
					}
					$this->set( $key, $value, $group );
					$result = $value;
				}
			}
		} elseif ( 'redis' === $this->backend ) {
			// Redis safely handles decrementation and ensures value is never < 0.
			$value = $this->get( $key, $group );
		   
			if ( false === $value || ! is_numeric( $value ) ) {
				$this->set( $key, 0, $group );
				$result = 0;
			} else {
				$new_value = max( 0, $value - $offset );
				$this->set( $key, $new_value, $group );
				$result = $new_value;
			}
		}
	   
		$elapsed = $this->timer_stop();
		$this->group_ops_stats( 'decr', $derived_key, $group, null, $elapsed );
	   
		// Update internal cache.
		if ( false !== $result ) {
			$this->cache[ $derived_key ] = $result;
		}
	   
		++$this->stats['decr'];
	   
		return $result;
	}

	/**
	 * Clears the object cache of all data.
	 *
	 * @return true Always returns true.
	 */
	public function flush() {
		$this->cache = array();

		if ( $this->backend_connected ) {
			$this->timer_start();
		   
			if ( 'memcached' === $this->backend ) {
				$result = $this->client->flush();
			} elseif ( 'redis' === $this->backend ) {
				$result = $this->client->flushAll();
			}
		   
			$elapsed = $this->timer_stop();
			$this->group_ops_stats( 'flush', 'all', 'all', null, $elapsed );
		}

		++$this->stats['flush'];
	   
		return true;
	}

	/**
	 * Removes all cache items in a group.
	 *
	 * @param string $group Name of group to remove from cache.
	 * @return true Always returns true.
	 */
	public function flush_group( $group ) {
		if ( empty( $group ) ) {
			return false;
		}

		if ( $this->backend_connected ) {
			// For Redis, we can potentially use a pattern delete if the extension supports it.
			if ( 'redis' === $this->backend && method_exists( $this->client, 'keys' ) ) {
				$prefix = $this->key_salt;
			   
				if ( in_array( $group, $this->global_groups ) ) {
					$prefix .= $this->global_prefix;
				} else {
					$prefix .= $this->blog_prefix;
				}
			   
				$pattern = preg_replace( '/\s+/', '', "$prefix:$group:*" );
			   
				// Get all matching keys.
				$keys = $this->client->keys( $pattern );
			   
				// Delete them in batches to avoid performance issues.
				if ( ! empty( $keys ) ) {
					$chunks = array_chunk( $keys, 100 );
					foreach ( $chunks as $chunk ) {
						$this->client->del( $chunk );
					}
				}
			}
		}

		// Remove matching items from internal cache.
		foreach ( $this->cache as $key => $value ) {
			if ( strpos( $key, ":$group:" ) !== false ) {
				unset( $this->cache[ $key ] );
			}
		}

		return true;
	}

	/**
	 * Sets the list of global cache groups.
	 *
	 * @param string|string[] $groups List of groups that are global.
	 */
	public function add_global_groups( $groups ) {
		$groups = (array) $groups;

		$this->global_groups = array_unique( array_merge( $this->global_groups, $groups ) );
	}

	/**
	 * Sets the list of non-persistent groups.
	 *
	 * @param string|string[] $groups List of groups that are non-persistent.
	 */
	public function add_non_persistent_groups( $groups ) {
		$groups = (array) $groups;

		$this->ignored_groups = array_unique( array_merge( $this->ignored_groups, $groups ) );
	}

	/**
	 * Switches the internal blog ID.
	 *
	 * This changes the blog ID used to create keys in blog specific groups.
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function switch_to_blog( $blog_id ) {
		$blog_id           = (int) $blog_id;
		$this->blog_prefix = $this->multisite ? $blog_id . ':' : '';
	}

	/**
	 * Validates and normalizes cache expiration value
	 *
	 * @param mixed $expiration Expiration value.
	 * @return int Normalized expiration value
	 */
	protected function validate_expiration( $expiration ) {
		$expiration = ( is_array( $expiration ) || is_object( $expiration ) ) ? 0 : absint( $expiration );
	   
		// Apply max TTL if set.
		if ( 0 === $expiration && isset( $this->settings['default_ttl'] ) && $this->settings['default_ttl'] > 0 ) {
			$expiration = $this->settings['default_ttl'];
		}
	   
		// Cap at max TTL if set.
		if ( $expiration > 0 && isset( $this->settings['max_ttl'] ) && $this->settings['max_ttl'] > 0 && $this->settings['max_ttl'] < $expiration ) {
			$expiration = $this->settings['max_ttl'];
		}
	   
		return $expiration;
	}

	/**
	 * Start timing an operation
	 */
	private function timer_start() {
		$this->time_start = microtime( true );
	}

	/**
	 * Stop timing an operation and return elapsed time
	 *
	 * @return float Elapsed time in seconds
	 */
	private function timer_stop() {
		$time_total        = microtime( true ) - $this->time_start;
		$this->time_total += $time_total;
	   
		return $time_total;
	}

	/**
	 * Records stats about cache operations for debugging
	 * 
	 * @param string $op       Operation type (get, set, etc.).
	 * @param string $key      Cache key.
	 * @param string $group    Cache group.
	 * @param int    $size     Size of data (bytes).
	 * @param float  $time     Operation time (seconds).
	 * @param string $comment  Additional comment.
	 */
	private function group_ops_stats( $op, $key, $group, $size, $time, $comment = '' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}
	   
		// Log slow operations.
		if ( $time > $this->slow_op_microseconds ) {
			if ( ! isset( $this->group_ops['slow-ops'] ) ) {
				$this->group_ops['slow-ops'] = array();
			}
		   
			$backtrace = '';
			if ( function_exists( 'wp_debug_backtrace_summary' ) ) {
				$backtrace = wp_debug_backtrace_summary(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary
			}

			$this->group_ops['slow-ops'][] = array( $op, $key, $size, $time, $comment, $group, $backtrace );
		}
	   
		// Log all operations for the group.
		if ( ! isset( $this->group_ops[ $group ] ) ) {
			$this->group_ops[ $group ] = array();
		}

		$this->group_ops[ $group ][] = array( $op, $key, $size, $time, $comment );
	   
		// Update total size.
		if ( $size ) {
			$this->size_total += $size;
		}
	}

	/**
	 * Get data size estimate for stats
	 *
	 * @param mixed $data The data to measure.
	 * @return int Size in bytes
	 */
	private function get_data_size( $data ) {
		if ( is_scalar( $data ) ) {
			return strlen( strval( $data ) );
		}
	   
		return strlen( serialize( $data ) );
	}

	/**
	 * Unserialize value only if it was serialized.
	 *
	 * @param string $original Maybe unserialized original, if is needed.
	 * @return mixed Unserialized data can be any type.
	 */
	protected function maybe_unserialize( $original ) {
		if ( $this->is_serialized( $original ) ) {
			return @unserialize( $original );
		}
		return $original;
	}

	/**
	 * Serialize data, if needed.
	 *
	 * @param mixed $data Data that might be serialized.
	 * @return mixed A scalar data
	 */
	protected function maybe_serialize( $data ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			return serialize( $data );
		}

		if ( $this->is_serialized( $data, false ) ) {
			return serialize( $data );
		}

		return $data;
	}

	/**
	 * Check value to find if it was serialized.
	 *
	 * @param mixed $data   Value to check to see if was serialized.
	 * @param bool  $strict Optional. Whether to be strict about the end of the string. Default true.
	 * @return bool False if not serialized and true if it was.
	 */
	protected function is_serialized( $data, $strict = true ) {
		// If it isn't a string, it isn't serialized.
		if ( ! is_string( $data ) ) {
			return false;
		}
	   
		$data = trim( $data );
	   
		if ( 'N;' === $data ) {
			return true;
		}
	   
		if ( strlen( $data ) < 4 ) {
			return false;
		}
	   
		if ( ':' !== $data[1] ) {
			return false;
		}
	   
		if ( $strict ) {
			$lastc = substr( $data, -1 );
			if ( ';' !== $lastc && '}' !== $lastc ) {
				return false;
			}
		} else {
			$semicolon = strpos( $data, ';' );
			$brace     = strpos( $data, '}' );
			// Either ; or } must exist.
			if ( false === $semicolon && false === $brace ) {
				return false;
			}
			// But neither must be in the first X characters.
			if ( false !== $semicolon && $semicolon < 3 ) {
				return false;
			}
			if ( false !== $brace && $brace < 4 ) {
				return false;
			}
		}
	   
		$token = $data[0];
		switch ( $token ) {
			case 's':
				if ( $strict ) {
					if ( '"' !== substr( $data, -2, 1 ) ) {
						return false;
					}
				} elseif ( false === strpos( $data, '"' ) ) {
					return false;
				}
				// Fall through.
			case 'a':
			case 'O':
				return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
			case 'b':
			case 'i':
			case 'd':
				$end = $strict ? '$' : '';
				return (bool) preg_match( "/^{$token}:[0-9.E-]+;{$end}/", $data );
		}
	   
		return false;
	}

	/**
	 * Convert Redis responses into something meaningful
	 *
	 * @param mixed $response Redis response.
	 * @return bool Success or failure
	 */
	protected function parse_redis_response( $response ) {
		if ( is_bool( $response ) ) {
			return $response;
		}

		if ( is_numeric( $response ) ) {
			return (bool) $response;
		}

		if ( is_object( $response ) && method_exists( $response, 'getPayload' ) ) {
			return 'OK' === $response->getPayload();
		}

		return false;
	}

	/**
	 * Echoes the stats of the caching.
	 *
	 * Gives the cache hits, and cache misses. Also prints every cached group,
	 * key and the data.
	 */
	public function stats() {
		if ( defined( 'WP_ADMIN' ) && WP_ADMIN ) {
			echo $this->get_stats_html();
		}
	}

	/**
	 * Get HTML for the cache stats
	 *
	 * @return string HTML output
	 */
	public function get_stats_html() {
		$html  = '<div id="wisesync-cache-stats" style="padding: 20px; background: #fff; border: 1px solid #ccc; margin: 20px 0;">';
		$html .= '<h2 style="margin-top: 0;">WiseSync Advanced Object Cache Stats</h2>';
	   
		$html .= '<p>';
		$html .= '<strong>Backend:</strong> ' . esc_html( $this->backend ) . '<br>';
		$html .= '<strong>Client:</strong> ' . esc_html( $this->backend_client ) . '<br>';
		$html .= '<strong>Status:</strong> ' . ( $this->backend_connected ? 'Connected' : 'Not Connected' ) . '<br>';
		$html .= '<strong>Cache Hits:</strong> ' . (int) $this->cache_hits . '<br>';
		$html .= '<strong>Cache Misses:</strong> ' . (int) $this->cache_misses . '<br>';
		$html .= '<strong>Total Time:</strong> ' . number_format( $this->time_total * 1000, 2 ) . ' ms<br>';
		$html .= '<strong>Total Size:</strong> ' . size_format( $this->size_total, 2 );
		$html .= '</p>';
	   
		// Display operation counts.
		$html .= '<h3>Operations</h3>';
		$html .= '<ul>';
		foreach ( $this->stats as $op => $count ) {
			if ( $count > 0 ) {
				$html .= '<li>' . esc_html( $op ) . ': ' . (int) $count . '</li>';
			}
		}
		$html .= '</ul>';
	   
		// Display cache groups.
		$html  .= '<h3>Cache Groups</h3>';
		$html  .= '<ul>';
		$groups = array_keys( $this->cache );
		foreach ( $groups as $group ) {
			$size  = strlen( serialize( $this->cache[ $group ] ) );
			$html .= '<li>' . esc_html( $group ) . ' - ' . size_format( $size / 1024, 2 ) . 'k</li>';
		}
		$html .= '</ul>';
	   
		$html .= '</div>';
	   
		return $html;
	}
}

/**
 * Sets up Object Cache Global and assigns it.
 *
 * @global WP_Object_Cache $wp_object_cache
 */
function wp_cache_init() {
	global $wp_object_cache;
	$wp_object_cache = new WP_Object_Cache(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}

/**
 * Adds data to the cache, if the cache key doesn't already exist.
 *
 * @since 2.0.0
 *
 * @see WP_Object_Cache::add()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    The cache key to use for retrieval later.
 * @param mixed      $data   The data to add to the cache.
 * @param string     $group  Optional. The group to add the cache to. Enables the same key
 *                           to be used across groups. Default empty.
 * @param int        $expire Optional. When the cache data should expire, in seconds.
 *                           Default 0 (no expiration).
 * @return bool True on success, false if cache key and group already exist.
 */
function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->add( $key, $data, $group, (int) $expire );
}

/**
 * Adds multiple values to the cache in one call.
 *
 * @since 6.0.0
 *
 * @see WP_Object_Cache::add_multiple()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param array  $data   Array of keys and values to be set.
 * @param string $group  Optional. Where the cache contents are grouped. Default empty.
 * @param int    $expire Optional. When to expire the cache contents, in seconds.
 *                       Default 0 (no expiration).
 * @return bool[] Array of return values, grouped by key. Each value is either
 *                true on success, or false if cache key and group already exist.
 */
function wp_cache_add_multiple( array $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->add_multiple( $data, $group, $expire );
}

/**
 * Replaces the contents of the cache with new data.
 *
 * @since 2.0.0
 *
 * @see WP_Object_Cache::replace()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    The key for the cache data that should be replaced.
 * @param mixed      $data   The new data to store in the cache.
 * @param string     $group  Optional. The group for the cache data that should be replaced.
 *                           Default empty.
 * @param int        $expire Optional. When to expire the cache contents, in seconds.
 *                           Default 0 (no expiration).
 * @return bool True if contents were replaced, false if original value does not exist.
 */
function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->replace( $key, $data, $group, (int) $expire );
}

/**
 * Saves the data to the cache.
 *
 * Differs from wp_cache_add() and wp_cache_replace() in that it will always write data.
 *
 * @since 2.0.0
 *
 * @see WP_Object_Cache::set()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    The cache key to use for retrieval later.
 * @param mixed      $data   The contents to store in the cache.
 * @param string     $group  Optional. Where to group the cache contents. Enables the same key
 *                           to be used across groups. Default empty.
 * @param int        $expire Optional. When to expire the cache contents, in seconds.
 *                           Default 0 (no expiration).
 * @return bool True on success, false on failure.
 */
function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->set( $key, $data, $group, (int) $expire );
}

/**
 * Sets multiple values to the cache in one call.
 *
 * @since 6.0.0
 *
 * @see WP_Object_Cache::set_multiple()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param array  $data   Array of keys and values to be set.
 * @param string $group  Optional. Where the cache contents are grouped. Default empty.
 * @param int    $expire Optional. When to expire the cache contents, in seconds.
 *                       Default 0 (no expiration).
 * @return bool[] Array of return values, grouped by key. Each value is either
 *                true on success, or false on failure.
 */
function wp_cache_set_multiple( array $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->set_multiple( $data, $group, $expire );
}

/**
 * Retrieves the cache contents from the cache by key and group.
 *
 * @since 2.0.0
 *
 * @see WP_Object_Cache::get()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key   The key under which the cache contents are stored.
 * @param string     $group Optional. Where the cache contents are grouped. Default empty.
 * @param bool       $force Optional. Whether to force an update of the local cache
 *                          from the persistent cache. Default false.
 * @param bool       $found Optional. Whether the key was found in the cache (passed by reference).
 *                          Disambiguates a return of false, a storable value. Default null.
 * @return mixed|false The cache contents on success, false on failure to retrieve contents.
 */
function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $wp_object_cache;

	return $wp_object_cache->get( $key, $group, $force, $found );
}

/**
 * Retrieves multiple values from the cache in one call.
 *
 * @since 5.5.0
 *
 * @see WP_Object_Cache::get_multiple()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param array  $keys  Array of keys under which the cache contents are stored.
 * @param string $group Optional. Where the cache contents are grouped. Default empty.
 * @param bool   $force Optional. Whether to force an update of the local cache
 *                      from the persistent cache. Default false.
 * @return array Array of return values, grouped by key. Each value is either
 *               the cache contents on success, or false on failure.
 */
function wp_cache_get_multiple( $keys, $group = '', $force = false ) {
	global $wp_object_cache;

	return $wp_object_cache->get_multiple( $keys, $group, $force );
}

/**
 * Retrieves multiple values from the cache in one call.
 *
 * @see WP_Object_Cache::get_multi()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param array $groups Array of groups and keys to retrieve.
 * @return array Array of values grouped by keys.
 */
function wp_cache_get_multi( $groups ) {
	global $wp_object_cache;

	// If the object cache class doesn't have this method, create a fallback.
	if ( method_exists( $wp_object_cache, 'get_multi' ) ) {
		return $wp_object_cache->get_multi( $groups );
	} else {
		$cache = array();
   
		foreach ( $groups as $group => $keys ) {
			foreach ( $keys as $key ) {
				$cache[ $group . ':' . $key ] = wp_cache_get( $key, $group );
			}
		}
   
		return $cache;
	}
}

/**
 * Removes the cache contents matching key and group.
 *
 * @since 2.0.0
 *
 * @see WP_Object_Cache::delete()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key   What the contents in the cache are called.
 * @param string     $group Optional. Where the cache contents are grouped. Default empty.
 * @return bool True on successful removal, false on failure.
 */
function wp_cache_delete( $key, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->delete( $key, $group );
}

/**
 * Deletes multiple values from the cache in one call.
 *
 * @since 6.0.0
 *
 * @see WP_Object_Cache::delete_multiple()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param array  $keys  Array of keys to be deleted.
 * @param string $group Optional. Where the cache contents are grouped. Default empty.
 * @return bool[] Array of return values, grouped by key. Each value is either
 *                true on success, or false if the contents were not deleted.
 */
function wp_cache_delete_multiple( array $keys, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->delete_multiple( $keys, $group );
}

/**
 * Increments numeric cache item's value.
 *
 * @since 3.3.0
 *
 * @see WP_Object_Cache::incr()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    The key for the cache contents that should be incremented.
 * @param int        $offset Optional. The amount by which to increment the item's value.
 *                           Default 1.
 * @param string     $group  Optional. The group the key is in. Default empty.
 * @return int|false The item's new value on success, false on failure.
 */
function wp_cache_incr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->incr( $key, $offset, $group );
}

/**
 * Decrements numeric cache item's value.
 *
 * @since 3.3.0
 *
 * @see WP_Object_Cache::decr()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    The cache key to decrement.
 * @param int        $offset Optional. The amount by which to decrement the item's value.
 *                           Default 1.
 * @param string     $group  Optional. The group the key is in. Default empty.
 * @return int|false The item's new value on success, false on failure.
 */
function wp_cache_decr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->decr( $key, $offset, $group );
}

/**
 * Removes all cache items.
 *
 * @since 2.0.0
 *
 * @see WP_Object_Cache::flush()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @return bool Always returns true.
 */
function wp_cache_flush() {
	global $wp_object_cache;

	return $wp_object_cache->flush();
}

/**
 * Removes all cache items from the in-memory runtime cache.
 *
 * @since 6.0.0
 *
 * @see WP_Object_Cache::flush()
 *
 * @return bool True on success, false on failure.
 */
function wp_cache_flush_runtime() {
	global $wp_object_cache;

	// If the object cache class doesn't have this method, create a fallback.
	if ( method_exists( $wp_object_cache, 'flush_runtime' ) ) {
		return $wp_object_cache->flush_runtime();
	} else {
		return $wp_object_cache->flush();
	}
}

/**
 * Removes all cache items in a group, if the object cache implementation supports it.
 *
 * @since 6.1.0
 *
 * @see WP_Object_Cache::flush_group()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param string $group Name of group to remove from cache.
 * @return bool True if group was flushed, false otherwise.
 */
function wp_cache_flush_group( $group ) {
	global $wp_object_cache;

	if ( method_exists( $wp_object_cache, 'flush_group' ) ) {
		return $wp_object_cache->flush_group( $group );
	}

	return false;
}

/**
 * Determines whether the object cache implementation supports a particular feature.
 *
 * @since 6.1.0
 *
 * @param string $feature Name of the feature to check for. Possible values include:
 *                        'add_multiple', 'set_multiple', 'get_multiple', 'delete_multiple',
 *                        'flush_runtime', 'flush_group'.
 * @return bool True if the feature is supported, false otherwise.
 */
function wp_cache_supports( $feature ) {
	switch ( $feature ) {
		case 'add_multiple':
		case 'set_multiple':
		case 'get_multiple':
		case 'delete_multiple':
		case 'flush_runtime':
			return true;

		case 'flush_group':
			global $wp_object_cache;
			return method_exists( $wp_object_cache, 'flush_group' );

		default:
			return false;
	}
}

/**
 * Closes the cache.
 *
 * This function has ceased to do anything since WordPress 2.5. The
 * functionality was removed along with the rest of the persistent cache.
 *
 * This does not mean that plugins can't implement this function when they need
 * to make sure that the cache is cleaned up after WordPress no longer needs it.
 *
 * @since 2.0.0
 *
 * @return true Always returns true.
 */
function wp_cache_close() {
	global $wp_object_cache;

	if ( method_exists( $wp_object_cache, 'close' ) ) {
		return $wp_object_cache->close();
	}

	return true;
}

/**
 * Adds a group or set of groups to the list of global groups.
 *
 * @since 2.6.0
 *
 * @see WP_Object_Cache::add_global_groups()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param string|array $groups A group or an array of groups to add.
 */
function wp_cache_add_global_groups( $groups ) {
	global $wp_object_cache;

	$wp_object_cache->add_global_groups( $groups );
}

/**
 * Adds a group or set of groups to the list of non-persistent groups.
 *
 * @since 2.6.0
 *
 * @param string|array $groups A group or an array of groups to add.
 */
function wp_cache_add_non_persistent_groups( $groups ) {
	global $wp_object_cache;

	$wp_object_cache->add_non_persistent_groups( $groups );
}

/**
 * Switches the internal blog ID.
 *
 * This changes the blog id used to create keys in blog specific groups.
 *
 * @since 3.5.0
 *
 * @see WP_Object_Cache::switch_to_blog()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int $blog_id Site ID.
 */
function wp_cache_switch_to_blog( $blog_id ) {
	global $wp_object_cache;

	$wp_object_cache->switch_to_blog( $blog_id );
}

/**
 * Resets internal cache keys and structures.
 *
 * If the cache back end uses global blog or site IDs as part of its cache keys,
 * this function instructs the back end to reset those keys and perform any cleanup
 * since blog or site IDs have changed since cache init.
 *
 * This function is deprecated. Use wp_cache_switch_to_blog() instead of this
 * function when preparing the cache for a blog switch. For clearing the cache
 * during unit tests, consider using wp_cache_init(). wp_cache_init() is not
 * recommended outside of unit tests as the performance penalty for using it is high.
 *
 * @since 3.0.0
 * @deprecated 3.5.0 Use wp_cache_switch_to_blog()
 * @see WP_Object_Cache::reset()
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 */
function wp_cache_reset() {
	_deprecated_function( __FUNCTION__, '3.5.0', 'wp_cache_switch_to_blog()' );

	global $wp_object_cache;

	if ( method_exists( $wp_object_cache, 'reset' ) ) {
		$wp_object_cache->reset();
	}
}
