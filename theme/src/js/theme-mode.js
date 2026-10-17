/**
 * Atlas dark/light mode toggle — Interactivity API store.
 *
 * Namespace: atlas/theme-mode. Global state (one theme per document), seeded
 * from the DOM that the blocking head script already set before paint. The
 * toggle action writes the data-theme attribute and persists to localStorage
 * using the exact same key/attribute as the head script so the two never
 * drift. Labels are pre-seeded from PHP gettext into global state (no JS i18n
 * runtime); the getters only select between them.
 */

import { store, getElement } from '@wordpress/interactivity';

/**
 * localStorage key — mirrors the blocking head script.
 *
 * @type {string}
 */
const STORAGE_KEY = 'atlas-theme-mode';

const { state } = store( 'atlas/theme-mode', {
	state: {
		// isDark is initialised in callbacks.init from the DOM; labelToLight /
		// labelToDark are seeded from PHP via wp_interactivity_state().

		/**
		 * String ARIA pressed value for the toggle button.
		 *
		 * @return {string} "true" or "false".
		 */
		get ariaPressed() {
			return state.isDark ? 'true' : 'false';
		},
		/**
		 * Accessible name describing the action the toggle performs next.
		 *
		 * @return {string} Pre-seeded gettext label.
		 */
		get toggleLabel() {
			return state.isDark ? state.labelToLight : state.labelToDark;
		},
	},

	actions: {
		/**
		 * Flip the theme: update global state, write data-theme on the root,
		 * and persist to localStorage (private-mode safe via try/catch).
		 *
		 * @return {void}
		 */
		toggleTheme() {
			state.isDark = ! state.isDark;
			const mode = state.isDark ? 'dark' : 'light';
			document.documentElement.setAttribute( 'data-theme', mode );
			try {
				localStorage.setItem( STORAGE_KEY, mode );
			} catch {
				// Safari private mode throws on write; ignore — the attribute
				// is already applied for this session.
			}
		},
	},

	callbacks: {
		/**
		 * Seed isDark from the DOM the head script set, then reveal the toggle
		 * (shipped hidden for progressive enhancement).
		 *
		 * @return {void}
		 */
		init() {
			state.isDark = 'dark' === document.documentElement.dataset.theme;

			const { ref } = getElement();
			if ( ref ) {
				ref.removeAttribute( 'hidden' );
			}
		},
	},
} );
