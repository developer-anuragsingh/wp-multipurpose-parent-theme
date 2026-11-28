/**
 * Atlas click-to-load Google map — Interactivity API store.
 *
 * Namespace: atlas/map. Privacy: an embedded Google map sends the visitor's IP
 * address to Google and may set cookies as soon as it loads, so the server
 * (Atlas\Contact_Settings::render_map) renders no iframe at all. This store
 * reveals the "Show map" button on hydration and creates the iframe only after
 * the visitor clicks it. Without JavaScript the button stays hidden and the
 * "Open in Google Maps" link is the fallback.
 *
 * Per-instance context: { loaded: boolean, src: string, title: string }.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

store( 'atlas/map', {
	actions: {
		/**
		 * The visitor chose to show the map: flip the flag; renderFrame does the
		 * DOM work.
		 */
		load() {
			getContext().loaded = true;
		},
	},
	callbacks: {
		/**
		 * The button is JS-only (it does nothing without this module), so it
		 * ships [hidden] and is revealed here.
		 */
		revealButton() {
			const { ref } = getElement();
			if ( ref ) {
				ref.removeAttribute( 'hidden' );
			}
		},
		/**
		 * Create the iframe once, after the click, and move focus to it so
		 * keyboard and screen-reader users land on the map instead of on the
		 * now-hidden button.
		 */
		renderFrame() {
			const context = getContext();
			const { ref } = getElement();
			if ( ! context.loaded || ! ref || ref.querySelector( 'iframe' ) ) {
				return;
			}
			const frame = document.createElement( 'iframe' );
			frame.src = context.src;
			frame.title = context.title;
			frame.loading = 'lazy';
			// Origin only (not the full page URL); enough for Maps API key
			// referrer restrictions.
			frame.referrerPolicy = 'strict-origin-when-cross-origin';
			frame.allowFullscreen = true;
			ref.appendChild( frame );
			frame.focus();
		},
	},
} );
