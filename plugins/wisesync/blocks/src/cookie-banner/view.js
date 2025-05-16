/**
 * Cookie Banner Frontend Script
 *
 * This script handles the frontend functionality for the Cookie Banner block.
 * It manages cookie storage, consent management, service registration, and UI interactions.
 */

// Cookie utility functions
const CookieUtils = {
	setCookie( name, value, days ) {
		const date = new Date();
		date.setTime( date.getTime() + ( days * 24 * 60 * 60 * 1000 ) );
		const expires = 'expires=' + date.toUTCString();
		document.cookie = name + '=' + value + ';' + expires + ';path=/;SameSite=Lax';
	},

	getCookie( name ) {
		const nameEQ = name + '=';
		const ca = document.cookie.split( ';' );
		for ( let i = 0; i < ca.length; i++ ) {
			let c = ca[ i ];
			while ( ' ' === c.charAt( 0 ) ) {
				c = c.substring( 1, c.length );
			}
			if ( 0 === c.indexOf( nameEQ ) ) {
				return c.substring( nameEQ.length, c.length );
			}
		}
		return null;
	},

	deleteCookie( name ) {
		document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;SameSite=Lax';
	},
};

// Global WiseSync Cookie namespace
window.WiseSyncCookies = {
	registeredServices: {},
	callbacks: {
		necessary: [],
		functional: [],
		analytical: [],
		advertising: [],
		tracking: [],
	},
	versions: {
		privacy: null,
		terms: null,
	},
};

/**
 * Check if a specific cookie type permission is granted
 *
 * @param {string} permission - Permission type to check ('necessary', 'functional', 'analytical', 'advertising', 'tracking')
 * @return {boolean} - Whether the permission is granted
 */
window.is_sync_cookie_permission_to = function( permission ) {
	// Necessary cookies always return true
	if ( 'necessary' === permission ) {
		return true;
	}

	// Check if the permission exists in our allowed types
	const allowedTypes = [ 'functional', 'analytical', 'advertising', 'tracking' ];
	if ( ! allowedTypes.includes( permission ) ) {
		console.warn( `Cookie permission type "${ permission }" is not recognized` );
		return false;
	}

	// Get consent data from cookie
	const consentCookie = CookieUtils.getCookie( 'wisesync_cookie_consent' );
	if ( ! consentCookie ) {
		return false;
	}

	try {
		const consentData = JSON.parse( consentCookie );
		return true === consentData[ permission ];
	} catch ( e ) {
		console.error( 'Error parsing cookie consent data:', e );
		return false;
	}
};

/**
 * Register a cookie service
 *
 * @param {string} service - Service slug (e.g., 'ga4', 'gtm')
 * @param {string} name    - Service display name (e.g., 'Google Analytics', 'Google Tag Manager')
 * @param {string} type    - Service type ('functional', 'analytical', 'advertising', 'tracking')
 */
window.register_cookie_service = function( service, name, type ) {
	// Validate service type
	const validTypes = [ 'functional', 'analytical', 'advertising', 'tracking' ];
	if ( ! validTypes.includes( type ) ) {
		console.error( `Invalid cookie service type: ${ type }. Must be one of: ${ validTypes.join( ', ' ) }` );
		return;
	}

	// Register the service
	window.WiseSyncCookies.registeredServices[ service ] = {
		name,
		type,
		registered: new Date().toISOString(),
	};

	// Check if we should activate this service based on current consent
	if ( window.is_sync_cookie_permission_to( type ) ) {
		// Trigger any callbacks for this service type
		triggerServiceTypeCallbacks( type );
	}
};

/**
 * Trigger callbacks for a specific service type
 *
 * @param {string} type - Service type
 */
function triggerServiceTypeCallbacks( type ) {
	if ( Array.isArray( window.WiseSyncCookies.callbacks[ type ] ) ) {
		window.WiseSyncCookies.callbacks[ type ].forEach( ( callback ) => {
			if ( 'function' === typeof callback ) {
				callback();
			}
		} );
	}
}

