/**
 * A global, delegated event manager that supports registration and deregistration
 * of named handlers with rich condition matching and callback data.
 */

const eventList = {};
const htmlDocument = document.getElementsByTagName( 'html' )[ 0 ];

// Commonly used event types
const validEventTypes = new Set( [
	'click',
	'dblclick',
	'mousedown',
	'mouseup',
	'mousemove',
	'mouseover',
	'mouseout',
	'mouseenter',
	'mouseleave',
	'keydown',
	'keyup',
	'keypress',
	'input',
	'change',
	'submit',
	'focus',
	'blur',
	'scroll',
	'resize',
	'wheel',
	'touchstart',
	'touchmove',
	'touchend',
	'touchcancel',
] );

/**
 * Checks if the provided event type is valid.
 *
 * @param {string} eventType The DOM event type to validate
 * @return {boolean} True if the event type is valid, false otherwise
 */
function isValidEventType( eventType ) {
	return validEventTypes.has( eventType.toLowerCase() );
}

/**
 * Register a named event handler.
 * @param {string}   eventName Unique name for this handler
 * @param {string}   eventType DOM event type (e.g. 'click', 'scroll', 'input')
 * @param {Object}   condition Matching conditions (selector, key/value, thresholds)
 * @param {Function} callback  Function(e, data) to invoke when matched
 * @return {void}
 */
