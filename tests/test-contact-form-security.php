<?php
/**
 * Security tests for Atlas\Contact_Form (token, rate limit, mail headers).
 *
 * @package AtlasTheme
 */

class Test_Contact_Form_Security {

	/**
	 * Build a valid submission request carrying the given token.
	 *
	 * @param string $token Form token.
	 * @param array  $extra Param overrides.
	 * @return WP_REST_Request
	 */
	private static function request( string $token, array $extra = array() ): WP_REST_Request {
		return new WP_REST_Request(
			array_merge(
				array(
					'name'              => 'Alex Morgan',
					'email'             => 'alex@example.com',
					'message'           => 'Hello, this is a long enough message.',
					'atlas_contact_hp'  => '',
					'atlas_contact_ts'  => $token,
				),
				$extra
			),
			array( 'X-Atlas-Enhanced' => '1' )
		);
	}

	private static function reset(): void {
		global $atlas_test_mail, $atlas_test_transients, $atlas_test_object_cache, $atlas_test_ext_cache, $atlas_test_mail_result;
		$atlas_test_mail         = array();
		$atlas_test_transients   = array();
		$atlas_test_object_cache = array();
		$atlas_test_ext_cache    = false;
		$atlas_test_mail_result  = true;
		$_SERVER['REMOTE_ADDR']  = '203.0.113.7';
	}

