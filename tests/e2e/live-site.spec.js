// @ts-check
/**
 * Live-site checks: these run against a REAL, running WordPress site, not the
 * fixture server, so they only run when a site address is given:
 *
 *   LIVE_BASE_URL=http://localhost:8885 npx playwright test live-site
 *   npm run test:e2e:live        (the local WordPress Studio site)
 *
 * Without LIVE_BASE_URL (for example in CI, where no such site exists) the
 * whole file is skipped instead of failing with ERR_CONNECTION_REFUSED.
 *
 * Note: test 4 submits the contact form for real, so the site stores one
 * submission and tries to send one email each run.
 */
const { test, expect } = require( '@playwright/test' );

const LIVE_BASE_URL = ( process.env.LIVE_BASE_URL || '' ).replace( /\/$/, '' );

test.skip(
	! LIVE_BASE_URL,
	'Live-site checks need a running site: set LIVE_BASE_URL (e.g. http://localhost:8885).'
);

test.use( { baseURL: LIVE_BASE_URL || undefined } );

test.describe( 'Atlas Full Site & Contact Form Live Verification', () => {
	test( '1. Home page loads with valid elements and navigation', async ( {
		page,
		isMobile,
	} ) => {
		const response = await page.goto( '/' );
		expect( response?.status() ).toBe( 200 );

		// Verify page title exists
		const title = await page.title();
		expect( title.length ).toBeGreaterThan( 0 );

		if ( isMobile ) {
			// On mobile viewport, navigation is inside hamburger button
			const mobileMenuBtn = page
				.locator( '.wp-block-navigation__responsive-container-open' )
				.first();
			await expect( mobileMenuBtn ).toBeVisible();
		} else {
			// On desktop viewport, navigation list is visible
			const nav = page.locator( 'nav.wp-block-navigation' ).first();
			await expect( nav ).toBeVisible();
			const contactLink = page
				.locator( 'a[href*="contact-form"]' )
				.first();
			await expect( contactLink ).toBeVisible();
		}
	} );

	test( '2. Contact page loads with all fields and correct structure', async ( {
		page,
	} ) => {
		const response = await page.goto( '/contact-form/' );
		expect( response?.status() ).toBe( 200 );

		// Verify fields are present
		await expect( page.locator( '#atlas-contact-1-name' ) ).toBeVisible();
		await expect( page.locator( '#atlas-contact-1-email' ) ).toBeVisible();
		await expect( page.locator( '#atlas-contact-1-phone' ) ).toBeVisible();
		await expect(
			page.locator( '#atlas-contact-1-message' )
		).toBeVisible();
		await expect(
			page.locator( '.atlas-contact-form__submit' )
		).toBeVisible();
	} );

	test( '3. Real-time validation checks for Name, Email, Phone and Message', async ( {
		page,
	} ) => {
		await page.goto( '/contact-form/' );

		const nameInput = page.locator( '#atlas-contact-1-name' );
		const emailInput = page.locator( '#atlas-contact-1-email' );
		const phoneInput = page.locator( '#atlas-contact-1-phone' );
		const messageInput = page.locator( '#atlas-contact-1-message' );
		const submitBtn = page.locator( '.atlas-contact-form__submit' );

		const nameError = page.locator( '#atlas-contact-1-name-error' );
		const emailError = page.locator( '#atlas-contact-1-email-error' );
		const phoneError = page.locator( '#atlas-contact-1-phone-error' );
		const messageError = page.locator( '#atlas-contact-1-message-error' );

		// (a) Name empty validation on blur/submit
		await nameInput.focus();
		await emailInput.focus(); // trigger blur on name
		await submitBtn.click();
		await expect( nameError ).toHaveText( 'Please enter your name.' );

		// Fix name
		await nameInput.fill( 'John Doe' );
		await nameInput.blur();
		await expect( nameError ).toHaveText( '' );
		await expect( nameInput ).toHaveAttribute( 'data-valid', 'true' );

		// (b) Invalid Email validation
		await emailInput.fill( 'not-a-valid-email' );
		await emailInput.blur();
		await expect( emailError ).toHaveText(
			'Please enter a valid email address.'
		);
		await expect( emailInput ).toHaveAttribute( 'data-valid', 'false' );

		// Fix Email
		await emailInput.fill( 'john.doe@example.com' );
		await emailInput.blur();
		await expect( emailError ).toHaveText( '' );
		await expect( emailInput ).toHaveAttribute( 'data-valid', 'true' );

		// (c) Invalid Phone validation (too short / invalid digits)
		await phoneInput.fill( '1234' );
		await phoneInput.blur();
		await expect( phoneError ).toHaveText(
			'Please enter a valid phone number (at least 7 digits).'
		);
		await expect( phoneInput ).toHaveAttribute( 'data-valid', 'false' );

		// Fix Phone with standard international format
		await phoneInput.fill( '+1 555 019 2834' );
		await phoneInput.blur();
		await expect( phoneError ).toHaveText( '' );
		await expect( phoneInput ).toHaveAttribute( 'data-valid', 'true' );

		// (d) Short Message validation (< 10 chars)
		await messageInput.fill( 'Too short' );
		await messageInput.blur();
		await expect( messageError ).toHaveText(
			'Please enter a message of at least 10 characters.'
		);
		await expect( messageInput ).toHaveAttribute( 'data-valid', 'false' );

		// Fix Message
		await messageInput.fill(
			'Hello! This is a valid automated UI verification test message with more than ten characters.'
		);
		await messageInput.blur();
		await expect( messageError ).toHaveText( '' );
		await expect( messageInput ).toHaveAttribute( 'data-valid', 'true' );
	} );

	test( '4. End-to-end form submission succeeds and displays status notification', async ( {
		page,
	} ) => {
		await page.goto( '/contact-form/' );

		await page
			.locator( '#atlas-contact-1-name' )
			.fill( 'Automated Tester' );
		await page
			.locator( '#atlas-contact-1-email' )
			.fill( 'tester@example.com' );
		await page
			.locator( '#atlas-contact-1-phone' )
			.fill( '+91 98765 43210' );
		await page
			.locator( '#atlas-contact-1-message' )
			.fill( 'Testing the live contact form submission end to end.' );

		const submitBtn = page.locator( '.atlas-contact-form__submit' );
		await submitBtn.click();

		// Wait for response status message
		const statusEl = page.locator( '#atlas-contact-1-status' );
		await expect( statusEl ).toBeVisible( { timeout: 10000 } );
		const statusText = await statusEl.textContent();
		expect( statusText?.length ).toBeGreaterThan( 0 );
	} );
} );
