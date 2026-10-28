<?php
/**
 * Unit tests for Atlas\Maintenance.
 *
 * @package AtlasTheme
 */

class Test_Maintenance {

	public static function run(): array {
		$results = array();

		// Test 1: Inactive by default.
		$results[] = array(
			'name'   => 'Maintenance is inactive by default',
			'passed' => false === Atlas\Maintenance::is_active(),
		);

		// Test 2: Active via filter.
		add_filter( 'atlas_maintenance_enabled', '__return_true' );

		$results[] = array(
			'name'   => 'Maintenance is active when atlas_maintenance_enabled filter returns true',
			'passed' => true === Atlas\Maintenance::is_active(),
		);

		// Test 3: Template override returns maintenance.html.
		$original_template = '/path/to/some/template.php';
		$override          = Atlas\Maintenance::override_template( $original_template );

		$results[] = array(
			'name'   => 'Maintenance overrides template with maintenance.html',
			'passed' => str_ends_with( $override, 'maintenance.html' ),
		);

		// Test 4: Default retry after is 3600.
		$results[] = array(
			'name'   => 'Maintenance default Retry-After is 3600 seconds',
			'passed' => 3600 === Atlas\Maintenance::DEFAULT_RETRY_AFTER,
		);

		return $results;
	}
}
