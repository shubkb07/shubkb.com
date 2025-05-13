# WiseSync Advanced Object Cache Documentation

## Table of Contents

- [Introduction](#introduction)
- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Configuration via Constants](#configuration-via-constants)
  - [Configuration via Global Variable](#configuration-via-global-variable)
- [Backend Options](#backend-options)
  - [Auto Detection](#auto-detection)
  - [Redis](#redis)
  - [Memcached](#memcached)
  - [WordPress Default](#wordpress-default)
- [Advanced Configuration](#advanced-configuration)
  - [Global Groups](#global-groups)
  - [Ignored Groups](#ignored-groups)
  - [Cache Expiration](#cache-expiration)
  - [Compression Settings](#compression-settings)
  - [Debug Settings](#debug-settings)
- [Multisite Support](#multisite-support)
- [Performance Monitoring](#performance-monitoring)
- [Troubleshooting](#troubleshooting)
- [Frequently Asked Questions](#frequently-asked-questions)

## Introduction

WiseSync Advanced Object Cache is a powerful drop-in plugin for WordPress that provides a persistent object cache using either Memcached or Redis, with smart fallbacks to the built-in WordPress object cache. It dynamically detects and selects the best available backend, ensuring maximum performance and reliability for your WordPress site.

## Features

- **Dynamic Backend Selection**: Automatically detects and uses the best available cache backend (Redis or Memcached)
- **Seamless Fallback**: Falls back to WordPress default object cache if persistent backends are unavailable
- **High Performance**: Optimized for speed and efficiency with minimal overhead
- **Multisite Compatible**: Full support for WordPress multisite installations
- **Comprehensive Cache API**: Supports all WordPress cache functions including the latest additions
- **Smart Expiration Management**: Configurable TTL settings with defaults and maximums
- **Flexible Configuration**: Easy to configure via constants or global variables
- **Detailed Monitoring**: Built-in stats tracking for performance analysis
- **Group Management**: Support for global groups and non-persistent groups
- **Production Ready**: Thoroughly tested and hardened for production environments

## Installation

1. Download the `object-cache.php` file
2. Upload it to your WordPress site's `wp-content` directory
3. If necessary, configure the plugin using constants in your `wp-config.php` file
4. That's it! WordPress will automatically use the cache

Note: If you're replacing an existing object cache implementation, make sure to flush your cache after installing.

## Configuration

WiseSync Advanced Object Cache works with zero configuration thanks to its auto-detection feature. However, you can customize its behavior in two ways:

### Configuration via Constants

Define constants in your `wp-config.php` file before WordPress loads:

```php
// Select a specific backend (default: 'auto')
define('WISESYNC_BACKEND', 'redis'); // Options: 'auto', 'redis', 'memcached', 'wordpress'

// Redis settings
define('WISESYNC_REDIS_HOST', '127.0.0.1');
define('WISESYNC_REDIS_PORT', 6379);
define('WISESYNC_REDIS_PASSWORD', 'your-password'); // Optional
define('WISESYNC_REDIS_DATABASE', 0); // Optional

// Memcached settings
define('WISESYNC_MEMCACHED_SERVERS', array('127.0.0.1:11211')); // Can be a single string or array

// Cache behavior
define('WISESYNC_MAX_TTL', 2592000); // Maximum cache lifetime in seconds (default: 30 days)
define('WISESYNC_DEFAULT_TTL', 86400); // Default TTL if none specified (default: 0 = no expiration)

// Debugging
define('WISESYNC_DEBUG', true); // Enable detailed debugging (default: follows WP_DEBUG)
```

### Configuration via Global Variable

For more complex configurations or dynamic settings, you can use the global variable approach:

```php
$sync_cache_settings = array(
    'backend' => 'auto',
    'servers' => array(
        'memcached' => array(
            'default' => array('127.0.0.1:11211')
        ),
        'redis' => array(
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'database' => 0
        )
    ),
    'global_groups' => array('users', 'userlogins', 'usermeta', 'site-options'),
    'ignored_groups' => array('counts', 'plugins'),
    'max_ttl' => 2592000,
    'default_ttl' => 0
);
```

This variable must be defined before the object cache file is loaded.

## Backend Options

### Auto Detection
When backend is set to 'auto' (the default), WiseSync detects the available backends in this order:

1. Redis (via either native extension or Predis library)
2. Memcached (via either Memcached or Memcache extension)
3. WordPress default (fallback)

### Redis

Redis is the recommended backend for best performance. WiseSync supports Redis through:

- **PHP Redis Extension**: The native PHP extension for Redis
- **Predis Library**: A pure PHP implementation (slower but more widely available)

Redis configuration options:

```php
'redis' => array(
    'host' => '127.0.0.1',           // Redis server hostname
    'port' => 6379,                  // Redis server port
    'password' => null,              // Optional password
    'database' => 0,                 // Redis database index
    'timeout' => 1,                  // Connection timeout in seconds
    'retry_interval' => 100,         // Retry interval in milliseconds
    'read_timeout' => 1              // Read timeout in seconds
)
```

### Memcached

Memcached is supported through:

- **Memcached Extension**: The recommended PHP extension
- **Memcache Extension**: Legacy PHP extension (fallback)

Memcached configuration options:

```php
'memcached' => array(
    'default' => array('127.0.0.1:11211')  // Array of servers in 'host:port' format
)
```

For more complex setups with multiple buckets:

```php
'memcached' => array(
    'bucket1' => array('server1:11211', 'server2:11211'),
    'bucket2' => array('server3:11211')
)
```

### WordPress Default

If neither Redis nor Memcached is available, or if you explicitly set backend to 'wordpress' or 'none', WiseSync will use the standard WordPress object cache, which stores objects in memory for the duration of the request.

## Advanced Configuration

### Global Groups
Global groups are shared across all sites in a multisite installation. Default global groups include:

```php
array(
    'users',
    'userlogins',
    'usermeta',
    'user_meta',
    'site-transient',
    'site-options',
    'site-lookup',
    'blog-lookup',
    'blog-details',
    'rss',
    'WP_Object_Cache_global'
)
```

You can modify this list:

```php
define('WISESYNC_GLOBAL_GROUPS', array('users', 'userlogins', 'custom-global-group'));
```

### Ignored Groups

Ignored groups are never stored in the persistent cache (Redis/Memcached) and are only kept in the local memory cache. Default ignored groups:

```php
array('counts', 'plugins')
```

You can modify this list:

```php
define('WISESYNC_IGNORED_GROUPS', array('counts', 'plugins', 'my-local-only-group'));
```
### Cache Expiration

WiseSync provides flexible control over cache expiration:

- **Default TTL**: The default time-to-live for cache objects if no expiration is specified
- **Max TTL**: The maximum allowed TTL to prevent very long-lived cache objects

```php
define('WISESYNC_DEFAULT_TTL', 86400);  // 1 day in seconds
define('WISESYNC_MAX_TTL', 2592000);    // 30 days in seconds
```

### Compression Settings

For Memcached, you can configure compression thresholds:

```php
$sync_cache_settings = array(
    'memcached_compression_threshold' => 20000,  // Only compress values larger than this (bytes)
    'memcached_compression_factor' => 0.2        // Compression factor (0.0-1.0)
);
```

For Redis, you can enable PHP-level compression:

```php
$sync_cache_settings = array(
    'redis_compression' => true
);
```
### Debug Settings

Enable detailed debugging to track cache performance:

```php
define('WISESYNC_DEBUG', true);
```

You can also set the threshold for logging slow operations:

```php
$sync_cache_settings = array(
    'slow_op_microseconds' => 0.005  // 5ms threshold for slow operations
);
```

## Multisite Support

WiseSync has full support for WordPress multisite installations. It automatically detects multisite and:

- Uses proper prefixing for keys to prevent collisions between sites
- Supports global groups shared across all sites
- Handles switch_to_blog() properly for accessing other sites' caches

No additional configuration is needed for multisite support.
## Performance Monitoring

WiseSync includes built-in performance monitoring to help you optimize your cache usage. When WP_DEBUG is enabled or WISESYNC_DEBUG is set to true, statistics are collected about cache operations.

You can view these stats in the WordPress admin by adding this code to your theme or plugin:

```php
function display_wisesync_cache_stats() {
    global $wp_object_cache;
    if (is_object($wp_object_cache) && method_exists($wp_object_cache, 'stats')) {
        $wp_object_cache->stats();
    }
}
add_action('admin_footer', 'display_wisesync_cache_stats');
```

Stats include:

- Cache hits and misses
- Total cache operation time
- Size of cached data
- Operation counts by type
- Slow operations

## Troubleshooting

### Cache Not Working

- Check if the object-cache.php file is in the wp-content directory
- Verify that the chosen backend (Redis/Memcached) is installed and running on your server
- Check connection settings (host, port, password)
- Temporarily enable debugging with `define('WISESYNC_DEBUG', true);`

### Slow Performance

- Check the stats to identify slow operations
- Consider switching to a different backend (Redis is generally faster than Memcached)
- Make sure your cache server has enough memory allocated
- Check network latency if using a remote cache server

### Site Breaking After Installation

If your site breaks after installing WiseSync:

- Remove the object-cache.php file from wp-content
- If using Redis or Memcached, make sure the PHP extension is properly installed
- Check for configuration errors in your wp-config.php

## Frequently Asked Questions

### Q: Which backend is better, Redis or Memcached?
**A:** Redis is generally recommended for WordPress caching as it offers more features, better persistence, and sometimes better performance. However, if Memcached is already configured in your environment, it works perfectly well too.

### Q: Will WiseSync work with other caching plugins?
**A:** Yes, WiseSync is compatible with page caching plugins (like WP Super Cache, W3 Total Cache, etc.) as it operates at a different level (object caching vs page caching). However, you should disable any object caching features in those plugins.

### Q: How do I know if it's working?
**A:** With debugging enabled, you can see statistics in the admin area. You should observe a high cache hit ratio and quick response times. You can also check your Redis/Memcached server to see if it's receiving requests.

### Q: Can I use WiseSync on shared hosting?
**A:** It depends on your hosting provider. Some shared hosts support Redis or Memcached, while others don't. If neither is available, WiseSync will fall back to the default WordPress object cache.

### Q: How do I flush the cache?
**A:** You can use standard WordPress functions like `wp_cache_flush()`. Most cache-aware plugins will also flush the cache when needed automatically.