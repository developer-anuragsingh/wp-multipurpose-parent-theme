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
 * @param {string} field Field key ('name', 'email', 'phone', 'message').
 * @param {string} value Current trimmed field value.
 * @return {boolean} True when the value satisfies the local constraint.
 */
const isFieldValid = ( field, value ) => {
	const trimmed = ( value || '' ).trim();
	switch ( field ) {
		case 'name':
			return (
				trimmed.length >= 2 &&
				trimmed.length <= 100 &&
				! /<[^>]*>/.test( trimmed )
			);
		case 'email':
			// RFC 5322 compatible format check; the server runs is_email().
			return (
				trimmed.length > 0 &&
				trimmed.length <= 254 &&
				/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( trimmed )
			);
		case 'phone':
			if ( trimmed.length === 0 ) {
				return true; // Optional field if empty
			}
			// Must contain 7 to 16 digits, with allowed characters +, spaces, hyphens, parentheses, dots
			{
				const digits = trimmed.replace( /\D/g, '' );
				const validChars = /^[\d\s+\-().]{7,25}$/.test( trimmed );
				return validChars && digits.length >= 7 && digits.length <= 16;
			}
		case 'message':
			return trimmed.length >= 10 && trimmed.length <= 5000;
		default:
			return true;
	}
};

const defaultLabels = {
	nameError: 'Please enter your name.',
	emailError: 'Please enter a valid email address.',
	phoneError: 'Please enter a valid phone number (at least 7 digits).',
	messageError: 'Please enter a message of at least 10 characters.',
	invalid: 'Please check the form and try again.',
	submitting: 'Sending your message…',
	success: 'Thanks — your message has been sent.',
	error: 'Something went wrong. Please try again later.',
};

/**
 * Set the visible error message in the associated error element.
 *
 * @param {HTMLElement} input    The input element.
 * @param {string}      errorMsg The error message to show, or empty to clear.
 */
function setFieldErrorMessage( input, errorMsg ) {
	const describedBy = input.getAttribute( 'aria-describedby' );
	let errorEl = null;

	if ( describedBy ) {
		errorEl = document.getElementById( describedBy );
	}

	if ( ! errorEl ) {
		const parent = input.closest( '.atlas-contact-form__field' );
		if ( parent ) {
			errorEl = parent.querySelector( '.atlas-contact-form__error' );
		}
	}

	if ( errorEl ) {
		errorEl.textContent = errorMsg;
	}
}

/**
 * Validate a single form input element and immediately update its visual state and error text.
 *
 * @param {HTMLInputElement|HTMLTextAreaElement} input  The form input.
 * @param {boolean}                              isBlur Whether this validation is triggered on blur/focusout.
 * @return {boolean} True if field is valid.
 */
