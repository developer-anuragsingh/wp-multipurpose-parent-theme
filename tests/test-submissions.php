<?php
/**
 * Tests for Atlas\Submissions (storage, retention, privacy tooling) and the
 * Contact_Form hooks that feed it.
 *
 * @package AtlasTheme
 */

class Test_Submissions {

	private static function reset(): void {
		global $atlas_test_store, $atlas_test_store_next, $atlas_test_filters, $atlas_test_actions, $atlas_test_mail, $atlas_test_mail_result, $atlas_test_transients, $atlas_test_cron, $atlas_test_policy;
		$atlas_test_store      = array();
		$atlas_test_store_next = 1;
		$atlas_test_cron       = array();
		$atlas_test_policy     = array();
		$atlas_test_mail       = array();
		$atlas_test_transients = array();
		$atlas_test_mail_result = true;
		unset( $atlas_test_filters['atlas_contact_store_submissions'], $atlas_test_filters['atlas_submission_retention_days'], $atlas_test_filters['atlas_contact_form_verify_challenge'] );
		$atlas_test_actions['atlas_contact_form_submitted'] = array(
			array(
				'callback'      => array( 'Atlas\Submissions', 'store' ),
				'priority'      => 10,
				'accepted_args' => 2,
			),
		);
		$_SERVER['REMOTE_ADDR'] = '198.51.100.9';
	}

	private static function set_filter( string $hook, callable $cb ): void {
		global $atlas_test_filters;
		$atlas_test_filters[ $hook ] = array(
			array(
				'callback'      => $cb,
				'priority'      => 10,
				'accepted_args' => 2,
			),
		);
	}

