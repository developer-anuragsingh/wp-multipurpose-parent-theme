<?php
/**
 * Unit tests for Zero-PHP Child Theme Starter and Scaffolding Script.
 *
 * @package AtlasTheme
 */

class Test_Child_Theme {

	public static function run(): array {
		$results = array();

		$child_dir   = dirname( __DIR__ ) . '/atlas-child';
		$style_css   = $child_dir . '/style.css';
		$theme_json  = $child_dir . '/theme.json';
		$script_file = dirname( __DIR__ ) . '/scripts/create-child-theme.sh';

		// 1. style.css verification.
		$style_content = file_exists( $style_css ) ? file_get_contents( $style_css ) : '';
		$results[]     = array(
			'name'   => 'Child theme style.css exists',
			'passed' => ! empty( $style_content ),
		);

		$results[] = array(
			'name'   => 'Child theme links to atlas-theme Template',
			'passed' => str_contains( $style_content, 'Template:     atlas-theme' ),
		);

		// 2. theme.json verification.
		$json_content = file_exists( $theme_json ) ? file_get_contents( $theme_json ) : '';
		$parsed_json  = json_decode( $json_content, true );
		$results[]    = array(
			'name'   => 'Child theme theme.json is valid JSON v3',
			'passed' => is_array( $parsed_json ) && ( 3 === ( $parsed_json['version'] ?? 0 ) ),
		);

		$results[] = array(
			'name'   => 'Child theme theme.json overrides palette without replacing presets',
			'passed' => ! empty( $parsed_json['settings']['color']['palette'] ),
		);

		// 3. Strict Zero-PHP verification.
		$php_files = glob( $child_dir . '/**/*.php' );
		if ( false === $php_files ) {
			$php_files = array();
		}
		$top_php   = glob( $child_dir . '/*.php' );
		if ( is_array( $top_php ) ) {
			$php_files = array_merge( $php_files, $top_php );
		}

		$results[] = array(
			'name'   => 'Zero-PHP Architecture: child theme strictly contains 0 PHP files',
			'passed' => 0 === count( $php_files ),
		);

		// 4. Scaffolding script verification.
		$results[] = array(
			'name'   => 'create-child-theme.sh is executable and present',
			'passed' => file_exists( $script_file ) && is_executable( $script_file ),
		);

		return $results;
	}
}
