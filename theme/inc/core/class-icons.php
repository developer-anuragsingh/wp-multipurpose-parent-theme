<?php
/**
 * Inline SVG icon set.
 *
 * One source of truth for every icon the theme draws, replacing the Unicode
 * glyphs (hamburger, multiplication sign, sun, moon, ...) that rendered
 * differently on every OS and font, were sometimes substituted by colour emoji,
 * and could not be sized or aligned reliably.
 *
 * Design rules:
 *  - Inline, no request, no icon font, no JS: the SVG ships in the HTML, so it
 *    is present on the first paint (no layout shift) and works with JS off.
 *  - Strokes use currentColor, so an icon follows the button's text colour in
 *    light mode, dark mode, hover and focus without extra CSS.
 *  - Purely decorative: aria-hidden + focusable="false". The control that holds
 *    the icon MUST carry the accessible name (aria-label, or visible text when
 *    the icon sits beside a label). An icon-only control without a name is a
 *    bug (tests/test-icons.php enforces this on the theme's own markup).
 *  - Explicit width/height attributes reserve the box before the stylesheet
 *    loads; CSS then sizes it in em.
 *
 * theme/parts/*.html are static HTML and cannot call PHP, so they carry literal
 * copies of these SVGs; tests/test-icons.php fails if a copy drifts.
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static icon registry.
 */
class Icons {

	/**
	 * Icon name => inner SVG markup (24x24 grid, stroke-based).
	 *
	 * @var array<string,string>
	 */
	const PATHS = array(
		'menu'         => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'        => '<path d="M6 6l12 12M18 6L6 18"/>',
		'copy'         => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/>',
		'check'        => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
		'sun'          => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'moon'         => '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5z"/>',
		'refresh'      => '<path d="M20 12a8 8 0 1 1-2.34-5.66"/><path d="M20 4v6h-6"/>',
		'chevron-down' => '<path d="M6 9l6 6 6-6"/>',
		'send'         => '<path d="M21 3L10 14"/><path d="M21 3l-7 18-4-7-7-4 18-7z"/>',
		'log-in'       => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l5-5-5-5M15 12H4"/>',
		'phone'        => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
		'mail'         => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/>',
		'map-pin'      => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
		'clock'        => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
		'share'        => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>',
		'whatsapp'     => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
		'telegram'     => '<path d="M21.5 2L2 10.5l7 3 2.5 7.5L15 17l6.5 4L21.5 2zM9 13.5l8.5-8L11 15l-2-1.5z"/>',
		'instagram'    => '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
		'facebook'     => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
		'twitter'      => '<path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>',
		'linkedin'     => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
	);

	/**
	 * Default rendered size in pixels (reserves the box; CSS sizes it in em).
	 *
	 * @var int
	 */
	const SIZE = 20;

	/**
	 * Build an icon's <svg> markup.
	 *
	 * Output is static, trusted markup from PATHS; only the optional class is
	 * caller-supplied and it is escaped. An unknown name returns '' so a typo
	 * can never emit a broken tag.
	 *
	 * @param string $name  Icon name, a key of PATHS.
	 * @param string $extra_class Extra CSS class(es) appended after "atlas-icon".
	 * @return string The SVG markup, or '' for an unknown icon.
	 */
	public static function get( string $name, string $extra_class = '' ): string {
		if ( ! isset( self::PATHS[ $name ] ) ) {
			return '';
		}

		$classes = trim( 'atlas-icon atlas-icon--' . $name . ' ' . $extra_class );

		return sprintf(
			'<svg class="%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
			esc_attr( $classes ),
			self::SIZE,
			self::PATHS[ $name ]
		);
	}

	/**
	 * Every registered icon name.
	 *
	 * @return string[] Names.
	 */
	public static function names(): array {
		return array_keys( self::PATHS );
	}
}
