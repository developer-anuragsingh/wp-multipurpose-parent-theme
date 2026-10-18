/**
 * Atlas UI-states offline module — Interactivity API store slice.
 *
 * Namespace: atlas/ui-states (one store, many slices). This slice owns the
 * offline banner the Notifications_Part renders: it keeps `state.isOffline` in
 * sync with the browser's connectivity (`navigator.onLine` plus the window
 * `online` / `offline` events), derives the banner's visibility from it, and
 * mirrors the offline label into the polite live region so assistive tech is
 * told about the connection change (R5.1, R5.2, R5.5).
 *
 * The retry control in the banner re-attempts the most recent failed request
 * (R5.3). That action lives in the shared client (`rest-client.js`,
 * `actions.retry`, which re-dispatches `state.lastRequest`); this module does
 * not redefine it — the banner's `data-wp-on--click="actions.retry"` binding
 * resolves to that shared action. The client is also what flags
 * `state.isOffline` when a request is dispatched while offline; this module
 * complements that by reacting to the connectivity *events* themselves so the
 * banner appears/clears even without an in-flight request.
 *
 * Scope is deliberately narrow: connectivity sync + banner visibility + the
 * offline announcement. It does not touch the slow-request notice
 * (slow-request.js), the failure/success toasts (failure.js), or the session
 * modal (session-modal.js). Labels are pre-seeded from PHP gettext into
 * `state.labels` (no JS i18n runtime).
 */

import { store } from '@wordpress/interactivity';

const { state } = store( 'atlas/ui-states', {
	state: {
		// Runtime: isOffline (synced below / by rest-client.js on a
		// short-circuited request); announce.polite (written below). Seeded by
		// PHP: labels.offline.

		/**
		 * Whether the offline banner should be hidden.
		 *
		 * The banner binds this via `data-wp-bind--hidden="state.offlineHidden"`,
		 * so it is hidden exactly while the browser is online and revealed the
		 * moment connectivity is lost (R5.1/R5.2). Expressed as the negation of
		 * `isOffline` so the single `isOffline` flag is the one source of truth
		 * for both this getter and the client's short-circuit.
		 *
		 * @return {boolean} True when NOT offline (banner hidden), else false.
		 */
		get offlineHidden() {
			return ! state.isOffline;
		},
	},

	callbacks: {
		/**
		 * Mount the offline concern on the Notifications_Part root.
		 *
		 * Fired by the part's `data-wp-init="callbacks.initNotifications"`. Seeds
		 * `state.isOffline` from the current `navigator.onLine` reading, ensures
		 * the polite announcement slot exists (the live-region binding reads
		 * `state.announce.polite`), announces immediately if the page loaded
		 * while already offline, and registers window `online` / `offline`
		 * listeners that keep `state.isOffline` and the announcement in sync for
		 * the lifetime of the page.
		 *
		 * Named `initNotifications` rather than `init` so it never collides with
		 * rest-client.js's per-region `callbacks.initRegion`: both slices merge
		 * into the one shared store, so a shared `callbacks.init` would let only
		 * the last-imported slice's version run. Distinct names let the
		 * Notifications_Part root run this connectivity setup while content
		 * regions independently run the region normalizer.
		 *
		 * @return {void}
		 */
		initNotifications() {
			// The polite live region binds state.announce.polite; make sure the
			// slot exists before any sibling module (slow-request, failure)
			// writes to it, so the binding never reads an undefined object.
			if ( ! state.announce ) {
				state.announce = { polite: '', assertive: '' };
			}

			syncOffline();

			window.addEventListener( 'online', syncOffline );
			window.addEventListener( 'offline', syncOffline );
		},
	},
} );

/**
 * Reconcile the store with the browser's current connectivity.
 *
 * Reads `navigator.onLine` and writes `state.isOffline` to match, which drives
 * the banner through `state.offlineHidden`. On a transition into offline it
 * writes the pre-translated offline label into the polite live region so the
 * change is announced (R5.5); on returning online it clears both the flag and
 * the announcement so a stale offline message is not re-read. Guarded so it
 * only writes when the value actually changes, avoiding redundant live-region
 * churn when the same event fires more than once.
 *
 * @return {void}
 */
function syncOffline() {
	const offline = ! navigator.onLine;

	if ( offline === state.isOffline ) {
		return;
	}

	state.isOffline = offline;

	if ( offline ) {
		// Non-urgent: the offline notice goes to the polite region (R5.5).
		state.announce.polite = state.labels.offline;
	} else if ( state.labels.offline === state.announce.polite ) {
		// Returning online: clear the banner (via isOffline) and retract the
		// offline announcement, but only if it is still the offline label so we
		// never stomp a notice another module placed in the polite region.
		state.announce.polite = '';
	}
}
