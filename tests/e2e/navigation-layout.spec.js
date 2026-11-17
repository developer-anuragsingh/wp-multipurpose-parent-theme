// @ts-check
/**
 * Layout-shift and no-JS guards for the mobile navigation.
 *
 * Why: the server cannot know the viewport, so the page used to ship the inline
 * nav and swap to the off-canvas drawer once navigation.js hydrated. On mobile
 * that made the header collapse (86px -> 58px on the live site) and pushed the
 * page up: CLS 0.12. The drawer layout must therefore apply from the very first
 * paint (pure CSS), and the menu must stay usable WITHOUT JavaScript via the
 * :target fallback. See docs/07-performance-and-qa/01-performance-budget.md §6.
 */
const { test, expect } = require( '@playwright/test' );

const MOBILE = { width: 412, height: 800 };

/**
 * Measure header height and the top of <main>.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<{header:number, mainTop:number}>} Rounded measurements.
 */
async function measure( page ) {
	return page.evaluate( () => ( {
		header: Math.round(
			document.querySelector( 'header' ).getBoundingClientRect().height
		),
		mainTop: Math.round(
			document.querySelector( 'main' ).getBoundingClientRect().top
		),
	} ) );
}

test.describe( 'Mobile navigation: no layout shift on hydration', () => {
	test.use( { viewport: MOBILE } );

	test( 'header height is identical before and after navigation.js hydrates', async ( {
		page,
		browser,
	} ) => {
		// Pre-hydration: the module is blocked, so only server HTML + CSS apply.
		const blocked = await browser.newPage( { viewport: MOBILE } );
		await blocked.route( '**/build/navigation.js', ( route ) =>
			route.abort()
		);
		await blocked.goto( '/interactive' );
		await expect( blocked.locator( '.atlas-nav' ) ).not.toHaveClass(
			/is-atlas-interactive/
		);
		const before = await measure( blocked );
		await blocked.close();

		// Post-hydration: the real, fully interactive page.
		await page.goto( '/interactive' );
		await expect( page.locator( '.atlas-nav' ) ).toHaveClass(
			/is-atlas-interactive/
		);
		const after = await measure( page );

		expect( after ).toEqual( before );
	} );

	test( 'hydration causes no layout shift in the header or of <main>', async ( {
		page,
	} ) => {
		// Only shifts of the header's contents or of <main> itself count here.
		// (Other components, e.g. the copy-to-clipboard button revealed inside a
		// quote, are separate concerns with their own guards/decisions.)
		await page.addInitScript( () => {
			// @ts-ignore Test-only global.
			window.__navShift = 0;
			new PerformanceObserver( ( list ) => {
				for ( const entry of list.getEntries() ) {
					// @ts-ignore LayoutShift is not in the DOM lib typings.
					if ( entry.hadRecentInput ) {
						continue;
					}
					// @ts-ignore
					const relevant = ( entry.sources || [] ).some( ( src ) => {
						const node = src.node;
						return (
							node &&
							( node.nodeName === 'MAIN' ||
								( node.closest && node.closest( 'header' ) ) )
						);
					} );
					if ( relevant ) {
						// @ts-ignore
						window.__navShift += entry.value;
					}
				}
			} ).observe( { type: 'layout-shift', buffered: true } );
		} );
		// Isolate hydration from font loading: the fixtures use the theme's real
		// webfonts, and on a slow CI runner they can arrive after first paint,
		// which reflows text for reasons unrelated to hydration. Hold every
		// theme module until the fonts are in, reset the counter, then release
		// the modules and measure only what hydration itself moves.
		let release = () => {};
		const gate = new Promise( ( resolve ) => {
			release = resolve;
		} );
		await page.route( '**/theme/build/*.js', async ( route ) => {
			await gate;
			await route.continue();
		} );

		// 'load' (and DOMContentLoaded) wait for module scripts, which are held,
		// so only wait for the response, then for the parsed DOM and the fonts.
		await page.goto( '/interactive', { waitUntil: 'commit' } );
		await page.waitForFunction( () => document.readyState !== 'loading' );
		await page.evaluate( () =>
			Promise.all( [
				document.fonts.load( '400 16px Inter' ),
				document.fonts.load( '700 16px Sora' ),
			] )
		);
		await page.waitForTimeout( 200 );
		await expect( page.locator( '.atlas-nav' ) ).not.toHaveClass(
			/is-atlas-interactive/
		);
		// @ts-ignore Test-only global.
		await page.evaluate( () => ( window.__navShift = 0 ) );

		release();
		await expect( page.locator( '.atlas-nav' ) ).toHaveClass(
			/is-atlas-interactive/
		);
		await expect( page.locator( '.atlas-theme-toggle' ) ).toBeVisible();
		await page.waitForTimeout( 500 );
		// @ts-ignore Test-only global.
		const shift = await page.evaluate( () => window.__navShift );
		expect( shift ).toBe( 0 );
	} );
} );