	public static function run(): array {
		global $atlas_test_mail, $atlas_test_ext_cache;
		$results = array();
		$now     = time();

		add_filter(
			'atlas_contact_form_recipient',
			static function () {
				return 'owner@example.com';
			}
		);
		$old     = Atlas\Contact_Form::issue_token( $now - 60 );

		// --- Token. ---
		$results[] = array(
			'name'   => 'Token: a freshly signed, old-enough token verifies ok',
			'passed' => 'ok' === Atlas\Contact_Form::verify_token( $old, $now ),
		);

		$results[] = array(
			'name'   => 'Token: younger than MIN_SUBMIT_SECONDS is too_fast',
			'passed' => 'too_fast' === Atlas\Contact_Form::verify_token( Atlas\Contact_Form::issue_token( $now - 1 ), $now ),
		);

		$parts     = explode( '.', $old );
		$back_date = ( (int) $parts[0] - 3600 ) . '.' . $parts[1];

		$results[] = array(
			'name'   => 'Token: changing the signed timestamp (back-dating) is rejected',
			'passed' => 'invalid' === Atlas\Contact_Form::verify_token( $back_date, $now ),
		);

		$results[] = array(
			'name'   => 'Token: malformed, empty and future tokens are rejected',
			'passed' => 'invalid' === Atlas\Contact_Form::verify_token( '', $now )
				&& 'invalid' === Atlas\Contact_Form::verify_token( '12345', $now )
				&& 'invalid' === Atlas\Contact_Form::verify_token( ( $now + 999 ) . '.' . str_repeat( 'a', 64 ), $now )
				&& 'invalid' === Atlas\Contact_Form::verify_token( Atlas\Contact_Form::issue_token( $now + 600 ), $now ),
		);

		$results[] = array(
			'name'   => 'Token: an expired token (older than the TTL) is rejected',
			'passed' => 'invalid' === Atlas\Contact_Form::verify_token(
				Atlas\Contact_Form::issue_token( $now - Atlas\Contact_Form::TOKEN_TTL_DEFAULT - 10 ),
				$now
			),
		);

		// --- Handler: token gate. ---
		self::reset();
		$res = Atlas\Contact_Form::handle_request( self::request( '' ) );

		$results[] = array(
			'name'   => 'Handler: missing token is rejected with 403 and sends no mail',
			'passed' => 403 === $res->get_status() && array() === $atlas_test_mail,
		);

		self::reset();
		$res = Atlas\Contact_Form::handle_request( self::request( $old ) );

		$results[] = array(
			'name'   => 'Handler: a valid submission sends exactly one mail and returns 200',
			'passed' => 200 === $res->get_status() && 1 === count( $atlas_test_mail ),
		);

		self::reset();
		$res = Atlas\Contact_Form::handle_request( self::request( Atlas\Contact_Form::issue_token( time() ) ) );

		$results[] = array(
			'name'   => 'Handler: too-fast submission gets generic 200 but no mail',
			'passed' => 200 === $res->get_status() && array() === $atlas_test_mail,
		);

		self::reset();
		$res = Atlas\Contact_Form::handle_request( self::request( $old, array( 'atlas_contact_hp' => 'bot' ) ) );

		$results[] = array(
			'name'   => 'Handler: filled honeypot gets generic 200 but no mail',
			'passed' => 200 === $res->get_status() && array() === $atlas_test_mail,
		);

		// --- Rate limit. ---
		self::reset();
		$last = null;
		for ( $i = 0; $i < Atlas\Contact_Form::RATE_LIMIT_DEFAULT + 1; $i++ ) {
			$last = Atlas\Contact_Form::handle_request( self::request( $old ) );
		}

		$results[] = array(
			'name'   => 'Rate limit: submission over the cap returns a 429 WP_Error',
			'passed' => is_wp_error( $last )
				&& 429 === $last->get_error_data()['status']
				&& Atlas\Contact_Form::RATE_LIMIT_DEFAULT === count( $atlas_test_mail ),
		);

		self::reset();
		$_SERVER['REMOTE_ADDR'] = '';
		$last                   = null;
		for ( $i = 0; $i < Atlas\Contact_Form::RATE_LIMIT_DEFAULT + 1; $i++ ) {
			$last = Atlas\Contact_Form::handle_request( self::request( $old ) );
		}

		$results[] = array(
			'name'   => 'Rate limit: an unresolvable IP is still throttled (fails closed)',
			'passed' => is_wp_error( $last ),
		);

		self::reset();
		$atlas_test_ext_cache = true;
		$last                 = null;
		for ( $i = 0; $i < Atlas\Contact_Form::RATE_LIMIT_DEFAULT + 1; $i++ ) {
			$last = Atlas\Contact_Form::handle_request( self::request( $old ) );
		}

		$results[] = array(
			'name'   => 'Rate limit: works through the atomic object-cache counter path',
			'passed' => is_wp_error( $last ) && Atlas\Contact_Form::RATE_LIMIT_DEFAULT === count( $atlas_test_mail ),
		);

		global $atlas_test_transients;
		self::reset();
		Atlas\Contact_Form::handle_request( self::request( $old ) );
		$keys = array_keys( $atlas_test_transients );

		$results[] = array(
			'name'   => 'Rate limit: the counter key never contains the raw IP or its md5',
			'passed' => 1 === count( $keys )
				&& ! str_contains( $keys[0], '203.0.113.7' )
				&& ! str_contains( $keys[0], md5( '203.0.113.7' ) ),
		);

		// --- Mail header injection. ---
		self::reset();
		Atlas\Contact_Form::handle_request(
			self::request(
				$old,
				array( 'name' => "Eve\r\nBcc: victim@example.com <x>" )
			)
		);
		$sent    = $atlas_test_mail[0] ?? array( 'subject' => '', 'headers' => array() );
		$headers = implode( "\n", (array) $sent['headers'] );

		$results[] = array(
			'name'   => 'Mail: CR/LF in the name cannot inject headers into Reply-To or subject',
			'passed' => ! str_contains( $headers, "\r" )
				&& 1 === count( explode( "\n", $headers ) )
				&& ! str_contains( $sent['subject'], "\n" )
				&& ! str_contains( $headers, 'Bcc' . ':' ),
		);

		$results[] = array(
			'name'   => 'Mail: header_safe strips CR, LF, NUL, angle brackets, quotes and separators',
			'passed' => 'a b c d e f g h' === Atlas\Contact_Form::header_safe( "a\rb\nc\0d<e>f\"g,h;" ),
		);

		// --- Token endpoint. ---
		$token_res = Atlas\Contact_Form::handle_token_request();

		$results[] = array(
			'name'   => 'Token endpoint returns a verifiable token with a no-store header',
			'passed' => 200 === $token_res->get_status()
				&& isset( $token_res->get_data()['token'] )
				&& 'invalid' !== Atlas\Contact_Form::verify_token( $token_res->get_data()['token'] )
				&& str_contains( $token_res->headers['Cache-Control'] ?? '', 'no-store' ),
		);

		return $results;
	}
}