	public static function run(): array {
		global $atlas_test_store, $atlas_test_post_types, $atlas_test_cron, $atlas_test_policy, $atlas_test_mail, $atlas_test_mail_result;
		$results = array();
		$now     = time();
		$token   = Atlas\Contact_Form::issue_token( $now - 60 );

		$data = array(
			'name'    => 'Alex Morgan',
			'email'   => 'Alex@Example.com',
			'message' => 'Hello there, please call me back.',
		);

		// --- Wiring: init() must register every hook (no hand-registered hooks here). ---
		global $atlas_test_actions, $atlas_test_filters;
		$saved_actions = $atlas_test_actions;
		$saved_filters = $atlas_test_filters;
		$atlas_test_actions = array();
		$atlas_test_filters = array();
		Atlas\Submissions::init();
		$wired_actions = array_keys( $atlas_test_actions );
		$wired_filters = array_keys( $atlas_test_filters );
		$atlas_test_actions = $saved_actions;
		$atlas_test_filters = $saved_filters;

		$results[] = array(
			'name'   => 'Submissions: init() wires the post type, purge schedule, store listener, cron and privacy hooks',
			'passed' => array() === array_diff(
				array( 'init', 'atlas_contact_form_submitted', 'atlas_purge_submissions', 'admin_init' ),
				$wired_actions
			) && array() === array_diff(
				array( 'wp_privacy_personal_data_exporters', 'wp_privacy_personal_data_erasers' ),
				$wired_filters
			),
		);

		// --- Post type privacy. ---
		Atlas\Submissions::register_post_type();
		$pt = $atlas_test_post_types['atlas_submission'] ?? array();

		$results[] = array(
			'name'   => 'Submissions: the post type is private (not public, queryable, searchable or in REST)',
			'passed' => false === $pt['public'] && false === $pt['publicly_queryable'] && true === $pt['exclude_from_search']
				&& false === $pt['show_in_rest'] && false === $pt['has_archive'] && false === $pt['rewrite'],
		);

		$caps = array_unique( array_diff( $pt['capabilities'] ?? array(), array( 'do_not_allow' ) ) );

		$results[] = array(
			'name'   => 'Submissions: every capability requires manage_options and creating via UI is disallowed',
			'passed' => array( 'manage_options' ) === array_values( $caps ) && 'do_not_allow' === $pt['capabilities']['create_posts'],
		);

		// --- Store. ---
		self::reset();
		$id = Atlas\Submissions::store( $data, true );
		$row = $atlas_test_store[ $id ] ?? array();

		$results[] = array(
			'name'   => 'Submissions: an accepted submission is stored privately with a lower-cased email and status',
			'passed' => $id > 0 && 'private' === $row['post_status'] && 'atlas_submission' === $row['post_type']
				&& 'alex@example.com' === $row['meta']['atlas_email'] && 'sent' === $row['meta']['atlas_mail_status'],
		);

		self::reset();
		$id = Atlas\Submissions::store( $data, false );

		$results[] = array(
			'name'   => 'Submissions: a failed mail is recorded as mail_failed (message is not lost)',
			'passed' => 'mail_failed' === ( $atlas_test_store[ $id ]['meta']['atlas_mail_status'] ?? '' ),
		);

		self::reset();
		$results[] = array(
			'name'   => 'Submissions: an invalid email or non-array payload stores nothing',
			'passed' => 0 === Atlas\Submissions::store( array_merge( $data, array( 'email' => 'nope' ) ) )
				&& 0 === Atlas\Submissions::store( 'x' ) && array() === $atlas_test_store,
		);

		self::reset();
		self::set_filter(
			'atlas_contact_store_submissions',
			static function () {
				return false;
			}
		);
		$results[] = array(
			'name'   => 'Submissions: storage can be disabled with atlas_contact_store_submissions',
			'passed' => 0 === Atlas\Submissions::store( $data ) && array() === $atlas_test_store,
		);

		// --- Wiring from the contact form. ---
		self::reset();
		$request = new WP_REST_Request(
			array(
				'name'             => 'Sam Lee',
				'email'            => 'sam@example.com',
				'message'          => 'A valid message of enough length.',
				'atlas_contact_hp' => '',
				'atlas_contact_ts' => $token,
			),
			array( 'X-Atlas-Enhanced' => '1' )
		);
		add_filter(
			'atlas_contact_form_recipient',
			static function () {
				return 'owner@example.com';
			}
		);
		Atlas\Contact_Form::handle_request( $request );

		$results[] = array(
			'name'   => 'Contact form: a sent submission is stored via the atlas_contact_form_submitted action',
			'passed' => 1 === count( $atlas_test_store ) && 1 === count( $atlas_test_mail )
				&& 'sent' === array_values( $atlas_test_store )[0]['meta']['atlas_mail_status'],
		);

		self::reset();
		$atlas_test_mail_result = false;
		$res                    = Atlas\Contact_Form::handle_request( $request );

		$results[] = array(
			'name'   => 'Contact form: when wp_mail fails the user gets a 500 but the message is still stored',
			'passed' => 500 === $res->get_status() && 1 === count( $atlas_test_store )
				&& 'mail_failed' === array_values( $atlas_test_store )[0]['meta']['atlas_mail_status'],
		);

		self::reset();
		$bot = new WP_REST_Request(
			array(
				'name'             => 'Bot',
				'email'            => 'bot@example.com',
				'message'          => 'Spam spam spam spam.',
				'atlas_contact_hp' => 'filled',
				'atlas_contact_ts' => $token,
			),
			array( 'X-Atlas-Enhanced' => '1' )
		);
		Atlas\Contact_Form::handle_request( $bot );

		$results[] = array(
			'name'   => 'Contact form: honeypot and rejected requests are never stored',
			'passed' => array() === $atlas_test_store,
		);

		// --- CAPTCHA / challenge extension point. ---
		self::reset();
		self::set_filter(
			'atlas_contact_form_verify_challenge',
			static function () {
				return false;
			}
		);
		$res = Atlas\Contact_Form::handle_request( $request );

		$results[] = array(
			'name'   => 'Challenge: a failing atlas_contact_form_verify_challenge filter rejects with 400, no mail, nothing stored',
			'passed' => 400 === $res->get_status() && array() === $atlas_test_mail && array() === $atlas_test_store,
		);

		self::reset();
		$res = Atlas\Contact_Form::handle_request( $request );

		$results[] = array(
			'name'   => 'Challenge: with no provider the form works unchanged',
			'passed' => 200 === $res->get_status() && 1 === count( $atlas_test_mail ),
		);

		// --- Retention. ---
		self::reset();
		$old = Atlas\Submissions::store( $data );
		$new = Atlas\Submissions::store( array_merge( $data, array( 'email' => 'new@example.com' ) ) );
		$atlas_test_store[ $old ]['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $now - ( 91 * 86400 ) );
		$atlas_test_store[ $new ]['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $now - ( 10 * 86400 ) );
		$removed                                    = Atlas\Submissions::purge_expired();

		$results[] = array(
			'name'   => 'Retention: entries older than 90 days are purged and recent ones kept',
			'passed' => 1 === $removed && ! isset( $atlas_test_store[ $old ] ) && isset( $atlas_test_store[ $new ] ),
		);

		self::set_filter(
			'atlas_submission_retention_days',
			static function () {
				return -5;
			}
		);

		$results[] = array(
			'name'   => 'Retention: the window can never drop below 1 day (no accidental "delete everything")',
			'passed' => 1 === Atlas\Submissions::retention_days() && 90 === Atlas\Submissions::RETENTION_DAYS_DEFAULT,
		);

		self::reset();
		for ( $i = 0; $i < 120; $i++ ) {
			$sid                                       = Atlas\Submissions::store( $data );
			$atlas_test_store[ $sid ]['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $now - ( 200 * 86400 ) );
		}

		$results[] = array(
			'name'   => 'Retention: purge handles more than one batch (120 expired entries)',
			'passed' => 120 === Atlas\Submissions::purge_expired() && array() === $atlas_test_store,
		);

		self::reset();
		Atlas\Submissions::schedule_purge();
		Atlas\Submissions::schedule_purge();

		$results[] = array(
			'name'   => 'Retention: the purge is scheduled daily exactly once',
			'passed' => 'daily' === ( $atlas_test_cron['atlas_purge_submissions']['recurrence'] ?? '' ),
		);

		// --- Export / erase. ---
		self::reset();
		Atlas\Submissions::store( $data );
		Atlas\Submissions::store( array_merge( $data, array( 'message' => 'Second message from the same person.' ) ) );
		Atlas\Submissions::store( array_merge( $data, array( 'email' => 'other@example.com', 'name' => 'Other' ) ) );

		$exp = Atlas\Submissions::export( 'ALEX@example.COM', 1 );

		$results[] = array(
			'name'   => 'Export: finds all of one person\'s submissions case-insensitively and none of anyone else\'s',
			'passed' => 2 === count( $exp['data'] ) && true === $exp['done']
				&& 'Alex Morgan' === $exp['data'][0]['data'][0]['value']
				&& 'alex@example.com' === $exp['data'][0]['data'][1]['value'],
		);

		$results[] = array(
			'name'   => 'Export: an empty email exports nothing',
			'passed' => array() === Atlas\Submissions::export( '', 1 )['data'],
		);

		$er = Atlas\Submissions::erase( 'alex@example.com', 1 );

		$results[] = array(
			'name'   => 'Erase: removes only that person\'s submissions and reports the count',
			'passed' => 2 === $er['items_removed'] && true === $er['done'] && false === $er['items_retained']
				&& 1 === count( $atlas_test_store ) && 'other@example.com' === array_values( $atlas_test_store )[0]['meta']['atlas_email'],
		);

		$exporters = Atlas\Submissions::register_exporter( array( 'core' => array() ) );
		$erasers   = Atlas\Submissions::register_eraser( array() );

		$results[] = array(
			'name'   => 'Privacy: exporter and eraser register with core without dropping existing ones',
			'passed' => isset( $exporters['core'], $exporters['atlas-contact-submissions']['callback'], $erasers['atlas-contact-submissions']['callback'] ),
		);

		self::reset();
		Atlas\Submissions::add_privacy_policy_content();
		$policy = implode( ' ', $atlas_test_policy );

		$results[] = array(
			'name'   => 'Privacy: suggested policy text states the real retention period',
			'passed' => str_contains( $policy, '90 days' ),
		);

		return $results;
	}
}
