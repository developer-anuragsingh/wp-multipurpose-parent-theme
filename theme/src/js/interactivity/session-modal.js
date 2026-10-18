/**
 * Atlas UI-states session-expired modal module — Interactivity API store slice.
 *
 * Namespace: atlas/ui-states (one store, many slices). This slice owns the
 * session-expired re-authentication modal the Notifications_Part renders. It is
 * the module the auth-error outcome belongs to: the shared REST client
 * (`rest-client.js`) classifies a 401/403 nonce failure as the `auth-error`
 * kind, and for a logged-in visitor whose session has merely lapsed this module
 * opens the focus-trapped modal (R9.4) instead of letting failure.js surface a
 * generic failure toast.
 *
 * Three concerns live here, each wired to a binding the modal markup in
 * parts/notifications.html already declares:
 *
 *   - Open on session expiry (R9.4): `actions.handleAuthError` sets
 *     `state.sessionModalOpen = true`; the modal's
 *     `data-wp-bind--hidden="state.sessionModalHidden"` reveals it.
 *   - Focus into + trapped within the modal (R9.5): `callbacks.watchSessionModal`
 *     (the dialog's `data-wp-watch`) moves focus inside when the modal opens;
 *     `actions.focusFirst` / `actions.focusLast` (the sentinel spans'
 *     `data-wp-on--focus`) wrap focus at the ends; `actions.handleModalKeydown`
 *     (the modal's `data-wp-on--keydown`) closes on Escape and keeps Tab within
 *     the dialog.
 *   - Nonce refresh on re-auth (R9.6): `actions.reauthenticate` (the re-auth
 *     button's `data-wp-on--click`) obtains a fresh `wp_rest` nonce and writes
 *     it to `state.nonce` so every subsequent request — rest-client.js reads
 *     `state.nonce` fresh on each call — carries the new nonce, then closes the
 *     modal on success.
 *
 * The nonce-refresh mechanism: there is no bespoke server endpoint wired for
 * this, so we re-fetch the standard WordPress REST nonce from the core
 * `rest-nonce` admin-ajax action (`/wp-admin/admin-ajax.php?action=rest-nonce`),
 * which returns the fresh `wp_rest` nonce as a plain-text body for the current
 * session. All DOM focus calls and the refresh fetch/storage are wrapped
 * defensively in `try/catch`, matching the theme-mode precedent, so a throwing
 * environment never breaks the session.
 *
 * Labels are pre-seeded from PHP gettext into `state.labels` (no JS i18n
 * runtime); this module only selects/uses them.
 */

import { store, getElement } from '@wordpress/interactivity';

/**
 * Core WordPress admin-ajax action that returns a fresh `wp_rest` nonce for the
 * current session as a plain-text body. Used to refresh `state.nonce` after
 * re-authentication (R9.6) without a bespoke endpoint.
 *
 * @type {string}
 */
const REST_NONCE_URL = '/wp-admin/admin-ajax.php?action=rest-nonce';

/**
 * CSS selector matching the natively focusable, non-sentinel controls inside the
 * dialog. Used to find the first focusable control when the modal opens and the
 * boundary controls the focus trap wraps between.
 *
 * @type {string}
 */
const FOCUSABLE_SELECTOR = [
	'a[href]',
	'button:not([disabled])',
	'input:not([disabled])',
	'select:not([disabled])',
	'textarea:not([disabled])',
	'[tabindex]:not([tabindex="-1"])',
].join( ',' );

/**
 * Move keyboard focus to an element, swallowing any error.
 *
 * Focus can throw or no-op for a detached / non-focusable node; this module must
 * never let that break the trap, so every DOM focus call routes through here
 * (the try/catch discipline the theme-mode module established).
 *
 * @param {Element|null|undefined} el The element to focus.
 * @return {void}
 */
function safeFocus( el ) {
	if ( ! el ) {
		return;
	}
	try {
		el.focus();
	} catch {
		// Non-focusable / detached node — ignore; the trap stays intact.
	}
}

