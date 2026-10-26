/**
 * Atlas copy-to-clipboard (Quotes niche) — Interactivity API store.
 *
 * Namespace: atlas/copy. Per-instance context (each quote has its own copied
 * flag and source text). A generator action performs the async Clipboard
 * write without a Promise library. The button accessible name is stable
 * ("Copy quote"); success is announced exactly once through a decoupled
 * aria-live region fed by statusText. The copy button is revealed on
 * hydration only when the Clipboard API is actually usable (canCopy), so an
 * insecure-context / unsupported browser never shows a dead control.
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

const { state } = store( 'atlas/copy', {
	state: {
		/**
		 * One-time global capability flag: Clipboard API present AND secure
		 * context. Referenced only as state.canCopy everywhere.
		 *
		 * @type {boolean}
		 */
		canCopy: !! ( navigator.clipboard && window.isSecureContext ),

		/**
		 * Stable accessible name for the copy button. Never flips on success,
		 * so screen readers are not re-announced from the button itself.
		 *
		 * @return {string} Pre-seeded gettext "Copy quote" label.
		 */
		get triggerLabel() {
			return getContext().labelCopy;
		},
		/**
		 * Visible button text. Flips to the "Copied!" label for the copied
		 * window so sighted users get clear, immediate feedback, then reverts.
		 * The accessible name (triggerLabel) stays stable regardless.
		 *
		 * @return {string} The current visible button label.
		 */
		get visibleLabel() {
			const context = getContext();
			return context.copied ? context.labelCopied : context.labelCopy;
		},
		/**
		 * Whether the button is in its copied state (for visual styling hook).
		 *
		 * @return {boolean} True during the copied window.
		 */
		get isCopied() {
			return !! getContext().copied;
		},
		/**
		 * Decorative glyph shown in the button: a checkmark once copied, a
		 * clipboard before. Marked aria-hidden in markup so it is purely
		 * visual and never announced.
		 *
		 * @return {string} The current glyph character.
		 */
		get iconGlyph() {
			return getContext().copied ? '\u2713' : '\u29C9';
		},
		/**
		 * Live-region text: "Copied!" while copied, else empty. Announces
		 * success exactly once to assistive tech, independent of the button.
		 *
		 * @return {string} The current status text.
		 */
		get statusText() {
			const context = getContext();
			return context.copied ? context.labelCopied : '';
		},
		/**
		 * aria-live politeness for the feedback region.
		 *
		 * @return {string} "polite".
		 */
		get ariaLive() {
			return 'polite';
		},
	},

	actions: {
		/**
		 * Copy the quote text to the clipboard, flash "Copied!" for 2s, then
		 * reset. Generator action so the async Clipboard write and the timeout
		 * resolve without a Promise library.
		 *
		 * @yield {Promise} The Clipboard write and the reset timeout.
		 */
		*copy() {
			// Defence-in-depth: the button is not revealed when canCopy is
			// false, so this is normally unreachable.
			if ( ! state.canCopy ) {
				return;
			}

			const context = getContext();
			const { ref } = getElement();

			let text = context.text;
			if ( ! text ) {
				const quote = ref.closest( '.wp-block-quote' );
				if ( quote ) {
					// Clone the quote and strip the injected copy controls so
					// the button/status labels ("Copy quote", "Copied!") never
					// leak into the copied text — only the real quote content
					// and citation are copied.
					const clone = quote.cloneNode( true );
					clone
						.querySelectorAll( '.atlas-copy' )
						.forEach( ( node ) => node.remove() );
					text = clone.textContent.trim().replace( /\s+/g, ' ' );
				}
			}
			if ( ! text ) {
				return;
			}

			yield navigator.clipboard.writeText( text );
			context.copied = true;
			yield new Promise( ( resolve ) => setTimeout( resolve, 2000 ) );
			context.copied = false;
		},
	},

	callbacks: {
		/**
		 * Reveal the copy button only when the Clipboard API is usable.
		 * Otherwise leave it hidden so no dead control is ever shown.
		 *
		 * @return {void}
		 */
		init() {
			if ( ! state.canCopy ) {
				return;
			}
			const { ref } = getElement();
			const button = ref.querySelector( '.atlas-copy__button' );
			if ( button ) {
				button.removeAttribute( 'hidden' );
			}
		},
	},
} );
