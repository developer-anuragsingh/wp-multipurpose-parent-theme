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

		// Test 3: Template override hands the block template to core's canvas.
		global $atlas_test_block_templates, $_wp_current_template_id, $_wp_current_template_content;
		$original_template = '/path/to/some/template.php';

		$atlas_test_block_templates = array(
			'atlas-theme//maintenance' => (object) array(
				'id'      => 'atlas-theme//maintenance',
				'content' => '<!-- wp:paragraph --><p>Back soon</p><!-- /wp:paragraph -->',
			),
		);
		$override = Atlas\Maintenance::override_template( $original_template );

		$results[] = array(
			'name'   => 'Maintenance serves the block template through template-canvas.php (never a raw .html include)',
			'passed' => str_ends_with( $override, 'wp-includes/template-canvas.php' )
				&& 'atlas-theme//maintenance' === $_wp_current_template_id
				&& str_contains( (string) $_wp_current_template_content, 'Back soon' ),
		);

		$atlas_test_block_templates = array(
			'atlas-theme//blank' => (object) array(
				'id'      => 'atlas-theme//blank',
				'content' => '<!-- wp:post-content /-->',
			),
		);
		$results[] = array(
			'name'   => 'Maintenance falls back to the blank block template when maintenance is missing',
			'passed' => str_ends_with( Atlas\Maintenance::override_template( $original_template ), 'template-canvas.php' )
				&& 'atlas-theme//blank' === $_wp_current_template_id,
		);

		$atlas_test_block_templates = array();
		$results[]                  = array(
			'name'   => 'Maintenance keeps the original template (never a .html path) when no block template resolves',
			'passed' => $original_template === Atlas\Maintenance::override_template( $original_template ),
		);

		// Test 4: Default retry after is 3600.
		$results[] = array(
			'name'   => 'Maintenance default Retry-After is 3600 seconds',
			'passed' => 3600 === Atlas\Maintenance::DEFAULT_RETRY_AFTER,
		);

		// Test 5: Interception emits nocache_headers.
		global $atlas_test_current_user_can, $atlas_test_headers;
		$atlas_test_current_user_can = false;
		$atlas_test_headers          = array();

		Atlas\Maintenance::maybe_intercept();

		$results[] = array(
			'name'   => 'Maintenance maybe_intercept emits nocache_headers',
			'passed' => isset( $atlas_test_headers['Cache-Control'] ) && str_contains( $atlas_test_headers['Cache-Control'], 'no-cache' ),
		);

		// Restore user cap mock.
		$atlas_test_current_user_can = true;

		return $results;
	}
}
