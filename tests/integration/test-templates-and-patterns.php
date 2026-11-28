<?php
/**
 * Templates, parts, patterns and the Markdown/maintenance renderers, through
 * real core parsing and block-template resolution.
 *
 * @package AtlasTheme
 */

class Test_Templates_And_Patterns extends WP_UnitTestCase {

	public function test_every_template_and_part_resolves_as_a_block_template(): void {
		foreach ( glob( ATLAS_THEME_DIR . '/templates/*.html' ) as $file ) {
			$slug     = basename( $file, '.html' );
			$template = get_block_template( 'atlas-theme//' . $slug, 'wp_template' );
			$this->assertNotNull( $template, "Template {$slug} does not resolve." );
			$this->assertNotEmpty( parse_blocks( $template->content ), "Template {$slug} has no blocks." );
		}

		foreach ( array( 'header', 'footer', 'footer-landing', 'notifications' ) as $slug ) {
			$this->assertNotNull( get_block_template( 'atlas-theme//' . $slug, 'wp_template_part' ), "Part {$slug} does not resolve." );
		}
	}

	public function test_the_theme_patterns_are_registered_and_render(): void {
		$registry = WP_Block_Patterns_Registry::get_instance();

		foreach ( array( 'atlas/contact-form', 'atlas/quotes-masonry', 'atlas/impact-metrics' ) as $name ) {
			$this->assertTrue( $registry->is_registered( $name ), "Pattern {$name} is not registered." );
			$html = do_blocks( $registry->get_registered( $name )['content'] );
			$this->assertNotSame( '', trim( $html ), "Pattern {$name} renders empty." );
		}
	}

	public function test_the_contact_page_map_is_click_to_load(): void {
		$html = do_blocks( '<!-- wp:pattern {"slug":"atlas/contact-page"} /-->' );
		$this->assertStringContainsString( 'data-wp-interactive="atlas/map"', $html, 'The map section is missing (it is on by default).' );
		$this->assertStringNotContainsString( '<iframe', $html, 'A Google iframe in the server HTML loads before consent.' );
		$this->assertMatchesRegularExpression( '#<a class="atlas-map__link" href="https://www\.google\.com/maps/search/\?api=1&\#038;query=#', $html );
		// The registry caches pattern content per request, so check the switch
		// through the same calls the pattern makes.
		update_option( \Atlas\Contact_Settings::OPT_SHOW_MAP, '0' );
		$off = \Atlas\Contact_Settings::render_map( \Atlas\Contact_Settings::get_map_embed_url() );
		delete_option( \Atlas\Contact_Settings::OPT_SHOW_MAP );
		$this->assertSame( '', $off, 'The map renders although its switch is off.' );
	}
	public function test_the_contact_pattern_ships_a_valid_form_token_and_no_nonce(): void {
		$html = WP_Block_Patterns_Registry::get_instance()->get_registered( 'atlas/contact-form' )['content'];

		$this->assertSame( 1, preg_match( '/name="atlas_contact_ts"[^>]*value="([^"]+)"/', $html, $m ) );
		$this->assertNotSame( 'invalid', Atlas\Contact_Form::verify_token( $m[1], time() + 10 ) );
		$this->assertStringNotContainsString( '_wpnonce', $html );
		$this->assertStringContainsString( 'atlas-icon--send', $html );
	}

	public function test_maintenance_serves_the_real_block_template_through_the_canvas(): void {
		$this->assertSame( ABSPATH . WPINC . '/template-canvas.php', Atlas\Maintenance::override_template( '/original.php' ) );

		global $_wp_current_template_content;
		$this->assertStringContainsString( '<!-- wp:', $_wp_current_template_content );
	}

	public function test_markdown_uses_real_core_url_and_tag_sanitising(): void {
		$content = '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Sub &amp; Title</h3><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p><a href="javascript:alert(1)">bad</a> and <a href="https://example.com/a?b=1&amp;c=2">good</a></p><!-- /wp:paragraph -->';
		$md = Atlas\Markdown_Endpoint::blocks_to_markdown( $content, 'T' );

		$this->assertStringContainsString( '### Sub & Title', $md );
		$this->assertStringContainsString( '[good](https://example.com/a?b=1&c=2)', $md );
		$this->assertStringNotContainsString( 'javascript:', $md );
	}
}
