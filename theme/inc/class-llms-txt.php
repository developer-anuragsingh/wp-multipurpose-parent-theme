<?php
/**
 * Opt-in /llms.txt and /llms-full.txt GEO catalog concern.
 *
 * Owns the Atlas theme's machine-readable /llms.txt and /llms-full.txt catalogs:
 * 1. /llms.txt: A plain-text Markdown digest (an H1 site title, a blockquote
 *    site summary, and a capped list of core public pages formatted as
 *    `- [Title](url): desc` lines).
 * 2. /llms-full.txt: An expanded full-content Markdown digest including the
 *    complete converted Markdown text of core public pages to feed AI context
 *    windows directly.
 *
 * Both endpoints match the request path on template_redirect without touching
 * rewrite rules or flushing permalinks. Default DISABLED (opt-in): nothing is
 * served unless atlas_llms_txt_enabled returns true. Both endpoints fail
 * closed: when disabled, unmatched, or empty, WordPress serves its ordinary
 * 404 without leaking any technical detail.
 *
 * Both catalogs expose ONLY already-public, published, non-password-protected
 * pages using core query APIs. Both catalogs use 12-hour transient caching
 * (TRANSIENT_KEY and TRANSIENT_KEY_FULL) with automatic invalidation on
 * save_post, deleted_post, and switch_theme.
 *
 * Mirrors Atlas\Robots / Atlas\Markdown_Endpoint precisely — a static registrar
 * wired from functions.php after Atlas\Markdown_Endpoint::init(). Resolved via
 * the hand-rolled autoloader (Atlas\Llms_Txt -> inc/class-llms-txt.php). The
 * response body is machine output, so no string here is gettext.
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for the opt-in /llms.txt and /llms-full.txt catalog endpoints.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object. All public surface is reachable from the
 * WordPress hook wired in init().
 */
class Llms_Txt {

	/**
	 * The exact request path that activates the concise catalog endpoint.
	 *
	 * Matched against the parsed REQUEST_URI path on template_redirect rather
	 * than registered as a rewrite rule, so the endpoint never touches the
	 * rewrite rules or the permalink state. Named in a constant per the
	 * no-magic-strings rule.
	 *
	 * @var string
	 */
	const REQUEST_PATH = '/llms.txt';

	/**
	 * The exact request path that activates the expanded full-content catalog endpoint.
	 *
	 * @var string
	 */
	const REQUEST_PATH_FULL = '/llms-full.txt';

	/**
	 * Master gate filter: serve the /llms.txt and /llms-full.txt catalogs at all.
	 *
	 * Defaults FALSE — this concern is opt-in. A site turns the catalogs on by
	 * returning true, so the semantic name lives in a constant rather than a
	 * repeated literal.
	 *
	 * @var string
	 */
	const FILTER_ENABLED = 'atlas_llms_txt_enabled';

	/**
	 * Filter: override or augment the concise catalog's entry list before formatting.
	 *
	 * Receives the default entry list (each entry an array of id/title/url/desc)
	 * so a site can curate which pages appear. The returned list is re-capped and
	 * every entry re-validated afterwards, so the filter can never leak
	 * non-public content.
	 *
	 * @var string
	 */
	const FILTER_ENTRIES = 'atlas_llms_txt_entries';

	/**
	 * Filter: override or augment the full catalog's page list before formatting.
	 *
	 * Receives the default page list (query post objects) so a site can curate
	 * which pages have their full markdown published in /llms-full.txt.
	 *
	 * @var string
	 */
	const FILTER_FULL_ENTRIES = 'atlas_llms_txt_full_entries';

	/**
	 * Filter: customize the maximum number of pages in the full catalog.
	 *
	 * @var string
	 */
	const FILTER_FULL_MAX_PAGES = 'atlas_llms_txt_full_max_pages';

	/**
	 * Hard cap on the number of concise catalog entries.
	 *
	 * Applied both to the default query and again after the entries filter so the
	 * catalog can never balloon regardless of what a filter returns.
	 *
	 * @var int
	 */
	const MAX_ENTRIES = 50;

	/**
	 * Default cap on the number of full catalog pages.
	 *
	 * Kept at 10 to protect against memory exhaustion and large payload DoS.
	 *
	 * @var int
	 */
	const MAX_FULL_PAGES = 10;

	/**
	 * Maximum length (in characters) of a single entry description.
	 *
	 * Entry descriptions come from the page excerpt; capping keeps each catalog
	 * line to a tidy single-line summary rather than a wall of text.
	 *
	 * @var int
	 */
	const DESCRIPTION_MAX = 160;

