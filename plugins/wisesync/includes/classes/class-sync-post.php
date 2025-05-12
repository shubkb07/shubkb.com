<?php
/**
 * Wisesync Post Class
 *
 * This class handles post-related operations for the WiseSync plugin.
 * That includes registing, creating, updating, and deleting posts.
 * Rewriting the post content and metadata.
 * Rewrite Rules and Permalinks with Tags and Categories.
 *
 * @package   WiseSync
 * @since     1.0.0
 */

namespace Sync;

/**
 * Class Sync_Post
 *
 * Handles post-related operations for the WiseSync plugin.
 *
 * @package Sync
 */
class Sync_Post {
	/**
	 * Option name for post types.
	 *
	 * @var string
	 */
	const OPTION_POST_TYPES = 'sync-post-types';
	
	/**
	 * Option name for taxonomies.
	 *
	 * @var string
	 */
	const OPTION_TAXONOMIES = 'sync-taxonomies';
	
	/**
	 * Registered post types.
	 *
	 * @var array
	 */
	private $registered_post_types = array();

	/**
	 * Registered taxonomies.
	 *
	 * @var array
	 */
	private $registered_taxonomies = array();
	
	/**
	 * Post cache.
	 *
	 * @var array
	 */
	private $post_cache = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Initialize the class.
		add_action( 'init', array( $this, 'init_post_types' ), 0 );
		add_action( 'init', array( $this, 'init_taxonomies' ), 0 );
		add_action( 'init', array( $this, 'add_rewrite_rules' ), 10 );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		
		// Run setup on plugin activation.
		add_action( 'sync_activation', array( $this, 'setup_post_types_and_taxonomies' ) );
		
		// Update DB whenever post types or taxonomies are registered.
		add_action( 'registered_post_type', array( $this, 'update_registered_post_types' ) );
		add_action( 'registered_taxonomy', array( $this, 'update_registered_taxonomies' ) );
		
