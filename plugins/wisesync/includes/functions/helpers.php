<?php
/**
 * WiseSync Helpers Functions
 *
 * This file contains helper functions for the WiseSync plugin.
 *
 * @package   WiseSync
 * @since     1.0.0
 */

/**
 * Adds a role, if it does not exist, with VIP Handler.
 *
 * This function is a wrapper for the `add_role` function, which adds a new role to WordPress.
 * While Following VIP Guidelines for Its Environment.
 *
 * @param string $role         Role name.
 * @param string $display_name Display name for role.
 * @param bool[] $capabilities List of capabilities keyed by the capability name,
 *                             e.g. array( 'edit_posts' => true, 'delete_posts' => false ).
 * @return WP_Role|void WP_Role object, if the role is added.
 */
function sync_add_role( $role, $display_name, $capabilities = array() ) {
	if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
		wpcom_vip_add_role( $role, $display_name, $capabilities );
	} else {
		return add_role( $role, $display_name, $capabilities ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.custom_role_add_role
	}
}

/**
 * Tries to convert an attachment URL into a post ID.
 *
 * This function is a wrapper for the `attachment_url_to_postid` function, which resolves an attachment URL into a post ID.
 * It is designed to work in both the WordPress VIP environment and standard WordPress installations.
 *
 * @param string $url The URL to resolve.
 *
 * @return int The found post ID, or 0 on failure.
 */
function sync_attachment_url_to_postid( $url ) {
	if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
		return wpcom_vip_attachment_url_to_postid( $url );
	} else {
		return attachment_url_to_postid( $url ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid
	}
}

/**
 * Count published posts from a user.
 *
 * This function is a wrapper for the `count_user_posts` function, which counts the number of posts
 * created by a specific user. It is designed to work in both the WordPress VIP environment and
 * standard WordPress installations.
 *
 * @param int          $user_id User ID.
 * @param string|array $post_type Post type or array of post types to count.
 * @param bool         $public_only Whether to limit to public posts only.
 * 
 * @return int|string The number of posts by the user.
 */
function sync_count_user_posts( $user_id, $post_type = 'post', $public_only = true ) {
	if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
		return (int) wpcom_vip_count_user_posts( $user_id, $post_type, $public_only );
	} else {
		return count_user_posts( $user_id, $post_type, $public_only ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.count_user_posts_count_user_posts
	}
}

/**
 * Retrieves adjacent post.
 *
 * This function is a wrapper for the `get_adjacent_post` function, which retrieves the adjacent post.
 * It is designed to work in both the WordPress VIP environment and standard WordPress installations.
 *
 * @param bool   $in_same_term   Whether post should be in the same taxonomy term.
 * @param string $excluded_terms Comma-separated list of excluded term IDs.
 * @param bool   $previous       Whether to retrieve previous post.
 * @param string $taxonomy       Taxonomy, if $in_same_term is true.
 *
 * @return WP_Post|null Post object if successful, null otherwise.
 */
function sync_get_adjacent_post( $in_same_term = false, $excluded_terms = '', $previous = true, $taxonomy = 'category' ) {
	if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
		return wpcom_vip_get_adjacent_post( $in_same_term, $excluded_terms, $previous, $taxonomy );
	} else {
		return get_adjacent_post( $in_same_term, $excluded_terms, $previous, $taxonomy ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_adjacent_post_get_adjacent_post
	}
}

/**
 * Retrieves the previous post.
 *
 * This function is a wrapper for the `get_previous_post` function, which retrieves the previous post.
 * It is designed to work in both the WordPress VIP environment and standard WordPress installations.
 *
 * @param bool   $in_same_term   Whether post should be in the same taxonomy term.
 * @param string $excluded_terms Comma-separated list of excluded term IDs.
 * @param string $taxonomy       Taxonomy, if $in_same_term is true.
 *
 * @return WP_Post|null Post object if successful, null otherwise.
 */
function sync_get_previous_post( $in_same_term = false, $excluded_terms = '', $taxonomy = 'category' ) {
	if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
		return wpcom_vip_get_adjacent_post( $in_same_term, $excluded_terms, true, $taxonomy );
	} else {
		return get_previous_post( $in_same_term, $excluded_terms, $taxonomy ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_adjacent_post_get_previous_post
	}
}

