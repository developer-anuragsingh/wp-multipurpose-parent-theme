<?php
/**
 * UI-states concern.
 *
 * Owns the accessible UI-state system layered onto the Atlas theme: the block
 * pattern registry for the archive empty-state and search no-results patterns,
 * and (in later phases) the nonce-verified REST route, Interactivity store
 * seeding, and the enqueue of the built ui-states module and stylesheet. Mirrors
 * Atlas\Assets precisely — a static registrar wired from functions.php after
 * Atlas\Assets::init(). Resolved via the hand-rolled autoloader (Atlas\ -> inc/).
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for the UI-state features.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object. All public surface is reachable from the
 * WordPress hooks wired in init().
 */
class UI_States {

	/**
	 * REST namespace the single async-contract route registers under.
	 *
	 * Versioned (atlas/v1) so the contract shape can evolve behind a new
	 * namespace without breaking already-shipped clients.
	 *
	 * @var string
	 */
	const REST_NAMESPACE = 'atlas/v1';

	/**
	 * REST route path (relative to self::REST_NAMESPACE) for the UI-state
	 * contract. The one endpoint the client uses for retry, timeout detection,
	 * maintenance, and success/form submission.
	 *
	 * @var string
	 */
	const REST_ROUTE = '/ui-state';

	/**
	 * Interactivity store namespace shared by every UI-state module.
	 *
	 * @var string
	 */
	const STORE = 'atlas/ui-states';

	/**
	 * Script module handle for the built UI-state module.
	 *
	 * The single build entry (src/js/ui-states.js) ships as one module under
	 * this handle, mirroring the per-feature handles in Atlas\Assets.
	 *
	 * @var string
	 */
	const MODULE_HANDLE = 'atlas-ui-states';

	/**
	 * Build slug shared by the module and its asset manifest.
	 *
	 * Keeps the three build paths (build/{SLUG}.js, build/{SLUG}.asset.php,
	 * build/{SLUG}.css) in lockstep from one source of truth.
	 *
	 * @var string
	 */
	const BUILD_SLUG = 'ui-states';

	/**
	 * Stylesheet handle for the built UI-state stylesheet.
	 *
	 * @var string
	 */
	const STYLE_HANDLE = 'atlas-ui-states';

	/**
	 * The slow-request threshold in milliseconds.
	 *
	 * The single source of the 8s threshold: seeded into the store so JS crosses
	 * exactly this boundary before announcing the slow-request notice (R5.4),
	 * never a value hard-coded in the module.
	 *
	 * @var int
	 */
	const SLOW_THRESHOLD_MS = 8000;

	/**
	 * Block pattern category slug the UI-state patterns register under.
	 *
	 * @var string
	 */
	const CATEGORY = 'atlas';

	/**
	 * The UI-state block patterns, mapping each registered slug to the pattern
	 * file (relative to the theme's patterns/ directory) that renders it.
	 *
	 * Registered explicitly (rather than relying on Core's directory
	 * auto-discovery) so this concern is the single, testable home for pattern
	 * registration and so the patterns render as PHP — their dynamic term
	 * lookups and output escaping run at render time.
	 *
	 * @var array<string, string>
	 */
	const PATTERNS = array(
		'atlas/archive-empty-state' => 'archive-empty-state.php',
		'atlas/search-no-results'   => 'search-no-results.php',
	);

	/**
	 * Register the UI-state WordPress hooks.
	 *
	 * Called once from functions.php immediately after Atlas\Assets::init().
	 * Keeps all UI-state hook wiring in one place so the boot order is explicit.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register_block_patterns' ) );
		add_action( 'rest_api_init', array( self::class, 'register_rest_route' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ) );
	}

	/**
	 * Register the block pattern category and the UI-state patterns.
	 *
	 * Runs on init. Registers the 'atlas' pattern category once, then each
	 * pattern in self::PATTERNS from the theme's patterns/ directory using its
	 * header-comment metadata (Title, Categories, Description, Inserter). A
	 * missing pattern file is skipped so a partial theme never fatals.
	 *
	 * @return void
	 */
	public static function register_block_patterns(): void {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		register_block_pattern_category(
			self::CATEGORY,
			array( 'label' => __( 'Atlas', 'atlas-theme' ) )
		);

		foreach ( self::PATTERNS as $slug => $file ) {
			$path = ATLAS_THEME_DIR . '/patterns/' . $file;
			if ( ! file_exists( $path ) ) {
				continue;
			}

			$headers = self::read_pattern_headers( $path );

			register_block_pattern(
				$slug,
				array(
					'title'       => $headers['title'],
					'description' => $headers['description'],
					'categories'  => array( self::CATEGORY ),
					'inserter'    => $headers['inserter'],
					'content'     => self::render_pattern( $path ),
				)
			);
		}
	}