		// Load registered post types from options.
		$this->load_registered_post_types();
	}
	
	/**
	 * Initialize post types from saved options
	 */
	public function init_post_types() {
		$saved_post_types = get_option( self::OPTION_POST_TYPES, array() );

		if ( ! empty( $saved_post_types ) ) {
			if ( is_string( $saved_post_types ) ) {
				$saved_post_types = json_decode( $saved_post_types, true );
			}
			if ( ! empty( $saved_post_types ) && is_array( $saved_post_types ) ) {
				foreach ( $saved_post_types as $post_type => $args ) {
					if ( ! post_type_exists( $post_type ) ) {
						register_post_type( $post_type, $args );
					}
				}
			}
		}
	}
	
	/**
	 * Initialize taxonomies from saved options
	 */
	public function init_taxonomies() {
		$saved_taxonomies = get_option( self::OPTION_TAXONOMIES, array() );
		
		if ( ! empty( $saved_taxonomies ) ) {
			if ( is_string( $saved_taxonomies ) ) {
				$saved_taxonomies = json_decode( $saved_taxonomies, true );
			}
			
			if ( ! empty( $saved_taxonomies ) && is_array( $saved_taxonomies ) ) {
				foreach ( $saved_taxonomies as $taxonomy => $tax_data ) {
					if ( ! taxonomy_exists( $taxonomy ) && isset( $tax_data['object_type'] ) && isset( $tax_data['args'] ) ) {
						register_taxonomy( $taxonomy, $tax_data['object_type'], $tax_data['args'] );
					}
				}
			}
		}
	}
	
	/**
	 * Load registered post types from options
	 */
	private function load_registered_post_types() {
		$saved_post_types            = get_option( self::OPTION_POST_TYPES, wp_json_encode( array() ) );
		$this->registered_post_types = json_decode( $saved_post_types, true );
		
		if ( ! is_array( $this->registered_post_types ) ) {
			$this->registered_post_types = array();
		}
		
		$saved_taxonomies            = get_option( self::OPTION_TAXONOMIES, wp_json_encode( array() ) );
		$this->registered_taxonomies = json_decode( $saved_taxonomies, true );
		
		if ( ! is_array( $this->registered_taxonomies ) ) {
			$this->registered_taxonomies = array();
		}
	}
	
	/**
	 * Setup post types and taxonomies
	 * This function runs on plugin activation.
	 */
	public function setup_post_types_and_taxonomies() {
		// Flush rewrite rules.
		$this->init_post_types();
		$this->init_taxonomies();
		$this->add_rewrite_rules();
		flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
	}
	
	/**
	 * Register a custom post type.
	 *
	 * @param string $post_type Post type key.
	 * @param array  $args      Optional. Arguments for post type registration.
	 * @return \WP_Post_Type|\WP_Error Post type object on success, WP_Error on failure.
	 */
	public function register_post_type( $post_type, $args = array() ) {
		// Set up default permalink structure.
		if ( 'post' === $post_type ) {
			$args['rewrite'] = isset( $args['rewrite'] ) ? $args['rewrite'] : array( 'slug' => 'blog' );
		} elseif ( 'page' === $post_type ) {
			$args['rewrite'] = isset( $args['rewrite'] ) ? $args['rewrite'] : array( 'slug' => '' );
		} else {
			$args['rewrite'] = isset( $args['rewrite'] ) ? $args['rewrite'] : array( 'slug' => $post_type );
		}
		
		// Ensure query_var is set.
		if ( ! isset( $args['query_var'] ) || true === $args['query_var'] ) {
			$args['query_var'] = $post_type;
		}
		
		// Save to our internal array for later use.
		$this->registered_post_types[ $post_type ] = $args;
		
		// Register the post type.
		$result = register_post_type( $post_type, $args );
		
		// Update the stored post types.
		$this->update_registered_post_types( $post_type );
		
		return $result;
	}
	
	/**
	 * Register a custom category type (actually a taxonomy)
	 *
	 * @param string       $taxonomy    Taxonomy key.
	 * @param string|array $object_type Object type or array of object types.
	 * @param array        $args        Optional. Arguments for taxonomy registration.
	 * @return \WP_Taxonomy|\WP_Error Taxonomy object on success, WP_Error on failure.
	 */
	public function register_category_type( $taxonomy, $object_type, $args = array() ) {
		// Set up default hierarchical as true for category-like behavior.
		$args['hierarchical'] = isset( $args['hierarchical'] ) ? $args['hierarchical'] : true;
		
		$result = $this->register_taxonomy( $taxonomy, $object_type, $args );
		if ( $result instanceof \WP_Error || $result instanceof \WP_Taxonomy ) {
			return $result;
		}
		return new \WP_Error( 'invalid_return', 'register_taxonomy did not return WP_Error or WP_Taxonomy' );
	}
	
	/**
	 * Register a custom taxonomy.
	 *
	 * @param string       $taxonomy    Taxonomy key.
	 * @param string|array $object_type Object type or array of object types.
	 * @param array        $args        Optional. Arguments for taxonomy registration.
	 * @return \WP_Taxonomy|\WP_Error Taxonomy object on success, WP_Error on failure.
	 */
	public function register_taxonomy( $taxonomy, $object_type, $args = array() ) {
		// Set up default permalink structure.
		$args['rewrite'] = isset( $args['rewrite'] ) ? $args['rewrite'] : array( 'slug' => $taxonomy );
		
		// Ensure query_var is set.
		if ( ! isset( $args['query_var'] ) || true === $args['query_var'] ) {
			$args['query_var'] = $taxonomy;
		}
		
		// Save to our internal array for later use.
		$this->registered_taxonomies[ $taxonomy ] = array(
			'object_type' => $object_type,
			'args'        => $args,
		);
		
		// Register the taxonomy.
		$result = register_taxonomy( $taxonomy, $object_type, $args );
		
		// Update the stored taxonomies.
		$this->update_registered_taxonomies( $taxonomy );
		
		return $result;
	}
	
	/**
	 * Add rewrite rules for custom permalinks
	 */
	public function add_rewrite_rules() {
		global $wp_rewrite;
		
		// Post type rules.
		foreach ( $this->registered_post_types as $post_type => $args ) {
			if ( 'post' === $post_type ) {
				// Blog posts: /blog/{post-slug}.
				add_rewrite_rule(
					'blog/([^/]+)/?$',
					'index.php?post_type=post&name=$matches[1]',
					'top'
				);
				
				// Blog archive: /blog/.
				add_rewrite_rule(
					'blog/?$',
					'index.php?post_type=post',
					'top'
				);
			} else {
				// Custom post types: /{post-type}/{post-slug}.
				$slug = isset( $args['rewrite']['slug'] ) ? $args['rewrite']['slug'] : $post_type;
				
				add_rewrite_rule(
					$slug . '/([^/]+)/?$',
					'index.php?post_type=' . $post_type . '&name=$matches[1]',
					'top'
				);
				
				// Custom post type archive: /{post-type}/.
				add_rewrite_rule(
					$slug . '/?$',
					'index.php?post_type=' . $post_type,
					'top'
				);
			}
		}
		
		// Taxonomy rules.
		foreach ( $this->registered_taxonomies as $taxonomy => $tax_data ) {
			$args = $tax_data['args'];
			$slug = isset( $args['rewrite']['slug'] ) ? $args['rewrite']['slug'] : $taxonomy;
			
			// Taxonomy term: /{taxonomy}/{term}.
			add_rewrite_rule(
				$slug . '/([^/]+)/?$',
				'index.php?' . $taxonomy . '=$matches[1]',
				'top'
			);
			
			// Taxonomy archive: /{taxonomy}/.
			add_rewrite_rule(
				$slug . '/?$',
				'index.php?' . $taxonomy . '=',
				'top'
			);
		}

		// Update permalink structure.
		$wp_rewrite->flush_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rules_flush_rules
	}
	
	/**
	 * Add query vars for post types and taxonomies
	 *
	 * @param array $query_vars Query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_vars( $query_vars ) {
		// Add post type query vars.
		foreach ( $this->registered_post_types as $post_type => $args ) {
			if ( isset( $args['query_var'] ) && $args['query_var'] ) {
				$query_var    = is_string( $args['query_var'] ) ? $args['query_var'] : $post_type;
				$query_vars[] = $query_var;
			}
		}
		
		// Add taxonomy query vars.
		foreach ( $this->registered_taxonomies as $taxonomy => $tax_data ) {
			$args = $tax_data['args'];
			if ( isset( $args['query_var'] ) && $args['query_var'] ) {
				$query_var    = is_string( $args['query_var'] ) ? $args['query_var'] : $taxonomy;
				$query_vars[] = $query_var;
			}
		}
		
		return $query_vars;
	}
	
	/**
	 * Update registered post types in database
	 *
	 * @param string $post_type Post type being registered.
	 */
	public function update_registered_post_types( $post_type ) {
		// Check if this post type was registered through our custom function.
		if ( isset( $this->registered_post_types[ $post_type ] ) ) {
			// Add it to or update it in the saved post types.
			$saved_post_types = get_option( self::OPTION_POST_TYPES, '[]' );
			$saved_post_types = json_decode( $saved_post_types, true );
			
			if ( ! is_array( $saved_post_types ) ) {
				$saved_post_types = array();
			}
			
			$saved_post_types[ $post_type ] = $this->registered_post_types[ $post_type ];
			
			// Update the option.
			update_option( self::OPTION_POST_TYPES, wp_json_encode( $saved_post_types ), true );
		}
	}
	
	/**
	 * Update registered taxonomies in database
	 *
	 * @param string $taxonomy Taxonomy being registered.
	 */
	public function update_registered_taxonomies( $taxonomy ) {
		// Check if this taxonomy was registered through our custom function.
		if ( isset( $this->registered_taxonomies[ $taxonomy ] ) ) {
			// Add it to or update it in the saved taxonomies.
			$saved_taxonomies = get_option( self::OPTION_TAXONOMIES, '[]' );
			$saved_taxonomies = json_decode( $saved_taxonomies, true );
			
			if ( ! is_array( $saved_taxonomies ) ) {
				$saved_taxonomies = array();
			}
			
			$saved_taxonomies[ $taxonomy ] = $this->registered_taxonomies[ $taxonomy ];
			
			// Update the option.
			update_option( self::OPTION_TAXONOMIES, wp_json_encode( $saved_taxonomies ), true );
		}
	}
	
	/**
	 * Get registered post types
	 *
	 * @return array Array of registered post types.
	 */
	public function get_registered_post_types() {
		return $this->registered_post_types;
	}
	
	/**
	 * Get registered taxonomies
	 *
	 * @return array Array of registered taxonomies.
	 */
	public function get_registered_taxonomies() {
		return $this->registered_taxonomies;
	}
	
	/**
	 * Update permalinks in the database
	 * This is useful when changing permalink structures
	 */
	public function update_permalinks() {
		flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
	}
	
	/**
	 * Check if a post type was registered through our custom function
	 *
	 * @param string $post_type Post type to check.
	 * @return bool True if registered through our function, false otherwise.
	 */
	public function is_registered_post_type( $post_type ) {
		return isset( $this->registered_post_types[ $post_type ] );
	}
	
	/**
	 * Check if a taxonomy was registered through our custom function
	 *
	 * @param string $taxonomy Taxonomy to check.
	 * @return bool True if registered through our function, false otherwise.
	 */
	public function is_registered_taxonomy( $taxonomy ) {
		return isset( $this->registered_taxonomies[ $taxonomy ] );
	}
}
