/**
 * Interactivity API runtime mock for Playwright E2E testing.
 *
 * Implements the core `@wordpress/interactivity` contract (store, getContext, getElement)
 * allowing native ESM store modules in theme/build/*.js to hydrate and react in headless Chrome.
 */

const stores = new Map();
let activeContext = {};
let activeElement = null;

export function getContext() {
	return activeContext;
}

export function getElement() {
	return { ref: activeElement };
}

export function store( namespace, config ) {
	let existing = stores.get( namespace );
	if ( ! existing ) {
		existing = { state: {}, actions: {}, callbacks: {} };
		stores.set( namespace, existing );
	}

	if ( config.state ) {
		Object.defineProperties(
			existing.state,
			Object.getOwnPropertyDescriptors( config.state )
		);
	}
	if ( config.actions ) {
		Object.defineProperties(
			existing.actions,
			Object.getOwnPropertyDescriptors( config.actions )
		);
	}
	if ( config.callbacks ) {
		Object.defineProperties(
			existing.callbacks,
			Object.getOwnPropertyDescriptors( config.callbacks )
		);
	}

	// Schedule hydration in microtask so store() finishes assignment before callbacks run
	if ( typeof document !== 'undefined' ) {
		queueMicrotask( () => {
			hydrateNamespace( namespace, existing );
		} );
	}

	return existing;
}

function resolvePath( obj, path ) {
	if ( ! obj || ! path ) {
		return undefined;
	}
	return path
		.split( '.' )
		.reduce(
			( acc, part ) =>
				acc !== null && acc !== undefined ? acc[ part ] : undefined,
			obj
		);
}

function updateBindings( root, storeDef, context ) {
	activeContext = context;
	activeElement = root;

	// 1. data-wp-text
	const textElements = [
		...( root.hasAttribute( 'data-wp-text' ) ? [ root ] : [] ),
		...Array.from( root.querySelectorAll( '[data-wp-text]' ) ),
	];
	textElements.forEach( ( el ) => {
		const expr = el.getAttribute( 'data-wp-text' );
		if ( expr ) {
			activeElement = el;
			const val = resolvePath( storeDef, expr );
			if ( val !== undefined && val !== null ) {
				el.textContent = String( val );
			}
		}
	} );

	// 2. data-wp-bind--[attr] and data-wp-class--[class]
	const allElements = [ root, ...Array.from( root.querySelectorAll( '*' ) ) ];
	allElements.forEach( ( el ) => {
		Array.from( el.attributes ).forEach( ( attr ) => {
			if ( attr.name.startsWith( 'data-wp-bind--' ) ) {
				const targetAttr = attr.name.replace( 'data-wp-bind--', '' );
				activeElement = el;
				const val = resolvePath( storeDef, attr.value );
				if ( targetAttr === 'hidden' ) {
					if ( val ) {
						el.setAttribute( 'hidden', '' );
					} else {
						el.removeAttribute( 'hidden' );
					}
				} else if (
					val === null ||
					val === undefined ||
					val === false
				) {
					el.removeAttribute( targetAttr );
				} else {
					el.setAttribute( targetAttr, String( val ) );
				}
			} else if ( attr.name.startsWith( 'data-wp-class--' ) ) {
				const className = attr.name.replace( 'data-wp-class--', '' );
				activeElement = el;
				const val = resolvePath( storeDef, attr.value );
				if ( val ) {
					el.classList.add( className );
				} else {
					el.classList.remove( className );
				}
			}
		} );
	} );

	// 3. data-wp-watch callbacks
	const watchers = [
		...( root.hasAttribute( 'data-wp-watch' ) ? [ root ] : [] ),
		...Array.from( root.querySelectorAll( '[data-wp-watch]' ) ),
	];
	watchers.forEach( ( el ) => {
		const watchPath = el.getAttribute( 'data-wp-watch' );
		if ( watchPath ) {
			const watchFn = resolvePath( storeDef, watchPath );
			if ( typeof watchFn === 'function' ) {
				activeContext = context;
				activeElement = el;
				watchFn();
			}
		}
	} );
}

function hydrateNamespace( namespace, storeDef ) {
	const elements = document.querySelectorAll(
		`[data-wp-interactive="${ namespace }"]`
	);
	elements.forEach( ( root ) => {
		if ( root._atlasHydrated ) {
			return;
		}
		root._atlasHydrated = true;

		let context = {};
		const rawContext = root.getAttribute( 'data-wp-context' );
		if ( rawContext ) {
			try {
				context = JSON.parse( rawContext );
			} catch {
				context = {};
			}
		}

		activeContext = context;
		activeElement = root;

		// Run init callback on root if specified
		const rootInit = root.getAttribute( 'data-wp-init' );
		if ( rootInit ) {
			const fn = resolvePath( storeDef, rootInit );
			if ( typeof fn === 'function' ) {
				fn();
			}
		}

		// Run init callback on child elements
		root.querySelectorAll( '[data-wp-init]' ).forEach( ( el ) => {
			const initCb = el.getAttribute( 'data-wp-init' );
			if ( initCb ) {
				const fn = resolvePath( storeDef, initCb );
				if ( typeof fn === 'function' ) {
					activeContext = context;
					activeElement = el;
					fn();
				}
			}
		} );

		// Attach click actions
		const clickables = [
			...( root.hasAttribute( 'data-wp-on--click' ) ? [ root ] : [] ),
			...Array.from( root.querySelectorAll( '[data-wp-on--click]' ) ),
		];

		clickables.forEach( ( btn ) => {
			const actionPath = btn.getAttribute( 'data-wp-on--click' );
			if ( actionPath ) {
				const actionFn = resolvePath( storeDef, actionPath );
				if ( typeof actionFn === 'function' ) {
					btn.addEventListener( 'click', async ( ev ) => {
						activeContext = context;
						activeElement = btn;
						const res = actionFn( ev );
						if ( res && typeof res.next === 'function' ) {
							let step = res.next();
							while ( ! step.done ) {
								updateBindings( root, storeDef, context );
								step = res.next( await step.value );
								updateBindings( root, storeDef, context );
							}
						}
						updateBindings( root, storeDef, context );
					} );
				}
			}
		} );

		// Attach keydown actions
		const keydownElements = [
			...( root.hasAttribute( 'data-wp-on--keydown' ) ? [ root ] : [] ),
			...Array.from( root.querySelectorAll( '[data-wp-on--keydown]' ) ),
		];

		keydownElements.forEach( ( el ) => {
			const actionPath = el.getAttribute( 'data-wp-on--keydown' );
			if ( actionPath ) {
				const actionFn = resolvePath( storeDef, actionPath );
				if ( typeof actionFn === 'function' ) {
					el.addEventListener( 'keydown', ( ev ) => {
						activeContext = context;
						activeElement = el;
						actionFn( ev );
						updateBindings( root, storeDef, context );
					} );
				}
			}
		} );

		// Initial bindings update after inits have run
		updateBindings( root, storeDef, context );
	} );
}

export function hydrate() {
	stores.forEach( ( storeDef, namespace ) => {
		hydrateNamespace( namespace, storeDef );
	} );
}

if ( typeof window !== 'undefined' ) {
	window.addEventListener( 'DOMContentLoaded', () => {
		hydrate();
	} );
}