// CookieConsent class to handle all consent functionality
class CookieConsent {
	constructor( blockId, config ) {
		this.blockId = blockId;
		this.config = config;
		this.cookieName = 'wisesync_cookie_consent';
		this.cookieExpiration = config.cookieExpiration || 365;
		this.isCustomizing = false;
		this.consentStatus = this.getConsentStatus();

		this.elements = {
			banner: document.getElementById( 'cookie-banner-' + blockId ),
			floatingButton: document.getElementById( 'cookie-settings-button-' + blockId ),
			customizeModal: document.getElementById( 'cookie-customize-modal-' + blockId ),
		};

		// Check if policy versions have changed
		this.policyVersionsChanged = this.checkPolicyVersions();

		this.init();
	}

	init() {
		// Show banner if consent not given or policy versions changed
		if ( ! this.hasConsent() || this.policyVersionsChanged ) {
			this.showBanner();
		} else {
			this.hideBanner();
			this.showFloatingButton();

			// Execute callbacks for already accepted consent types
			this.executeCallbacks( this.consentStatus );
		}

		this.attachEventListeners();

		// Store the current versions
		this.storeCurrentPolicyVersions();
	}

	attachEventListeners() {
		// Main banner buttons
		const acceptAllBtn = this.elements.banner.querySelector( '.cookie-accept-all' );
		const rejectAllBtn = this.elements.banner.querySelector( '.cookie-reject-all' );
		const customizeBtn = this.elements.banner.querySelector( '.cookie-customize' );

		if ( acceptAllBtn ) {
			acceptAllBtn.addEventListener( 'click', () => this.acceptAll() );
		}

		if ( rejectAllBtn ) {
			rejectAllBtn.addEventListener( 'click', () => this.rejectAll() );
		}

		if ( customizeBtn ) {
			customizeBtn.addEventListener( 'click', () => this.openCustomizeModal() );
		}

		// Floating button
		if ( this.elements.floatingButton ) {
			this.elements.floatingButton.addEventListener( 'click', () => this.openCustomizeModal() );
		}

		// Customize modal
		const savePrefsBtn = this.elements.customizeModal
			? this.elements.customizeModal.querySelector( '.cookie-save-preferences' ) : null;

		if ( savePrefsBtn ) {
			savePrefsBtn.addEventListener( 'click', () => this.savePreferences() );
		}

		// Close modal button and backdrop
		const closeModalBtn = this.elements.customizeModal
			? this.elements.customizeModal.querySelector( '.cookie-modal-close' ) : null;

		if ( closeModalBtn ) {
			closeModalBtn.addEventListener( 'click', () => this.closeCustomizeModal() );
		}

		if ( this.elements.customizeModal ) {
			document.addEventListener( 'click', ( event ) => {
				if ( event.target === this.elements.customizeModal ) {
					this.closeCustomizeModal();
				}
			} );
		}

		// Handle escape key
		document.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key && this.isCustomizing ) {
				this.closeCustomizeModal();
			}
		} );
	}

	hasConsent() {
		const consent = CookieUtils.getCookie( this.cookieName );
		return null !== consent;
	}

	getConsentStatus() {
		const consent = CookieUtils.getCookie( this.cookieName );
		if ( ! consent ) {
			return {
				necessary: true,
				functional: false,
				analytical: false,
				advertising: false,
				tracking: false,
			};
		}

		try {
			return JSON.parse( consent );
		} catch ( e ) {
			return {
				necessary: true,
				functional: false,
				analytical: false,
				advertising: false,
				tracking: false,
			};
		}
	}

	checkPolicyVersions() {
		// Skip check if feature is disabled or no consent yet
		if ( ! this.config.checkVersionChanges || ! this.hasConsent() ) {
			return false;
		}

		const versionsCookie = CookieUtils.getCookie( 'wisesync_policy_versions' );
		if ( ! versionsCookie ) {
			return false;
		}

		try {
			const storedVersions = JSON.parse( versionsCookie );

			// Check if privacy policy version changed
			if ( this.config.privacyPolicyVersion &&
                storedVersions.privacy &&
                storedVersions.privacy !== this.config.privacyPolicyVersion ) {
				return true;
			}

			// Check if terms version changed
			if ( this.config.termsVersion &&
                storedVersions.terms &&
                storedVersions.terms !== this.config.termsVersion ) {
				return true;
			}

			return false;
		} catch ( e ) {
			return false;
		}
	}

	storeCurrentPolicyVersions() {
		if ( ! this.config.checkVersionChanges ) {
			return;
		}

		const versions = {
			privacy: this.config.privacyPolicyVersion || null,
			terms: this.config.termsVersion || null,
		};

		CookieUtils.setCookie( 'wisesync_policy_versions', JSON.stringify( versions ), this.cookieExpiration );

		// Update global version information
		window.WiseSyncCookies.versions = versions;
	}

	acceptAll() {
		const consentData = {
			necessary: true,
			functional: this.config.enabledCookieTypes?.functional ?? true,
			analytical: this.config.enabledCookieTypes?.analytical ?? true,
			advertising: this.config.enabledCookieTypes?.advertising ?? true,
			tracking: this.config.enabledCookieTypes?.tracking ?? true,
		};

		this.saveConsentStatus( consentData );
		this.hideBanner();
		this.showFloatingButton();
		this.executeCallbacks( consentData );
	}

	rejectAll() {
		const consentData = {
			necessary: true, // Necessary cookies can't be rejected
			functional: false,
			analytical: false,
			advertising: false,
			tracking: false,
		};

		this.saveConsentStatus( consentData );
		this.hideBanner();
		this.showFloatingButton();
		this.executeCallbacks( consentData );
	}

	savePreferences() {
		const consentData = {
			necessary: true, // Always true
			functional: this.elements.customizeModal.querySelector( '#cookie-functional' )?.checked ?? false,
			analytical: this.elements.customizeModal.querySelector( '#cookie-analytical' )?.checked ?? false,
			advertising: this.elements.customizeModal.querySelector( '#cookie-advertising' )?.checked ?? false,
			tracking: this.elements.customizeModal.querySelector( '#cookie-tracking' )?.checked ?? false,
		};

		this.saveConsentStatus( consentData );
		this.hideBanner();
		this.closeCustomizeModal();
		this.showFloatingButton();
		this.executeCallbacks( consentData );
	}

	saveConsentStatus( consentData ) {
		const consentString = JSON.stringify( consentData );
		CookieUtils.setCookie( this.cookieName, consentString, this.cookieExpiration );
		this.consentStatus = consentData;

		// Dispatch event that consent has been updated
		const event = new CustomEvent( 'cookieConsentUpdated', {
			detail: {
				consent: consentData,
				blockId: this.blockId,
				services: window.WiseSyncCookies.registeredServices,
			},
		} );
		document.dispatchEvent( event );
	}

	showBanner() {
		if ( this.elements.banner ) {
			this.elements.banner.classList.add( 'visible' );
			this.elements.banner.setAttribute( 'aria-hidden', 'false' );
		}
	}

	hideBanner() {
		if ( this.elements.banner ) {
			this.elements.banner.classList.remove( 'visible' );
			this.elements.banner.setAttribute( 'aria-hidden', 'true' );
		}
	}

	showFloatingButton() {
		if ( this.elements.floatingButton ) {
			setTimeout( () => {
				this.elements.floatingButton.classList.add( 'visible' );
				this.elements.floatingButton.setAttribute( 'aria-hidden', 'false' );
			}, 1000 );
		}
	}

	openCustomizeModal() {
		if ( this.elements.customizeModal ) {
			// Update checkbox states based on current consent
			const functionalCheckbox = this.elements.customizeModal.querySelector( '#cookie-functional' );
			const analyticalCheckbox = this.elements.customizeModal.querySelector( '#cookie-analytical' );
			const advertisingCheckbox = this.elements.customizeModal.querySelector( '#cookie-advertising' );
			const trackingCheckbox = this.elements.customizeModal.querySelector( '#cookie-tracking' );

			if ( functionalCheckbox ) {
				functionalCheckbox.checked = this.consentStatus.functional;
			}
			if ( analyticalCheckbox ) {
				analyticalCheckbox.checked = this.consentStatus.analytical;
			}
			if ( advertisingCheckbox ) {
				advertisingCheckbox.checked = this.consentStatus.advertising;
			}
			if ( trackingCheckbox ) {
				trackingCheckbox.checked = this.consentStatus.tracking;
			}

			// Show modal
			this.elements.customizeModal.classList.add( 'visible' );
			this.elements.customizeModal.setAttribute( 'aria-hidden', 'false' );
			this.isCustomizing = true;

			// Hide banner if visible
			this.hideBanner();

			// Focus first interactive element
			const firstInput = this.elements.customizeModal.querySelector( 'input[type="checkbox"]' );
			if ( firstInput ) {
				firstInput.focus();
			}
		}
	}

	closeCustomizeModal() {
		if ( this.elements.customizeModal ) {
			this.elements.customizeModal.classList.remove( 'visible' );
			this.elements.customizeModal.setAttribute( 'aria-hidden', 'true' );
			this.isCustomizing = false;
		}
	}

	executeCallbacks( consentData ) {
		// Trigger callbacks based on consent settings
		Object.keys( consentData ).forEach( ( category ) => {
			if ( true === consentData[ category ] &&
                Array.isArray( window.WiseSyncCookies.callbacks[ category ] ) ) {
				window.WiseSyncCookies.callbacks[ category ].forEach( ( callback ) => {
					if ( 'function' === typeof callback ) {
						callback();
					}
				} );
			}
		} );

		// Check for registered services that can be activated
		Object.keys( window.WiseSyncCookies.registeredServices ).forEach( ( serviceKey ) => {
			const service = window.WiseSyncCookies.registeredServices[ serviceKey ];
			if ( true === consentData[ service.type ] ) {
				// This service's type has consent
				const serviceEvent = new CustomEvent( 'cookieServiceActivated', {
					detail: {
						service: serviceKey,
						name: service.name,
						type: service.type,
					},
				} );
				document.dispatchEvent( serviceEvent );
			}
		} );
	}
}

