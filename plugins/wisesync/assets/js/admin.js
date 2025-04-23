/**
 * Sync Dashboard JavaScript - Vanilla JS version
 */
document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	// Initialize when document is ready
	syncInitDashboard();
} );

/**
 * Initialize the sync dashboard
 */
function syncInitDashboard() {
	syncInitMenuHandler();
	syncInitTabHandler();
}

/**
 * Handle menu clicks and submenu toggles
 */
function syncInitMenuHandler() {
	// Handle main menu item clicks
	const menuItems = document.querySelectorAll( '.sync-menu-item:not(.sync-has-submenu)' );
	menuItems.forEach( function( menuItem ) {
		menuItem.addEventListener( 'click', function() {
			syncActivateMenuItem( this );
		} );
	} );

	// Handle submenu toggle
	const submenuToggles = document.querySelectorAll( '.sync-has-submenu > .sync-menu-item-header' );
	submenuToggles.forEach( function( toggle ) {
		toggle.addEventListener( 'click', function() {
			const menuItem = this.parentNode;

			// Toggle submenu expanded class
			menuItem.classList.toggle( 'sync-submenu-expanded' );

			// Toggle submenu visibility
			const submenu = menuItem.querySelector( '.sync-submenu' );
			if ( 'block' === submenu.style.display ) {
				submenu.style.display = 'none';
			} else {
				submenu.style.display = 'block';
			}

			// If this menu wasn't active already and a submenu item isn't active
			if ( ! menuItem.classList.contains( 'sync-active' ) &&
                0 === menuItem.querySelectorAll( '.sync-submenu-item.sync-active' ).length ) {
				syncResetActiveMenu();
				menuItem.classList.add( 'sync-active' );
			}
		} );
	} );

	// Handle submenu item clicks
	const submenuItems = document.querySelectorAll( '.sync-submenu-item' );
	submenuItems.forEach( function( item ) {
		item.addEventListener( 'click', function( e ) {
			e.stopPropagation(); // Prevent bubbling to parent menu item

			// Activate this submenu item
			syncResetActiveMenu();
			this.classList.add( 'sync-active' );

			// Also activate parent menu
			const parentMenu = this.closest( '.sync-has-submenu' );
			if ( parentMenu ) {
				parentMenu.classList.add( 'sync-active' );
			}

			// Show tab content
			const tabId = this.getAttribute( 'data-tab' );
			syncShowTabContent( tabId );
		} );
	} );
}

/**
 * Activate menu item and show corresponding tab
 * @param menuItem
 */
function syncActivateMenuItem( menuItem ) {
	syncResetActiveMenu();
	menuItem.classList.add( 'sync-active' );

	const tabId = menuItem.getAttribute( 'data-tab' );
	syncShowTabContent( tabId );
}

/**
 * Reset active state on all menu items
 */
function syncResetActiveMenu() {
	const activeItems = document.querySelectorAll( '.sync-menu-item.sync-active, .sync-submenu-item.sync-active' );
	activeItems.forEach( function( item ) {
		item.classList.remove( 'sync-active' );
	} );
}

/**
 * Handle tab switching
 */
function syncInitTabHandler() {
	// Show default tab (first one)
	const tabContents = document.querySelectorAll( '.sync-tab-content' );
	const activeTab = document.querySelector( '.sync-tab-content.sync-tab-active' );

	if ( 0 < tabContents.length && ! activeTab ) {
		tabContents[ 0 ].classList.add( 'sync-tab-active' );
	}
}

/**
 * Show specific tab content
 * @param tabId
 */
function syncShowTabContent( tabId ) {
	// Hide all tabs
	const allTabs = document.querySelectorAll( '.sync-tab-content' );
	allTabs.forEach( function( tab ) {
		tab.classList.remove( 'sync-tab-active' );
	} );

	// Show selected tab
	const targetTab = document.getElementById( 'sync-' + tabId );
	if ( targetTab ) {
		targetTab.classList.add( 'sync-tab-active' );
	}
}

/**
 * Example function to fetch data for dashboard
 * (This would be connected to your AJAX endpoints)
 */
