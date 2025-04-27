document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	// Initialize Sync Dashboard
	syncDashboardInit();

	/**
	 * Initialize the Sync Dashboard
	 */
	function syncDashboardInit() {
		setupMobileMenu();
		setupMenuNavigation();
		setupDismissibleCards();
		setupToggleSwitches();
		setupRefreshButton();
		setupActionButtons();
		addSpinnerStyles();
	}

	/**
	 * Mobile menu toggle
	 */
	function setupMobileMenu() {
		const mobileToggle = document.getElementById( 'sync-mobile-toggle' );
		const sidebar = document.getElementById( 'sync-sidebar' );

		if ( mobileToggle ) {
			mobileToggle.addEventListener( 'click', function() {
				sidebar.classList.toggle( 'sync-mobile-open' );
			} );
		}

		// Close mobile menu when clicking outside
		document.addEventListener( 'click', function( e ) {
			if ( sidebar && sidebar.classList.contains( 'sync-mobile-open' ) ) {
				const clickedInside = e.target.closest( '#sync-sidebar' ) || e.target.closest( '#sync-mobile-toggle' );
				if ( !clickedInside ) {
					sidebar.classList.remove( 'sync-mobile-open' );
				}
			}
		} );
	}

	/**
	 * Setup menu navigation and dynamic content loading
	 */
	function setupMenuNavigation() {
		const menuLinks = document.querySelectorAll( '.sync-menu-link' );
		const submenuLinks = document.querySelectorAll( '.sync-submenu-link' );
		const pageTitle = document.querySelector( '.sync-page-title' );
		const contentContainer = document.getElementById( 'sync-dynamic-content' );

		// Handle main menu clicks
		menuLinks.forEach( function( link ) {
			link.addEventListener( 'click', function( e ) {
				const menuItem = this.parentElement;
				const submenu = menuItem.querySelector( '.sync-submenu' );
				const menuSlug = this.getAttribute( 'data-slug' );

				// If this item has a submenu
				if ( submenu ) {
					e.preventDefault();

					// Toggle active state
					if ( menuItem.classList.contains( 'sync-active' ) ) {
						menuItem.classList.remove( 'sync-active' );
					} else {
						// Remove active class from siblings
						document.querySelectorAll( '.sync-menu-item.sync-active' ).forEach( function( activeItem ) {
							activeItem.classList.remove( 'sync-active' );
						} );
						menuItem.classList.add( 'sync-active' );
					}
				} else {
					e.preventDefault();
					// If no submenu, simply set this item as active
					document.querySelectorAll( '.sync-menu-item' ).forEach( function( item ) {
						item.classList.remove( 'sync-active' );
					} );
					menuItem.classList.add( 'sync-active' );

					// Update page title
					if ( pageTitle ) {
						const menuText = this.querySelector( '.sync-menu-text' ).textContent;
						pageTitle.textContent = menuText;
					}

					// Load content for this menu
					loadMenuContent(menuSlug);
				}
			} );
		} );

		// Handle submenu clicks
		submenuLinks.forEach( function( link ) {
			link.addEventListener( 'click', function( e ) {
				e.preventDefault();

				const parentSlug = this.getAttribute( 'data-parent' );
				const subMenuSlug = this.getAttribute( 'data-slug' );

				// Set active state for submenu item
				document.querySelectorAll( '.sync-submenu-item' ).forEach( function( item ) {
					item.classList.remove( 'sync-active' );
				} );
				this.parentElement.classList.add( 'sync-active' );

				// Update page title
				if ( pageTitle ) {
					const menuText = this.closest( '.sync-menu-item' ).querySelector( '.sync-menu-text' ).textContent;
					const submenuText = this.textContent;
					pageTitle.textContent = `${menuText} - ${submenuText}`;
				}

				// Load content for this submenu
				loadSubmenuContent(parentSlug, subMenuSlug);
			} );
		} );

		/**
		 * Load content for a main menu item
		 * @param {string} menuSlug - The slug of the menu to load
		 */
		function loadMenuContent(menuSlug) {
			const template = document.getElementById( `sync-page-${menuSlug}` );
			
			if (template) {
				// Fade out current content
				fadeOut(contentContainer, 200, function() {
					// Replace content with template content
					contentContainer.innerHTML = template.innerHTML;
					
					// Reinitialize components in the new content
					setupDismissibleCards();
					setupToggleSwitches();
					setupRefreshButton();
					setupActionButtons();
					
					// Fade in new content
					contentContainer.style.opacity = '0';
					contentContainer.style.display = '';
					fadeIn(contentContainer, 200);
				});
			} else if (menuSlug === 'dashboard') {
				// Dashboard is already loaded by default
				// Just ensure it's visible
				fadeOut(contentContainer, 200, function() {
					// The server already rendered the dashboard content,
					// but if it wasn't visible, make it visible now
					contentContainer.style.opacity = '0';
					contentContainer.style.display = '';
					fadeIn(contentContainer, 200);
				});
			}
		}

		/**
		 * Load content for a submenu item
		 * @param {string} parentSlug - The slug of the parent menu
		 * @param {string} subMenuSlug - The slug of the submenu to load
		 */
		function loadSubmenuContent(parentSlug, subMenuSlug) {
			const template = document.getElementById( `sync-subpage-${parentSlug}-${subMenuSlug}` );
			
			if (template) {
				// Fade out current content
				fadeOut(contentContainer, 200, function() {
					// Replace content with template content
					contentContainer.innerHTML = template.innerHTML;
					
					// Reinitialize components in the new content
					setupDismissibleCards();
					setupToggleSwitches();
					setupRefreshButton();
					setupActionButtons();
					
					// Fade in new content
					contentContainer.style.opacity = '0';
					contentContainer.style.display = '';
					fadeIn(contentContainer, 200);
				});
			}
		}
	}

	/**
	 * Dismissible cards
	 */
	function setupDismissibleCards() {
		const dismissIcons = document.querySelectorAll( '.sync-dismiss-icon' );

		dismissIcons.forEach( function( icon ) {
			icon.addEventListener( 'click', function() {
				// Find the parent card and remove it with animation
				const card = this.closest( '.sync-card' );
				fadeOut( card, 300, function() {
					card.remove();
				} );

				// Here you could also store the dismissed state in localStorage or send to the server
				const cardId = card.dataset.id || 'welcome-card';
				localStorage.setItem( 'sync-dismissed-' + cardId, 'true' );
			} );
		} );

		// Check for previously dismissed cards
		document.querySelectorAll( '.sync-card' ).forEach( function( card ) {
			const cardId = card.dataset.id || 'welcome-card';
			if ( 'true' === localStorage.getItem( 'sync-dismissed-' + cardId ) ) {
				card.style.display = 'none';
			}
		} );
	}

	/**
	 * Toggle switches
	 */
	function setupToggleSwitches() {
		const toggles = document.querySelectorAll( '.sync-toggle input[type="checkbox"]' );

		// Toggle switch change handler
		toggles.forEach( function( toggle ) {
			toggle.addEventListener( 'change', function() {
				const isChecked = this.checked;
				const toggleId = this.id;

				// Here you can handle specific toggle logic
				if ( 'sync-analytics-toggle' === toggleId ) {
					if ( isChecked ) {
						console.log( 'Analytics tracking enabled' );
						// You could trigger an AJAX call here to update the setting on the server
					} else {
						console.log( 'Analytics tracking disabled' );
					}
				}
			} );
		} );

		// Initialize toggle states from saved settings
		// This is a placeholder - in a real implementation, you'd get these from WordPress options
		const analyticsToggle = document.getElementById( 'sync-analytics-toggle' );
		if ( analyticsToggle ) {
			analyticsToggle.checked = false;
		}
	}

	/**
	 * Refresh button
	 */
	function setupRefreshButton() {
		const refreshBtn = document.querySelector( '.sync-refresh-btn' );

		if ( refreshBtn ) {
			refreshBtn.addEventListener( 'click', function() {
				const originalText = this.innerHTML;

				// Show loading state
				this.innerHTML = '<span class="dashicons dashicons-update sync-spin"></span> Refreshing...';
				this.disabled = true;

				// Simulate AJAX request
				setTimeout( () => {
					// Restore button state
					this.innerHTML = originalText;
					this.disabled = false;

					// Show success message
					const message = document.createElement( 'div' );
					message.className = 'sync-message sync-message-success';
					message.textContent = 'Information refreshed successfully!';
					this.insertAdjacentElement( 'afterend', message );

					// Remove message after delay
					setTimeout( () => {
						fadeOut( message, 300, function() {
							message.remove();
						} );
					}, 3000 );
				}, 1500 );
			} );
		}
	}

	/**
	 * Action buttons
	 */
	function setupActionButtons() {
		const actionButtons = document.querySelectorAll( '.sync-action-button' );

		actionButtons.forEach( function( button ) {
			button.addEventListener( 'click', function() {
				const originalText = this.innerHTML;

				// Show loading state
				this.innerHTML = '<span class="dashicons dashicons-update sync-spin"></span> Processing...';
				this.disabled = true;

				// Simulate AJAX request
				setTimeout( () => {
					// Restore button state
					this.innerHTML = originalText;
					this.disabled = false;

					// Show success message
					const actionGroup = this.closest( '.sync-action-group' );
					const message = document.createElement( 'div' );
					message.className = 'sync-message sync-message-success';
					message.textContent = 'Operation completed successfully!';
					actionGroup.appendChild( message );

					// Remove message after delay
					setTimeout( () => {
						fadeOut( message, 300, function() {
							message.remove();
						} );
					}, 3000 );
				}, 2000 );
			} );
		} );
	}

	/**
	 * Helper function to fade out an element
	 * @param {Element} element - The DOM element to fade out
	 * @param {number} duration - The duration of the fade in milliseconds
	 * @param {Function} callback - Callback function to run after fade completes
	 */
	function fadeOut( element, duration, callback ) {
		element.style.opacity = 1;

		const start = performance.now();

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
	 * @param {Element} element - The DOM element to fade in
	 * @param {number} duration - The duration of the fade in milliseconds
	 * @param {Function} callback - Callback function to run after fade completes
	 */
	function fadeIn( element, duration, callback ) {
		element.style.opacity = 0;
		element.style.display = '';

		const start = performance.now();

		function animate( time ) {
			const elapsed = time - start;
			const opacity = Math.min( elapsed / duration, 1 );

			element.style.opacity = opacity;

			if ( elapsed < duration ) {
				requestAnimationFrame( animate );
			} else {
				if ( 'function' === typeof callback ) {
					callback();
				}
			}
		}

		requestAnimationFrame( animate );
	}

	/**
	 * Add CSS animation for spinner
	 */
	function addSpinnerStyles() {
		const style = document.createElement( 'style' );
		style.type = 'text/css';
		style.innerHTML = `
		.sync-spin {
			animation: sync-spin 2s linear infinite;
		}
		@keyframes sync-spin {
			100% {
				transform: rotate(360deg);
			}
		}
		.sync-message {
			margin-top: 10px;
			padding: 8px 12px;
			border-radius: 3px;
			font-size: 13px;
		}
		.sync-message-success {
			background-color: #f0f9e6;
			color: #46b450;
			border-left: 3px solid #46b450;
		}
		`;
		document.head.appendChild( style );
	}
} );