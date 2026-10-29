<?php
/**
 * Unit tests for transient caching in Schema and Llms_Txt.
 *
 * @package AtlasTheme
 */

class Test_Caching {

	public static function run(): array {
		$results = array();

		// Test 1: Schema transient keys and invalidation.
		$results[] = array(
			'name'   => 'Schema defines TRANSIENT_PREFIX',
			'passed' => 'atlas_schema_' === Atlas\Schema::TRANSIENT_PREFIX,
		);

		set_transient( 'atlas_schema_front', array( 'node' => 'test' ), 3600 );
		set_transient( 'atlas_schema_post_42', array( 'node' => 'post' ), 3600 );

		Atlas\Schema::invalidate_cache( 42 );

		$results[] = array(
			'name'   => 'Schema invalidate_cache clears specific post transient',
			'passed' => false === get_transient( 'atlas_schema_post_42' ),
		);

		$results[] = array(
			'name'   => 'Schema invalidate_cache clears front transient',
			'passed' => false === get_transient( 'atlas_schema_front' ),
		);

		// Test 2: Llms_Txt transient keys and invalidation.
		$results[] = array(
			'name'   => 'Llms_Txt defines TRANSIENT_KEY',
			'passed' => 'atlas_llms_txt_catalog' === Atlas\Llms_Txt::TRANSIENT_KEY,
		);

		$results[] = array(
			'name'   => 'Llms_Txt defines TRANSIENT_KEY_FULL',
			'passed' => 'atlas_llms_txt_full_catalog' === Atlas\Llms_Txt::TRANSIENT_KEY_FULL,
		);

		set_transient( Atlas\Llms_Txt::TRANSIENT_KEY, 'catalog content', 3600 );
		set_transient( Atlas\Llms_Txt::TRANSIENT_KEY_FULL, 'full catalog content', 3600 );
		Atlas\Llms_Txt::invalidate_cache();

		$results[] = array(
			'name'   => 'Llms_Txt invalidate_cache clears both concise and full catalog transients',
			'passed' => false === get_transient( Atlas\Llms_Txt::TRANSIENT_KEY ) && false === get_transient( Atlas\Llms_Txt::TRANSIENT_KEY_FULL ),
		);

		return $results;
	}
}
