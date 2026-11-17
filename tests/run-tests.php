<?php
/**
 * Test runner for Atlas theme tests.
 *
 * Runs all unit test suites and outputs results.
 *
 * @package AtlasTheme
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/test-speculation-rules.php';
require_once __DIR__ . '/test-robots.php';
require_once __DIR__ . '/test-bindings.php';
require_once __DIR__ . '/test-caching.php';
require_once __DIR__ . '/test-maintenance.php';
require_once __DIR__ . '/test-patterns.php';
require_once __DIR__ . '/test-child-theme.php';
require_once __DIR__ . '/test-infinite-scroll.php';
require_once __DIR__ . '/test-llms-txt.php';
require_once __DIR__ . '/test-i18n.php';
require_once __DIR__ . '/test-security-headers.php';
require_once __DIR__ . '/test-contact-form-security.php';
require_once __DIR__ . '/test-contact-settings.php';
require_once __DIR__ . '/test-markdown-endpoint.php';
require_once __DIR__ . '/test-assets.php';
require_once __DIR__ . '/test-submissions.php';
require_once __DIR__ . '/test-colors.php';
require_once __DIR__ . '/test-fixtures.php';
require_once __DIR__ . '/test-icons.php';
require_once __DIR__ . '/test-typography.php';
require_once __DIR__ . '/test-schema-nodes.php';
require_once __DIR__ . '/test-seo-schema.php';

echo "Running Atlas Theme Unit Tests...\n\n";

$suites = array(
	'Speculation Rules'  => Test_Speculation_Rules::run(),
	'Robots & AI Policy' => Test_Robots::run(),
	'Block Bindings'     => Test_Bindings::run(),
	'Contact Form'       => Test_Contact_Form::run(),
	'Fragment Caching'   => Test_Caching::run(),
	'503 Maintenance'    => Test_Maintenance::run(),
	'Niche Patterns'     => Test_Patterns::run(),
	'Zero-PHP Child'     => Test_Child_Theme::run(),
	'Infinite Scroll'    => Test_Infinite_Scroll::run(),
	'GEO & llms.txt'     => Test_Llms_Txt::run(),
	'i18n & Language'    => Test_I18n::run(),
	'Security Headers'   => Test_Security_Headers::run(),
	'Contact Form Security' => Test_Contact_Form_Security::run(),
	'Contact Settings & Channels' => Test_Contact_Settings::run(),
	'Markdown Endpoint'  => Test_Markdown_Endpoint::run(),
	'Assets & CSP Nonce' => Test_Assets::run(),
	'Submissions & Privacy' => Test_Submissions::run(),
	'Colours & Contrast' => Test_Colors::run(),
	'Fixture Drift & Nav' => Test_Fixtures::run(),
	'Icons' => Test_Icons::run(),
	'Typography & Layout' => Test_Typography::run(),
	'Schema Nodes & Cache' => Test_Schema_Nodes::run(),
	// MUST stay last: defines an SEO-plugin constant that disables Seo/Schema for the rest of the process.
	'SEO & Schema'       => Test_Seo_Schema::run(),
);

$total_tests = 0;
$failed_tests = 0;

foreach ( $suites as $suite_name => $tests ) {
	echo "=== Suite: {$suite_name} ===\n";
	foreach ( $tests as $test ) {
		$total_tests++;
		if ( $test['passed'] ) {
			echo "  [PASS] {$test['name']}\n";
		} else {
			$failed_tests++;
			echo "  [FAIL] {$test['name']}\n";
		}
	}
	echo "\n";
}

echo "----------------------------------------\n";
echo "Total Tests: {$total_tests} | Passed: " . ( $total_tests - $failed_tests ) . " | Failed: {$failed_tests}\n";

if ( $failed_tests > 0 ) {
	echo "\nTEST RUN FAILED!\n";
	exit( 1 );
}

echo "\nALL TESTS PASSED!\n";
exit( 0 );
