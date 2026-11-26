<?php
/**
 * What the theme prints into real pages: security headers, the CSP hash of the
 * exact inline script, JSON-LD for real posts, SEO meta and the language switch.
 *
 * @package AtlasTheme
 */

class Test_Front_End_Output extends WP_UnitTestCase {

	public function test_security_headers_are_added_by_wp_headers(): void {
		$headers = apply_filters( 'wp_headers', array() );

		$this->assertSame( 'nosniff', $headers['X-Content-Type-Options'] ?? '' );
		$this->assertSame( 'SAMEORIGIN', $headers['X-Frame-Options'] ?? '' );
		$this->assertArrayHasKey( 'Referrer-Policy', $headers );
		$this->assertArrayHasKey( 'Content-Security-Policy-Report-Only', $headers );
	}

	public function test_the_csp_hash_matches_the_inline_script_wordpress_actually_prints(): void {
		$this->go_to( home_url( '/' ) );
		$head = get_echo( 'wp_head' );

		$this->assertSame( 1, preg_match( '#<script nonce="[^"]+">(\(function\(\)\{try\{var m=localStorage[^<]*)</script>#', $head, $m ), 'Theme-mode script not found in wp_head.' );

		$hash = "'sha256-" . base64_encode( hash( 'sha256', $m[1], true ) ) . "'"; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$csp  = apply_filters( 'wp_headers', array() )['Content-Security-Policy-Report-Only'];
		$this->assertStringContainsString( $hash, $csp );
		$this->assertDoesNotMatchRegularExpression( "/script-src[^;]*unsafe-inline/", $csp );
	}

	public function test_core_inline_scripts_receive_the_csp_nonce(): void {
		$tag = wp_get_inline_script_tag( 'console.log(1);' );
		$this->assertStringContainsString( 'nonce="' . Atlas\Assets::get_csp_nonce() . '"', $tag );
	}

	public function test_a_real_post_gets_blogposting_and_breadcrumb_json_ld(): void {
		$cat  = self::factory()->category->create( array( 'name' => 'News' ) );
		$user = self::factory()->user->create( array( 'display_name' => 'Jane Writer' ) );
		$post = self::factory()->post->create(
			array(
				'post_title'    => 'Real Post',
				'post_author'   => $user,
				'post_category' => array( $cat ),
			)
		);

		$this->go_to( get_permalink( $post ) );
		$graph = $this->graph();

		$article = $this->node( $graph, 'BlogPosting' );
		$this->assertSame( 'Real Post', $article['headline'] );
		$this->assertSame( 'Jane Writer', $article['author']['name'] );
		$this->assertSame( get_permalink( $post ), $article['mainEntityOfPage']['@id'] );

		$crumbs = wp_list_pluck( $this->node( $graph, 'BreadcrumbList' )['itemListElement'], 'name' );
		$this->assertSame( array( 'Home', 'News', 'Real Post' ), $crumbs );
	}

	public function test_renaming_the_site_refreshes_an_already_cached_post_graph(): void {
		$post = self::factory()->post->create( array( 'post_title' => 'Cached Post' ) );
		$this->go_to( get_permalink( $post ) );
		$this->assertSame( 'Atlas Test Site', $this->node( $this->graph(), 'BlogPosting' )['publisher']['name'] );

		update_option( 'blogname', 'Renamed Site' );

		$this->go_to( get_permalink( $post ) );
		$this->assertSame( 'Renamed Site', $this->node( $this->graph(), 'BlogPosting' )['publisher']['name'] );
	}

	public function test_a_hostile_post_title_cannot_break_out_of_the_json_ld_script(): void {
		$title = 'Evil </script><!--<script>alert(1)</script> & co';
		$post  = self::factory()->post->create( array( 'post_title' => $title ) );
		$this->go_to( get_permalink( $post ) );

		$out = get_echo( array( 'Atlas\\Schema', 'print_json_ld' ) );
		$this->assertSame( 1, substr_count( $out, '</script>' ) );
		$this->assertStringNotContainsString( '<!--', $out );
		$this->assertSame( wp_strip_all_tags( get_the_title( $post ) ), wp_strip_all_tags( $this->node( $this->graph(), 'BlogPosting' )['headline'] ) );
	}

	public function test_meta_description_and_search_noindex(): void {
		// Core escapes the tagline when it is saved, so markup in it is literal
		// text, never HTML. The meta tag must carry it without breaking out.
		update_option( 'blogdescription', 'Tagline "with" <b>markup</b>' );
		$this->go_to( home_url( '/' ) );
		$head = get_echo( array( 'Atlas\\Seo', 'print_meta' ) );

		$this->assertSame( 1, preg_match( '#<meta name="description" content="([^"]*)">#', $head, $m ), 'No meta description.' );
		$this->assertStringNotContainsString( '<b>', $m[1] );
		$this->assertStringContainsString( 'Tagline &quot;with&quot;', $m[1] );
		$this->assertStringNotContainsString( '&amp;quot;', $m[1], 'The description is double-escaped.' );
		$this->assertStringNotContainsString( 'noindex', $head );

		$this->go_to( home_url( '/?s=atlas' ) );
		$this->assertStringContainsString( '<meta name="robots" content="noindex,follow">', get_echo( array( 'Atlas\\Seo', 'print_meta' ) ) );
	}

	public function test_og_image_is_an_absolute_url(): void {
		$this->go_to( home_url( '/' ) );
		$head = get_echo( array( 'Atlas\\Seo', 'print_meta' ) );

		$this->assertSame( 1, preg_match( '#<meta property="og:image" content="([^"]+)">#', $head, $m ) );
		$this->assertStringStartsWith( 'http', $m[1] );
		$this->assertStringEndsWith( '/og-default.png', $m[1] );
	}

	public function test_language_switch_shows_short_labels_with_full_names(): void {
		$this->go_to( home_url( '/' ) );
		$html = Atlas\I18n::render_switcher();

		$this->assertMatchesRegularExpression( '#<a [^>]*data-lang="hi_IN"[^>]*aria-label="HI \(हिन्दी\)"[^>]*>HI</a>#u', $html );
		$this->assertStringContainsString( 'lang=hi_IN', $html );
	}

	/**
	 * Print and decode the current request's JSON-LD graph.
	 *
	 * @return array<int,array>
	 */
	private function graph(): array {
		$out = get_echo( array( 'Atlas\\Schema', 'print_json_ld' ) );
		$this->assertSame( 1, preg_match( '#<script type="application/ld\+json">(.*)</script>#s', $out, $m ), 'No JSON-LD printed.' );
		$data = json_decode( $m[1], true );
		$this->assertIsArray( $data, 'JSON-LD is not valid JSON.' );
		return $data['@graph'];
	}

	private function node( array $graph, string $type ): array {
		foreach ( $graph as $node ) {
			if ( ( $node['@type'] ?? '' ) === $type ) {
				return $node;
			}
		}
		$this->fail( "No {$type} node in the graph." );
	}
}
