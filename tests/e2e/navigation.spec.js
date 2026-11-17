// @ts-check
const { test, expect } = require( '@playwright/test' );

test.describe( 'Atlas Mobile Navigation Drawer E2E', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( '/interactive' );
	} );

	test( 'desktop displays inline navigation without drawer collapse and hides toggle', async ( {
		page,
		isMobile,
	} ) => {
		if ( isMobile ) {
			test.skip();
		}
		const navPanel = page.locator( '#atlas-nav-panel' );
		await expect( navPanel ).toBeVisible();

		// The sandwich toggle button must be hidden on desktop (never visible on wide screens)
		const toggle = page.locator( '.atlas-nav__toggle' );
		await expect( toggle ).toBeHidden();
	} );

	test( 'mobile drawer toggle opens panel and manages aria-expanded', async ( {
		page,
		isMobile,
	} ) => {
		if ( ! isMobile ) {
			test.skip();
		}

		const toggle = page.locator( '.atlas-nav__toggle' );
		await expect( toggle ).toBeVisible();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );

		// Open drawer
		await toggle.click();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );

		// Press Escape to close and verify focus returns to toggle
		await page.keyboard.press( 'Escape' );
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( toggle ).toBeFocused();

		// Re-open with the toggle, then close with the in-panel close control.
		// The open panel is position:fixed and covers the header toggle, so a
		// user cannot press the toggle a second time (a test that tried to was
		// only passing while the 0.3s slide-in was still in progress). The close
		// control is the real way out, besides Escape.
		await toggle.click();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( page.locator( '#atlas-nav-panel' ) ).toBeVisible();
		await page.locator( '.atlas-nav__close' ).click();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( toggle ).toBeFocused();
	} );
} );
