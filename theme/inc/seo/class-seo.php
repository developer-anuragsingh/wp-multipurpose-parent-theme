<?php
/**
 * Traditional on-page SEO meta concern.
 *
 * Owns the Atlas theme's classic on-page SEO <head> output: Open Graph +
 * Twitter Card tags, a self-referential canonical, conservative hreflang
 * alternates, and a noindex robots directive on uncrawlable contexts. All of it
 * prints on wp_head at priority 1 and every value is escaped at the point of
 * output (esc_attr for text, esc_url for URLs).
 *
 * It deliberately does NOT fight WordPress core's title-tag: Atlas\Setup already
 * calls add_theme_support( 'title-tag' ), so core owns <title> and this class
 * never outputs one — og:title merely mirrors wp_get_document_title() so the
 * social preview matches the page title.
 *
 * Two gates govern output. A master filter (atlas_seo_enabled, default true)
 * turns the whole concern off. When a known SEO plugin (Yoast, Rank Math,
 * SEOPress) is active the class backs off per-output: it still prints the
 * noindex decision (plugins may not cover maintenance mode) but suppresses
 * OG/Twitter/canonical/hreflang so the page never carries duplicate tags.
 *
 * NOTE: the default og:image path (ATLAS_THEME_URI . '/assets/images/og-default.png')
 * is a placeholder. The binary is intentionally NOT shipped this phase — only
 * fonts live under theme/assets/. Printing the URL is harmless if the file 404s,
 * and a site can point FILTER_OG_IMAGE at a real asset. A featured image, when
 * present, always wins over this fallback.
 *
 * Mirrors Atlas\Schema and Atlas\Robots precisely — a static registrar wired
 * from functions.php after Atlas\Contact_Form::init(). Resolved via the
 * hand-rolled autoloader (Atlas\Seo -> inc/class-seo.php).
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for the traditional on-page SEO meta feature.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object. All public surface is reachable from the
 * WordPress hook wired in init().
 */
class Seo {

	/**
	 * Master gate filter: emit the Atlas SEO meta at all.
	 *
	 * Defaults true. A site may hard-disable the whole concern by returning
	 * false, independent of the SEO-plugin back-off, so the semantic name lives
	 * in a constant rather than a repeated literal.
	 *
	 * @var string
	 */
	const FILTER_ENABLED = 'atlas_seo_enabled';

	/**
	 * Filter: override the default og:image URL.
	 *
	 * Receives the placeholder theme default so a site can point it at a real
	 * shipped asset. Only consulted when the current view has no featured image.
	 *
	 * @var string
	 */
	const FILTER_OG_IMAGE = 'atlas_seo_default_og_image';

	/**
	 * Filter: override the og:image dimensions array.
	 *
	 * Receives an associative array with 'width' and 'height' keys (integers)
	 * resolved from the featured image attachment metadata. A site may replace
	 * or augment the values before they are printed as og:image:width /
	 * og:image:height. Return an empty array to suppress the dimension tags.
	 *
	 * @var string
	 */
	const FILTER_OG_IMAGE_DIMS = 'atlas_seo_og_image_dims';

	/**
	 * Filter: supply the hreflang alternates map (locale => URL).
	 *
	 * Defaults to an empty array so NO hreflang links are emitted unless a site
	 * explicitly supplies the en/hi alternates — conservative because a wrong
	 * hreflang is worse than none.
	 *
	 * @var string
	 */
	const FILTER_HREFLANG = 'atlas_seo_hreflang_locales';

	/**
	 * Filter: force maintenance mode for the noindex decision.
	 *
	 * Defaults false. Combined with the MAINTENANCE_CONSTANT so an operator can
	 * flag maintenance either by defining the constant or by returning true.
	 *
	 * @var string
	 */
	const FILTER_MAINTENANCE = 'atlas_seo_maintenance';

	/**
	 * Filter: override or augment the assembled Open Graph / Twitter tag map.
	 *
	 * Receives the computed tag map (property/name => content) so a site can
	 * replace values or add tags before they are printed.
	 *
	 * @var string
	 */
	const FILTER_OG_TAGS = 'atlas_seo_og_tags';

