/**
 * Atlas mobile navigation drawer — Interactivity API store.
 *
 * Namespace: atlas/navigation. Progressive-enhancement, a11y-first off-canvas
 * drawer. The server HTML + CSS already render the final layout (inline list on
 * desktop; collapsed drawer + toggle link on mobile) and the menu opens with no
 * JS via :target, so hydration changes no geometry (no layout shift). This
 * module adds the hydration gate class, takes over open/close from :target,
 * moves and traps focus while open at the mobile breakpoint, and restores focus
 * on close.
 *
 * The drawer breakpoint is read from the server-rendered `data-breakpoint`
 * attribute (single source: Atlas\Assets::DRAWER_BREAKPOINT) so JS and CSS
 * never drift.
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Focusable-element selector for the focus trap.
 *
 * @type {string}
 */
const FOCUSABLE_SELECTOR =
	'a[href], button:not([disabled]), input:not([disabled]), ' +
	'select:not([disabled]), textarea:not([disabled]), ' +
	'[tabindex]:not([tabindex="-1"])';

/**
 * Per-instance DOM references and the live MediaQueryList, keyed by the drawer
 * root element so multiple header instances never collide. Kept out of the
 * store state because they are DOM handles, not reactive data.
 *
 * @type {WeakMap<HTMLElement, { trigger: HTMLElement|null }>}
 */
const instanceState = new WeakMap();

/**
 * Resolve the panel element for the current drawer root.
 *
 * @param {HTMLElement} root Drawer root element.
 * @return {HTMLElement|null} The panel element, or null when absent.
 */
function getPanel( root ) {
	return root.querySelector( '#atlas-nav-panel' );
}

/**
 * Collect the currently focusable elements inside the panel.
 *
 * Excludes elements that are not actually reachable (display:none / detached
 * via offsetParent, or aria-hidden), matching the design's focus-trap rules.
 *
 * @param {HTMLElement} panel Panel element.
 * @return {HTMLElement[]} Ordered focusable elements.
 */
function getFocusable( panel ) {
	return Array.from( panel.querySelectorAll( FOCUSABLE_SELECTOR ) )
		.filter( ( el ) => el.offsetParent !== null )
		.filter( ( el ) => el.getAttribute( 'aria-hidden' ) !== 'true' );
}

