( () => {
	const e = { setCookie( e, t, i ) {
		const n = new Date; n.setTime( n.getTime() + 24 * i * 60 * 60 * 1e3 ); const o = 'expires=' + n.toUTCString(); document.cookie = e + '=' + t + ';' + o + ';path=/;SameSite=Lax';
	}, getCookie( e ) {
		const t = e + '=',
			i = document.cookie.split( ';' ); for ( let e = 0; e < i.length; e++ ) {
			let n = i[ e ]; for ( ;' ' === n.charAt( 0 ); ) {
				n = n.substring( 1, n.length );
			} if ( 0 === n.indexOf( t ) ) {
				return n.substring( t.length, n.length );
			}
		} return null;
	}, deleteCookie( e ) {
		document.cookie = e + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;SameSite=Lax';
	} }; window.WiseSyncCookies = { registeredServices: {}, callbacks: { necessary: [], functional: [], analytical: [], advertising: [], tracking: [] }, versions: { privacy: null, terms: null } }, window.is_sync_cookie_permission_to = function( t ) {
		if ( 'necessary' === t ) {
			return ! 0;
		} if ( ! [ 'functional', 'analytical', 'advertising', 'tracking' ].includes( t ) ) {
			return console.warn( `Cookie permission type "${ t }" is not recognized` ), ! 1;
		} const i = e.getCookie( 'wisesync_cookie_consent' ); if ( ! i ) {
			return ! 1;
		} try {
			return ! 0 === JSON.parse( i )[ t ];
		} catch ( e ) {
			return console.error( 'Error parsing cookie consent data:', e ), ! 1;
		}
	}, window.register_cookie_service = function( e, t, i ) {
		const n = [ 'functional', 'analytical', 'advertising', 'tracking' ]; n.includes( i ) ? ( window.WiseSyncCookies.registeredServices[ e ] = { name: t, type: i, registered: ( new Date ).toISOString() }, window.is_sync_cookie_permission_to( i ) && ( function( e ) {
			Array.isArray( window.WiseSyncCookies.callbacks[ e ] ) && window.WiseSyncCookies.callbacks[ e ].forEach( ( ( e ) => {
				'function' === typeof e && e();
			} ) );
		}( i ) ) ) : console.error( `Invalid cookie service type: ${ i }. Must be one of: ${ n.join( ', ' ) }` );
	}; class t {
		constructor( e, t ) {
			this.blockId = e, this.config = t, this.cookieName = 'wisesync_cookie_consent', this.cookieExpiration = t.cookieExpiration || 365, this.isCustomizing = ! 1, this.consentStatus = this.getConsentStatus(), this.elements = { banner: document.getElementById( 'cookie-banner-' + e ), floatingButton: document.getElementById( 'cookie-settings-button-' + e ), customizeModal: document.getElementById( 'cookie-customize-modal-' + e ) }, this.policyVersionsChanged = this.checkPolicyVersions(), this.init();
		}init() {
			! this.hasConsent() || this.policyVersionsChanged ? this.showBanner() : ( this.hideBanner(), this.showFloatingButton(), this.executeCallbacks( this.consentStatus ) ), this.attachEventListeners(), this.storeCurrentPolicyVersions();
		}attachEventListeners() {
			const e = this.elements.banner.querySelector( '.cookie-accept-all' ),
				t = this.elements.banner.querySelector( '.cookie-reject-all' ),
				i = this.elements.banner.querySelector( '.cookie-customize' ); e && e.addEventListener( 'click', ( () => this.acceptAll() ) ), t && t.addEventListener( 'click', ( () => this.rejectAll() ) ), i && i.addEventListener( 'click', ( () => this.openCustomizeModal() ) ), this.elements.floatingButton && this.elements.floatingButton.addEventListener( 'click', ( () => this.openCustomizeModal() ) ); const n = this.elements.customizeModal ? this.elements.customizeModal.querySelector( '.cookie-save-preferences' ) : null; n && n.addEventListener( 'click', ( () => this.savePreferences() ) ); const o = this.elements.customizeModal ? this.elements.customizeModal.querySelector( '.cookie-modal-close' ) : null; o && o.addEventListener( 'click', ( () => this.closeCustomizeModal() ) ), this.elements.customizeModal && document.addEventListener( 'click', ( ( e ) => {
				e.target === this.elements.customizeModal && this.closeCustomizeModal();
			} ) ), document.addEventListener( 'keydown', ( ( e ) => {
				'Escape' === e.key && this.isCustomizing && this.closeCustomizeModal();
			} ) );
		}hasConsent() {
			return null !== e.getCookie( this.cookieName );
		}getConsentStatus() {
			const t = e.getCookie( this.cookieName ); if ( ! t ) {
				return { necessary: ! 0, functional: ! 1, analytical: ! 1, advertising: ! 1, tracking: ! 1 };
			} try {
				return JSON.parse( t );
			} catch ( e ) {
				return { necessary: ! 0, functional: ! 1, analytical: ! 1, advertising: ! 1, tracking: ! 1 };
			}
		}checkPolicyVersions() {
			if ( ! this.config.checkVersionChanges || ! this.hasConsent() ) {
				return ! 1;
			} const t = e.getCookie( 'wisesync_policy_versions' ); if ( ! t ) {
				return ! 1;
			} try {
				const e = JSON.parse( t ); return ! ( ! this.config.privacyPolicyVersion || ! e.privacy || e.privacy === this.config.privacyPolicyVersion ) || ! ( ! this.config.termsVersion || ! e.terms || e.terms === this.config.termsVersion );
			} catch ( e ) {
				return ! 1;
			}
		}storeCurrentPolicyVersions() {
			if ( ! this.config.checkVersionChanges ) {
				return;
			} const t = { privacy: this.config.privacyPolicyVersion || null, terms: this.config.termsVersion || null }; e.setCookie( 'wisesync_policy_versions', JSON.stringify( t ), this.cookieExpiration ), window.WiseSyncCookies.versions = t;
		}acceptAll() {
			let e, t, i, n; const o = { necessary: ! 0, functional: null === ( e = this.config.enabledCookieTypes?.functional ) || void 0 === e || e, analytical: null === ( t = this.config.enabledCookieTypes?.analytical ) || void 0 === t || t, advertising: null === ( i = this.config.enabledCookieTypes?.advertising ) || void 0 === i || i, tracking: null === ( n = this.config.enabledCookieTypes?.tracking ) || void 0 === n || n }; this.saveConsentStatus( o ), this.hideBanner(), this.showFloatingButton(), this.executeCallbacks( o );
		}rejectAll() {
			const e = { necessary: ! 0, functional: ! 1, analytical: ! 1, advertising: ! 1, tracking: ! 1 }; this.saveConsentStatus( e ), this.hideBanner(), this.showFloatingButton(), this.executeCallbacks( e );
		}savePreferences() {
			let e, t, i, n; const o = { necessary: ! 0, functional: null !== ( e = this.elements.customizeModal.querySelector( '#cookie-functional' )?.checked ) && void 0 !== e && e, analytical: null !== ( t = this.elements.customizeModal.querySelector( '#cookie-analytical' )?.checked ) && void 0 !== t && t, advertising: null !== ( i = this.elements.customizeModal.querySelector( '#cookie-advertising' )?.checked ) && void 0 !== i && i, tracking: null !== ( n = this.elements.customizeModal.querySelector( '#cookie-tracking' )?.checked ) && void 0 !== n && n }; this.saveConsentStatus( o ), this.hideBanner(), this.closeCustomizeModal(), this.showFloatingButton(), this.executeCallbacks( o );
		}saveConsentStatus( t ) {
			const i = JSON.stringify( t ); e.setCookie( this.cookieName, i, this.cookieExpiration ), this.consentStatus = t; const n = new CustomEvent( 'cookieConsentUpdated', { detail: { consent: t, blockId: this.blockId, services: window.WiseSyncCookies.registeredServices } } ); document.dispatchEvent( n );
		}showBanner() {
			this.elements.banner && ( this.elements.banner.classList.add( 'visible' ), this.elements.banner.setAttribute( 'aria-hidden', 'false' ) );
		}hideBanner() {
			this.elements.banner && ( this.elements.banner.classList.remove( 'visible' ), this.elements.banner.setAttribute( 'aria-hidden', 'true' ) );
		}showFloatingButton() {
			this.elements.floatingButton && setTimeout( ( () => {
				this.elements.floatingButton.classList.add( 'visible' ), this.elements.floatingButton.setAttribute( 'aria-hidden', 'false' );
			} ), 1e3 );
		}openCustomizeModal() {
			if ( this.elements.customizeModal ) {
				const e = this.elements.customizeModal.querySelector( '#cookie-functional' ),
					t = this.elements.customizeModal.querySelector( '#cookie-analytical' ),
					i = this.elements.customizeModal.querySelector( '#cookie-advertising' ),
					n = this.elements.customizeModal.querySelector( '#cookie-tracking' ); e && ( e.checked = this.consentStatus.functional ), t && ( t.checked = this.consentStatus.analytical ), i && ( i.checked = this.consentStatus.advertising ), n && ( n.checked = this.consentStatus.tracking ), this.elements.customizeModal.classList.add( 'visible' ), this.elements.customizeModal.setAttribute( 'aria-hidden', 'false' ), this.isCustomizing = ! 0, this.hideBanner(); const o = this.elements.customizeModal.querySelector( 'input[type="checkbox"]' ); o && o.focus();
			}
		}closeCustomizeModal() {
			this.elements.customizeModal && ( this.elements.customizeModal.classList.remove( 'visible' ), this.elements.customizeModal.setAttribute( 'aria-hidden', 'true' ), this.isCustomizing = ! 1 );
		}executeCallbacks( e ) {
			Object.keys( e ).forEach( ( ( t ) => {
				! 0 === e[ t ] && Array.isArray( window.WiseSyncCookies.callbacks[ t ] ) && window.WiseSyncCookies.callbacks[ t ].forEach( ( ( e ) => {
					'function' === typeof e && e();
				} ) );
			} ) ), Object.keys( window.WiseSyncCookies.registeredServices ).forEach( ( ( t ) => {
				const i = window.WiseSyncCookies.registeredServices[ t ]; if ( ! 0 === e[ i.type ] ) {
					const e = new CustomEvent( 'cookieServiceActivated', { detail: { service: t, name: i.name, type: i.type } } ); document.dispatchEvent( e );
				}
			} ) );
		}
	} function i( e ) {
		const t = e.querySelector( '.cookie-banner' ); t && ( t.classList.remove( 'hidden' ), t.classList.remove( 'minimized' ) );
	} function n( e ) {
		e && e.classList.add( 'hidden' );
	} function o( e, t, i ) {
		let n = ''; if ( i ) {
			const e = new Date; e.setTime( e.getTime() + 24 * i * 60 * 60 * 1e3 ), n = '; expires=' + e.toUTCString();
		}document.cookie = e + '=' + encodeURIComponent( t ) + n + '; path=/; Secure; SameSite=Lax';
	} function s( e ) {
		const t = new CustomEvent( 'cookieConsentUpdate', { detail: e } ); document.dispatchEvent( t ), window.cookieConsent = e;
	} function c() {
		return void 0 !== window.visitorGeoData && window.visitorGeoData.countryCode ? window.visitorGeoData.countryCode : void 0 !== window.geoipCountryCode ? window.geoipCountryCode : '';
	} function r() {
		const e = c(); let t = 365; return ( [ 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE' ].includes( e ) || [ 'GB', 'CN', 'KR' ].includes( e ) ) && ( t = 180 ), t;
	}document.addEventListener( 'DOMContentLoaded', ( () => {
		document.querySelectorAll( '.wisesync-cookie-banner' ).forEach( ( ( e ) => {
			const i = e.dataset.blockId,
				n = document.getElementById( 'cookie-config-' + i ); if ( n ) {
				try {
					const e = JSON.parse( n.textContent ); new t( i, e );
				} catch ( e ) {
					console.error( 'Error parsing cookie banner config:', e );
				}
			}
		} ) ), window.registerCookieCallback = function( e, t ) {
			void 0 !== window.WiseSyncCookies.callbacks[ e ] && 'function' === typeof t && ( window.WiseSyncCookies.callbacks[ e ].push( t ), window.is_sync_cookie_permission_to( e ) && t() );
		};
	} ) ), document.addEventListener( 'DOMContentLoaded', ( () => {
		! ( function() {
			const e = document.querySelectorAll( '.wp-block-sync-cookie-banner' ); e.length && e.forEach( ( ( e ) => {
				const t = JSON.parse( e.getAttribute( 'data-cookie-settings' ) || '{}' ),
					a = ! 1 !== t.showBanner; if ( ! a ) {
					e.classList.add( 'minimized-banner' ); const t = e.querySelector( '.cookie-banner' ); t && t.classList.add( 'minimized' );
				}! ( function( e, t ) {
					const i = e.querySelector( '.accept-all-cookies' ),
						a = e.querySelector( '.reject-all-cookies' ),
						l = e.querySelector( '.customize-cookies' ),
						d = e.querySelector( '.save-cookie-preferences' ),
						u = e.querySelector( '.cookie-settings' ),
						h = e.querySelector( '.cookie-banner' ); i && i.addEventListener( 'click', ( () => {
						! ( function( e ) {
							const t = { necessary: ! 0, functional: ! 0, analytical: ! 0, advertising: ! 0, tracking: ! 0, acceptedAll: ! 0, timestamp: ( new Date ).toISOString(), version: { privacyPolicy: e.privacyPolicyVersion || '', terms: e.termsVersion || '' }, country: c() },
								i = r(); o( 'cookie_consent', JSON.stringify( t ), i ), s( t );
						}( t ) ), n( h );
					} ) ), a && a.addEventListener( 'click', ( () => {
						! ( function( e ) {
							const t = { necessary: ! 0, functional: ! 1, analytical: ! 1, advertising: ! 1, tracking: ! 1, acceptedAll: ! 1, timestamp: ( new Date ).toISOString(), version: { privacyPolicy: e.privacyPolicyVersion || '', terms: e.termsVersion || '' }, country: c() },
								i = r(); o( 'cookie_consent', JSON.stringify( t ), i ), s( t );
						}( t ) ), n( h );
					} ) ), l && u && l.addEventListener( 'click', ( () => {
						! ( function( e ) {
							e.classList.toggle( 'active' );
						}( u ) );
					} ) ), d && d.addEventListener( 'click', ( () => {
						! ( function( e, t ) {
							const i = e.querySelector( '#cookie-functional' ),
								n = e.querySelector( '#cookie-analytical' ),
								a = e.querySelector( '#cookie-advertising' ),
								l = e.querySelector( '#cookie-tracking' ),
								d = { necessary: ! 0, functional: !! i && i.checked, analytical: !! n && n.checked, advertising: !! a && a.checked, tracking: !! l && l.checked, acceptedAll: ! 1, timestamp: ( new Date ).toISOString(), version: { privacyPolicy: t.privacyPolicyVersion || '', terms: t.termsVersion || '' }, country: c() }; d.functional && d.analytical && d.advertising && d.tracking && ( d.acceptedAll = ! 0 ); const u = r(); o( 'cookie_consent', JSON.stringify( d ), u ), s( d );
						}( e, t ) ), n( h ), u && u.classList.remove( 'active' );
					} ) );
				}( e, t ) ), ( function( e ) {
					const t = e.querySelector( '.floating-cookie-button' ),
						o = e.querySelector( '.cookie-banner' ); t && t.addEventListener( 'click', ( () => {
						o.classList.contains( 'hidden' ) ? i( e ) : n( o );
					} ) );
				}( e ) ), ! ( function() {
					const e = ( function() {
						const e = 'cookie_consent=',
							t = document.cookie.split( ';' ); for ( let i = 0; i < t.length; i++ ) {
							const n = t[ i ].trim(); if ( 0 === n.indexOf( e ) ) {
								return decodeURIComponent( n.substring( 15 ) );
							}
						} return null;
					}() ); if ( ! e ) {
						return ! 1;
					} try {
						const t = JSON.parse( e ),
							i = document.querySelectorAll( '.wp-block-sync-cookie-banner' ); if ( ! i.length ) {
							return ! 0;
						} const n = JSON.parse( i[ 0 ].getAttribute( 'data-cookie-settings' ) || '{}' ); return ! n.checkVersionChanges || t.version.privacyPolicy === n.privacyPolicyVersion && t.version.terms === n.termsVersion;
					} catch ( e ) {
						return ! 1;
					}
				}() ) && a && setTimeout( ( () => {
					i( e );
				} ), 500 );
			} ) );
		}() );
	} ) );
} )();