	/**
	 * Seed the Interactivity store and enqueue the built UI-state assets.
	 *
	 * Runs on wp_enqueue_scripts (front end only, guarded by is_admin() exactly
	 * like Atlas\Assets). First seeds the shared atlas/ui-states store with the
	 * server config the modules read instead of a global: the REST URL, a fresh
	 * wp_rest nonce, the slow-request threshold, and the pre-translated gettext
	 * labels (so JS performs no runtime i18n, matching the theme-mode
	 * precedent). Then registers the single build module from its build-time
	 * .asset.php (real dependencies — including @wordpress/interactivity — and a
	 * cache-busting version) and enqueues it; a missing .asset.php (build not
	 * run) is skipped so the page fails closed to the no-JS state rather than
	 * fataling. The built stylesheet is enqueued the same way, guarded on its
	 * presence.
	 *
	 * @return void
	 */
	public static function register_assets(): void {
		if ( is_admin() ) {
			return;
		}

		// Seed the shared store before the module loads so it reads config from
		// state, not a global. Labels are pre-translated here (no JS i18n).
		wp_interactivity_state(
			self::STORE,
			array(
				'restUrl'       => rest_url( self::REST_NAMESPACE . self::REST_ROUTE ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'slowThreshold' => self::SLOW_THRESHOLD_MS,
				'labels'        => array(
					'offline'           => __( 'You appear to be offline. Check your connection and try again.', 'atlas-theme' ),
					'slow'              => __( 'This is taking longer than usual. Please wait.', 'atlas-theme' ),
					'failure'           => __( 'Something went wrong. Please try again.', 'atlas-theme' ),
					'maintenance'       => __( 'The site is undergoing maintenance. Please try again shortly.', 'atlas-theme' ),
					'success'           => __( 'Done.', 'atlas-theme' ),
					'sessionExpired'    => __( 'Your session has expired. Please sign in again to continue.', 'atlas-theme' ),
					'commentSubmitted'  => __( 'Your comment has been posted.', 'atlas-theme' ),
					'pendingModeration' => __( 'Your comment is awaiting moderation.', 'atlas-theme' ),
				),
			)
		);

		// Register + enqueue the single built module. Fail closed when the build
		// is absent so the page degrades to the no-JS state instead of fataling.
		$asset_file = ATLAS_THEME_DIR . '/build/' . self::BUILD_SLUG . '.asset.php';
		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;

			wp_register_script_module(
				self::MODULE_HANDLE,
				ATLAS_THEME_URI . '/build/' . self::BUILD_SLUG . '.js',
				isset( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
				isset( $asset['version'] ) ? $asset['version'] : ATLAS_THEME_VERSION
			);

			wp_enqueue_script_module( self::MODULE_HANDLE );
		}

		// The built stylesheet ships wherever the interactive markup renders.
		// Guarded on presence so a theme without a build never 404s the asset.
		$style_file = ATLAS_THEME_DIR . '/build/' . self::BUILD_SLUG . '.css';
		if ( file_exists( $style_file ) ) {
			wp_enqueue_style(
				self::STYLE_HANDLE,
				ATLAS_THEME_URI . '/build/' . self::BUILD_SLUG . '.css',
				array( 'atlas-theme' ),
				ATLAS_THEME_VERSION
			);
		}
	}

	/**
	 * Register the nonce-verified UI-state REST route.
	 *
	 * Runs on rest_api_init. Registers a single route (namespace atlas/v1, path
	 * /ui-state) answering both GET and POST so the client has one endpoint for
	 * reads and submissions. verify_nonce() is the permission_callback and the
	 * sole authentication gate: it runs before handle_request(), so a request
	 * that fails nonce verification never reaches the handler and returns no
	 * data.
	 *
	 * @return void
	 */
	public static function register_rest_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => array( \WP_REST_Server::READABLE, \WP_REST_Server::CREATABLE ),
				'callback'            => array( self::class, 'handle_request' ),
				'permission_callback' => array( self::class, 'verify_nonce' ),
			)
		);
	}

	/**
	 * Permission callback: verify the WordPress REST nonce on every request.
	 *
	 * Reads the X-WP-Nonce request header and checks it against the 'wp_rest'
	 * action with wp_verify_nonce(). Returning a WP_Error (rather than false)
	 * lets the endpoint surface an explicit authentication status: the client
	 * classifies it as an auth error and, for a logged-in visitor, treats it as
	 * session expiry. A logged-in visitor whose nonce has merely lapsed gets 403
	 * (authenticated but stale token); an anonymous caller gets 401. Because
	 * this is the permission_callback, a failure short-circuits the request
	 * before handle_request() runs, so no data is ever returned on failure.
	 *
	 * @param \WP_REST_Request $request The incoming REST request.
	 * @return true|\WP_Error True when the nonce is valid, WP_Error otherwise.
	 */
	public static function verify_nonce( \WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return true;
		}

		return new \WP_Error(
			'rest_cookie_invalid_nonce',
			__( 'Your session has expired. Please refresh and sign in again.', 'atlas-theme' ),
			array( 'status' => is_user_logged_in() ? 403 : 401 )
		);
	}

	/**
	 * Route callback: return the single async-contract response shape.
	 *
	 * Every response is the one stable shape the client has a single code path
	 * for: { status, data, message }, where status is 'success', 'error', or
	 * 'maintenance' and message is pre-translated via the atlas-theme text
	 * domain (the client renders it verbatim, performing no runtime i18n). When
	 * the site is in maintenance the response carries status 'maintenance' and
	 * HTTP 503 so the client can show the maintenance notice rather than the
	 * generic failure toast; otherwise it is a 200 success. Nonce failures never
	 * reach here — verify_nonce() gates them as an authentication error.
	 *
	 * @param \WP_REST_Request $request The incoming (nonce-verified) request.
	 * @return \WP_REST_Response The single-contract response.
	 */
	public static function handle_request( \WP_REST_Request $request ): \WP_REST_Response {
		unset( $request );

		if ( function_exists( 'wp_is_maintenance_mode' ) && wp_is_maintenance_mode() ) {
			return new \WP_REST_Response(
				array(
					'status'  => 'maintenance',
					'data'    => array(),
					'message' => __( 'The site is undergoing maintenance. Please try again shortly.', 'atlas-theme' ),
				),
				503
			);
		}

		return new \WP_REST_Response(
			array(
				'status'  => 'success',
				'data'    => array(),
				'message' => __( 'Request completed successfully.', 'atlas-theme' ),
			),
			200
		);
	}

	/**
	 * Read a pattern file's header-comment metadata.
	 *
	 * Parses the Title, Description, and Inserter headers from the pattern file
	 * so the registered pattern carries the same human-facing metadata authored
	 * in the file. Falls back to the slug-derived values when a header is
	 * absent. Inserter defaults to true unless explicitly set to a falsey value.
	 *
	 * @param string $path Absolute path to the pattern file.
	 * @return array{title:string,description:string,inserter:bool} Parsed headers.
	 */
	private static function read_pattern_headers( string $path ): array {
		$data = get_file_data(
			$path,
			array(
				'title'       => 'Title',
				'description' => 'Description',
				'inserter'    => 'Inserter',
			)
		);

		$inserter = $data['inserter'];

		return array(
			'title'       => '' !== $data['title'] ? $data['title'] : '',
			'description' => $data['description'],
			'inserter'    => ! in_array( strtolower( $inserter ), array( 'no', 'false', '0' ), true ),
		);
	}

	/**
	 * Render a PHP pattern file to its block-markup string.
	 *
	 * The patterns are PHP so their dynamic term lookups and output escaping run
	 * at render time; buffering their output yields the serialized block markup
	 * register_block_pattern() stores as the pattern content.
	 *
	 * @param string $path Absolute path to the pattern file.
	 * @return string The rendered block markup.
	 */
	private static function render_pattern( string $path ): string {
		ob_start();
		require $path;
		return (string) ob_get_clean();
	}
}
