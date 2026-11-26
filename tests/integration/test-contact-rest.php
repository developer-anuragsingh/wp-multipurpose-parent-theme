<?php
/**
 * The contact form's REST routes, end to end through the real REST server,
 * real argument validation, real wp_mail (core's mock mailer) and real storage.
 *
 * @package AtlasTheme
 */

class Test_Contact_Rest extends WP_UnitTestCase {

	/**
	 * @var WP_REST_Server
	 */
	private $server;

	public function set_up(): void {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init', $this->server );

		reset_phpmailer_instance();
		wp_cache_flush();
		$_SERVER['REMOTE_ADDR'] = '203.0.113.' . wp_rand( 1, 250 );
	}

	public function tear_down(): void {
		global $wp_rest_server;
		$wp_rest_server = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		unset( $_SERVER['HTTP_REFERER'] );
		parent::tear_down();
	}

	/**
	 * Dispatch a POST to the contact route.
	 *
	 * @param array $params   Body params.
	 * @param bool  $enhanced Send the X-Atlas-Enhanced header (JS path).
	 * @return WP_REST_Response
	 */
	private function submit( array $params, bool $enhanced = true ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/atlas/v1/contact' );
		$request->set_body_params(
			array_merge(
				array(
					'name'             => 'Alex Morgan',
					'email'            => 'Alex@Example.com',
					'message'          => 'Hello, this is a long enough message.',
					'atlas_contact_hp' => '',
					'atlas_contact_ts' => Atlas\Contact_Form::issue_token( time() - 60 ),
				),
				$params
			)
		);
		if ( $enhanced ) {
			$request->set_header( 'X-Atlas-Enhanced', '1' );
		}
		return rest_ensure_response( $this->server->dispatch( $request ) );
	}

	private function sent_mail(): array {
		return tests_retrieve_phpmailer_instance()->mock_sent;
	}

	private function submissions(): array {
		return get_posts(
			array(
				'post_type'   => 'atlas_submission',
				'post_status' => 'any',
				'numberposts' => -1,
			)
		);
	}

	public function test_both_routes_are_registered(): void {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/atlas/v1/contact', $routes );
		$this->assertArrayHasKey( '/atlas/v1/contact/token', $routes );
	}

	public function test_token_endpoint_returns_a_signed_token_that_is_never_cached(): void {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/atlas/v1/contact/token' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertMatchesRegularExpression( '/^\d+\.[a-f0-9]{64}$/', $response->get_data()['token'] );
		$this->assertStringContainsString( 'no-store', $response->get_headers()['Cache-Control'] ?? '' );
	}

	public function test_a_submission_without_a_token_is_rejected(): void {
		$response = $this->submit( array( 'atlas_contact_ts' => '' ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertEmpty( $this->sent_mail() );
		$this->assertEmpty( $this->submissions() );
	}

	public function test_a_forged_token_is_rejected(): void {
		$response = $this->submit( array( 'atlas_contact_ts' => ( time() - 60 ) . '.' . str_repeat( 'a', 64 ) ) );
		$this->assertSame( 403, $response->get_status() );
	}

	public function test_core_rejects_an_invalid_email_through_the_args_schema(): void {
		$response = $this->submit( array( 'email' => 'not-an-email' ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEmpty( $this->sent_mail() );
	}

	public function test_a_valid_submission_is_mailed_and_stored_privately(): void {
		$response = $this->submit( array() );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'success', $response->get_data()['status'] );

		$mail = $this->sent_mail();
		$this->assertCount( 1, $mail );
		$this->assertSame( get_option( 'admin_email' ), $mail[0]['to'][0][0] );
		$this->assertStringContainsString( 'Alex Morgan', $mail[0]['subject'] );
		$this->assertStringContainsString( 'Reply-To: Alex Morgan <Alex@Example.com>', $mail[0]['header'] );

		$posts = $this->submissions();
		$this->assertCount( 1, $posts );
		$this->assertSame( 'private', $posts[0]->post_status );
		$this->assertSame( 'alex@example.com', get_post_meta( $posts[0]->ID, 'atlas_email', true ) );
		$this->assertSame( 'sent', get_post_meta( $posts[0]->ID, 'atlas_mail_status', true ) );
	}

	public function test_a_filled_honeypot_looks_successful_but_sends_and_stores_nothing(): void {
		$response = $this->submit( array( 'atlas_contact_hp' => 'i-am-a-bot' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertEmpty( $this->sent_mail() );
		$this->assertEmpty( $this->submissions() );
	}

	public function test_a_too_fast_submission_looks_successful_but_sends_nothing(): void {
		$response = $this->submit( array( 'atlas_contact_ts' => Atlas\Contact_Form::issue_token( time() ) ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertEmpty( $this->sent_mail() );
	}

	public function test_header_injection_through_the_name_is_neutralised(): void {
		$this->submit( array( 'name' => "Eve\r\nBcc: victim@example.com" ) );

		$mail = $this->sent_mail();
		$this->assertCount( 1, $mail );
		$this->assertStringNotContainsString( 'victim@example.com', $mail[0]['header'] );
		$this->assertStringNotContainsStringIgnoringCase( "\nbcc:", $mail[0]['header'] );
		$this->assertStringNotContainsString( "\n", $mail[0]['subject'] );
	}

	public function test_the_sixth_submission_from_one_ip_is_rate_limited(): void {
		for ( $i = 1; $i <= Atlas\Contact_Form::RATE_LIMIT_DEFAULT; $i++ ) {
			$this->assertSame( 200, $this->submit( array() )->get_status(), "Submission {$i} should pass." );
		}

		$response = $this->submit( array() );
		$this->assertSame( 429, $response->get_status() );

		// The Retry-After header is added on rest_post_dispatch, as for a real request.
		$response = apply_filters( 'rest_post_dispatch', $response, $this->server, new WP_REST_Request( 'POST', '/atlas/v1/contact' ) );
		$this->assertSame( (string) Atlas\Contact_Form::RATE_LIMIT_WINDOW, $response->get_headers()['Retry-After'] ?? '' );
		$this->assertCount( Atlas\Contact_Form::RATE_LIMIT_DEFAULT, $this->sent_mail() );
	}

	public function test_the_no_js_post_redirects_back_with_the_outcome(): void {
		$_SERVER['HTTP_REFERER'] = home_url( '/contact/' );
		$response                = $this->submit( array(), false );

		$this->assertSame( 303, $response->get_status() );
		$location = $response->get_headers()['Location'] ?? '';
		$this->assertStringStartsWith( home_url( '/contact/' ), $location );
		$this->assertStringContainsString( 'atlas-contact=sent', $location );
	}

	public function test_a_mail_failure_still_keeps_the_message(): void {
		add_filter( 'pre_wp_mail', '__return_false' );
		$response = $this->submit( array() );
		remove_filter( 'pre_wp_mail', '__return_false' );

		$this->assertSame( 500, $response->get_status() );
		$posts = $this->submissions();
		$this->assertCount( 1, $posts );
		$this->assertSame( 'mail_failed', get_post_meta( $posts[0]->ID, 'atlas_mail_status', true ) );
	}
}
