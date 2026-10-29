<?php
/**
 * Unit tests for Atlas\Infinite_Scroll.
 *
 * @package AtlasTheme
 */

class Test_Infinite_Scroll {

	public static function run(): array {
		$results = array();

		// 1. Constant definitions.
		$results[] = array(
			'name'   => 'Infinite_Scroll defines module handle constant',
			'passed' => 'atlas-infinite-scroll' === Atlas\Infinite_Scroll::HANDLE,
		);

		$results[] = array(
			'name'   => 'Infinite_Scroll defines filter hook constant',
			'passed' => 'atlas_infinite_scroll_enabled' === Atlas\Infinite_Scroll::FILTER_ENABLED,
		);

		// 2. Passthrough when no next page exists.
		$single_page_html = '<div class="wp-block-query"><ul class="wp-block-post-template"><li>Post 1</li></ul></div>';
		$block_data       = array( 'blockName' => 'core/query' );
		$output           = Atlas\Infinite_Scroll::render_infinite_scroll( $single_page_html, $block_data );

		$results[] = array(
			'name'   => 'Infinite_Scroll leaves single-page queries untouched without next link',
			'passed' => $output === $single_page_html,
		);

		// 3. Injection when next page link exists.
		$paginated_html = '<div class="wp-block-query"><ul class="wp-block-post-template"><li>Post 1</li></ul><div class="wp-block-query-pagination"><div class="wp-block-query-pagination-next"><a href="https://example.com/page/2/">Next</a></div></div></div>';
		$enhanced_html  = Atlas\Infinite_Scroll::render_infinite_scroll( $paginated_html, $block_data );

		$results[] = array(
			'name'   => 'Infinite_Scroll injects data-wp-interactive atlas/infinite-scroll',
			'passed' => str_contains( $enhanced_html, 'data-wp-interactive="atlas/infinite-scroll"' ),
		);

		$results[] = array(
			'name'   => 'Infinite_Scroll injects sentinel element for IntersectionObserver',
			'passed' => str_contains( $enhanced_html, 'class="atlas-infinite-scroll__sentinel"' ),
		);

		$results[] = array(
			'name'   => 'Infinite_Scroll injects accessible button and status live region',
			'passed' => str_contains( $enhanced_html, 'class="atlas-infinite-scroll__button' )
				&& str_contains( $enhanced_html, 'class="atlas-infinite-scroll__status"' )
				&& str_contains( $enhanced_html, 'aria-live="polite"' ),
		);

		$results[] = array(
			'name'   => 'Infinite_Scroll encodes correct nextUrl into context',
			'passed' => str_contains( $enhanced_html, 'https://example.com/page/2/' ),
		);

		// 4. Respects filter when disabled.
		add_filter( 'atlas_infinite_scroll_enabled', '__return_false' );
		$disabled_html = Atlas\Infinite_Scroll::render_infinite_scroll( $paginated_html, $block_data );
		// Clean up filter
		global $atlas_test_filters;
		unset( $atlas_test_filters['atlas_infinite_scroll_enabled'] );

		$results[] = array(
			'name'   => 'Infinite_Scroll respects atlas_infinite_scroll_enabled filter returning false',
			'passed' => $disabled_html === $paginated_html,
		);

		return $results;
	}
}