// Initialize the cookie banner when DOM is ready
document.addEventListener( 'DOMContentLoaded', () => {
	const cookieBanners = document.querySelectorAll( '.wisesync-cookie-banner' );

	cookieBanners.forEach( ( banner ) => {
		const blockId = banner.dataset.blockId;
		const configElement = document.getElementById( 'cookie-config-' + blockId );

		if ( configElement ) {
			try {
				const config = JSON.parse( configElement.textContent );
				new CookieConsent( blockId, config );
			} catch ( e ) {
				console.error( 'Error parsing cookie banner config:', e );
			}
		}
	} );

	// Create helper function for scripts to register callbacks
	window.registerCookieCallback = function( category, callback ) {
		if ( 'undefined' !== typeof window.WiseSyncCookies.callbacks[ category ] &&
            'function' === typeof callback ) {
			window.WiseSyncCookies.callbacks[ category ].push( callback );

			// Check if consent already given and execute immediately if so
			if ( window.is_sync_cookie_permission_to( category ) ) {
				callback();
			}
		}
	};
} );

/**
 * Cookie Banner Frontend Script
 */

document.addEventListener( 'DOMContentLoaded', () => {
	initCookieBanner();
} );

/**
 * Initialize the cookie banner functionality
 */
function initCookieBanner() {
	const banners = document.querySelectorAll( '.wp-block-sync-cookie-banner' );

	if ( ! banners.length ) {
		return;
	}

	banners.forEach( ( banner ) => {
		const cookieData = JSON.parse( banner.getAttribute( 'data-cookie-settings' ) || '{}' );
		const showBanner = false !== cookieData.showBanner; // Default to true if not specified

		if ( ! showBanner ) {
			banner.classList.add( 'minimized-banner' );
			const bannerElement = banner.querySelector( '.cookie-banner' );
			if ( bannerElement ) {
				bannerElement.classList.add( 'minimized' );
			}
		}

		setupBannerEvents( banner, cookieData );
		setupFloatingButton( banner, cookieData );

		// Check if a cookie decision has already been made
		if ( ! hasConsentCookie() && showBanner ) {
			setTimeout( () => {
				showCookieBanner( banner );
			}, 500 );
		}
	} );
}

