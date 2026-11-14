/**
 * Atlas contact-form — Interactivity API store (build entry).
 *
 * Namespace: atlas/contact-form. A single standalone store (its own webpack
 * entry), NOT a slice of atlas/ui-states. It progressively enhances a real
 * <form method="post" action="…"> so a no-JS visitor still submits straight to
 * the REST route; this module intercepts the submit, validates locally for UX,
 * and POSTs the same payload with the signed form token.
 *
 * Server config (restUrl, tokenUrl, pre-translated labels) is seeded by
 * Atlas\Contact_Form::register_assets() via wp_interactivity_state, so the
 * module reads config from state and performs NO runtime i18n — it only selects
 * among pre-translated strings. There is NO validation library (agent-rules §4
 * Zero-KB JS baseline): local checks are plain required/format/length tests, and
 * the server re-validates every field as the source of truth.
 *
 * The CSS import makes wp-scripts emit build/contact-form.css alongside the
 * module, matching the ui-states entry.
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

// Authored stylesheet; wp-scripts emits build/contact-form.css from this import.
import '../css/contact-form.css';

/**
 * Local, library-free field validation. UX-only — the server is the source of
 * truth. Returns true when the field passes its local constraint.
 *
 * @param {string} field Field key ('name', 'email', 'message').
 * @param {string} value Current trimmed field value.
 * @return {boolean} True when the value satisfies the local constraint.
 */
const isFieldValid = ( field, value ) => {
	const trimmed = ( value || '' ).trim();
	switch ( field ) {
		case 'name':
			return trimmed.length > 0 && trimmed.length <= 100;
		case 'email':
			// Light format check only; the server runs is_email().
			return (
				trimmed.length > 0 &&
				trimmed.length <= 254 &&
				/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( trimmed )
			);
		case 'message':
			return trimmed.length >= 10 && trimmed.length <= 5000;
		default:
			return true;
	}
};

/**
 * Fetch a fresh signed form token and write it into the form's hidden field.
 *
 * Never throws: on any failure the existing token is left untouched.
 *
 * @param {HTMLElement} form The contact form element.
 * @return {Promise<void>} Resolves once the field is updated (or on failure).
 */
const refreshFormToken = async ( form ) => {
	try {
		const response = await fetch( state.tokenUrl, {
			method: 'GET',
			credentials: 'omit',
			cache: 'no-store',
		} );
		if ( ! response.ok ) {
			return;
		}
		const data = await response.json();
		const field = form.querySelector( '[data-role="ts"]' );
		if ( field && data && typeof data.token === 'string' ) {
			field.value = data.token;
		}
	} catch {
		// Keep the server-rendered token.
	}
};