function registerEvent( eventName, eventType, condition, callback ) { // eslint-disable-line no-unused-vars
	if ( 'string' !== typeof eventName || ! eventName.trim() ) {
		throw new Error( 'registerEvent: eventName must be a non-empty string.' );
	}
	const type = eventType.toLowerCase();
	if ( ! isValidEventType( type ) ) {
		throw new Error( `registerEvent: "${ eventType }" is not supported.` );
	}
	if ( 'object' !== typeof condition || null === condition ) {
		throw new Error( 'registerEvent: condition must be an object.' );
	}
	if ( 'function' !== typeof callback ) {
		throw new Error( 'registerEvent: callback must be a function.' );
	}

	// Initialize global listener for this type if first time
	if ( ! eventList[ type ] ) {
		const handlers = {};
		let ticking = false;

		/**
		 * Process an event by checking all handlers against their conditions.
		 *
		 * @param {Event} e The DOM event object
		 */
		function processEvent( e ) {
			const target = e.target;

			Object.entries( handlers ).forEach( ( [ name, handler ] ) => {
				let matched = false;
				let conditionMatchWith = null;

				// Base data object
				const data = {
					eventType: type,
					eventName: name,
					triggeredCount: handler.triggeredCount,
					targetElement: null,
				};

				// GENERIC HTML data for any element
				if ( target && 'innerHTML' in target ) {
					data.htmlData = {
						innerHTML: target.innerHTML,
						innerText: target.innerText,
					};
				}

				// --- CLICK & MOUSE EVENTS ---
				if (
					[
						'click',
						'dblclick',
						'mousedown',
						'mouseup',
						'mousemove',
						'mouseover',
						'mouseout',
						'mouseenter',
						'mouseleave',
					].includes( type )
				) {
					// selector match
					if ( handler.condition.selector ) {
						const el = target.closest( handler.condition.selector );
						if ( el ) {
							matched = true;
							conditionMatchWith = handler.condition.selector;
							data.targetElement = el;
						}
					} else {
						matched = true;
						data.targetElement = target;
					}
					if ( matched ) {
						data.mouseData = {
							clientX: e.clientX,
							clientY: e.clientY,
							pageX: e.pageX,
							pageY: e.pageY,
							screenX: e.screenX,
							screenY: e.screenY,
							button: e.button,
						};
					}
				} else if ( [ 'keydown', 'keyup', 'keypress' ].includes( type ) ) {
					matched = Object.entries( handler.condition ).every(
						( [ k, v ] ) => e[ k ] === v
					);
					data.keyData = {
						key: e.key,
						code: e.code,
						keyCode: e.keyCode,
						charCode: e.charCode,
						altKey: e.altKey,
						ctrlKey: e.ctrlKey,
						metaKey: e.metaKey,
						shiftKey: e.shiftKey,
					};
				} else if ( [ 'input', 'change' ].includes( type ) ) {
					// selector match
					if ( handler.condition.selector ) {
						const el = target.closest( handler.condition.selector );
						if ( el ) {
							matched = true;
							conditionMatchWith = handler.condition.selector;
							data.targetElement = el;
						}
					} else {
						matched = true;
						data.targetElement = target;
					}
					if ( matched ) {
						data.inputData = {
							value: target.value,
							checked: target.checked,
						};
					}
				} else if ( [ 'focus', 'blur' ].includes( type ) ) {
					if ( handler.condition.selector ) {
						const el = target.closest( handler.condition.selector );
						if ( el ) {
							matched = true;
							conditionMatchWith = handler.condition.selector;
							data.targetElement = el;
						}
					} else {
						matched = true;
						data.targetElement = target;
					}
					if ( matched ) {
						data.focusData = {
							id: target.id,
							name: target.name,
							value: target.value,
						};
					}
				} else if ( 'submit' === type ) {
					if ( handler.condition.selector ) {
						const form = target.closest( handler.condition.selector );
						if ( form ) {
							matched = true;
							conditionMatchWith = handler.condition.selector;
							data.targetElement = form;
						}
					} else if ( 'FORM' === target.tagName ) {
						matched = true;
						data.targetElement = target;
					}
					if ( matched ) {
						data.formData = {
							id: data.targetElement.id,
							name: data.targetElement.name,
						};
					}
				} else if ( 'scroll' === type ) {
					const docEl = document.documentElement;
					const scrollTop = docEl.scrollTop;
					const scrollHeight = docEl.scrollHeight;
					const clientHeight = docEl.clientHeight;
					const percentage = ( scrollTop / ( scrollHeight - clientHeight ) ) * 100;
					data.scrollData = {
						scrollTop,
						scrollHeight,
						clientHeight,
						percentage,
						isVertical: true,
					};

					matched = true;
					if ( 'number' === typeof handler.condition.percentage ) {
						matched = percentage >= handler.condition.percentage;
						conditionMatchWith = handler.condition.percentage;
					}
					if ( 'number' === typeof handler.condition.height ) {
						matched = matched && scrollTop >= handler.condition.height;
						conditionMatchWith = handler.condition.height;
					}
				} else if ( 'wheel' === type ) {
					matched = true;
					data.wheelData = {
						deltaX: e.deltaX,
						deltaY: e.deltaY,
						deltaZ: e.deltaZ,
						deltaMode: e.deltaMode,
					};
				} else if (
					[ 'touchstart', 'touchmove', 'touchend', 'touchcancel' ].includes( type )
				) {
					matched = true;
					data.touchData = {
						touches: e.touches.length,
						firstTouch: e.touches[ 0 ]
							? {
								identifier: e.touches[ 0 ].identifier,
								clientX: e.touches[ 0 ].clientX,
								clientY: e.touches[ 0 ].clientY,
								pageX: e.touches[ 0 ].pageX,
								pageY: e.touches[ 0 ].pageY,
								screenX: e.touches[ 0 ].screenX,
								screenY: e.touches[ 0 ].screenY,
							}
							: null,
					};
				} else {
					matched = true;
				}

				if ( matched ) {
					handler.triggeredCount += 1;
					data.triggeredCount = handler.triggeredCount;
					data.conditionMatchWith = conditionMatchWith;

					try {
						handler.callback( e, data );
					} catch ( err ) {
						// Log error in callback
						// eslint-disable-next-line no-console
						console.error( `Error in "${ name }" callback:`, err );
					}
				}
			} );
		}

		/**
		 * Single listener with rAF throttle for scroll
		 *
		 * @param {Event} e The DOM event object to process
		 */
		const listener = ( e ) => {
			if ( 'scroll' === type ) {
				if ( ! ticking ) {
					window.requestAnimationFrame( () => {
						processEvent( e );
						ticking = false;
					} );
					ticking = true;
				}
			} else {
				processEvent( e );
			}
		};

		const targetEl = 'scroll' === type ? window : htmlDocument;
		targetEl.addEventListener( type, listener, false );

		eventList[ type ] = { listener, handlers };
	}

	// Store the handler
	eventList[ type ].handlers[ eventName ] = {
		condition,
		callback,
		triggeredCount: 0,
	};
}

/**
 * Deregister and cleanup a named event handler.
 *
 * @param {string} eventName The unique name of the event handler to deregister
 * @return {void}
 * @throws {Error} If the eventName is not a non-empty string or no handler is found
 */
function deRegisterEvent( eventName ) { // eslint-disable-line no-unused-vars
	if ( 'string' !== typeof eventName || ! eventName.trim() ) {
		throw new Error( 'deRegisterEvent: eventName must be a non-empty string.' );
	}
	for ( const [ type, { listener, handlers } ] of Object.entries( eventList ) ) {
		if ( handlers[ eventName ] ) {
			delete handlers[ eventName ];
			// Remove listener when no more handlers for this eventType
			if ( 0 === Object.keys( handlers ).length ) {
				const targetEl = 'scroll' === type ? window : htmlDocument;
				targetEl.removeEventListener( type, listener, false );
				delete eventList[ type ];
			}
			return;
		}
	}
	throw new Error( `deRegisterEvent: no handler found named "${ eventName }".` );
}
