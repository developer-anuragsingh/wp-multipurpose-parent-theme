/**
 * Atlas UI-states button module — Interactivity API store slice.
 *
 * Namespace: atlas/ui-states (one store, many slices). This slice derives an
 * action-triggering button's micro-states purely from that button's own request
 * `status`, which lives in per-button `data-wp-context` ({ status, disabled }).
 * Mirroring loading.js's getter style but keyed to the button's context rather
 * than a content region, it exposes:
 *
 *   - `state.buttonLoading`     — true iff the button's own request is in flight,
 *                                 driving the distinct loading state (R8.6).
 *   - `state.spinnerHidden`     — the inverse, bound by the inline spinner's
 *                                 `data-wp-bind--hidden` so the spinner shows
 *                                 exactly while that button is loading (R4.4).
 *   - `state.buttonAriaBusy`    — the string `"true"` while loading, else
 *                                 `"false"`, so `aria-busy` tracks the button's
 *                                 own request lifecycle (R4.4).
 *
 * It also owns the activation guard that enforces R8.5's "prevent activation"
 * clause on the JS side: `actions.activate` is a no-op when the button's context
 * reports `disabled`, so a disabled button dispatches nothing and makes no REST
 * request. Buttons bind `data-wp-on--click="actions.activate"`; the CSS pairs
 * this with `pointer-events: none` on the disabled state (design Button states),
 * so the two layers prevent activation together.
 *
 * Scope is deliberately narrow: derive the button's loading/spinner/aria from
 * its own `status`, and guard activation while disabled. It never dispatches a
 * request itself and never mutates `status` — the `idle -> loading -> idle`
 * transitions belong to whatever concern dispatches through the shared client
 * (rest-client.js), which also normalizes `status` to `idle` on mount so these
 * getters always read a defined value. Content-region skeletons (loading.js),
 * offline, slow-request, failure, and form/session states live in their own
 * sibling modules.
 */

import { store, getContext } from '@wordpress/interactivity';

const { actions } = store( 'atlas/ui-states', {
	state: {
		/**
		 * Whether this button's own request is in flight.
		 *
		 * True exactly while the button's per-button context status is
		 * `loading`, so the button renders its distinct loading state and the
		 * inline spinner appears (R4.4, R8.6). Read from `getContext()` so each
		 * button resolves against its own `data-wp-context`.
		 *
		 * @return {boolean} True iff this button's status is `loading`.
		 */
		get buttonLoading() {
			return 'loading' === getContext().status;
		},

		/**
		 * Whether this button's inline spinner should be hidden.
		 *
		 * The inverse of `buttonLoading`, bound by the spinner element's
		 * `data-wp-bind--hidden` so the spinner is present exactly while this
		 * button's request is in flight and inert otherwise (R4.4).
		 *
		 * @return {boolean} True iff this button is NOT loading.
		 */
		get spinnerHidden() {
			return 'loading' !== getContext().status;
		},

		/**
		 * String `aria-busy` value for this button.
		 *
		 * Mirrors the button's own request status so assistive tech is told the
		 * button is busy while its request is in flight and no longer busy once
		 * it completes (R4.4). Returns a string because `aria-busy` is a
		 * string-valued ARIA attribute.
		 *
		 * @return {string} "true" while this button's status is `loading`, else "false".
		 */
		get buttonAriaBusy() {
			return 'loading' === getContext().status ? 'true' : 'false';
		},
	},

	actions: {
		/**
		 * Activation guard for an action-triggering button (R8.5).
		 *
		 * Buttons bind `data-wp-on--click="actions.activate"`. When the button's
		 * own context reports `disabled`, this is a no-op: it dispatches no
		 * action and triggers no REST request, so a disabled button is inert on
		 * the JS side (the CSS `pointer-events: none` on the disabled state is
		 * the paired visual guard). When the button is enabled it dispatches the
		 * request through the shared client (rest-client.js), which owns the
		 * nonce, timer, and `status` lifecycle.
		 *
		 * @return {void}
		 */
		*activate() {
			const context = getContext();

			// Disabled: inert. Return before touching the shared client so no
			// action is dispatched and no REST request is made (R8.5).
			if ( context && context.disabled ) {
				return;
			}

			// Enabled: dispatch this button's request through the shared client
			// (rest-client.js owns the nonce, timer, and status lifecycle). The
			// `actions` handle resolves across the one shared store's slices, so
			// `actions.request` is the client's generator even though it lives in
			// a sibling module.
			if ( actions.request ) {
				yield actions.request();
			}
		},
	},
} );
