/**
 * Atlas UI-states comment-feedback module — Interactivity API store slice.
 *
 * Namespace: atlas/ui-states (one store, many slices). This slice owns the two
 * comment-submission banners the Notifications_Part / comment region can render
 * and maps a submission outcome to exactly one of them (design Property 14):
 *
 *   - approved -> the comment-submitted confirmation banner (R9.1): the comment
 *                 was posted and is already visible.
 *   - pending  -> the pending-moderation banner (R9.2): the comment was accepted
 *                 but is being held for moderation.
 *
 * The two banners are mutually exclusive — a single outcome selects one and
 * clears the other, so the visitor never sees both "posted" and "awaiting
 * moderation" at once. Both are non-urgent confirmations, so like the success
 * toast in failure.js they are announced through the POLITE live region
 * (`state.announce.polite`, `role="status"`) rather than the assertive one.
 *
 * Labels are pre-translated and seeded from PHP gettext into `state.labels`
 * (`commentSubmitted`, `pendingModeration`) by `Atlas\UI_States::register_assets`
 * exactly like the theme-mode / failure precedent — this module performs no
 * runtime i18n, it only selects the right label.
 *
 * State owned here (see Data Models in design.md):
 *   - `state.commentFeedback` — `{ status }` where `status` is one of
 *     `COMMENT_OUTCOME` (`none` | `approved` | `pending`). The single source of
 *     truth both getters derive from, so the two banners stay mutually
 *     exclusive by construction.
 *
 * Getters the banner markup binds to:
 *   - `state.commentSubmittedHidden` / `state.commentPendingHidden` —
 *     `data-wp-bind--hidden` on each banner so exactly the selected one is in
 *     the document (and the accessibility tree) at a time.
 *   - `state.commentFeedbackMessage` — `data-wp-text` on the active banner,
 *     resolving to the pre-translated label for the current outcome.
 *
 * Scope is deliberately narrow: classify the comment outcome into one banner
 * and announce it politely. It does not touch the failure/success toasts
 * (failure.js), the offline banner (offline.js), or the session modal
 * (session-modal.js); it shares only the polite live region with them.
 */

import { store } from '@wordpress/interactivity';

/**
 * Comment-submission outcome states this module maps to a banner. Exactly one
 * is active at a time so the two banners are mutually exclusive (design
 * Property 14 / R9.1 / R9.2); `NONE` is the idle state in which neither banner
 * shows.
 *
 * @type {{ NONE: string, APPROVED: string, PENDING: string }}
 */
const COMMENT_OUTCOME = {
	NONE: 'none',
	APPROVED: 'approved',
	PENDING: 'pending',
};

