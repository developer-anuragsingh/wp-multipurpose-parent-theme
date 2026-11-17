<?php
/**
 * Tests for Atlas\Schema's BlogPosting and BreadcrumbList nodes and its
 * generation-based cache.
 *
 * Must run BEFORE the "SEO & Schema" suite, whose last tests define an
 * SEO-plugin constant that disables Schema for the rest of the process.
 *
 * @package AtlasTheme
 */

class Test_Schema_Nodes {

	/**
	 * Print the JSON-LD for the current request and decode it.
	 *
	 * @return array{raw:string, graph:array<int,array>} Raw output and graph nodes.
	 */
	private static function render(): array {
		ob_start();
		Atlas\Schema::print_json_ld();
		$raw = (string) ob_get_clean();
		preg_match( '#<script type="application/ld\+json">(.*)</script>#s', $raw, $m );
		$data = isset( $m[1] ) ? json_decode( $m[1], true ) : null;

		return array(
			'raw'   => $raw,
			'graph' => is_array( $data ) ? ( $data['@graph'] ?? array() ) : array(),
		);
	}

	/**
	 * Find the first node of a type.
	 *
	 * @param array  $graph Graph nodes.
	 * @param string $type  @type to find.
	 * @return array|null The node, or null.
	 */
	private static function node( array $graph, string $type ): ?array {
		foreach ( $graph as $node ) {
			if ( ( $node['@type'] ?? '' ) === $type ) {
				return $node;
			}
		}
		return null;
	}

	/**
	 * Configure a single-post request.
	 *
	 * @param array $overrides Post property and request overrides.
	 * @return void
	 */
	private static function single_post( array $overrides = array() ): void {
		global $atlas_test_request;
		$atlas_test_request = array_merge(
			array(
				'is_singular' => 'post',
				'post_id'     => 42,
				'post'        => new WP_Post(
					array(
						'ID'         => 42,
						'post_title' => 'Hello World',
						'post_name'  => 'hello',
					)
				),
				'categories'  => array( (object) array( 'term_id' => 7, 'name' => 'News' ) ),
				'author'      => 'Jane Doe',
			),
			$overrides
		);
	}

	private static function reset(): void {
		global $atlas_test_transients, $atlas_test_options, $atlas_test_filters, $atlas_test_request, $atlas_test_bloginfo;
		$atlas_test_transients = array();
		$atlas_test_options    = array();
		$atlas_test_request    = array();
		unset( $atlas_test_filters['atlas_schema_author_sameas'] );
		$atlas_test_bloginfo['name'] = 'Atlas Test Site';
	}

