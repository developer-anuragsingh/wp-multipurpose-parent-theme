<?php
/**
 * Unit tests for Atlas\Speculation_Rules.
 *
 * @package AtlasTheme
 */

class Test_Speculation_Rules {

	public static function run(): array {
		$results = array();

		// Test 1: Configuration tuning.
		$input  = array(
			'mode'      => 'conservative',
			'eagerness' => 'conservative',
		);
		$output = Atlas\Speculation_Rules::filter_configuration( $input );

		$results[] = array(
			'name'   => 'Speculation_Rules sets prefetch mode',
			'passed' => isset( $output['mode'] ) && 'prefetch' === $output['mode'],
		);

		$results[] = array(
			'name'   => 'Speculation_Rules sets moderate eagerness',
			'passed' => isset( $output['eagerness'] ) && 'moderate' === $output['eagerness'],
		);

		$results[] = array(
			'name'   => 'Speculation_Rules excludes commerce routes',
			'passed' => isset( $output['href_exclude_paths'] )
				&& in_array( '/cart/*', $output['href_exclude_paths'], true )
				&& in_array( '/checkout/*', $output['href_exclude_paths'], true )
				&& in_array( '/my-account/*', $output['href_exclude_paths'], true )
				&& in_array( '/*add-to-cart=*', $output['href_exclude_paths'], true ),
		);

		// Test 2: False input passes through.
		$results[] = array(
			'name'   => 'Speculation_Rules passes through false input',
			'passed' => false === Atlas\Speculation_Rules::filter_configuration( false ),
		);

		return $results;
	}
}
