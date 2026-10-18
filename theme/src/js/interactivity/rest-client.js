/**
 * Atlas UI-states shared REST client — Interactivity API store slice.
 *
 * Namespace: atlas/ui-states (one store, many slices). This is the single seam
 * every async concern dispatches through: it injects the `X-WP-Nonce` header on
 * every request to `state.restUrl`, remembers the in-flight request as
 * `state.lastRequest` so retry can re-dispatch it, starts the slow-request timer
 * at `state.slowThreshold`, and classifies the response into one of four
 * outcomes (success | error | maintenance | auth-error).
 *
 * Scope is deliberately narrow: fetch + nonce + timer + lastRequest + classify.
 * It does NOT render the offline banner, the slow notice, the failure/success
 * toast, or the session modal — each of those concerns reacts in its own module
 * (offline.js, slow-request.js, failure.js, session-modal.js) off the seams this
 * module exposes:
 *
 *   - `state.isOffline` — set true when a request is short-circuited because
 *     `navigator.onLine` is false; the offline module owns the banner.
 *   - `actions.markSlow` — a no-op hook the slow timer calls when the threshold
 *     is crossed; the slow-request module overrides/reacts to drive the notice.
 *   - `actions.classify` — returns the classified outcome descriptor; the
 *     failure and session modules branch on `outcome.kind`.
 *
 * Server config (restUrl, nonce, slowThreshold, labels) is seeded into the store
 * by `Atlas\UI_States::register_assets()` via `wp_interactivity_state`, so this
 * module reads config from state rather than any global, matching the
 * theme-mode / navigation precedent.
 */

import { store, getContext } from '@wordpress/interactivity';

/**
 * Classification outcome kinds the client surfaces for a response. Exactly one
 * is returned per response so downstream modules branch on a single, total,
 * mutually-exclusive value (design Property 6).
 *
 * @type {{ SUCCESS: string, ERROR: string, MAINTENANCE: string, AUTH: string }}
 */
const OUTCOME = {
	SUCCESS: 'success',
	ERROR: 'error',
	MAINTENANCE: 'maintenance',
	AUTH: 'auth-error',
};

/**
 * Build the fetch init object for a request, injecting the WordPress REST nonce
 * header on top of any caller-supplied args. The nonce is read fresh from state
 * on every call so a nonce refreshed by the session modal is used by subsequent
 * requests (design Property 10).
 *
 * @param {Object} args  Caller-supplied fetch init (method, body, headers, ...).
 * @param {string} nonce The current `wp_rest` nonce from state.
 * @return {Object} The fetch init with the X-WP-Nonce header merged in.
 */
function withNonce( args, nonce ) {
	const init = { ...args };
	init.headers = {
		...( args && args.headers ),
		'X-WP-Nonce': nonce,
	};
	return init;
}

/**
 * Classify an HTTP response + parsed body into exactly one outcome descriptor.
 *
 * An auth error (HTTP 401/403, i.e. missing/invalid nonce / expired session)
 * takes precedence so the session module can react to it; otherwise the single
 * async contract's `status` field selects success / maintenance / error. Any
 * unrecognized shape falls through to `error` so classification is total.
 *
 * @param {Response} response The fetch Response.
 * @param {Object}   body     The parsed JSON body ({ status, data, message }).
 * @return {{ kind: string, status: number, body: Object }} The outcome descriptor.
 */
function classifyResponse( response, body ) {
	const status = response ? response.status : 0;

	if ( 401 === status || 403 === status ) {
		return { kind: OUTCOME.AUTH, status, body };
	}

	const contractStatus = body && body.status;

	if ( 'success' === contractStatus ) {
		return { kind: OUTCOME.SUCCESS, status, body };
	}

	if ( 'maintenance' === contractStatus ) {
		return { kind: OUTCOME.MAINTENANCE, status, body };
	}

	return { kind: OUTCOME.ERROR, status, body };
}

