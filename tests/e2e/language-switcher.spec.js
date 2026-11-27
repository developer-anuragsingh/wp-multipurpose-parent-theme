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
test.describe( 'Hindi pages use a larger Devanagari text size', () => {
	/**
	 * Body font size (px) and line-height ratio.
	 *
	 * @param {import('@playwright/test').Page} page Playwright page.
	 */
	const measure = ( page ) =>
		page.evaluate( () => {
			const body = window.getComputedStyle( document.body );
			const size = parseFloat( body.fontSize );
			return {
				body: size,
				lineHeight: parseFloat( body.lineHeight ) / size,
			};
		} );
	test( 'body text is 15-16px with 1.75 leading on a Hindi page, unchanged on an English one', async ( {
		page,
	} ) => {
		await page.goto( '/interactive' );
		const english = await measure( page );
		// Latin body: theme.json "medium" (14-15px), line-height 1.6.
		expect( english.body ).toBeLessThanOrEqual( 15 );
		expect( english.lineHeight ).toBeCloseTo( 1.6, 2 );
		// Atlas\I18n renders <html lang="hi-IN"> for Hindi.
		await page.evaluate( () =>
			document.documentElement.setAttribute( 'lang', 'hi-IN' )
		);
		const hindi = await measure( page );
		expect( hindi.body ).toBeGreaterThanOrEqual( 15 );
		expect( hindi.body ).toBeLessThanOrEqual( 16 );
		expect( hindi.body ).toBeGreaterThan( english.body );
		expect( hindi.lineHeight ).toBeCloseTo( 1.75, 2 );
	} );
	test( 'an inline lang="hi" element on an English page keeps the Latin size', async ( {
		page,
	} ) => {
		await page.goto( '/interactive' );
		// A Hindi quotation inside English content, sized by the preset itself
		// (as the editor's font-size classes are): it must not grow, or Hindi
		// words would jump in size inside English text.
		const sizes = await page.evaluate( () => {
			const make = ( lang ) => {
				const el = document.createElement( 'p' );
				el.setAttribute( 'lang', lang );
				el.style.fontSize = 'var(--wp--preset--font-size--medium)';
				el.textContent = 'नमस्ते';
				document.querySelector( 'main' ).appendChild( el );
				return parseFloat( window.getComputedStyle( el ).fontSize );
			};
			return { en: make( 'en' ), hi: make( 'hi' ) };
		} );
		expect( sizes.hi ).toBe( sizes.en );
	} );
} );
