<?php
/**
 * Opt-in /llms.txt GEO catalog concern.
 *
 * Owns the Atlas theme's machine-readable /llms.txt catalog — a plain-text
 * Markdown document (an H1 site title, a blockquote site summary, and a capped
 * list of important public published pages as `- [Title](url): desc` lines)
 * that AI crawlers can read to discover the site's canonical content. It serves
 * that catalog by matching the /llms.txt request path on template_redirect; it
 * deliberately registers NO rewrite rule and triggers NO flush/permalink-resave,
 * so the rewrite/query surface and the install's permalink state are left
 * entirely untouched.
 *
 * Default DISABLED (opt-in): nothing is served unless atlas_llms_txt_enabled
 * returns true. Fail-closed is the governing rule — when disabled, when the
 * request path does not match, or when the catalog comes back empty, the handler
 * simply returns and WordPress serves its ordinary 404, so no stack trace or
 * sensitive detail is ever emitted. The catalog exposes ONLY already-public,
 * published, non-password-protected pages; the filterable entry list is re-capped
 * and every entry re-validated after filtering so a filter can never leak drafts,
 * private/password-protected content, or PII. It uses core query APIs only (no
 * raw $wpdb).
 *
 * NOTE: /llms-full.txt (the full-content variant) is intentionally deferred
 * (YAGNI) and MAY later be added behind this same FILTER_ENABLED opt-in.
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
 * Static registrar for the opt-in /llms.txt catalog endpoint.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object. All public surface is reachable from the
 * WordPress hook wired in init().
 */
class Llms_Txt {

	/**
	 * The exact request path that activates the endpoint.
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
	 * Master gate filter: serve the /llms.txt catalog at all.
	 *
	 * Defaults FALSE — this concern is opt-in. A site turns the catalog on by
	 * returning true, so the semantic name lives in a constant rather than a
	 * repeated literal.
	 *
	 * @var string
	 */
	const FILTER_ENABLED = 'atlas_llms_txt_enabled';

	/**
	 * Filter: override or augment the catalog's entry list before formatting.
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
	 * Hard cap on the number of catalog entries.
	 *
	 * Applied both to the default query and again after the entries filter so the
	 * catalog can never balloon regardless of what a filter returns.
	 *
	 * @var int
	 */
	const MAX_ENTRIES = 50;

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
	 * Register the /llms.txt endpoint WordPress hooks.
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
	}

	/**
	 * Emit the /llms.txt catalog when every gate passes, else return.
	 *
	 * Fails closed: it returns immediately unless the enable filter is truthy
	 * (default false), then parses the request path from REQUEST_URI and returns
	 * unless the normalized path exactly equals REQUEST_PATH (the query string is
	 * ignored). On a match it builds the catalog and, if the body comes back
	 * empty, returns so WordPress serves its normal 404. Otherwise it emits a 200
	 * status, a text/plain Content-Type, an X-Robots-Tag: noindex header (the
	 * catalog mirror is never indexed), echoes the body, and exits.
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

		if ( self::REQUEST_PATH !== $path ) {
			return;
		}

		$catalog = self::build_catalog();

		if ( '' === $catalog ) {
			return;
		}

		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );

		echo $catalog; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Machine text/plain body; URLs sanitised with esc_url_raw and text reduced to plain text in build_catalog().
		exit;
	}

	/**
	 * Assemble the plain-text Markdown catalog for the site.
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
	private static function build_catalog(): string {
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
}
