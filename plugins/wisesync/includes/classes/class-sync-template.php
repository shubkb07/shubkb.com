<?php
/**
 * Sync Template Class
 *
 * Handles template registration, management, and execution for the WiseSync plugin.
 * Supports post types, taxonomies, meta boxes, user roles, theme templates, and theme parts.
 *
 * @package WiseSync
 * @since 1.0.0
 */

namespace Sync;

/**
 * Class Sync_Template
 *
 * Manages templates and template groups for various WordPress components.
 *
 * @since 1.0.0
 */
class Sync_Template {

	/**
	 * Post type for templates.
	 *
	 * @var string
	 */
	const POST_TYPE_TEMPLATE = 'sync_templates';

	/**
	 * Post type for template groups.
	 *
	 * @var string
	 */
	const POST_TYPE_TEMPLATE_GROUP = 'sync_template_groups';

	/**
	 * Option name for active templates.
	 *
	 * @var string
	 */
	const OPTION_ACTIVE_TEMPLATES = 'sync_active_templates';

	/**
	 * Supported template types.
	 *
	 * @var array
	 */
	private $supported_template_types = array(
		'post_type',
		'taxonomy',
		'meta_box',
		'user_role',
		'theme_template',
		'theme_part',
		'widget',
		'shortcode',
		'block_pattern',
		'block_style',
	);

	/**
	 * Active templates cache.
	 *
	 * @var array
	 */
	private $active_templates = array();

	/**
	 * Whether active templates have been loaded.
	 *
	 * @var bool
	 */
	private $active_templates_loaded = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register post types early.
		add_action( 'init', array( $this, 'register_post_types' ), 5 );
		
		// Load and execute active templates.
		add_action( 'init', array( $this, 'load_active_templates' ), 10 );
		add_action( 'init', array( $this, 'execute_active_templates' ), 15 );
		
		// Register theme templates and parts when theme is loaded.
		add_action( 'after_setup_theme', array( $this, 'execute_theme_templates' ), 20 );
		
		// Handle template deletion.
		add_action( 'before_delete_post', array( $this, 'handle_template_deletion' ) );
		
		// Add template meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_template_meta_boxes' ) );
		
		// Save template data.
		add_action( 'save_post', array( $this, 'save_template_data' ) );

