/**
 * Unit tests for Atlas Interactivity store contracts and validation.
 *
 * Uses Node.js native test runner (node --test).
 */

const { describe, it } = require('node:test');
const assert = require('node:assert/strict');

describe('Atlas UI States: REST Client Classification', () => {
	const OUTCOME = {
		SUCCESS: 'success',
		ERROR: 'error',
		MAINTENANCE: 'maintenance',
		AUTH: 'auth-error',
	};

	function classify(status, body) {
		if (status === 401 || status === 403) {
			return OUTCOME.AUTH;
		}
		if (status === 503 || (body && body.status === 'maintenance')) {
			return OUTCOME.MAINTENANCE;
		}
		if (status >= 200 && status < 300 && body && body.status === 'success') {
			return OUTCOME.SUCCESS;
		}
		return OUTCOME.ERROR;
	}

	it('classifies 401 and 403 as auth-error', () => {
		assert.equal(classify(401, null), OUTCOME.AUTH);
		assert.equal(classify(403, null), OUTCOME.AUTH);
	});

	it('classifies 503 or maintenance payload as maintenance', () => {
		assert.equal(classify(503, null), OUTCOME.MAINTENANCE);
		assert.equal(classify(200, { status: 'maintenance' }), OUTCOME.MAINTENANCE);
	});

	it('classifies 200 with success status as success', () => {
		assert.equal(classify(200, { status: 'success', data: {} }), OUTCOME.SUCCESS);
	});

	it('classifies 400 or 500 error responses as error', () => {
		assert.equal(classify(400, { status: 'error' }), OUTCOME.ERROR);
		assert.equal(classify(500, null), OUTCOME.ERROR);
	});
});

describe('Atlas Contact Form: Validation Contract', () => {
	function validateContactForm({ name, email, message }) {
		const errors = {};
		if (!name || name.trim().length === 0) {
			errors.name = 'Please enter your name.';
		}
		const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		if (!email || !emailPattern.test(email.trim())) {
			errors.email = 'Please enter a valid email address.';
		}
		if (!message || message.trim().length < 10) {
			errors.message = 'Please enter a message of at least 10 characters.';
		}
		return {
			isValid: Object.keys(errors).length === 0,
			errors,
		};
	}

	it('validates a complete, correctly filled form', () => {
		const result = validateContactForm({
			name: 'Test User',
			email: 'user@example.com',
			message: 'This is a valid inquiry message with sufficient length.',
		});
		assert.equal(result.isValid, true);
		assert.deepEqual(result.errors, {});
	});

	it('rejects empty fields and invalid emails', () => {
		const result = validateContactForm({
			name: '',
			email: 'invalid-email',
			message: 'short',
		});
		assert.equal(result.isValid, false);
		assert.ok(result.errors.name);
		assert.ok(result.errors.email);
		assert.ok(result.errors.message);
	});
});
