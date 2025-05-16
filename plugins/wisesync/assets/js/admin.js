/**
 * Admin Dashboard JavaScript using the Event.js delegation system
 */

document.addEventListener( 'DOMContentLoaded', function () {
	'use strict';

	// Initialize Sync Dashboard
	syncDashboardInit();

	/**
	 * Initialize the Sync Dashboard
	 */
	function syncDashboardInit() {
		setupMobileMenu();
		setupMenuNavigation();
		handleUrlHash(); // Load content based on URL hash on page load
		setupDismissibleCards();

		// Setup hashchange event with delegated event system
		registerEvent( 'sync-hash-change', 'hashchange', {}, function () {
			handleUrlHash();
		} );

		registerEvent(
			'sync-settings-single-ajax-form-input',
			'input',
			{ selector: '.sync-single-form :is(input, textarea)' },
			syncSettingsSingleAjaxFormSubmit,
			1000
		);

		registerEvent(
			'sync-settings-single-ajax-form-submit',
			'click',
			{ selector: '.sync-single-form .sync-button.sync-submit-button' },
			syncSettingsSingleAjaxFormSubmit
		);

		// registerEvent(
		// 	'sync-settings-each-ajax-form-submit'
		// );
	}

	/**
	 * Handle AJAX form submission for single forms
	 *
	 * @param {Event} e    Event object
	 * @param {JSON}  data Prased Event Data
	 */
	function syncSettingsSingleAjaxFormSubmit( e, data ) {
		const formElement = data.targetElement.closest( 'form' );
		const inputElements = formElement.querySelectorAll( 'input, textarea' );
		const formButton = formElement.querySelector( '.sync-button.sync-submit-button' );
		if ( checkRequiredInputs( inputElements ) && checkRegexMatchForForm( inputElements ) ) {
			// Get Form Button and enable it
			formButton.removeAttribute( 'disabled' );
		} else {
			// Get Form Button and disable it
			formButton.setAttribute( 'disabled', 'disabled' );
		}
		if ( 'click' === data.eventType && 'sync-settings-single-ajax-form-submit' === data.eventName ) {
			if ( checkRequiredInputs( inputElements ) && checkRegexMatchForForm( inputElements ) ) {
				ajaxRequest( formElement, 'POST' );
			}
		}
	}

	/**
	 * Check if inputElements have required attribute, then check if they are empty
	 *
	 * @param {NodeList} inputElements - NodeList of input elements
	 * @return {boolean} - True if all required inputs are filled, false otherwise
	 */
	function checkRequiredInputs( inputElements ) {
		let allFilled = true;

		inputElements.forEach( function ( input ) {
			if ( input.hasAttribute( 'required' ) && ! input.value.trim() ) {
				allFilled = false;
			}
		} );

		return allFilled;
	}

	/**
	 * Check if field is valid or not by regex
	 *
	 * It will check if input field have attribute data-regex-match or not,
	 * if yes then is value is matching that regex,
	 * if yes then return true else false.
	 * @param {NodeList} inputElements - NodeList of input elements
	 * @return {boolean} - True if all required inputs are filled, false otherwise
	 */
	function checkRegexMatchForForm( inputElements ) {
		let allValid = true;

		// Loop through all input elements
		inputElements.forEach( function ( input ) {
			// If any single validation fails, the overall result should be false
			// Important: We still check all inputs but remember if any failed
			const isValid = checkRegexMatchOfInput( input );
			if ( ! isValid ) {
				allValid = false;
				// Don't return early - we want to validate all fields to show all errors
			}
		} );
		return allValid;
	}

	/**
	 * Check if field is valid or not by regex
	 *
	 * It will check if input field have attribute data-regex-match or not,
	 * if yes then is value is matching that regex,
	 * if yes then return true else false.
	 * @param {Element} inputElement - NodeList of input elements
	 * @return {boolean} - True if all required inputs are filled, false otherwise
	 */
	function checkRegexMatchOfInput( inputElement ) {
		// If no regex attribute, consider it valid
		if ( ! inputElement.hasAttribute( 'data-regex-match' ) ) {
			return true;
		}

		const patternStr = inputElement.getAttribute( 'data-regex-match' );
		const inputValue = inputElement.value.trim();
		let pattern,
			flags = '';

		// Handle pattern with or without regex literal syntax
		if ( patternStr.startsWith( '/' ) ) {
			const lastSlashIndex = patternStr.lastIndexOf( '/' );
			if ( 0 < lastSlashIndex ) {
				// Extract pattern between slashes and any flags after the last slash
				pattern = patternStr.slice( 1, lastSlashIndex );
				flags = patternStr.slice( lastSlashIndex + 1 );
			} else {
				pattern = patternStr;
			}
		} else {
			pattern = patternStr;
		}

		try {
			const regex = new RegExp( pattern, flags );
			return regex.test( inputValue );
		} catch ( e ) {
			return false;
		}
	}

	/**
	 * Send AJAX request with form data as application/x-www-form-urlencoded
	 *
	 * @param {HTMLFormElement} form   The form element whose inputs to send
	 * @param {string}          method HTTP method to use (defaults to "POST")
	 * @return {void}
	 */
	function ajaxRequest( form, method = 'POST' ) {
		if ( ! ( form instanceof HTMLFormElement ) ) {
			console.error( 'ajaxRequest: first argument must be a <form> element' );
			return;
		}

		// Gather all inputs into a FormData instance
		const formData = new FormData( form );

		// Convert to URL-encoded string
		const urlParams = new URLSearchParams();
		for ( const [ key, value ] of formData.entries() ) {
			urlParams.append( key, value );
		}

		// Fire off the request
		fetch( window.ajaxurl, {
			method,
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
			},
			body: urlParams.toString(),
		} )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( `Server returned ${ response.status } ${ response.statusText }` );
				}
				return response.json();
			} )
			.then( ( json ) => {
				console.log( 'ajaxRequest response:', json );
			} )
			.catch( ( err ) => {
				console.error( 'ajaxRequest error:', err );
			} );
	}

	/**
	 * Mobile menu toggle
	 */
	function setupMobileMenu() {
		// Mobile toggle click event
		registerEvent(
			'sync-mobile-toggle-click',
			'click',
			{ selector: '#sync-mobile-toggle' },
			function ( e, data ) {
				const sidebar = document.getElementById( 'sync-sidebar' );
				sidebar.classList.toggle( 'sync-mobile-open' );
			}
		);

		// Close mobile menu when clicking outside
		registerEvent( 'sync-mobile-outside-click', 'click', {}, function ( e, data ) {
			const sidebar = document.getElementById( 'sync-sidebar' );
			if ( sidebar && sidebar.classList.contains( 'sync-mobile-open' ) ) {
				const clickedInside =
					e.target.closest( '#sync-sidebar' ) || e.target.closest( '#sync-mobile-toggle' );
				if ( ! clickedInside ) {
					sidebar.classList.remove( 'sync-mobile-open' );
				}
			}
		} );
	}

	/**
	 * Setup menu navigation and dynamic content loading
	 */
	function setupMenuNavigation() {
		// Handle main menu clicks
		registerEvent(
			'sync-menu-link-click',
			'click',
			{ selector: '.sync-menu-link' },
			function ( e, data ) {
				e.preventDefault();

				const link = data.targetElement;
				const menuItem = link.parentElement;
				const submenu = menuItem.querySelector( '.sync-submenu' );
				const menuSlug = link.getAttribute( 'data-slug' );
				const href = link.getAttribute( 'href' );
				const pageTitle = document.querySelector( '.sync-page-title' );

				// If this item has a submenu
				if ( submenu ) {
					// Toggle active state
					if ( menuItem.classList.contains( 'sync-active' ) ) {
						menuItem.classList.remove( 'sync-active' );
					} else {
						// Remove active class from siblings
						document.querySelectorAll( '.sync-menu-item.sync-active' ).forEach( function ( activeItem ) {
							activeItem.classList.remove( 'sync-active' );
						} );
						menuItem.classList.add( 'sync-active' );
					}
				} else {
					// If no submenu, simply set this item as active
					document.querySelectorAll( '.sync-menu-item' ).forEach( function ( item ) {
						item.classList.remove( 'sync-active' );
					} );
					menuItem.classList.add( 'sync-active' );

					// Update page title
					if ( pageTitle ) {
						const menuText = link.querySelector( '.sync-menu-text' ).textContent;
						pageTitle.textContent = menuText;
					}

					// Update URL hash without triggering hashchange event
					history.pushState( null, '', href );

					// Load content for this menu
					loadMenuContent( menuSlug );
				}
			}
		);

		// Handle submenu clicks
		registerEvent(
			'sync-submenu-link-click',
			'click',
			{ selector: '.sync-submenu-link' },
			function ( e, data ) {
				e.preventDefault();

				const link = data.targetElement;
				const parentSlug = link.getAttribute( 'data-parent' );
				const subMenuSlug = link.getAttribute( 'data-slug' );
				const href = link.getAttribute( 'href' );
				const pageTitle = document.querySelector( '.sync-page-title' );

				// Set active state for parent menu item
				document.querySelectorAll( '.sync-menu-item' ).forEach( function ( item ) {
					item.classList.remove( 'sync-active' );
				} );
				link.closest( '.sync-menu-item' ).classList.add( 'sync-active' );

				// Set active state for submenu item
				document.querySelectorAll( '.sync-submenu-item' ).forEach( function ( item ) {
					item.classList.remove( 'sync-active' );
				} );
				link.parentElement.classList.add( 'sync-active' );

				// Update page title
				if ( pageTitle ) {
					const menuText = link
						.closest( '.sync-menu-item' )
						.querySelector( '.sync-menu-text' ).textContent;
					const submenuText = link.textContent;
					pageTitle.textContent = `${ menuText } - ${ submenuText }`;
				}

				// Update URL hash
				history.pushState( null, '', href );

				// Load content for this submenu
				loadSubmenuContent( parentSlug, subMenuSlug );
			}
		);

		// Initial load of default content if no hash in URL
		if ( ! window.location.hash ) {
			const defaultMenuLink = document.querySelector( '.sync-menu-link' );
			if ( defaultMenuLink ) {
				const defaultMenuSlug = defaultMenuLink.getAttribute( 'data-slug' );
				loadMenuContent( defaultMenuSlug );
			}
		}
	}

	/**
	 * Handle URL hash on page load to navigate to specific menu/submenu
	 */
	function handleUrlHash() {
		if ( window.location.hash ) {
			const hash = window.location.hash.substring( 1 ); // Remove the # character

			// Check if hash is for a submenu (format: sync-parent-child)
			if ( 2 < hash.split( '-' ).length ) {
				const parts = hash.split( '-' );
				// Remove 'sync-' prefix and get the parent and child slugs
				const parentSlug = parts[ 1 ];
				const childSlug = parts.slice( 2 ).join( '-' ); // In case child slug has hyphens

				// Set active states
				const menuItem = document.querySelector(
					`.sync-menu-link[data-slug="${ parentSlug }"]`
				).parentElement;
				menuItem.classList.add( 'sync-active' );

				const submenuItem = document.querySelector(
					`.sync-submenu-link[data-parent="${ parentSlug }"][data-slug="${ childSlug }"]`
				);
				if ( submenuItem ) {
					submenuItem.parentElement.classList.add( 'sync-active' );

					// Update page title
					const pageTitle = document.querySelector( '.sync-page-title' );
					if ( pageTitle ) {
						const menuText = menuItem.querySelector( '.sync-menu-text' ).textContent;
						const submenuText = submenuItem.textContent;
						pageTitle.textContent = `${ menuText } - ${ submenuText }`;
					}

					// Load submenu content
					loadSubmenuContent( parentSlug, childSlug );
				}
			} else if ( hash.startsWith( 'sync-' ) ) {
				const menuSlug = hash.replace( 'sync-', '' );
				const menuLink = document.querySelector( `.sync-menu-link[data-slug="${ menuSlug }"]` );

				if ( menuLink ) {
					// Set active state
					document.querySelectorAll( '.sync-menu-item' ).forEach( function ( item ) {
						item.classList.remove( 'sync-active' );
					} );
					menuLink.parentElement.classList.add( 'sync-active' );

					// Update page title
					const pageTitle = document.querySelector( '.sync-page-title' );
					if ( pageTitle ) {
						const menuText = menuLink.querySelector( '.sync-menu-text' ).textContent;
						pageTitle.textContent = menuText;
					}

					// Load menu content
					loadMenuContent( menuSlug );
				}
			}
		} else {
			// No hash in URL, load default menu content
			const defaultMenuLink = document.querySelector( '.sync-menu-link' );
			if ( defaultMenuLink ) {
				const defaultMenuSlug = defaultMenuLink.getAttribute( 'data-slug' );
				loadMenuContent( defaultMenuSlug );
			}
		}
	}

	/**
	 * Dismissible cards
	 */
	function setupDismissibleCards() {
		// Use event delegation for dismiss icons
		registerEvent(
			'sync-dismiss-card',
			'click',
			{ selector: '.sync-dismiss-icon' },
			function ( e, data ) {
				// Find the parent card and remove it with animation
				const card = data.targetElement.closest( '.sync-card' );
				fadeOut( card, 300, function () {
					card.remove();
				} );

				// Store the dismissed state in localStorage
				const cardId = card.dataset.id || 'welcome-card';
				localStorage.setItem( 'sync-dismissed-' + cardId, 'true' );
			}
		);

		// Check for previously dismissed cards
		document.querySelectorAll( '.sync-card' ).forEach( function ( card ) {
			const cardId = card.dataset.id || 'welcome-card';
			if ( 'true' === localStorage.getItem( 'sync-dismissed-' + cardId ) ) {
				card.style.display = 'none';
			}
		} );
	}

	/**
	 * Load content for a main menu item
	 * @param {string} menuSlug - The slug of the menu to load
	 */
	function loadMenuContent( menuSlug ) {
		const template = document.getElementById( `sync-page-${ menuSlug }` );
		const contentContainer = document.getElementById( 'sync-dynamic-content' );

		if ( template ) {
			// Fade out current content
			fadeOut( contentContainer, 200, function () {
				// Replace content with template content
				contentContainer.innerHTML = template.innerHTML;

				// Reinitialize components in the new content
				setupDismissibleCards();

				// Fade in new content
				contentContainer.style.opacity = '0';
				contentContainer.style.display = '';
				fadeIn( contentContainer, 200 );
			} );
		}
	}

	/**
	 * Load content for a submenu item
	 * @param {string} parentSlug  - The slug of the parent menu
	 * @param {string} subMenuSlug - The slug of the submenu to load
	 */
	function loadSubmenuContent( parentSlug, subMenuSlug ) {
		const template = document.getElementById( `sync-subpage-${ parentSlug }-${ subMenuSlug }` );
		const contentContainer = document.getElementById( 'sync-dynamic-content' );

		if ( template ) {
			// Fade out current content
			fadeOut( contentContainer, 200, function () {
				// Replace content with template content
				contentContainer.innerHTML = template.innerHTML;

				// Reinitialize components in the new content
				setupDismissibleCards();

				// Fade in new content
				contentContainer.style.opacity = '0';
				contentContainer.style.display = '';
				fadeIn( contentContainer, 200 );
			} );
		}
	}

	/**
	 * Helper function to fade out an element
	 * @param {Element}  element  - The DOM element to fade out
	 * @param {number}   duration - The duration of the fade in milliseconds
	 * @param {Function} callback - Callback function to run after fade completes
	 */
	function fadeOut( element, duration, callback ) {
		element.style.opacity = 1;

		const start = performance.now();

		/**
		 * Animate the fade out effect
		 *
		 * @param {number} time - The current time
		 */
		function animate( time ) {
			const elapsed = time - start;
			const opacity = 1 - Math.min( elapsed / duration, 1 );

			element.style.opacity = opacity;

			if ( elapsed < duration ) {
				requestAnimationFrame( animate );
			} else {
				element.style.display = 'none';
				if ( 'function' === typeof callback ) {
					callback();
				}
			}
		}

		requestAnimationFrame( animate );
	}

	/**
	 * Helper function to fade in an element
	 * @param {Element}  element  - The DOM element to fade in
	 * @param {number}   duration - The duration of the fade in milliseconds
	 * @param {Function} callback - Callback function to run after fade completes
	 */
	function fadeIn( element, duration, callback ) {
		element.style.opacity = 0;
		element.style.display = '';

		const start = performance.now();

		/**
		 * Animate the fade in effect.
		 *
		 * @param {number} time - The current time.
		 */
		function animate( time ) {
			const elapsed = time - start;
			const opacity = Math.min( elapsed / duration, 1 );

			element.style.opacity = opacity;

			if ( elapsed < duration ) {
				requestAnimationFrame( animate );
			} else if ( 'function' === typeof callback ) {
				callback();
			}
		}

		requestAnimationFrame( animate );
	}

	// Site Health Check

	// Copy Sync Logs button
	const syncLogsCopyBtn = document.querySelector( '.copy-sync-info' );
	if ( syncLogsCopyBtn ) {
		syncLogsCopyBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();

			const button = this;
			const originalText = button.textContent;
			const textarea = document.querySelector( '.sync-logs' );

			// Copy to clipboard
			textarea.select();
			document.execCommand( 'copy' );

			// Visual feedback
			button.textContent = 'Copied!';

			setTimeout( function () {
				button.textContent = originalText;
			}, 2000 );
		} );
	}

	// Copy All Sync Information button
	const allSyncInfoBtn = document.querySelector( '.copy-all-sync-info' );
	if ( allSyncInfoBtn ) {
		allSyncInfoBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();

			const button = this;
			const originalText = button.textContent;
			const feedback = document.querySelector( '.copy-feedback' );
			const textarea = document.getElementById( 'complete-sync-info' );

			// Copy to clipboard
			textarea.style.display = 'block';
			textarea.select();
			document.execCommand( 'copy' );
			textarea.style.display = 'none';

			// Visual feedback
			button.textContent = 'Copied!';
			feedback.textContent = 'Sync information copied to clipboard';
			feedback.classList.add( 'visible' );

			setTimeout( function () {
				button.textContent = originalText;
				feedback.classList.remove( 'visible' );
				setTimeout( function () {
					feedback.textContent = '';
				}, 300 );
			}, 2000 );
		} );
	}

	// Modern clipboard API implementation (as fallback for newer browsers)
	if ( navigator.clipboard && window.isSecureContext ) {
		const modernCopyButtons = document.querySelectorAll( '.copy-sync-info, .copy-all-sync-info' );

		modernCopyButtons.forEach( function ( button ) {
			button.addEventListener( 'click', function ( e ) {
				const isSyncLogs = button.classList.contains( 'copy-sync-info' );
				const textToCopy = isSyncLogs
					? document.querySelector( '.sync-logs' ).value
					: document.getElementById( 'complete-sync-info' ).value;

				navigator.clipboard.writeText( textToCopy );
			} );
		} );
	}
} );
