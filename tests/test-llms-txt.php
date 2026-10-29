<?php
/**
 * Unit tests for Atlas\Llms_Txt GEO catalogs (/llms.txt and /llms-full.txt).
 *
 * @package AtlasTheme
 */

class Test_Llms_Txt {

	/**
	 * Run the Llms_Txt test suite.
	 *
	 * @return array[] Test results.
	 */
	public static function run(): array {
		$results = array();

		// Test 1: Verify path and filter constants.
		$results[] = array(
			'name'   => 'Llms_Txt defines REQUEST_PATH and REQUEST_PATH_FULL constants',
			'passed' => '/llms.txt' === Atlas\Llms_Txt::REQUEST_PATH && '/llms-full.txt' === Atlas\Llms_Txt::REQUEST_PATH_FULL,
		);

		$results[] = array(
			'name'   => 'Llms_Txt defines filter and limit constants',
			'passed' => 'atlas_llms_txt_enabled' === Atlas\Llms_Txt::FILTER_ENABLED &&
						'atlas_llms_txt_entries' === Atlas\Llms_Txt::FILTER_ENTRIES &&
						'atlas_llms_txt_full_entries' === Atlas\Llms_Txt::FILTER_FULL_ENTRIES &&
						'atlas_llms_txt_full_max_pages' === Atlas\Llms_Txt::FILTER_FULL_MAX_PAGES &&
						50 === Atlas\Llms_Txt::MAX_ENTRIES &&
						10 === Atlas\Llms_Txt::MAX_FULL_PAGES,
		);

		// Setup mock posts in environment.
		global $atlas_test_posts, $atlas_test_bloginfo;
		$atlas_test_bloginfo = array(
			'name'        => 'Atlas Parent Theme',
			'description' => 'Fast, accessible FSE parent theme.',
		);

		$atlas_test_posts = array(
			10 => array(
				'ID'            => 10,
				'post_title'    => 'About Us',
				'post_name'     => 'about',
				'post_status'   => 'publish',
				'post_type'     => 'page',
				'post_excerpt'  => 'Learn more about our mission and team.',
				'post_content'  => '<!-- wp:paragraph --><p>We build accessible themes.</p><!-- /wp:paragraph -->',
				'post_password' => '',
			),
			20 => array(
				'ID'            => 20,
				'post_title'    => 'Services',
				'post_name'     => 'services',
				'post_status'   => 'publish',
				'post_type'     => 'page',
				'post_excerpt'  => 'Comprehensive web development solutions.',
				'post_content'  => '<!-- wp:heading {"level":2} --><h2>Core Capabilities</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Full-Site Editing and performance optimization.</p><!-- /wp:paragraph -->',
				'post_password' => '',
			),
			30 => array(
				'ID'            => 30,
				'post_title'    => 'Draft Page',
				'post_name'     => 'draft-page',
				'post_status'   => 'draft',
				'post_type'     => 'page',
				'post_excerpt'  => 'Unpublished content.',
				'post_content'  => '<!-- wp:paragraph --><p>Private draft.</p><!-- /wp:paragraph -->',
				'post_password' => '',
			),
			40 => array(
				'ID'            => 40,
				'post_title'    => 'Secret Page',
				'post_name'     => 'secret-page',
				'post_status'   => 'publish',
				'post_type'     => 'page',
				'post_excerpt'  => 'Protected content.',
				'post_content'  => '<!-- wp:paragraph --><p>Confidential.</p><!-- /wp:paragraph -->',
				'post_password' => 'supersecret',
			),
		);

		// Test 3: Concise /llms.txt output structure.
		$concise = Atlas\Llms_Txt::build_catalog();

		$has_title_and_desc = false !== strpos( $concise, '# Atlas Parent Theme' ) &&
							false !== strpos( $concise, '> Fast, accessible FSE parent theme.' );
		$has_pages_heading  = false !== strpos( $concise, '## Pages' );
		$has_about_entry    = false !== strpos( $concise, '- [About Us](https://example.com/about/): Learn more about our mission and team.' );
		$has_services_entry = false !== strpos( $concise, '- [Services](https://example.com/services/): Comprehensive web development solutions.' );
		$excludes_draft     = false === strpos( $concise, 'Draft Page' );
		$excludes_secret    = false === strpos( $concise, 'Secret Page' );

		$results[] = array(
			'name'   => 'Llms_Txt build_catalog formats concise digest and excludes non-public posts',
			'passed' => $has_title_and_desc && $has_pages_heading && $has_about_entry && $has_services_entry && $excludes_draft && $excludes_secret,
		);

		// Test 4: Expanded /llms-full.txt output structure.
		$full = Atlas\Llms_Txt::build_full_catalog();

		$full_has_site_header = false !== strpos( $full, '# Atlas Parent Theme' ) &&
								false !== strpos( $full, '> Fast, accessible FSE parent theme.' ) &&
								false !== strpos( $full, 'This document provides the full text of core public pages' );

		$full_has_separators  = false !== strpos( $full, '---' );
		$full_has_about_body  = false !== strpos( $full, '# About Us' ) && false !== strpos( $full, 'We build accessible themes.' );
		$full_has_services    = false !== strpos( $full, '# Services' ) && false !== strpos( $full, '## Core Capabilities' );
		$full_excludes_draft  = false === strpos( $full, 'Draft Page' );
		$full_excludes_secret = false === strpos( $full, 'Secret Page' );

		$results[] = array(
			'name'   => 'Llms_Txt build_full_catalog converts core pages to Markdown with separators',
			'passed' => $full_has_site_header && $full_has_separators && $full_has_about_body && $full_has_services && $full_excludes_draft && $full_excludes_secret,
		);

		// Test 5: Filter atlas_llms_txt_full_max_pages controls page cap.
		add_filter(
			'atlas_llms_txt_full_max_pages',
			function () {
				return 1;
			}
		);

		$capped_full = Atlas\Llms_Txt::build_full_catalog();
		$capped_pass = false !== strpos( $capped_full, '# About Us' ) && false === strpos( $capped_full, '# Services' );

		$results[] = array(
			'name'   => 'Llms_Txt respects atlas_llms_txt_full_max_pages filter',
			'passed' => $capped_pass,
		);

		return $results;
	}
}
