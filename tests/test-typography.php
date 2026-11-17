<?php
/**
 * Typography and spacing guards for theme.json.
 *
 * Background: WordPress ignores a theme's font-size (and spacing-size) presets
 * that reuse a core slug (small, medium, large, x-large; 20-80) unless the
 * matching "default*" switch is false. The theme's whole font scale was being
 * silently replaced by core's (13/20/36/42px), which made body text 20px on
 * desktop. These checks make that class of mistake fail in the fast suite.
 *
 * @package AtlasTheme
 */

class Test_Typography {

	/**
	 * Convert a CSS length in rem/px to pixels (1rem = 16px).
	 *
	 * @param string $len Length such as "0.875rem" or "14px".
	 * @return float Pixels.
	 */
	private static function px( string $len ): float {
		return str_ends_with( $len, 'rem' ) ? (float) $len * 16 : (float) $len;
	}

	public static function run(): array {
		$results = array();
		$json    = json_decode( (string) file_get_contents( ATLAS_THEME_DIR . '/theme.json' ), true );
		$typo    = $json['settings']['typography'];
		$sizes   = array();
		foreach ( $typo['fontSizes'] as $f ) {
			$sizes[ $f['slug'] ] = $f;
		}

		$results[] = array(
			'name'   => 'Typography: defaultFontSizes is false, otherwise core silently replaces the theme\'s own font scale',
			'passed' => isset( $typo['defaultFontSizes'] ) && false === $typo['defaultFontSizes'],
		);

		$core_slugs = array( 'small', 'medium', 'large', 'x-large' );
		$results[]  = array(
			'name'   => 'Typography: the scale defines every core slug it relies on (small, medium, large, x-large, xx-large)',
			'passed' => array() === array_diff( array_merge( $core_slugs, array( 'xx-large' ) ), array_keys( $sizes ) ),
		);

		$order = array_map(
			static function ( $slug ) use ( $sizes ) {
				return self::px( $sizes[ $slug ]['size'] );
			},
			array( 'small', 'medium', 'large', 'x-large', 'xx-large' )
		);
		$sorted = $order;
		sort( $sorted );

		$results[] = array(
			'name'   => 'Typography: the scale is strictly increasing (' . implode( ' < ', array_map( 'strval', $order ) ) . ' px)',
			'passed' => $order === $sorted && count( array_unique( $order ) ) === count( $order ),
		);

		$body_min = self::px( $sizes['medium']['fluid']['min'] );
		$body_max = self::px( $sizes['medium']['fluid']['max'] );

		$results[] = array(
			'name'   => sprintf( 'Typography: body text (medium) is compact but readable: %g-%g px, never below 14px', $body_min, $body_max ),
			'passed' => $body_min >= 14 && $body_max <= 16,
		);

		$results[] = array(
			'name'   => 'Typography: the smallest preset is not below 12px (captions, labels)',
			'passed' => self::px( $sizes['small']['fluid']['min'] ) >= 12,
		);

		$results[] = array(
			'name'   => 'Typography: the largest heading (xx-large, h1) tops out at 36px or less',
			'passed' => self::px( $sizes['xx-large']['fluid']['max'] ) <= 36,
		);

		$fluid_ok = true;
		foreach ( $sizes as $f ) {
			$fluid_ok = $fluid_ok && self::px( $f['fluid']['min'] ) <= self::px( $f['size'] ) && self::px( $f['size'] ) <= self::px( $f['fluid']['max'] );
		}

		$results[] = array(
			'name'   => 'Typography: every preset\'s base size sits between its fluid min and max',
			'passed' => $fluid_ok,
		);

		$results[] = array(
			'name'   => 'Typography: the body font size is the medium preset (one place to tune the whole app)',
			'passed' => 'var:preset|font-size|medium' === $json['styles']['typography']['fontSize'],
		);

		// --- Side padding. ---
		$pad = $json['styles']['spacing']['padding'] ?? array();

		$results[] = array(
			'name'   => 'Layout: root left/right padding is set (content never touches the screen edge)',
			'passed' => ! empty( $pad['left'] ) && ! empty( $pad['right'] ) && $pad['left'] === $pad['right'],
		);

		$results[] = array(
			'name'   => 'Layout: root padding is at least 1rem on small screens and capped at 2rem',
			'passed' => 1 === preg_match( '/^clamp\(\s*1rem\s*,[^,]+,\s*2rem\s*\)$/', (string) ( $pad['left'] ?? '' ) ),
		);

		$results[] = array(
			'name'   => 'Layout: useRootPaddingAwareAlignments stays on so full-width sections still reach the edges',
			'passed' => true === $json['settings']['useRootPaddingAwareAlignments'],
		);

		// Header and footers take their side padding from the same root value, so
		// the title/copyright line up with the page content at every width.
		foreach ( array( 'header', 'footer', 'footer-landing' ) as $part ) {
			$html = (string) file_get_contents( ATLAS_THEME_DIR . '/parts/' . $part . '.html' );

			$results[] = array(
				'name'   => 'Layout: ' . $part . '.html uses the root side padding (not a fixed preset), so it aligns with the page content',
				'passed' => str_contains( $html, '"left":"var(--wp--style--root--padding-left)"' )
					&& str_contains( $html, '"right":"var(--wp--style--root--padding-right)"' )
					&& str_contains( $html, 'padding-left:var(--wp--style--root--padding-left)' )
					&& str_contains( $html, 'padding-right:var(--wp--style--root--padding-right)' ),
			);
		}

		// --- Form fields: iOS Safari zooms the page on focus under 16px. ---
		$css = (string) file_get_contents( ATLAS_THEME_DIR . '/src/css/contact-form.css' );
		preg_match( '/\.atlas-contact-form__input,\s*\.atlas-contact-form__textarea\s*\{(.*?)\}/s', $css, $block );

		$results[] = array(
			'name'   => 'Forms: contact fields are at least 16px so iOS Safari does not zoom the page on focus',
			'passed' => isset( $block[1] ) && 1 === preg_match( '/font-size:\s*1rem\s*;/', $block[1] ),
		);

		return $results;
	}
}
