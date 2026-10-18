/**
 * Atlas UI-states failure / maintenance / success module — Interactivity API
 * store slice.
 *
 * Namespace: atlas/ui-states (one store, many slices). This slice reacts to a
 * classified outcome from the shared REST client (`actions.classify` in
 * rest-client.js, which returns `{ kind, status, body }` where `kind` is one of
 * `state.outcomeKinds`) and surfaces exactly one notice per outcome — the three
 * notices are mutually exclusive (design Property 6 / Error Handling):
 *
 *   - error       -> a failure toast in the ASSERTIVE live region (role="alert")
 *                    carrying a retry control bound to `actions.retry`
 *                    (rest-client.js re-dispatches `state.lastRequest`). R6.1/R6.2.
 *   - maintenance -> a maintenance notice, also ASSERTIVE, shown INSTEAD of the
 *                    generic failure toast. R6.3.
 *   - success     -> a success toast in the POLITE live region (role="status").
 *                    R9.3.
 *
 * The auth-error outcome is deliberately NOT handled here: session expiry is the
 * session modal's concern (session-modal.js, task 17.1). This module simply does
 * not treat an auth error as a generic failure, so it produces no toast for it.
 *
 * Labels are pre-translated and seeded from PHP gettext into `state.labels`
 * (`failure`, `maintenance`, `success`) exactly like the theme-mode precedent —
 * this module performs no runtime i18n, it only selects the right label.
 *
 * State owned here (see Data Models in design.md):
 *   - `state.toasts` — the list the toast stack renders. Each entry is
 *     `{ id, kind, message, retryable }`.
 *   - `state.announce` — `{ polite, assertive }`, mirrored into the two live
 *     regions in parts/notifications.html via `data-wp-text`. The urgency of the
 *     outcome selects which region (and therefore which role) announces it
 *     (R6.4 assertive for failure/maintenance; R9.3/R10.2 polite for success).
 *   - `state.toastsHidden` — bound by the toast stack container's
 *     `data-wp-bind--hidden`; true while there are no toasts to show.
 */

import { store } from '@wordpress/interactivity';

/**
 * Monotonic counter backing unique toast ids.
 *
 * Toast ids must be stable keys for the rendered stack and never collide across
 * a session, so a simple incrementing counter is sufficient (and avoids relying
 * on message text, which may repeat).
 *
 * @type {number}
 */
let toastSeq = 0;

/**
 * Mint the next unique toast id.
 *
 * @return {string} A session-unique toast id.
 */
function nextToastId() {
	toastSeq += 1;
	return `atlas-toast-${ toastSeq }`;
}

