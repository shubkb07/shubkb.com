<?php
/**
 * WiseSync Cookie Banner Block
 *
 * @package Wisesync
 * @subpackage Blocks
 * @since 1.0.0
 */

/**
 * Server-side rendering of the Cookie Banner block.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the cookie banner HTML.
 */
function render_cookie_banner_block( $attributes, $content, $block ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	// Get attributes with defaults.
	$banner_title       = isset( $attributes['bannerTitle'] ) ? $attributes['bannerTitle'] : __( 'Cookie Consent', 'wisesync' );
	$banner_description = isset( $attributes['bannerDescription'] ) ? $attributes['bannerDescription'] : __( 'We use cookies to enhance your browsing experience, serve personalized ads or content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.', 'wisesync' );

	// Add unique ID to ensure this block only activates once per page even if it appears multiple times.
	$unique_id = 'wisesync-cookie-banner-' . uniqid();

	// Build the HTML output.
	$output = sprintf(
		'<div id="%1$s" class="wisesync-cookie-banner">
            <div class="wisesync-cookie-banner-container">
                <h3 class="wisesync-cookie-banner-title">%2$s</h3>
                <div class="wisesync-cookie-banner-content">
                    <p>%3$s</p>
                </div>
                <div class="wisesync-cookie-banner-actions">
                    <button class="wisesync-cookie-accept-all">%4$s</button>
                    <button class="wisesync-cookie-accept-necessary">%5$s</button>
                </div>
            </div>
        </div>',
		( $unique_id ),
		esc_html( $banner_title ),
		esc_html ($banner_description ),
		esc_html__( 'Accept All', 'wisesync' ),
		esc_html__( 'Accept Necessary Only', 'wisesync' )
	);

	return $output;
}
