/**
 * Atlas UI-states loading / skeleton module — Interactivity API store slice.
 *
 * Namespace: atlas/ui-states (one store, many slices). This slice derives the
 * display of a content region purely from that region's request `status`, which
 * lives in per-region `data-wp-context` ({ status }). It exposes two getters:
 *
 *   - `state.showSkeleton` — true iff the region is `loading`, so a shimmer
 *     skeleton renders in place of the pending content (R4.1).
 *   - `state.ariaBusy` — the string `"true"` while `loading`, else `"false"`,
 *     mirroring the region's status so `aria-busy` tracks the request lifecycle
 *     (R4.2 on start, R4.3 on completion).
 *
 * Scope is deliberately narrow: this module only *derives* display and aria from
 * `status`. It never dispatches requests or mutates `status` — the region's
 * `idle -> loading -> idle` transitions are owned by whatever dispatches through
 * the shared client (rest-client.js), which also normalizes `status` to `idle`
 * on mount so these getters always read a defined value. Button spinners,
 * offline, slow-request, and failure handling live in their own sibling modules.
 */

import { store, getContext } from '@wordpress/interactivity';

store( 'atlas/ui-states', {
	state: {
		/**
		 * Whether the shimmer skeleton should render for this content region.
		 *
		 * True exactly while the region's request is in flight, so the skeleton
		 * stands in for the pending content and disappears once the status
		 * returns to idle (R4.1).
		 *
		 * @return {boolean} True iff the region's status is `loading`.
		 */
		get showSkeleton() {
			return 'loading' === getContext().status;
		},

		/**
		 * String `aria-busy` value for this content region.
		 *
		 * Mirrors the region's request status so assistive tech is told the
		 * region is busy while a request is in flight (R4.2) and no longer busy
		 * once it completes (R4.3). Returns a string because `aria-busy` is a
		 * string-valued ARIA attribute.
		 *
		 * @return {string} "true" while the status is `loading`, else "false".
		 */
		get ariaBusy() {
			return 'loading' === getContext().status ? 'true' : 'false';
		},
	},
} );