	/**
	 * Transient key for caching the compiled /llms.txt catalog.
	 *
	 * @var string
	 */
	const TRANSIENT_KEY = 'atlas_llms_txt_catalog';

	/**
	 * Transient key for caching the compiled /llms-full.txt catalog.
	 *
	 * @var string
	 */
	const TRANSIENT_KEY_FULL = 'atlas_llms_txt_full_catalog';

	/**
	 * Cache TTL for catalog transients in seconds (12 hours).
	 *
	 * @var int
	 */
	const TRANSIENT_TTL = 43200;

	/**
	 * Register the /llms.txt and /llms-full.txt endpoint WordPress hooks.
	 *
	 * Called once from functions.php after Atlas\Markdown_Endpoint::init(). Hooks
	 * template_redirect so a matched, enabled request can emit the catalog and
	 * exit before WordPress produces its normal response. Keeps all endpoint
	 * wiring in one place so the boot order is explicit.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( self::class, 'maybe_render_llms_txt' ) );
		add_action( 'save_post', array( self::class, 'invalidate_cache' ) );
		add_action( 'deleted_post', array( self::class, 'invalidate_cache' ) );
		add_action( 'switch_theme', array( self::class, 'invalidate_cache' ) );
	}

	/**
	 * Emit the /llms.txt or /llms-full.txt catalog when every gate passes, else return.
	 *
	 * Fails closed: it returns immediately unless the enable filter is truthy
	 * (default false), then parses the request path from REQUEST_URI and returns
	 * unless the normalized path exactly equals REQUEST_PATH or REQUEST_PATH_FULL.
	 * On a match it builds the appropriate catalog and, if the body comes back
	 * empty, returns so WordPress serves its normal 404. Otherwise it emits a 200
	 * status, a text/plain Content-Type, an X-Robots-Tag: noindex header, echoes
	 * the body, and exits.
	 *
	 * @return void
	 */
	public static function maybe_render_llms_txt(): void {
		if ( ! apply_filters( self::FILTER_ENABLED, false ) ) {
			return;
		}

		// Read-only public GET: sanitise on input, compare path only (ignore query).
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

		if ( ! is_string( $path ) || '' === $path ) {
			return;
		}

		$path = untrailingslashit( $path );

		if ( self::REQUEST_PATH === $path ) {
			self::render_catalog();
		} elseif ( self::REQUEST_PATH_FULL === $path ) {
			self::render_full_catalog();
		}
	}

	/**
	 * Render and output the concise /llms.txt catalog.
	 *
	 * @return void
	 */
	private static function render_catalog(): void {
		$catalog = get_transient( self::TRANSIENT_KEY );

		if ( ! is_string( $catalog ) || '' === $catalog ) {
			$catalog = self::build_catalog();

			if ( '' !== $catalog ) {
				set_transient( self::TRANSIENT_KEY, $catalog, self::TRANSIENT_TTL );
			}
		}

		if ( '' === $catalog ) {
			return;
		}

		self::emit_response( $catalog );
	}

	/**
	 * Render and output the expanded /llms-full.txt catalog.
	 *
	 * @return void
	 */
	private static function render_full_catalog(): void {
		$catalog = get_transient( self::TRANSIENT_KEY_FULL );

		if ( ! is_string( $catalog ) || '' === $catalog ) {
			$catalog = self::build_full_catalog();

			if ( '' !== $catalog ) {
				set_transient( self::TRANSIENT_KEY_FULL, $catalog, self::TRANSIENT_TTL );
			}
		}

		if ( '' === $catalog ) {
			return;
		}

		self::emit_response( $catalog );
	}