/**
 * Set up event listeners for the cookie banner
 * @param banner
 * @param cookieData
 */
function setupBannerEvents( banner, cookieData ) {
	const acceptAllBtn = banner.querySelector( '.accept-all-cookies' );
	const rejectAllBtn = banner.querySelector( '.reject-all-cookies' );
	const customizeBtn = banner.querySelector( '.customize-cookies' );
	const savePrefsBtn = banner.querySelector( '.save-cookie-preferences' );
	const cookieSettings = banner.querySelector( '.cookie-settings' );
	const cookieBanner = banner.querySelector( '.cookie-banner' );

	if ( acceptAllBtn ) {
		acceptAllBtn.addEventListener( 'click', () => {
			acceptAllCookies( cookieData );
			hideCookieBanner( cookieBanner );
		} );
	}

	if ( rejectAllBtn ) {
		rejectAllBtn.addEventListener( 'click', () => {
			rejectAllCookies( cookieData );
			hideCookieBanner( cookieBanner );
		} );
	}

	if ( customizeBtn && cookieSettings ) {
		customizeBtn.addEventListener( 'click', () => {
			toggleCookieSettings( cookieSettings );
		} );
	}

	if ( savePrefsBtn ) {
		savePrefsBtn.addEventListener( 'click', () => {
			saveCookiePreferences( banner, cookieData );
			hideCookieBanner( cookieBanner );
			if ( cookieSettings ) {
				cookieSettings.classList.remove( 'active' );
			}
		} );
	}
}

