<?php
/**
 * Icon system guards.
 *
 * Icons are decorative; the control around them must carry the accessible
 * name. These checks run in the fast PHP suite so an icon-only button without a
 * name, an invalid or unsafe SVG, or a drifted static copy cannot ship.
 *
 * @package AtlasTheme
 */

class Test_Icons {

	/**
	 * Controls (a/button) that contain no text of their own but are not named.
	 *
	 * @param string $html Markup to scan.
	 * @return string[] Descriptions of unnamed icon-only controls.
	 */
	private static function unnamed_controls( string $html ): array {
		$bad = array();
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8"?><body>' . $html . '</body>' );
		libxml_clear_errors();

		foreach ( array( 'a', 'button' ) as $tag ) {
			foreach ( $dom->getElementsByTagName( $tag ) as $el ) {
				/** @var DOMElement $el */
				if ( '' !== trim( $el->textContent ) ) {
					continue; // Has visible text: that is its name.
				}
				if ( '' === trim( (string) $el->getAttribute( 'aria-label' ) ) && '' === trim( (string) $el->getAttribute( 'aria-labelledby' ) ) ) {
					$bad[] = '<' . $tag . ' class="' . $el->getAttribute( 'class' ) . '">';
				}
			}
		}

		return $bad;
	}

	public static function run(): array {
		$results = array();
		$names   = Atlas\Icons::names();

		// --- Registry quality. ---
		$all_valid = true;
		$unsafe    = false;
		foreach ( $names as $name ) {
			$svg = Atlas\Icons::get( $name );
			$xml = new DOMDocument();
			libxml_use_internal_errors( true );
			$ok = $xml->loadXML( $svg );
			libxml_clear_errors();
			$all_valid = $all_valid && $ok && 'svg' === $xml->documentElement->nodeName;
			if ( preg_match( '/<script|\son[a-z]+=|javascript:|<foreignObject|href=/i', $svg ) ) {
				$unsafe = true;
			}
		}

		$results[] = array(
			'name'   => 'Icons: every icon is well-formed SVG (' . count( $names ) . ' icons)',
			'passed' => count( $names ) >= 10 && $all_valid,
		);

		$results[] = array(
			'name'   => 'Icons: no script, event handler, foreignObject or external reference in any icon',
			'passed' => ! $unsafe,
		);

		$svg = Atlas\Icons::get( 'copy' );

		$results[] = array(
			'name'   => 'Icons: decorative by default (aria-hidden, not focusable), follows text colour, sized before CSS loads',
			'passed' => str_contains( $svg, 'aria-hidden="true"' )
				&& str_contains( $svg, 'focusable="false"' )
				&& str_contains( $svg, 'stroke="currentColor"' )
				&& str_contains( $svg, 'width="20" height="20"' ),
		);

		$results[] = array(
			'name'   => 'Icons: an unknown name returns an empty string (no broken tag) and an extra class is escaped',
			'passed' => '' === Atlas\Icons::get( 'does-not-exist' )
				&& ! str_contains( Atlas\Icons::get( 'check', '"><script>' ), '<script>' ),
		);

		// --- The theme's own markup: icon-only controls must be named. ---
		$header = (string) file_get_contents( ATLAS_THEME_DIR . '/parts/header.html' );
		$notes  = (string) file_get_contents( ATLAS_THEME_DIR . '/parts/notifications.html' );
		$copy   = Atlas\Assets::render_copy_controls(
			'<blockquote class="wp-block-quote"><p>x</p></blockquote>',
			array( 'attrs' => array( 'className' => Atlas\Assets::COPY_MARKER ) )
		);

		foreach ( array(
			'header.html'        => $header,
			'notifications.html' => $notes,
			'copy controls'     => $copy,
		) as $label => $markup ) {
			$bad = self::unnamed_controls( $markup );

			$results[] = array(
				'name'   => 'Icons: every icon-only control in ' . $label . ' has an accessible name' . ( $bad ? ' (unnamed: ' . implode( ', ', $bad ) . ')' : '' ),
				'passed' => array() === $bad,
			);
		}

		// --- Static copies must equal Icons::get() byte for byte. ---
		$needed = array(
			'menu',
			'close',
			'sun',
			'moon',
		);
		$drift  = array();
		foreach ( $needed as $name ) {
			if ( ! str_contains( $header, Atlas\Icons::get( $name ) ) ) {
				$drift[] = 'header:' . $name;
			}
		}
		foreach ( array( 'refresh', 'log-in' ) as $name ) {
			if ( ! str_contains( $notes, Atlas\Icons::get( $name ) ) ) {
				$drift[] = 'notifications:' . $name;
			}
		}
		$harness = (string) file_get_contents( dirname( ATLAS_THEME_DIR ) . '/tests/e2e/fixtures/interactive-harness.html' );
		foreach ( array( 'menu', 'close', 'sun', 'moon', 'copy', 'check' ) as $name ) {
			if ( ! str_contains( $harness, Atlas\Icons::get( $name ) ) ) {
				$drift[] = 'harness:' . $name;
			}
		}

		$results[] = array(
			'name'   => 'Icons: static copies in header.html, notifications.html and the E2E harness equal Icons::get()' . ( $drift ? ' (drifted: ' . implode( ', ', $drift ) . ')' : '' ),
			'passed' => array() === $drift,
		);

		// --- Unicode glyph icons must not come back. ---
		$glyph_hits = array();
		foreach ( array( '&#9776;', '&#10005;', '&#9728;', '&#9790;', '&#10697;', "\u{29C9}" ) as $glyph ) {
			foreach ( array( $header, $notes, $copy, $harness ) as $markup ) {
				if ( str_contains( $markup, $glyph ) ) {
					$glyph_hits[] = $glyph;
				}
			}
		}

		$results[] = array(
			'name'   => 'Icons: no Unicode glyph stand-ins (hamburger, X, sun, moon, clipboard) remain in the markup',
			'passed' => array() === $glyph_hits,
		);

		// --- Labels next to icons stay real text (name = visible text). ---
		$submit = (string) file_get_contents( ATLAS_THEME_DIR . '/patterns/contact-form.php' );

		$results[] = array(
			'name'   => 'Icons: Send message keeps its visible text beside the icon (icon + label, not icon-only)',
			'passed' => str_contains( $submit, "Icons::get( 'send' )" ) && str_contains( $submit, "esc_html__( 'Send message'" ),
		);

		return $results;
	}
}
