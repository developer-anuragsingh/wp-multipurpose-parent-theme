// @ts-check
const { test, expect } = require( '@playwright/test' );

test.describe( 'Atlas Language Switcher E2E', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( '/interactive' );
	} );

	test( 'language switcher is visible in header with the short EN and HI options', async ( {
		page,
	} ) => {
		const switcher = page.locator( '.atlas-lang-switcher' );
		await expect( switcher ).toBeVisible();

		const enLink = switcher.locator( '[data-lang="en_US"]' );
		const hiLink = switcher.locator( '[data-lang="hi_IN"]' );

		await expect( enLink ).toBeVisible();
		await expect( enLink ).toHaveText( 'EN' );
		await expect( enLink ).toHaveAttribute( 'hreflang', 'en' );
		await expect( enLink ).toHaveAttribute( 'aria-current', 'true' );
		await expect( enLink ).toHaveClass( /is-active/ );

		await expect( hiLink ).toBeVisible();
		await expect( hiLink ).toHaveText( 'HI' );
		// Compact label on screen, full name for assistive tech and as the tooltip.
		await expect( hiLink ).toHaveAttribute( 'aria-label', 'HI (हिन्दी)' );
		await expect( hiLink ).toHaveAttribute( 'title', 'हिन्दी' );
		await expect( enLink ).toHaveAttribute( 'aria-label', 'EN (English)' );
		await expect( hiLink ).toHaveAttribute( 'hreflang', 'hi' );
	} );
} );
