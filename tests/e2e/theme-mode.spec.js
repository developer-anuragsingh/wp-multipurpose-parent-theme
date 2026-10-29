// @ts-check
const { test, expect } = require( '@playwright/test' );

test.describe( 'Atlas Dark/Light Mode Toggle E2E', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( '/interactive' );
	} );

	test( 'clicking toggle switches data-theme and persists in localStorage', async ( {
		page,
	} ) => {
		const toggle = page.locator( '.atlas-theme-toggle' );
		await expect( toggle ).toBeVisible();

		// Read initial theme attribute
		const initialTheme = await page.evaluate(
			() =>
				document.documentElement.getAttribute( 'data-theme' ) || 'light'
		);
		const expectedTheme = initialTheme === 'dark' ? 'light' : 'dark';

		// Toggle theme
		await toggle.click();

		// Verify root attribute
		await expect( page.locator( 'html' ) ).toHaveAttribute(
			'data-theme',
			expectedTheme
		);

		// Verify localStorage persistence
		const storedTheme = await page.evaluate( () =>
			localStorage.getItem( 'atlas-theme-mode' )
		);
		expect( storedTheme ).toBe( expectedTheme );

		// Reload page and verify persistence without flicker
		await page.reload();
		await expect( page.locator( 'html' ) ).toHaveAttribute(
			'data-theme',
			expectedTheme
		);
	} );
} );
