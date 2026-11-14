// @ts-check
/**
 * Automated accessibility checks (axe-core) for the theme's interactive
 * harness and block patterns, in light and dark mode, on desktop and mobile.
 *
 * Automated scanning finds roughly a third of WCAG problems; it does NOT
 * replace the manual keyboard and screen-reader pass in
 * docs/07-performance-and-qa/02-testing-and-ci.md §5. Treat a green run as
 * "no machine-detectable violations", not as WCAG conformance.
 *
 * Point it at a real WordPress site instead of the fixtures with:
 *   A11Y_BASE_URL=http://localhost:8885 A11Y_PATHS=/,/contact-form/ npx playwright test accessibility
 */
const { test, expect } = require( '@playwright/test' );
const AxeBuilder = require( '@axe-core/playwright' ).default;

const TAGS = [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ];
// An absolute base URL overrides the fixture server (no trailing slash).
const BASE = ( process.env.A11Y_BASE_URL || '' ).replace( /\/$/, '' );
const PAGES = process.env.A11Y_PATHS
	? process.env.A11Y_PATHS.split( ',' )
	: [ '/interactive', '/patterns' ];

/**
 * Format violations so a failure explains itself in the CI log.
 *
 * @param {import('axe-core').Result[]} violations Axe violations.
 * @return {string} Human-readable summary.
 */
function summarize( violations ) {
	return violations
		.map(
			( v ) =>
				`[${ v.impact }] ${ v.id }: ${ v.help }\n` +
				v.nodes
					.slice( 0, 5 )
					.map( ( n ) => `    ${ n.target.join( ' ' ) }` )
					.join( '\n' )
		)
		.join( '\n' );
}

for ( const path of PAGES ) {
	for ( const mode of [ 'light', 'dark' ] ) {
		test( `no axe violations on ${ path } (${ mode } mode)`, async ( {
			page,
		} ) => {
			await page.addInitScript( ( m ) => {
				try {
					window.localStorage.setItem( 'atlas-theme-mode', m );
				} catch ( e ) {
					// Storage unavailable: the page falls back to the OS preference.
				}
			}, mode );
			await page.goto( BASE + path );
			// The real theme's inline head script sets data-theme before paint;
			// the static fixtures do not include it, so apply the same attribute.
			await page.evaluate( ( m ) => {
				document.documentElement.setAttribute( 'data-theme', m );
			}, mode );
			await expect( page.locator( 'html' ) ).toHaveAttribute(
				'data-theme',
				mode
			);

			const { violations } = await new AxeBuilder( { page } )
				.withTags( TAGS )
				.analyze();

			expect( violations.length, summarize( violations ) ).toBe( 0 );
		} );
	}
}
