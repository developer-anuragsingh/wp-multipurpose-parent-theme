/**
 * Lightweight HTTP Fixture Server for Playwright E2E and Visual Regression Tests.
 *
 * Serves theme stylesheets, built Interactivity API modules, and HTML harnesses.
 * Operates without external runtime dependencies (using native node:http and node:fs).
 */

const http = require( 'http' );
const fs = require( 'fs' );
const path = require( 'path' );

const PORT = 3456;
const REPO_ROOT = path.resolve( __dirname, '../../..' );
const THEME_DIR = path.resolve( REPO_ROOT, 'theme' );
const FIXTURES_DIR = __dirname;

const MIME_TYPES = {
	'.html': 'text/html; charset=UTF-8',
	'.css': 'text/css; charset=UTF-8',
	'.js': 'application/javascript; charset=UTF-8',
	'.json': 'application/json; charset=UTF-8',
	'.woff2': 'font/woff2',
	'.png': 'image/png',
	'.svg': 'image/svg+xml',
};

/**
 * Build the colour preset CSS (custom properties and has-* classes) from the
 * REAL theme.json, the way WordPress generates it. The harnesses used to
 * hand-copy these values and drifted (wrong primary/accent), which made visual
 * and accessibility results meaningless.
 *
 * @return {string} CSS text.
 */
function paletteCss() {
	const themeJson = JSON.parse(
		fs.readFileSync( path.join( THEME_DIR, 'theme.json' ), 'utf8' )
	);
	const palette = themeJson.settings.color.palette;
	const vars = palette
		.map( ( c ) => `--wp--preset--color--${ c.slug }:${ c.color };` )
		.join( '' );
	const classes = palette
		.map(
			( c ) =>
				`.has-${ c.slug }-color{color:var(--wp--preset--color--${ c.slug })}` +
				`.has-${ c.slug }-background-color{background-color:var(--wp--preset--color--${ c.slug })}` +
				`.has-${ c.slug }-border-color{border-color:var(--wp--preset--color--${ c.slug })}`
		)
		.join( '' );
	// The link element colour, exactly as WordPress emits it from
	// styles.elements.link (zero-specificity :where, so component classes win).
	const link = themeJson.styles?.elements?.link;
	const toVar = ( ref ) =>
		typeof ref === 'string' && ref.startsWith( 'var:preset|color|' )
			? `var(--wp--preset--color--${ ref.split( '|' ).pop() })`
			: null;
	const linkColor = toVar( link?.color?.text );
	const hoverColor = toVar( link?.[ ':hover' ]?.color?.text );
	const linkCss = linkColor
		? `:root :where(a:where(:not(.wp-element-button))){color:${ linkColor };text-decoration:underline}` +
		  ( hoverColor
				? `:root :where(a:where(:not(.wp-element-button)):hover){color:${ hoverColor }}`
				: '' )
		: '';
	return `:root{${ vars }}${ classes }${ linkCss }`;
}

const server = http.createServer( ( req, res ) => {
	const parsedUrl = new URL(
		req.url || '/',
		`http://${ req.headers.host || '127.0.0.1' }`
	);
	const pathname = parsedUrl.pathname;

	// Health check for Playwright webServer readiness.
	if ( pathname === '/health' ) {
		res.writeHead( 200, { 'Content-Type': 'text/plain' } );
		res.end( 'OK' );
		return;
	}

	if ( pathname === '/theme-palette.css' ) {
		res.writeHead( 200, {
			'Content-Type': 'text/css; charset=UTF-8',
			'Cache-Control': 'no-cache',
		} );
		res.end( paletteCss() );
		return;
	}
	// Route to fixtures or theme assets.
	let filePath = '';

	if ( pathname === '/' || pathname === '/interactive' ) {
		filePath = path.join( FIXTURES_DIR, 'interactive-harness.html' );
	} else if ( pathname === '/patterns' ) {
		filePath = path.join( FIXTURES_DIR, 'patterns-harness.html' );
	} else if ( pathname.startsWith( '/theme/' ) ) {
		filePath = path.join( THEME_DIR, pathname.replace( '/theme/', '' ) );
	} else {
		filePath = path.join( FIXTURES_DIR, pathname );
	}

	// Security: prevent directory traversal.
	if ( ! filePath.startsWith( REPO_ROOT ) ) {
		res.writeHead( 403, { 'Content-Type': 'text/plain' } );
		res.end( 'Forbidden' );
		return;
	}

	if (
		! fs.existsSync( filePath ) ||
		fs.statSync( filePath ).isDirectory()
	) {
		res.writeHead( 404, { 'Content-Type': 'text/plain' } );
		res.end( 'Not Found' );
		return;
	}

	const ext = path.extname( filePath );
	const contentType = MIME_TYPES[ ext ] || 'application/octet-stream';

	res.writeHead( 200, {
		'Content-Type': contentType,
		'Access-Control-Allow-Origin': '*',
		'Cache-Control': 'no-cache',
	} );

	fs.createReadStream( filePath ).pipe( res );
} );

server.listen( PORT, '127.0.0.1', () => {
	// eslint-disable-next-line no-console
	console.log(
		`Atlas E2E Test Fixture Server running on http://127.0.0.1:${ PORT }`
	);
} );
