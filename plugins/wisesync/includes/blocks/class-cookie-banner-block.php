<?php
/**
 * Cookie Banner Block Class
 *
 * Handles the server-side rendering and registration of the Cookie Banner block
 *
 * @package WiseSync
 */

namespace WiseSync\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Cookie Banner Block Class
 */
class Cookie_Banner_Block {

	/**
	 * Block initialization
	 */
	public function init() {
		// Register the block type with the render callback
		register_block_type(
			plugin_dir_path( WISESYNC_PLUGIN_FILE ) . 'blocks/build/cookie-banner',
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Renders the Cookie Banner block on the frontend
	 *
	 * @param array    $attributes The block attributes.
	 * @param string   $content The block content.
	 * @param WP_Block $block The block instance.
	 * @return string The HTML markup for the block.
	 */
	public function render( $attributes, $content, $block ) {
		// Check if block should only be shown in site editor
		$show_only_in_site_editor = isset( $attributes['showOnlyInSiteEditor'] ) ? $attributes['showOnlyInSiteEditor'] : true;
		if ( $show_only_in_site_editor && ! $this->is_site_editor_template() ) {
			return '';
		}

		// Generate a unique ID for this cookie banner instance
		$block_id = uniqid( 'cookie-banner-' );

		// Extract attributes with defaults
		$banner_title           = isset( $attributes['bannerTitle'] ) ? $attributes['bannerTitle'] : 'Cookie Consent';
		$banner_description     = isset( $attributes['bannerDescription'] ) ? $attributes['bannerDescription'] : 'We use cookies to enhance your browsing experience, serve personalized ads or content, and analyze our traffic.';
		$privacy_policy_link    = isset( $attributes['privacyPolicyLink'] ) ? $attributes['privacyPolicyLink'] : '';
		$privacy_policy_text    = isset( $attributes['privacyPolicyText'] ) ? $attributes['privacyPolicyText'] : 'Privacy Policy';
		$privacy_policy_version = isset( $attributes['privacyPolicyVersion'] ) ? $attributes['privacyPolicyVersion'] : '';
		$terms_link             = isset( $attributes['termsLink'] ) ? $attributes['termsLink'] : '';
		$terms_text             = isset( $attributes['termsText'] ) ? $attributes['termsText'] : 'Terms of Service';
		$terms_version          = isset( $attributes['termsVersion'] ) ? $attributes['termsVersion'] : '';
		$check_version_changes  = isset( $attributes['checkVersionChanges'] ) ? $attributes['checkVersionChanges'] : true;
		$accept_all_text        = isset( $attributes['acceptAllText'] ) ? $attributes['acceptAllText'] : 'Accept All';
		$reject_all_text        = isset( $attributes['rejectAllText'] ) ? $attributes['rejectAllText'] : 'Reject All';
		$customize_text         = isset( $attributes['customizeText'] ) ? $attributes['customizeText'] : 'Customize';
		$save_preferences_text  = isset( $attributes['savePreferencesText'] ) ? $attributes['savePreferencesText'] : 'Save Preferences';

		// Cookie categories
		$necessary_cookies_title         = isset( $attributes['necessaryCookiesTitle'] ) ? $attributes['necessaryCookiesTitle'] : 'Necessary';
		$necessary_cookies_description   = isset( $attributes['necessaryCookiesDescription'] ) ? $attributes['necessaryCookiesDescription'] : 'Necessary cookies are essential for the website to function properly.';
		$functional_cookies_title        = isset( $attributes['functionalCookiesTitle'] ) ? $attributes['functionalCookiesTitle'] : 'Functional';
		$functional_cookies_description  = isset( $attributes['functionalCookiesDescription'] ) ? $attributes['functionalCookiesDescription'] : 'Functional cookies help perform certain functionalities.';
		$analytical_cookies_title        = isset( $attributes['analyticalCookiesTitle'] ) ? $attributes['analyticalCookiesTitle'] : 'Analytics';
		$analytical_cookies_description  = isset( $attributes['analyticalCookiesDescription'] ) ? $attributes['analyticalCookiesDescription'] : 'Analytical cookies help understand how visitors interact with the website.';
		$advertising_cookies_title       = isset( $attributes['advertisingCookiesTitle'] ) ? $attributes['advertisingCookiesTitle'] : 'Advertising';
		$advertising_cookies_description = isset( $attributes['advertisingCookiesDescription'] ) ? $attributes['advertisingCookiesDescription'] : 'Advertising cookies are used to provide visitors with relevant ads and marketing campaigns.';
		$tracking_cookies_title          = isset( $attributes['trackingCookiesTitle'] ) ? $attributes['trackingCookiesTitle'] : 'Tracking';
		$tracking_cookies_description    = isset( $attributes['trackingCookiesDescription'] ) ? $attributes['trackingCookiesDescription'] : 'Tracking cookies help us understand how you interact with our website and allow us to improve user experience.';

		// Enabled cookie types
		$enabled_cookie_types = isset( $attributes['enabledCookieTypes'] ) ? $attributes['enabledCookieTypes'] : array(
			'necessary'   => true,
			'functional'  => true,
			'analytical'  => true,
			'advertising' => true,
			'tracking'    => true,
		);

		// Layout settings
		$banner_position          = isset( $attributes['bannerPosition'] ) ? $attributes['bannerPosition'] : 'bottom';
		$floating_button_position = isset( $attributes['floatingButtonPosition'] ) ? $attributes['floatingButtonPosition'] : 'bottom-right';

		// Colors
		$primary_color    = isset( $attributes['primaryColor'] ) ? $attributes['primaryColor'] : '#0073aa';
		$text_color       = isset( $attributes['textColor'] ) ? $attributes['textColor'] : '#333333';
		$background_color = isset( $attributes['backgroundColor'] ) ? $attributes['backgroundColor'] : '#ffffff';

		// Cookie expiration
		$cookie_expiration = isset( $attributes['cookieExpiration'] ) ? $attributes['cookieExpiration'] : 365;

		// Prepare the config data to pass to JavaScript
		$config = array(
			'cookieExpiration'     => $cookie_expiration,
			'primaryColor'         => $primary_color,
			'textColor'            => $text_color,
			'backgroundColor'      => $background_color,
			'privacyPolicyVersion' => $privacy_policy_version,
			'termsVersion'         => $terms_version,
			'checkVersionChanges'  => $check_version_changes,
			'enabledCookieTypes'   => $enabled_cookie_types,
		);

		// Generate inline CSS for styling
		$inline_css = "
            #cookie-banner-{$block_id} .cookie-banner-container {
                background-color: {$background_color};
                color: {$text_color};
                border-top: 3px solid {$primary_color};
            }
            #cookie-banner-{$block_id} .cookie-banner-title {
                color: {$text_color};
            }
            #cookie-banner-{$block_id} .cookie-privacy-link a,
            #cookie-banner-{$block_id} .cookie-terms-link a {
                color: {$primary_color};
            }
            #cookie-banner-{$block_id} .cookie-action-button.primary {
                background-color: {$primary_color};
                border-color: {$primary_color};
            }
            #cookie-banner-{$block_id} .cookie-action-button.secondary {
                border-color: {$primary_color};
                color: {$primary_color};
            }
            #cookie-settings-button-{$block_id} {
                background-color: {$primary_color};
            }
            #cookie-customize-modal-{$block_id} .cookie-save-preferences {
                background-color: {$primary_color};
                border-color: {$primary_color};
            }
        ";

		// Start output buffer
		ob_start();

		// Output inline CSS
		echo '<style>' . $inline_css . '</style>';

		// Hidden configuration element
		echo '<script id="cookie-config-' . $block_id . '" class="cookie-config" type="application/json">' .
			wp_json_encode( $config ) .
		'</script>';

		// Main banner
		echo '<div id="cookie-banner-' . $block_id . '" class="wisesync-cookie-banner banner-' . esc_attr( $banner_position ) . '" data-block-id="' . esc_attr( $block_id ) . '" aria-hidden="true">';
		echo '<div class="cookie-banner-container">';

		// Banner content
		echo '<div class="cookie-banner-content">';
		echo '<h2 class="cookie-banner-title">' . esc_html( $banner_title ) . '</h2>';
		echo '<p class="cookie-banner-description">' . esc_html( $banner_description ) . '</p>';

		// Links section
		$has_links = ! empty( $privacy_policy_link ) || ! empty( $terms_link );
		if ( $has_links ) {
			echo '<div class="cookie-banner-links">';

			if ( ! empty( $privacy_policy_link ) ) {
				echo '<span class="cookie-privacy-link">';
				echo '<a href="' . esc_url( $privacy_policy_link ) . '" target="_blank">' . esc_html( $privacy_policy_text ) . '</a>';
				echo '</span>';
			}

			if ( ! empty( $privacy_policy_link ) && ! empty( $terms_link ) ) {
				echo '<span class="link-separator"> | </span>';
			}

			if ( ! empty( $terms_link ) ) {
				echo '<span class="cookie-terms-link">';
				echo '<a href="' . esc_url( $terms_link ) . '" target="_blank">' . esc_html( $terms_text ) . '</a>';
				echo '</span>';
			}

			echo '</div>';
		}

		echo '</div>'; // End banner content

		// Banner actions
		echo '<div class="cookie-banner-actions">';
		echo '<button class="cookie-action-button secondary cookie-customize">' . esc_html( $customize_text ) . '</button>';
		echo '<button class="cookie-action-button secondary cookie-reject-all">' . esc_html( $reject_all_text ) . '</button>';
		echo '<button class="cookie-action-button primary cookie-accept-all">' . esc_html( $accept_all_text ) . '</button>';
		echo '</div>'; // End banner actions

		echo '</div>'; // End banner container
		echo '</div>'; // End main banner

		// Floating settings button
		echo '<button id="cookie-settings-button-' . $block_id . '" class="cookie-settings-button ' . esc_attr( $floating_button_position ) . '" aria-hidden="true">';
		echo '<span class="dashicons dashicons-privacy" aria-hidden="true"></span>';
		echo '<span class="screen-reader-text">Cookie Settings</span>';
		echo '</button>';

		// Customize modal
		echo '<div id="cookie-customize-modal-' . $block_id . '" class="cookie-customize-modal" aria-hidden="true">';
		echo '<div class="modal-content" role="dialog" aria-labelledby="modal-title-' . $block_id . '">';

		// Modal header
		echo '<div class="modal-header">';
		echo '<h3 id="modal-title-' . $block_id . '">' . esc_html__( 'Cookie Settings', 'wisesync' ) . '</h3>';
		echo '<button class="cookie-modal-close" aria-label="' . esc_attr__( 'Close', 'wisesync' ) . '">&times;</button>';
		echo '</div>';

		// Modal body
		echo '<div class="modal-body">';

		// Necessary cookies
		echo '<div class="cookie-category necessary">';
		echo '<div class="cookie-category-header">';
		echo '<h4 class="cookie-category-title">' . esc_html( $necessary_cookies_title ) . '</h4>';
		echo '<label class="cookie-category-toggle">';
		echo '<input type="checkbox" id="cookie-necessary" checked disabled>';
		echo '</label>';
		echo '</div>';
		echo '<p class="cookie-category-description">' . esc_html( $necessary_cookies_description ) . '</p>';
		echo '</div>';

		// Functional cookies
		if ( $enabled_cookie_types['functional'] ) {
			echo '<div class="cookie-category">';
			echo '<div class="cookie-category-header">';
			echo '<h4 class="cookie-category-title">' . esc_html( $functional_cookies_title ) . '</h4>';
			echo '<label class="cookie-category-toggle">';
			echo '<input type="checkbox" id="cookie-functional">';
			echo '</label>';
			echo '</div>';
			echo '<p class="cookie-category-description">' . esc_html( $functional_cookies_description ) . '</p>';
			echo '</div>';
		}

		// Analytical cookies
		if ( $enabled_cookie_types['analytical'] ) {
			echo '<div class="cookie-category">';
			echo '<div class="cookie-category-header">';
			echo '<h4 class="cookie-category-title">' . esc_html( $analytical_cookies_title ) . '</h4>';
			echo '<label class="cookie-category-toggle">';
			echo '<input type="checkbox" id="cookie-analytical">';
			echo '</label>';
			echo '</div>';
			echo '<p class="cookie-category-description">' . esc_html( $analytical_cookies_description ) . '</p>';
			echo '</div>';
		}

		// Advertising cookies
		if ( $enabled_cookie_types['advertising'] ) {
			echo '<div class="cookie-category">';
			echo '<div class="cookie-category-header">';
			echo '<h4 class="cookie-category-title">' . esc_html( $advertising_cookies_title ) . '</h4>';
			echo '<label class="cookie-category-toggle">';
			echo '<input type="checkbox" id="cookie-advertising">';
			echo '</label>';
			echo '</div>';
			echo '<p class="cookie-category-description">' . esc_html( $advertising_cookies_description ) . '</p>';
			echo '</div>';
		}

		// Tracking cookies
		if ( $enabled_cookie_types['tracking'] ) {
			echo '<div class="cookie-category">';
			echo '<div class="cookie-category-header">';
			echo '<h4 class="cookie-category-title">' . esc_html( $tracking_cookies_title ) . '</h4>';
			echo '<label class="cookie-category-toggle">';
			echo '<input type="checkbox" id="cookie-tracking">';
			echo '</label>';
			echo '</div>';
			echo '<p class="cookie-category-description">' . esc_html( $tracking_cookies_description ) . '</p>';
			echo '</div>';
		}

		echo '</div>'; // End modal body

		// Modal footer
		echo '<div class="modal-footer">';
		echo '<button class="cookie-save-preferences">' . esc_html( $save_preferences_text ) . '</button>';
		echo '</div>';

		echo '</div>'; // End modal content
		echo '</div>'; // End customize modal

		return ob_get_clean();
	}

	/**
	 * Check if the current block is being rendered in a site editor template
	 */
	private function is_site_editor_template() {
		// Check for template or template part post types
		// This function will return true when the block is used in site editor templates
		// but false when used in individual posts/pages
		global $post;

		if ( $post && in_array( $post->post_type, array( 'wp_template', 'wp_template_part' ) ) ) {
			return true;
		}

		// FSE template rendering
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen && in_array( $screen->id, array( 'site-editor', 'appearance_page_gutenberg-edit-site' ) ) ) {
				return true;
			}
		}

		// Check if we're in template viewing context
		$is_template_mode = isset( $_GET['template-editing'] ) || isset( $_GET['postId'] ) && strpos( $_GET['postId'], 'theme' ) === 0;

		return $is_template_mode;
	}
}