	/**
	 * Name of the PHP constant that signals maintenance mode.
	 *
	 * A site may `define( 'ATLAS_MAINTENANCE', true )` to mark the whole install
	 * as under maintenance so the SEO layer emits noindex.
	 *
	 * @var string
	 */
	const MAINTENANCE_CONSTANT = 'ATLAS_MAINTENANCE';

	/**
	 * Maximum length (in characters) of the og:description value.
	 *
	 * Open Graph descriptions are truncated by consumers around this length, so
	 * capping here keeps the shared preview tidy rather than shipping a wall of
	 * text.
	 *
	 * @var int
	 */
	const DESCRIPTION_MAX = 200;

	/**
	 * Register the SEO meta WordPress hooks.
	 *
	 * Called once from functions.php. Hooks wp_head at priority 1 so the meta
	 * prints early in <head>, alongside Atlas\Schema. Keeps all SEO hook wiring
	 * in one place so the boot order is explicit.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_head', array( self::class, 'print_meta' ), 1 );
		add_action( 'wp_head', array( self::class, 'print_pagination_links' ), 1 );
	}

	/**
	 * Build and print the on-page SEO meta for the current request.
	 *
	 * Returns immediately when the master gate filter is off. The robots-meta
	 * (noindex) decision is always printed when applicable because a plugin may
	 * not cover maintenance mode; the OG/Twitter/canonical/hreflang tags print
	 * only when no known SEO plugin is active, so the page never carries
	 * duplicate tags.
	 *
	 * @return void
	 */
	public static function print_meta(): void {
		if ( ! apply_filters( self::FILTER_ENABLED, true ) ) {
			return;
		}

		// Always emit the noindex decision — plugins may not cover maintenance.
		if ( is_search() || self::is_maintenance() ) {
			echo '<meta name="robots" content="noindex,follow">' . "\n";
		}

		// Back off from OG/canonical/hreflang when a plugin owns them.
		if ( self::seo_plugin_active() ) {
			return;
		}

		self::print_meta_description();
		self::print_open_graph();
		self::print_canonical();
		self::print_hreflang();
	}