/**
 * Retrieves the next post.
 *
 * This function is a wrapper for the `get_next_post` function, which retrieves the next post.
 * It is designed to work in both the WordPress VIP environment and standard WordPress installations.
 *
 * @param bool   $in_same_term   Whether post should be in the same taxonomy term.
 * @param string $excluded_terms Comma-separated list of excluded term IDs.
 * @param string $taxonomy       Taxonomy, if $in_same_term is true.
 *
 * @return WP_Post|null Post object if successful, null otherwise.
 */
function sync_get_next_post( $in_same_term = false, $excluded_terms = '', $taxonomy = 'category' ) {
	if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
		return wpcom_vip_get_adjacent_post( $in_same_term, $excluded_terms, false, $taxonomy );
	} else {
		return get_next_post( $in_same_term, $excluded_terms, $taxonomy ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_adjacent_post_get_next_post
	}
}

/**
 * Embed content through oEmbed.
 *
 * This function is a wrapper for the `wp_oembed_get` function, which embeds content through oEmbed.
 * It is designed to work in both the WordPress VIP environment and standard WordPress installations.
 *
 * @param string $url  The URL to the content that should be embedded.
 * @param array  $args Optional arguments.
 *
 * @return string|false The embed HTML on success, false otherwise.
 */
function sync_wp_oembed_get( $url, $args = array() ) {
	if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
		return wpcom_vip_wp_oembed_get( $url, $args );
	} else {
		return wp_oembed_get( $url, $args ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_oembed_get_wp_oembed_get
	}
}

/**
 * Determines the post ID of a URL.
 *
 * This function is a wrapper for the `url_to_postid` function, which determines the post ID of a URL.
 * It is designed to work in both the WordPress VIP environment and standard WordPress installations.
 *
 * @param string $url The URL to determine the post ID for.
 *
 * @return int The post ID on success, 0 on failure.
 */
function sync_url_to_postid( $url ) {
	if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
		return wpcom_vip_url_to_postid( $url );
	} else {
		return url_to_postid( $url ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.url_to_postid_url_to_postid
	}
}

/**
 * Handles the redirect for 'old slugs'.
 *
 * This function is a wrapper for the `wp_old_slug_redirect` function, which handles the redirect for 'old slugs'.
 * It is designed to work in both the WordPress VIP environment and standard WordPress installations.
 *
 * @return bool|void False if no redirect was triggered, void on redirect.
 */
function sync_old_slug_redirect() {
	if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
		wpcom_vip_wp_old_slug_redirect();
		return false; // Only reached if no redirect happened.
	} else {
		return wp_old_slug_redirect(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_old_slug_redirect_wp_old_slug_redirect
	}
}

/**
 * Retrieves a page object by page title.
 *
 * This function is a wrapper for the `get_page_by_title` function, which retrieves a page object by page title.
 * It is designed to work in both the WordPress VIP environment and standard WordPress installations.
 * For WordPress 6.2 and above, uses WP_Query instead of the deprecated function.
 *
 * @param string       $page_title Page title.
 * @param string       $output     Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N. Default OBJECT.
 * @param string|array $post_type  Optional. Post type or array of post types. Default 'page'.
 *
 * @return WP_Post|array|null WP_Post on success or null on failure.
 */
function sync_get_page_by_title( $page_title, $output = OBJECT, $post_type = 'page' ) {
	global $wp_version;
	
	if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
		return wpcom_vip_get_page_by_title( $page_title, $output, $post_type );
	} elseif ( version_compare( $wp_version, '6.2', '>=' ) ) {
		// For WP 6.2+, use WP_Query as get_page_by_title is deprecated.
		$query = new WP_Query(
			array(
				'title'          => $page_title,
				'post_type'      => $post_type,
				'posts_per_page' => 1,
			)
		);
		
		if ( ! empty( $query->posts ) ) {
			$post = $query->posts[0];
			if ( ARRAY_A === $output ) {
				return $post->to_array();
			} elseif ( ARRAY_N === $output ) {
				return array_values( $post->to_array() );
			}
			return $post;
		}
		return null;
	}
	return null;
}
