# SyncCache: Advanced WordPress Caching System

## Table of Contents

- [Introduction](#introduction)
- [Key Features](#key-features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration Options](#configuration-options)
  - [General Settings](#general-settings)
  - [Mobile & Device Settings](#mobile--device-settings)
  - [Content Exclusions](#content-exclusions)
  - [Optimization Settings](#optimization-settings)
  - [CDN Settings](#cdn-settings)
  - [Advanced Settings](#advanced-settings)
- [API Reference](#api-reference)
  - [SyncCache Main Class](#synccache-main-class)
    - [Class: `Sync_Advanced_Cache`](#class-sync_advanced_cache)
  - [Optimization Class](#optimization-class)
    - [Class: `Sync_Optimization`](#class-sync_optimization)
- [Hooks & Filters](#hooks--filters)
  - [Actions](#actions)
  - [Filters](#filters)
- [Implementing SyncCache in Your UI](#implementing-synccache-in-your-ui)
  - [Admin Page Integration](#admin-page-integration)
  - [Admin Bar Integration](#admin-bar-integration)
  - [AJAX Integration](#ajax-integration)
- [Advanced Usage Examples](#advanced-usage-examples)
  - [Custom Cache Exclusion Logic](#custom-cache-exclusion-logic)
  - [Custom Preload Logic](#custom-preload-logic)
  - [Custom Cache Directory](#custom-cache-directory)
  - [Custom Cache Lifetime by Post Type](#custom-cache-lifetime-by-post-type)
  - [Custom Cache Key Factors](#custom-cache-key-factors)
- [Troubleshooting](#troubleshooting)
  - [Common Issues](#common-issues)
    - [Cache Not Being Created](#cache-not-being-created)
    - [Cache Not Clearing on Updates](#cache-not-clearing-on-updates)
    - [High Server Load](#high-server-load)
  - [Browser Caching Issues](#browser-caching-issues)
  - [Debug Tools](#debug-tools)
    - [Cache Status Headers](#cache-status-headers)
    - [Debug Logging](#debug-logging)
    - [Testing Cache Effectiveness](#testing-cache-effectiveness)
- [Extending SyncCache](#extending-synccache)
  - [Creating a Custom Module](#creating-a-custom-module)
  - [Loading Multiple Modules](#loading-multiple-modules)
- [Best Practices](#best-practices)
  - [Performance Optimization](#performance-optimization)
  - [Security Considerations](#security-considerations)
  - [SEO Friendly Caching](#seo-friendly-caching)
- [Changelog](#changelog)
  - [Version 1.0.0 (Initial Release)](#version-100-initial-release)

## Introduction

SyncCache is a high-performance WordPress caching system designed to significantly improve site speed and reduce server load. It provides full-page caching with device/user segmentation, extensive optimization features, and a comprehensive management interface.

## Key Features

- **Full-Page Caching**: Serves cached static versions of pages with support for device detection and user roles
- **Smart Cache Segmentation**: Creates separate caches for mobile, desktop, and tablet devices
- **User-Role Based Caching**: Optionally creates separate caches for logged-in users with different roles
- **Performance Optimization**: Includes HTML, CSS, and JavaScript minification and combination
- **Image Optimization**: Implements lazy loading for images
- **CDN Integration**: Supports content delivery networks with automatic URL rewriting
- **Intelligent Cache Invalidation**: Automatically clears relevant cache files when content changes
- **Cache Preloading**: Proactively caches important pages for optimal performance
- **Analytics & Statistics**: Tracks cache performance with detailed reporting
- **Shared Hosting Friendly**: Includes IOPS protection to prevent resource exhaustion
- **Multilingual Support**: Compatible with WPML, Polylang, and other multilingual plugins
- **WooCommerce Compatible**: Special handling for WooCommerce pages and products

## System Requirements

- WordPress 6.8 or higher
- PHP 8.0 or higher
- Write access to wp-config.php and wp-content directory
- Minimum 128MB PHP memory limit (256MB recommended)

## Installation

1. Upload the `sync-cache` directory to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Settings > SyncCache to configure options
4. The plugin will automatically set up the required `advanced-cache.php` file and add the `WP_CACHE` constant to your wp-config.php file

## Configuration Options

### General Settings

| Option | Description | Default |
|--------|-------------|---------|
| `cache_enabled` | Enable or disable caching globally | Enabled |
| `cache_lifetime` | How long pages should remain cached (in seconds) | 86400 (24 hours) |
| `enable_in_dev_mode` | Whether to enable caching in development environments | Disabled |
| `cache_logged_in_users` | Whether to cache pages for logged-in users | Disabled |
| `purge_on_post_edit` | Automatically purge relevant cache when content is updated | Enabled |
| `purge_on_comment` | Automatically purge page cache when comments are posted | Enabled |
| `preload_homepage` | Automatically cache the homepage after purging | Enabled |
| `preload_public_posts` | Automatically cache recently published posts | Enabled |
| `preload_public_taxonomies` | Automatically cache category and tag archives | Enabled |

### Mobile & Device Settings

| Option | Description | Default |
|--------|-------------|---------|
| `cache_mobile` | Enable caching for mobile devices | Enabled |
| `cache_tablet` | Enable caching for tablet devices | Enabled |
| `separate_mobile_cache` | Create separate cache files for different device types | Enabled |

### Content Exclusions

| Option | Description | Default |
|--------|-------------|---------|
| `cache_exclude_urls` | URLs that should never be cached | Admin, cart, checkout |
| `cache_exclude_cookies` | If these cookies exist, do not serve cached content | Login, cart cookies |
| `cache_exclude_user_agents` | User agents that should not receive cached content | Bots, crawlers |
| `cache_404` | Whether to cache 404 pages | Disabled |
| `cache_query_strings` | Whether to cache URLs with query parameters | Disabled |
| `allowed_query_strings` | Query parameters that should not prevent caching | search, lang |
| `cache_rest_api` | Whether to cache REST API responses | Disabled |
| `cache_ajax` | Whether to cache AJAX requests | Disabled |
| `cache_feed` | Whether to cache RSS/Atom feeds | Disabled |

### Optimization Settings

| Option | Description | Default |
|--------|-------------|---------|
| `minify_html` | Strip whitespace and comments from HTML | Enabled |
| `minify_css` | Minify CSS files | Enabled |
| `minify_js` | Minify JavaScript files | Enabled |
| `combine_css` | Combine multiple CSS files into one | Enabled |
| `combine_js` | Combine multiple JavaScript files into one | Enabled |
| `lazy_load` | Add lazy loading to images | Enabled |

### CDN Settings

| Option | Description | Default |
|--------|-------------|---------|
| `cdn_enabled` | Enable CDN integration | Disabled |
| `cdn_url` | Base URL of your CDN | Empty |
| `cdn_includes` | File types to serve from CDN | Images, CSS, JS, fonts |

### Advanced Settings

| Option | Description | Default |
|--------|-------------|---------|
| `iops_protection` | Limit file operations to prevent server overload | Enabled |
| `max_files_per_second` | Maximum files created per second | 100 |
| `warmup_method` | Method for cache preloading (auto, background, direct) | auto |
| `enable_logging` | Enable debug logging | Enabled |
| `debug_mode` | Add debug headers to cached pages | Disabled |
| `cache_analytics` | Track cache performance statistics | Enabled |
| `analytics_sampling_rate` | Percentage of requests to track | 10% |
| `admin_roles_manage_cache` | User roles that can purge cache | admin, editor |

## API Reference

### SyncCache Main Class

#### Class: `Sync_Advanced_Cache`

Main class for managing the SyncCache system.

**Constructor:**

```php
$sync_cache = new Sync\Sync_Advanced_Cache( $options );
```

**Parameters**:
- `$options` (array): Configuration options

**Public Methods**:

```php
// Plugin lifecycle
$sync_cache->activate(); // Activate the cache system
$sync_cache->deactivate(); // Deactivate the cache system

// Cache file management
$sync_cache->create_advanced_cache(); // Create the advanced-cache.php drop-in
$sync_cache->remove_advanced_cache(); // Remove the advanced-cache.php drop-in
$sync_cache->add_wp_cache_constant(); // Add WP_CACHE=true to wp-config.php
$sync_cache->remove_wp_cache_constant(); // Remove WP_CACHE from wp-config.php

// Cache purging
$sync_cache->purge_all_cache(); // Clear the entire cache
$sync_cache->purge_post_cache( $post_id ); // Clear cache for a specific post
$sync_cache->purge_url_cache( $url ); // Clear cache for a specific URL
$sync_cache->purge_home_cache(); // Clear cache for the homepage
$sync_cache->purge_archives(); // Clear cache for all archive pages
### Optimization Class

#### Class: `Sync_Optimization`

Handles optimization features like minification, combination, and lazy loading.

**Constructor:**

```php
$sync_optimization = new Sync\Sync_Optimization( $options );
```

**Parameters**:
- `$options` (array): Configuration options

**Public Methods**:

```php
// Minification
$sync_optimization->minify_html( $content ); // Minify HTML content
$sync_optimization->optimize_css(); // Optimize CSS files (minify/combine)
$sync_optimization->optimize_js(); // Optimize JS files (minify/combine)

// Lazy loading
$sync_optimization->add_lazy_loading( $content ); // Add lazy loading to images

// CDN
$sync_optimization->rewrite_urls_for_cdn( $content ); // Rewrite URLs to use CDN
```
## Hooks & Filters

SyncCache provides several hooks and filters for extending its functionality:

### Actions

```php
// Run before clearing the entire cache
do_action( 'sync_cache_before_purge_all', $cache_path );

// Run after clearing the entire cache
do_action( 'sync_cache_after_purge_all', $cache_path );

// Run before clearing cache for a specific post
do_action( 'sync_cache_before_purge_post', $post_id, $post );

// Run after clearing cache for a specific post
do_action( 'sync_cache_after_purge_post', $post_id, $post );

// Run before preloading cache
do_action( 'sync_cache_before_preload', $urls );

// Run after preloading cache
do_action( 'sync_cache_after_preload', $urls, $success_count );

// Run during cache cleanup
do_action( 'sync_cache_during_cleanup', $cache_path, $deleted_count );
```
### Filters

```php
// Filter cache lifetime for a specific post
$lifetime = apply_filters( 'sync_cache_post_lifetime', $lifetime, $post_id );

// Filter which user roles can manage cache
$roles = apply_filters( 'sync_cache_admin_roles', $roles );

// Filter URLs to exclude from caching
$excluded_urls = apply_filters( 'sync_cache_excluded_urls', $excluded_urls );

// Filter the cache key for a request
$cache_key = apply_filters( 'sync_cache_key', $cache_key, $request_uri, $device_type, $user_role );

// Filter HTML content before minification
$content = apply_filters( 'sync_cache_before_minify_html', $content );

// Filter HTML content after minification
$content = apply_filters( 'sync_cache_after_minify_html', $content );

// Filter CSS content before minification
$css = apply_filters( 'sync_cache_before_minify_css', $css );

// Filter JS content before minification
$js = apply_filters( 'sync_cache_before_minify_js', $js );

// Filter whether to exclude a page from caching
$should_skip = apply_filters( 'sync_cache_should_skip_cache', $should_skip, $request_uri );

// Filter URLs to preload
$urls = apply_filters( 'sync_cache_preload_urls', $urls );
```
## Implementing SyncCache in Your UI

### Admin Page Integration

The SyncCache admin interface is registered under Settings > SyncCache. You can add your own UI elements using the following approach:

```php
// Add a custom tab to SyncCache settings
function my_custom_sync_cache_tab( $tabs ) {
    $tabs['my_tab'] = 'My Custom Settings';
    return $tabs;
}
add_filter( 'sync_cache_setting_tabs', 'my_custom_sync_cache_tab' );

// Add content for custom tab
function my_custom_sync_cache_tab_content() {
    // Your tab content here
}
add_action( 'sync_cache_settings_tab_my_tab', 'my_custom_sync_cache_tab_content' );
```
### Admin Bar Integration

SyncCache adds a purge cache button to the admin bar. You can add custom purge options:

```php
// Add custom purge option to admin bar
function my_custom_sync_cache_admin_bar( $wp_admin_bar ) {
    $wp_admin_bar->add_node( array(
        'parent' => 'sync-cache',
        'id'     => 'sync-cache-purge-custom',
        'title'  => __( 'Purge Custom Cache', 'my-plugin' ),
        'href'   => wp_nonce_url( admin_url( 'admin-post.php?action=my_custom_cache_purge' ), 'my_custom_cache_purge' ),
    ) );
}
add_action( 'admin_bar_menu', 'my_custom_sync_cache_admin_bar', 1000 );

// Handle custom purge
function handle_custom_cache_purge() {
    // Verify nonce and user capabilities
    if ( !isset( $_GET['_wpnonce'] ) || !wp_verify_nonce( $_GET['_wpnonce'], 'my_custom_cache_purge' ) ) {
        wp_die( 'Security check failed.' );
    }

    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to purge the cache.' );
    }

    // Get the SyncCache instance
    $sync_cache = new Sync\Sync_Advanced_Cache( get_option( 'sync_cache_settings', array() ) );
    
    // Your custom purge logic here
    
    // Redirect back
    wp_safe_redirect( wp_get_referer() ?: admin_url() );
    exit;
}
add_action( 'admin_post_my_custom_cache_purge', 'handle_custom_cache_purge' );
```
### AJAX Integration

To add AJAX-based cache purging:

```php
// Enqueue script
function enqueue_my_custom_sync_cache_script() {
    wp_enqueue_script( 
        'my-custom-sync-cache', 
        plugin_dir_url( __FILE__ ) . 'js/my-custom-cache.js', 
        array( 'jquery' ), 
        '1.0.0', 
        true 
    );
    
    wp_localize_script( 'my-custom-sync-cache', 'my_cache', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'my_custom_sync_cache_nonce' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'enqueue_my_custom_sync_cache_script' );

// AJAX handler
function my_custom_sync_cache_ajax() {
    // Verify nonce
    if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'my_custom_sync_cache_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ) );
    }
    
    // Verify capabilities
    if ( !current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission.' ) );
    }
    
    // Get the SyncCache instance
    $sync_cache = new Sync\Sync_Advanced_Cache( get_option( 'sync_cache_settings', array() ) );
    
    // Your custom logic here
    
    wp_send_json_success( array( 'message' => 'Success!' ) );
}
add_action( 'wp_ajax_my_custom_sync_cache', 'my_custom_sync_cache_ajax' );
```
## Advanced Usage Examples

### Custom Cache Exclusion Logic

```php
// Exclude specific user roles from cache
function my_custom_exclude_user_roles( $should_skip, $request_uri ) {
    // Get current user
    $user = wp_get_current_user();
    
    // Exclude specific roles from caching
    $excluded_roles = array( 'wholesale_customer', 'beta_tester' );
    
    if ( array_intersect( $excluded_roles, $user->roles ) ) {
        return true; // Skip cache for these roles
    }
    
    return $should_skip; // Return original value for other cases
}
add_filter( 'sync_cache_should_skip_cache', 'my_custom_exclude_user_roles', 10, 2 );
```

```php
// Exclude pages with specific meta value
function my_custom_exclude_meta_pages( $should_skip, $request_uri ) {
    global $post;
    
    if ( is_singular() && $post && get_post_meta( $post->ID, 'exclude_from_cache', true ) ) {
        return true; // Skip cache for this page
    }
    
    return $should_skip; // Return original value for other cases
}
add_filter( 'sync_cache_should_skip_cache', 'my_custom_exclude_meta_pages', 10, 2 );
```

### Custom Preload Logic

```php
// Add WooCommerce products to preload list
function my_custom_woo_preload_urls( $urls ) {
    // Skip if WooCommerce not active
    if ( ! function_exists( 'wc_get_products' ) ) {
        return $urls;
    }
    
    // Get featured products
    $products = wc_get_products( array(
        'featured' => true,
        'limit'    => 10,
    ) );
    
    foreach ( $products as $product ) {
        $urls[] = get_permalink( $product->get_id() );
    }
    
    return $urls;
}
add_filter( 'sync_cache_preload_urls', 'my_custom_woo_preload_urls' );
```

### Custom Cache Directory

```php
// Change cache directory
function my_custom_cache_directory( $options ) {
    // Point to custom directory
    $options['cache_path'] = WP_CONTENT_DIR . '/my-custom-cache';
    
    return $options;
}
add_filter( 'sync_cache_settings', 'my_custom_cache_directory' );
```

### Custom Cache Lifetime by Post Type

```php
// Different cache lifetime for different post types
function my_custom_post_lifetime( $lifetime, $post_id ) {
    $post_type = get_post_type( $post_id );
    
    switch ( $post_type ) {
        case 'product':
            return 3600; // 1 hour for products
        case 'news':
            return 1800; // 30 minutes for news
        case 'page':
            return 604800; // 1 week for pages
        default:
            return $lifetime; // Default for everything else
    }
}
add_filter( 'sync_cache_post_lifetime', 'my_custom_post_lifetime', 10, 2 );
```

### Custom Cache Key Factors

```php
// Add geo-location to cache key
function my_custom_geo_cache_key( $cache_key, $request_uri, $device_type, $user_role ) {
    // Get user's country (you'd need a geolocation method)
    $country = my_get_user_country();
    
    if ( $country ) {
        // Append country to cache key factors
        return md5( $request_uri . $device_type . $user_role . $country );
    }
    
    return $cache_key;
}
add_filter( 'sync_cache_key', 'my_custom_geo_cache_key', 10, 4 );
```

## Troubleshooting

### Common Issues

#### Cache Not Being Created

- Check WP_CACHE constant: Ensure `define('WP_CACHE', true);` exists in wp-config.php
- Check permissions: The web server user needs write access to wp-content directory
- Check for conflicts: Disable other caching plugins
- Enable debug mode: Set debug_mode to true to see cache headers

#### Cache Not Clearing on Updates

- Check purge settings: Ensure `purge_on_post_edit` and `purge_on_comment` are enabled
- Check for plugin conflicts: Some plugins modify standard WordPress hooks
- Try manual purge: Use the admin bar "Purge Cache" option

#### High Server Load

- Check IOPS protection: Ensure `iops_protection` is enabled
- Adjust file limit: Lower `max_files_per_second` value
- Check preload settings: Disable or adjust preloading frequency

### Browser Caching Issues

Add these rules to your `.htaccess` file for better browser caching:

```apache
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType text/javascript "access plus 1 month"
  ExpiresByType application/pdf "access plus 1 month"
  ExpiresByType text/html "access plus 1 day"
</IfModule>
```
### Debug Tools

#### Cache Status Headers

With debug mode enabled, SyncCache adds these headers:

- `X-SyncCache`: HIT (served from cache), MISS (not in cache), BYPASS (skipped cache)
- `X-SyncCache-Created`: When the cache file was created
- `X-SyncCache-Age`: How old the cache file is in seconds
- `X-SyncCache-Key`: The cache key used for this request
- `X-SyncCache-File`: The cache file path

#### Debug Logging

Enable logging to track cache operations in `/wp-content/cache/sync-cache/logs/cache-YYYY-MM-DD.log`

#### Testing Cache Effectiveness

Use these tools to verify caching:

- Browser developer tools: Check the Network tab and look for X-SyncCache headers
- WordPress admin: Visit the Cache Statistics page
- External tools: Use GTmetrix or Pingdom to verify page speed improvements

## Extending SyncCache

### Creating a Custom Module

```php
/**
 * Custom SyncCache Module
 *
 * @package SyncCache
 */

namespace Sync;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    die( 'No direct access.' );
}

/**
 * My_Custom_Module class.
 */
class My_Custom_Module {
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
        
        // Register hooks
        add_action( 'init', array( $this, 'init' ) );
        add_filter( 'sync_cache_settings', array( $this, 'add_settings' ) );
    }

    /**
     * Initialize.
     */
    public function init() {
        // Your initialization code here
    }

    /**
     * Add settings.
     *
     * @param array $settings Existing settings.
     * @return array Modified settings.
     */
    public function add_settings( $settings ) {
        // Add your custom settings
        $settings['my_custom_setting'] = true;
        
        return $settings;
    }
}

// Initialize
$sync_custom = new My_Custom_Module( get_option( 'sync_cache_settings', array() ) );
```
```

### Loading Multiple Modules

```php
/**
 * SyncCache Extension Loader
 *
 * @package SyncCache
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    die( 'No direct access.' );
}

// Get plugin options
$options = get_option( 'sync_cache_settings', array() );

// Define modules to load
$modules = array(
    'custom-module' => array(
        'file'  => 'class-custom-module.php',
        'class' => 'Sync\\My_Custom_Module',
    ),
    'another-module' => array(
        'file'  => 'class-another-module.php',
        'class' => 'Sync\\Another_Module',
    ),
);

// Load each module
foreach ( $modules as $module => $data ) {
    if ( file_exists( plugin_dir_path( __FILE__ ) . 'modules/' . $data['file'] ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'modules/' . $data['file'];
        
        if ( class_exists( $data['class'] ) ) {
            new $data['class']( $options );
        }
    }
}
```

## Best Practices

### Performance Optimization

- Balance cache lifetime: Shorter cache times mean fresher content but more server load
- Use cache segmentation wisely: Each segment multiplies the number of cache files
- Be cautious with minification: Test thoroughly, especially JS minification
- Consider CDN for assets: Offload static files to a CDN for better performance
- Monitor cache size: Large cache directories can impact server performance

### Security Considerations

- Protect cache directory: Add .htaccess rules to prevent direct access
- Sanitize inputs: Always sanitize and validate user inputs
- Use nonces: Always verify nonces for admin actions
- Check user capabilities: Verify user permissions before cache operations
- Be careful with sensitive content: Exclude pages with sensitive user data from caching

### SEO Friendly Caching

- Cache query parameters selectively: Cache parameters like "lang" but exclude session IDs
- Preserve canonical tags: Ensure canonical URLs are properly maintained
- Respect robots.txt: Don't cache pages excluded by robots.txt
- Handle pagination properly: Ensure paginated content is cached correctly
- Cache mobile pages separately: Mobile-optimized content should have separate cache

## Changelog

### Version 1.0.0 (Initial Release)

- Full-page caching system with device/user segmentation
- HTML, CSS, and JavaScript minification and combination
- Lazy loading for images
- CDN integration with URL rewriting
- Intelligent cache invalidation
- Cache preloading and analytics
- IOPS protection for shared hosting
- Multilingual support