const { state, actions } = store( 'atlas/ui-states', {
	state: {
		/**
		 * The current comment-feedback outcome.
		 *
		 * `status` is one of `COMMENT_OUTCOME`; seeded to `NONE` so the banner
		 * bindings read a defined value before any submission and so neither
		 * banner shows at rest. Replaced (not mutated) by the actions below so
		 * the runtime observes the change. Declared here with the idle value so
		 * sibling slices never read an undefined object.
		 *
		 * @type {{ status: string }}
		 */
		commentFeedback: {
			status: COMMENT_OUTCOME.NONE,
		},

		/**
		 * Whether the comment-submitted confirmation banner should be hidden.
		 *
		 * Bound by `data-wp-bind--hidden` on the submitted banner so it occupies
		 * the document only when the comment was approved/posted (R9.1).
		 * Derived from the single `commentFeedback.status`, which guarantees it
		 * can never be visible at the same time as the pending banner.
		 *
		 * @return {boolean} True unless the outcome is `approved`.
		 */
		get commentSubmittedHidden() {
			return COMMENT_OUTCOME.APPROVED !== state.commentFeedback.status;
		},

		/**
		 * Whether the pending-moderation banner should be hidden.
		 *
		 * Bound by `data-wp-bind--hidden` on the pending banner so it occupies
		 * the document only when the comment was held for moderation (R9.2).
		 * Derived from the same single status as the submitted banner, so the
		 * two are mutually exclusive by construction.
		 *
		 * @return {boolean} True unless the outcome is `pending`.
		 */
		get commentPendingHidden() {
			return COMMENT_OUTCOME.PENDING !== state.commentFeedback.status;
		},

		/**
		 * The pre-translated message for the active comment banner.
		 *
		 * Bound by `data-wp-text` on the banners; resolves to the posted label
		 * when approved, the moderation label when pending, and an empty string
		 * at rest. Selects between the labels PHP seeded — no runtime i18n.
		 *
		 * @return {string} The pre-translated label for the current outcome.
		 */
		get commentFeedbackMessage() {
			switch ( state.commentFeedback.status ) {
				case COMMENT_OUTCOME.APPROVED:
					return state.labels.commentSubmitted;
				case COMMENT_OUTCOME.PENDING:
					return state.labels.pendingModeration;
				default:
					return '';
			}
		},
	},

	actions: {
		/**
		 * Map a comment-submission outcome to exactly one banner (Property 14).
		 *
		 * Branches on whether the submitted comment was approved (posted and
		 * visible) or held for moderation and surfaces the matching banner,
		 * clearing the other. Accepts the moderation flag either as a plain
		 * boolean or as a descriptor (e.g. the parsed REST body) carrying
		 * `pending` / `held` / `approved`, so the comment form can hand this
		 * action whatever shape its submission yields.
		 *
		 * @param {boolean|{pending?: boolean, held?: boolean, approved?: boolean}} [outcome] Truthy /
		 *                                                                                    `{ pending|held: true }` for a comment held for moderation; falsey /
		 *                                                                                    `{ approved: true }` for an approved comment. Defaults to approved.
		 * @return {void}
		 */
		handleCommentOutcome( outcome ) {
			const isPending =
				true === outcome ||
				( !! outcome &&
					'object' === typeof outcome &&
					( true === outcome.pending || true === outcome.held ) &&
					true !== outcome.approved );

			if ( isPending ) {
				actions.showCommentPending();
			} else {
				actions.showCommentSubmitted();
			}
		},

		/**
		 * Surface the comment-submitted confirmation banner and announce it
		 * politely (R9.1).
		 *
		 * Sets the outcome to `approved` (which hides the pending banner through
		 * the shared status) and mirrors the pre-translated posted label into
		 * the polite live region, matching failure.js's non-urgent success
		 * announcement.
		 *
		 * @return {void}
		 */
		showCommentSubmitted() {
			state.commentFeedback = { status: COMMENT_OUTCOME.APPROVED };
			actions.announceCommentFeedback( state.labels.commentSubmitted );
		},

		/**
		 * Surface the pending-moderation banner and announce it politely (R9.2).
		 *
		 * Sets the outcome to `pending` (which hides the submitted banner through
		 * the shared status) and mirrors the pre-translated moderation label
		 * into the polite live region.
		 *
		 * @return {void}
		 */
		showCommentPending() {
			state.commentFeedback = { status: COMMENT_OUTCOME.PENDING };
			actions.announceCommentFeedback( state.labels.pendingModeration );
		},

		/**
		 * Write a comment confirmation to the polite live region (R10.2).
		 *
		 * Comment confirmations are non-urgent state changes, so — like the
		 * success toast — they go to the polite region (`role="status"`), which
		 * waits for a pause instead of interrupting. Lazily seeds the shared
		 * `announce` object so the live-region binding never reads `undefined`
		 * when this module runs before a sibling slice has created it.
		 *
		 * @param {string} message The pre-translated message to announce.
		 * @return {void}
		 */
		announceCommentFeedback( message ) {
			if ( ! state.announce ) {
				state.announce = { polite: '', assertive: '' };
			}
			state.announce.polite = message;
		},
	},
} );