const { actions } = store( 'atlas/navigation', {
	state: {
		/**
		 * Convenience passthrough of the per-instance open flag.
		 *
		 * @return {boolean} Whether the drawer is open.
		 */
		get isOpen() {
			return getContext().isOpen;
		},
		/**
		 * String ARIA expanded value for the toggle button.
		 *
		 * @return {string} "true" or "false".
		 */
		get ariaExpanded() {
			return getContext().isOpen ? 'true' : 'false';
		},
		/**
		 * Whether the panel should be hidden. Only hides in the drawer
		 * presentation (mobile); at desktop the inline list is never hidden.
		 *
		 * @return {boolean} Whether to hide the panel.
		 */
		get drawerHidden() {
			const context = getContext();
			return context.isDrawer ? ! context.isOpen : false;
		},
		/**
		 * Panel role — "dialog" only in the drawer presentation, else null so
		 * the binding omits the attribute (plain nav landmark at desktop).
		 *
		 * @return {string|null} "dialog" or null.
		 */
		get dialogRole() {
			return getContext().isDrawer ? 'dialog' : null;
		},
		/**
		 * aria-modal — "true" only in the drawer presentation, else null.
		 *
		 * @return {string|null} "true" or null.
		 */
		get ariaModal() {
			return getContext().isDrawer ? 'true' : null;
		},
	},

	actions: {
		/**
		 * Open the drawer. Focus move-in and scroll lock are handled by the
		 * watchOpen callback reacting to the state change.
		 *
		 * @return {void}
		 */
		open() {
			getContext().isOpen = true;
		},
		/**
		 * Close the drawer. Focus restore and scroll unlock run in watchOpen.
		 *
		 * @param {Event} [event] Click event when invoked from the close link.
		 * @return {void}
		 */
		close( event ) {
			// The close control is a real link (href="#atlas-nav") so it also
			// works without JS; once hydrated, JS owns the state instead.
			if ( event && event.preventDefault ) {
				event.preventDefault();
			}
			getContext().isOpen = false;
		},
		/**
		 * Toggle the drawer open/closed.
		 *
		 * @param {Event} [event] Click event when invoked from the toggle link.
		 * @return {void}
		 */
		toggle( event ) {
			// The toggle is a real link (href="#atlas-nav-panel") so it also
			// opens the menu without JS; once hydrated, JS owns the state, so
			// cancel the hash navigation (no URL change, no :target).
			if ( event && event.preventDefault ) {
				event.preventDefault();
			}
			const context = getContext();
			if ( context.isOpen ) {
				actions.close();
			} else {
				actions.open();
			}
		},
		/**
		 * Keyboard support for the role="button" link toggle. Enter already
		 * activates a link natively (and fires click); Space does not, so add it
		 * as a real button would, without scrolling the page.
		 *
		 * @param {KeyboardEvent} event Keyboard event.
		 * @return {void}
		 */
		handleToggleKey( event ) {
			if ( ' ' === event.key ) {
				event.preventDefault();
				actions.toggle();
			}
		},
		/**
		 * Handle keydown at the drawer root: Escape closes, Tab/Shift+Tab trap
		 * focus. No-ops unless the drawer presentation is active and open.
		 *
		 * @param {KeyboardEvent} event Keyboard event.
		 * @return {void}
		 */
		handleKeydown( event ) {
			const context = getContext();
			if ( ! context.isDrawer || ! context.isOpen ) {
				return;
			}

			if ( 'Escape' === event.key ) {
				actions.close();
				return;
			}

			if ( 'Tab' !== event.key ) {
				return;
			}

			const { ref } = getElement();
			const panel = getPanel( ref );
			if ( ! panel ) {
				return;
			}

			const focusable = getFocusable( panel );
			if ( 0 === focusable.length ) {
				event.preventDefault();
				panel.focus();
				return;
			}

			const active = ref.ownerDocument.activeElement;
			const first = focusable[ 0 ];
			const last = focusable[ focusable.length - 1 ];

			if ( ! event.shiftKey && active === last ) {
				event.preventDefault();
				first.focus();
			} else if ( event.shiftKey && active === first ) {
				event.preventDefault();
				last.focus();
			}
		},
	},

	callbacks: {
		/**
		 * Init on the drawer root: add the hydration gate class, reveal the
		 * toggle, read the breakpoint from data-breakpoint, set isDrawer, and
		 * keep it in sync with the MediaQueryList. On a drawer->desktop change
		 * while open, close first (tearing down focus/scroll side effects)
		 * before clearing isDrawer.
		 *
		 * @return {void|(()=>void)} Optional cleanup remover.
		 */
		init() {
			const { ref } = getElement();

			ref.classList.add( 'is-atlas-interactive' );

			const breakpoint = ref.dataset.breakpoint;
			if ( ! breakpoint || ! window.matchMedia ) {
				return;
			}

			const context = getContext();
			const mql = window.matchMedia( '(max-width: ' + breakpoint + ')' );
			context.isDrawer = mql.matches;

			// If the menu was opened without JS (URL is #atlas-nav-panel), adopt
			// that as the open state and drop the hash so JS, not :target,
			// controls the panel from here on.
			const panel = getPanel( ref );
			if (
				context.isDrawer &&
				panel &&
				window.location.hash === '#' + panel.id
			) {
				context.isOpen = true;
				window.history.replaceState(
					null,
					'',
					window.location.pathname + window.location.search
				);
			}

			const onChange = ( event ) => {
				if ( ! event.matches && context.isOpen ) {
					// Drawer -> desktop while open: tear down side effects
					// (focus restore + scroll unlock) before clearing isDrawer.
					actions.close();
				}
				context.isDrawer = event.matches;
			};

			mql.addEventListener( 'change', onChange );

			return () => {
				mql.removeEventListener( 'change', onChange );
			};
		},
		/**
		 * Watch the open state on the panel: on open, store the trigger, move
		 * focus into the panel, and lock body scroll; on close, unlock scroll
		 * and restore focus. Only acts in the drawer presentation.
		 *
		 * @return {void}
		 */
		watchOpen() {
			const context = getContext();
			if ( ! context.isDrawer ) {
				return;
			}

			const { ref } = getElement();
			const root = ref.closest( '.atlas-nav' ) || ref;

			if ( ! instanceState.has( root ) ) {
				instanceState.set( root, { trigger: null } );
			}
			const local = instanceState.get( root );

			if ( context.isOpen ) {
				local.trigger = ref.ownerDocument.activeElement;
				ref.ownerDocument.body.style.overflow = 'hidden';

				const focusable = getFocusable( ref );
				if ( focusable.length > 0 ) {
					focusable[ 0 ].focus();
				} else {
					ref.focus();
				}
			} else {
				ref.ownerDocument.body.style.overflow = '';
				if (
					local.trigger &&
					typeof local.trigger.focus === 'function'
				) {
					local.trigger.focus();
					local.trigger = null;
				}
			}
		},
	},
} );
