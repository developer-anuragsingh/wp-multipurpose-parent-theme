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

	test( 'copy button is revealed on hydration and copies quote', async ( {
		page,
	} ) => {
		const copyButton = page.locator( '.atlas-copy__button' );
		await expect( copyButton ).toBeVisible();

		// Click copy button
		await copyButton.click();

		// Check visible label updates to Copied!
		const label = copyButton.locator( '.atlas-copy__label' );
		await expect( label ).toHaveText( 'Copied!' );

		// Check aria-live status text
		const status = page.locator( '.atlas-copy__status' );
		await expect( status ).toHaveText( 'Copied!' );
	} );
} );