const { state, actions } = store( 'atlas/ui-states', {
	state: {
		// Seeded by PHP: restUrl, nonce, slowThreshold, labels.
		// Runtime: lastRequest (set below), isOffline (set below / by offline.js).

		/**
		 * The outcome kinds, exposed on state so sibling modules can compare
		 * against stable constants instead of re-declaring the string literals.
		 *
		 * @return {{ SUCCESS: string, ERROR: string, MAINTENANCE: string, AUTH: string }} Outcome kinds.
		 */
		get outcomeKinds() {
			return OUTCOME;
		},
	},

	actions: {
		/**
		 * Dispatch a request to the REST endpoint through the shared client.
		 *
		 * Records the descriptor as `state.lastRequest` first (so retry can
		 * re-dispatch it even if the request short-circuits or throws), then:
		 * when `navigator.onLine` is false it flags `state.isOffline` and
		 * returns without touching the network (the offline module owns the
		 * banner); otherwise it starts the slow timer, fetches with the nonce
		 * header, clears the timer, parses the body, and returns the classified
		 * outcome for the calling concern to react to.
		 *
		 * @param {Object} [request]          The request descriptor. When
		 *                                    omitted, `endpoint` defaults to the
		 *                                    seeded `state.restUrl` and `args` to
		 *                                    an empty fetch init.
		 * @param {string} [request.endpoint] The URL to fetch.
		 * @param {Object} [request.args]     The fetch init (method, body, ...).
		 * @return {Object|undefined} The outcome descriptor from classify(), or
		 *                            undefined when short-circuited offline.
		 */
		*request( request = {} ) {
			const endpoint = request.endpoint || state.restUrl;
			const args = request.args || {};

			// Remember the most recent request so retry (R5.3/R6.2) can
			// re-dispatch exactly this endpoint + args.
			state.lastRequest = { endpoint, args };

			// Dispatched while offline: short-circuit and flag the seam the
			// offline module reacts to. No banner logic lives here (R5.1).
			if ( ! navigator.onLine ) {
				state.isOffline = true;
				return undefined;
			}

			// Start the slow-request timer (R5.4). markSlow is a seam the
			// slow-request module reacts to; this module only fires it.
			const timer = setTimeout( actions.markSlow, state.slowThreshold );

			let response;
			let body = null;
			try {
				response = yield fetch(
					endpoint,
					withNonce( args, state.nonce )
				);

				try {
					body = yield response.json();
				} catch {
					// Non-JSON / empty body — classification falls through to
					// the error outcome, which is the correct recoverable path.
					body = null;
				}
			} finally {
				clearTimeout( timer );
			}

			return actions.classify( response, body );
		},

		/**
		 * Re-dispatch the most recently recorded request (R5.3/R6.2).
		 *
		 * Used by the offline banner and the failure toast retry controls. When
		 * no request has been recorded yet it is a no-op.
		 *
		 * @return {Object|undefined} The outcome descriptor from request(), or
		 *         undefined when there is nothing to retry.
		 */
		*retry() {
			if ( ! state.lastRequest ) {
				return undefined;
			}
			return yield actions.request( state.lastRequest );
		},

		/**
		 * Classify a response into exactly one outcome descriptor.
		 *
		 * Exposed as an action (not just an internal helper) so sibling modules
		 * can classify a response they obtained themselves and so the seam is
		 * mockable in tests. The returned `kind` is one of state.outcomeKinds.
		 *
		 * @param {Response} response The fetch Response.
		 * @param {Object}   body     The parsed JSON body.
		 * @return {{ kind: string, status: number, body: Object }} The outcome.
		 */
		classify( response, body ) {
			return classifyResponse( response, body );
		},

		/**
		 * Slow-request timer hook (R5.4).
		 *
		 * A no-op seam the request timer fires when `state.slowThreshold` ms
		 * elapse with a request still in flight. The slow-request module reacts
		 * to this to write the notice into the polite live region; keeping it a
		 * no-op here preserves this module's narrow scope (no notice logic).
		 *
		 * @return {void}
		 */
		markSlow() {
			// Intentionally empty — slow-request.js owns the notice. Present so
			// the timer always has a valid callback even before that module
			// loads, and so the seam is stable for sibling modules to react to.
		},
	},

	callbacks: {
		/**
		 * Reset the per-region request status to idle on mount.
		 *
		 * The client itself holds no per-region UI; this only normalizes a
		 * region's context status so loading.js (and button.js) have a defined
		 * starting value. Safe to run on any element bound to this store.
		 *
		 * Named `initRegion` rather than `init` so it never collides with the
		 * notifications-root initializer offline.js registers: both slices merge
		 * into the one shared store, and a shared `callbacks.init` would let the
		 * last-imported slice's version win (deep-merge). Keeping distinct names
		 * means a content region binds `data-wp-init="callbacks.initRegion"` for
		 * this normalizer while the Notifications_Part binds
		 * `callbacks.initNotifications` for the connectivity setup — both run.
		 *
		 * @return {void}
		 */
		initRegion() {
			const context = getContext();
			if ( context && undefined === context.status ) {
				context.status = 'idle';
			}
		},
	},
} );