function syncFetchDashboardData() {
	// Create a new XMLHttpRequest
	const xhr = new XMLHttpRequest();

	xhr.open( 'POST', ajaxurl, true ); // ajaxurl is provided by WordPress
	xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );

	xhr.onload = function() {
		if ( 200 <= xhr.status && 300 > xhr.status ) {
			try {
				const response = JSON.parse( xhr.responseText );
				if ( response.success ) {
					// Update dashboard with data
					syncUpdateDashboardStats( response.data );
				}
			} catch ( e ) {
				console.error( 'Error parsing AJAX response:', e );
			}
		}
	};

	xhr.onerror = function() {
		console.error( 'Error fetching dashboard data' );
	};

	// Prepare data and send request
	const data = 'action=sync_get_dashboard_data&nonce=' + syncDashboardVars.nonce;
	xhr.send( data );
}

/**
 * Update dashboard statistics with fetched data
 * @param data
 */
function syncUpdateDashboardStats( data ) {
	// Example - you would customize this based on your data structure
	const statNumbers = document.querySelectorAll( '.sync-stat-number' );

	if ( data.totalSyncs && statNumbers[ 0 ] ) {
		statNumbers[ 0 ].textContent = data.totalSyncs;
	}

	if ( data.lastSync && statNumbers[ 1 ] ) {
		statNumbers[ 1 ].textContent = data.lastSync;
	}

	if ( data.successRate && statNumbers[ 2 ] ) {
		statNumbers[ 2 ].textContent = data.successRate + '%';
	}
}

/**
 * Handle form submissions
 */
function syncInitFormHandlers() {
	const settingsForm = document.querySelector( '.sync-settings-form' );
	if ( settingsForm ) {
		settingsForm.addEventListener( 'submit', function( e ) {
			e.preventDefault();

			// Serialize form data
			const formData = new FormData( this );
			const serialized = new URLSearchParams( formData ).toString();

			// AJAX request
			const xhr = new XMLHttpRequest();
			xhr.open( 'POST', ajaxurl, true );
			xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );

			xhr.onload = function() {
				if ( 200 <= xhr.status && 300 > xhr.status ) {
					try {
						const response = JSON.parse( xhr.responseText );
						if ( response.success ) {
							syncShowNotification( 'Settings saved successfully!', 'success' );
						} else {
							syncShowNotification( 'Error saving settings.', 'error' );
						}
					} catch ( e ) {
						syncShowNotification( 'Error processing server response.', 'error' );
					}
				}
			};

			xhr.onerror = function() {
				syncShowNotification( 'Server error while saving settings.', 'error' );
			};

			// Prepare data and send request
			const data = 'action=sync_save_settings&formData=' + encodeURIComponent( serialized ) + '&nonce=' + syncDashboardVars.nonce;
			xhr.send( data );
		} );
	}
}

/**
 * Show notification toast
 * @param message
 * @param type
 */
function syncShowNotification( message, type ) {
	// Create notification element
	const notification = document.createElement( 'div' );
	notification.className = 'sync-notification sync-notification-' + type;
	notification.textContent = message;

	// Append to body
	document.body.appendChild( notification );

	// Animate in (use setTimeout to ensure DOM update before animation)
	setTimeout( function() {
		notification.classList.add( 'sync-notification-show' );
	}, 10 );

	// Remove after delay
	setTimeout( function() {
		notification.classList.remove( 'sync-notification-show' );
		setTimeout( function() {
			notification.remove();
		}, 300 );
	}, 3000 );
}

/**
 * Helper function to serialize form data
 * @param form
 */
function syncSerializeForm( form ) {
	const serialized = [];
	for ( let i = 0; i < form.elements.length; i++ ) {
		const field = form.elements[ i ];
		if ( ! field.name || field.disabled || 'file' === field.type || 'reset' === field.type || 'submit' === field.type || 'button' === field.type ) {
			continue;
		}

		if ( 'select-multiple' === field.type ) {
			for ( let n = 0; n < field.options.length; n++ ) {
				if ( ! field.options[ n ].selected ) {
					continue;
				}
				serialized.push( encodeURIComponent( field.name ) + '=' + encodeURIComponent( field.options[ n ].value ) );
			}
		} else if ( ( 'checkbox' !== field.type && 'radio' !== field.type ) || field.checked ) {
			serialized.push( encodeURIComponent( field.name ) + '=' + encodeURIComponent( field.value ) );
		}
	}

	return serialized.join( '&' );
}