/**
 * Set up the floating cookie button
 * @param banner
 * @param cookieData
 */
function setupFloatingButton( banner, cookieData ) {
	const floatingButton = banner.querySelector( '.floating-cookie-button' );
	const cookieBanner = banner.querySelector( '.cookie-banner' );

	if ( floatingButton ) {
		floatingButton.addEventListener( 'click', () => {
			if ( cookieBanner.classList.contains( 'hidden' ) ) {
				showCookieBanner( banner );
			} else {
				hideCookieBanner( cookieBanner );
			}
		} );
	}
}

/**
 * Show the cookie banner
 * @param banner
 */
function showCookieBanner( banner ) {
	const cookieBanner = banner.querySelector( '.cookie-banner' );
	if ( cookieBanner ) {
		cookieBanner.classList.remove( 'hidden' );
		cookieBanner.classList.remove( 'minimized' );
	}
}

/**
 * Hide the cookie banner
 * @param cookieBanner
 */
function hideCookieBanner( cookieBanner ) {
	if ( cookieBanner ) {
		cookieBanner.classList.add( 'hidden' );
	}
}

/**
 * Toggle cookie settings visibility
 * @param cookieSettings
 */
function toggleCookieSettings( cookieSettings ) {
	cookieSettings.classList.toggle( 'active' );
}

/**
 * Accept all cookies
 * @param cookieData
 */
function acceptAllCookies( cookieData ) {
	const consent = {
		necessary: true,
		functional: true,
		analytical: true,
		advertising: true,
		tracking: true,
		acceptedAll: true,
		timestamp: new Date().toISOString(),
		version: {
			privacyPolicy: cookieData.privacyPolicyVersion || '',
			terms: cookieData.termsVersion || '',
		},
		country: getVisitorCountryCode(),
	};

	// Apply GeoIP-based expiration date
	const cookieExpiration = getGeoIPBasedExpiration();

	setCookie( 'cookie_consent', JSON.stringify( consent ), cookieExpiration );
	dispatchConsentEvent( consent );
}

/**
 * Reject all cookies
 * @param cookieData
 */
function rejectAllCookies( cookieData ) {
	const consent = {
		necessary: true, // Necessary cookies are always accepted
		functional: false,
		analytical: false,
		advertising: false,
		tracking: false,
		acceptedAll: false,
		timestamp: new Date().toISOString(),
		version: {
			privacyPolicy: cookieData.privacyPolicyVersion || '',
			terms: cookieData.termsVersion || '',
		},
		country: getVisitorCountryCode(),
	};

	// Apply GeoIP-based expiration date
	const cookieExpiration = getGeoIPBasedExpiration();

	setCookie( 'cookie_consent', JSON.stringify( consent ), cookieExpiration );
	dispatchConsentEvent( consent );
}

/**
 * Save cookie preferences from the selections made
 * @param banner
 * @param cookieData
 */
