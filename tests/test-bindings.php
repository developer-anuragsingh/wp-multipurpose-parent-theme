<?php
/**
 * Unit tests for Atlas\Bindings and Atlas\Contact_Form.
 *
 * @package AtlasTheme
 */

class Test_Bindings {

	public static function run(): array {
		$results = array();

		$results[] = array(
			'name'   => 'Bindings declares atlas/post-meta source constant',
			'passed' => 'atlas/post-meta' === Atlas\Bindings::SOURCE,
		);

		$results[] = array(
			'name'   => 'Bindings declares atlas_subtitle meta constant',
			'passed' => 'atlas_subtitle' === Atlas\Bindings::META_SUBTITLE,
		);

		return $results;
	}
}

class Test_Contact_Form {

	public static function run(): array {
		$results = array();

		$results[] = array(
			'name'   => 'Contact_Form rate limit default is 5',
			'passed' => 5 === Atlas\Contact_Form::RATE_LIMIT_DEFAULT,
		);

		$results[] = array(
			'name'   => 'Contact_Form minimum submit time is 3 seconds',
			'passed' => 3 === Atlas\Contact_Form::MIN_SUBMIT_SECONDS,
		);

		$results[] = array(
			'name'   => 'Contact_Form route is /contact',
			'passed' => '/contact' === Atlas\Contact_Form::REST_ROUTE,
		);

		return $results;
	}
}
