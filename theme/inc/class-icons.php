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
