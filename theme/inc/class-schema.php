<?php
/**
 * JSON-LD structured-data concern.
 *
 * Owns the Atlas theme's server-side JSON-LD output: a single
 * <script type="application/ld+json"> @graph printed on wp_head (priority 1),
 * built with wp_json_encode. Emits Organization + WebSite on the front page,
 * BlogPosting on single posts (Person author, publisher, dates,
 * mainEntityOfPage, image), and BreadcrumbList wherever a sensible navigation
 * trail exists. Fails closed — when the required data for a context is missing
 * the class prints NOTHING rather than a malformed or empty tag — and backs off
 * entirely when a known SEO/schema plugin (Yoast, Rank Math, SEOPress) is
 * active so the two do not emit duplicate graphs.
 *
 * FAQPage and HowTo are intentionally NOT emitted: Google retired FAQ rich
 * results (May 7, 2026) and no longer supports HowTo, so neither earns a search
 * feature. They remain valid schema.org markup and MAY be added later strictly
 * as optional Answer-Engine-Optimization support tied to an actual FAQ pattern,
 * never as a promised rich result. Product/Review/AggregateRating schema is a
 * future WooCommerce concern and is deliberately out of scope here.
 *
 * Mirrors Atlas\UI_States and Atlas\Bindings precisely — a static registrar
 * wired from functions.php. Resolved via the hand-rolled autoloader
 * (Atlas\Schema -> inc/class-schema.php).
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for the JSON-LD structured-data feature.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object. All public surface is reachable from the
 * WordPress hook wired in init().
 */
class Schema {

	/**
	 * Master gate filter: emit the JSON-LD graph at all.
	 *
	 * Defaults true. A site may hard-disable Atlas schema by returning false,
	 * independent of the SEO-plugin back-off, so the semantic name lives in a
	 * constant rather than a repeated literal.
	 *
	 * @var string
	 */
	const FILTER_ENABLED = 'atlas_schema_enabled';

	/**
	 * Filter: override or augment the Organization node.
	 *
	 * Receives the computed Organization array so a site can supply sameAs
	 * profiles, a different logo, or a fully replaced node.
	 *
	 * @var string
	 */
	const FILTER_ORGANIZATION = 'atlas_schema_organization';

	/**
	 * Filter: supply the post author's sameAs profile URLs.
	 *
	 * Defaults to an empty array (the sameAs key is omitted when empty) because
	 * the theme cannot know an author's canonical profile URLs.
	 *
	 * @var string
	 */
	const FILTER_AUTHOR_SAMEAS = 'atlas_schema_author_sameas';

	/**
	 * Filter: the assembled @graph envelope, applied last before encoding.
	 *
	 * The final extension point so a site can add or remove nodes wholesale
	 * after the per-context assembly has run.
	 *
	 * @var string
	 */
	const FILTER_GRAPH = 'atlas_schema_graph';

	/**
	 * The schema.org @type for the brand entity node.
	 *
	 * @var string
	 */
	const TYPE_ORGANIZATION = 'Organization';

	/**
	 * The schema.org @type for the site node.
	 *
	 * @var string
	 */
	const TYPE_WEBSITE = 'WebSite';

	/**
	 * The schema.org @type for the single-post article node.
	 *
	 * @var string
	 */
	const TYPE_ARTICLE = 'BlogPosting';

	/**
	 * The schema.org @type for the breadcrumb trail node.
	 *
	 * @var string
	 */
	const TYPE_BREADCRUMB = 'BreadcrumbList';

	/**
	 * The schema.org @type for a person (article author).
	 *
	 * @var string
	 */
	const TYPE_PERSON = 'Person';

	/**
	 * The schema.org @type for a single breadcrumb entry.
	 *
	 * @var string
	 */
	const TYPE_LISTITEM = 'ListItem';

	/**
	 * Transient key prefix for cached JSON-LD graph nodes.
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'atlas_schema_';

	/**
	 * Cache TTL for schema transients in seconds (12 hours).
	 *
	 * @var int
	 */
	const TRANSIENT_TTL = 43200;

