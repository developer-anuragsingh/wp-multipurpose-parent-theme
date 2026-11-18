<?php
/**
 * Unit tests for Atlas block patterns and Assets copy controls.
 *
 * @package AtlasTheme
 */

class Test_Patterns {

	public static function run(): array {
		$results = array();

		// Quotes Masonry pattern tests.
		$quotes_file    = ATLAS_THEME_DIR . '/patterns/quotes-masonry.php';
		$quotes_content = file_exists( $quotes_file ) ? file_get_contents( $quotes_file ) : '';

		$results[] = array(
			'name'   => 'Quotes Masonry pattern file exists',
			'passed' => ! empty( $quotes_content ),
		);

		$results[] = array(
			'name'   => 'Quotes Masonry defines slug atlas/quotes-masonry',
			'passed' => str_contains( $quotes_content, 'Slug: atlas/quotes-masonry' ),
		);

		$results[] = array(
			'name'   => 'Quotes Masonry includes CSS masonry grid and copyable quotes',
			'passed' => str_contains( $quotes_content, 'atlas-masonry-grid' ) && str_contains( $quotes_content, 'is-style-atlas-copyable' ),
		);

		// Impact Metrics pattern tests.
		$impact_file    = ATLAS_THEME_DIR . '/patterns/impact-metrics.php';
		$impact_content = file_exists( $impact_file ) ? file_get_contents( $impact_file ) : '';

		$results[] = array(
			'name'   => 'Impact Metrics pattern file exists',
			'passed' => ! empty( $impact_content ),
		);

		$results[] = array(
			'name'   => 'Impact Metrics defines slug atlas/impact-metrics',
			'passed' => str_contains( $impact_content, 'Slug: atlas/impact-metrics' ),
		);

		$results[] = array(
			'name'   => 'Impact Metrics includes accessible ARIA region and statistics',
			'passed' => str_contains( $impact_content, 'role="region"' ) && str_contains( $impact_content, 'Organizational Impact Metrics' ),
		);

		// Editorial ToC pattern tests.
		$toc_file    = ATLAS_THEME_DIR . '/patterns/editorial-toc.php';
		$toc_content = file_exists( $toc_file ) ? file_get_contents( $toc_file ) : '';

		$results[] = array(
			'name'   => 'Editorial ToC pattern file exists',
			'passed' => ! empty( $toc_content ),
		);

		$results[] = array(
			'name'   => 'Editorial ToC defines slug atlas/editorial-toc',
			'passed' => str_contains( $toc_content, 'Slug: atlas/editorial-toc' ),
		);

		$results[] = array(
			'name'   => 'Editorial ToC includes reading metadata and anchor navigation',
			'passed' => str_contains( $toc_content, 'role="complementary"' ) && str_contains( $toc_content, 'Table of Contents' ),
		);

		// Services Bento pattern tests.
		$bento_file    = ATLAS_THEME_DIR . '/patterns/services-bento.php';
		$bento_content = file_exists( $bento_file ) ? (string) file_get_contents( $bento_file ) : '';
		$results[]     = array(
			'name'   => 'Services Bento Grid pattern file exists and defines slug',
			'passed' => ! empty( $bento_content ) && str_contains( $bento_content, 'Slug: atlas/services-bento' ),
		);

		// Team Grid pattern tests.
		$team_file    = ATLAS_THEME_DIR . '/patterns/team-grid.php';
		$team_content = file_exists( $team_file ) ? (string) file_get_contents( $team_file ) : '';
		$results[]    = array(
			'name'   => 'Team Grid pattern file exists and defines slug',
			'passed' => ! empty( $team_content ) && str_contains( $team_content, 'Slug: atlas/team-grid' ),
		);

		// Logo Clouds pattern tests.
		$logos_file    = ATLAS_THEME_DIR . '/patterns/logo-clouds.php';
		$logos_content = file_exists( $logos_file ) ? (string) file_get_contents( $logos_file ) : '';
		$results[]     = array(
			'name'   => 'Logo Clouds pattern file exists and defines slug',
			'passed' => ! empty( $logos_content ) && str_contains( $logos_content, 'Slug: atlas/logo-clouds' ),
		);

		// Process Steps pattern tests.
		$process_file    = ATLAS_THEME_DIR . '/patterns/process-steps.php';
		$process_content = file_exists( $process_file ) ? (string) file_get_contents( $process_file ) : '';
		$results[]       = array(
			'name'   => 'Process Steps pattern file exists and defines slug',
			'passed' => ! empty( $process_content ) && str_contains( $process_content, 'Slug: atlas/process-steps' ),
		);

		// CTA Banner pattern tests.
		$cta_file    = ATLAS_THEME_DIR . '/patterns/cta-banner.php';
		$cta_content = file_exists( $cta_file ) ? (string) file_get_contents( $cta_file ) : '';
		$results[]   = array(
			'name'   => 'CTA Banner pattern file exists and defines slug',
			'passed' => ! empty( $cta_content ) && str_contains( $cta_content, 'Slug: atlas/cta-banner' ),
		);

		// Copy controls rendering tests.
		$copyable_block = array(
			'blockName' => 'core/quote',
			'attrs'     => array( 'className' => 'is-style-atlas-copyable' ),
		);
		$raw_html       = '<blockquote class="wp-block-quote is-style-atlas-copyable"><p>Simple is better than complex.</p></blockquote>';
		$rendered_html  = Atlas\Assets::render_copy_controls( $raw_html, $copyable_block );

		$results[] = array(
			'name'   => 'Assets render_copy_controls injects atlas/copy interactivity wrapper',
			'passed' => str_contains( $rendered_html, 'data-wp-interactive="atlas/copy"' )
				&& str_contains( $rendered_html, 'class="atlas-copy__button"' )
				&& str_contains( $rendered_html, 'data-wp-on--click="actions.copy"' ),
		);

		$normal_block  = array(
			'blockName' => 'core/quote',
			'attrs'     => array( 'className' => 'is-style-plain' ),
		);
		$plain_html    = '<blockquote class="wp-block-quote is-style-plain"><p>Standard quote.</p></blockquote>';
		$passthru_html = Atlas\Assets::render_copy_controls( $plain_html, $normal_block );

		$results[] = array(
			'name'   => 'Assets render_copy_controls passes through non-copyable quotes untouched',
			'passed' => $passthru_html === $plain_html,
		);

		return $results;
	}
}
