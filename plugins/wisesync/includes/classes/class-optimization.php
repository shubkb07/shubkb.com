<?php
/**
 * SyncCache Optimization Class
 *
 * This class handles various optimization features like minification, lazy loading, etc.
 *
 * @package SyncCache
 */

namespace Sync;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access.' );
}

/**
 * Sync_Optimization class.
 */
class Sync_Optimization {
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
		
		// Initialize optimization features.
		$this->init();
	}

	/**
	 * Initialize optimization features.
	 */
	public function init() {
		// Only initialize if not in admin area.
		if ( is_admin() ) {
			return;
		}
		
		// Minify HTML.
		if ( ! empty( $this->options['minify_html'] ) ) {
			add_action( 'template_redirect', array( $this, 'start_html_minification' ), 999 );
		}
		
		// Handle CSS optimization.
		if ( ! empty( $this->options['minify_css'] ) || ! empty( $this->options['combine_css'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'optimize_css' ), 999 );
		}
		
		// Handle JS optimization.
		if ( ! empty( $this->options['minify_js'] ) || ! empty( $this->options['combine_js'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'optimize_js' ), 999 );
		}
		
		// Lazy loading.
		if ( ! empty( $this->options['lazy_load'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_lazy_load_scripts' ) );
			add_filter( 'the_content', array( $this, 'add_lazy_loading' ) );
		}
		
		// CDN rewriting.
		if ( ! empty( $this->options['cdn_enabled'] ) && ! empty( $this->options['cdn_url'] ) && $this->options['cdn_url'] !== '{{CDN_URL}}' ) {
			add_action( 'template_redirect', array( $this, 'setup_cdn_rewriting' ), 1 );
		}
	}

	/**
	 * Start HTML minification.
	 */
	public function start_html_minification() {
		// Skip if this is an admin or AJAX request.
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		
		// Skip if this is a REST API request.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		
		// Start output buffering.
		ob_start( array( $this, 'minify_html' ) );
	}

	/**
	 * Minify HTML content.
	 *
	 * @param string $content HTML content.
	 * @return string Minified HTML.
	 */
	public function minify_html( $content ) {
		// Skip if empty.
		if ( empty( $content ) ) {
			return $content;
		}
		
		// Skip if not HTML.
		if ( strpos( $content, '<html' ) === false || strpos( $content, '</html>' ) === false ) {
			return $content;
		}
		
		// Skip if already minified.
		if ( strpos( $content, '<!-- Minified by SyncCache -->' ) !== false ) {
			return $content;
		}
		
		// Simple minification patterns.
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
		
		// Apply minification.
		$minified = preg_replace( $search, $replace, $content );
		
		// Add minification signature.
		$minified = str_replace( '</head>', '<!-- Minified by SyncCache --></head>', $minified );
		
		return $minified;
	}

	/**
	 * Optimize CSS.
	 */
	public function optimize_css() {
		// Get all enqueued styles.
		global $wp_styles;
		
		// Skip if no styles.
		if ( ! is_object( $wp_styles ) || empty( $wp_styles->queue ) ) {
			return;
		}
		
		// Skip if this is an admin or AJAX request.
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		
		// Get all enqueued styles.
		$styles = array();
		
		foreach ( $wp_styles->queue as $handle ) {
			// Get style info.
			$style = $wp_styles->registered[ $handle ];
			
			// Skip if it has dependencies (will break if combined).
			if ( ! empty( $style->deps ) ) {
				continue;
			}
			
			// Skip if it has conditional comments.
			if ( isset( $style->extra['conditional'] ) ) {
				continue;
			}
			
			// Skip if it's a remote URL.
			if ( isset( $style->src ) && strpos( $style->src, site_url() ) === false ) {
				continue;
			}
			
			// Add to list.
			$styles[] = $style;
		}
		
		// Skip if no styles to optimize.
		if ( empty( $styles ) ) {
			return;
		}
		
		// Process each style.
		foreach ( $styles as $style ) {
			// Get full path.
			$path = ABSPATH . str_replace( site_url(), '', $style->src );
			
			// Skip if file doesn't exist.
			if ( ! file_exists( $path ) ) {
				continue;
			}
			
			// Get content.
			$content = file_get_contents( $path );
			
			// Skip if empty.
			if ( empty( $content ) ) {
				continue;
			}
			
			// Minify if enabled.
			if ( ! empty( $this->options['minify_css'] ) ) {
				$content = $this->minify_css( $content );
			}
			
			// Generate cache path.
			$cache_dir = $this->get_cache_path() . '/css';
			
			// Create directory if it doesn't exist.
			if ( ! is_dir( $cache_dir ) ) {
				@mkdir( $cache_dir, 0755, true );
			}
			
			// Generate filename.
			$filename = basename( $style->src );
			
			// Add min suffix if not already there.
			if ( strpos( $filename, '.min.' ) === false ) {
				$filename = str_replace( '.css', '.min.css', $filename );
			}
			
			// Full cache path.
			$cache_file = $cache_dir . '/' . $filename;
			
			// Save to cache.
			file_put_contents( $cache_file, $content );
			
			// Update URL.
			$cache_url = content_url( 'cache/sync-cache/css/' . $filename );
			
			// Deregister original and register optimized version.
			wp_deregister_style( $style->handle );
			wp_register_style( $style->handle, $cache_url, array(), null );
			wp_enqueue_style( $style->handle );
		}
		
		// Combine CSS if enabled.
		if ( ! empty( $this->options['combine_css'] ) ) {
			$this->combine_css();
		}
	}

	/**
	 * Minify CSS content.
	 *
	 * @param string $css CSS content.
	 * @return string Minified CSS.
	 */
	private function minify_css( $css ) {
		// Skip if empty.
		if ( empty( $css ) ) {
			return $css;
		}
		
		// Remove comments.
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		
		// Remove whitespace.
		$css = preg_replace( '/\s+/', ' ', $css );
		
		// Remove spaces around operators.
		$css = preg_replace( '/\s*({|}|,|;|:|>|\+|~)\s*/', '$1', $css );
		
		// Remove leading zeros from integers and decimal points.
		$css = preg_replace( '/(^|[^0-9])0+([0-9]+)/', '$1$2', $css );
		$css = preg_replace( '/(^|[^0-9])0+(\.[0-9]+)/', '$1$2', $css );
		
		// Remove unnecessary semicolons.
		$css = str_replace( ';}', '}', $css );
		
		return trim( $css );
	}

	/**
	 * Combine CSS files.
	 */
	private function combine_css() {
		// Get all enqueued styles.
		global $wp_styles;
		
		// Skip if no styles.
		if ( ! is_object( $wp_styles ) || empty( $wp_styles->queue ) ) {
			return;
		}
		
		// Get all enqueued styles.
		$styles = array();
		
		foreach ( $wp_styles->queue as $handle ) {
			// Get style info.
			$style = $wp_styles->registered[ $handle ];
			
			// Skip if it has conditional comments.
			if ( isset( $style->extra['conditional'] ) ) {
				continue;
			}
			
			// Skip if it's a remote URL.
			if ( isset( $style->src ) && strpos( $style->src, site_url() ) === false ) {
				continue;
			}
			
			// Add to list.
			$styles[] = $style;
		}
		
		// Skip if less than 2 styles (no need to combine).
		if ( count( $styles ) < 2 ) {
			return;
		}
		
		// Combined content.
		$combined = '';
		
		// Handles to deregister.
		$handles_to_remove = array();
		
		// Combine styles.
		foreach ( $styles as $style ) {
			// Get full path.
			$path = ABSPATH . str_replace( site_url(), '', $style->src );
			
			// Skip if file doesn't exist.
			if ( ! file_exists( $path ) ) {
				continue;
			}
			
			// Get content.
			$content = file_get_contents( $path );
			
			// Skip if empty.
			if ( empty( $content ) ) {
				continue;
			}
			
			// Add separator.
			$combined .= "/* " . $style->handle . " */\n";
			
			// Add content.
			$combined .= $content . "\n";
			
			// Add to deregister list.
			$handles_to_remove[] = $style->handle;
		}
		
		// Skip if no combined content.
		if ( empty( $combined ) ) {
			return;
		}
		
		// Minify if enabled.
		if ( ! empty( $this->options['minify_css'] ) ) {
			$combined = $this->minify_css( $combined );
		}
		
		// Generate cache path.
		$cache_dir = $this->get_cache_path() . '/css';
		
		// Create directory if it doesn't exist.
		if ( ! is_dir( $cache_dir ) ) {
			@mkdir( $cache_dir, 0755, true );
		}
		
		// Generate unique filename.
		$filename = 'combined-' . md5( $combined ) . '.css';
		
		// Full cache path.
		$cache_file = $cache_dir . '/' . $filename;
		
		// Save to cache.
		file_put_contents( $cache_file, $combined );
		
		// Update URL.
		$cache_url = content_url( 'cache/sync-cache/css/' . $filename );
		
		// Deregister original styles.
		foreach ( $handles_to_remove as $handle ) {
			wp_deregister_style( $handle );
		}
		
		// Register combined style.
		wp_register_style( 'sync-combined-css', $cache_url, array(), null );
		wp_enqueue_style( 'sync-combined-css' );
	}

	/**
	 * Optimize JavaScript.
	 */
	public function optimize_js() {
		// Get all enqueued scripts.
		global $wp_scripts;
		
		// Skip if no scripts.
		if ( ! is_object( $wp_scripts ) || empty( $wp_scripts->queue ) ) {
			return;
		}
		
		// Skip if this is an admin or AJAX request.
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		
		// Get all enqueued scripts.
		$scripts = array();
		
		foreach ( $wp_scripts->queue as $handle ) {
			// Get script info.
			$script = $wp_scripts->registered[ $handle ];
			
			// Skip if it has dependencies (will break if combined).
			if ( ! empty( $script->deps ) ) {
				continue;
			}
			
			// Skip if it has conditional comments.
			if ( isset( $script->extra['conditional'] ) ) {
				continue;
			}
			
			// Skip if it's in footer (will break if combined).
			if ( isset( $script->extra['group'] ) && $script->extra['group'] === 1 ) {
				continue;
			}
			
			// Skip if it's a remote URL.
			if ( isset( $script->src ) && strpos( $script->src, site_url() ) === false ) {
				continue;
			}
			
			// Add to list.
			$scripts[] = $script;
		}
		
		// Skip if no scripts to optimize.
		if ( empty( $scripts ) ) {
			return;
		}
		
		// Process each script.
		foreach ( $scripts as $script ) {
			// Get full path.
			$path = ABSPATH . str_replace( site_url(), '', $script->src );
			
			// Skip if file doesn't exist.
			if ( ! file_exists( $path ) ) {
				continue;
			}
			
			// Get content.
			$content = file_get_contents( $path );
			
			// Skip if empty.
			if ( empty( $content ) ) {
				continue;
			}
			
			// Minify if enabled.
			if ( ! empty( $this->options['minify_js'] ) ) {
				$content = $this->minify_js( $content );
			}
			
			// Generate cache path.
			$cache_dir = $this->get_cache_path() . '/js';
			
			// Create directory if it doesn't exist.
			if ( ! is_dir( $cache_dir ) ) {
				@mkdir( $cache_dir, 0755, true );
			}
			
			// Generate filename.
			$filename = basename( $script->src );
			
			// Add min suffix if not already there.
			if ( strpos( $filename, '.min.' ) === false ) {
				$filename = str_replace( '.js', '.min.js', $filename );
			}
			
			// Full cache path.
			$cache_file = $cache_dir . '/' . $filename;
			
			// Save to cache.
			file_put_contents( $cache_file, $content );
			
			// Update URL.
			$cache_url = content_url( 'cache/sync-cache/js/' . $filename );
			
			// Deregister original and register optimized version.
			wp_deregister_script( $script->handle );
			wp_register_script( $script->handle, $cache_url, array(), null );
			wp_enqueue_script( $script->handle );
		}
		
		// Combine JS if enabled.
		if ( ! empty( $this->options['combine_js'] ) ) {
			$this->combine_js();
		}
	}

	/**
	 * Minify JavaScript content.
	 *
	 * @param string $js JavaScript content.
	 * @return string Minified JavaScript.
	 */
	private function minify_js( $js ) {
		// Skip if empty.
		if ( empty( $js ) ) {
			return $js;
		}
		
		// Very simple minification (for production, use a more robust solution).
		// Remove comments.
		$js = preg_replace( '/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/', '', $js );
		
		// Remove whitespace.
		$js = preg_replace( '/\s+/', ' ', $js );
		
		// Remove whitespace around specific tokens.
		$js = preg_replace( '/\s*({|}|=|\(|\)|\[|\]|,|;|:|\+|~|>|\|)\s*/', '$1', $js );
		
		return trim( $js );
	}

	/**
	 * Combine JavaScript files.
	 */
	private function combine_js() {
		// Get all enqueued scripts.
		global $wp_scripts;
		
		// Skip if no scripts.
		if ( ! is_object( $wp_scripts ) || empty( $wp_scripts->queue ) ) {
			return;
		}
		
		// Get all enqueued scripts.
		$header_scripts = array();
		$footer_scripts = array();
		
		foreach ( $wp_scripts->queue as $handle ) {
			// Get script info.
			$script = $wp_scripts->registered[ $handle ];
			
			// Skip if it has conditional comments.
			if ( isset( $script->extra['conditional'] ) ) {
				continue;
			}
			
			// Skip if it's a remote URL.
			if ( isset( $script->src ) && strpos( $script->src, site_url() ) === false ) {
				continue;
			}
			
			// Sort by location.
			if ( isset( $script->extra['group'] ) && $script->extra['group'] === 1 ) {
				$footer_scripts[] = $script;
			} else {
				$header_scripts[] = $script;
			}
		}
		
		// Combine header scripts.
		if ( count( $header_scripts ) >= 2 ) {
			$this->do_combine_js( $header_scripts, 'header' );
		}
		
		// Combine footer scripts.
		if ( count( $footer_scripts ) >= 2 ) {
			$this->do_combine_js( $footer_scripts, 'footer' );
		}
	}

	/**
	 * Perform JS combination.
	 *
	 * @param array  $scripts Scripts to combine.
	 * @param string $location Location (header/footer).
	 */
	private function do_combine_js( $scripts, $location ) {
		// Combined content.
		$combined = '';
		
		// Handles to deregister.
		$handles_to_remove = array();
		
		// Dependencies.
		$dependencies = array();
		
		// Combine scripts.
		foreach ( $scripts as $script ) {
			// Get full path.
			$path = ABSPATH . str_replace( site_url(), '', $script->src );
			
			// Skip if file doesn't exist.
			if ( ! file_exists( $path ) ) {
				continue;
			}
			
			// Get content.
			$content = file_get_contents( $path );
			
			// Skip if empty.
			if ( empty( $content ) ) {
				continue;
			}
			
			// Add separator.
			$combined .= "/* " . $script->handle . " */\n";
			
			// Add content.
			$combined .= $content . ";\n";
			
			// Add to deregister list.
			$handles_to_remove[] = $script->handle;
			
			// Collect dependencies.
			if ( ! empty( $script->deps ) ) {
				$dependencies = array_merge( $dependencies, $script->deps );
			}
		}
		
		// Skip if no combined content.
		if ( empty( $combined ) ) {
			return;
		}
		
		// Minify if enabled.
		if ( ! empty( $this->options['minify_js'] ) ) {
			$combined = $this->minify_js( $combined );
		}
		
		// Generate cache path.
		$cache_dir = $this->get_cache_path() . '/js';
		
		// Create directory if it doesn't exist.
		if ( ! is_dir( $cache_dir ) ) {
			@mkdir( $cache_dir, 0755, true );
		}
		
		// Generate unique filename.
		$filename = 'combined-' . $location . '-' . md5( $combined ) . '.js';
		
		// Full cache path.
		$cache_file = $cache_dir . '/' . $filename;
		
		// Save to cache.
		file_put_contents( $cache_file, $combined );
		
		// Update URL.
		$cache_url = content_url( 'cache/sync-cache/js/' . $filename );
		
		// Deregister original scripts.
		foreach ( $handles_to_remove as $handle ) {
			wp_deregister_script( $handle );
		}
		
		// Register combined script.
		$handle = 'sync-combined-js-' . $location;
		wp_register_script( $handle, $cache_url, array_unique( $dependencies ), null, $location === 'footer' );
		wp_enqueue_script( $handle );
	}

	/**
	 * Enqueue lazy loading scripts.
	 */
	public function enqueue_lazy_load_scripts() {
		// Skip if this is an admin or AJAX request.
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		
		// Enqueue the script.
		wp_enqueue_script(
			'sync-lazy-load',
			plugin_dir_url( __FILE__ ) . 'assets/js/sync-optimize.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);
		
		// Enqueue the style.
		wp_enqueue_style(
			'sync-lazy-load',
			plugin_dir_url( __FILE__ ) . 'assets/css/sync-optimize.css',
			array(),
			'1.0.0'
		);
	}

	/**
	 * Add lazy loading attributes to images in content.
	 *
	 * @param string $content Post content.
	 * @return string Modified content with lazy loading.
	 */
	public function add_lazy_loading( $content ) {
		// Skip if empty.
		if ( empty( $content ) ) {
			return $content;
		}
		
		// Skip if it's an admin or feed.
		if ( is_admin() || is_feed() ) {
			return $content;
		}
		
		// Skip if it's an REST API request.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $content;
		}
		
		// Skip if the content already has lazy loading.
		if ( strpos( $content, 'data-lazy="1"' ) !== false ) {
			return $content;
		}
		
		// Set up the DOM document.
		$html = new \DOMDocument();
		
		// Suppress errors for malformed HTML.
		libxml_use_internal_errors( true );
		
		// Load the content.
		$html->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );
		
		// Get all images.
		$images = $html->getElementsByTagName( 'img' );
		
		// Process each image.
		foreach ( $images as $img ) {
			// Skip if it already has loading attribute.
			if ( $img->hasAttribute( 'loading' ) ) {
				continue;
			}
			
			// Skip if it has skip-lazy class.
			if ( $img->hasAttribute( 'class' ) && strpos( $img->getAttribute( 'class' ), 'skip-lazy' ) !== false ) {
				continue;
			}
			
			// Add loading attribute.
			$img->setAttribute( 'loading', 'lazy' );
			
			// Add placeholder class.
			if ( $img->hasAttribute( 'class' ) ) {
				$img->setAttribute( 'class', $img->getAttribute( 'class' ) . ' sync-lazy' );
			} else {
				$img->setAttribute( 'class', 'sync-lazy' );
			}
			
			// Add data-lazy attribute.
			$img->setAttribute( 'data-lazy', '1' );
		}
		
		// Get the updated content.
		$content = $html->saveHTML();
		
		// Restore error handling.
		libxml_clear_errors();
		
		return $content;
	}

	/**
	 * Setup CDN rewriting.
	 */
	public function setup_cdn_rewriting() {
		// Skip if this is an admin or AJAX request.
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		
		// Skip if CDN URL is not set.
		$cdn_url = isset( $this->options['cdn_url'] ) ? $this->options['cdn_url'] : '';
		if ( empty( $cdn_url ) || '{{CDN_URL}}' === $cdn_url ) {
			return;
		}
		
		// Start output buffering.
		ob_start( array( $this, 'rewrite_urls_for_cdn' ) );
	}

	/**
	 * Rewrite URLs for CDN.
	 *
	 * @param string $content HTML content.
	 * @return string Modified content with CDN URLs.
	 */
	public function rewrite_urls_for_cdn( $content ) {
		// Skip if empty.
		if ( empty( $content ) ) {
			return $content;
		}
		
		// Get CDN URL.
		$cdn_url = isset( $this->options['cdn_url'] ) ? $this->options['cdn_url'] : '';
		if ( empty( $cdn_url ) || '{{CDN_URL}}' === $cdn_url ) {
			return $content;
		}
		
		// Get the site URL.
		$site_url = site_url();
		
		// Get included extensions.
		$extensions = isset( $this->options['cdn_includes'] ) ? $this->options['cdn_includes'] : array(
			'.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg',
			'.css', '.js', '.woff', '.woff2', '.ttf', '.eot',
		);
		
		// Build the pattern.
		$pattern = '/(https?:\/\/[^\/]+)?(\/[^"\']*?(' . implode( '|', array_map( 'preg_quote', $extensions ) ) . ')(\?[^"\']*)?)/i';
		
		// Replace URLs.
		$content = preg_replace_callback( $pattern, function( $matches ) use ( $site_url, $cdn_url ) {
			// Skip if it's already a CDN URL.
			if ( strpos( $matches[0], $cdn_url ) !== false ) {
				return $matches[0];
			}
			
			// If the URL is absolute without domain.
			if ( isset( $matches[2] ) && strpos( $matches[2], '/' ) === 0 ) {
				return $cdn_url . $matches[2];
			}
			
			// If the URL is relative.
			if ( isset( $matches[0] ) && strpos( $matches[0], 'http' ) !== 0 ) {
				return $cdn_url . '/' . ltrim( $matches[0], '/' );
			}
			
			// If the URL is absolute with domain.
			return str_replace( $site_url, $cdn_url, $matches[0] );
		}, $content );
		
		return $content;
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
}
