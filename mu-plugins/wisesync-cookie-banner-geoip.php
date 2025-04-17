<?php
/**
 * Plugin Name: WiseSync Cookie Banner GeoIP Configuration
 * Description: Dynamically configures cookie banner settings based on visitor's GeoIP location
 * Version: 1.0.0
 * Author: WiseSync
 * Text Domain: wisesync-cookie-banner
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle the GeoIP-based cookie banner configuration
 */
class WiseSync_Cookie_Banner_GeoIP {
	/**
	 * Initialize the hooks
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'init', array( $this, 'register_cookie_banner_config' ) );
		add_filter( 'wisesync_cookie_banner_config', array( $this, 'apply_geoip_rules' ) );
	}

	/**
	 * Add GeoIP data to the frontend
	 */
	public function enqueue_scripts() {
		$country_code = $this->get_visitor_country_code();
		$country_name = $this->get_visitor_country_name();

		// Pass GeoIP data to JavaScript
		wp_localize_script(
			'wisesync-cookie-banner-view',
			'visitorGeoData',
			array(
				'countryCode' => $country_code,
				'countryName' => $country_name,
			)
		);
	}

	/**
	 * Register a custom REST API endpoint for the cookie banner configuration
	 */
	public function register_cookie_banner_config() {
		register_rest_route(
			'wisesync/v1',
			'/cookie-banner-config',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_cookie_banner_config' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get the cookie banner configuration based on the visitor's country
	 *
	 * @return WP_REST_Response
	 */
	public function get_cookie_banner_config() {
		$config = $this->apply_geoip_rules( array() );
		return rest_ensure_response( $config );
	}

	/**
	 * Apply GeoIP rules to the cookie banner configuration
	 *
	 * @param array $config Existing configuration
	 * @return array Modified configuration
	 */
	public function apply_geoip_rules( $config ) {
		$country_code = $this->get_visitor_country_code();
		$country_name = $this->get_visitor_country_name();

		// Set default configuration
		$default_config = array(
			'bannerTitle'               => 'Cookie Consent',
			'bannerText'                => 'This site uses cookies to enhance your experience. Please accept or customize your settings.',
			'necessaryCookiesText'      => 'Strictly necessary cookies - These are required for the website to function and cannot be disabled.',
			'functionalCookiesText'     => 'Functional cookies - These enable enhanced functionality and personalization.',
			'analyticalCookiesText'     => 'Analytical cookies - These help us improve our website by collecting anonymous information.',
			'advertisingCookiesText'    => 'Advertising cookies - These are used to show you relevant ads on other websites.',
			'trackingCookiesText'       => 'Tracking cookies - These track your online activity to help advertisers deliver more relevant advertising.',
			'acceptAllButtonText'       => 'Accept All',
			'rejectAllButtonText'       => 'Reject All',
			'customizeButtonText'       => 'Customize Settings',
			'savePreferencesButtonText' => 'Save Preferences',
			'privacyPolicyText'         => 'Privacy Policy',
			'privacyPolicyURL'          => '/privacy-policy/',
			'cookiePolicyText'          => 'Cookie Policy',
			'cookiePolicyURL'           => '/cookie-policy/',
			'expirationDays'            => 365, // Default
			'requiredConsent'           => 'opt-in', // Default to opt-in
			'showDoNotSellLink'         => false,
			'doNotSellLinkText'         => 'Do Not Sell or Share My Personal Information',
			'doNotSellLinkURL'          => '/do-not-sell-my-info/',
			'privacyPolicyVersion'      => '1.0.0',
			'termsVersion'              => '1.0.0',
		);

		// Country-specific configurations
		switch ( $country_code ) {
			// EU Countries
			case 'AT':
			case 'BE':
			case 'BG':
			case 'HR':
			case 'CY':
			case 'CZ':
			case 'DK':
			case 'EE':
			case 'FI':
			case 'FR':
			case 'DE':
			case 'GR':
			case 'HU':
			case 'IE':
			case 'IT':
			case 'LV':
			case 'LT':
			case 'LU':
			case 'MT':
			case 'NL':
			case 'PL':
			case 'PT':
			case 'RO':
			case 'SK':
			case 'SI':
			case 'ES':
			case 'SE':
																							// GDPR + ePrivacy Directive
																							$default_config['bannerText']      = 'This site uses cookies for analytics and marketing. Please accept or customize your settings.';
																							$default_config['expirationDays']  = 180;
																							$default_config['requiredConsent'] = 'opt-in';
				break;

			// United Kingdom
			case 'GB':
				// UK GDPR + PECR
				$default_config['bannerText']      = 'This site uses cookies for analytics and marketing. Under UK GDPR, please accept or customize your settings.';
				$default_config['expirationDays']  = 180;
				$default_config['requiredConsent'] = 'opt-in';
				break;

			// United States
			case 'US':
				// CCPA/CPRA
				$default_config['bannerText']        = 'We use cookies to enhance your experience. By continuing to browse, you agree to our use of cookies.';
				$default_config['requiredConsent']   = 'opt-out';
				$default_config['expirationDays']    = 365;
				$default_config['showDoNotSellLink'] = true;
				break;

			// California specifically
			case 'CA_US': // This would require additional logic to detect California specifically
				$default_config['bannerText']        = 'This site uses cookies. Under the CCPA/CPRA, California residents have specific rights regarding personal information.';
				$default_config['requiredConsent']   = 'opt-out';
				$default_config['expirationDays']    = 365;
				$default_config['showDoNotSellLink'] = true;
				break;

			// Canada
			case 'CA':
				// PIPEDA
				$default_config['bannerText']      = 'This site uses cookies. Under PIPEDA, your continued use implies consent, but you can customize your preferences.';
				$default_config['requiredConsent'] = 'implied';
				$default_config['expirationDays']  = 365;
				break;

			// Brazil
			case 'BR':
				// LGPD
				$default_config['bannerText']      = 'This site uses cookies. Under LGPD, we need your consent for non-essential cookies.';
				$default_config['requiredConsent'] = 'opt-in';
				$default_config['expirationDays']  = 365;
				break;

			// Japan
			case 'JP':
				// APPI
				$default_config['bannerText']      = 'This site uses cookies. Under APPI, we need your consent for third-party cookies.';
				$default_config['requiredConsent'] = 'opt-in';
				$default_config['expirationDays']  = 365;
				break;

			// Singapore
			case 'SG':
				// PDPA
				$default_config['bannerText']      = 'This site uses cookies. Under PDPA, your continued use implies consent.';
				$default_config['requiredConsent'] = 'implied';
				$default_config['expirationDays']  = 365;
				break;

			// Australia
			case 'AU':
				// Privacy Act
				$default_config['bannerText']      = 'This site uses cookies. Under the Privacy Act, your continued use implies consent.';
				$default_config['requiredConsent'] = 'implied';
				$default_config['expirationDays']  = 365;
				break;

			// India
			case 'IN':
				// DPDP Act
				$default_config['bannerText']      = 'This site uses cookies. Under the DPDP Act, we need your consent for non-essential cookies.';
				$default_config['requiredConsent'] = 'opt-in';
				$default_config['expirationDays']  = 365;
				break;

			// China
			case 'CN':
				// PIPL
				$default_config['bannerText']      = 'This site uses cookies. Under PIPL, we need your explicit consent for all data processing.';
				$default_config['requiredConsent'] = 'opt-in';
				$default_config['expirationDays']  = 180;
				break;

			// South Korea
			case 'KR':
				// PIPA
				$default_config['bannerText']      = 'This site uses cookies. Under PIPA, we need your explicit consent for data processing.';
				$default_config['requiredConsent'] = 'opt-in';
				$default_config['expirationDays']  = 180;
				break;

			// South Africa
			case 'ZA':
				// POPIA
				$default_config['bannerText']      = 'This site uses cookies. Under POPIA, we need your consent for non-essential cookies.';
				$default_config['requiredConsent'] = 'opt-in';
				$default_config['expirationDays']  = 365;
				break;

			// United Arab Emirates / Saudi Arabia
			case 'AE':
			case 'SA':
				// UAE PDPL / Saudi PDPL
				$default_config['bannerText']      = 'This site uses cookies. Under local data protection law, we need your consent for personal data processing.';
				$default_config['requiredConsent'] = 'opt-in';
				$default_config['expirationDays']  = 365;
				break;

			// Default for all other countries
			default:
				// Best practice: opt-in for all non-essential cookies
				$default_config['bannerText']      = 'This site uses cookies to enhance your experience. Please let us know which cookies we can store.';
				$default_config['requiredConsent'] = 'opt-in';
				$default_config['expirationDays']  = 365;
				break;
		}

		// Merge with any existing configuration, with country-specific values taking precedence
		$final_config = array_merge( $config, $default_config );

		// Add country information
		$final_config['visitorCountryCode'] = $country_code;
		$final_config['visitorCountryName'] = $country_name;

		return $final_config;
	}

	/**
	 * Get the visitor's country code from GeoIP
	 *
	 * @return string Country code or empty string if not available
	 */
	public function get_visitor_country_code() {
		return isset( $_SERVER['GEOIP_COUNTRY_CODE'] ) ? $_SERVER['GEOIP_COUNTRY_CODE'] : '';
	}

	/**
	 * Get the visitor's country name from GeoIP
	 *
	 * @return string Country name or empty string if not available
	 */
	public function get_visitor_country_name() {
		return isset( $_SERVER['GEOIP_COUNTRY_NAME'] ) ? $_SERVER['GEOIP_COUNTRY_NAME'] : '';
	}

	/**
	 * Get instance of the class
	 *
	 * @return WiseSync_Cookie_Banner_GeoIP
	 */
	public static function get_instance() {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new self();
		}
		return $instance;
	}
}

// Initialize the class
WiseSync_Cookie_Banner_GeoIP::get_instance();