const { state, actions } = store( 'atlas/ui-states', {
	state: {
		// Runtime state seeded lazily by the actions below:
		//   toasts: Array<{ id, kind, message, retryable }>
		//   announce: { polite, assertive }
		// Labels (failure/maintenance/success) are seeded from PHP.

		/**
		 * Whether the toast stack container should be hidden.
		 *
		 * Bound by `data-wp-bind--hidden` on the toast stack in
		 * parts/notifications.html so the container only occupies the document
		 * once there is at least one toast to render — keeping it inert and out
		 * of the accessibility tree while empty.
		 *
		 * @return {boolean} True iff there are no toasts.
		 */
		get toastsHidden() {
			const toasts = state.toasts;
			return ! toasts || 0 === toasts.length;
		},
	},

	actions: {
		/**
		 * React to a classified outcome and surface exactly one notice.
		 *
		 * Branches on `outcome.kind` against `state.outcomeKinds` (the constants
		 * rest-client.js owns) so the three user-facing outcomes are mutually
		 * exclusive:
		 *
		 *   - ERROR       -> assertive failure toast with retry (R6.1/R6.2/R6.4).
		 *   - MAINTENANCE -> assertive maintenance notice, no failure toast
		 *                    (R6.3/R6.4).
		 *   - SUCCESS     -> polite success toast (R9.3/R10.2).
		 *
		 * AUTH and any unknown kind are intentionally ignored here: auth errors
		 * belong to the session modal (17.1), and ignoring them means this module
		 * never mislabels an expired session as a generic failure.
		 *
		 * @param {{ kind: string, status: number, body: Object }} [outcome] The
		 *                                                                   outcome descriptor returned by `actions.classify`.
		 * @return {void}
		 */
		handleOutcome( outcome ) {
			if ( ! outcome ) {
				return;
			}

			const kinds = state.outcomeKinds;

			switch ( outcome.kind ) {
				case kinds.ERROR:
					actions.showFailure();
					break;
				case kinds.MAINTENANCE:
					actions.showMaintenance();
					break;
				case kinds.SUCCESS:
					actions.showSuccess();
					break;
				default:
					// AUTH / unknown: not this module's concern (R6.5/R9.4 are
					// handled by session-modal.js). Produce no notice.
					break;
			}
		},

		/**
		 * Surface a failure toast and announce it assertively (R6.1/R6.2/R6.4).
		 *
		 * Pushes a retryable `error` toast carrying the pre-translated failure
		 * label; the toast stack renders a retry control bound to `actions.retry`
		 * (rest-client.js re-dispatches `state.lastRequest`). The same label is
		 * written to the assertive live region so screen-reader users hear it via
		 * `role="alert"`.
		 *
		 * @return {void}
		 */
		showFailure() {
			actions.pushToast(
				state.outcomeKinds.ERROR,
				state.labels.failure,
				true
			);
			actions.announceAssertive( state.labels.failure );
		},

		/**
		 * Surface the maintenance notice and announce it assertively (R6.3/R6.4).
		 *
		 * Shown INSTEAD of the generic failure toast: a non-retryable
		 * `maintenance` toast with the pre-translated maintenance label, mirrored
		 * into the assertive live region (`role="alert"`).
		 *
		 * @return {void}
		 */
		showMaintenance() {
			actions.pushToast(
				state.outcomeKinds.MAINTENANCE,
				state.labels.maintenance,
				false
			);
			actions.announceAssertive( state.labels.maintenance );
		},

		/**
		 * Surface a success toast and announce it politely (R9.3/R10.2).
		 *
		 * A non-retryable `success` toast with the pre-translated success label,
		 * mirrored into the polite live region (`role="status"`) because success
		 * is a non-urgent state change.
		 *
		 * @return {void}
		 */
		showSuccess() {
			actions.pushToast(
				state.outcomeKinds.SUCCESS,
				state.labels.success,
				false
			);
			actions.announcePolite( state.labels.success );
		},

		/**
		 * Append a toast to the stack the Notifications_Part renders.
		 *
		 * Each toast is `{ id, kind, message, retryable }`; `retryable` toasts
		 * render the retry control. The list is replaced (not mutated in place)
		 * so the Interactivity runtime observes the change and re-renders.
		 *
		 * @param {string}  kind      One of `state.outcomeKinds`.
		 * @param {string}  message   The pre-translated label to display.
		 * @param {boolean} retryable Whether this toast offers a retry control.
		 * @return {void}
		 */
		pushToast( kind, message, retryable ) {
			state.toasts = [
				...( state.toasts || [] ),
				{ id: nextToastId(), kind, message, retryable },
			];
		},

		/**
		 * Dismiss a toast by id.
		 *
		 * Lets a toast's close control remove exactly its own entry; replaces the
		 * list so the runtime re-renders the stack.
		 *
		 * @param {string} id The id of the toast to remove.
		 * @return {void}
		 */
		dismissToast( id ) {
			state.toasts = ( state.toasts || [] ).filter(
				( toast ) => toast.id !== id
			);
		},

		/**
		 * Write an urgent announcement to the assertive live region (R6.4).
		 *
		 * The assertive region (`role="alert"`) interrupts the user, which is the
		 * correct urgency for failures and maintenance.
		 *
		 * @param {string} message The pre-translated message to announce.
		 * @return {void}
		 */
		announceAssertive( message ) {
			if ( ! state.announce ) {
				state.announce = { polite: '', assertive: '' };
			}
			state.announce.assertive = message;
		},

		/**
		 * Write a non-urgent announcement to the polite live region (R10.2).
		 *
		 * The polite region (`role="status"`) waits for a pause, which is the
		 * correct urgency for a success confirmation.
		 *
		 * @param {string} message The pre-translated message to announce.
		 * @return {void}
		 */
		announcePolite( message ) {
			if ( ! state.announce ) {
				state.announce = { polite: '', assertive: '' };
			}
			state.announce.polite = message;
		},
	},
} );
