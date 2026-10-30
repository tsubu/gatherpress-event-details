/**
 * ESLint flat config.
 *
 * Mirrors GatherPress's own config, so the plugin is linted with the same
 * WordPress rules as the code it extends. The plugin is resolved through
 * @wordpress/scripts rather than declared again.
 */

/**
 * External dependencies
 */
const path = require( 'path' );

const scriptsDir = path.dirname(
	require.resolve( '@wordpress/scripts/package.json' )
);
const requireFromScripts = ( name ) =>
	require( require.resolve( name, { paths: [ scriptsDir ] } ) );

const wpPlugin = requireFromScripts( '@wordpress/eslint-plugin' );
const globals = requireFromScripts( 'globals' );

module.exports = [
	{
		ignores: [ 'build/**', 'coverage/**', 'node_modules/**', 'vendor/**' ],
	},
	...wpPlugin.configs[ 'recommended-with-formatting' ],
	{
		languageOptions: {
			globals: {
				...globals.browser,
			},
		},
		rules: {
			'@wordpress/i18n-text-domain': [
				'error',
				{ allowedTextDomain: 'gatherpress-event-details' },
			],
			// WordPress provides @wordpress/* as script externals at runtime, and the
			// resolver does not follow their .cjs / .mjs entry points.
			'import/no-unresolved': [ 'error', { ignore: [ '^@wordpress/' ] } ],
			yoda: [ 'error', 'always', { exceptRange: true } ],
		},
	},
	{
		files: [ 'test/**/*.js' ],
		languageOptions: {
			globals: {
				...globals.jest,
			},
		},
	},
];
