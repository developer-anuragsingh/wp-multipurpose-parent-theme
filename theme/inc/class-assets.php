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
	 * Track whether the initial content image has been prioritized for LCP.
	 *
	 * @var bool
	 */
	private static $lcp_optimized = false;

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
		add_action( 'wp_head', array( self::class, 'preload_critical_fonts' ), 1 );
		add_filter( 'render_block_core/quote', array( self::class, 'render_copy_controls' ), 10, 2 );
		add_filter( 'wp_content_img_tag', array( self::class, 'optimize_content_images' ), 10, 2 );
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
	 * Inject the copy-to-clipboard controls into a Copyable core/quote.
	 *
	 * Runs on render_block_core/quote (front end). Only quotes carrying the
	 * Copyable marker get the controls; all other quotes pass through
	 * untouched. The injected markup is the DOM contract the atlas/copy
	 * Interactivity store (build/copy.js) binds to: an interactive wrapper with
	 * per-instance context, a button that ships hidden (revealed on hydration
	 * only when the Clipboard API is usable), and a visually-hidden polite
	 * aria-live region. User-facing labels come from gettext (literal
	 * 'atlas-theme') and are seeded into context as JSON so the JS needs no i18n
	 * runtime. Controls are inserted just before the closing </blockquote> so
	 * the store's `ref.closest('.wp-block-quote')` text lookup still works.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The parsed block (name, attrs, innerBlocks).
	 * @return string The block HTML, with copy controls when Copyable.
	 */
	public static function render_copy_controls( string $block_content, array $block ): string {
		if ( is_admin() ) {
			return $block_content;
		}

		$class_name = isset( $block['attrs']['className'] ) ? (string) $block['attrs']['className'] : '';
		if ( ! str_contains( $class_name, self::COPY_MARKER ) ) {
			return $block_content;
		}

		$context = wp_json_encode(
			array(
				'copied'      => false,
				'labelCopy'   => __( 'Copy quote', 'atlas-theme' ),
				'labelCopied' => __( 'Copied!', 'atlas-theme' ),
			)
		);

		if ( false === $context ) {
			return $block_content;
		}

		$controls = sprintf(
			'<div class="atlas-copy" data-wp-interactive="atlas/copy" data-wp-context=\'%1$s\' data-wp-init="callbacks.init">' .
				'<button type="button" class="atlas-copy__button" hidden data-wp-on--click="actions.copy" data-wp-bind--aria-label="state.triggerLabel" data-wp-class--is-copied="state.isCopied">' .
					'<span class="atlas-copy__icon" aria-hidden="true" data-wp-text="state.iconGlyph"></span>' .
					'<span class="atlas-copy__label" data-wp-text="state.visibleLabel">%2$s</span>' .
				'</button>' .
				'<span class="atlas-copy__status" data-wp-bind--aria-live="state.ariaLive" data-wp-text="state.statusText"></span>' .
			'</div>',
			esc_attr( $context ),
			esc_html__( 'Copy quote', 'atlas-theme' )
		);

		// Insert before the final closing </blockquote> so the controls sit
		// inside .wp-block-quote (the store reads quote text via closest()).
		$pos = strripos( $block_content, '</blockquote>' );
		if ( false === $pos ) {
			return $block_content . $controls;
		}

		return substr( $block_content, 0, $pos ) . $controls . substr( $block_content, $pos );
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

	/**
	 * Preload the critical body font in the document head.
	 *
	 * Hooked to wp_head at priority 1 so it loads immediately after the theme
	 * mode script, warming the primary variable font cache before stylesheet
	 * evaluation to improve FCP and eliminate layout shift (CLS).
	 *
	 * @return void
	 */
	public static function preload_critical_fonts(): void {
		if ( is_admin() ) {
			return;
		}

		$default_fonts = array(
			ATLAS_THEME_URI . '/assets/fonts/inter-variable.woff2',
		);

		/**
		 * Filter the critical font URLs preloaded in <head>.
		 *
		 * @param string[] $fonts Array of font asset URLs to preload.
		 */
		$fonts = (array) apply_filters( 'atlas_critical_fonts_preload', $default_fonts );

		foreach ( $fonts as $font_url ) {
			if ( is_string( $font_url ) && '' !== $font_url ) {
				printf(
					'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
					esc_url( $font_url )
				);
			}
		}
	}

	/**
	 * Prioritize the initial content image for LCP performance.
	 *
	 * Hooked to wp_content_img_tag. Ensures the first above-the-fold content
	 * image receives fetchpriority="high" and removes loading="lazy" to reduce
	 * Largest Contentful Paint (LCP) time per the performance budget.
	 *
	 * @param string $filtered_image Complete img HTML tag.
	 * @param string $context        Context of the image (e.g. the_content).
	 * @return string Optimized img HTML tag.
	 */
	public static function optimize_content_images( string $filtered_image, string $context ): string {
		if ( is_admin() || self::$lcp_optimized ) {
			return $filtered_image;
		}

		// Only optimize in standard page/post content contexts.
		if ( ! in_array( $context, array( 'the_content', 'the_excerpt' ), true ) ) {
			return $filtered_image;
		}

		/**
		 * Filter whether to apply automated LCP image optimization.
		 *
		 * @param bool $enable Whether to prioritize the first content image.
		 */
		if ( ! (bool) apply_filters( 'atlas_lcp_image_optimization', true ) ) {
			return $filtered_image;
		}

		self::$lcp_optimized = true;

		// Ensure loading="lazy" is not applied to the LCP image.
		$filtered_image = preg_replace( '/\s+loading=["\']lazy["\']/i', ' loading="eager"', $filtered_image );

		// Inject fetchpriority="high" if not already present.
		if ( ! str_contains( $filtered_image, 'fetchpriority=' ) ) {
			$filtered_image = preg_replace( '/<img\s+/i', '<img fetchpriority="high" ', $filtered_image, 1 );
		}

		return $filtered_image;
	}
}
