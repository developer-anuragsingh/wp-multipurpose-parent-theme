<?php
/**
 * Unit tests for Atlas\I18n (Language Switching & Front-End UI).
 *
 * @package AtlasTheme
 */

class Test_I18n {

	/**
	 * Run the I18n test suite.
	 *
	 * @return array[] Test results.
	 */
	public static function run(): array {
		$results = array();

		// Test 1: Verify constants.
		$results[] = array(
			'name'   => 'I18n defines COOKIE_NAME and QUERY_VAR constants',
			'passed' => 'atlas_locale' === Atlas\I18n::COOKIE_NAME &&
						'lang' === Atlas\I18n::QUERY_VAR &&
						'en_US' === Atlas\I18n::DEFAULT_LOCALE,
		);

		// Test 2: Supported locales contain English and Hindi defaults.
		$supported = Atlas\I18n::get_supported_locales();
		$has_en    = isset( $supported['en_US'] ) && 'EN' === $supported['en_US']['short'];
		$has_hi    = isset( $supported['hi_IN'] ) && 'हिन्दी' === $supported['hi_IN']['native'];

		$results[] = array(
			'name'   => 'I18n get_supported_locales contains en_US and hi_IN with native labels',
			'passed' => $has_en && $has_hi,
		);

		// Test 3: Match locale normalization.
		$match_full  = 'hi_IN' === Atlas\I18n::match_locale( 'hi_IN', $supported );
		$match_short = 'hi_IN' === Atlas\I18n::match_locale( 'hi', $supported );
		$match_en    = 'en_US' === Atlas\I18n::match_locale( 'EN', $supported );
		$match_bad   = null === Atlas\I18n::match_locale( 'invalid_xyz', $supported );

		$results[] = array(
			'name'   => 'I18n match_locale normalizes codes and rejects invalid locales',
			'passed' => $match_full && $match_short && $match_en && $match_bad,
		);

		// Test 4: filter_locale with $_GET query parameter.
		$_GET['lang'] = 'hi_IN';
		$locale_from_get = Atlas\I18n::filter_locale( 'en_US' );
		unset( $_GET['lang'] );

		$results[] = array(
			'name'   => 'I18n filter_locale activates locale from lang query parameter',
			'passed' => 'hi_IN' === $locale_from_get,
		);

		// Test 5: filter_locale with $_COOKIE.
		$_COOKIE['atlas_locale'] = 'hi_IN';
		$locale_from_cookie = Atlas\I18n::filter_locale( 'en_US' );
		unset( $_COOKIE['atlas_locale'] );

		$results[] = array(
			'name'   => 'I18n filter_locale respects cookie persistence when query param absent',
			'passed' => 'hi_IN' === $locale_from_cookie,
		);

		// Test 6: filter_hreflang generates alternates.
		$hreflang = Atlas\I18n::filter_hreflang( array() );
		$has_hr_en = isset( $hreflang['en'] ) && false !== strpos( $hreflang['en'], 'lang=en_US' );
		$has_hr_hi = isset( $hreflang['hi'] ) && false !== strpos( $hreflang['hi'], 'lang=hi_IN' );
		$has_hr_def = isset( $hreflang['x-default'] );

		$results[] = array(
			'name'   => 'I18n filter_hreflang enriches alternates with en, hi, and x-default',
			'passed' => $has_hr_en && $has_hr_hi && $has_hr_def,
		);

		// Test 7: render_switcher output markup.
		$html = Atlas\I18n::render_switcher();
		$has_nav       = false !== strpos( $html, '<nav class="atlas-lang-switcher"' );
		$has_en_link   = false !== strpos( $html, 'data-lang="en_US"' ) && false !== strpos( $html, 'English' );
		$has_hi_link   = false !== strpos( $html, 'data-lang="hi_IN"' ) && false !== strpos( $html, 'हिन्दी' );
		$has_separator = false !== strpos( $html, 'class="atlas-lang-switcher__separator"' );

		$results[] = array(
			'name'   => 'I18n render_switcher emits accessible navigation markup and links',
			'passed' => $has_nav && $has_en_link && $has_hi_link && $has_separator,
		);

		// Test 8: filter_header_template_part dynamically updates switcher.
		$dummy_header = '<header><div class="logo"></div><nav class="atlas-lang-switcher"><a href="?lang=old">Old</a></nav></header>';
		$filtered     = Atlas\I18n::filter_header_template_part( $dummy_header, array( 'attrs' => array( 'slug' => 'header' ) ) );
		$updated      = false !== strpos( $filtered, 'data-lang="en_US"' ) && false !== strpos( $filtered, 'data-lang="hi_IN"' );

		$results[] = array(
			'name'   => 'I18n filter_header_template_part injects active switcher into header part',
			'passed' => $updated,
		);

		return $results;
	}
}
