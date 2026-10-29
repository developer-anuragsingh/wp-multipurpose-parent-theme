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
