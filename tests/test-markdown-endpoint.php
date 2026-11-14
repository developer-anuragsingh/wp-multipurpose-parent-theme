<?php
/**
 * Tests for Atlas\Markdown_Endpoint conversion, safety, caching and size cap.
 *
 * @package AtlasTheme
 */

class Test_Markdown_Endpoint {

	public static function run(): array {
		global $atlas_test_posts, $atlas_test_transients, $atlas_test_filters;
		$results = array();

		$content = implode(
			"\n",
			array(
				'<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Sub &amp; Title</h3><!-- /wp:heading -->',
				'<!-- wp:paragraph --><p>Hello <a href="https://example.com/a?x=1&amp;y=2">link</a> and <strong>bold</strong>.</p><!-- /wp:paragraph -->',
				'<!-- wp:code --><pre class="wp-block-code"><code>if ( $a &lt; 1 ) {}</code></pre><!-- /wp:code -->',
			)
		);

		$md = Atlas\Markdown_Endpoint::blocks_to_markdown( $content, 'My <b>Title</b>' );

		$results[] = array(
			'name'   => 'Markdown: title becomes an H1 and tags are stripped from it',
			'passed' => str_starts_with( $md, "# My Title\n" ),
		);

		$results[] = array(
			'name'   => 'Markdown: headings keep their level and decode entities',
			'passed' => str_contains( $md, '### Sub & Title' ),
		);

		$results[] = array(
			'name'   => 'Markdown: inline links are preserved and other tags removed',
			'passed' => str_contains( $md, 'Hello [link](https://example.com/a?x=1&y=2) and bold.' ),
		);

		$results[] = array(
			'name'   => 'Markdown: code blocks are fenced with literal (decoded) text',
			'passed' => str_contains( $md, "```\nif ( \$a < 1 ) {}\n```" ),
		);

		$hostile = implode(
			"\n",
			array(
				'<!-- wp:paragraph --><p><a href="javascript:alert(1)">click</a> <script>alert(2)</script>safe</p><!-- /wp:paragraph -->',
				'<!-- wp:image --><figure><img src="javascript:alert(3)" alt="x"/></figure><!-- /wp:image -->',
			)
		);
		$hmd     = Atlas\Markdown_Endpoint::blocks_to_markdown( $hostile, '' );

		$results[] = array(
			'name'   => 'Markdown: javascript: URLs are dropped from links and images',
			'passed' => ! str_contains( $hmd, 'javascript:' ) && ! str_contains( $hmd, '](' ),
		);

		$results[] = array(
			'name'   => 'Markdown: <script> markup never reaches the output',
			'passed' => ! str_contains( $hmd, '<script' ),
		);

		$results[] = array(
			'name'   => 'Markdown: empty content and empty title yield an empty string',
			'passed' => '' === Atlas\Markdown_Endpoint::blocks_to_markdown( '', '' ),
		);

		// --- Caching + size cap. ---
		$atlas_test_transients = array();
		$atlas_test_posts      = array(
			7 => array(
				'ID'                => 7,
				'post_title'        => 'Cached',
				'post_content'      => '<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph -->',
				'post_modified_gmt' => '2026-01-01 00:00:00',
				'post_status'       => 'publish',
				'post_type'         => 'page',
			),
		);

		$first = Atlas\Markdown_Endpoint::markdown_for_post( 7 );
		$atlas_test_posts[7]['post_content'] = '<!-- wp:paragraph --><p>Changed</p><!-- /wp:paragraph -->';
		$second = Atlas\Markdown_Endpoint::markdown_for_post( 7 );

		$results[] = array(
			'name'   => 'Markdown: a repeat request with the same modified time is served from cache',
			'passed' => str_contains( $first, 'First' ) && $first === $second && 1 === count( $atlas_test_transients ),
		);

		$atlas_test_posts[7]['post_modified_gmt'] = '2026-02-02 00:00:00';
		$third                                    = Atlas\Markdown_Endpoint::markdown_for_post( 7 );

		$results[] = array(
			'name'   => 'Markdown: editing the post (new modified time) bypasses the stale cache entry',
			'passed' => str_contains( $third, 'Changed' ),
		);

		$atlas_test_transients                  = array();
		$atlas_test_posts[7]['post_modified_gmt'] = '2026-03-03 00:00:00';
		$atlas_test_posts[7]['post_content']      = '<!-- wp:paragraph --><p>' . str_repeat( 'é', 3000 ) . '</p><!-- /wp:paragraph -->';
		$atlas_test_filters['atlas_markdown_max_bytes'] = array(
			array(
				'callback'      => static function () {
					return 1024;
				},
				'priority'      => 10,
				'accepted_args' => 1,
			),
		);
		$capped = Atlas\Markdown_Endpoint::markdown_for_post( 7 );
		unset( $atlas_test_filters['atlas_markdown_max_bytes'] );

		$results[] = array(
			'name'   => 'Markdown: output is capped to the byte limit on a valid UTF-8 boundary',
			'passed' => strlen( $capped ) <= 1025 && mb_check_encoding( $capped, 'UTF-8' ),
		);

		$atlas_test_posts     = array();
		$atlas_test_transients = array();

		return $results;
	}
}
