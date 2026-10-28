<?php
/**
 * Unit tests for Atlas\Robots.
 *
 * @package AtlasTheme
 */

class Test_Robots {

	public static function run(): array {
		$results = array();

		// Test 1: Non-public site leaves robots.txt untouched.
		$initial_text = "User-agent: *\nDisallow: /\n";
		$output       = Atlas\Robots::filter_robots_txt( $initial_text, false );
		$results[]    = array(
			'name'   => 'Robots leaves non-public site untouched',
			'passed' => $output === $initial_text,
		);

		// Test 2: Public site appends AI crawlers.
		$public_output = Atlas\Robots::filter_robots_txt( $initial_text, true );
		$has_gptbot    = str_contains( $public_output, 'User-agent: GPTBot' ) && str_contains( $public_output, 'Allow: /' );
		$has_claude    = str_contains( $public_output, 'User-agent: ClaudeBot' ) && str_contains( $public_output, 'Allow: /' );
		$has_ajax      = str_contains( $public_output, 'Allow: /wp-admin/admin-ajax.php' );

		$results[] = array(
			'name'   => 'Robots appends default AI crawler allow lines and admin-ajax',
			'passed' => $has_gptbot && $has_claude && $has_ajax,
		);

		return $results;
	}
}
