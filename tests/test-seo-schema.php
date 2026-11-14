<?php
/**
 * Output tests for Atlas\Seo (meta) and Atlas\Schema (JSON-LD).
 *
 * IMPORTANT: this suite must run LAST. Its final tests define a SEO-plugin
 * constant (constants cannot be undefined), which makes both concerns back off
 * for the rest of the process.
 *
 * @package AtlasTheme
 */

class Test_Seo_Schema {

	private static function capture( callable $fn ): string {
		ob_start();
		$fn();
		return (string) ob_get_clean();
	}

	private static function request( array $ctx ): void {
		global $atlas_test_request;
		$atlas_test_request = $ctx;
	}

	private static function filter( string $hook, callable $cb ): void {
		global $atlas_test_filters;
		$atlas_test_filters[ $hook ] = array(
			array(
				'callback'      => $cb,
				'priority'      => 10,
				'accepted_args' => 1,
			),
		);
	}

	private static function unfilter( string $hook ): void {
		global $atlas_test_filters;
		unset( $atlas_test_filters[ $hook ] );
	}

	public static function run(): array {
		global $atlas_test_transients, $atlas_test_bloginfo;
		$results = array();

		// --- Seo: robots. ---
		self::request( array( 'is_search' => true ) );
		$out = self::capture( array( 'Atlas\Seo', 'print_meta' ) );

		$results[] = array(
			'name'   => 'Seo: search results are marked noindex,follow',
			'passed' => str_contains( $out, '<meta name="robots" content="noindex,follow">' ),
		);

		self::request( array( 'canonical' => 'https://example.com/' ) );
		$out = self::capture( array( 'Atlas\Seo', 'print_meta' ) );

		$results[] = array(
			'name'   => 'Seo: a normal page is NOT noindexed',
			'passed' => ! str_contains( $out, 'noindex' ),
		);

		self::filter(
			'atlas_seo_maintenance',
			static function () {
				return true;
			}
		);
		$out = self::capture( array( 'Atlas\Seo', 'print_meta' ) );
		self::unfilter( 'atlas_seo_maintenance' );

		$results[] = array(
			'name'   => 'Seo: maintenance mode is marked noindex',
			'passed' => str_contains( $out, 'content="noindex,follow"' ),
		);

		// --- Seo: Open Graph / canonical / escaping. ---
		self::request(
			array(
				'is_front_page' => true,
				'title'         => '"><script>alert(1)</script> Home',
				'canonical'     => 'https://example.com/?a=1&b=2',
			)
		);
		$atlas_test_bloginfo['description'] = 'Tag <b>line</b>   with   spaces';
		$out                                 = self::capture( array( 'Atlas\Seo', 'print_meta' ) );

		$results[] = array(
			'name'   => 'Seo: a hostile document title cannot break out of the og:title attribute',
			'passed' => ! str_contains( $out, '<script' ) && str_contains( $out, 'property="og:title" content="&quot;&gt;&lt;script&gt;' ),
		);

		$results[] = array(
			'name'   => 'Seo: a standard <meta name="description"> is printed (search snippet) with the cleaned text',
			'passed' => str_contains( $out, '<meta name="description" content="Tag line with spaces">' )
				&& 1 === substr_count( $out, 'name="description"' ),
		);

		$results[] = array(
			'name'   => 'Seo: og:description is stripped of tags and whitespace-collapsed',
			'passed' => str_contains( $out, 'property="og:description" content="Tag line with spaces"' ),
		);

		$results[] = array(
			'name'   => 'Seo: canonical link and og:url use the escaped canonical URL',
			'passed' => str_contains( $out, '<link rel="canonical" href="https://example.com/?a=1&amp;b=2">' )
				&& str_contains( $out, 'property="og:url"' ),
		);

		$results[] = array(
			'name'   => 'Seo: falls back to the default OG image and a large Twitter card',
			'passed' => str_contains( $out, 'og-default.png' ) && str_contains( $out, 'name="twitter:card" content="summary_large_image"' ),
		);

		$atlas_test_bloginfo['description'] = '"><script>alert(1)</script>';
		$out                                 = self::capture( array( 'Atlas\Seo', 'print_meta' ) );

		$results[] = array(
			'name'   => 'Seo: a hostile description cannot break out of the meta description attribute',
			'passed' => ! str_contains( $out, '<script' ) && str_contains( $out, 'name="description" content="&quot;&gt;">' ),
		);

		$atlas_test_bloginfo['description'] = '';
		$out                                 = self::capture( array( 'Atlas\Seo', 'print_meta' ) );

		$results[] = array(
			'name'   => 'Seo: no description means no empty meta description tag',
			'passed' => ! str_contains( $out, 'name="description"' ) && ! str_contains( $out, 'og:description' ),
		);

		$atlas_test_bloginfo['description'] = str_repeat( 'x', 500 );
		$out                                 = self::capture( array( 'Atlas\Seo', 'print_meta' ) );
		preg_match( '/og:description" content="([^"]*)"/', $out, $m );

		$results[] = array(
			'name'   => 'Seo: og:description is capped at DESCRIPTION_MAX characters',
			'passed' => isset( $m[1] ) && Atlas\Seo::DESCRIPTION_MAX === strlen( $m[1] ),
		);

		self::filter(
			'atlas_seo_hreflang_locales',
			static function () {
				return array( 'hi-IN' => 'javascript:alert(1)', 'en' => 'https://example.com/en/' );
			}
		);
		$out = self::capture( array( 'Atlas\Seo', 'print_meta' ) );
		self::unfilter( 'atlas_seo_hreflang_locales' );

		$results[] = array(
			'name'   => 'Seo: hreflang emits nothing by default and never a javascript: URL when supplied',
			'passed' => ! str_contains( $out, 'javascript:' ) && str_contains( $out, 'hreflang="en" href="https://example.com/en/"' ),
		);

		self::request( array( 'is_front_page' => true, 'canonical' => 'https://example.com/' ) );
		self::filter(
			'atlas_seo_enabled',
			static function () {
				return false;
			}
		);
		$out = self::capture( array( 'Atlas\Seo', 'print_meta' ) );
		self::unfilter( 'atlas_seo_enabled' );

		$results[] = array(
			'name'   => 'Seo: the master gate filter disables all output',
			'passed' => '' === $out,
		);

		// --- Schema. ---
		$atlas_test_bloginfo['name'] = 'Atlas Test Site';
		$atlas_test_transients       = array();
		self::request( array( 'is_front_page' => true ) );
		$out = self::capture( array( 'Atlas\Schema', 'print_json_ld' ) );
		preg_match( '#<script type="application/ld\+json">(.*)</script>#s', $out, $m );
		$graph = isset( $m[1] ) ? json_decode( $m[1], true ) : null;

		$types = is_array( $graph ) ? array_column( $graph['@graph'] ?? array(), '@type' ) : array();

		$results[] = array(
			'name'   => 'Schema: the front page emits valid JSON-LD with Organization and WebSite',
			'passed' => is_array( $graph )
				&& 'https://schema.org' === ( $graph['@context'] ?? '' )
				&& in_array( 'Organization', $types, true )
				&& in_array( 'WebSite', $types, true ),
		);

		$results[] = array(
			'name'   => 'Schema: the graph is cached in a transient after the first build',
			'passed' => 1 === count( $atlas_test_transients ),
		);

		$atlas_test_transients       = array();
		$atlas_test_bloginfo['name'] = 'Evil </script><!--<script>alert(1)</script> & Co';
		$out                         = self::capture( array( 'Atlas\Schema', 'print_json_ld' ) );

		$results[] = array(
			'name'   => 'Schema: "</script>" and "<!--<script>" in site data cannot close or confuse the inline script',
			'passed' => 1 === substr_count( $out, '</script>' )
				&& 1 === substr_count( $out, '<script' )
				&& ! str_contains( $out, '<!--' )
				&& str_contains( $out, 'Evil \\u003C/script\\u003E' ),
		);

		$atlas_test_bloginfo['name'] = 'Atlas Test Site';

		$atlas_test_transients = array( Atlas\Schema::cache_key( 'front' ) => array( array( '@type' => 'Thing', 'name' => 'Cached' ) ) );
		$out                   = self::capture( array( 'Atlas\Schema', 'print_json_ld' ) );

		$results[] = array(
			'name'   => 'Schema: a cached graph is served without rebuilding',
			'passed' => str_contains( $out, '"Cached"' ),
		);

		Atlas\Schema::invalidate_cache( 0 );

		$results[] = array(
			'name'   => 'Schema: invalidate_cache clears the front-page graph',
			'passed' => ! isset( $atlas_test_transients[ Atlas\Schema::cache_key( 'front' ) ] ),
		);

		self::request( array( 'canonical' => 'https://example.com/x/' ) );
		$out = self::capture( array( 'Atlas\Schema', 'print_json_ld' ) );

		$results[] = array(
			'name'   => 'Schema: a context with no nodes prints nothing (no empty script tag)',
			'passed' => '' === $out,
		);

		self::request( array( 'is_front_page' => true ) );
		$atlas_test_transients = array();
		self::filter(
			'atlas_schema_enabled',
			static function () {
				return false;
			}
		);
		$out = self::capture( array( 'Atlas\Schema', 'print_json_ld' ) );
		self::unfilter( 'atlas_schema_enabled' );

		$results[] = array(
			'name'   => 'Schema: the master gate filter disables output',
			'passed' => '' === $out,
		);

		// --- Plugin back-off (defines a constant; keep these last). ---
		$atlas_test_transients = array();
		self::request( array( 'is_front_page' => true, 'is_search' => true, 'canonical' => 'https://example.com/' ) );
		$before = self::capture( array( 'Atlas\Seo', 'print_meta' ) ) . self::capture( array( 'Atlas\Schema', 'print_json_ld' ) );

		if ( ! defined( 'RANK_MATH_VERSION' ) ) {
			define( 'RANK_MATH_VERSION', '1.0' );
		}
		$atlas_test_transients = array();
		$seo_after             = self::capture( array( 'Atlas\Seo', 'print_meta' ) );
		$schema_after          = self::capture( array( 'Atlas\Schema', 'print_json_ld' ) );

		$results[] = array(
			'name'   => 'Seo: with an SEO plugin active it still emits noindex but backs off OG/canonical',
			'passed' => str_contains( $before, 'og:title' )
				&& str_contains( $seo_after, 'noindex,follow' )
				&& ! str_contains( $seo_after, 'og:title' )
				&& ! str_contains( $seo_after, 'name="description"' )
				&& ! str_contains( $seo_after, 'rel="canonical"' ),
		);

		$results[] = array(
			'name'   => 'Schema: with an SEO plugin active it emits no JSON-LD (no duplicate graph)',
			'passed' => str_contains( $before, 'application/ld+json' ) && '' === $schema_after,
		);

		return $results;
	}
}
