// @ts-check
const { test, expect } = require( '@playwright/test' );

test.describe( 'Atlas Mobile Navigation Drawer E2E', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( '/interactive' );
	} );

	test( 'desktop displays inline navigation without drawer collapse', async ( {
		page,
		isMobile,
	} ) => {
		if ( isMobile ) {
			test.skip();
		}
		const navPanel = page.locator( '#atlas-nav-panel' );
		await expect( navPanel ).toBeVisible();
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
	} );
} );
