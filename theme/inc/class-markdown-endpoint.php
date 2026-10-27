<?php
/**
 * Opt-in clean-Markdown endpoint concern (?format=md).
 *
 * Owns the Atlas theme's machine-readable Markdown view of single posts. When a
 * public request carries ?format=md and the current query resolves to a single
 * published, publicly viewable, non-password-protected post, this concern
 * converts the post's block content to pure Markdown and serves it as its own
 * response — text/markdown, a canonical Link header pointing at the normal HTML
 * permalink, and X-Robots-Tag: noindex so the Markdown mirror is never indexed.
 *
 * It hooks template_redirect (which fires BEFORE wp_head) and calls exit on a
 * successful render, so Atlas\Seo and Atlas\Schema — both wired on wp_head — are
 * never reached for a Markdown response and no HTML <head> meta leaks into it.
 *
 * Fail-closed is the governing rule: every gate (the enable filter, the trigger
 * value, singular/published/viewable/not-password-protected, and a non-empty
 * conversion) simply returns on failure, letting WordPress render the ordinary
 * HTML page. The endpoint deliberately does NOT register a public query var
 * (reading $_GET directly keeps the rewrite/query surface untouched) and emits
 * only already-public content, so there is no PII exposure and no CSRF nonce is
 * needed for a read-only public GET.
 *
 * Mirrors Atlas\Seo / Atlas\Robots precisely — a static registrar wired from
 * functions.php after Atlas\Robots::init(). Resolved via the hand-rolled
 * autoloader (Atlas\Markdown_Endpoint -> inc/class-markdown-endpoint.php). The
 * response body is machine output, so no string here is gettext.
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for the opt-in ?format=md Markdown endpoint.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object. All public surface is reachable from the
 * WordPress hook wired in init().
 */
class Markdown_Endpoint {

	/**
	 * The request key that activates the endpoint.
	 *
	 * Read directly from $_GET rather than registered as a public query var so
	 * the endpoint never touches the rewrite rules or the main query. Named in a
	 * constant per the no-magic-strings rule.
	 *
	 * @var string
	 */
	const QUERY_VAR = 'format';

	/**
	 * The QUERY_VAR value that triggers a Markdown response.
	 *
	 * Only this exact value (after sanitize_key normalisation) switches the
	 * request to Markdown; any other value falls through to normal HTML.
	 *
	 * @var string
	 */
	const TRIGGER_VALUE = 'md';

	/**
	 * Master gate filter: serve the Markdown endpoint at all.
	 *
	 * Defaults true (enabled). A site may hard-disable the whole concern by
	 * returning false, so the semantic name lives in a constant rather than a
	 * repeated literal.
	 *
	 * @var string
	 */
	const FILTER_ENABLED = 'atlas_markdown_endpoint_enabled';

