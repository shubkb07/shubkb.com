/**
 * SyncCache Optimization JavaScript
 *
 * Handles lazy loading of images and other optimization features.
 *
 * @param $
 * @package
 */

( function( $ ) {
	'use strict';

	// Lazy loading function.
	/**
	 *
	 */
	function initLazyLoad() {
		// Handle images with loading="lazy" attribute.
		if ( 'loading' in HTMLImageElement.prototype ) {
			// Browser supports native lazy loading.
			const images = document.querySelectorAll( 'img.sync-lazy' );
			images.forEach( ( img ) => {
				// Set loading attribute if not already set.
				if ( ! img.hasAttribute( 'loading' ) ) {
					img.setAttribute( 'loading', 'lazy' );
				}
			} );
		} else {
			// Fallback for browsers that don't support native lazy loading.
			const lazyImages = document.querySelectorAll( 'img.sync-lazy' );

			// Create an intersection observer.
			const imageObserver = new IntersectionObserver( ( entries, observer ) => {
				entries.forEach( ( entry ) => {
					if ( entry.isIntersecting ) {
						const image = entry.target;

						// If the image has a data-src attribute, use it.
						if ( image.dataset.src ) {
							image.src = image.dataset.src;
						}

						// If the image has a data-srcset attribute, use it.
						if ( image.dataset.srcset ) {
							image.srcset = image.dataset.srcset;
						}

						// Remove lazy loading class.
						image.classList.remove( 'sync-lazy' );

						// Stop observing this image.
						observer.unobserve( image );
					}
				} );
			} );

			// Observe each lazy image.
			lazyImages.forEach( ( image ) => {
				imageObserver.observe( image );
			} );
		}
	}

	// Initialize when the DOM is ready.
	$( document ).ready( function() {
		// Initialize lazy loading.
		initLazyLoad();
	} );

}( jQuery ) );
