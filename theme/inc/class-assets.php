<?php
/**
 * Phase 2 interactivity assets concern.
 *
 * Owns everything Phase 2 adds to the Atlas theme: registration and conditional
 * enqueue of the three Interactivity API script modules (navigation, theme-mode,
 * copy), the plain interactivity stylesheet, the flicker-free dark-mode blocking
 * head script with its CSP-safe per-request nonce, the Copyable block style on
 * core/quote, and seeding gettext labels into the Interactivity stores. Keeps
 * Atlas\Setup unchanged and gives Phase 2 a single, testable home. Resolved via
 * the hand-rolled autoloader (Atlas\ -> inc/).
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for the Phase 2 interactive features.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object. The per-request CSP nonce is memoized in a
 * static property so every caller shares one value.
 */
class Assets {

	/**
	 * Script module handle for the navigation drawer.
	 *
	 * @var string
	 */
	const HANDLE_NAVIGATION = 'atlas-navigation';

	/**
	 * Script module handle for the dark/light mode toggle.
	 *
	 * @var string
	 */
	const HANDLE_THEME_MODE = 'atlas-theme-mode';

	/**
	 * Script module handle for copy-to-clipboard.
	 *
	 * @var string
	 */
	const HANDLE_COPY = 'atlas-copy';

	/**
	 * Class applied by Core when the Copyable block style is selected on a
	 * core/quote block. The single quote marker the copy feature keys on.
	 *
	 * @var string
	 */
	const COPY_MARKER = 'is-style-atlas-copyable';

	/**
	 * The single source of the drawer breakpoint.
	 *
	 * Rendered into the drawer root's data-breakpoint attribute (read by the
	 * JS matchMedia) and kept in lockstep with the CSS @media value in
	 * interactivity.css, so JS and CSS never drift.
	 *
	 * @var string
	 */
	const DRAWER_BREAKPOINT = '48rem';

	/**
	 * Per-request CSP nonce, memoized after first generation.
	 *
	 * @var string|null
	 */
	private static $csp_nonce = null;

	/**
	 * Register the Phase 2 WordPress hooks.
	 *
	 * Called once from functions.php immediately after Atlas\Setup::init().
	 * Keeps all Phase 2 hook wiring in one place so the boot order is explicit.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register_block_styles' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ) );
		add_action( 'wp_head', array( self::class, 'print_theme_mode_script' ), 0 );
	}

	/**
	 * Register the Copyable block style on core/quote.
	 *
	 * This is the minimum wiring that makes the mandated copy component
	 * reachable: selecting "Copyable" makes Core apply the COPY_MARKER class to
	 * the serialized block, which page_has_quotes() detects.
	 *
	 * @return void
	 */
	public static function register_block_styles(): void {
		register_block_style(
			'core/quote',
			array(
				'name'  => 'atlas-copyable',
				'label' => __( 'Copyable', 'atlas-theme' ),
			)
		);
	}

