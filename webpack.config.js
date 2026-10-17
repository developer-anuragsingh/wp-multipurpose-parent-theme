/**
 * Atlas Phase 2 build config.
 *
 * Builds the three Interactivity API stores from theme/src/js as native script
 * MODULES (ESM output, `@wordpress/interactivity` recorded as a module
 * dependency in each .asset.php) into theme/build. Run via
 * `wp-scripts build --experimental-modules`, which makes the default
 * @wordpress/scripts config export an array `[ scriptConfig, moduleConfig ]`;
 * we keep only the module config and point its entry/output at our custom
 * source layout. No CSS entry: theme/src/css/interactivity.css is a plain
 * hand-authored file enqueued directly, outside this pipeline.
 */

const defaults = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

const entry = {
	navigation: path.resolve( __dirname, 'theme/src/js/navigation.js' ),
	'theme-mode': path.resolve( __dirname, 'theme/src/js/theme-mode.js' ),
	copy: path.resolve( __dirname, 'theme/src/js/copy.js' ),
};

const outputPath = path.resolve( __dirname, 'theme/build' );

/**
 * Apply the Atlas entry/output overrides onto a base config.
 *
 * @param {Object} config A @wordpress/scripts webpack config object.
 * @return {Object} The config with Atlas entry/output applied.
 */
const withAtlas = ( config ) => ( {
	...config,
	entry,
	output: {
		...config.output,
		path: outputPath,
	},
} );

// Under --experimental-modules the default export is [ scriptConfig,
// moduleConfig ]; keep only the module config so output is true ESM with
// @wordpress/interactivity as a module dependency.
module.exports = Array.isArray( defaults )
	? withAtlas( defaults[ 1 ] )
	: withAtlas( defaults );