	/**
	 * Emit plain text HTTP response headers and body, then terminate request.
	 *
	 * @param string $body Response body text.
	 * @return void
	 */
	private static function emit_response( string $body ): void {
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );

		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Machine text/plain body; URLs sanitised with esc_url_raw and content converted to plain text.
		exit;
	}

	/**
	 * Assemble the concise plain-text Markdown catalog (/llms.txt) for the site.
	 *
	 * Builds, in order: an H1 line (`# ` + the site name), a blockquote summary
	 * (`> ` + the site description, skipped when empty), and a `## Pages` section
	 * of entry lines. Default entries come from published public pages via
	 * get_posts() (post_type=page, post_status=publish, capped at MAX_ENTRIES) —
	 * no raw $wpdb. The entry list is passed through FILTER_ENTRIES, then
	 * re-capped at MAX_ENTRIES and every entry re-validated (page must still be
	 * publish, of a publicly viewable type, not password protected, and carry a
	 * valid URL) so the filter can never leak non-public content. Each surviving
	 * entry is formatted as `- [Title](url): desc`. Returns '' when nothing
	 * qualifies so the handler fails closed to a normal 404.
	 *
	 * @return string The assembled catalog, or '' when nothing qualifies.
	 */
	public static function build_catalog(): string {
		$lines = array();

		$site_name = trim( (string) get_bloginfo( 'name' ) );

		if ( '' === $site_name ) {
			return '';
		}

		$lines[] = '# ' . $site_name;

		$summary = trim( (string) get_bloginfo( 'description' ) );

		if ( '' !== $summary ) {
			$lines[] = '';
			$lines[] = '> ' . $summary;
		}

		$entries = self::collect_entries();

		/**
		 * Filter the /llms.txt catalog entry list before formatting.
		 *
		 * Each entry is an array with keys id, title, url, desc. The returned
		 * list is re-capped at MAX_ENTRIES and every entry re-validated against
		 * the public-content rules, so a filter can curate the catalog but can
		 * never leak non-public content.
		 *
		 * @param array[] $entries Default entry list.
		 */
		$entries = apply_filters( self::FILTER_ENTRIES, $entries );

		if ( ! is_array( $entries ) ) {
			return '';
		}

		$entries = array_slice( $entries, 0, self::MAX_ENTRIES );

		$entry_lines = array();

		foreach ( $entries as $entry ) {
			$line = self::format_entry( $entry );

			if ( '' !== $line ) {
				$entry_lines[] = $line;
			}
		}

		if ( empty( $entry_lines ) ) {
			return '';
		}

		$lines[] = '';
		$lines[] = '## Pages';
		$lines[] = '';

		$lines = array_merge( $lines, $entry_lines );

		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Assemble the expanded full-content Markdown catalog (/llms-full.txt) for the site.
	 *
	 * Formats an H1 site title, optional blockquote summary, an intro description,
	 * and each core page separated by markdown thematic breaks ('---') with its
	 * converted block markdown body. Excludes any drafts, non-viewable types, or
	 * password-protected pages.
	 *
	 * @return string The assembled full catalog, or '' when nothing qualifies.
	 */
	public static function build_full_catalog(): string {
		$site_name = trim( (string) get_bloginfo( 'name' ) );

		if ( '' === $site_name ) {
			return '';
		}

		$lines   = array();
		$lines[] = '# ' . $site_name;

		$summary = trim( (string) get_bloginfo( 'description' ) );

		if ( '' !== $summary ) {
			$lines[] = '';
			$lines[] = '> ' . $summary;
		}

		$lines[] = '';
		$lines[] = 'This document provides the full text of core public pages from ' . $site_name . '.';

		$max_pages = (int) apply_filters( self::FILTER_FULL_MAX_PAGES, self::MAX_FULL_PAGES );
		$max_pages = max( 1, min( 50, $max_pages ) );

		$pages = self::collect_full_pages( $max_pages );

		/**
		 * Filter the /llms-full.txt page list before converting to Markdown.
		 *
		 * @param \WP_Post[] $pages Query array of post objects.
		 */
		$pages = apply_filters( self::FILTER_FULL_ENTRIES, $pages );

		if ( ! is_array( $pages ) || empty( $pages ) ) {
			return '';
		}

		$pages = array_slice( $pages, 0, $max_pages );

		$page_sections = array();

		foreach ( $pages as $page ) {
			$section = self::format_full_page( $page );

			if ( '' !== $section ) {
				$page_sections[] = $section;
			}
		}

		if ( empty( $page_sections ) ) {
			return '';
		}

		foreach ( $page_sections as $section ) {
			$lines[] = '';
			$lines[] = '---';
			$lines[] = '';
			$lines[] = $section;
		}

		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Query core published pages for the full catalog.
	 *
	 * @param int $max_pages Maximum number of pages to query.
	 * @return array The queried post objects.
	 */
	private static function collect_full_pages( int $max_pages ): array {
		$pages = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'publish',
				'posts_per_page'   => $max_pages,
				'orderby'          => 'menu_order title',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		return is_array( $pages ) ? $pages : array();
	}

	/**
	 * Re-validate a post object and convert its content to Markdown for the full catalog.
	 *
	 * @param mixed $page Candidate post object or array.
	 * @return string The converted Markdown section, or '' when invalid.
	 */
	private static function format_full_page( $page ): string {
		$post_id = 0;

		if ( is_object( $page ) && isset( $page->ID ) ) {
			$post_id = (int) $page->ID;
		} elseif ( is_array( $page ) && isset( $page['id'] ) ) {
			$post_id = (int) $page['id'];
		}

		if ( $post_id <= 0 ) {
			return '';
		}

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return '';
		}

		if ( ! is_post_type_viewable( get_post_type( $post_id ) ) ) {
			return '';
		}

		if ( post_password_required( $post_id ) ) {
			return '';
		}

		$post = get_post( $post_id );

		if ( ! $post || ! isset( $post->post_content ) ) {
			return '';
		}

		$title = trim( wp_strip_all_tags( (string) get_the_title( $post_id ) ) );

		if ( '' === $title ) {
			return '';
		}

		$markdown = Markdown_Endpoint::blocks_to_markdown( (string) $post->post_content, $title );

		return trim( $markdown );
	}

	/**
	 * Build the default entry list from published public pages.
	 *
	 * Queries pages via get_posts() (post_type=page, post_status=publish, capped
	 * at MAX_ENTRIES) — core API only, no raw $wpdb — and maps each to an entry
	 * array (id/title/url/desc). Validation is deferred to format_entry() so the
	 * same rules apply to both default and filtered entries.
	 *
	 * @return array[] The default entry list.
	 */
	private static function collect_entries(): array {
		$pages = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'publish',
				'posts_per_page'   => self::MAX_ENTRIES,
				'orderby'          => 'menu_order title',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		if ( ! is_array( $pages ) || empty( $pages ) ) {
			return array();
		}

		$entries = array();

		foreach ( $pages as $page ) {
			$entries[] = array(
				'id'    => (int) $page->ID,
				'title' => (string) get_the_title( $page->ID ),
				'url'   => esc_url_raw( (string) get_permalink( $page->ID ) ),
				'desc'  => self::entry_description( $page->ID ),
			);
		}

		return $entries;
	}

	/**
	 * Validate one entry and format it as a catalog line.
	 *
	 * Re-validates the entry's post regardless of its origin — it must resolve to
	 * a page/post that is still published, of a publicly viewable type, and not
	 * password protected — and requires a valid http(s) URL and a non-empty
	 * title. Any failure returns '' so the entry is dropped (fail closed). On
	 * pass it returns `- [Title](url): desc`, with the trailing `: desc` omitted
	 * when the description is empty.
	 *
	 * @param mixed $entry The candidate entry (expected array of id/title/url/desc).
	 * @return string The formatted catalog line, or '' when the entry is invalid.
	 */
	private static function format_entry( $entry ): string {
		if ( ! is_array( $entry ) || ! isset( $entry['id'] ) ) {
			return '';
		}

		$post_id = (int) $entry['id'];

		if ( $post_id <= 0 ) {
			return '';
		}

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return '';
		}

		if ( ! is_post_type_viewable( get_post_type( $post_id ) ) ) {
			return '';
		}

		if ( post_password_required( $post_id ) ) {
			return '';
		}

		$url = isset( $entry['url'] ) ? esc_url_raw( (string) $entry['url'] ) : '';

		if ( '' === $url ) {
			return '';
		}

		$title = isset( $entry['title'] ) ? trim( wp_strip_all_tags( (string) $entry['title'] ) ) : '';

		if ( '' === $title ) {
			return '';
		}

		$desc = isset( $entry['desc'] ) ? trim( wp_strip_all_tags( (string) $entry['desc'] ) ) : '';
		$desc = trim( (string) preg_replace( '/\s+/', ' ', $desc ) );

		$line = '- [' . $title . '](' . $url . ')';

		if ( '' !== $desc ) {
			$line .= ': ' . $desc;
		}

		return $line;
	}

	/**
	 * Build the short description for one entry from its excerpt.
	 *
	 * Reads the page excerpt, strips tags, collapses whitespace, and caps the
	 * result at DESCRIPTION_MAX characters so each catalog line stays a tidy
	 * single-line summary. Returns '' when no excerpt is available.
	 *
	 * @param int $post_id The page ID.
	 * @return string The trimmed, capped description, or '' when none exists.
	 */
	private static function entry_description( int $post_id ): string {
		$raw = wp_strip_all_tags( (string) get_the_excerpt( $post_id ) );
		$raw = trim( (string) preg_replace( '/\s+/', ' ', $raw ) );

		if ( '' === $raw ) {
			return '';
		}

		if ( mb_strlen( $raw ) > self::DESCRIPTION_MAX ) {
			$raw = rtrim( mb_substr( $raw, 0, self::DESCRIPTION_MAX ) );
		}

		return $raw;
	}

	/**
	 * Invalidate the /llms.txt and /llms-full.txt transient caches when content changes.
	 *
	 * Hooked to save_post, deleted_post, and switch_theme.
	 *
	 * @return void
	 */
	public static function invalidate_cache(): void {
		delete_transient( self::TRANSIENT_KEY );
		delete_transient( self::TRANSIENT_KEY_FULL );
	}
}