	/**
	 * Report whether a known SEO plugin owns on-page meta.
	 *
	 * Checks the version constants Yoast SEO, Rank Math, and SEOPress define on
	 * load — the same detection Atlas\Schema uses so both concerns back off in
	 * lockstep. Kept a tiny defined()-only guard per KISS.
	 *
	 * @return bool True when a known SEO plugin is active.
	 */
	private static function seo_plugin_active(): bool {
		return defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'SEOPRESS_VERSION' );
	}

	/**
	 * Report whether the site is in maintenance mode for indexing purposes.
	 *
	 * True when the MAINTENANCE_CONSTANT is defined and truthy, or when the
	 * maintenance filter returns true — either signal lets an operator flag the
	 * install as under maintenance so the SEO layer emits noindex.
	 *
	 * @return bool True when maintenance mode is active.
	 */
	private static function is_maintenance(): bool {
		$by_constant = defined( self::MAINTENANCE_CONSTANT ) && constant( self::MAINTENANCE_CONSTANT );

		return (bool) $by_constant || (bool) apply_filters( self::FILTER_MAINTENANCE, false );
	}

	/**
	 * Print the standard <meta name="description"> tag.
	 *
	 * Search engines use it for the result snippet and Lighthouse flags its
	 * absence. It reuses the same capped, tag-stripped text as og:description, and
	 * prints nothing when no description exists (fail closed, no empty tag).
	 *
	 * @return void
	 */
	private static function print_meta_description(): void {
		$description = self::description();

		if ( '' === $description ) {
			return;
		}

		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}

	/**
	 * Build and print the Open Graph and Twitter Card tags.
	 *
	 * Assembles the tag map — og:title mirrors wp_get_document_title() (never a
	 * separate <title>), og:description is the post excerpt on singular views
	 * and the site tagline elsewhere (stripped, trimmed, capped), og:url is the
	 * canonical current URL, og:type is 'article' for single posts and 'website'
	 * otherwise, og:image is the featured image when present and the filterable
	 * placeholder default otherwise, and twitter:card is summary_large_image.
	 * The map is passed through FILTER_OG_TAGS, then each entry is printed with
	 * og:* as a property attribute and the rest as a name attribute. Values are
	 * escaped with esc_url for URL-bearing keys and esc_attr otherwise. Empty
	 * values are skipped so no empty tag is published (fail closed).
	 *
	 * @return void
	 */
	private static function print_open_graph(): void {
		$image      = get_the_post_thumbnail_url( null, 'full' );
		$thumb_id   = function_exists( 'get_post_thumbnail_id' ) ? get_post_thumbnail_id() : 0;
		$image_dims = array();

		if ( is_string( $image ) && '' !== $image && $thumb_id && function_exists( 'wp_get_attachment_metadata' ) ) {
			$meta = wp_get_attachment_metadata( (int) $thumb_id );

			if ( is_array( $meta ) && ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
				$image_dims = array(
					'width'  => (int) $meta['width'],
					'height' => (int) $meta['height'],
				);
			}
		}

		if ( ! is_string( $image ) || '' === $image ) {
			$image      = apply_filters( self::FILTER_OG_IMAGE, ATLAS_THEME_URI . '/assets/images/og-default.png' );
			$image_dims = array(); // Dimensions only known for attachment images.
		}

		/**
		 * Filter the og:image dimension array before printing.
		 *
		 * Receives an associative array with integer 'width' and 'height' keys,
		 * or an empty array when dimensions could not be resolved. Return an
		 * empty array to suppress og:image:width / og:image:height output.
		 *
		 * @param array $image_dims Resolved dimensions, or [] when unknown.
		 */
		$image_dims = apply_filters( self::FILTER_OG_IMAGE_DIMS, $image_dims );

		// og:locale — convert WordPress locale (e.g. "en_US", "hi_IN") to
		// underscore format, which is already the native format; kept explicit
		// for clarity and future-proofing.
		$locale = str_replace( '-', '_', get_locale() );

		$tags = array(
			'og:title'       => wp_get_document_title(),
			'og:description' => self::description(),
			'og:url'         => self::current_url(),
			'og:type'        => is_singular( 'post' ) ? 'article' : 'website',
			'og:image'       => $image,
			'og:locale'      => $locale,
			'twitter:card'   => 'summary_large_image',
		);

		/**
		 * Filter the assembled Open Graph / Twitter tag map before printing.
		 *
		 * @param array $tags Map of property/name => content.
		 */
		$tags = apply_filters( self::FILTER_OG_TAGS, $tags );

		if ( ! is_array( $tags ) ) {
			return;
		}

		// Merge validated image dimensions into the tag map after the main filter.
		if ( is_array( $image_dims )
			&& isset( $image_dims['width'], $image_dims['height'] )
			&& (int) $image_dims['width'] > 0
			&& (int) $image_dims['height'] > 0
		) {
			$tags['og:image:width']  = (int) $image_dims['width'];
			$tags['og:image:height'] = (int) $image_dims['height'];
		}

		foreach ( $tags as $key => $value ) {
			$key   = (string) $key;
			$value = (string) $value;

			if ( '' === $key || '' === $value ) {
				continue;
			}

			$is_url    = in_array( $key, array( 'og:url', 'og:image' ), true );
			$attr_name = 0 === strpos( $key, 'og:' ) ? 'property' : 'name';

			if ( $is_url ) {
				printf(
					'<meta %1$s="%2$s" content="%3$s">' . "\n",
					esc_attr( $attr_name ),
					esc_attr( $key ),
					esc_url( $value )
				);
			} else {
				printf(
					'<meta %1$s="%2$s" content="%3$s">' . "\n",
					esc_attr( $attr_name ),
					esc_attr( $key ),
					esc_attr( $value )
				);
			}
		}
	}

	/**
	 * Build and print the self-referential canonical link.
	 *
	 * Printed only on the contexts where a canonical is meaningful — singular
	 * views, archives, and the front page — using the canonical current URL.
	 * Fails closed: when no URL can be resolved nothing is printed.
	 *
	 * @return void
	 */
	private static function print_canonical(): void {
		if ( ! ( is_singular() || is_archive() || is_front_page() ) ) {
			return;
		}

		$url = self::current_url();

		if ( '' === $url ) {
			return;
		}

		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	}

	/**
	 * Build and print the hreflang alternate links.
	 *
	 * Conservative by design: the alternates map comes solely from the
	 * FILTER_HREFLANG filter (locale => URL) and defaults to empty, so NOTHING
	 * is emitted unless a site explicitly supplies alternates. The <html lang>
	 * attribute is left entirely to core. Each entry is printed with the locale
	 * escaped via esc_attr and the URL via esc_url; empty entries are skipped.
	 *
	 * @return void
	 */
	private static function print_hreflang(): void {
		$alternates = apply_filters( self::FILTER_HREFLANG, array() );

		if ( ! is_array( $alternates ) || empty( $alternates ) ) {
			return;
		}

		foreach ( $alternates as $locale => $url ) {
			$locale = (string) $locale;
			$url    = (string) $url;

			if ( '' === $locale || '' === $url ) {
				continue;
			}

			echo '<link rel="alternate" hreflang="' . esc_attr( $locale ) . '" href="' . esc_url( $url ) . '">' . "\n";
		}
	}

	/**
	 * Print rel="prev" and rel="next" pagination link elements.
	 *
	 * Emitted on archive pages (is_archive(), is_home(), is_search()) only when
	 * there are paged results — a previous or next page exists. Each link uses
	 * get_pagenum_link() so it respects permalink structure. Output is suppressed
	 * when a known SEO plugin is active (they handle pagination links). Empty
	 * URLs are skipped (fail closed). URLs are escaped with esc_url().
	 *
	 * Hooked to wp_head at priority 1 (same as print_meta).
	 *
	 * @return void
	 */
	public static function print_pagination_links(): void {
		if ( self::seo_plugin_active() || ! function_exists( 'get_pagenum_link' ) ) {
			return;
		}

		// Only meaningful on paged list contexts.
		if ( ! ( is_archive() || is_home() || is_search() ) ) {
			return;
		}

		global $wp_query;

		$paged     = function_exists( 'get_query_var' ) ? (int) get_query_var( 'paged' ) : 1;
		$paged     = $paged > 0 ? $paged : 1;
		$max_pages = isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 1;

		// rel="prev" — only when we are on page 2+.
		if ( $paged > 1 ) {
			$prev_url = get_pagenum_link( $paged - 1 );

			if ( is_string( $prev_url ) && '' !== $prev_url ) {
				echo '<link rel="prev" href="' . esc_url( $prev_url ) . '">' . "\n";
			}
		}

		// rel="next" — only when a next page exists.
		if ( $paged < $max_pages ) {
			$next_url = get_pagenum_link( $paged + 1 );

			if ( is_string( $next_url ) && '' !== $next_url ) {
				echo '<link rel="next" href="' . esc_url( $next_url ) . '">' . "\n";
			}
		}
	}

	/**
	 * Resolve the canonical URL for the current request.
	 *
	 * Prefers core's wp_get_canonical_url() (singular views), then the permalink
	 * for a resolvable singular object, and finally the home URL — so a sensible
	 * absolute URL is always returned for canonical and og:url.
	 *
	 * @return string The current canonical URL, or '' when none can be resolved.
	 */
	private static function current_url(): string {
		$canonical = wp_get_canonical_url();

		if ( is_string( $canonical ) && '' !== $canonical ) {
			return $canonical;
		}

		if ( is_singular() ) {
			$permalink = get_permalink();

			if ( is_string( $permalink ) && '' !== $permalink ) {
				return $permalink;
			}
		}

		return home_url( '/' );
	}

	/**
	 * Build the og:description value for the current request.
	 *
	 * Uses the post excerpt on singular views and the site tagline elsewhere,
	 * then strips tags, collapses whitespace, and caps the result at
	 * DESCRIPTION_MAX characters so the shared preview stays tidy. Returns '' when
	 * no description is available so the empty tag is skipped (fail closed).
	 *
	 * @return string The trimmed, capped description, or '' when none exists.
	 */
	private static function description(): string {
		$raw = is_singular() ? get_the_excerpt() : get_bloginfo( 'description' );
		$raw = wp_strip_all_tags( (string) $raw );
		$raw = trim( preg_replace( '/\s+/', ' ', $raw ) );

		if ( '' === $raw ) {
			return '';
		}

		if ( mb_strlen( $raw ) > self::DESCRIPTION_MAX ) {
			$raw = rtrim( mb_substr( $raw, 0, self::DESCRIPTION_MAX ) );
		}

		return $raw;
	}
}
