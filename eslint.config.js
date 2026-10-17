/**
 * Project ESLint flat config for the Atlas theme JS source.
 *
 * Reuses the @wordpress/scripts default flat config and appends one override:
 * `@wordpress/interactivity` is externalised by the build's dependency
 * extraction and provided by WordPress at runtime, so it is never installed
 * into node_modules. Without ignoring it, `import/no-unresolved` would
 * false-positive on every Interactivity store module.
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...defaultConfig,
	{
		rules: {
			'import/no-unresolved': [
				'error',
				{ ignore: [ '^@wordpress/interactivity$' ] },
			],
		},
	},
];
