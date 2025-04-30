/**
 * Admin Dashboard JavaScript using the Event.js delegation system
 */

document.addEventListener('DOMContentLoaded', function () {
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
		registerEvent('sync-hash-change', 'hashchange', {}, function () {
			handleUrlHash();
		});

		registerEvent(
			'sync-settings-single-ajax-form-submit',
			'input',
			{ selector: '.sync-single-form input' },
			function (e, data) {
				console.log('Single form input changed:', data.targetElement);
				console.log('New value:', data.targetElement.value);

				// get input form selector
				const form = data.targetElement.closest('.sync-single-form form');
				console.log('Form:', form);
				console.log('Form ID:', form.id);
				console.log('Form action:', form.action);
				console.log('Form method:', form.method);
				console.log('Form data:', new FormData(form));

				// Get nonce.
				const nonce = form.querySelector('input[name="sync_nonce"]');
				if (nonce) {
					console.log('Nonce:', nonce.value);
				}
			}
		);

		// registerEvent(
		// 	'sync-settings-each-ajax-form-submit'
		// );
	}

	function ajaxRequest(action, nonce, data, method = 'POST') {
		const contructedData = {
			action,
			nonce,
		}
		fetch(window.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			data: contructedData,
		}).then((response) => {});
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
			function (e, data) {
				const sidebar = document.getElementById('sync-sidebar');
				sidebar.classList.toggle('sync-mobile-open');
			}
		);

		// Close mobile menu when clicking outside
		registerEvent('sync-mobile-outside-click', 'click', {}, function (e, data) {
			const sidebar = document.getElementById('sync-sidebar');
			if (sidebar && sidebar.classList.contains('sync-mobile-open')) {
				const clickedInside =
					e.target.closest('#sync-sidebar') || e.target.closest('#sync-mobile-toggle');
				if (!clickedInside) {
					sidebar.classList.remove('sync-mobile-open');
				}
			}
		});
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
			function (e, data) {
				e.preventDefault();

				const link = data.targetElement;
				const menuItem = link.parentElement;
				const submenu = menuItem.querySelector('.sync-submenu');
				const menuSlug = link.getAttribute('data-slug');
				const href = link.getAttribute('href');
				const pageTitle = document.querySelector('.sync-page-title');

				// If this item has a submenu
				if (submenu) {
					// Toggle active state
					if (menuItem.classList.contains('sync-active')) {
						menuItem.classList.remove('sync-active');
					} else {
						// Remove active class from siblings
						document.querySelectorAll('.sync-menu-item.sync-active').forEach(function (activeItem) {
							activeItem.classList.remove('sync-active');
						});
						menuItem.classList.add('sync-active');
					}
				} else {
					// If no submenu, simply set this item as active
					document.querySelectorAll('.sync-menu-item').forEach(function (item) {
						item.classList.remove('sync-active');
					});
					menuItem.classList.add('sync-active');

					// Update page title
					if (pageTitle) {
						const menuText = link.querySelector('.sync-menu-text').textContent;
						pageTitle.textContent = menuText;
					}

					// Update URL hash without triggering hashchange event
					history.pushState(null, '', href);

					// Load content for this menu
					loadMenuContent(menuSlug);
				}
			}
		);

		// Handle submenu clicks
		registerEvent(
			'sync-submenu-link-click',
			'click',
			{ selector: '.sync-submenu-link' },
			function (e, data) {
				e.preventDefault();

				const link = data.targetElement;
				const parentSlug = link.getAttribute('data-parent');
				const subMenuSlug = link.getAttribute('data-slug');
				const href = link.getAttribute('href');
				const pageTitle = document.querySelector('.sync-page-title');

				// Set active state for parent menu item
				document.querySelectorAll('.sync-menu-item').forEach(function (item) {
					item.classList.remove('sync-active');
				});
				link.closest('.sync-menu-item').classList.add('sync-active');

				// Set active state for submenu item
				document.querySelectorAll('.sync-submenu-item').forEach(function (item) {
					item.classList.remove('sync-active');
				});
				link.parentElement.classList.add('sync-active');

				// Update page title
				if (pageTitle) {
					const menuText = link
						.closest('.sync-menu-item')
						.querySelector('.sync-menu-text').textContent;
					const submenuText = link.textContent;
					pageTitle.textContent = `${menuText} - ${submenuText}`;
				}

				// Update URL hash
				history.pushState(null, '', href);

				// Load content for this submenu
				loadSubmenuContent(parentSlug, subMenuSlug);
			}
		);

		// Initial load of default content if no hash in URL
		if (!window.location.hash) {
			const defaultMenuLink = document.querySelector('.sync-menu-link');
			if (defaultMenuLink) {
				const defaultMenuSlug = defaultMenuLink.getAttribute('data-slug');
				loadMenuContent(defaultMenuSlug);
			}
		}
	}

	/**
	 * Handle URL hash on page load to navigate to specific menu/submenu
	 */
	function handleUrlHash() {
		if (window.location.hash) {
			const hash = window.location.hash.substring(1); // Remove the # character

			// Check if hash is for a submenu (format: sync-parent-child)
			if (2 < hash.split('-').length) {
				const parts = hash.split('-');
				// Remove 'sync-' prefix and get the parent and child slugs
				const parentSlug = parts[1];
				const childSlug = parts.slice(2).join('-'); // In case child slug has hyphens

				// Set active states
				const menuItem = document.querySelector(
					`.sync-menu-link[data-slug="${parentSlug}"]`
				).parentElement;
				menuItem.classList.add('sync-active');

				const submenuItem = document.querySelector(
					`.sync-submenu-link[data-parent="${parentSlug}"][data-slug="${childSlug}"]`
				);
				if (submenuItem) {
					submenuItem.parentElement.classList.add('sync-active');

					// Update page title
					const pageTitle = document.querySelector('.sync-page-title');
					if (pageTitle) {
						const menuText = menuItem.querySelector('.sync-menu-text').textContent;
						const submenuText = submenuItem.textContent;
						pageTitle.textContent = `${menuText} - ${submenuText}`;
					}

					// Load submenu content
					loadSubmenuContent(parentSlug, childSlug);
				}
			} else if (hash.startsWith('sync-')) {
				const menuSlug = hash.replace('sync-', '');
				const menuLink = document.querySelector(`.sync-menu-link[data-slug="${menuSlug}"]`);

				if (menuLink) {
					// Set active state
					document.querySelectorAll('.sync-menu-item').forEach(function (item) {
						item.classList.remove('sync-active');
					});
					menuLink.parentElement.classList.add('sync-active');

					// Update page title
					const pageTitle = document.querySelector('.sync-page-title');
					if (pageTitle) {
						const menuText = menuLink.querySelector('.sync-menu-text').textContent;
						pageTitle.textContent = menuText;
					}

					// Load menu content
					loadMenuContent(menuSlug);
				}
			}
		} else {
			// No hash in URL, load default menu content
			const defaultMenuLink = document.querySelector('.sync-menu-link');
			if (defaultMenuLink) {
				const defaultMenuSlug = defaultMenuLink.getAttribute('data-slug');
				loadMenuContent(defaultMenuSlug);
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
			function (e, data) {
				// Find the parent card and remove it with animation
				const card = data.targetElement.closest('.sync-card');
				fadeOut(card, 300, function () {
					card.remove();
				});

				// Store the dismissed state in localStorage
				const cardId = card.dataset.id || 'welcome-card';
				localStorage.setItem('sync-dismissed-' + cardId, 'true');
			}
		);

		// Check for previously dismissed cards
		document.querySelectorAll('.sync-card').forEach(function (card) {
			const cardId = card.dataset.id || 'welcome-card';
			if ('true' === localStorage.getItem('sync-dismissed-' + cardId)) {
				card.style.display = 'none';
			}
		});
	}

	/**
	 * Load content for a main menu item
	 * @param {string} menuSlug - The slug of the menu to load
	 */
	function loadMenuContent(menuSlug) {
		const template = document.getElementById(`sync-page-${menuSlug}`);
		const contentContainer = document.getElementById('sync-dynamic-content');

		if (template) {
			// Fade out current content
			fadeOut(contentContainer, 200, function () {
				// Replace content with template content
				contentContainer.innerHTML = template.innerHTML;

				// Reinitialize components in the new content
				setupDismissibleCards();

				// Fade in new content
				contentContainer.style.opacity = '0';
				contentContainer.style.display = '';
				fadeIn(contentContainer, 200);
			});
		}
	}

	/**
	 * Load content for a submenu item
	 * @param {string} parentSlug  - The slug of the parent menu
	 * @param {string} subMenuSlug - The slug of the submenu to load
	 */
	function loadSubmenuContent(parentSlug, subMenuSlug) {
		const template = document.getElementById(`sync-subpage-${parentSlug}-${subMenuSlug}`);
		const contentContainer = document.getElementById('sync-dynamic-content');

		if (template) {
			// Fade out current content
			fadeOut(contentContainer, 200, function () {
				// Replace content with template content
				contentContainer.innerHTML = template.innerHTML;

				// Reinitialize components in the new content
				setupDismissibleCards();

				// Fade in new content
				contentContainer.style.opacity = '0';
				contentContainer.style.display = '';
				fadeIn(contentContainer, 200);
			});
		}
	}

	/**
	 * Helper function to fade out an element
	 * @param {Element}  element  - The DOM element to fade out
	 * @param {number}   duration - The duration of the fade in milliseconds
	 * @param {Function} callback - Callback function to run after fade completes
	 */
	function fadeOut(element, duration, callback) {
		element.style.opacity = 1;

		const start = performance.now();

		/**
		 * Animate the fade out effect
		 *
		 * @param {number} time - The current time
		 */
		function animate(time) {
			const elapsed = time - start;
			const opacity = 1 - Math.min(elapsed / duration, 1);

			element.style.opacity = opacity;

			if (elapsed < duration) {
				requestAnimationFrame(animate);
			} else {
				element.style.display = 'none';
				if ('function' === typeof callback) {
					callback();
				}
			}
		}

		requestAnimationFrame(animate);
	}

	/**
	 * Helper function to fade in an element
	 * @param {Element}  element  - The DOM element to fade in
	 * @param {number}   duration - The duration of the fade in milliseconds
	 * @param {Function} callback - Callback function to run after fade completes
	 */
	function fadeIn(element, duration, callback) {
		element.style.opacity = 0;
		element.style.display = '';

		const start = performance.now();

		/**
		 * Animate the fade in effect.
		 *
		 * @param {number} time - The current time.
		 */
		function animate(time) {
			const elapsed = time - start;
			const opacity = Math.min(elapsed / duration, 1);

			element.style.opacity = opacity;

			if (elapsed < duration) {
				requestAnimationFrame(animate);
			} else if ('function' === typeof callback) {
				callback();
			}
		}

		requestAnimationFrame(animate);
	}
});
