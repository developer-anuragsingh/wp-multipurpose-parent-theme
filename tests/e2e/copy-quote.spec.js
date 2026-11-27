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
		await expect( copyButton.locator( '.atlas-icon--copy' ) ).toBeVisible();

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
test.describe( 'Copy button reserves its space (no layout shift)', () => {
	/**
	 * Where the quote and the content after it sit on the page.
	 *
	 * @param {import('@playwright/test').Page} page Playwright page.
	 */
	const measure = ( page ) =>
		page
			.locator( '.wp-block-quote.is-style-atlas-copyable' )
			.evaluate( ( quote ) => {
				const next = quote.closest( 'section' )?.nextElementSibling;
				return {
					quoteHeight: quote.getBoundingClientRect().height,
					nextTop: next ? next.getBoundingClientRect().top : null,
				};
			} );
	test( 'revealing the button on hydration does not move the content below the quote', async ( {
		page,
		context,
	} ) => {
		await context.grantPermissions( [ 'clipboard-write' ] );
		// Hold the copy module until the fonts are in, so only the reveal itself
		// is measured (webfonts arriving late reflow text for other reasons).
		let release = () => {};
		const gate = new Promise( ( resolve ) => {
			release = resolve;
		} );
		await page.route( '**/theme/build/copy.js', async ( route ) => {
			await gate;
			await route.continue();
		} );
		await page.goto( '/interactive', { waitUntil: 'commit' } );
		await page.waitForFunction( () => document.readyState !== 'loading' );
		await page.evaluate( () =>
			Promise.all( [
				document.fonts.load( '400 16px Inter' ),
				document.fonts.load( '700 16px Sora' ),
			] )
		);
		await expect( page.locator( '.atlas-copy__button' ) ).toBeHidden();
		const before = await measure( page );
		release();
		await expect( page.locator( '.atlas-copy__button' ) ).toBeVisible();
		const after = await measure( page );
		expect( after ).toEqual( before );
	} );
	test( 'without the script the hidden button keeps its box but is invisible and unreachable', async ( {
		page,
	} ) => {
		await page.route( '**/theme/build/copy.js', ( route ) =>
			route.abort()
		);
		await page.goto( '/interactive' );
		const info = await page
			.locator( '.atlas-copy__button' )
			.evaluate( ( el ) => {
				const box = el.getBoundingClientRect();
				return {
					hiddenAttr: el.hasAttribute( 'hidden' ),
					width: box.width,
					height: box.height,
					visibility: window.getComputedStyle( el ).visibility,
				};
			} );
		expect( info.hiddenAttr ).toBe( true );
		expect( info.width ).toBeGreaterThan( 0 );
		expect( info.height ).toBeGreaterThan( 0 );
		// visibility:hidden also removes it from the tab order and a11y tree.
		expect( info.visibility ).toBe( 'hidden' );
		await expect( page.locator( '.atlas-copy__button' ) ).toBeHidden();
	} );
} );
