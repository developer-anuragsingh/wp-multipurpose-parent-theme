// @ts-check
const { defineConfig, devices } = require( '@playwright/test' );

/**
 * Playwright Configuration for Atlas Multipurpose Parent Theme.
 *
 * Implements E2E and Visual Regression test suites per docs/07-performance-and-qa/03-testing-strategy.md.
 * Automatically spawns a local fixture HTTP server serving theme assets, patterns, and Interactivity API modules.
 */
module.exports = defineConfig( {
	testDir: './tests/e2e',
	fullyParallel: true,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: process.env.CI ? 1 : undefined,
	reporter: process.env.CI ? [ [ 'github' ], [ 'list' ] ] : 'list',
	timeout: 30000,
	snapshotPathTemplate: '{testDir}/visual-regression.spec.js-snapshots/{arg}-{projectName}{ext}',
	expect: {
		toHaveScreenshot: {
			maxDiffPixelRatio: 0.05,
			animations: 'disabled',
		},
	},
	use: {
		baseURL: 'http://127.0.0.1:3456',
		trace: 'on-first-retry',
	},
	projects: [
		{
			name: 'desktop-chrome',
			use: {
				...devices[ 'Desktop Chrome' ],
				viewport: { width: 1280, height: 720 },
				channel: process.env.CI ? undefined : 'chrome',
			},
		},
		{
			name: 'mobile-chrome',
			use: {
				...devices[ 'Pixel 7' ],
				viewport: { width: 375, height: 667 },
				channel: process.env.CI ? undefined : 'chrome',
			},
		},
	],
	webServer: {
		command: 'node tests/e2e/fixtures/server.js',
		url: 'http://127.0.0.1:3456/health',
		reuseExistingServer: ! process.env.CI,
		timeout: 10000,
	},
} );
