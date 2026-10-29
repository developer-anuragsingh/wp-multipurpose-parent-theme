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