test.describe( 'JS-only header controls reserve their space', () => {
	test.use( { viewport: MOBILE } );

	test( 'the hidden theme toggle keeps its box but is invisible and unreachable', async ( {
		page,
	} ) => {
		await page.route( '**/build/theme-mode.js', ( route ) =>
			route.abort()
		);
		await page.goto( '/interactive' );
		const info = await page
			.locator( '.atlas-theme-toggle' )
			.evaluate( ( el ) => {
				const box = el.getBoundingClientRect();
				const style = window.getComputedStyle( el );
				return {
					hiddenAttr: el.hasAttribute( 'hidden' ),
					width: box.width,
					height: box.height,
					visibility: style.visibility,
				};
			} );
		// Still [hidden] (module blocked), yet occupying layout space...
		expect( info.hiddenAttr ).toBe( true );
		expect( info.width ).toBeGreaterThan( 0 );
		expect( info.height ).toBeGreaterThan( 0 );
		// ...while invisible, which also removes it from tab order and a11y tree.
		expect( info.visibility ).toBe( 'hidden' );
		await expect( page.locator( '.atlas-theme-toggle' ) ).toBeHidden();
	} );
} );

/**
 * Open and close the menu using only the server HTML + CSS (:target).
 *
 * @param {import('@playwright/test').Page} page Page already loaded.
 */
async function expectCssOnlyMenuWorks( page ) {
	const toggle = page.locator( '.atlas-nav__toggle' );
	const panel = page.locator( '#atlas-nav-panel' );

	// Closed by default: the toggle is shown, the menu is not.
	await expect( toggle ).toBeVisible();
	await expect( panel ).toBeHidden();

	// Following the link opens it through :target.
	await toggle.click();
	await expect( panel ).toBeVisible();
	await expect(
		page.locator( '#atlas-nav-panel a[href="#contact"]' )
	).toBeVisible();

	// The in-panel close link closes it again.
	await page.locator( '.atlas-nav__close' ).click();
	await expect( panel ).toBeHidden();
}

test.describe( 'Mobile navigation works without JavaScript', () => {
	test.use( { viewport: MOBILE, javaScriptEnabled: false } );

	test( 'the menu opens and closes with JS disabled (:target fallback)', async ( {
		page,
	} ) => {
		await page.goto( '/interactive' );
		await expectCssOnlyMenuWorks( page );
	} );
} );

test.describe( 'Mobile navigation survives a failed navigation.js', () => {
	test.use( { viewport: MOBILE } );

	test( 'the menu still opens and closes when the module is blocked', async ( {
		page,
	} ) => {
		await page.route( '**/build/navigation.js', ( route ) =>
			route.abort()
		);
		await page.goto( '/interactive' );
		await expect( page.locator( '.atlas-nav' ) ).not.toHaveClass(
			/is-atlas-interactive/
		);
		await expectCssOnlyMenuWorks( page );
	} );

	test( 'JS adopts a menu opened without JS (#atlas-nav-panel) and removes the hash', async ( {
		page,
	} ) => {
		await page.goto( '/interactive#atlas-nav-panel' );
		await expect( page.locator( '.atlas-nav' ) ).toHaveClass(
			/is-atlas-interactive/
		);
		await expect( page.locator( '.atlas-nav__toggle' ) ).toHaveAttribute(
			'aria-expanded',
			'true'
		);
		await expect( page.locator( '#atlas-nav-panel' ) ).toBeVisible();
		expect( new URL( page.url() ).hash ).toBe( '' );
	} );

	test( 'hydrated toggle does not change the URL and Space activates it', async ( {
		page,
	} ) => {
		await page.goto( '/interactive' );
		const toggle = page.locator( '.atlas-nav__toggle' );
		await expect( page.locator( '.atlas-nav' ) ).toHaveClass(
			/is-atlas-interactive/
		);
		await toggle.click();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
		expect( new URL( page.url() ).hash ).toBe( '' );

		await page.keyboard.press( 'Escape' );
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( toggle ).toBeFocused();

		await page.keyboard.press( 'Space' );
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
	} );
} );
