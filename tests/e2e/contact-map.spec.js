// @ts-check
const { test, expect } = require( '@playwright/test' );

/**
 * Count requests to Google and answer them locally (no external network in CI).
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<string[]>} Live list of requested Google URLs.
 */
async function trackGoogle( page ) {
	/** @type {string[]} */
	const urls = [];
	await page.route( /^https:\/\/([a-z]+\.)*google\.com\//, ( route ) => {
		urls.push( route.request().url() );
		return route.fulfill( {
			contentType: 'text/html',
			body: '<!doctype html><title>map</title>',
		} );
	} );
	return urls;
}

test.describe( 'Contact map loads from Google only after consent', () => {
	test( 'nothing is requested from Google until "Show map" is clicked', async ( {
		page,
	} ) => {
		const google = await trackGoogle( page );
		await page.goto( '/interactive' );
		const map = page.locator( '.atlas-map' );
		const show = map.locator( '.atlas-map__show' );
		await map.scrollIntoViewIfNeeded();
		await expect( show ).toBeVisible();
		await expect( map.locator( 'iframe' ) ).toHaveCount( 0 );
		expect( google ).toHaveLength( 0 );
		const before = await map.boundingBox();

		await show.click();

		const frame = map.locator( 'iframe' );
		await expect( frame ).toHaveCount( 1 );
		await expect( frame ).toHaveAttribute(
			'src',
			/^https:\/\/maps\.google\.com\/maps\?q=28\.6139%2C77\.2090/
		);
		await expect( frame ).toHaveAttribute(
			'title',
			'Office location on Google Maps'
		);
		await expect( frame ).toBeFocused();
		await expect( map.locator( '.atlas-map__consent' ) ).toBeHidden();
		await expect.poll( () => google.length ).toBeGreaterThan( 0 );
		// The map takes the placeholder's box: no shift below it.
		const after = await map.boundingBox();
		expect( after?.height ).toBe( before?.height );
	} );

	test( 'the notice describes the button, and the link opens Google Maps in a new tab', async ( {
		page,
	} ) => {
		await page.goto( '/interactive' );
		const show = page.locator( '.atlas-map__show' );
		await expect( show ).toHaveAccessibleDescription( /IP address/ );
		const link = page.locator( '.atlas-map__link' );
		await expect( link ).toHaveAttribute( 'target', '_blank' );
		await expect( link ).toHaveAttribute( 'rel', /noopener/ );
		await expect( link ).toHaveAccessibleName(
			'Open in Google Maps (opens in a new tab)'
		);
	} );
} );

test.describe( 'Contact map without JavaScript', () => {
	test.use( { javaScriptEnabled: false } );
	test( 'the useless button stays hidden and the link is the fallback', async ( {
		page,
	} ) => {
		const google = await trackGoogle( page );
		await page.goto( '/interactive' );
		await expect( page.locator( '.atlas-map__show' ) ).toBeHidden();
		await expect( page.locator( '.atlas-map__link' ) ).toBeVisible();
		await expect( page.locator( '.atlas-map iframe' ) ).toHaveCount( 0 );
		expect( google ).toHaveLength( 0 );
	} );
} );
