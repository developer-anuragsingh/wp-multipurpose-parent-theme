<?php
/**
 * Archive Infinite Scroll concern.
 *
 * Owns the Atlas theme's progressive enhancement infinite scroll feature for
 * WordPress archive and query blocks. Enhances standard core/query pagination
 * with native Interactivity API fetching (0 KB initial JS overhead) while
 * preserving server-rendered pagination links for search crawlers and non-JS clients.
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar and render filter for archive infinite scroll.
 */
class Infinite_Scroll {

	/**
	 * Script module handle for archive infinite scroll.
	 *
	 * @var string
	 */
	const HANDLE = 'atlas-infinite-scroll';

	/**
	 * Filter hook name allowing operators to disable or customize infinite scroll.
	 *
	 * @var string
	 */
	const FILTER_ENABLED = 'atlas_infinite_scroll_enabled';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ) );
		add_filter( 'render_block_core/query', array( self::class, 'render_infinite_scroll' ), 10, 2 );
	}

	/**
	 * Register the script module from its build manifest.
	 *
	 * @return void
	 */
	public static function register_assets(): void {
		if ( is_admin() || ! function_exists( 'wp_register_script_module' ) ) {
			return;
		}

		$asset_file = ATLAS_THEME_DIR . '/build/infinite-scroll.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_register_script_module(
			self::HANDLE,
			ATLAS_THEME_URI . '/build/infinite-scroll.js',
			isset( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
			isset( $asset['version'] ) ? $asset['version'] : ATLAS_THEME_VERSION
		);
	}

	/**
	 * Intercept core/query block rendering to attach infinite scroll directives.
	 *
	 * Only activates when a valid next-page link exists in the query pagination,
	 * ensuring single-page archives pass through untouched.
	 *
	 * @param string $block_content Rendered HTML of the block.
	 * @param array  $block         Parsed block attributes and data.
	 * @return string Modified block HTML with infinite scroll markup.
	 */
	public static function render_infinite_scroll( string $block_content, array $block ): string {
		if ( is_admin() ) {
			return $block_content;
		}

		/**
		 * Filter whether infinite scroll should be enabled on the current query block.
		 *
		 * @param bool  $enabled Whether infinite scroll is active.
		 * @param array $block   Parsed query block data.
		 */
		if ( ! (bool) apply_filters( self::FILTER_ENABLED, true, $block ) ) {
			return $block_content;
		}

		// Check if a next page link exists in pagination.
		if ( ! str_contains( $block_content, 'wp-block-query-pagination-next' ) ) {
			return $block_content;
		}

		$matches = array();
		$matched = preg_match( '/wp-block-query-pagination-next[^>]*>.*?<a[^>]+href=([\'"])(.*?)\1/is', $block_content, $matches );
		if ( ! $matched || empty( $matches[2] ) ) {
			return $block_content;
		}

		$next_url = $matches[2];

		if ( function_exists( 'wp_enqueue_script_module' ) ) {
			wp_enqueue_script_module( self::HANDLE );
		}

		$context = wp_json_encode(
			array(
				'nextUrl'       => esc_url_raw( $next_url ),
				'loading'       => false,
				'hasMore'       => true,
				'labelLoading'  => __( 'Loading more posts…', 'atlas-theme' ),
				'labelLoadMore' => __( 'Load more posts', 'atlas-theme' ),
				'labelLoaded'   => __( 'More posts loaded.', 'atlas-theme' ),
				'labelEnd'      => __( 'All posts loaded.', 'atlas-theme' ),
				'labelError'    => __( 'Error loading posts.', 'atlas-theme' ),
			)
		);

		if ( false === $context ) {
			return $block_content;
		}

		$controls = sprintf(
			'<div class="atlas-infinite-scroll" data-wp-interactive="atlas/infinite-scroll" data-wp-context=\'%1$s\' data-wp-init="callbacks.init">' .
				'<button type="button" class="atlas-infinite-scroll__button wp-element-button" hidden data-wp-on--click="actions.loadMore" data-wp-bind--disabled="state.isLoading">' .
					'<span data-wp-text="state.buttonText">%2$s</span>' .
				'</button>' .
				'<div class="atlas-infinite-scroll__sentinel" data-wp-init="callbacks.observeSentinel" style="height:1px;"></div>' .
				'<div class="atlas-infinite-scroll__status" role="status" aria-live="polite" data-wp-text="state.statusText"></div>' .
			'</div>',
			esc_attr( $context ),
			esc_html__( 'Load more posts', 'atlas-theme' )
		);

		$pos = strripos( $block_content, '</div>' );
		if ( false === $pos ) {
			return $block_content . $controls;
		}

		return substr( $block_content, 0, $pos ) . $controls . substr( $block_content, $pos );
	}
}