	/**
	 * Register the JSON-LD WordPress hooks.
	 *
	 * Called once from functions.php. Hooks wp_head at priority 1 so the graph
	 * prints early in <head>, before most plugin output. Keeps all schema hook
	 * wiring in one place so the boot order is explicit.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_head', array( self::class, 'print_json_ld' ), 1 );
		add_action( 'save_post', array( self::class, 'invalidate_cache' ), 10, 1 );
		add_action( 'deleted_post', array( self::class, 'invalidate_cache' ), 10, 1 );
		add_action( 'switch_theme', array( self::class, 'flush_cache' ) );
	}

	/**
	 * Build and print the JSON-LD @graph for the current request.
	 *
	 * Fails closed in three places so the page never carries a broken tag:
	 * returns before printing when the master gate filter is off or a known SEO
	 * plugin is active, when the per-context assembly yields no nodes, or when
	 * encoding fails. The already-JSON-encoded string is printed verbatim inside
	 * the script element (JSON values are safe for that context); only the
	 * "</" sequence is neutralised so the inline script cannot be closed early.
	 *
	 * @return void
	 */
	public static function print_json_ld(): void {
		if ( ! apply_filters( self::FILTER_ENABLED, true ) || self::seo_plugin_active() ) {
			return;
		}

		$nodes = self::build_nodes();

		if ( empty( $nodes ) ) {
			return;
		}

		$graph = array(
			'@context' => 'https://schema.org',
			'@graph'   => $nodes,
		);

		/**
		 * Filter the assembled JSON-LD graph before it is encoded and printed.
		 *
		 * @param array $graph The { '@context', '@graph' } envelope.
		 */
		$graph = apply_filters( self::FILTER_GRAPH, $graph );

		if ( empty( $graph['@graph'] ) ) {
			return;
		}

		$json = wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $json ) {
			return;
		}

		// Neutralise any "</" so a string value can never close the inline
		// <script> element early. The JSON is already encoded, so no further
		// HTML escaping is applied (esc_html would corrupt the JSON).
		$json = str_replace( '</', '<\/', $json );

