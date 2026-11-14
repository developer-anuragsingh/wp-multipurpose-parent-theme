<?php
/**
 * Colour-system guards: every colour preset the markup references must exist in
 * theme.json, and the text/surface pairs the theme relies on must meet WCAG AA
 * in BOTH light and dark mode.
 *
 * Why this exists: a pattern referencing a missing preset silently renders with
 * no colour (the CSS variable is simply undefined), which once made an
 * accessibility scan pass for the wrong reason. These checks run in the fast PHP
 * suite so they cannot be skipped like a browser test.
 *
 * @package AtlasTheme
 */

class Test_Colors {

	/**
	 * WCAG relative luminance of a #rrggbb colour.
	 *
	 * @param string $hex Colour.
	 * @return float Luminance 0..1.
	 */
	private static function luminance( string $hex ): float {
		$hex = ltrim( $hex, '#' );
		$ch  = array_map(
			static function ( $pair ) {
				$c = hexdec( $pair ) / 255;
				return $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
			},
			str_split( $hex, 2 )
		);
		return 0.2126 * $ch[0] + 0.7152 * $ch[1] + 0.0722 * $ch[2];
	}

	private static function ratio( string $a, string $b ): float {
		$la = self::luminance( $a );
		$lb = self::luminance( $b );
		return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
	}

	public static function run(): array {
		$results = array();

		$theme_json = json_decode( (string) file_get_contents( ATLAS_THEME_DIR . '/theme.json' ), true );
		$light      = array();
		foreach ( $theme_json['settings']['color']['palette'] as $color ) {
			$light[ $color['slug'] ] = strtolower( $color['color'] );
		}

		// Dark-mode overrides come straight from interactivity.css (single source of truth).
		$css  = (string) file_get_contents( ATLAS_THEME_DIR . '/src/css/interactivity.css' );
		$dark = $light;
		if ( preg_match( '/:root\[data-theme="dark"\]\s*\{(.*?)\}/s', $css, $block ) ) {
			preg_match_all( '/--wp--preset--color--([a-z0-9-]+):\s*(#[0-9a-fA-F]{6})/', $block[1], $m, PREG_SET_ORDER );
			foreach ( $m as $override ) {
				$dark[ $override[1] ] = strtolower( $override[2] );
			}
		}

		// --- Every referenced preset exists. ---
		$core_classes = array( 'text', 'link', 'border', 'background' ); // has-text-color etc. are core, not presets.
		$referenced   = array();
		foreach ( array( 'patterns', 'parts', 'templates' ) as $dir ) {
			foreach ( glob( ATLAS_THEME_DIR . '/' . $dir . '/*' ) ?: array() as $file ) {
				$text = (string) file_get_contents( $file );
				preg_match_all( '/has-([a-z0-9-]+?)-(?:background-color|border-color|color)\b/', $text, $a );
				preg_match_all( '/"(?:backgroundColor|textColor|borderColor|gradient)":"([a-z0-9-]+)"/', $text, $b );
				preg_match_all( '/var:preset\|color\|([a-z0-9-]+)/', $text, $c );
				preg_match_all( '/--wp--preset--color--([a-z0-9-]+)/', $text, $d );
				foreach ( array_merge( $a[1], $b[1], $c[1], $d[1] ) as $slug ) {
					if ( ! in_array( $slug, $core_classes, true ) ) {
						$referenced[ $slug ][] = basename( $file );
					}
				}
			}
		}
		$missing = array_diff_key( $referenced, $light );

		$results[] = array(
			'name'   => 'Colours: every colour preset used in patterns/parts/templates is defined in theme.json'
				. ( $missing ? ' (missing: ' . implode( ', ', array_keys( $missing ) ) . ')' : '' ),
			'passed' => array() === $missing,
		);

		// --- Contrast pairs the design depends on: text slug on background slug, min ratio. ---
		$pairs = array(
			array( 'dark', 'light', 4.5, 'body text' ),
			array( 'dark', 'surface', 4.5, 'text on cards (quotes, testimonial, impact)' ),
			array( 'accent-text', 'light', 3.0, 'large metric numbers on the page surface' ),
			array( 'accent-text', 'surface', 3.0, 'large metric numbers on cards' ),
			array( 'white', 'primary', 4.5, 'button label on primary' ),
			array( 'link', 'light', 4.5, 'link text on the page surface' ),
			array( 'link', 'surface', 4.5, 'link text on cards' ),
		);

		foreach ( array(
			'light' => $light,
			'dark'  => $dark,
		) as $mode => $palette ) {
			foreach ( $pairs as $pair ) {
				list( $fg, $bg, $min, $what ) = $pair;
				$r                            = self::ratio( $palette[ $fg ] ?? '#000000', $palette[ $bg ] ?? '#000000' );

				$results[] = array(
					'name'   => sprintf( 'Contrast (%s mode): %s on %s >= %.1f:1 for %s (is %.2f)', $mode, $fg, $bg, $min, $what, $r ),
					'passed' => isset( $palette[ $fg ], $palette[ $bg ] ) && $r >= $min,
				);
			}
		}

		$results[] = array(
			'name'   => 'Colours: links are coloured by the dark-mode aware "link" preset (not raw primary, not a per-selector override)',
			'passed' => 'var:preset|color|link' === ( $theme_json['styles']['elements']['link']['color']['text'] ?? '' )
				&& ! str_contains( $css, 'a:where(:not(.wp-element-button))' ),
		);

		$results[] = array(
			'name'   => 'Colours: the brand accent is NOT used as text on the light surface (it is only ' . sprintf( '%.2f', self::ratio( $light['accent'], $light['light'] ) ) . ':1)',
			'passed' => self::ratio( $light['accent'], $light['light'] ) < 3.0
				&& false === strpos( (string) file_get_contents( ATLAS_THEME_DIR . '/patterns/impact-metrics.php' ), 'has-accent-color' ),
		);

		return $results;
	}
}