		// Handle admin actions.
		add_action( 'admin_post_sync_activate_template', array( $this, 'handle_activate_template_action' ) );
		add_action( 'admin_post_sync_deactivate_template', array( $this, 'handle_deactivate_template_action' ) );
	}

	/**
	 * Register template post types.
	 */
	public function register_post_types() {
		// Register template post type.
		$template_args = array(
			'label'              => __( 'Templates', 'wisesync' ),
			'description'        => __( 'Sync Templates', 'wisesync' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => current_user_can( 'manage_options' ),
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => array( 'sync_template', 'sync_templates' ),
			'map_meta_cap'       => true,
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor' ),
			'show_in_rest'       => false,
		);

		register_post_type( self::POST_TYPE_TEMPLATE, $template_args );

		// Register template group post type.
		$group_args = array(
			'label'              => __( 'Template Groups', 'wisesync' ),
			'description'        => __( 'Sync Template Groups', 'wisesync' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => current_user_can( 'manage_options' ),
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => array( 'sync_template_group', 'sync_template_groups' ),
			'map_meta_cap'       => true,
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor' ),
			'show_in_rest'       => false,
		);

		register_post_type( self::POST_TYPE_TEMPLATE_GROUP, $group_args );

		// Add capabilities to administrator role.
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$capabilities = array(
				'read_sync_template',
				'read_private_sync_templates',
				'edit_sync_template',
				'edit_sync_templates',
				'edit_others_sync_templates',
				'edit_private_sync_templates',
				'edit_published_sync_templates',
				'publish_sync_templates',
				'delete_sync_template',
				'delete_sync_templates',
				'delete_others_sync_templates',
				'delete_private_sync_templates',
				'delete_published_sync_templates',
				'read_sync_template_group',
				'read_private_sync_template_groups',
				'edit_sync_template_group',
				'edit_sync_template_groups',
				'edit_others_sync_template_groups',
				'edit_private_sync_template_groups',
				'edit_published_sync_template_groups',
				'publish_sync_template_groups',
				'delete_sync_template_group',
				'delete_sync_template_groups',
				'delete_others_sync_template_groups',
				'delete_private_sync_template_groups',
				'delete_published_sync_template_groups',
			);

			foreach ( $capabilities as $capability ) {
				$admin_role->add_cap( $capability );
			}
		}
	}

	/**
	 * Register a single template.
	 *
	 * @param string $template_type Template type.
	 * @param array  $template_data Template configuration data.
	 * @param bool   $active        Whether to activate template immediately.
	 * @return int|false Template post ID on success, false on failure.
	 */
	public function register_template( $template_type, $template_data, $active = false ) {
		// Validate template type.
		if ( ! $this->is_valid_template_type( $template_type ) ) {
			return false;
		}

		// Sanitize template data.
		$template_data = $this->sanitize_template_data( $template_data );
		if ( empty( $template_data ) ) {
			return false;
		}

		// Create template title if not provided.
		$template_title = isset( $template_data['name'] ) ? $template_data['name'] : '';
		if ( empty( $template_title ) ) {
			$template_title = ucfirst( str_replace( '_', ' ', $template_type ) ) . ' Template';
		}

		// Create template post.
		$post_data = array(
			'post_title'   => sanitize_text_field( $template_title ),
			'post_content' => wp_json_encode( $template_data ),
			'post_status'  => 'publish',
			'post_type'    => self::POST_TYPE_TEMPLATE,
		);

		$template_id = wp_insert_post( $post_data );

		if ( is_wp_error( $template_id ) || ! $template_id ) {
			return false;
		}

		// Save template type and data as meta.
		update_post_meta( $template_id, '_sync_template_type', sanitize_key( $template_type ) );
		update_post_meta( $template_id, '_sync_template_data', wp_slash( wp_json_encode( $template_data ) ) );

		// Activate template if requested.
		if ( $active ) {
			$this->activate_template( $template_id );
		}

		/**
		 * Fires after a template is registered.
		 *
		 * @param int    $template_id   Template post ID.
		 * @param string $template_type Template type.
		 * @param array  $template_data Template data.
		 * @param bool   $active        Whether template was activated.
		 */
		do_action( 'sync_template_registered', $template_id, $template_type, $template_data, $active );

		return $template_id;
	}

	/**
	 * Register a template group.
	 *
	 * @param string       $group_name Group name.
	 * @param array|string $templates  Array of templates or JSON string.
	 * @param bool         $active     Whether to activate group immediately.
	 * @return int|false Template group post ID on success, false on failure.
	 */
	public function register_template_group( $group_name, $templates, $active = false ) {
		// Sanitize group name.
		$group_name = sanitize_text_field( $group_name );
		if ( empty( $group_name ) ) {
			return false;
		}

		// Parse templates if JSON string.
		if ( is_string( $templates ) ) {
			$templates = json_decode( $templates, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return false;
			}
		}

		// Validate templates array.
		if ( ! is_array( $templates ) || empty( $templates ) ) {
			return false;
		}

		// Register individual templates and collect their IDs.
		$template_ids = array();
		foreach ( $templates as $template ) {
			if ( ! isset( $template['type'] ) || ! isset( $template['data'] ) ) {
				continue;
			}

			$template_id = $this->register_template( $template['type'], $template['data'], false );
			if ( $template_id ) {
				$template_ids[] = $template_id;
			}
		}

		if ( empty( $template_ids ) ) {
			return false;
		}

		// Create template group post.
		$post_data = array(
			'post_title'   => $group_name,
			'post_content' => wp_json_encode( $template_ids ),
			'post_status'  => 'publish',
			'post_type'    => self::POST_TYPE_TEMPLATE_GROUP,
		);

		$group_id = wp_insert_post( $post_data );

		if ( is_wp_error( $group_id ) || ! $group_id ) {
			// Clean up created templates.
			foreach ( $template_ids as $template_id ) {
				wp_delete_post( $template_id, true );
			}
			return false;
		}

		// Save template IDs as meta.
		update_post_meta( $group_id, '_sync_template_ids', $template_ids );

		// Activate group if requested.
		if ( $active ) {
			$this->activate_template( $group_id );
		}

		/**
		 * Fires after a template group is registered.
		 *
		 * @param int   $group_id     Template group post ID.
		 * @param string $group_name  Group name.
		 * @param array $template_ids Array of template post IDs.
		 * @param bool  $active       Whether group was activated.
		 */
		do_action( 'sync_template_group_registered', $group_id, $group_name, $template_ids, $active );

		return $group_id;
	}

	/**
	 * Activate a template or template group.
	 *
	 * @param int $template_id Template or template group post ID.
	 * @return bool True on success, false on failure.
	 */
	public function activate_template( $template_id ) {
		// Validate template ID.
		$template_id = absint( $template_id );
		if ( ! $template_id ) {
			return false;
		}

		// Check if template exists.
		$post = get_post( $template_id );
		if ( ! $post || ! in_array( $post->post_type, array( self::POST_TYPE_TEMPLATE, self::POST_TYPE_TEMPLATE_GROUP ), true ) ) {
			return false;
		}

		// Load active templates.
		$this->load_active_templates();

		// Check if already active.
		if ( in_array( $template_id, $this->active_templates, true ) ) {
			return true;
		}

		// Add to active templates.
		$this->active_templates[] = $template_id;

		// Update option.
		$this->save_active_templates();

		/**
		 * Fires after a template is activated.
		 *
		 * @param int $template_id Template post ID.
		 */
		do_action( 'sync_template_activated', $template_id );

		return true;
	}

	/**
	 * Deactivate a template or template group.
	 *
	 * @param int $template_id Template or template group post ID.
	 * @return bool True on success, false on failure.
	 */
	public function deactivate_template( $template_id ) {
		// Validate template ID.
		$template_id = absint( $template_id );
		if ( ! $template_id ) {
			return false;
		}

		// Load active templates.
		$this->load_active_templates();

		// Remove from active templates.
		$key = array_search( $template_id, $this->active_templates, true );
		if ( false !== $key ) {
			unset( $this->active_templates[ $key ] );
			$this->active_templates = array_values( $this->active_templates );

			// Update option.
			$this->save_active_templates();

			/**
			 * Fires after a template is deactivated.
			 *
			 * @param int $template_id Template post ID.
			 */
			do_action( 'sync_template_deactivated', $template_id );

			return true;
		}

		return false;
	}

	/**
	 * Delete a template or template group.
	 *
	 * @param int  $template_id Template or template group post ID.
	 * @param bool $force       Whether to force deletion of active templates.
	 * @return bool True on success, false on failure.
	 */
	public function delete_template( $template_id, $force = false ) {
		// Validate template ID.
		$template_id = absint( $template_id );
		if ( ! $template_id ) {
			return false;
		}

		// Check if template exists.
		$post = get_post( $template_id );
		if ( ! $post || ! in_array( $post->post_type, array( self::POST_TYPE_TEMPLATE, self::POST_TYPE_TEMPLATE_GROUP ), true ) ) {
			return false;
		}

		// Check if template is active and force is not set.
		if ( ! $force && $this->is_template_active( $template_id ) ) {
			return false;
		}

		// Deactivate template first.
		$this->deactivate_template( $template_id );

		// If it's a template group, delete associated templates.
		if ( self::POST_TYPE_TEMPLATE_GROUP === $post->post_type ) {
			$template_ids = get_post_meta( $template_id, '_sync_template_ids', true );
			if ( is_array( $template_ids ) ) {
				foreach ( $template_ids as $child_template_id ) {
					$this->delete_template( $child_template_id, true );
				}
			}
		}

		// Delete the template.
		$deleted = wp_delete_post( $template_id, true );

		/**
		 * Fires after a template is deleted.
		 *
		 * @param int  $template_id Template post ID.
		 * @param bool $deleted     Whether deletion was successful.
		 */
		do_action( 'sync_template_deleted', $template_id, $deleted );

		return (bool) $deleted;
	}

	/**
	 * Get all templates of a specific type.
	 *
	 * @param string $template_type Template type.
	 * @param bool   $active_only   Whether to return only active templates.
	 * @return array Array of template posts.
	 */
	public function get_templates( $template_type = '', $active_only = false ) {
		$args = array(
			'post_type'      => self::POST_TYPE_TEMPLATE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		);

		// Filter by template type.
		if ( ! empty( $template_type ) && $this->is_valid_template_type( $template_type ) ) {
			$args['meta_query'][] = array(
				'key'   => '_sync_template_type',
				'value' => sanitize_key( $template_type ),
			);
		}

		// Filter by active status.
		if ( $active_only ) {
			$this->load_active_templates();
			if ( ! empty( $this->active_templates ) ) {
				$args['post__in'] = $this->active_templates;
			} else {
				return array();
			}
		}

		return get_posts( $args );
	}

	/**
	 * Get all template groups.
	 *
	 * @param bool $active_only Whether to return only active groups.
	 * @return array Array of template group posts.
	 */
	public function get_template_groups( $active_only = false ) {
		$args = array(
			'post_type'      => self::POST_TYPE_TEMPLATE_GROUP,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		// Filter by active status.
		if ( $active_only ) {
			$this->load_active_templates();
			if ( ! empty( $this->active_templates ) ) {
				$args['post__in'] = $this->active_templates;
			} else {
				return array();
			}
		}

		return get_posts( $args );
	}

	/**
	 * Check if a template is active.
	 *
	 * @param int $template_id Template post ID.
	 * @return bool True if active, false otherwise.
	 */
	public function is_template_active( $template_id ) {
		$this->load_active_templates();
		return in_array( absint( $template_id ), $this->active_templates, true );
	}

	/**
	 * Get template configuration data.
	 *
	 * @param int $template_id Template post ID.
	 * @return array|false Template data on success, false on failure.
	 */
	public function get_template_data( $template_id ) {
		// Validate template ID.
		$template_id = absint( $template_id );
		if ( ! $template_id ) {
			return false;
		}

		// Check if template exists.
		$post = get_post( $template_id );
		if ( ! $post || self::POST_TYPE_TEMPLATE !== $post->post_type ) {
			return false;
		}

		// Get template data from meta.
		$template_data = get_post_meta( $template_id, '_sync_template_data', true );
		if ( empty( $template_data ) ) {
			// Fallback to post content.
			$template_data = json_decode( $post->post_content, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return false;
			}
		} else {
			$template_data = json_decode( $template_data, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return false;
			}
		}

		/**
		 * Filters template data before returning.
		 *
		 * @param array $template_data Template data.
		 * @param int   $template_id   Template post ID.
		 */
		return apply_filters( 'sync_get_template_data', $template_data, $template_id );
	}

	/**
	 * Update template configuration data.
	 *
	 * @param int   $template_id   Template post ID.
	 * @param array $template_data New template data.
	 * @return bool True on success, false on failure.
	 */
	public function update_template_data( $template_id, $template_data ) {
		// Validate template ID.
		$template_id = absint( $template_id );
		if ( ! $template_id ) {
			return false;
		}

		// Check if template exists.
		$post = get_post( $template_id );
		if ( ! $post || self::POST_TYPE_TEMPLATE !== $post->post_type ) {
			return false;
		}

		// Sanitize template data.
		$template_data = $this->sanitize_template_data( $template_data );
		if ( empty( $template_data ) ) {
			return false;
		}

		// Update template data.
		$updated = update_post_meta( $template_id, '_sync_template_data', wp_slash( wp_json_encode( $template_data ) ) );

		// Also update post content.
		wp_update_post(
			array(
				'ID'           => $template_id,
				'post_content' => wp_json_encode( $template_data ),
			)
		);

		/**
		 * Fires after template data is updated.
		 *
		 * @param int   $template_id   Template post ID.
		 * @param array $template_data Updated template data.
		 */
		do_action( 'sync_template_data_updated', $template_id, $template_data );

		return (bool) $updated;
	}

	/**
	 * Load active templates from database.
	 */
	public function load_active_templates() {
		if ( ! $this->active_templates_loaded ) {
			$active_templates = get_option( self::OPTION_ACTIVE_TEMPLATES, array() );
			
			if ( is_string( $active_templates ) ) {
				$active_templates = json_decode( $active_templates, true );
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					$active_templates = array();
				}
			}

			$this->active_templates        = is_array( $active_templates ) ? array_map( 'absint', $active_templates ) : array();
			$this->active_templates_loaded = true;
		}
	}

	/**
	 * Save active templates to database.
	 */
	private function save_active_templates() {
		update_option( self::OPTION_ACTIVE_TEMPLATES, wp_json_encode( $this->active_templates ) );
	}

	/**
	 * Execute active templates.
	 */
	public function execute_active_templates() {
		$this->load_active_templates();

		if ( empty( $this->active_templates ) ) {
			return;
		}

		foreach ( $this->active_templates as $template_id ) {
			$post = get_post( $template_id );
			if ( ! $post ) {
				continue;
			}

			if ( self::POST_TYPE_TEMPLATE === $post->post_type ) {
				$this->execute_template( $template_id );
			} elseif ( self::POST_TYPE_TEMPLATE_GROUP === $post->post_type ) {
				$this->execute_template_group( $template_id );
			}
		}

		/**
		 * Fires after all active templates are executed.
		 */
		do_action( 'sync_active_templates_executed' );
	}

	/**
	 * Execute theme templates when theme is loaded.
	 */
	public function execute_theme_templates() {
		$this->load_active_templates();

		if ( empty( $this->active_templates ) ) {
			return;
		}

		foreach ( $this->active_templates as $template_id ) {
			$post = get_post( $template_id );
			if ( ! $post || self::POST_TYPE_TEMPLATE !== $post->post_type ) {
				continue;
			}

			$template_type = get_post_meta( $template_id, '_sync_template_type', true );
			if ( in_array( $template_type, array( 'theme_template', 'theme_part' ), true ) ) {
				$this->execute_template( $template_id );
			}
		}
	}

	/**
	 * Execute a single template.
	 *
	 * @param int $template_id Template post ID.
	 */
	private function execute_template( $template_id ) {
		$template_type = get_post_meta( $template_id, '_sync_template_type', true );
		$template_data = $this->get_template_data( $template_id );

		if ( ! $template_data || ! $this->is_valid_template_type( $template_type ) ) {
			return;
		}

		/**
		 * Filters whether to execute a template.
		 *
		 * @param bool  $execute       Whether to execute the template.
		 * @param int   $template_id   Template post ID.
		 * @param string $template_type Template type.
		 * @param array $template_data Template data.
		 */
		$execute = apply_filters( 'sync_execute_template', true, $template_id, $template_type, $template_data );

		if ( ! $execute ) {
			return;
		}

		switch ( $template_type ) {
			case 'post_type':
				$this->execute_post_type_template( $template_data );
				break;

			case 'taxonomy':
				$this->execute_taxonomy_template( $template_data );
				break;

			case 'meta_box':
				$this->execute_meta_box_template( $template_data );
				break;

			case 'user_role':
				$this->execute_user_role_template( $template_data );
				break;

			case 'theme_template':
				$this->execute_theme_template_template( $template_data );
				break;

			case 'theme_part':
				$this->execute_theme_part_template( $template_data );
				break;

			case 'widget':
				$this->execute_widget_template( $template_data );
				break;

			case 'shortcode':
				$this->execute_shortcode_template( $template_data );
				break;

			case 'block_pattern':
				$this->execute_block_pattern_template( $template_data );
				break;

			case 'block_style':
				$this->execute_block_style_template( $template_data );
				break;
		}

		/**
		 * Fires after a template is executed.
		 *
		 * @param int    $template_id   Template post ID.
		 * @param string $template_type Template type.
		 * @param array  $template_data Template data.
		 */
		do_action( 'sync_template_executed', $template_id, $template_type, $template_data );
	}

	/**
	 * Execute a template group.
	 *
	 * @param int $group_id Template group post ID.
	 */
	private function execute_template_group( $group_id ) {
		$template_ids = get_post_meta( $group_id, '_sync_template_ids', true );

		if ( ! is_array( $template_ids ) || empty( $template_ids ) ) {
			return;
		}

		foreach ( $template_ids as $template_id ) {
			$this->execute_template( $template_id );
		}

		/**
		 * Fires after a template group is executed.
		 *
		 * @param int   $group_id     Template group post ID.
		 * @param array $template_ids Array of template post IDs.
		 */
		do_action( 'sync_template_group_executed', $group_id, $template_ids );
	}

	/**
	 * Execute post type template.
	 *
	 * @param array $template_data Template data.
	 */
	private function execute_post_type_template( $template_data ) {
		if ( ! isset( $template_data['post_type'] ) || ! isset( $template_data['args'] ) ) {
			return;
		}

		global $sync_post;
		
		$post_type = sanitize_key( $template_data['post_type'] );
		$args      = $this->sanitize_post_type_args( $template_data['args'] );

		if ( ! post_type_exists( $post_type ) ) {
			$sync_post->register_post_type( $post_type, $args );
		}
	}

	/**
	 * Execute taxonomy template.
	 *
	 * @param array $template_data Template data.
	 */
	private function execute_taxonomy_template( $template_data ) {
		if ( ! isset( $template_data['taxonomy'] ) || ! isset( $template_data['object_type'] ) || ! isset( $template_data['args'] ) ) {
			return;
		}

		global $sync_post;

		$taxonomy    = sanitize_key( $template_data['taxonomy'] );
		$object_type = $template_data['object_type'];
		$args        = $this->sanitize_taxonomy_args( $template_data['args'] );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			$sync_post->register_taxonomy( $taxonomy, $object_type, $args );
		}
	}

	/**
	 * Execute meta box template.
	 *
	 * @param array $template_data Template data.
	 */
	private function execute_meta_box_template( $template_data ) {
		if ( ! isset( $template_data['id'] ) || ! isset( $template_data['title'] ) || ! isset( $template_data['callback'] ) ) {
			return;
		}

		$meta_box_id    = sanitize_key( $template_data['id'] );
		$meta_box_title = sanitize_text_field( $template_data['title'] );
		$callback       = $template_data['callback'];
		$screen         = isset( $template_data['screen'] ) ? $template_data['screen'] : null;
		$context        = isset( $template_data['context'] ) ? $template_data['context'] : 'advanced';
		$priority       = isset( $template_data['priority'] ) ? $template_data['priority'] : 'default';
		$callback_args  = isset( $template_data['callback_args'] ) ? $template_data['callback_args'] : null;

		add_action(
			'add_meta_boxes',
			function () use ( $meta_box_id, $meta_box_title, $callback, $screen, $context, $priority, $callback_args ) {
				add_meta_box( $meta_box_id, $meta_box_title, $callback, $screen, $context, $priority, $callback_args );
			}
		);
	}

	/**
	 * Execute user role template.
	 *
	 * @param array $template_data Template data.
	 */
	private function execute_user_role_template( $template_data ) {
		if ( ! isset( $template_data['role'] ) || ! isset( $template_data['display_name'] ) ) {
				return;
		}

		$role         = sanitize_key( $template_data['role'] );
		$display_name = sanitize_text_field( $template_data['display_name'] );
		$capabilities = isset( $template_data['capabilities'] ) ? $template_data['capabilities'] : array();

		if ( ! get_role( $role ) ) {
			sync_add_role( $role, $display_name, $capabilities );
		}
	}

	/**
	 * Execute theme template template.
	 *
	 * @param array $template_data Template data.
	 */
	private function execute_theme_template_template( $template_data ) {
		if ( ! current_theme_supports( 'block-templates' ) ) {
				return;
		}

		if ( ! isset( $template_data['slug'] ) || ! isset( $template_data['content'] ) ) {
			return;
		}

		$slug        = sanitize_key( $template_data['slug'] );
		$title       = isset( $template_data['title'] ) ? sanitize_text_field( $template_data['title'] ) : $slug;
		$description = isset( $template_data['description'] ) ? sanitize_text_field( $template_data['description'] ) : '';
		$content     = $template_data['content'];
		$post_types  = isset( $template_data['post_types'] ) ? $template_data['post_types'] : array();

		// Register the block template.
		add_filter(
			'get_block_templates',
			function ( $query_result, $query ) use ( $slug, $title, $description, $content, $post_types ) {
				if ( isset( $query['slug__in'] ) && ! in_array( $slug, $query['slug__in'], true ) ) {
					return $query_result;
				}

				$template              = new \WP_Block_Template();
				$template->id          = get_stylesheet() . '//' . $slug;
				$template->theme       = get_stylesheet();
				$template->slug        = $slug;
				$template->status      = 'publish';
				$template->source      = 'plugin';
				$template->title       = $title;
				$template->description = $description;
				$template->type        = 'wp_template';
				$template->post_types  = $post_types;
				$template->content     = $content;

				$query_result[] = $template;

				return $query_result;
			},
			10,
			2
		);
	}

	/**
	 * Execute theme part template.
	 *
	 * @param array $template_data Template data.
	 */
	private function execute_theme_part_template( $template_data ) {
		if ( ! current_theme_supports( 'block-template-parts' ) ) {
				return;
		}

		if ( ! isset( $template_data['slug'] ) || ! isset( $template_data['content'] ) ) {
			return;
		}

		$slug        = sanitize_key( $template_data['slug'] );
		$title       = isset( $template_data['title'] ) ? sanitize_text_field( $template_data['title'] ) : $slug;
		$description = isset( $template_data['description'] ) ? sanitize_text_field( $template_data['description'] ) : '';
		$content     = $template_data['content'];
		$area        = isset( $template_data['area'] ) ? sanitize_key( $template_data['area'] ) : 'uncategorized';

		// Register the block template part.
		add_filter(
			'get_block_templates',
			function ( $query_result, $query ) use ( $slug, $title, $description, $content, $area ) {
				if ( isset( $query['slug__in'] ) && ! in_array( $slug, $query['slug__in'], true ) ) {
					return $query_result;
				}

				$template              = new \WP_Block_Template();
				$template->id          = get_stylesheet() . '//' . $slug;
				$template->theme       = get_stylesheet();
				$template->slug        = $slug;
				$template->status      = 'publish';
				$template->source      = 'plugin';
				$template->title       = $title;
				$template->description = $description;
				$template->type        = 'wp_template_part';
				$template->area        = $area;
				$template->content     = $content;

				$query_result[] = $template;

				return $query_result;
			},
			10,
			2
		);
	}

	/**
	 * Execute widget template.
	 *
	 * @param array $template_data Template data.
	 */
	private function execute_widget_template( $template_data ) {
		if ( ! isset( $template_data['id_base'] ) || ! isset( $template_data['name'] ) ) {
				return;
		}

		$widget_class = isset( $template_data['widget_class'] ) ? $template_data['widget_class'] : null;
	
		if ( $widget_class && class_exists( $widget_class ) ) {
			add_action(
				'widgets_init',
				function () use ( $widget_class ) {
					register_widget( $widget_class );
				}
			);
		}
	}

	/**
	 * Execute shortcode template.
	 *
	 * @param array $template_data Template data.
	 */
	private function execute_shortcode_template( $template_data ) {
		if ( ! isset( $template_data['tag'] ) || ! isset( $template_data['callback'] ) ) {
				return;
		}

		$tag      = sanitize_key( $template_data['tag'] );
		$callback = $template_data['callback'];

		if ( ! shortcode_exists( $tag ) && is_callable( $callback ) ) {
			add_shortcode( $tag, $callback );
		}
	}

	/**
	 * Execute block pattern template.
	 *
	 * @param array $template_data Template data.
	 */
	private function execute_block_pattern_template( $template_data ) {
		if ( ! function_exists( 'register_block_pattern' ) || ! isset( $template_data['name'] ) || ! isset( $template_data['content'] ) ) {
				return;
		}

		$name       = sanitize_key( $template_data['name'] );
		$properties = array(
			'title'   => isset( $template_data['title'] ) ? sanitize_text_field( $template_data['title'] ) : $name,
			'content' => $template_data['content'],
		);

		if ( isset( $template_data['description'] ) ) {
			$properties['description'] = sanitize_text_field( $template_data['description'] );
		}

		if ( isset( $template_data['categories'] ) && is_array( $template_data['categories'] ) ) {
			$properties['categories'] = array_map( 'sanitize_key', $template_data['categories'] );
		}

		if ( isset( $template_data['keywords'] ) && is_array( $template_data['keywords'] ) ) {
			$properties['keywords'] = array_map( 'sanitize_text_field', $template_data['keywords'] );
		}

		register_block_pattern( $name, $properties );
	}

	/**
	 * Execute block style template.
	 *
	 * @param array $template_data Template data.
	 */
	private function execute_block_style_template( $template_data ) {
		if ( ! function_exists( 'register_block_style' ) || ! isset( $template_data['block_name'] ) || ! isset( $template_data['style_properties'] ) ) {
				return;
		}

		$block_name       = sanitize_text_field( $template_data['block_name'] );
		$style_properties = $template_data['style_properties'];

		if ( isset( $style_properties['name'] ) ) {
			$style_properties['name'] = sanitize_key( $style_properties['name'] );
		}

		if ( isset( $style_properties['label'] ) ) {
			$style_properties['label'] = sanitize_text_field( $style_properties['label'] );
		}

		register_block_style( $block_name, $style_properties );
	}

	/**
	 * Handle template deletion.
	 *
	 * @param int $post_id Post ID being deleted.
	 */
	public function handle_template_deletion( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, array( self::POST_TYPE_TEMPLATE, self::POST_TYPE_TEMPLATE_GROUP ), true ) ) {
				return;
		}

		// Deactivate template before deletion.
		$this->deactivate_template( $post_id );
	}

	/**
	 * Add template meta boxes.
	 */
	public function add_template_meta_boxes() {
		// Template type meta box.
		add_meta_box(
			'sync_template_type',
			__( 'Template Type', 'wisesync' ),
			array( $this, 'template_type_meta_box' ),
			self::POST_TYPE_TEMPLATE,
			'side',
			'high'
		);

		// Template status meta box.
		add_meta_box(
			'sync_template_status',
			__( 'Template Status', 'wisesync' ),
			array( $this, 'template_status_meta_box' ),
			array( self::POST_TYPE_TEMPLATE, self::POST_TYPE_TEMPLATE_GROUP ),
			'side',
			'high'
		);

		// Template data meta box.
		add_meta_box(
			'sync_template_data',
			__( 'Template Configuration', 'wisesync' ),
			array( $this, 'template_data_meta_box' ),
			self::POST_TYPE_TEMPLATE,
			'normal',
			'high'
		);

		// Template group templates meta box.
		add_meta_box(
			'sync_template_group_templates',
			__( 'Group Templates', 'wisesync' ),
			array( $this, 'template_group_templates_meta_box' ),
			self::POST_TYPE_TEMPLATE_GROUP,
			'normal',
			'high'
		);
	}

	/**
	 * Template type meta box callback.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function template_type_meta_box( $post ) {
		$template_type = get_post_meta( $post->ID, '_sync_template_type', true );
		wp_nonce_field( 'sync_template_meta', 'sync_template_meta_nonce' );
		?>
		<p>
			<label for="sync_template_type"><?php esc_html_e( 'Template Type:', 'wisesync' ); ?></label>
			<select name="sync_template_type" id="sync_template_type" style="width: 100%;">
				<option value=""><?php esc_html_e( 'Select Type', 'wisesync' ); ?></option>
			<?php foreach ( $this->supported_template_types as $type ) : ?>
					<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $template_type, $type ); ?>>
					<?php echo esc_html( ucfirst( str_replace( '_', ' ', $type ) ) ); ?>
					</option>
			<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Template status meta box callback.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function template_status_meta_box( $post ) {
		$is_active = $this->is_template_active( $post->ID );
		?>
		<p>
			<strong><?php esc_html_e( 'Status:', 'wisesync' ); ?></strong>
		<?php if ( $is_active ) : ?>
				<span style="color: #46b450;"><?php esc_html_e( 'Active', 'wisesync' ); ?></span>
		<?php else : ?>
				<span style="color: #dc3232;"><?php esc_html_e( 'Inactive', 'wisesync' ); ?></span>
		<?php endif; ?>
		</p>
		<p>
		<?php if ( $is_active ) : ?>
				<button type="button" class="button" onclick="syncDeactivateTemplate(<?php echo esc_js( $post->ID ); ?>)">
				<?php esc_html_e( 'Deactivate', 'wisesync' ); ?>
				</button>
		<?php else : ?>
				<button type="button" class="button button-primary" onclick="syncActivateTemplate(<?php echo esc_js( $post->ID ); ?>)">
				<?php esc_html_e( 'Activate', 'wisesync' ); ?>
				</button>
		<?php endif; ?>
		</p>
		<script>
		function syncActivateTemplate(templateId) {
			if (confirm('<?php esc_js( _e( 'Are you sure you want to activate this template?', 'wisesync' ) ); ?>')) {
				window.location.href = '<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>?action=sync_activate_template&template_id=' + templateId + '&_wpnonce=<?php echo esc_attr( wp_create_nonce( 'sync_activate_template' ) ); ?>';
			}
		}
		function syncDeactivateTemplate(templateId) {
			if (confirm('<?php esc_js( _e( 'Are you sure you want to deactivate this template?', 'wisesync' ) ); ?>')) {
				window.location.href = '<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>?action=sync_deactivate_template&template_id=' + templateId + '&_wpnonce=<?php echo esc_attr( wp_create_nonce( 'sync_deactivate_template' ) ); ?>';
			}
		}
		</script>
		<?php
	}

	/**
	 * Template data meta box callback.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function template_data_meta_box( $post ) {
		$template_data      = $this->get_template_data( $post->ID );
		$template_data_json = $template_data ? wp_json_encode( $template_data, JSON_PRETTY_PRINT ) : '';
		?>
		<p>
			<label for="sync_template_data"><?php esc_html_e( 'Template Configuration (JSON):', 'wisesync' ); ?></label>
			<textarea name="sync_template_data" id="sync_template_data" rows="20" style="width: 100%; font-family: monospace;"><?php echo esc_textarea( $template_data_json ); ?></textarea>
		</p>
		<p class="description">
		<?php esc_html_e( 'Enter the template configuration in JSON format. This will define how the template behaves when activated.', 'wisesync' ); ?>
		</p>
		<?php
	}

	/**
	 * Template group templates meta box callback.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function template_group_templates_meta_box( $post ) {
		$template_ids = get_post_meta( $post->ID, '_sync_template_ids', true );
		if ( ! is_array( $template_ids ) ) {
				$template_ids = array();
		}
		?>
		<div id="sync-template-group-templates">
		<?php if ( ! empty( $template_ids ) ) : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Template', 'wisesync' ); ?></th>
							<th><?php esc_html_e( 'Type', 'wisesync' ); ?></th>
							<th><?php esc_html_e( 'Status', 'wisesync' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $template_ids as $template_id ) : ?>
						<?php
						$template = get_post( $template_id );
						if ( ! $template ) {
							continue;
						}
						$template_type = get_post_meta( $template_id, '_sync_template_type', true );
						$is_active     = $this->is_template_active( $template_id );
						?>
							<tr>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $template_id ) ); ?>">
									<?php echo esc_html( $template->post_title ); ?>
									</a>
								</td>
								<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $template_type ) ) ); ?></td>
								<td>
								<?php if ( $is_active ) : ?>
										<span style="color: #46b450;"><?php esc_html_e( 'Active', 'wisesync' ); ?></span>
								<?php else : ?>
										<span style="color: #dc3232;"><?php esc_html_e( 'Inactive', 'wisesync' ); ?></span>
								<?php endif; ?>
								</td>
							</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
		<?php else : ?>
				<p><?php esc_html_e( 'No templates in this group.', 'wisesync' ); ?></p>
		<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Save template data when post is saved.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_template_data( $post_id ) {
		// Check if this is an autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check nonce.
		if ( ! isset( $_POST['sync_template_meta_nonce'] ) || ! wp_verify_nonce( $_POST['sync_template_meta_nonce'], 'sync_template_meta' ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || self::POST_TYPE_TEMPLATE !== $post->post_type ) {
			return;
		}

		// Save template type.
		if ( isset( $_POST['sync_template_type'] ) ) {
			$template_type = sanitize_key( $_POST['sync_template_type'] );
			if ( $this->is_valid_template_type( $template_type ) ) {
				update_post_meta( $post_id, '_sync_template_type', $template_type );
			}
		}

		// Save template data.
		if ( isset( $_POST['sync_template_data'] ) ) {
			$template_data_json = wp_unslash( $_POST['sync_template_data'] );
			$template_data      = json_decode( $template_data_json, true );

			if ( json_last_error() === JSON_ERROR_NONE && is_array( $template_data ) ) {
				$template_data = $this->sanitize_template_data( $template_data );
				update_post_meta( $post_id, '_sync_template_data', wp_slash( wp_json_encode( $template_data ) ) );

				// Also update post content.
				wp_update_post(
					array(
						'ID'           => $post_id,
						'post_content' => wp_json_encode( $template_data ),
					)
				);
			}
		}
	}

	/**
	 * Handle activate template admin action.
	 */
	public function handle_activate_template_action() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'sync_activate_template' ) ) {
				wp_die( esc_html__( 'Invalid nonce.', 'wisesync' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wisesync' ) );
		}

		$template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;
		if ( ! $template_id ) {
			wp_die( esc_html__( 'Invalid template ID.', 'wisesync' ) );
		}

		$activated = $this->activate_template( $template_id );

		$redirect_url = wp_get_referer();
		if ( ! $redirect_url ) {
			$redirect_url = admin_url( 'edit.php?post_type=' . self::POST_TYPE_TEMPLATE );
		}

		if ( $activated ) {
			$redirect_url = add_query_arg( 'sync_message', 'template_activated', $redirect_url );
		} else {
			$redirect_url = add_query_arg( 'sync_message', 'template_activation_failed', $redirect_url );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle deactivate template admin action.
	 */
	public function handle_deactivate_template_action() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'sync_deactivate_template' ) ) {
				wp_die( esc_html__( 'Invalid nonce.', 'wisesync' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wisesync' ) );
		}

		$template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;
		if ( ! $template_id ) {
			wp_die( esc_html__( 'Invalid template ID.', 'wisesync' ) );
		}

		$deactivated = $this->deactivate_template( $template_id );

		$redirect_url = wp_get_referer();
		if ( ! $redirect_url ) {
			$redirect_url = admin_url( 'edit.php?post_type=' . self::POST_TYPE_TEMPLATE );
		}

		if ( $deactivated ) {
			$redirect_url = add_query_arg( 'sync_message', 'template_deactivated', $redirect_url );
		} else {
			$redirect_url = add_query_arg( 'sync_message', 'template_deactivation_failed', $redirect_url );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Check if template type is valid.
	 *
	 * @param string $template_type Template type to check.
	 * @return bool True if valid, false otherwise.
	 */
	private function is_valid_template_type( $template_type ) {
		return in_array( $template_type, $this->supported_template_types, true );
	}

	/**
	 * Sanitize template data.
	 *
	 * @param array $data Template data to sanitize.
	 * @return array Sanitized template data.
	 */
	private function sanitize_template_data( $data ) {
		if ( ! is_array( $data ) ) {
				return array();
		}

		$sanitized = array();

		foreach ( $data as $key => $value ) {
			$key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_template_data( $value );
			} elseif ( is_string( $value ) ) {
				$sanitized[ $key ] = sanitize_text_field( $value );
			} elseif ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
				$sanitized[ $key ] = $value;
			} elseif ( is_callable( $value ) ) {
					// Keep callable values as-is for now.
					$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize post type arguments.
	 *
	 * @param array $args Post type arguments.
	 * @return array Sanitized arguments.
	 */
	private function sanitize_post_type_args( $args ) {
		if ( ! is_array( $args ) ) {
				return array();
		}

		$sanitized     = array();
		$string_fields = array( 'label', 'description' );
		$bool_fields   = array( 'public', 'publicly_queryable', 'show_ui', 'show_in_menu', 'show_in_nav_menus', 'show_in_admin_bar', 'exclude_from_search', 'has_archive', 'hierarchical', 'show_in_rest' );

		foreach ( $args as $key => $value ) {
			$key = sanitize_key( $key );

			if ( in_array( $key, $string_fields, true ) ) {
				$sanitized[ $key ] = sanitize_text_field( $value );
			} elseif ( in_array( $key, $bool_fields, true ) ) {
				$sanitized[ $key ] = (bool) $value;
			} elseif ( 'supports' === $key && is_array( $value ) ) {
				$sanitized[ $key ] = array_map( 'sanitize_key', $value );
			} elseif ( 'rewrite' === $key && is_array( $value ) ) {
					$sanitized[ $key ] = array(
						'slug'       => isset( $value['slug'] ) ? sanitize_key( $value['slug'] ) : '',
						'with_front' => isset( $value['with_front'] ) ? (bool) $value['with_front'] : true,
					);
			} elseif ( 'labels' === $key && is_array( $value ) ) {
					$sanitized[ $key ] = array_map( 'sanitize_text_field', $value );
			} else {
					$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize taxonomy arguments.
	 *
	 * @param array $args Taxonomy arguments.
	 * @return array Sanitized arguments.
	 */
	private function sanitize_taxonomy_args( $args ) {
		if ( ! is_array( $args ) ) {
				return array();
		}

		$sanitized     = array();
		$string_fields = array( 'label', 'description' );
		$bool_fields   = array( 'public', 'publicly_queryable', 'show_ui', 'show_in_menu', 'show_in_nav_menus', 'show_in_quick_edit', 'show_admin_column', 'hierarchical', 'show_in_rest' );

		foreach ( $args as $key => $value ) {
			$key = sanitize_key( $key );

			if ( in_array( $key, $string_fields, true ) ) {
				$sanitized[ $key ] = sanitize_text_field( $value );
			} elseif ( in_array( $key, $bool_fields, true ) ) {
				$sanitized[ $key ] = (bool) $value;
			} elseif ( 'rewrite' === $key && is_array( $value ) ) {
				$sanitized[ $key ] = array(
					'slug'         => isset( $value['slug'] ) ? sanitize_key( $value['slug'] ) : '',
					'with_front'   => isset( $value['with_front'] ) ? (bool) $value['with_front'] : true,
					'hierarchical' => isset( $value['hierarchical'] ) ? (bool) $value['hierarchical'] : false,
				);
			} elseif ( 'labels' === $key && is_array( $value ) ) {
					$sanitized[ $key ] = array_map( 'sanitize_text_field', $value );
			} else {
					$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Get supported template types.
	 *
	 * @return array Supported template types.
	 */
	public function get_supported_template_types() {
		/**
		 * Filters supported template types.
		 *
		 * @param array $types Supported template types.
		 */
		return apply_filters( 'sync_supported_template_types', $this->supported_template_types );
	}

	/**
	 * Import templates from JSON.
	 *
	 * @param string $json_data JSON data containing templates.
	 * @return array|false Array of imported template IDs on success, false on failure.
	 */
	public function import_templates( $json_data ) {
		$templates_data = json_decode( $json_data, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $templates_data ) ) {
				return false;
		}

		$imported_ids = array();

		foreach ( $templates_data as $template_data ) {
			if ( ! isset( $template_data['type'] ) || ! isset( $template_data['data'] ) ) {
				continue;
			}

			$template_id = $this->register_template( $template_data['type'], $template_data['data'], false );
			if ( $template_id ) {
				$imported_ids[] = $template_id;
			}
		}

		/**
		 * Fires after templates are imported.
		 *
		 * @param array $imported_ids Array of imported template IDs.
		 * @param array $templates_data Original templates data.
		 */
		do_action( 'sync_templates_imported', $imported_ids, $templates_data );

		return empty( $imported_ids ) ? false : $imported_ids;
	}

	/**
	 * Export templates to JSON.
	 *
	 * @param array $template_ids Array of template IDs to export.
	 * @return string|false JSON data on success, false on failure.
	 */
	public function export_templates( $template_ids ) {
		if ( ! is_array( $template_ids ) || empty( $template_ids ) ) {
				return false;
		}

		$export_data = array();

		foreach ( $template_ids as $template_id ) {
			$template_id = absint( $template_id );
			$post        = get_post( $template_id );

			if ( ! $post || self::POST_TYPE_TEMPLATE !== $post->post_type ) {
				continue;
			}

			$template_type = get_post_meta( $template_id, '_sync_template_type', true );
			$template_data = $this->get_template_data( $template_id );

			if ( $template_data && $template_type ) {
				$export_data[] = array(
					'type' => $template_type,
					'data' => $template_data,
					'meta' => array(
						'title'    => $post->post_title,
						'created'  => $post->post_date,
						'modified' => $post->post_modified,
					),
				);
			}
		}

		if ( empty( $export_data ) ) {
			return false;
		}

		/**
		 * Fires before templates are exported.
		 *
		 * @param array $export_data Export data.
		 * @param array $template_ids Template IDs being exported.
		 */
		do_action( 'sync_templates_before_export', $export_data, $template_ids );

		return wp_json_encode( $export_data, JSON_PRETTY_PRINT );
	}

	/**
	 * Get template statistics.
	 *
	 * @return array Template statistics.
	 */
	public function get_template_statistics() {
		$stats = array(
			'total_templates'  => 0,
			'active_templates' => 0,
			'total_groups'     => 0,
			'active_groups'    => 0,
			'types'            => array(),
		);

		// Count templates.
		$templates                = $this->get_templates();
		$stats['total_templates'] = count( $templates );

		// Count active templates.
		$active_templates          = $this->get_templates( '', true );
		$stats['active_templates'] = count( $active_templates );

		// Count by type.
		foreach ( $templates as $template ) {
			$template_type = get_post_meta( $template->ID, '_sync_template_type', true );
			if ( $template_type ) {
				if ( ! isset( $stats['types'][ $template_type ] ) ) {
					$stats['types'][ $template_type ] = array(
						'total'  => 0,
						'active' => 0,
					);
				}
				++$stats['types'][ $template_type ]['total'];

				if ( $this->is_template_active( $template->ID ) ) {
					++$stats['types'][ $template_type ]['active'];
				}
			}
		}

		// Count groups.
		$groups                = $this->get_template_groups();
		$stats['total_groups'] = count( $groups );

		// Count active groups.
		$active_groups          = $this->get_template_groups( true );
		$stats['active_groups'] = count( $active_groups );

		/**
		 * Filters template statistics.
		 *
		 * @param array $stats Template statistics.
		 */
		return apply_filters( 'sync_template_statistics', $stats );
	}

	/**
	 * Bulk activate templates.
	 *
	 * @param array $template_ids Array of template IDs to activate.
	 * @return array Array of results (template_id => success).
	 */
	public function bulk_activate_templates( $template_ids ) {
		if ( ! is_array( $template_ids ) ) {
				return array();
		}

		$results = array();

		foreach ( $template_ids as $template_id ) {
			$template_id             = absint( $template_id );
			$results[ $template_id ] = $this->activate_template( $template_id );
		}

		/**
		 * Fires after bulk template activation.
		 *
		 * @param array $results Results of activation attempts.
		 * @param array $template_ids Template IDs that were processed.
		 */
		do_action( 'sync_templates_bulk_activated', $results, $template_ids );

		return $results;
	}

	/**
	 * Bulk deactivate templates.
	 *
	 * @param array $template_ids Array of template IDs to deactivate.
	 * @return array Array of results (template_id => success).
	 */
	public function bulk_deactivate_templates( $template_ids ) {
		if ( ! is_array( $template_ids ) ) {
				return array();
		}

		$results = array();

		foreach ( $template_ids as $template_id ) {
			$template_id             = absint( $template_id );
			$results[ $template_id ] = $this->deactivate_template( $template_id );
		}

		/**
		 * Fires after bulk template deactivation.
		 *
		 * @param array $results Results of deactivation attempts.
		 * @param array $template_ids Template IDs that were processed.
		 */
		do_action( 'sync_templates_bulk_deactivated', $results, $template_ids );

		return $results;
	}

	/**
	 * Duplicate a template.
	 *
	 * @param int    $template_id Template ID to duplicate.
	 * @param string $new_title   New title for duplicated template.
	 * @return int|false New template ID on success, false on failure.
	 */
	public function duplicate_template( $template_id, $new_title = '' ) {
		$template_id = absint( $template_id );
		$post        = get_post( $template_id );

		if ( ! $post || self::POST_TYPE_TEMPLATE !== $post->post_type ) {
				return false;
		}

		$template_type = get_post_meta( $template_id, '_sync_template_type', true );
		$template_data = $this->get_template_data( $template_id );

		if ( ! $template_data || ! $template_type ) {
			return false;
		}

		// Set new title.
		if ( empty( $new_title ) ) {
			$new_title = $post->post_title . ' (Copy)';
		}

		// Update template data with new title.
		if ( isset( $template_data['name'] ) ) {
			$template_data['name'] = $new_title;
		}

		// Register new template.
		$new_template_id = $this->register_template( $template_type, $template_data, false );

		if ( $new_template_id ) {
			// Update post title.
			wp_update_post(
				array(
					'ID'         => $new_template_id,
					'post_title' => $new_title,
				)
			);

			/**
			 * Fires after a template is duplicated.
			 *
			 * @param int $new_template_id New template ID.
			 * @param int $template_id     Original template ID.
			 */
			do_action( 'sync_template_duplicated', $new_template_id, $template_id );
		}

		return $new_template_id;
	}

	/**
	 * Get template by slug.
	 *
	 * @param string $slug Template slug.
	 * @param string $template_type Template type (optional).
	 * @return \WP_Post|false Template post on success, false on failure.
	 */
	public function get_template_by_slug( $slug, $template_type = '' ) {
		$args = array(
			'post_type'      => self::POST_TYPE_TEMPLATE,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'name'           => sanitize_key( $slug ),
		);

		if ( ! empty( $template_type ) && $this->is_valid_template_type( $template_type ) ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_sync_template_type',
					'value' => sanitize_key( $template_type ),
				),
			);
		}

		$templates = get_posts( $args );

		return ! empty( $templates ) ? $templates[0] : false;
	}

	/**
	 * Clear template cache.
	 */
	public function clear_template_cache() {
		$this->active_templates_loaded = false;
		$this->active_templates        = array();

		/**
		 * Fires when template cache is cleared.
		 */
		do_action( 'sync_template_cache_cleared' );
	}

	/**
	 * Validate template data structure.
	 *
	 * @param array  $template_data Template data to validate.
	 * @param string $template_type Template type.
	 * @return bool|\WP_Error True if valid, WP_Error with details if invalid.
	 */
	public function validate_template_data( $template_data, $template_type ) {
		if ( ! is_array( $template_data ) ) {
				return new \WP_Error( 'invalid_data', __( 'Template data must be an array.', 'wisesync' ) );
		}

		if ( ! $this->is_valid_template_type( $template_type ) ) {
			return new \WP_Error( 'invalid_type', __( 'Invalid template type.', 'wisesync' ) );
		}

		// Validate based on template type.
		switch ( $template_type ) {
			case 'post_type':
				if ( ! isset( $template_data['post_type'] ) || ! isset( $template_data['args'] ) ) {
					return new \WP_Error( 'missing_fields', __( 'Post type template requires post_type and args fields.', 'wisesync' ) );
				}
				break;

			case 'taxonomy':
				if ( ! isset( $template_data['taxonomy'] ) || ! isset( $template_data['object_type'] ) || ! isset( $template_data['args'] ) ) {
					return new \WP_Error( 'missing_fields', __( 'Taxonomy template requires taxonomy, object_type, and args fields.', 'wisesync' ) );
				}
				break;

			case 'meta_box':
				if ( ! isset( $template_data['id'] ) || ! isset( $template_data['title'] ) || ! isset( $template_data['callback'] ) ) {
					return new \WP_Error( 'missing_fields', __( 'Meta box template requires id, title, and callback fields.', 'wisesync' ) );
				}
				break;

			case 'user_role':
				if ( ! isset( $template_data['role'] ) || ! isset( $template_data['display_name'] ) ) {
					return new \WP_Error( 'missing_fields', __( 'User role template requires role and display_name fields.', 'wisesync' ) );
				}
				break;

			case 'theme_template':
			case 'theme_part':
				if ( ! isset( $template_data['slug'] ) || ! isset( $template_data['content'] ) ) {
					return new \WP_Error( 'missing_fields', __( 'Theme template requires slug and content fields.', 'wisesync' ) );
				}
				break;

			case 'shortcode':
				if ( ! isset( $template_data['tag'] ) || ! isset( $template_data['callback'] ) ) {
					return new \WP_Error( 'missing_fields', __( 'Shortcode template requires tag and callback fields.', 'wisesync' ) );
				}
				break;

			case 'block_pattern':
				if ( ! isset( $template_data['name'] ) || ! isset( $template_data['content'] ) ) {
					return new \WP_Error( 'missing_fields', __( 'Block pattern template requires name and content fields.', 'wisesync' ) );
				}
				break;

			case 'block_style':
				if ( ! isset( $template_data['block_name'] ) || ! isset( $template_data['style_properties'] ) ) {
					return new \WP_Error( 'missing_fields', __( 'Block style template requires block_name and style_properties fields.', 'wisesync' ) );
				}
				break;
		}

		/**
		 * Filters template data validation.
		 *
		 * @param bool|\WP_Error $valid         Whether template data is valid.
		 * @param array          $template_data Template data.
		 * @param string         $template_type Template type.
		 */
		return apply_filters( 'sync_validate_template_data', true, $template_data, $template_type );
	}

	/**
	 * Get active templates by type.
	 *
	 * @param string $template_type Template type.
	 * @return array Array of active template IDs.
	 */
	public function get_active_templates_by_type( $template_type ) {
		if ( ! $this->is_valid_template_type( $template_type ) ) {
				return array();
		}

		$templates = $this->get_templates( $template_type, true );
		return wp_list_pluck( $templates, 'ID' );
	}

	/**
	 * Check if template can be executed.
	 *
	 * @param int $template_id Template ID.
	 * @return bool True if can be executed, false otherwise.
	 */
	public function can_execute_template( $template_id ) {
		$template_id = absint( $template_id );
		$post        = get_post( $template_id );

		if ( ! $post || self::POST_TYPE_TEMPLATE !== $post->post_type ) {
				return false;
		}

		$template_type = get_post_meta( $template_id, '_sync_template_type', true );
		$template_data = $this->get_template_data( $template_id );

		if ( ! $template_data || ! $this->is_valid_template_type( $template_type ) ) {
			return false;
		}

		$validation = $this->validate_template_data( $template_data, $template_type );
		if ( is_wp_error( $validation ) ) {
			return false;
		}

		/**
		 * Filters whether a template can be executed.
		 *
		 * @param bool  $can_execute    Whether template can be executed.
		 * @param int   $template_id    Template ID.
		 * @param string $template_type Template type.
		 * @param array $template_data  Template data.
		 */
		return apply_filters( 'sync_can_execute_template', true, $template_id, $template_type, $template_data );
	}

	/**
	 * Get template dependencies.
	 *
	 * @param int $template_id Template ID.
	 * @return array Array of dependency information.
	 */
	public function get_template_dependencies( $template_id ) {
		$template_id   = absint( $template_id );
		$template_type = get_post_meta( $template_id, '_sync_template_type', true );
		$template_data = $this->get_template_data( $template_id );

		$dependencies = array(
			'classes'   => array(),
			'functions' => array(),
			'constants' => array(),
			'plugins'   => array(),
			'themes'    => array(),
		);

		if ( ! $template_data || ! $template_type ) {
			return $dependencies;
		}

		// Check dependencies based on template type.
		switch ( $template_type ) {
			case 'theme_template':
			case 'theme_part':
				$dependencies['themes'][] = array(
					'name'  => 'Block Theme Support',
					'check' => current_theme_supports( 'block-templates' ),
				);
				break;

			case 'meta_box':
				if ( isset( $template_data['callback'] ) && is_string( $template_data['callback'] ) ) {
					$dependencies['functions'][] = array(
						'name'  => $template_data['callback'],
						'check' => function_exists( $template_data['callback'] ),
					);
				}
				break;

			case 'widget':
				if ( isset( $template_data['widget_class'] ) ) {
					$dependencies['classes'][] = array(
						'name'  => $template_data['widget_class'],
						'check' => class_exists( $template_data['widget_class'] ),
					);
				}
				break;

			case 'shortcode':
				if ( isset( $template_data['callback'] ) && is_string( $template_data['callback'] ) ) {
					$dependencies['functions'][] = array(
						'name'  => $template_data['callback'],
						'check' => function_exists( $template_data['callback'] ),
					);
				}
				break;

			case 'block_pattern':
				$dependencies['functions'][] = array(
					'name'  => 'register_block_pattern',
					'check' => function_exists( 'register_block_pattern' ),
				);
				break;

			case 'block_style':
				$dependencies['functions'][] = array(
					'name'  => 'register_block_style',
					'check' => function_exists( 'register_block_style' ),
				);
				break;
		}

		/**
		 * Filters template dependencies.
		 *
		 * @param array  $dependencies  Template dependencies.
		 * @param int    $template_id   Template ID.
		 * @param string $template_type Template type.
		 * @param array  $template_data Template data.
		 */
		return apply_filters( 'sync_template_dependencies', $dependencies, $template_id, $template_type, $template_data );
	}
}
