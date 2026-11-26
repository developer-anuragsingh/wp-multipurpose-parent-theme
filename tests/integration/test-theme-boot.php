<?php
/**
 * The theme boots inside real WordPress: active, versioned, every concern wired.
 *
 * @package AtlasTheme
 */

class Test_Theme_Boot extends WP_UnitTestCase {

	public function test_atlas_theme_is_the_active_theme(): void {
		$this->assertSame( 'atlas-theme', get_stylesheet() );
		$this->assertSame( 'atlas-theme', get_template() );
		$this->assertTrue( wp_get_theme()->exists() );
		$this->assertFalse( wp_get_theme()->errors(), 'WordPress reports the theme as broken.' );
		$this->assertSame( 'Atlas Multipurpose', wp_get_theme()->get( 'Name' ) );
	}

	public function test_the_suite_tests_this_checkout(): void {
		$this->assertSame( realpath( dirname( __DIR__, 2 ) . '/theme' ), realpath( get_stylesheet_directory() ) );
	}

	public function test_version_constant_matches_style_css(): void {
		$this->assertTrue( defined( 'ATLAS_THEME_VERSION' ) );
		$this->assertSame( wp_get_theme()->get( 'Version' ), ATLAS_THEME_VERSION );
	}

	public function test_it_is_a_block_theme(): void {
		$this->assertTrue( wp_is_block_theme() );
	}

	/**
	 * Every concern registers its hooks in real WordPress (not just in doubles).
	 */
	public function test_every_concern_is_wired(): void {
		$expected = array(
			array( 'wp_headers', array( 'Atlas\Security_Headers', 'filter_headers' ) ),
			array( 'rest_api_init', array( 'Atlas\Contact_Form', 'register_rest_route' ) ),
			array( 'atlas_contact_form_submitted', array( 'Atlas\Submissions', 'store' ) ),
			array( 'wp_privacy_personal_data_exporters', array( 'Atlas\Submissions', 'register_exporter' ) ),
			array( 'wp_privacy_personal_data_erasers', array( 'Atlas\Submissions', 'register_eraser' ) ),
			array( 'wp_head', array( 'Atlas\Schema', 'print_json_ld' ) ),
			array( 'wp_head', array( 'Atlas\Seo', 'print_meta' ) ),
			array( 'update_option_blogname', array( 'Atlas\Schema', 'flush_cache' ) ),
			array( 'template_redirect', array( 'Atlas\Maintenance', 'maybe_intercept' ) ),
			array( 'template_redirect', array( 'Atlas\Markdown_Endpoint', 'maybe_render_markdown' ) ),
		);

		foreach ( $expected as $pair ) {
			$this->assertNotFalse(
				has_filter( $pair[0], $pair[1] ),
				sprintf( '%s::%s is not hooked to %s', $pair[1][0], $pair[1][1], $pair[0] )
			);
		}
	}

	/**
	 * Core's emoji loader is removed on the front end (it was a 1.2s long task).
	 */
	public function test_emoji_loader_is_removed(): void {
		$this->assertFalse( has_action( 'wp_head', 'print_emoji_detection_script' ) );
		$this->assertStringNotContainsString( 'wp-emoji', get_echo( 'wp_head' ) );
	}
}
