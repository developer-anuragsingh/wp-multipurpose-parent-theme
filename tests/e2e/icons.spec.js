// @ts-check
/**
 * Icon behaviour: swaps, colour inheritance in both modes, and touch targets.
 * Names and markup are asserted in the PHP suite (tests/test-icons.php); axe
 * (accessibility.spec.js) checks the rendered result for unnamed controls.
 */
const { test, expect } = require( '@playwright/test' );

test.describe( 'Header icon controls', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( '/interactive' );
		await expect( page.locator( '.atlas-theme-toggle' ) ).toBeVisible();
	} );

	test( 'the theme toggle shows a sun in light mode and a moon in dark mode', async ( {
		page,
	} ) => {
		const toggle = page.locator( '.atlas-theme-toggle' );
		await page.evaluate( () =>
			document.documentElement.setAttribute( 'data-theme', 'light' )
		);
		await expect( toggle.locator( '.atlas-icon--sun' ) ).toBeVisible();
		await expect( toggle.locator( '.atlas-icon--moon' ) ).toBeHidden();

		await page.evaluate( () =>
			document.documentElement.setAttribute( 'data-theme', 'dark' )
		);
		await expect( toggle.locator( '.atlas-icon--moon' ) ).toBeVisible();
		await expect( toggle.locator( '.atlas-icon--sun' ) ).toBeHidden();

		// Icon-only: no text, but a name and a matching tooltip.
		expect( ( await toggle.innerText() ).trim() ).toBe( '' );
		const label = await toggle.getAttribute( 'aria-label' );
		expect( label ).toMatch( /mode/i );
		await expect( toggle ).toHaveAttribute( 'title', label || '' );
	} );

	for ( const mode of [ 'light', 'dark' ] ) {
		test( `icons take the control's text colour in ${ mode } mode`, async ( {
			page,
		} ) => {
			await page.evaluate( ( m ) => {
				document.documentElement.setAttribute( 'data-theme', m );
			}, mode );
			const result = await page
				.locator( '.atlas-theme-toggle' )
				.evaluate( ( el ) => {
					const svg = el.querySelector( 'svg:not([hidden])' );
					const visible = [ ...el.querySelectorAll( 'svg' ) ].find(
						( s ) => getComputedStyle( s ).display !== 'none' &&
							s.getBoundingClientRect().width > 0
					);
					return {
						found: !! visible || !! svg,
						stroke: visible && getComputedStyle( visible ).stroke,
						color: getComputedStyle( el ).color,
					};
				} );
			expect( result.found ).toBe( true );
			expect( result.stroke ).toBe( result.color );
		} );
	}

	test( 'the theme toggle is at least a 44px touch target', async ( {
		page,
	} ) => {
		const box = await page.locator( '.atlas-theme-toggle' ).boundingBox();
		expect( box && box.width ).toBeGreaterThanOrEqual( 44 );
		expect( box && box.height ).toBeGreaterThanOrEqual( 44 );
	} );
} );

test.describe( 'Mobile menu icons', () => {
	test.use( { viewport: { width: 412, height: 800 } } );

	test( 'hamburger becomes an X when open, and the in-panel close is an icon-only control', async ( {
		page,
	} ) => {
		await page.goto( '/interactive' );
		const toggle = page.locator( '.atlas-nav__toggle' );
		await expect( page.locator( '.atlas-nav' ) ).toHaveClass(
			/is-atlas-interactive/
		);

		// 44px target and a name, no text.
		const box = await toggle.boundingBox();
		expect( box && box.width ).toBeGreaterThanOrEqual( 44 );
		expect( box && box.height ).toBeGreaterThanOrEqual( 44 );
		expect( ( await toggle.innerText() ).trim() ).toBe( '' );
		await expect( toggle ).toHaveAttribute(
			'aria-label',
			'Toggle navigation menu'
		);

		await expect( toggle.locator( '.atlas-icon--menu' ) ).toBeVisible();
		await expect( toggle.locator( '.atlas-icon--close' ) ).toBeHidden();

		await toggle.click();
		await expect( toggle.locator( '.atlas-icon--close' ) ).toBeVisible();
		await expect( toggle.locator( '.atlas-icon--menu' ) ).toBeHidden();

		const close = page.locator( '.atlas-nav__close' );
		await expect( close ).toBeVisible();
		await expect( close ).toHaveAttribute( 'aria-label', 'Close menu' );
		expect( ( await close.innerText() ).trim() ).toBe( '' );
		await expect( close.locator( 'svg' ) ).toBeVisible();

		await close.click();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
	} );
} );