function saveCookiePreferences( banner, cookieData ) {
	const functionalCheckbox = banner.querySelector( '#cookie-functional' );
	const analyticalCheckbox = banner.querySelector( '#cookie-analytical' );
	const advertisingCheckbox = banner.querySelector( '#cookie-advertising' );
	const trackingCheckbox = banner.querySelector( '#cookie-tracking' );

	const consent = {
		necessary: true, // Always required
		functional: functionalCheckbox ? functionalCheckbox.checked : false,
		analytical: analyticalCheckbox ? analyticalCheckbox.checked : false,
		advertising: advertisingCheckbox ? advertisingCheckbox.checked : false,
		tracking: trackingCheckbox ? trackingCheckbox.checked : false,
		acceptedAll: false,
		timestamp: new Date().toISOString(),
		version: {
			privacyPolicy: cookieData.privacyPolicyVersion || '',
			terms: cookieData.termsVersion || '',
		},
		country: getVisitorCountryCode(),
	};

	// Check if all options are selected to determine if "acceptedAll" should be true
	if ( consent.functional && consent.analytical && consent.advertising && consent.tracking ) {
		consent.acceptedAll = true;
	}

	// Apply GeoIP-based expiration date
	const cookieExpiration = getGeoIPBasedExpiration();

	setCookie( 'cookie_consent', JSON.stringify( consent ), cookieExpiration );
	dispatchConsentEvent( consent );
}

/**
 * Check if a consent cookie exists
 */
function hasConsentCookie() {
	const cookie = getCookie( 'cookie_consent' );
	if ( ! cookie ) {
		return false;
	}

	try {
		// If cookie versions don't match current versions, we need to re-ask
		const cookieData = JSON.parse( cookie );

		// Get the current settings from the data attribute
		const banners = document.querySelectorAll( '.wp-block-sync-cookie-banner' );
		if ( ! banners.length ) {
			return true;
		} // If no banner is found, assume cookie is valid

		const currentSettings = JSON.parse( banners[ 0 ].getAttribute( 'data-cookie-settings' ) || '{}' );

		// Check if versions match when check is enabled
		if ( currentSettings.checkVersionChanges ) {
			if (
				cookieData.version.privacyPolicy !== currentSettings.privacyPolicyVersion ||
                cookieData.version.terms !== currentSettings.termsVersion
			) {
				return false; // Version mismatch, need to re-consent
			}
		}

		return true;
	} catch ( e ) {
		return false;
	}
}

/**
 * Set a cookie with the given name, value, and expiration days
 * @param name
 * @param value
 * @param days
 */
function setCookie( name, value, days ) {
	let expires = '';
	if ( days ) {
		const date = new Date();
		date.setTime( date.getTime() + ( days * 24 * 60 * 60 * 1000 ) );
		expires = '; expires=' + date.toUTCString();
	}
	document.cookie = name + '=' + encodeURIComponent( value ) + expires + '; path=/; Secure; SameSite=Lax';
}

/**
 * Get a cookie by name
 * @param name
 */
function getCookie( name ) {
	const nameEQ = name + '=';
	const cookies = document.cookie.split( ';' );

	for ( let i = 0; i < cookies.length; i++ ) {
		const cookie = cookies[ i ].trim();
		if ( 0 === cookie.indexOf( nameEQ ) ) {
			return decodeURIComponent( cookie.substring( nameEQ.length ) );
		}
	}

	return null;
}

/**
 * Dispatch a consent event for other scripts to listen to
 * @param consent
 */
function dispatchConsentEvent( consent ) {
	const event = new CustomEvent( 'cookieConsentUpdate', { detail: consent } );
	document.dispatchEvent( event );

	// Also update global consent object
	window.cookieConsent = consent;
}

/**
 * Get the visitor's country code from the server-side GeoIP detection
 */
function getVisitorCountryCode() {
	// Access country code set via PHP in a global variable
	if ( 'undefined' !== typeof window.visitorGeoData && window.visitorGeoData.countryCode ) {
		return window.visitorGeoData.countryCode;
	}

	// Fallback to server variable if available
	if ( 'undefined' !== typeof window.geoipCountryCode ) {
		return window.geoipCountryCode;
	}

	return '';
}

/**
 * Get the cookie expiration days based on the visitor's country (from GeoIP)
 */
function getGeoIPBasedExpiration() {
	const countryCode = getVisitorCountryCode();
	let expirationDays = 365; // Default to a year

	// EU and UK countries require shorter consent period (180 days)
	const euCountries = [
		'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU',
		'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
	];

	const strictCountries = [
		'GB', // UK GDPR
		'CN', // PIPL
		'KR', // PIPA
	];

	if ( euCountries.includes( countryCode ) || strictCountries.includes( countryCode ) ) {
		expirationDays = 180;
	}

	return expirationDays;
}