	/**
	 * Register and conditionally enqueue the Phase 2 assets.
	 *
	 * Runs on wp_enqueue_scripts (front end only). Registration is cheap (no
	 * bytes ship until enqueue). Each module reads its build-time .asset.php for
	 * real dependencies (including @wordpress/interactivity) and a cache-busting
	 * version; a missing .asset.php (build not run) is skipped so the page fails
	 * closed to the no-JS state rather than fataling.
	 *
	 * @return void
	 */
	public static function register_assets(): void {
		if ( is_admin() ) {
			return;
		}

		$modules = array(
			'navigation' => self::HANDLE_NAVIGATION,
			'theme-mode' => self::HANDLE_THEME_MODE,
			'copy'       => self::HANDLE_COPY,
		);

		$registered = array();

		foreach ( $modules as $slug => $handle ) {
			$asset_file = ATLAS_THEME_DIR . "/build/{$slug}.asset.php";
			if ( ! file_exists( $asset_file ) ) {
				continue;
			}

			$asset = require $asset_file;

			wp_register_script_module(
				$handle,
				ATLAS_THEME_URI . "/build/{$slug}.js",
				isset( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
				isset( $asset['version'] ) ? $asset['version'] : ATLAS_THEME_VERSION
			);

			$registered[ $slug ] = true;
		}

		// The interactivity stylesheet ships wherever the interactive markup
		// renders so the no-JS fallback styles (and dark-mode preset re-point)
		// are always present. Plain file from src/css, versioned by the theme.
		wp_enqueue_style(
			'atlas-interactivity',
			ATLAS_THEME_URI . '/src/css/interactivity.css',
			array( 'atlas-theme' ),
			ATLAS_THEME_VERSION
		);

		// Seed the theme-mode gettext labels into global Interactivity state so
		// the module only selects between pre-translated strings (no JS i18n).
		wp_interactivity_state(
			'atlas/theme-mode',
			array(
				'labelToLight' => __( 'Switch to light mode', 'atlas-theme' ),
				'labelToDark'  => __( 'Switch to dark mode', 'atlas-theme' ),
			)
		);

		// Header controls are present on every front-end view; enqueue both.
		if ( isset( $registered['navigation'] ) ) {
			wp_enqueue_script_module( self::HANDLE_NAVIGATION );
		}
		if ( isset( $registered['theme-mode'] ) ) {
			wp_enqueue_script_module( self::HANDLE_THEME_MODE );
		}

		// Copy ships only where a copyable quote actually exists.
		if ( isset( $registered['copy'] ) && self::page_has_quotes() ) {
			wp_enqueue_script_module( self::HANDLE_COPY );
		}
	}

	/**
	 * Print the flicker-free dark-mode blocking head script.
	 *
	 * Hooked to wp_head at priority 0 so it runs before any stylesheet link
	 * applies: it reads localStorage 'atlas-theme-mode' (falling back to the
	 * prefers-color-scheme media query) and sets data-theme on the root element
	 * before paint. CSP-safe via a per-request nonce (never unsafe-inline); the
	 * nonce is escaped on output.
	 *
	 * @return void
	 */
	public static function print_theme_mode_script(): void {
		if ( is_admin() ) {
			return;
		}

		$nonce = self::get_csp_nonce();

		$script = <<<'JS'
(function(){try{var m=localStorage.getItem('atlas-theme-mode');if(!m){m=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-theme',m);}catch(e){}})();
JS;

		printf(
			'<script nonce="%1$s">%2$s</script>' . "\n",
			esc_attr( $nonce ),
			$script // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static inline bootstrap script, no dynamic data.
		);
	}

	/**
	 * Return the per-request CSP nonce, generating it lazily on first call.
	 *
	 * The single source of the nonce for the whole request: it is memoized in a
	 * static property so the dark-mode head script and any future CSP header
	 * layer share one identical value by construction. The value is passed
	 * through the 'atlas_csp_script_nonce' filter so other layers may observe
	 * (but should not fork) it.
	 *
	 * @return string The per-request nonce.
	 */
	public static function get_csp_nonce(): string {
		if ( null === self::$csp_nonce ) {
			self::$csp_nonce = base64_encode( random_bytes( 16 ) );
		}

		/**
		 * Filters the per-request CSP script nonce.
		 *
		 * @param string $nonce The memoized per-request nonce.
		 */
		return (string) apply_filters( 'atlas_csp_script_nonce', self::$csp_nonce );
	}

	/**
	 * Whether the current request renders a copyable quote.
	 *
	 * Front-end singular only, fail-closed. Correlates the marker to a
	 * core/quote block's className (recursively, so a nested quote counts)
	 * rather than a loose search over raw content, so a stray marker string
	 * elsewhere can never enqueue the copy module.
	 *
	 * @return bool True only when a core/quote carries the Copyable marker.
	 */
	public static function page_has_quotes(): bool {
		if ( is_admin() || ! is_singular() ) {
			return false;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		// Cheap pre-check before the more expensive parse.
		if ( ! has_block( 'core/quote', $post )
			|| ! str_contains( $post->post_content, self::COPY_MARKER ) ) {
			return false;
		}

		return self::blocks_have_copyable_quote( parse_blocks( $post->post_content ) );
	}

	/**
	 * Recursively test parsed blocks for a copyable core/quote.
	 *
	 * @param array $blocks Parsed block array from parse_blocks().
	 * @return bool True when a core/quote carries the Copyable marker.
	 */
	private static function blocks_have_copyable_quote( array $blocks ): bool {
		foreach ( $blocks as $block ) {
			if ( 'core/quote' === ( $block['blockName'] ?? '' )
				&& isset( $block['attrs']['className'] )
				&& str_contains( (string) $block['attrs']['className'], self::COPY_MARKER ) ) {
				return true;
			}

			if ( ! empty( $block['innerBlocks'] )
				&& self::blocks_have_copyable_quote( $block['innerBlocks'] ) ) {
				return true;
			}
		}

		return false;
	}
}
