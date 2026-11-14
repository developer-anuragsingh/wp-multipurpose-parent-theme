// @ts-check
const { test, expect } = require( '@playwright/test' );

test.describe( 'Atlas Copy Quote to Clipboard E2E', () => {
	test.beforeEach( async ( { page, context } ) => {
		await context.grantPermissions( [
			'clipboard-read',
			'clipboard-write',
		] );
		await page.goto( '/interactive' );
	} );

	test( 'copy button is an icon-only control that copies the quote', async ( {
		page,
	} ) => {
		const copyButton = page.locator( '.atlas-copy__button' );
		await expect( copyButton ).toBeVisible();

		// Icon-only: no visible text, but a stable accessible name and tooltip.
		await expect( copyButton ).toHaveAttribute(
			'aria-label',
			'Copy quote'
		);
		await expect( copyButton ).toHaveAttribute( 'title', 'Copy quote' );
		expect( ( await copyButton.innerText() ).trim() ).toBe( '' );
		await expect( copyButton.locator( 'svg:visible' ) ).toHaveCount( 1 );
		await expect(
			copyButton.locator( '.atlas-icon--copy' )
		).toBeVisible();

		await copyButton.click();

		// The icon becomes a check; the name never changes.
		await expect(
			copyButton.locator( '.atlas-icon--check' )
		).toBeVisible();
		await expect( copyButton.locator( '.atlas-icon--copy' ) ).toBeHidden();
		await expect( copyButton ).toHaveAttribute(
			'aria-label',
			'Copy quote'
		);

		// Success is announced once, through the live region.
		await expect( page.locator( '.atlas-copy__status' ) ).toHaveText(
			'Copied!'
		);

		// The clipboard really holds the quote text (no label text leaked in).
		const copied = await page.evaluate( () =>
			navigator.clipboard.readText()
		);
		expect( copied ).toContain( 'Simplicity is prerequisite' );
		expect( copied ).not.toContain( 'Copy quote' );
		expect( copied ).not.toContain( 'Copied!' );

		// And it reverts to the copy icon after the copied window.
		await expect( copyButton.locator( '.atlas-icon--copy' ) ).toBeVisible( {
			timeout: 5000,
		} );
		await expect( page.locator( '.atlas-copy__status' ) ).toHaveText( '' );
	} );
} );
