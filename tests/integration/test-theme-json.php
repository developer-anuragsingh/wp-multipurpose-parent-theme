<?php
/**
 * theme.json is applied by real WordPress the way the theme intends.
 *
 * These are exactly the bugs the unit doubles could not see: core silently
 * replacing the font scale, and no root padding being emitted.
 *
 * @package AtlasTheme
 */

class Test_Theme_Json extends WP_UnitTestCase {

	public function test_the_theme_font_scale_is_used_not_cores(): void {
		$sizes = wp_get_global_settings( array( 'typography', 'fontSizes' ) );

		// Core keeps its defaults in the settings tree even with
		// defaultFontSizes:false; what matters is that the THEME origin holds
		// every core slug at the theme's own size (with defaultFontSizes left on,
		// core drops the colliding theme slugs and only xx-large survived).
		$by_slug = wp_list_pluck( $sizes['theme'] ?? array(), 'size', 'slug' );
		foreach (
			array(
				'small'    => '0.75rem',
				'medium'   => '0.875rem',
				'large'    => '1rem',
				'x-large'  => '1.25rem',
				'xx-large' => '1.625rem',
			) as $slug => $size
		) {
			$this->assertSame( $size, $by_slug[ $slug ] ?? null, "Theme preset {$slug} is not applied." );
		}
	}

	public function test_core_default_sizes_are_not_emitted_as_css(): void {
		$css = wp_get_global_stylesheet( array( 'variables' ) );

		foreach ( array( 'small: 13px', 'medium: clamp(14px', 'large: clamp(22.041px', 'x-large: clamp(25.014px' ) as $core_value ) {
			$this->assertStringNotContainsString( '--wp--preset--font-size--' . $core_value, $css, "Core default font size {$core_value} is still emitted." );
		}
	}

	public function test_body_uses_the_theme_medium_preset(): void {
		$css = wp_get_global_stylesheet();

		$this->assertMatchesRegularExpression( '/--wp--preset--font-size--medium:\s*clamp\(0\.875rem/', $css );
		$this->assertStringNotContainsString( '--wp--preset--font-size--medium: clamp(14px', $css, 'Core 20px medium is still emitted.' );
	}

	public function test_root_side_padding_is_emitted(): void {
		$css = wp_get_global_stylesheet();

		$this->assertStringContainsString( '--wp--style--root--padding-left: clamp(1rem, 4vw, 2rem)', $css );
		$this->assertStringContainsString( '--wp--style--root--padding-right: clamp(1rem, 4vw, 2rem)', $css );
	}

	public function test_role_based_colour_presets_exist(): void {
		$palette = wp_list_pluck( wp_get_global_settings( array( 'color', 'palette', 'theme' ) ), 'color', 'slug' );

		foreach ( array( 'surface', 'accent-text', 'link' ) as $slug ) {
			$this->assertArrayHasKey( $slug, $palette, "Colour preset {$slug} is missing." );
		}
		$this->assertStringContainsString( 'var(--wp--preset--color--link)', wp_get_global_stylesheet(), 'Links are not coloured by the link preset.' );
	}
}
