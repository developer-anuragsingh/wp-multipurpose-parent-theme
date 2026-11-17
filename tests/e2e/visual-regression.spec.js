// @ts-check
const { test, expect } = require( '@playwright/test' );

test.describe( 'Atlas Block Patterns Visual Regression', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( '/patterns' );
		// Screenshots must use the theme's real fonts (Inter/Sora, served by the
		// fixture server from theme.json), never a fallback that differs per OS.
		await page.evaluate( () => document.fonts.ready );
		const loaded = await page.evaluate( () =>
			[ ...document.fonts ]
				.filter( ( f ) => f.status === 'loaded' )
				.map( ( f ) => f.family )
		);
		expect( loaded ).toEqual(
			expect.arrayContaining( [ 'Inter', 'Sora' ] )
		);
	} );

	test( 'hero answer-first pattern visual layout', async ( { page } ) => {
		const hero = page.locator( '#pattern-hero' );
		await expect( hero ).toBeVisible();
		await expect( hero ).toHaveScreenshot( 'hero-answer-first.png' );
	} );

	test( 'NGO impact metrics pattern visual layout', async ( { page } ) => {
		const impact = page.locator( '#pattern-impact' );
		await expect( impact ).toBeVisible();
		await expect( impact ).toHaveScreenshot( 'impact-metrics.png' );
	} );

	test( 'quotes masonry grid pattern visual layout', async ( { page } ) => {
		const quotes = page.locator( '#pattern-quotes' );
		await expect( quotes ).toBeVisible();
		await expect( quotes ).toHaveScreenshot( 'quotes-masonry.png' );
	} );
} );
