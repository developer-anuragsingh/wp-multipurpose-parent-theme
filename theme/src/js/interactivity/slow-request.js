/**
 * Atlas UI-states slow-request module — Interactivity API store slice.
 *
 * Namespace: atlas/ui-states (one store, many slices). This slice owns the
 * slow-request notice (R5.4): when a request stays in flight past the slow
 * threshold, the shared client's slow timer fires and this module writes the
 * pre-translated slow label into the polite live region so assistive tech
 * announces it; when the request completes the notice is cleared so it does
 * not linger (R5.5).
 *
 * How it hooks the shared client's timer lifecycle (rest-client.js):
 *
 *   - rest-client.js starts `setTimeout( actions.markSlow, state.slowThreshold )`
 *     on every request and `clearTimeout`s it on completion. It defines
 *     `actions.markSlow` as an intentionally empty seam, documenting that this
 *     module owns the notice. Because every module registers on the same
 *     `atlas/ui-states` store and the Interactivity `store()` call deep-merges
 *     successive registrations, defining `actions.markSlow` here overrides that
 *     no-op (the build entry imports rest-client.js *before* this module, so the
 *     seam exists first and this definition wins). The 8s timer therefore drives
 *     the real notice without rest-client.js needing to know about it.
 *   - The threshold itself is never duplicated here: it lives in
 *     `state.slowThreshold` (seeded to 8000 by `Atlas\UI_States::register_assets`),
 *     and the timer that uses it is owned by rest-client.js. This module only
 *     reacts to the timer firing.
 *
 * Completion clearing: rest-client.js clears the *timer* on completion, which
 * stops a not-yet-fired notice from ever appearing. Once the notice has already
 * been written (the request outruns the threshold) it must also be removed when
 * the request finishes so it does not linger in the live region. This module
 * exposes `actions.clearSlow` for that: the completion path (and the retry/
 * offline/failure concerns that resolve a request) call it to blank the polite
 * region. Clearing is idempotent and only touches the slow notice.
 *
 * Scope is deliberately narrow: write the slow label on `markSlow`, blank it on
 * `clearSlow`. It owns no other announcement — offline, success, failure, and
 * maintenance notices live in their own sibling modules and share the same
 * polite/assertive live regions.
 */

import { store } from '@wordpress/interactivity';

const { state } = store( 'atlas/ui-states', {
	state: {
		/**
		 * The announcement text mirrored into the live regions.
		 *
		 * `polite` is bound to the `role="status"` region in
		 * `parts/notifications.html` (`data-wp-text="state.announce.polite"`);
		 * `assertive` to the `role="alert"` region. Seeded here with empty
		 * strings so the bindings read a defined value before any notice fires
		 * and so a cleared notice renders as no text rather than `undefined`.
		 * Sibling modules share this object — merging keeps whichever value is
		 * already present, so declaring it here never clobbers another slice.
		 *
		 * @type {{ polite: string, assertive: string }}
		 */
		announce: {
			polite: '',
			assertive: '',
		},
	},

	actions: {
		/**
		 * Announce the slow-request notice (R5.4).
		 *
		 * Overrides the no-op `markSlow` seam that rest-client.js registers: the
		 * shared client's slow timer fires this when a request is still in
		 * flight after `state.slowThreshold` ms, and here we write the
		 * pre-translated slow label into the polite live region so it is
		 * announced to assistive tech without interrupting (status, not alert).
		 * The label and threshold both come from state — nothing is hard-coded.
		 *
		 * @return {void}
		 */
		markSlow() {
			state.announce.polite = state.labels.slow;
		},

		/**
		 * Clear the slow-request notice on completion (R5.5).
		 *
		 * Once a request finishes, the slow notice must not linger in the polite
		 * region. rest-client.js clears the *timer* on completion (preventing a
		 * not-yet-fired notice from appearing); this blanks the notice that was
		 * already announced when the request outran the threshold. Only clears
		 * when the slow label is the current polite text so it never stomps a
		 * success/offline announcement another slice placed there.
		 *
		 * @return {void}
		 */
		clearSlow() {
			if ( state.announce.polite === state.labels.slow ) {
				state.announce.polite = '';
			}
		},
	},
} );