		echo '<script type="application/ld+json">' . $json . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON is machine-generated via wp_json_encode and "</" neutralised; esc_* would corrupt it.
	}

	/**
	 * Assemble the non-empty @graph nodes for the current context.
	 *
	 * Branches by request context: the front page/home carries Organization +
	 * WebSite, a single post carries BlogPosting with the Organization reused as
	 * its publisher, and any context with a sensible trail carries a
	 * BreadcrumbList. Each builder fails closed to an empty array, and only
	 * non-empty nodes are collected, so an incomplete context simply contributes
	 * fewer nodes rather than a malformed one.
	 *
	 * @return array<int, array> The list of JSON-LD nodes (possibly empty).
	 */
	private static function build_nodes(): array {
		$cache_key = self::get_cache_key();

		if ( '' !== $cache_key ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$nodes = array();

		if ( is_front_page() || is_home() ) {
			$organization = self::build_organization();
			$website      = self::build_website();

			if ( ! empty( $organization ) ) {
				$nodes[] = $organization;
			}
			if ( ! empty( $website ) ) {
				$nodes[] = $website;
			}
		}

		if ( is_singular( 'post' ) ) {
			$article = self::build_article();

			if ( ! empty( $article ) ) {
				$nodes[] = $article;
			}
		}

		$breadcrumb = self::build_breadcrumb();

		if ( ! empty( $breadcrumb ) ) {
			$nodes[] = $breadcrumb;
		}

		if ( '' !== $cache_key && ! empty( $nodes ) ) {
			set_transient( $cache_key, $nodes, self::TRANSIENT_TTL );
		}

		return $nodes;
	}

	/**
	 * Compute the transient cache key for the current request context.
	 *
	 * @return string Transient key, or empty string if context should not be cached.
	 */
	private static function get_cache_key(): string {
		if ( is_front_page() || is_home() ) {
			return self::TRANSIENT_PREFIX . 'front';
		}

		if ( is_singular( 'post' ) ) {
			$post_id = get_the_ID();
			if ( $post_id ) {
				return self::TRANSIENT_PREFIX . 'post_' . (int) $post_id;
			}
		}

		return '';
	}

	/**
	 * Invalidate schema transients when content changes.
	 *
	 * Hooked to save_post and deleted_post. Clears the specific post cache and
	 * the front-page schema cache so updates take effect immediately.
	 *
	 * @param int $post_id Post ID being saved or deleted.
	 * @return void
	 */
	public static function invalidate_cache( $post_id = 0 ): void {
		$clean_id = (int) $post_id;
		if ( $clean_id > 0 ) {
			delete_transient( self::TRANSIENT_PREFIX . 'post_' . $clean_id );
		}
		delete_transient( self::TRANSIENT_PREFIX . 'front' );
	}

	/**
	 * Flush schema transients on theme switch.
	 *
	 * @return void
	 */
	public static function flush_cache(): void {
		delete_transient( self::TRANSIENT_PREFIX . 'front' );
	}

	/**
	 * Report whether a known SEO/schema plugin owns structured data.
	 *
	 * Checks the version constants Yoast SEO, Rank Math, and SEOPress define on
	 * load. When any is present the theme backs off entirely so the page does
	 * not carry two competing graphs. Kept a tiny defined()-only guard per KISS.
	 *
	 * @return bool True when a known SEO plugin is active.
	 */
	private static function seo_plugin_active(): bool {
		return defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'SEOPRESS_VERSION' );
	}

	/**
	 * Build the Organization brand-entity node.
	 *
	 * Carries the site name and home URL always, the custom-logo image URL when
	 * one is set (omitted otherwise so no empty logo is published), and the
	 * sameAs profiles only when the organization filter supplies them. The whole
	 * node is passed through the organization filter so a site can replace or
	 * augment it. Returns an array that always has at least @type/name/url.
	 *
	 * @return array The Organization node.
	 */
	private static function build_organization(): array {
		$org = array(
			'@type' => self::TYPE_ORGANIZATION,
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);

		$logo_id = (int) get_theme_mod( 'custom_logo' );

		if ( $logo_id > 0 ) {
			$logo_url = wp_get_attachment_image_url( $logo_id, 'full' );

			if ( is_string( $logo_url ) && '' !== $logo_url ) {
				$org['logo'] = $logo_url;
			}
		}

		/**
		 * Filter the Organization node (e.g. to add sameAs profile URLs).
		 *
		 * @param array $org The computed Organization node.
		 */
		$org = apply_filters( self::FILTER_ORGANIZATION, $org );

		return self::omit_empty( $org );
	}

	/**
	 * Build the WebSite node.
	 *
	 * Carries the site name, home URL, and language. No SearchAction is emitted
	 * — the sitelinks search box is deprecated, so publishing one is noise.
	 *
	 * @return array The WebSite node.
	 */
	private static function build_website(): array {
		return array(
			'@type'      => self::TYPE_WEBSITE,
			'name'       => get_bloginfo( 'name' ),
			'url'        => home_url( '/' ),
			'inLanguage' => get_bloginfo( 'language' ),
		);
	}

	/**
	 * Build the BlogPosting article node for a single post.
	 *
	 * Fails closed: returns an empty array unless the request is a single post
	 * with a resolvable post object and a non-empty title, so a malformed
	 * article node is never emitted. Carries headline, mainEntityOfPage,
	 * published/modified dates, a Person author (with sameAs only when the
	 * author filter supplies profiles), the Organization as publisher, and the
	 * featured image only when the post has one.
	 *
	 * @return array The BlogPosting node, or empty when the context is unfit.
	 */
	private static function build_article(): array {
		if ( ! is_singular( 'post' ) ) {
			return array();
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return array();
		}

		$headline = get_the_title( $post );

		if ( '' === trim( (string) $headline ) ) {
			return array();
		}

		$author = array(
			'@type' => self::TYPE_PERSON,
			'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
		);

		$author_sameas = apply_filters( self::FILTER_AUTHOR_SAMEAS, array() );

		if ( is_array( $author_sameas ) && ! empty( $author_sameas ) ) {
			$author['sameAs'] = array_values( $author_sameas );
		}

		$article = array(
			'@type'            => self::TYPE_ARTICLE,
			'headline'         => $headline,
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => get_permalink( $post ),
			),
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
			'author'           => $author,
			'publisher'        => self::build_organization(),
		);

		$image = get_the_post_thumbnail_url( $post, 'full' );

		if ( is_string( $image ) && '' !== $image ) {
			$article['image'] = $image;
		}

		return $article;
	}

	/**
	 * Build the BreadcrumbList node for the current request.
	 *
	 * Starts from Home and appends the sensible intermediate and current nodes
	 * for the context (a post's primary category then the post; an archive's
	 * term/post-type then nothing further). Fails closed: returns an empty array
	 * when the only node would be Home, because a one-item breadcrumb is not a
	 * trail worth publishing. Each entry is a ListItem with position, name, and
	 * item URL.
	 *
	 * @return array The BreadcrumbList node, or empty when there is no trail.
	 */
	private static function build_breadcrumb(): array {
		$trail = array(
			array(
				'name' => __( 'Home', 'atlas-theme' ),
				'url'  => home_url( '/' ),
			),
		);

		if ( is_singular( 'post' ) ) {
			$post = get_post();

			if ( $post instanceof \WP_Post ) {
				$categories = get_the_category( $post->ID );

				if ( ! empty( $categories ) ) {
					$category = $categories[0];
					$cat_link = get_category_link( $category->term_id );

					if ( is_string( $cat_link ) && '' !== $cat_link ) {
						$trail[] = array(
							'name' => $category->name,
							'url'  => $cat_link,
						);
					}
				}

				$title = get_the_title( $post );

				if ( '' !== trim( (string) $title ) ) {
					$trail[] = array(
						'name' => $title,
						'url'  => get_permalink( $post ),
					);
				}
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();

			if ( $term instanceof \WP_Term ) {
				$trail[] = array(
					'name' => $term->name,
					'url'  => get_term_link( $term ),
				);
			}
		} elseif ( is_post_type_archive() ) {
			$object = get_queried_object();

			if ( $object instanceof \WP_Post_Type ) {
				$trail[] = array(
					'name' => $object->labels->name,
					'url'  => get_post_type_archive_link( $object->name ),
				);
			}
		}

		// A single Home node is not a trail worth publishing — fail closed.
		if ( count( $trail ) < 2 ) {
			return array();
		}

		$items    = array();
		$position = 1;

		foreach ( $trail as $node ) {
			if ( ! is_string( $node['url'] ) || '' === $node['url'] ) {
				continue;
			}

			$items[] = array(
				'@type'    => self::TYPE_LISTITEM,
				'position' => $position,
				'name'     => $node['name'],
				'item'     => $node['url'],
			);

			++$position;
		}

		if ( count( $items ) < 2 ) {
			return array();
		}

		return array(
			'@type'           => self::TYPE_BREADCRUMB,
			'itemListElement' => $items,
		);
	}

	/**
	 * Remove keys whose values are empty so no empty property is published.
	 *
	 * Operates one level deep: a JSON-LD node is a flat-ish associative array,
	 * and an empty scalar or array value for a property is noise that weakens
	 * entity resolution. Zero and '0' are preserved because they are meaningful
	 * values, not absence.
	 *
	 * @param array $node The node to prune.
	 * @return array The node with empty-valued keys removed.
	 */
	private static function omit_empty( array $node ): array {
		foreach ( $node as $key => $value ) {
			if ( '' === $value || null === $value || array() === $value ) {
				unset( $node[ $key ] );
			}
		}

		return $node;
	}
}
