/**
 * Atlas UI-states form module — Interactivity API store slice.
 *
 * Namespace: atlas/ui-states (one store, many slices). This slice derives a form
 * field's accessible validation state purely from that field's own
 * `data-wp-context` ({ valid, errorId, disabled }), mirroring the loading.js
 * precedent (per-region context + pure getters, no network, no mutation here).
 *
 * It exposes four getters a field's bindings read:
 *
 *   - `state.ariaInvalid` — the string `"true"` when the field is invalid and
 *     `"false"` when valid, so `data-wp-bind--aria-invalid` tracks validity
 *     across every transition (R7.2, R7.4).
 *   - `state.showError` — true exactly while the field is invalid, so the inline
 *     error element is rendered (`data-wp-bind--hidden="!state.showError"` or a
 *     `data-wp-bind--hidden="state.errorHidden"` binding) when invalid and
 *     removed the moment the field becomes valid again (R7.3, R7.4).
 *   - `state.errorHidden` — the inverse of `showError`, provided because
 *     `data-wp-bind--hidden` reads a positive "is hidden" value; the inline
 *     error is hidden exactly when the field is valid.
 *   - `state.errorId` — the id of the field's inline error element while the
 *     field is invalid, and an empty string when valid, so the field's
 *     `data-wp-bind--aria-describedby` references the error only while it is
 *     shown and drops the association once the field passes validation (R7.3).
 *
 * It also exposes `state.disabled`, the field's disabled flag, so the input's
 * `data-wp-bind--disabled` reflects the per-field disabled state (R7.5 visual
 * styling is driven by the resulting `:disabled`/`[disabled]` CSS).
 *
 * Scope is deliberately narrow: this module only *derives* ARIA/visibility from
 * the field's context. It never runs validation, dispatches requests, or mutates
 * `valid` / `errorId` / `disabled` — whatever validates the field (native
 * constraint validation or a submit handler) owns writing those context values;
 * these getters always read a defined value because the field ships them in its
 * `data-wp-context`. Button, offline, slow-request, and failure handling live in
 * their own sibling modules.
 */

import { store, getContext } from '@wordpress/interactivity';

store( 'atlas/ui-states', {
	state: {
		/**
		 * String `aria-invalid` value for this form field.
		 *
		 * Mirrors the field's validity so assistive tech is told the field is
		 * invalid while it fails validation (R7.2) and valid again once it
		 * passes (R7.4). Returns a string because `aria-invalid` is a
		 * string-valued ARIA attribute.
		 *
		 * @return {string} "true" when the field's context is invalid, else "false".
		 */
		get ariaInvalid() {
			return getContext().valid ? 'false' : 'true';
		},

		/**
		 * Whether this field's inline error message should be rendered.
		 *
		 * True exactly while the field is invalid, so the error element appears
		 * when validation fails (R7.3) and is removed the moment the field
		 * becomes valid (R7.4). Visibility is derived from validity — the two
		 * can never disagree.
		 *
		 * @return {boolean} True iff the field's context is invalid.
		 */
		get showError() {
			return ! getContext().valid;
		},

		/**
		 * Positive "is hidden" value for the inline error element.
		 *
		 * The inverse of `showError`, provided because `data-wp-bind--hidden`
		 * reads a truthy "hidden" value: the error is hidden exactly when the
		 * field is valid, and shown exactly when it is invalid (R7.3, R7.4).
		 *
		 * @return {boolean} True iff the field's context is valid (error hidden).
		 */
		get errorHidden() {
			return !! getContext().valid;
		},

		/**
		 * The id of this field's inline error element, for `aria-describedby`.
		 *
		 * Returns the field's `errorId` while the field is invalid so the input's
		 * `data-wp-bind--aria-describedby` references the rendered inline error
		 * (R7.3), and an empty string while the field is valid so the
		 * association is dropped once the error is removed (R7.4). Returning an
		 * empty string (rather than the id) removes the attribute association
		 * via the Interactivity API's falsy-attribute handling.
		 *
		 * @return {string} The error element id when invalid, else an empty string.
		 */
		get errorId() {
			const context = getContext();
			return context.valid ? '' : context.errorId || '';
		},

		/**
		 * Whether this form field is disabled.
		 *
		 * Reflects the per-field `disabled` flag so the input's
		 * `data-wp-bind--disabled` renders the field inert and the stylesheet's
		 * `:disabled` rules give it the visually distinct disabled state (R7.5).
		 *
		 * @return {boolean} True iff the field's context marks it disabled.
		 */
		get disabled() {
			return !! getContext().disabled;
		},
	},
} );
