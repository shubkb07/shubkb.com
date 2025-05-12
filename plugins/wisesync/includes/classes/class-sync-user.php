<?php
/**
 * WiseSync User Class
 *
 * This class handles user-related operations for the WiseSync plugin.
 * It provides wrapper functions for managing roles, capabilities, users, and metadata.
 *
 * @package   WiseSync
 * @since     1.0.0
 */

namespace Sync;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sync_User
 *
 * Handles user-related operations for the WiseSync plugin.
 *
 * @package Sync
 */
class Sync_User {

	/**
	 * Roles to register.
	 *
	 * @var array
	 */
	private $roles = array();

	/**
	 * Role capabilities to modify.
	 *
	 * @var array
	 */
	private $role_capabilities = array();

	/**
	 * Users to register.
	 *
	 * @var array
	 */
	private $users = array();

	/**
	 * User roles to change.
	 *
	 * @var array
	 */
	private $user_roles = array();

	/**
	 * User metadata to set.
	 *
	 * @var array
	 */
	private $user_metadata = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Initialize hooks for user operations.
		add_action( 'init', array( $this, 'register_roles' ), 10 );
		add_action( 'init', array( $this, 'update_role_capabilities' ), 11 );
		add_action( 'init', array( $this, 'register_users' ), 12 );
		add_action( 'init', array( $this, 'update_user_roles' ), 13 );
		add_action( 'init', array( $this, 'update_user_metadata' ), 14 );
	}

	/**
	 * Add a new role to be registered.
	 *
	 * @param string $role         Role name.
	 * @param string $display_name Display name for the role.
	 * @param array  $capabilities Array of capabilities for the role.
	 * @return Sync_User Current instance for chaining.
	 */
	public function add_role( $role, $display_name, $capabilities = array() ) {
		$this->roles[] = array(
			'role'         => $role,
			'display_name' => $display_name,
			'capabilities' => $capabilities,
		);

		return $this;
	}

	/**
	 * Add multiple roles at once.
	 *
	 * @param array $roles Array of roles to add.
	 * @return Sync_User Current instance for chaining.
	 */
	public function add_roles( $roles ) {
		foreach ( $roles as $role ) {
			if ( isset( $role['role'] ) && isset( $role['display_name'] ) ) {
				$capabilities = isset( $role['capabilities'] ) ? $role['capabilities'] : array();
				$this->add_role( $role['role'], $role['display_name'], $capabilities );
			}
		}

		return $this;
	}

	/**
	 * Add capabilities to an existing role.
	 *
	 * @param string $role         Role name.
	 * @param array  $capabilities Array of capabilities to add.
	 * @param bool   $remove       Whether to remove capabilities instead of adding.
	 * @return Sync_User Current instance for chaining.
	 */
	public function modify_role_capabilities( $role, $capabilities, $remove = false ) {
		$this->role_capabilities[] = array(
			'role'         => $role,
			'capabilities' => $capabilities,
			'remove'       => $remove,
		);

		return $this;
	}

	/**
	 * Bulk modify role capabilities.
	 *
	 * @param array $role_capabilities Array of role capabilities to modify.
	 * @return Sync_User Current instance for chaining.
	 */
	public function modify_roles_capabilities( $role_capabilities ) {
		foreach ( $role_capabilities as $role_cap ) {
			if ( isset( $role_cap['role'] ) && isset( $role_cap['capabilities'] ) ) {
				$remove = isset( $role_cap['remove'] ) ? $role_cap['remove'] : false;
				$this->modify_role_capabilities( $role_cap['role'], $role_cap['capabilities'], $remove );
			}
		}

		return $this;
	}

	/**
	 * Add a user to be registered.
	 *
	 * @param string $username Username.
	 * @param string $email    User email.
	 * @param string $password User password.
	 * @param array  $args     Additional user arguments.
	 * @return Sync_User Current instance for chaining.
	 */
	public function add_user( $username, $email, $password, $args = array() ) {
		$this->users[] = array_merge(
			array(
				'username' => $username,
				'email'    => $email,
				'password' => $password,
			),
			$args
		);

		return $this;
	}

	/**
	 * Add multiple users at once.
	 *
	 * @param array $users Array of users to add.
	 * @return Sync_User Current instance for chaining.
	 */
	public function add_users( $users ) {
		foreach ( $users as $user ) {
			if ( isset( $user['username'] ) && isset( $user['email'] ) && isset( $user['password'] ) ) {
				$args = array_diff_key( $user, array_flip( array( 'username', 'email', 'password' ) ) );
				$this->add_user( $user['username'], $user['email'], $user['password'], $args );
			}
		}

		return $this;
	}

	/**
	 * Change a user's role.
	 *
	 * @param mixed  $user_identifier User ID or email or username.
	 * @param string $role            New role.
	 * @param bool   $append          Whether to append the role instead of replacing.
	 * @return Sync_User Current instance for chaining.
	 */
	public function change_user_role( $user_identifier, $role, $append = false ) {
		$this->user_roles[] = array(
			'user'   => $user_identifier,
			'role'   => $role,
			'append' => $append,
		);

		return $this;
	}

	/**
	 * Change multiple user roles at once.
	 *
	 * @param array $user_roles Array of user roles to change.
	 * @return Sync_User Current instance for chaining.
	 */
	public function change_user_roles( $user_roles ) {
		foreach ( $user_roles as $user_role ) {
			if ( isset( $user_role['user'] ) && isset( $user_role['role'] ) ) {
				$append = isset( $user_role['append'] ) ? $user_role['append'] : false;
				$this->change_user_role( $user_role['user'], $user_role['role'], $append );
			}
		}

		return $this;
	}

	/**
	 * Add metadata to a user.
	 *
	 * @param mixed  $user_identifier User ID or email or username.
	 * @param string $meta_key        Meta key.
	 * @param mixed  $meta_value      Meta value.
	 * @param bool   $unique          Whether the meta key should be unique.
	 * @return Sync_User Current instance for chaining.
	 */
	public function add_user_meta( $user_identifier, $meta_key, $meta_value, $unique = false ) {
		$this->user_metadata[] = array(
			'user'       => $user_identifier,
			'meta_key'   => $meta_key,
			'meta_value' => $meta_value,
			'unique'     => $unique,
			'action'     => 'add',
		);

		return $this;
	}

	/**
	 * Update metadata for a user.
	 *
	 * @param mixed  $user_identifier User ID or email or username.
	 * @param string $meta_key        Meta key.
	 * @param mixed  $meta_value      Meta value.
	 * @param mixed  $prev_value      Previous value to update from (optional).
	 * @return Sync_User Current instance for chaining.
	 */
	public function update_user_meta( $user_identifier, $meta_key, $meta_value, $prev_value = '' ) {
		$this->user_metadata[] = array(
			'user'       => $user_identifier,
			'meta_key'   => $meta_key,
			'meta_value' => $meta_value,
			'prev_value' => $prev_value,
			'action'     => 'update',
		);

		return $this;
	}

	/**
	 * Delete metadata from a user.
	 *
	 * @param mixed  $user_identifier User ID or email or username.
	 * @param string $meta_key        Meta key.
	 * @param mixed  $meta_value      Meta value to delete (optional).
	 * @return Sync_User Current instance for chaining.
	 */
	public function delete_user_meta( $user_identifier, $meta_key, $meta_value = '' ) {
		$this->user_metadata[] = array(
			'user'       => $user_identifier,
			'meta_key'   => $meta_key,
			'meta_value' => $meta_value,
			'action'     => 'delete',
		);

		return $this;
	}

	/**
	 * Bulk update user metadata.
	 *
	 * @param array $metadata Array of user metadata operations.
	 * @return Sync_User Current instance for chaining.
	 */
	public function update_user_metadata_bulk( $metadata ) {
		foreach ( $metadata as $meta_op ) {
			if ( isset( $meta_op['user'] ) && isset( $meta_op['meta_key'] ) && isset( $meta_op['action'] ) ) {
				$action = $meta_op['action'];
				
				if ( 'add' === $action ) {
					$unique = isset( $meta_op['unique'] ) ? $meta_op['unique'] : false;
					$this->add_user_meta( $meta_op['user'], $meta_op['meta_key'], $meta_op['meta_value'], $unique );
				} elseif ( 'update' === $action ) {
					$prev_value = isset( $meta_op['prev_value'] ) ? $meta_op['prev_value'] : '';
					$this->update_user_meta( $meta_op['user'], $meta_op['meta_key'], $meta_op['meta_value'], $prev_value );
				} elseif ( 'delete' === $action ) {
					$meta_value = isset( $meta_op['meta_value'] ) ? $meta_op['meta_value'] : '';
					$this->delete_user_meta( $meta_op['user'], $meta_op['meta_key'], $meta_value );
				}
			}
		}

		return $this;
	}

	/**
	 * Register all roles.
	 */
	public function register_roles() {
		foreach ( $this->roles as $role ) {
			sync_add_role( $role['role'], $role['display_name'], $role['capabilities'] );
		}
	}

	/**
	 * Update all role capabilities.
	 */
	public function update_role_capabilities() {
		foreach ( $this->role_capabilities as $role_cap ) {
			$role = get_role( $role_cap['role'] );
			
			if ( $role ) {
				foreach ( $role_cap['capabilities'] as $capability => $grant ) {
					if ( $role_cap['remove'] ) {
						$role->remove_cap( $capability );
					} else {
						$role->add_cap( $capability, $grant );
					}
				}
			}
		}
	}

	/**
	 * Register all users.
	 */
	public function register_users() {
		foreach ( $this->users as $user ) {
			$userdata = array(
				'user_login' => $user['username'],
				'user_email' => $user['email'],
				'user_pass'  => $user['password'],
			);

			// Add optional user data.
			if ( isset( $user['first_name'] ) ) {
				$userdata['first_name'] = $user['first_name'];
			}
			if ( isset( $user['last_name'] ) ) {
				$userdata['last_name'] = $user['last_name'];
			}
			if ( isset( $user['display_name'] ) ) {
				$userdata['display_name'] = $user['display_name'];
			}
			if ( isset( $user['role'] ) ) {
				$userdata['role'] = $user['role'];
			}
			if ( isset( $user['url'] ) ) {
				$userdata['user_url'] = $user['url'];
			}
			if ( isset( $user['description'] ) ) {
				$userdata['description'] = $user['description'];
			}

			$user_id = wp_insert_user( $userdata );

			// Set user meta if present.
			if ( ! is_wp_error( $user_id ) && isset( $user['meta'] ) && is_array( $user['meta'] ) ) {
				foreach ( $user['meta'] as $meta_key => $meta_value ) {
					update_user_meta( $user_id, $meta_key, $meta_value );
				}
			}
		}
	}

	/**
	 * Update all user roles.
	 */
	public function update_user_roles() {
		foreach ( $this->user_roles as $user_role ) {
			$user_id = $this->get_user_id( $user_role['user'] );
			
			if ( $user_id ) {
				$user = new \WP_User( $user_id );
				
				if ( $user_role['append'] ) {
					$user->add_role( $user_role['role'] );
				} else {
					$user->set_role( $user_role['role'] );
				}
			}
		}
	}

	/**
	 * Update all user metadata.
	 */
	public function update_user_metadata() {
		foreach ( $this->user_metadata as $metadata ) {
			$user_id = $this->get_user_id( $metadata['user'] );
			
			if ( $user_id ) {
				if ( 'add' === $metadata['action'] ) {
					add_user_meta( $user_id, $metadata['meta_key'], $metadata['meta_value'], $metadata['unique'] );
				} elseif ( 'update' === $metadata['action'] ) {
					if ( ! empty( $metadata['prev_value'] ) ) {
						update_user_meta( $user_id, $metadata['meta_key'], $metadata['meta_value'], $metadata['prev_value'] );
					} else {
						update_user_meta( $user_id, $metadata['meta_key'], $metadata['meta_value'] );
					}
				} elseif ( 'delete' === $metadata['action'] ) {
					if ( ! empty( $metadata['meta_value'] ) ) {
						delete_user_meta( $user_id, $metadata['meta_key'], $metadata['meta_value'] );
					} else {
						delete_user_meta( $user_id, $metadata['meta_key'] );
					}
				}
			}
		}
	}

	/**
	 * Get user ID from identifier (ID, email, or username).
	 *
	 * @param mixed $user_identifier User ID or email or username.
	 * @return int|false User ID or false if not found.
	 */
	private function get_user_id( $user_identifier ) {
		if ( is_numeric( $user_identifier ) ) {
			return absint( $user_identifier );
		}
		
		if ( is_email( $user_identifier ) ) {
			$user = get_user_by( 'email', $user_identifier );
		} else {
			$user = get_user_by( 'login', $user_identifier );
		}
		
		return $user ? $user->ID : false;
	}

	/**
	 * Remove a role from the system.
	 *
	 * @param string $role Role to remove.
	 * @return Sync_User Current instance for chaining.
	 */
	public function remove_role( $role ) {
		// This will be executed directly since we can't queue it up.
		remove_role( $role );
		
		return $this;
	}

	/**
	 * Delete a user from the system.
	 *
	 * @param mixed $user_identifier User ID or email or username.
	 * @param int   $reassign        User ID to reassign posts to (optional).
	 * @return bool|Sync_User True on success, WP_Error on failure, or current instance for chaining if queued.
	 */
	public function delete_user( $user_identifier, $reassign = null ) {
		$user_id = $this->get_user_id( $user_identifier );
		
		if ( $user_id ) {
			return wp_delete_user( $user_id, $reassign );
		}
		
		return false;
	}

	/**
	 * Check if a user has a specific capability.
	 *
	 * @param mixed  $user_identifier User ID or email or username.
	 * @param string $capability      Capability to check.
	 * @param mixed  $args            Optional arguments to pass to has_cap().
	 * @return bool Whether user has the capability.
	 */
	public function user_can( $user_identifier, $capability, $args = null ) {
		$user_id = $this->get_user_id( $user_identifier );
		
		if ( $user_id ) {
			$user = new \WP_User( $user_id );
			return $user->has_cap( $capability, $args );
		}
		
		return false;
	}

	/**
	 * Get all users with a specific role.
	 *
	 * @param string $role Role to check for.
	 * @return array Array of WP_User objects.
	 */
	public function get_users_by_role( $role ) {
		return get_users( array( 'role' => $role ) );
	}

	/**
	 * Get count of users with a specific role.
	 *
	 * @param string $role Role to count.
	 * @return int Number of users with the role.
	 */
	public function count_users_by_role( $role ) {
		$users = $this->get_users_by_role( $role );
		return count( $users );
	}

	/**
	 * Get all capabilities for a specific role.
	 *
	 * @param string $role Role to check.
	 * @return array|false Array of capabilities or false if role doesn't exist.
	 */
	public function get_role_capabilities( $role ) {
		$role_obj = get_role( $role );
		
		if ( $role_obj ) {
			return $role_obj->capabilities;
		}
		
		return false;
	}
}
