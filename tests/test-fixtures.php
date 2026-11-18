<?php
/**
 * Fixture drift guard.
 *
 * The Playwright harnesses hand-copy parts of the real header markup. When the
 * copy drifts, browser tests silently verify a page that does not exist (this
 * already produced wrong accessibility and layout results once). These checks
 * fail fast in the PHP suite if the interactive controls' opening tags differ.
 *
 * @package AtlasTheme
 */

class Test_Fixtures {

	/**
	 * Return the normalised opening tag of the first element matching a class.
	 *
	 * @param string $html  Source markup.
	 * @param string $class Exact class attribute value to find.
	 * @return string The opening tag with whitespace collapsed, or ''.
	 */
	private static function open_tag( string $html, string $class ): string {
		if ( 1 !== preg_match( '/<(?:a|button|div)\b[^>]*\bclass="' . preg_quote( $class, '/' ) . '"[^>]*>/s', $html, $m ) ) {
			return '';
		}
		return trim( (string) preg_replace( '/\s+/', ' ', $m[0] ) );
	}

	public static function run(): array {
		$results = array();

		$header  = (string) file_get_contents( ATLAS_THEME_DIR . '/parts/header.html' );
		$harness = (string) file_get_contents( dirname( ATLAS_THEME_DIR ) . '/tests/e2e/fixtures/interactive-harness.html' );

		foreach ( array(
			'atlas-nav'           => 'drawer root',
			'atlas-nav__toggle'   => 'drawer toggle link',
			'atlas-nav__close'    => 'drawer close link',
			'atlas-theme-toggle'  => 'theme toggle button',
		) as $class => $label ) {
			$real = self::open_tag( $header, $class );
			$copy = self::open_tag( $harness, $class );

			$results[] = array(
				'name'   => 'Fixture drift: the E2E harness ' . $label . ' opening tag matches theme/parts/header.html',
				'passed' => '' !== $real && $real === $copy,
			);
		}

		$results[] = array(
			'name'   => 'Header: the drawer toggle is a real link to the panel (works with no JS) and not a bare button',
			'passed' => 1 === preg_match( '/<a href="#atlas-nav-panel"[^>]*class="atlas-nav__toggle"/', $header )
				&& ! str_contains( $header, '<button type="button" class="atlas-nav__toggle"' ),
		);

		$results[] = array(
			'name'   => 'Header: the panel has a close link and the root has the id the close link targets',
			'passed' => str_contains( $header, 'href="#atlas-nav" class="atlas-nav__close"' )
				&& str_contains( $header, 'class="atlas-nav" id="atlas-nav"' ),
		);

		// --- The notifications part exists once per page and is not a flex child. ---
		$footer = (string) file_get_contents( ATLAS_THEME_DIR . '/parts/footer.html' );
		$landing = (string) file_get_contents( ATLAS_THEME_DIR . '/parts/footer-landing.html' );
		$tag     = '<!-- wp:template-part {"slug":"notifications"} /-->';

		$results[] = array(
			'name'   => 'Header: the notifications part is included exactly once on a page (header only), so its ids and live regions are not duplicated',
			'passed' => 1 === substr_count( $header, $tag ) && 0 === substr_count( $footer, $tag ) && 0 === substr_count( $landing, $tag ),
		);

		$tag_pos   = strpos( $header, $tag );
		$group_pos = strpos( $header, '<!-- wp:group' );

		$results[] = array(
			'name'   => 'Header: the notifications part sits before the flex group, never inside it (an empty flex child adds a gap and shifts the title)',
			'passed' => false !== $tag_pos && false !== $group_pos && $tag_pos < $group_pos,
		);

		$css = (string) file_get_contents( ATLAS_THEME_DIR . '/assets/css/interactivity.css' );

		$results[] = array(
			'name'   => 'CSS: the mobile drawer has a no-JS :target open rule and is not gated on hydration for its closed layout',
			'passed' => str_contains( $css, '.atlas-nav__panel:target' )
				&& 1 === preg_match( '/@media \(max-width: 48rem\)[^{]*\{\s*(?:\/\*.*?\*\/\s*)+\.atlas-nav__toggle\s*\{\s*display:\s*inline-flex/s', $css ),
		);

		return $results;
	}
}