const { state } = store( 'atlas/contact-form', {
	state: {
		/**
		 * Current aria-live status message for the form-level status region.
		 *
		 * @return {string} The status text, or an empty string when idle.
		 */
		get statusText() {
			return getContext().status || '';
		},

		/**
		 * Whether a submission is in flight (binds the submit button disabled).
		 *
		 * @return {boolean} True while submitting.
		 */
		get isSubmitting() {
			return !! getContext().submitting;
		},

		/**
		 * The name field's string aria-invalid value.
		 *
		 * @return {string} "true" when invalid, else "false".
		 */
		get nameAriaInvalid() {
			return getContext().fields.name.valid ? 'false' : 'true';
		},

		/**
		 * The email field's string aria-invalid value.
		 *
		 * @return {string} "true" when invalid, else "false".
		 */
		get emailAriaInvalid() {
			return getContext().fields.email.valid ? 'false' : 'true';
		},

		/**
		 * The message field's string aria-invalid value.
		 *
		 * @return {string} "true" when invalid, else "false".
		 */
		get messageAriaInvalid() {
			return getContext().fields.message.valid ? 'false' : 'true';
		},

		/**
		 * The name field's inline error text (empty while valid).
		 *
		 * @return {string} Pre-translated error, or an empty string.
		 */
		get nameErrorText() {
			return getContext().fields.name.valid ? '' : state.labels.nameError;
		},

		/**
		 * The email field's inline error text (empty while valid).
		 *
		 * @return {string} Pre-translated error, or an empty string.
		 */
		get emailErrorText() {
			return getContext().fields.email.valid
				? ''
				: state.labels.emailError;
		},

		/**
		 * The message field's inline error text (empty while valid).
		 *
		 * @return {string} Pre-translated error, or an empty string.
		 */
		get messageErrorText() {
			return getContext().fields.message.valid
				? ''
				: state.labels.messageError;
		},
	},

	callbacks: {
		/**
		 * Swap the server-rendered form token for a fresh one on load.
		 *
		 * A full-page-cached page serves an old token; fetching a new one keeps
		 * the submission valid regardless of cache age. On any failure the
		 * server-rendered token is left in place (the no-JS value still works
		 * until it expires).
		 *
		 * @yield {Promise} The token fetch.
		 * @return {void}
		 */
		*refreshToken() {
			const { ref } = getElement();
			yield refreshFormToken( ref );
		},
	},

	actions: {
		/**
		 * Validate a single field on blur (instant local check, no debounce).
		 *
		 * Reads the field key from the input's data-field attribute and writes
		 * the result into that field's context so the bound aria-invalid and
		 * inline error update immediately.
		 *
		 * @return {void}
		 */
		validateField() {
			const { ref } = getElement();
			const field = ref.getAttribute( 'data-field' );
			if ( ! field ) {
				return;
			}
			const context = getContext();
			const entry = context.fields[ field ];
			if ( ! entry ) {
				return;
			}
			entry.valid = isFieldValid( field, ref.value );
			// Mark the field as touched so revalidateIfErrored knows to run.
			entry.touched = true;
		},

		/**
		 * Re-validate on input, but only after the field has first errored.
		 *
		 * Keeps typing from flashing an error before the user leaves the field,
		 * while clearing a shown error the moment the input becomes valid again.
		 *
		 * @return {void}
		 */
		revalidateIfErrored() {
			const { ref } = getElement();
			const field = ref.getAttribute( 'data-field' );
			if ( ! field ) {
				return;
			}
			const entry = getContext().fields[ field ];
			// Only re-validate once the field has been touched AND is currently
			// showing an error; never interrupt first-time typing.
			if ( ! entry || entry.valid || ! entry.touched ) {
				return;
			}
			entry.valid = isFieldValid( field, ref.value );
		},

		/**
		 * Submit handler bound via data-wp-on--submit. Generator action so the
		 * async fetch resolves without a Promise library.
		 *
		 * Flow: preventDefault -> validate all fields (focus the first invalid
		 * and abort on failure) -> announce "submitting" -> POST JSON with the
		 * signed form token and X-Atlas-Enhanced header -> branch on HTTP status
		 * (200/400/429/other) -> announce + manage focus. submitting is always
		 * cleared in finally.
		 *
		 * @param {Event} event The form submit event.
		 * @yield {Promise} The fetch request and its JSON parse.
		 * @return {void}
		 */
		*submit( event ) {
			event.preventDefault();
			const context = getContext();
			const { ref } = getElement();

			// Validate every field up front; collect current values.
			const values = {};
			let firstInvalid = null;
			[ 'name', 'email', 'message' ].forEach( ( field ) => {
				const input = ref.querySelector( `[data-field="${ field }"]` );
				const value = input ? input.value : '';
				values[ field ] = value;
				const valid = isFieldValid( field, value );
				context.fields[ field ].valid = valid;
				context.fields[ field ].touched = true;
				if ( ! valid && ! firstInvalid ) {
					firstInvalid = input;
				}
			} );

			if ( firstInvalid ) {
				context.status = state.labels.invalid;
				context.statusType = 'error';
				firstInvalid.focus();
				return;
			}

			context.submitting = true;
			context.status = state.labels.submitting;
			context.statusType = 'pending';

			try {
				const response = yield fetch( state.restUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-Atlas-Enhanced': '1',
					},
					// No cookies/nonce: the signed form token authorises the
					// request, and omitting credentials means a stale core REST
					// nonce can never cause a spurious 403.
					credentials: 'omit',
					body: JSON.stringify( {
						name: values.name.trim(),
						email: values.email.trim(),
						message: values.message.trim(),
						[ ref.getAttribute( 'data-hp-field' ) ||
						'atlas_contact_hp' ]:
							ref.querySelector( '[data-role="hp"]' )?.value ||
							'',
						[ ref.getAttribute( 'data-ts-field' ) ||
						'atlas_contact_ts' ]:
							ref.querySelector( '[data-role="ts"]' )?.value ||
							'',
					} ),
				} );

				if ( response.status === 200 ) {
					// Clear the fields and announce success; move focus to status.
					[ 'name', 'email', 'message' ].forEach( ( field ) => {
						const input = ref.querySelector(
							`[data-field="${ field }"]`
						);
						if ( input ) {
							input.value = '';
						}
						context.fields[ field ].valid = true;
						context.fields[ field ].touched = false;
					} );
					context.status = state.labels.success;
					context.statusType = 'success';
					const status = ref.querySelector( '[data-role="status"]' );
					if ( status ) {
						status.focus();
					}
					// A token is tied to one render time; mint a new one so a
					// second message is not treated as a "too fast" bot.
					yield refreshFormToken( ref );
				} else if (
					response.status === 400 ||
					response.status === 403
				) {
					// 400: server rejected input that passed local checks.
					// 403: the form token was rejected (expired/forged); get a
					// fresh one so a retry succeeds. Either way, focus the
					// first field for correction.
					if ( response.status === 403 ) {
						yield refreshFormToken( ref );
					}
					context.status = state.labels.invalid;
					context.statusType = 'error';
					const firstField = ref.querySelector(
						'[data-field="name"]'
					);
					if ( firstField ) {
						firstField.focus();
					}
				} else if ( response.status === 429 ) {
					context.status = state.labels.rateLimited;
					context.statusType = 'error';
				} else {
					context.status = state.labels.error;
					context.statusType = 'error';
				}
			} catch {
				context.status = state.labels.error;
				context.statusType = 'error';
			} finally {
				context.submitting = false;
			}
		},
	},
} );
