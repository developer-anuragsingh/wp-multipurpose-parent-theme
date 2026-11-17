<?php
/**
 * Unit tests for Atlas\Security_Headers.
 *
 * @package AtlasTheme
 */

class Test_Security_Headers {

	public static function run(): array {
		$results = array();

		$headers = Atlas\Security_Headers::filter_headers( array() );

		$results[] = array(
			'name'   => 'Adds nosniff, frame, referrer and permissions headers',
			'passed' => 'nosniff' === ( $headers['X-Content-Type-Options'] ?? '' )
				&& 'SAMEORIGIN' === ( $headers['X-Frame-Options'] ?? '' )
				&& isset( $headers['Referrer-Policy'], $headers['Permissions-Policy'] ),
		);

		$results[] = array(
			'name'   => 'CSP defaults to report-only in non-production environments (auto-detect)',
			'passed' => isset( $headers['Content-Security-Policy-Report-Only'] ) && ! isset( $headers['Content-Security-Policy'] ),
		);

		global $atlas_test_env_type;
		$atlas_test_env_type = 'production';
		$results[]           = array(
			'name'   => 'CSP auto-detects and enforces in production environment',
			'passed' => Atlas\Security_Headers::MODE_ENFORCE === Atlas\Security_Headers::csp_mode(),
		);
		$atlas_test_env_type = 'local';

		$csp  = $headers['Content-Security-Policy-Report-Only'] ?? '';
		$hash = "'sha256-" . base64_encode( hash( 'sha256', Atlas\Assets::theme_mode_script(), true ) ) . "'";

		$results[] = array(
			'name'   => 'CSP allows the theme-mode script by SHA-256 hash and never unsafe-inline scripts',
			'passed' => str_contains( $csp, $hash )
				&& str_contains( $csp, "object-src 'none'" )
				&& 1 === preg_match( "/script-src [^;]*'nonce-/", $csp )
				&& 0 === preg_match( "/script-src [^;]*unsafe-inline/", $csp ),
		);

		$results[] = array(
			'name'   => 'HSTS is not sent over plain HTTP',
			'passed' => ! isset( $headers['Strict-Transport-Security'] ),
		);

		$kept = Atlas\Security_Headers::filter_headers( array( 'x-frame-options' => 'DENY' ) );

		$results[] = array(
			'name'   => 'Existing headers are never overwritten (case-insensitive)',
			'passed' => 'DENY' === $kept['x-frame-options'] && ! isset( $kept['X-Frame-Options'] ),
		);

		$mode_cb = static function () {
			return 'enforce';
		};
		add_filter( 'atlas_csp_mode', $mode_cb );
		$enforced = Atlas\Security_Headers::filter_headers( array() );

		$results[] = array(
			'name'   => 'Enforce mode emits Content-Security-Policy',
			'passed' => isset( $enforced['Content-Security-Policy'] ) && ! isset( $enforced['Content-Security-Policy-Report-Only'] ),
		);

		global $atlas_test_filters;
		$atlas_test_filters['atlas_csp_mode'] = array(
			array(
				'callback'      => static function () {
					return 'bogus';
				},
				'priority'      => 10,
				'accepted_args' => 1,
			),
		);

		$results[] = array(
			'name'   => 'Invalid CSP mode falls back to report-only',
			'passed' => Atlas\Security_Headers::MODE_REPORT_ONLY === Atlas\Security_Headers::csp_mode(),
		);

		$atlas_test_filters['atlas_csp_mode'] = array(
			array(
				'callback'      => static function () {
					return 'off';
				},
				'priority'      => 10,
				'accepted_args' => 1,
			),
		);
		$off = Atlas\Security_Headers::filter_headers( array() );

		$results[] = array(
			'name'   => 'Mode off emits no CSP header',
			'passed' => ! isset( $off['Content-Security-Policy'] ) && ! isset( $off['Content-Security-Policy-Report-Only'] ),
		);

		$no_nonce = Atlas\Security_Headers::add_nonce_attribute( array( 'id' => 'x' ) );
		unset( $atlas_test_filters['atlas_csp_mode'] );
		$with_nonce = Atlas\Security_Headers::add_nonce_attribute( array( 'id' => 'x' ) );
		$kept_nonce = Atlas\Security_Headers::add_nonce_attribute( array( 'nonce' => 'abc' ) );

		$results[] = array(
			'name'   => 'Nonce is added to scripts only when CSP is active and never replaced',
			'passed' => ! isset( $no_nonce['nonce'] ) && ! empty( $with_nonce['nonce'] ) && 'abc' === $kept_nonce['nonce'],
		);

		$atlas_test_filters['atlas_security_headers'] = array(
			array(
				'callback'      => static function ( $h ) {
					$h['X-Bad'] = "a\r\nSet-Cookie: x=1";
					return $h;
				},
				'priority'      => 10,
				'accepted_args' => 1,
			),
		);
		$injected = Atlas\Security_Headers::filter_headers( array() );
		unset( $atlas_test_filters['atlas_security_headers'] );

		$results[] = array(
			'name'   => 'Header values containing CR/LF are dropped (no response splitting)',
			'passed' => ! isset( $injected['X-Bad'] ),
		);

		return $results;
	}
}