	public static function run(): array {
		global $atlas_test_transients, $atlas_test_actions, $atlas_test_filters, $atlas_test_request, $atlas_test_bloginfo, $atlas_test_options;
		$results = array();

		// --- BlogPosting. ---
		self::reset();
		self::single_post();
		$r       = self::render();
		$article = self::node( $r['graph'], 'BlogPosting' );

		$results[] = array(
			'name'   => 'Schema article: a single post emits a BlogPosting with headline, permalink, dates, author and publisher',
			'passed' => null !== $article
				&& 'Hello World' === $article['headline']
				&& 'https://example.com/hello/' === $article['mainEntityOfPage']['@id']
				&& 1 === preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', (string) $article['datePublished'] )
				&& 1 === preg_match( '/^\d{4}-\d{2}-\d{2}T/', (string) $article['dateModified'] )
				&& 'Person' === $article['author']['@type'] && 'Jane Doe' === $article['author']['name']
				&& 'Organization' === $article['publisher']['@type'] && 'Atlas Test Site' === $article['publisher']['name'],
		);

		$results[] = array(
			'name'   => 'Schema article: no image property without a featured image, and no empty sameAs on the author',
			'passed' => null !== $article && ! isset( $article['image'] ) && ! isset( $article['author']['sameAs'] ),
		);

		self::reset();
		self::single_post( array( 'thumbnail' => 'https://example.com/img.jpg' ) );
		$article = self::node( self::render()['graph'], 'BlogPosting' );

		$results[] = array(
			'name'   => 'Schema article: the featured image is included when the post has one',
			'passed' => null !== $article && 'https://example.com/img.jpg' === ( $article['image'] ?? '' ),
		);

		self::reset();
		self::single_post(
			array(
				'post' => new WP_Post(
					array(
						'ID'           => 42,
						'post_title'   => 'Article With Content',
						'post_name'    => 'article-content',
						'post_content' => 'One two three four five six seven eight nine ten.',
					)
				),
			)
		);
		$article = self::node( self::render()['graph'], 'BlogPosting' );

		$results[] = array(
			'name'   => 'Schema article: includes wordCount and SpeakableSpecification',
			'passed' => null !== $article && isset( $article['wordCount'] ) && 10 === $article['wordCount']
				&& isset( $article['speakable']['@type'] ) && 'SpeakableSpecification' === $article['speakable']['@type']
				&& array( 'article', 'h1', '.entry-summary' ) === $article['speakable']['cssSelector'],
		);

		self::reset();
		self::single_post( array( 'post' => new WP_Post( array( 'ID' => 42, 'post_title' => '   ', 'post_name' => 'x' ) ) ) );
		$r = self::render();

		$results[] = array(
			'name'   => 'Schema article: a post with an empty title emits no BlogPosting (fails closed)',
			'passed' => null === self::node( $r['graph'], 'BlogPosting' ),
		);

		self::reset();
		self::single_post();
		$atlas_test_filters['atlas_schema_author_sameas'] = array(
			array(
				'callback'      => static function () {
					return array( 'https://example.com/jane', 'https://social.example/jane' );
				},
				'priority'      => 10,
				'accepted_args' => 1,
			),
		);
		$article = self::node( self::render()['graph'], 'BlogPosting' );

		$results[] = array(
			'name'   => 'Schema article: author sameAs appears only when the filter supplies profiles',
			'passed' => null !== $article && array( 'https://example.com/jane', 'https://social.example/jane' ) === ( $article['author']['sameAs'] ?? null ),
		);

		// --- Hostile titles. ---
		self::reset();
		$evil = 'A </script><!--<script>alert(1)</script> & "quoted"';
		self::single_post( array( 'post' => new WP_Post( array( 'ID' => 42, 'post_title' => $evil, 'post_name' => 'evil' ) ) ) );
		$r       = self::render();
		$article = self::node( $r['graph'], 'BlogPosting' );

		$results[] = array(
			'name'   => 'Schema article: a hostile post title cannot close or confuse the script, and round-trips intact',
			'passed' => 1 === substr_count( $r['raw'], '</script>' )
				&& 1 === substr_count( $r['raw'], '<script' )
				&& ! str_contains( $r['raw'], '<!--' )
				&& null !== $article && $evil === $article['headline'],
		);

		// --- Product Schema ---
		// 1. Without WooCommerce active, no Product node is emitted (fails closed).
		self::reset();
		$atlas_test_request = array(
			'is_singular' => 'product',
			'post_id'     => 99,
			'post'        => new WP_Post(
				array(
					'ID'         => 99,
					'post_title' => 'Premium WordPress Theme',
					'post_name'  => 'premium-theme',
				)
			),
		);
		$r_inactive = self::render();

		$results[] = array(
			'name'   => 'Schema product: without WooCommerce active, no Product node is emitted',
			'passed' => null === self::node( $r_inactive['graph'], 'Product' ),
		);

		// 2. With WooCommerce active, a single product emits a Product node with title, url, description, and image.
		self::reset();
		$atlas_test_filters['atlas_schema_woocommerce_active'] = array(
			array(
				'callback'      => '__return_true',
				'priority'      => 10,
				'accepted_args' => 1,
			),
		);
		$atlas_test_request = array(
			'is_singular' => 'product',
			'post_id'     => 99,
			'post'        => new WP_Post(
				array(
					'ID'           => 99,
					'post_title'   => 'Premium WordPress Theme',
					'post_name'    => 'premium-theme',
					'post_excerpt' => 'A fast, accessible multipurpose parent theme.',
				)
			),
			'thumbnail'   => 'https://example.com/product.jpg',
		);
		$r       = self::render();
		$product = self::node( $r['graph'], 'Product' );

		$results[] = array(
			'name'   => 'Schema product: a single product emits a Product node with title, url, description, and image',
			'passed' => null !== $product
				&& 'Premium WordPress Theme' === $product['name']
				&& 'https://example.com/premium-theme/' === $product['url']
				&& 'A fast, accessible multipurpose parent theme.' === $product['description']
				&& 'https://example.com/product.jpg' === ( $product['image'] ?? '' ),
		);

		// 3. Product with empty title fails closed.
		self::reset();
		$atlas_test_filters['atlas_schema_woocommerce_active'] = array(
			array(
				'callback'      => '__return_true',
				'priority'      => 10,
				'accepted_args' => 1,
			),
		);
		$atlas_test_request = array(
			'is_singular' => 'product',
			'post_id'     => 100,
			'post'        => new WP_Post( array( 'ID' => 100, 'post_title' => '' ) ),
		);
		$r_empty = self::render();

		$results[] = array(
			'name'   => 'Schema product: a product with an empty title emits no Product node (fails closed)',
			'passed' => null === self::node( $r_empty['graph'], 'Product' ),
		);

		// --- BreadcrumbList. ---
		self::reset();
		self::single_post();
		$crumb = self::node( self::render()['graph'], 'BreadcrumbList' );
		$items = $crumb['itemListElement'] ?? array();

		$results[] = array(
			'name'   => 'Schema breadcrumb: a categorised post gets Home > Category > Post with positions 1..3',
			'passed' => 3 === count( $items )
				&& array( 1, 2, 3 ) === array_column( $items, 'position' )
				&& array( 'Home', 'News', 'Hello World' ) === array_column( $items, 'name' )
				&& 'https://example.com/category/7' === $items[1]['item']
				&& 'https://example.com/hello/' === $items[2]['item'],
		);

		self::reset();
		self::single_post( array( 'categories' => array() ) );
		$items = self::node( self::render()['graph'], 'BreadcrumbList' )['itemListElement'] ?? array();

		$results[] = array(
			'name'   => 'Schema breadcrumb: an uncategorised post gets Home > Post',
			'passed' => 2 === count( $items ) && array( 'Home', 'Hello World' ) === array_column( $items, 'name' ),
		);

		self::reset();
		$atlas_test_request = array(
			'is_category' => true,
			'queried'     => new WP_Term( array( 'term_id' => 9, 'name' => 'Guides' ) ),
		);
		$r     = self::render();
		$items = self::node( $r['graph'], 'BreadcrumbList' )['itemListElement'] ?? array();

		$results[] = array(
			'name'   => 'Schema breadcrumb: a category archive gets Home > Term and nothing else in the graph',
			'passed' => array( 'Home', 'Guides' ) === array_column( $items, 'name' )
				&& 1 === count( $r['graph'] ),
		);

		self::reset();
		$atlas_test_request = array(
			'is_post_type_archive' => true,
			'queried'              => new WP_Post_Type( 'event', 'Events' ),
		);
		$items = self::node( self::render()['graph'], 'BreadcrumbList' )['itemListElement'] ?? array();

		$results[] = array(
			'name'   => 'Schema breadcrumb: a post-type archive gets Home > Archive label with its archive URL',
			'passed' => array( 'Home', 'Events' ) === array_column( $items, 'name' ) && 'https://example.com/event/' === ( $items[1]['item'] ?? '' ),
		);

		self::reset();
		$atlas_test_request = array(
			'is_front_page' => true,
		);
		$r = self::render();

		$results[] = array(
			'name'   => 'Schema breadcrumb: the front page has no breadcrumb (a one-item trail is not published)',
			'passed' => null === self::node( $r['graph'], 'BreadcrumbList' ) && null !== self::node( $r['graph'], 'Organization' ),
		);

		// --- Cache keys and invalidation. ---
		self::reset();
		self::single_post();
		self::render();

		$results[] = array(
			'name'   => 'Schema cache: a rendered post graph is stored under the post key of the current generation',
			'passed' => isset( $atlas_test_transients[ Atlas\Schema::cache_key( 'post_42' ) ] )
				&& str_starts_with( Atlas\Schema::cache_key( 'post_42' ), 'atlas_schema_0_' ),
		);

		Atlas\Schema::invalidate_cache( 42 );

		$results[] = array(
			'name'   => 'Schema cache: saving a post clears that post\'s graph',
			'passed' => ! isset( $atlas_test_transients[ Atlas\Schema::cache_key( 'post_42' ) ] ),
		);

		// --- Site-wide changes must refresh EVERY cached graph. ---
		self::reset();
		self::single_post();
		self::render();
		self::single_post( array( 'author' => 'Renamed Author' ) );
		$stale = self::node( self::render()['graph'], 'BlogPosting' );

		$results[] = array(
			'name'   => 'Schema cache: without a flush, a cached graph is served (author rename not yet visible)',
			'passed' => 'Jane Doe' === ( $stale['author']['name'] ?? '' ),
		);

		Atlas\Schema::flush_cache();
		$fresh = self::node( self::render()['graph'], 'BlogPosting' );

		$results[] = array(
			'name'   => 'Schema cache: flush_cache refreshes every post graph, not only the front page (author rename visible)',
			'passed' => 'Renamed Author' === ( $fresh['author']['name'] ?? '' )
				&& 1 === (int) $atlas_test_options['atlas_schema_cache_gen']
				&& str_starts_with( Atlas\Schema::cache_key( 'post_42' ), 'atlas_schema_1_' ),
		);

		self::reset();
		Atlas\Schema::flush_cache();
		Atlas\Schema::flush_cache();
		Atlas\Schema::flush_cache();

		$results[] = array(
			'name'   => 'Schema cache: each flush advances the generation by one',
			'passed' => 3 === (int) $atlas_test_options['atlas_schema_cache_gen'],
		);

		// --- init() wiring: the site-wide changes that must flush. ---
		$saved_actions      = $atlas_test_actions;
		$atlas_test_actions = array();
		Atlas\Schema::init();
		$wired = $atlas_test_actions;

		self::reset();
		foreach ( array( 'switch_theme', 'update_option_blogname', 'update_option_home', 'update_option_siteurl', 'profile_update', 'edited_term', 'delete_term' ) as $hook ) {
			$before = (int) ( $atlas_test_options['atlas_schema_cache_gen'] ?? 0 );
			do_action( $hook );
			$flushed[ $hook ] = (int) ( $atlas_test_options['atlas_schema_cache_gen'] ?? 0 ) === $before + 1;
		}
		$atlas_test_actions = $saved_actions;

		$results[] = array(
			'name'   => 'Schema cache: switching theme, renaming the site, changing the URL, editing a profile or a term each flush all graphs'
				. ( in_array( false, $flushed, true ) ? ' (not flushing: ' . implode( ', ', array_keys( array_filter( $flushed, static fn( $ok ) => ! $ok ) ) ) . ')' : '' ),
			'passed' => ! in_array( false, $flushed, true ) && count( $flushed ) === 7,
		);

		$results[] = array(
			'name'   => 'Schema cache: init() also flushes when the active theme\'s mods (logo) change and still saves/deletes per post',
			'passed' => isset( $wired['save_post'], $wired['deleted_post'], $wired['wp_head'] )
				&& (bool) array_filter( array_keys( $wired ), static fn( $h ) => str_starts_with( $h, 'update_option_theme_mods_' ) ),
		);

		self::reset();
		$atlas_test_bloginfo['name'] = 'Atlas Test Site';

		return $results;
	}
}
