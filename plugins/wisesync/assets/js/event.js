// Global registry of all delegated events by type
const eventList = {};

// The root element for delegation (except scrolls)
const htmlDocument = document.getElementsByTagName('html')[0];

// Supported DOM event types
const validEventTypes = new Set([
  'click','dblclick','mousedown','mouseup','mousemove',
  'mouseover','mouseout','mouseenter','mouseleave',
  'keydown','keyup','keypress',
  'submit','focus','blur','change','input',
  'scroll','resize'
]);

function isValidEventType(eventType) {
  return validEventTypes.has(eventType.toLowerCase());
}

const registerEvent = (eventName, eventType, condition, callback) => {
  if (typeof eventName !== 'string' || !eventName.trim()) {
    throw new Error('registerEvent: eventName must be a non-empty string.');
  }
  const type = eventType.toLowerCase();
  if (!isValidEventType(type)) {
    throw new Error(`registerEvent: "${eventType}" is not supported.`);
  }
  if (typeof condition !== 'object' || condition === null) {
    throw new Error('registerEvent: condition must be an object.');
  }
  if (typeof callback !== 'function') {
    throw new Error('registerEvent: callback must be a function.');
  }

  // First time seeing this eventType?  Create one global listener.
  if (!eventList[type]) {
    const handlers = {};
    let ticking = false;

    function processEvent(e) {
      const target = e.target;
      Object.entries(handlers).forEach(([name, handler]) => {
        let matched = false;
        let conditionMatchWith = null;
        const data = {
          eventType: type,
          eventName: name,
          triggeredCount: handler.triggeredCount,
          targetElement: null
        };

        // --- CLICK & MOUSE EVENTS ---
        if (type === 'click' || type.startsWith('mouse')) {
          if (handler.condition.selector) {
            const el = target.closest(handler.condition.selector);
            if (el) {
              matched = true;
              conditionMatchWith = handler.condition.selector;
              data.targetElement = el;
            }
          } else {
            matched = true;
            data.targetElement = target;
          }
        }

        // --- KEYBOARD EVENTS ---
        else if (['keydown','keyup','keypress'].includes(type)) {
          matched = Object.entries(handler.condition)
            .every(([k,v]) => e[k] === v);
          data.keyData = {
            key: e.key, code: e.code,
            altKey: e.altKey, ctrlKey: e.ctrlKey,
            metaKey: e.metaKey, shiftKey: e.shiftKey
          };
        }

        // --- SCROLL EVENTS ---
        else if (type === 'scroll') {
          const docEl = document.documentElement;
          const scrollTop = docEl.scrollTop;
          const scrollHeight = docEl.scrollHeight;
          const clientHeight = docEl.clientHeight;
          const percentage = (scrollTop / (scrollHeight - clientHeight)) * 100;
          data.scrollData = { scrollTop, scrollHeight, clientHeight, percentage, isVertical: true };

          matched = true;
          if (typeof handler.condition.percentage === 'number') {
            matched = percentage >= handler.condition.percentage;
            conditionMatchWith = handler.condition.percentage;
          }
          if (typeof handler.condition.height === 'number') {
            matched = matched && scrollTop >= handler.condition.height;
            conditionMatchWith = handler.condition.height;
          }
        }

        // --- OTHER EVENTS (always match) ---
        else {
          matched = true;
        }

        if (matched) {
          handler.triggeredCount += 1;
          data.triggeredCount = handler.triggeredCount;
          data.conditionMatchWith = conditionMatchWith;
          try {
            handler.callback(e, data);
          } catch (err) {
            console.error(`Error in "${name}" callback:`, err);
          }
        }
      });
    }

    // The actual listener, which throttles scrolls via requestAnimationFrame
    const listener = (e) => {
      if (type === 'scroll') {
        if (!ticking) {
          window.requestAnimationFrame(() => {
            processEvent(e);
            ticking = false;
          });
          ticking = true;
        }
      } else {
        processEvent(e);
      }
    };

    // Attach to window for scroll, htmlDocument for everything else
    const targetEl = type === 'scroll' ? window : htmlDocument;
    targetEl.addEventListener(type, listener, false);

    eventList[type] = { listener, handlers };
  }

  // Register (or overwrite) your named handler
  eventList[type].handlers[eventName] = {
    condition,
    callback,
    triggeredCount: 0
  };
};

const deRegisterEvent = (eventName) => {
  if (typeof eventName !== 'string' || !eventName.trim()) {
    throw new Error('deRegisterEvent: eventName must be a non-empty string.');
  }
  for (const [type, { listener, handlers }] of Object.entries(eventList)) {
    if (handlers[eventName]) {
      delete handlers[eventName];
      // If no more handlers for this type, tear down the listener
      if (Object.keys(handlers).length === 0) {
        const targetEl = type === 'scroll' ? window : htmlDocument;
        targetEl.removeEventListener(type, listener, false);
        delete eventList[type];
      }
      return;
    }
  }
  throw new Error(`deRegisterEvent: no handler found named "${eventName}".`);
};

registerEvent('click', 'click', { selector: '' }, (e, data) => {
	  console.log('Click event triggered:', data);
	  console.log('Click event triggered:', e);
});
