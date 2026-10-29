// @ts-check
const { test, expect } = require( '@playwright/test' );

test.describe( 'Atlas Contact Form Validation E2E', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( '/interactive' );
	} );

	test( 'submitting empty form displays client-side validation errors', async ( {
		page,
	} ) => {
		const submitBtn = page.locator( '.atlas-contact-form__submit' );
		await submitBtn.click();

		const nameError = page.locator( '#name-error' );
		await expect( nameError ).toHaveText( 'Please enter your name.' );

		const emailError = page.locator( '#email-error' );
		await expect( emailError ).toHaveText(
			'Please enter a valid email address.'
		);
	} );

	test( 'invalid email shows error and valid email clears error', async ( {
		page,
	} ) => {
		const nameInput = page.locator( '#contact-name' );
		const emailInput = page.locator( '#contact-email' );
		const messageInput = page.locator( '#contact-message' );
		const submitBtn = page.locator( '.atlas-contact-form__submit' );

		await nameInput.fill( 'Alex Morgan' );
		await emailInput.fill( 'not-an-email' );
		await messageInput.fill(
			'This is a message with more than ten characters.'
		);
		await submitBtn.click();

		const emailError = page.locator( '#email-error' );
		await expect( emailError ).toHaveText(
			'Please enter a valid email address.'
		);

		// Fix email
		await emailInput.fill( 'alex@example.com' );
		await submitBtn.click();
		await expect( emailError ).toHaveText( '' );
	} );
} );