/**
 * Collect the focusable, non-sentinel controls inside the dialog, in DOM order.
 *
 * The sentinel spans are excluded (they carry the wrap handlers, not real
 * content) so the trap wraps between the genuine first/last controls.
 *
 * @param {Element|null|undefined} dialog The dialog container element.
 * @return {Element[]} The focusable controls, possibly empty.
 */
function focusableWithin( dialog ) {
	if ( ! dialog ) {
		return [];
	}
	const nodes = dialog.querySelectorAll( FOCUSABLE_SELECTOR );
	return Array.prototype.filter.call(
		nodes,
		( node ) => ! node.classList.contains( 'atlas-notifications__sentinel' )
	);
}

const { state, actions } = store( 'atlas/ui-states', {
	state: {
		// Runtime: sessionModalOpen (set by the actions below). Seeded by PHP:
		// nonce, labels.

		/**
		 * Whether the session modal should be hidden.
		 *
		 * The modal binds this via `data-wp-bind--hidden="state.sessionModalHidden"`,
		 * so it is hidden exactly while it is not open and revealed the moment a
		 * session-expiry auth error opens it (R9.4). Expressed as the negation of
		 * `sessionModalOpen` so that single flag is the one source of truth for
		 * both the binding here and the focus/keydown logic below.
		 *
		 * @return {boolean} True when the modal is NOT open (hidden), else false.
		 */
		get sessionModalHidden() {
			return ! state.sessionModalOpen;
		},
	},

	actions: {
		/**
		 * React to a classified auth-error outcome and open the modal (R9.4).
		 *
		 * The shared client classifies a 401/403 nonce failure as the
		 * `auth-error` kind; for a logged-in visitor this is treated as session
		 * expiry (see design Error Handling). This action opens the modal for
		 * that outcome and ignores every other kind, so it never mistakes a
		 * generic failure for an expired session. Callers (failure/rest-client
		 * flow) pass the outcome descriptor returned by `actions.classify`.
		 *
		 * @param {{ kind: string, status: number, body: Object }} [outcome] The
		 *                                                                   outcome descriptor from `actions.classify`.
		 * @return {void}
		 */
		handleAuthError( outcome ) {
			if ( ! outcome || outcome.kind !== state.outcomeKinds.AUTH ) {
				return;
			}
			actions.openSessionModal();
		},

		/**
		 * Open the session modal (R9.4).
		 *
		 * Flips the single `state.sessionModalOpen` flag; the modal's
		 * `data-wp-bind--hidden` reveals it and the dialog's `data-wp-watch`
		 * (`callbacks.watchSessionModal`) reacts by moving focus inside (R9.5).
		 *
		 * @return {void}
		 */
		openSessionModal() {
			state.sessionModalOpen = true;
		},

		/**
		 * Close the session modal.
		 *
		 * Flips the flag back; the binding hides the modal again. Called on
		 * Escape and after a successful re-authentication.
		 *
		 * @return {void}
		 */
		closeSessionModal() {
			state.sessionModalOpen = false;
		},

		/**
		 * Keydown handler bound to the modal via `data-wp-on--keydown` (R9.5).
		 *
		 * Escape closes the modal; Tab / Shift+Tab are kept within the dialog by
		 * redirecting focus to the opposite boundary control when focus is about
		 * to leave at either end. The sentinel spans handle the common wrap via
		 * their focus handlers, but this guards the case where the dialog holds a
		 * single focusable control (or none) and the sentinels are skipped.
		 *
		 * @param {KeyboardEvent} event The keydown event.
		 * @return {void}
		 */
		handleModalKeydown( event ) {
			if ( ! event ) {
				return;
			}

			if ( 'Escape' === event.key ) {
				actions.closeSessionModal();
				return;
			}

			if ( 'Tab' !== event.key ) {
				return;
			}

			const { ref } = getElement();
			// The keydown binds the outer modal; the focusable controls live in
			// its dialog child. Query from the modal so either works.
			const focusables = focusableWithin( ref );
			if ( 0 === focusables.length ) {
				// Nothing to tab to — keep focus trapped on the dialog itself.
				event.preventDefault();
				return;
			}

			const first = focusables[ 0 ];
			const last = focusables[ focusables.length - 1 ];
			const active = ref ? ref.ownerDocument.activeElement : null;

			if ( event.shiftKey && active === first ) {
				event.preventDefault();
				safeFocus( last );
			} else if ( ! event.shiftKey && active === last ) {
				event.preventDefault();
				safeFocus( first );
			}
		},

		/**
		 * Wrap focus to the last focusable control (R9.5).
		 *
		 * Bound to the leading sentinel span via `data-wp-on--focus`: when focus
		 * reaches that sentinel (Shift+Tab off the first control) it is bounced
		 * to the last real control so focus never escapes the dialog backwards.
		 *
		 * @return {void}
		 */
		focusLast() {
			const { ref } = getElement();
			const dialog = ref
				? ref.closest( '.atlas-notifications__modal-dialog' )
				: null;
			const focusables = focusableWithin( dialog );
			if ( focusables.length > 0 ) {
				safeFocus( focusables[ focusables.length - 1 ] );
			}
		},

		/**
		 * Wrap focus to the first focusable control (R9.5).
		 *
		 * Bound to the trailing sentinel span via `data-wp-on--focus`: when focus
		 * reaches that sentinel (Tab off the last control) it is bounced to the
		 * first real control so focus never escapes the dialog forwards.
		 *
		 * @return {void}
		 */
		focusFirst() {
			const { ref } = getElement();
			const dialog = ref
				? ref.closest( '.atlas-notifications__modal-dialog' )
				: null;
			const focusables = focusableWithin( dialog );
			if ( focusables.length > 0 ) {
				safeFocus( focusables[ 0 ] );
			}
		},

		/**
		 * Refresh the nonce after re-authentication, then close the modal (R9.6).
		 *
		 * Bound to the re-auth button via `data-wp-on--click`. Fetches a fresh
		 * `wp_rest` nonce from the core `rest-nonce` admin-ajax action and writes
		 * it to `state.nonce`; because rest-client.js reads `state.nonce` fresh on
		 * every request, every subsequent request carries the new nonce (design
		 * Property 10). On a successful refresh the modal closes. The whole flow
		 * is wrapped in try/catch so a network/storage failure leaves the modal
		 * open for the visitor to retry rather than breaking the session.
		 *
		 * @return {void}
		 */
		*reauthenticate() {
			try {
				const response = yield fetch( REST_NONCE_URL, {
					credentials: 'same-origin',
				} );

				if ( ! response || ! response.ok ) {
					return;
				}

				const nonce = ( yield response.text() ).trim();
				if ( ! nonce ) {
					return;
				}

				// Single source of truth for the nonce every request reads.
				state.nonce = nonce;

				// Refresh succeeded — the session is usable again.
				actions.closeSessionModal();
			} catch {
				// Network / parse failure — keep the modal open so the visitor
				// can try again; never let a throw break the session.
			}
		},
	},

	callbacks: {
		/**
		 * Move focus into the dialog when the modal opens (R9.5).
		 *
		 * Bound to the dialog via `data-wp-watch`, so it re-runs whenever
		 * `state.sessionModalOpen` changes. On open it focuses the first
		 * focusable control (falling back to the dialog itself, which carries
		 * `tabindex="-1"`), which — paired with the sentinel wrap handlers and
		 * the keydown Tab handling — keeps focus trapped inside while the modal
		 * is open. On close it does nothing here; the binding hides the modal.
		 *
		 * @return {void}
		 */
		watchSessionModal() {
			if ( ! state.sessionModalOpen ) {
				return;
			}

			const { ref } = getElement();
			if ( ! ref ) {
				return;
			}

			const focusables = focusableWithin( ref );
			// Prefer the first real control; fall back to the dialog container
			// (tabindex="-1") so focus still lands inside the modal.
			safeFocus( focusables.length > 0 ? focusables[ 0 ] : ref );
		},
	},
} );