	/**
	 * Register the Markdown endpoint WordPress hooks.
	 *
	 * Called once from functions.php after Atlas\Robots::init(). Hooks
	 * template_redirect — which fires before wp_head — so a successful render can
	 * exit before any SEO/Schema head meta is produced. Keeps all endpoint wiring
	 * in one place so the boot order is explicit.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( self::class, 'maybe_render_markdown' ) );
	}

	/**
	 * Render the Markdown response when every gate passes, else return.
	 *
	 * Fails closed: it reads and sanitises the trigger from $_GET and returns
	 * unless ALL of the following hold — the trigger equals TRIGGER_VALUE, the
	 * enable filter is truthy, the request is singular, the resolved post is
	 * published and of a publicly viewable type, and it is not password
	 * protected. On pass it builds the Markdown body (returning if the body comes
	 * back empty), then emits a 200 status, a text/markdown Content-Type, a
	 * canonical Link header to the normal permalink, and an X-Robots-Tag: noindex
	 * header, echoes the body and exits — before wp_head is ever reached.
	 *
	 * @return void
	 */
	public static function maybe_render_markdown(): void {
		// Read-only public GET of public content: sanitise on input, no nonce.
		$trigger = isset( $_GET[ self::QUERY_VAR ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_key( wp_unslash( $_GET[ self::QUERY_VAR ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';

		if ( self::TRIGGER_VALUE !== $trigger ) {
			return;
		}

		if ( ! apply_filters( self::FILTER_ENABLED, true ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return;
		}

		if ( ! is_post_type_viewable( get_post_type( $post_id ) ) ) {
			return;
		}

		if ( post_password_required( $post_id ) ) {
			return;
		}

		$markdown = self::blocks_to_markdown(
			(string) get_post_field( 'post_content', $post_id ),
			(string) get_the_title( $post_id )
		);

		if ( '' === $markdown ) {
			return;
		}

		status_header( 200 );
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Link: <' . esc_url_raw( (string) get_permalink( $post_id ) ) . '>; rel="canonical"' );
		header( 'X-Robots-Tag: noindex' );

		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Machine Markdown body; URLs sanitised with esc_url_raw and text decoded to plain text in blocks_to_markdown().
		exit;
	}

	/**
	 * Convert a post's block content to a pure-Markdown document.
	 *
	 * Pure by design: it reads ONLY its two arguments and no request or global
	 * state, so it is testable in isolation. It prepends the plain title as an
	 * H1, then walks parse_blocks() output converting the handled core blocks
	 * (paragraph, heading, list/list-item, quote, image, code, preformatted) to
	 * their Markdown equivalents while preserving inline links. Any unknown or
	 * unhandled block degrades to the plain text of its rendered output and is
	 * skipped when empty, so no raw block delimiters or HTML clutter ever reach
	 * the body. URLs are sanitised with esc_url_raw and entities are decoded so
	 * the result is plain text.
	 *
	 * @param string $post_content The raw post_content (block markup).
	 * @param string $title        The plain post title for the H1.
	 * @return string The Markdown document, or '' when nothing converts.
	 */
	private static function blocks_to_markdown( string $post_content, string $title ): string {
		$lines = array();

		$title = self::plain_text( $title );

		if ( '' !== $title ) {
			$lines[] = '# ' . $title;
		}

		foreach ( parse_blocks( $post_content ) as $block ) {
			$chunk = self::block_to_markdown( $block );

			if ( '' !== $chunk ) {
				$lines[] = $chunk;
			}
		}

		$body = trim( implode( "\n\n", $lines ) );

		return '' === $body ? '' : $body . "\n";
	}

	/**
	 * Convert a single parsed block to its Markdown representation.
	 *
	 * Dispatches on the block name to the handled core blocks and recurses into
	 * inner blocks for container blocks (list, quote). Returns '' for a block
	 * that yields no content so the caller can skip it. Unknown blocks fall
	 * through to a plain-text degrade of their rendered HTML — never raw markup.
	 *
	 * @param array $block A single block array from parse_blocks().
	 * @return string The Markdown for the block, or '' when empty.
	 */
	private static function block_to_markdown( array $block ): string {
		$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';

		switch ( $name ) {
			case 'core/paragraph':
				return self::inline_markdown( self::inner_html( $block ) );

			case 'core/heading':
				return self::heading_to_markdown( $block );

			case 'core/list':
				return self::list_to_markdown( $block );

			case 'core/list-item':
				// Handled by the parent list; emit nothing on its own.
				return '';

			case 'core/quote':
				return self::quote_to_markdown( $block );

			case 'core/image':
				return self::image_to_markdown( $block );

			case 'core/code':
			case 'core/preformatted':
				return self::code_to_markdown( $block );

			case '':
				// Freeform/classic HTML (null blockName): degrade to plain text.
				return self::plain_text( self::inner_html( $block ) );

			default:
				return self::plain_text( render_block( $block ) );
		}
	}

	/**
	 * Convert a core/heading block to an ATX Markdown heading.
	 *
	 * Reads the level attribute (defaulting to 2, as the block editor does) and
	 * clamps it to the valid 1..6 range, then prefixes that many '#' characters.
	 * Returns '' when the heading text is empty so no bare marker is emitted.
	 *
	 * @param array $block The core/heading block.
	 * @return string The Markdown heading, or '' when empty.
	 */
	private static function heading_to_markdown( array $block ): string {
		$level = isset( $block['attrs']['level'] ) ? (int) $block['attrs']['level'] : 2;
		$level = max( 1, min( 6, $level ) );
		$text  = self::inline_markdown( self::inner_html( $block ) );

		if ( '' === $text ) {
			return '';
		}

		return str_repeat( '#', $level ) . ' ' . $text;
	}

	/**
	 * Convert a core/list block (and its core/list-item children) to Markdown.
	 *
	 * Uses '1. ' markers for an ordered list (the ordered attribute) and '- '
	 * otherwise, one line per non-empty item. Returns '' when the list has no
	 * usable items so no empty block is emitted.
	 *
	 * @param array $block The core/list block.
	 * @return string The Markdown list, or '' when empty.
	 */
	private static function list_to_markdown( array $block ): string {
		$ordered = ! empty( $block['attrs']['ordered'] );
		$items   = array();
		$index   = 1;

		foreach ( (array) ( $block['innerBlocks'] ?? array() ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$text = self::inline_markdown( self::inner_html( $item ) );

			if ( '' === $text ) {
				continue;
			}

			$marker  = $ordered ? $index . '. ' : '- ';
			$items[] = $marker . $text;
			++$index;
		}

		return empty( $items ) ? '' : implode( "\n", $items );
	}

	/**
	 * Convert a core/quote block to a Markdown blockquote.
	 *
	 * Renders the inner blocks to plain paragraphs, then prefixes every line with
	 * '> '. Returns '' when the quote is empty so no bare marker is emitted.
	 *
	 * @param array $block The core/quote block.
	 * @return string The Markdown blockquote, or '' when empty.
	 */
	private static function quote_to_markdown( array $block ): string {
		$parts = array();

		foreach ( (array) ( $block['innerBlocks'] ?? array() ) as $inner ) {
			if ( ! is_array( $inner ) ) {
				continue;
			}

			$chunk = self::block_to_markdown( $inner );

			if ( '' !== $chunk ) {
				$parts[] = $chunk;
			}
		}

		$text = trim( implode( "\n", $parts ) );

		if ( '' === $text ) {
			return '';
		}

		$quoted = array();

		foreach ( explode( "\n", $text ) as $line ) {
			$quoted[] = '> ' . $line;
		}

		return implode( "\n", $quoted );
	}

	/**
	 * Convert a core/image block to Markdown image syntax.
	 *
	 * Resolves the src from the first <img> in the block's inner HTML and the alt
	 * from its alt attribute, then emits ![alt](src) with the URL sanitised via
	 * esc_url_raw. Returns '' when no src can be found so no broken image is
	 * emitted.
	 *
	 * @param array $block The core/image block.
	 * @return string The Markdown image, or '' when no source resolves.
	 */
	private static function image_to_markdown( array $block ): string {
		$html = self::inner_html( $block );

		$src = '';
		$alt = '';

		if ( preg_match( '/<img[^>]*\ssrc=("|\')(.*?)\1/i', $html, $src_match ) ) {
			$src = $src_match[2];
		}

		if ( preg_match( '/<img[^>]*\salt=("|\')(.*?)\1/i', $html, $alt_match ) ) {
			$alt = self::plain_text( $alt_match[2] );
		}

		$src = esc_url_raw( $src );

		if ( '' === $src ) {
			return '';
		}

		return '![' . $alt . '](' . $src . ')';
	}

	/**
	 * Convert a core/code or core/preformatted block to a fenced code block.
	 *
	 * Strips the wrapping tags to the raw inner text and wraps it in a
	 * triple-backtick fence. Entities are decoded so the fenced text is literal.
	 * Returns '' when the block has no content.
	 *
	 * @param array $block The core/code or core/preformatted block.
	 * @return string The fenced Markdown code block, or '' when empty.
	 */
	private static function code_to_markdown( array $block ): string {
		$html = self::inner_html( $block );
		$text = wp_strip_all_tags( $html );
		$text = self::decode( $text );
		$text = trim( $text, "\n" );

		if ( '' === trim( $text ) ) {
			return '';
		}

		return "```\n" . $text . "\n```";
	}

	/**
	 * Reduce a block's HTML to Markdown inline text, preserving links.
	 *
	 * Rewrites <a href="..."> elements to [text](url) (URL sanitised with
	 * esc_url_raw), strips every remaining tag to plain text, decodes entities,
	 * and collapses runs of whitespace so the result is a single tidy line.
	 * Returns '' when nothing remains.
	 *
	 * @param string $html The block inner HTML.
	 * @return string The inline Markdown text, or '' when empty.
	 */
	private static function inline_markdown( string $html ): string {
		$html = preg_replace_callback(
			'/<a\b[^>]*\shref=("|\')(.*?)\1[^>]*>(.*?)<\/a>/is',
			static function ( $matches ) {
				$url  = esc_url_raw( html_entity_decode( $matches[2], ENT_QUOTES, 'UTF-8' ) );
				$text = self::plain_text( $matches[3] );

				if ( '' === $url ) {
					return $text;
				}

				if ( '' === $text ) {
					$text = $url;
				}

				return '[' . $text . '](' . $url . ')';
			},
			$html
		);

		return self::plain_text( (string) $html );
	}

	/**
	 * Strip a fragment of HTML to a single decoded plain-text line.
	 *
	 * Removes all tags, decodes entities, and collapses internal whitespace so
	 * the body is plain text without HTML clutter.
	 *
	 * @param string $html The HTML (or text) fragment.
	 * @return string The trimmed plain-text equivalent.
	 */
	private static function plain_text( string $html ): string {
		$text = wp_strip_all_tags( $html );
		$text = self::decode( $text );
		$text = preg_replace( '/\s+/', ' ', $text );

		return trim( (string) $text );
	}

	/**
	 * Decode HTML entities to their literal characters.
	 *
	 * Centralises entity decoding so every converter emits literal UTF-8 text
	 * rather than &amp;-style entities.
	 *
	 * @param string $text The possibly entity-encoded text.
	 * @return string The decoded text.
	 */
	private static function decode( string $text ): string {
		return html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Concatenate a block's innerHTML / innerContent fragments.
	 *
	 * The parse_blocks() output splits a block's HTML across innerContent (with
	 * nulls where inner blocks sit); joining the string fragments yields the
	 * block's own markup. Falls back to innerHTML when present.
	 *
	 * @param array $block A parsed block array.
	 * @return string The block's own HTML.
	 */
	private static function inner_html( array $block ): string {
		if ( isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
			$html = '';

			foreach ( $block['innerContent'] as $fragment ) {
				if ( is_string( $fragment ) ) {
					$html .= $fragment;
				}
			}

			return $html;
		}

		return isset( $block['innerHTML'] ) ? (string) $block['innerHTML'] : '';
	}
}