function validateFieldElement( input, isBlur = false ) {
	const field = input.getAttribute( 'data-field' );
	if ( ! field ) {
		return true;
	}

	const rawValue = input.value || '';
	const trimmed = rawValue.trim();
	const isRequired = input.hasAttribute( 'required' );

	let isValid = true;
	let errorMsg = '';

	const labels = state && state.labels ? state.labels : defaultLabels;

	if ( isRequired && trimmed.length === 0 ) {
		if ( isBlur || input.getAttribute( 'data-touched' ) === 'true' ) {
			isValid = false;
			errorMsg =
				labels[ `${ field }Error` ] ||
				defaultLabels[ `${ field }Error` ] ||
				'This field is required.';
		} else {
			input.removeAttribute( 'data-valid' );
			input.setAttribute( 'aria-invalid', 'false' );
			setFieldErrorMessage( input, '' );
			return true;
		}
	} else if ( trimmed.length > 0 ) {
		input.setAttribute( 'data-touched', 'true' );
		isValid = isFieldValid( field, rawValue );
		if ( ! isValid ) {
			errorMsg =
				labels[ `${ field }Error` ] ||
				defaultLabels[ `${ field }Error` ] ||
				'Please enter a valid value.';
		}
	} else {
		// Optional empty field (e.g. phone)
		isValid = true;
		errorMsg = '';
	}

	// Update DOM attributes immediately
	input.setAttribute( 'data-valid', isValid ? 'true' : 'false' );
	input.setAttribute( 'aria-invalid', isValid ? 'false' : 'true' );
	setFieldErrorMessage( input, errorMsg );

	// Sync to Interactivity context if available
	try {
		const context = getContext();
		if ( context && context.fields && context.fields[ field ] ) {
			context.fields[ field ].valid = isValid;
			if ( isBlur || trimmed.length > 0 ) {
				context.fields[ field ].touched = true;
			}
		}
	} catch {
		// Outside interactivity context (fallback)
	}

	return isValid;
}

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
		 * The phone field's string aria-invalid value.
		 *
		 * @return {string} "true" when invalid, else "false".
		 */
		get phoneAriaInvalid() {
			const entry = getContext().fields?.phone;
			return entry && ! entry.valid ? 'true' : 'false';
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
		 * The phone field's inline error text (empty while valid).
		 *
		 * @return {string} Pre-translated error, or an empty string.
		 */
		get phoneErrorText() {
			const entry = getContext().fields?.phone;
			return entry && ! entry.valid
				? state.labels.phoneError ||
						'Please enter a valid phone number.'
				: '';
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
		 * Live validation as the user types (real-time data validation).
		 *
		 * @return {void}
		 */
		liveValidate() {
			const { ref } = getElement();
			if ( ref ) {
				validateFieldElement( ref, false );
			}
		},

		/**
		 * Validate a single field on blur or focusout.
		 *
		 * @return {void}
		 */
		validateField() {
			const { ref } = getElement();
			if ( ref ) {
				validateFieldElement( ref, true );
			}
		},

		/**
		 * Re-validate on input. Kept as fallback delegating to liveValidate.
		 *
		 * @return {void}
		 */
		revalidateIfErrored() {
			const { ref } = getElement();
			if ( ref ) {
				validateFieldElement( ref, false );
			}
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
			[ 'name', 'email', 'phone', 'message' ].forEach( ( field ) => {
				const input = ref.querySelector( `[data-field="${ field }"]` );
				if ( input ) {
					values[ field ] = input.value;
					const valid = validateFieldElement( input, true );
					if ( ! valid && ! firstInvalid ) {
						firstInvalid = input;
					}
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
						name: ( values.name || '' ).trim(),
						email: ( values.email || '' ).trim(),
						phone: ( values.phone || '' ).trim(),
						message: ( values.message || '' ).trim(),
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
					[ 'name', 'email', 'phone', 'message' ].forEach(
						( field ) => {
							const input = ref.querySelector(
								`[data-field="${ field }"]`
							);
							if ( input ) {
								input.value = '';
								input.removeAttribute( 'data-valid' );
							}
							if ( context.fields && context.fields[ field ] ) {
								context.fields[ field ].valid = true;
								context.fields[ field ].touched = false;
							}
						}
					);
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

// Progressive enhancement: direct capture listeners for instant live typing and focusout validation.
if ( typeof document !== 'undefined' ) {
	const handleEvent = ( event, isBlur ) => {
		const target = event.target;
		if (
			target &&
			target.getAttribute &&
			target.hasAttribute( 'data-field' )
		) {
			if ( target.closest( '.atlas-contact-form' ) ) {
				validateFieldElement( target, isBlur );
			}
		}
	};

	document.addEventListener(
		'input',
		( e ) => handleEvent( e, false ),
		true
	);
	document.addEventListener(
		'focusout',
		( e ) => handleEvent( e, true ),
		true
	);
	document.addEventListener( 'blur', ( e ) => handleEvent( e, true ), true );
	document.addEventListener(
		'change',
		( e ) => handleEvent( e, true ),
		true
	);
}
