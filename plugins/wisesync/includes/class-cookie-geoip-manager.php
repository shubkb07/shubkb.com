<?php
/**
 * Cookie GeoIP Manager
 *
 * @package WISESYNC
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class to handle GeoIP functionality including US county detection
 */
class WSYNC_Cookie_GeoIP_Manager {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_filter( 'wisesync_cookie_banner_config', array( $this, 'apply_geoip_rules' ) );
	}

	/**
	 * Enqueue scripts and localize GeoIP data
	 */
	public function enqueue_scripts() {
		$geo_data = $this->get_visitor_geo_data();

		// Pass GeoIP data to JavaScript
		wp_localize_script( 'wisesync-cookie-banner', 'visitorGeoData', $geo_data );
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_rest_routes() {
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
	 * Get cookie banner configuration via REST API
	 *
	 * @return WP_REST_Response
	 */
	public function get_cookie_banner_config() {
		$config = $this->apply_geoip_rules( array() );
		return rest_ensure_response( $config );
	}

	/**
	 * Apply GeoIP rules to cookie banner configuration
	 *
	 * @param array $config Existing configuration
	 * @return array Modified configuration
	 */
	public function apply_geoip_rules( $config ) {
		$geo_data = $this->get_visitor_geo_data();

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

		// Merge with any existing config
		$config = wp_parse_args( $config, $default_config );

		// Apply country-specific rules
		$country_code = $geo_data['countryCode'];

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
                $config['bannerText']      = 'This site uses cookies for analytics and marketing. Please accept or customize your settings.';
                $config['expirationDays']  = 180;
                $config['requiredConsent'] = 'opt-in';
                break;

            // United Kingdom
            case 'GB':
                // UK GDPR + PECR
                $config['bannerText']      = 'This site uses cookies for analytics and marketing. Under UK GDPR, please accept or customize your settings.';
                $config['expirationDays']  = 180;
                $config['requiredConsent'] = 'opt-in';
                break;

            // United States
            case 'US':
                // Default US config (less strict than GDPR)
                $config['bannerText']        = 'We use cookies to enhance your experience. By continuing to browse, you agree to our use of cookies.';
                $config['requiredConsent']   = 'opt-out';
                $config['expirationDays']    = 365;
                $config['showDoNotSellLink'] = true;

                // Check for California
                if ( $this->is_california( $geo_data ) ) {
                    $config['bannerText']        = 'This site uses cookies. Under the CCPA/CPRA, California residents have specific rights regarding personal information.';
                    $config['requiredConsent']   = 'opt-out';
                    $config['expirationDays']    = 365;
                    $config['showDoNotSellLink'] = true;
                }

                // Check for Colorado, Connecticut, Virginia, Utah or other states with specific privacy laws
                if ( $this->is_privacy_law_state( $geo_data ) ) {
                    $config['showDoNotSellLink'] = true;
                    $config['requiredConsent']   = 'opt-out';
                }
                break;

            // Canada
            case 'CA':
                // PIPEDA
                $config['bannerText']      = 'This site uses cookies. Under PIPEDA, your continued use implies consent, but you can customize your preferences.';
                $config['requiredConsent'] = 'implied';
                $config['expirationDays']  = 365;
                break;

            // Brazil
            case 'BR':
                // LGPD
                $config['bannerText']      = 'This site uses cookies. Under LGPD, we need your consent for non-essential cookies.';
                $config['requiredConsent'] = 'opt-in';
                $config['expirationDays']  = 365;
                break;

            // Japan
            case 'JP':
                // APPI
                $config['bannerText']      = 'This site uses cookies. Under APPI, we need your consent for third-party cookies.';
                $config['requiredConsent'] = 'opt-in';
                $config['expirationDays']  = 365;
                break;

            // Singapore
            case 'SG':
                // PDPA
                $config['bannerText']      = 'This site uses cookies. Under PDPA, your continued use implies consent.';
                $config['requiredConsent'] = 'implied';
                $config['expirationDays']  = 365;
                break;

            // Australia
            case 'AU':
                // Privacy Act
                $config['bannerText']      = 'This site uses cookies. Under the Privacy Act, your continued use implies consent.';
                $config['requiredConsent'] = 'implied';
                $config['expirationDays']  = 365;
                break;

            // India
            case 'IN':
                // DPDP Act
                $config['bannerText']      = 'This site uses cookies. Under the DPDP Act, we need your consent for non-essential cookies.';
                $config['requiredConsent'] = 'opt-in';
                $config['expirationDays']  = 365;
                break;

            // China
            case 'CN':
                // PIPL
                $config['bannerText']      = 'This site uses cookies. Under PIPL, we need your explicit consent for all data processing.';
                $config['requiredConsent'] = 'opt-in';
                $config['expirationDays']  = 180;
                break;

            // South Korea
            case 'KR':
                // PIPA
                $config['bannerText']      = 'This site uses cookies. Under PIPA, we need your explicit consent for data processing.';
                $config['requiredConsent'] = 'opt-in';
                $config['expirationDays']  = 180;
                break;

            // South Africa
            case 'ZA':
                // POPIA
                $config['bannerText']      = 'This site uses cookies. Under POPIA, we need your consent for non-essential cookies.';
                $config['requiredConsent'] = 'opt-in';
                $config['expirationDays']  = 365;
                break;

            // United Arab Emirates / Saudi Arabia
            case 'AE':
            case 'SA':
                // UAE PDPL / Saudi PDPL
                $config['bannerText']      = 'This site uses cookies. Under local data protection law, we need your consent for personal data processing.';
                $config['requiredConsent'] = 'opt-in';
                $config['expirationDays']  = 365;
                break;

            // Default for all other countries
            default:
                // Best practice: opt-in for all non-essential cookies
                $config['bannerText']      = 'This site uses cookies to enhance your experience. Please let us know which cookies we can store.';
                $config['requiredConsent'] = 'opt-in';
                $config['expirationDays']  = 365;
                break;
        }

		// Add country information
		$config['visitorCountryCode'] = $geo_data['countryCode'];
		$config['visitorCountryName'] = $geo_data['countryName'];

		return $config;
	}

	/**
	 * Get visitor geo data including country and US county if available
	 *
	 * @return array Geo data
	 */
	public function get_visitor_geo_data() {
		$country_code = isset( $_SERVER['GEOIP_COUNTRY_CODE'] ) ? $_SERVER['GEOIP_COUNTRY_CODE'] : '';
		$country_name = isset( $_SERVER['GEOIP_COUNTRY_NAME'] ) ? $_SERVER['GEOIP_COUNTRY_NAME'] : '';
		$region_code  = isset( $_SERVER['GEOIP_REGION'] ) ? $_SERVER['GEOIP_REGION'] : '';
		$region_name  = isset( $_SERVER['GEOIP_REGION_NAME'] ) ? $_SERVER['GEOIP_REGION_NAME'] : '';
		$city        = isset( $_SERVER['GEOIP_CITY'] ) ? $_SERVER['GEOIP_CITY'] : '';
		$postal_code = isset( $_SERVER['GEOIP_POSTAL_CODE'] ) ? $_SERVER['GEOIP_POSTAL_CODE'] : '';
		
		return array(
			'countryCode' => $country_code,
			'countryName' => $country_name,
			'regionCode'  => $region_code,
			'regionName'  => $region_name,
			'city'        => $city,
			'postalCode'  => $postal_code,
		);
	}

	/**
	 * Check if user is from California
	 *
	 * @param array $geo_data Geo data
	 * @return boolean True if from California
	 */
	public function is_california( $geo_data ) {
		return ( $geo_data['countryCode'] === 'US' && $geo_data['regionCode'] === 'CA' );
	}

	/**
	 * Check if user is from a US state with specific privacy laws
	 * (Colorado, Connecticut, Virginia, Utah, etc.)
	 *
	 * @param array $geo_data Geo data
	 * @return boolean True if from a privacy law state
	 */
	public function is_privacy_law_state( $geo_data ) {
		if ( $geo_data['countryCode'] !== 'US' ) {
			return false;
		}

		$privacy_law_states = array( 'CA', 'CO', 'CT', 'VA', 'UT' );
		return in_array( $geo_data['regionCode'], $privacy_law_states, true );
	}

	/**
	 * Check if country is subject to GDPR
	 *
	 * @param string $country_code Country code
	 * @return boolean True if subject to GDPR
	 */
	public function is_gdpr_country( $country_code ) {
		$gdpr_countries = array(
			'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
			'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
			'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'NO', 'IS',
			'LI',
		);
		
		return in_array( $country_code, $gdpr_countries, true );
	}
}
