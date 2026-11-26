<?php
/**
 * Stored contact submissions are private and governed by core's privacy tools.
 *
 * @package AtlasTheme
 */

class Test_Privacy extends WP_UnitTestCase {

	private function store( string $email, string $message = 'A stored message.' ): int {
		return Atlas\Submissions::store(
			array(
				'name'    => 'Person',
				'email'   => $email,
				'message' => $message,
			),
			true
		);
	}

	public function test_the_post_type_is_private_and_not_in_rest(): void {
		$type = get_post_type_object( 'atlas_submission' );

		$this->assertNotNull( $type );
		$this->assertFalse( $type->public );
		$this->assertFalse( $type->publicly_queryable );
		$this->assertFalse( $type->show_in_rest );
		$this->assertTrue( $type->exclude_from_search );
		$this->assertContains( 'atlas_submission', array_keys( get_post_types( array( 'public' => false ) ) ) );
	}

	public function test_only_administrators_can_read_a_submission(): void {
		$id     = $this->store( 'reader@example.com' );
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$admin  = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->assertFalse( user_can( $editor, 'read_post', $id ), 'An editor can read a private contact submission.' );
		$this->assertFalse( user_can( $editor, 'edit_post', $id ) );
		$this->assertTrue( user_can( $admin, 'read_post', $id ) );
	}

	public function test_submissions_never_show_up_in_a_front_end_query(): void {
		$this->store( 'hidden@example.com' );
		$this->go_to( home_url( '/?post_type=atlas_submission' ) );

		$this->assertFalse( have_posts() && 'atlas_submission' === get_post_type() );
	}

	public function test_the_personal_data_exporter_returns_only_that_persons_data(): void {
		$this->store( 'Pat@Example.com', 'First message from Pat.' );
		$this->store( 'pat@example.com', 'Second message from Pat.' );
		$this->store( 'someone@example.com', 'Not Pat.' );

		$exporters = apply_filters( 'wp_privacy_personal_data_exporters', array() );
		$this->assertArrayHasKey( 'atlas-contact-submissions', $exporters );

		$result = call_user_func( $exporters['atlas-contact-submissions']['callback'], 'PAT@example.com', 1 );
		$this->assertCount( 2, $result['data'] );
		$this->assertTrue( $result['done'] );

		$values = wp_list_pluck( array_merge( ...wp_list_pluck( $result['data'], 'data' ) ), 'value' );
		$this->assertContains( 'Second message from Pat.', $values );
		$this->assertNotContains( 'Not Pat.', $values );
	}

	public function test_the_eraser_deletes_only_that_persons_data(): void {
		$this->store( 'erase-me@example.com' );
		$keep = $this->store( 'keep@example.com' );

		$erasers = apply_filters( 'wp_privacy_personal_data_erasers', array() );
		$result  = call_user_func( $erasers['atlas-contact-submissions']['callback'], 'erase-me@example.com', 1 );

		$this->assertSame( 1, $result['items_removed'] );
		$this->assertNotNull( get_post( $keep ) );
		$this->assertSame(
			array( $keep ),
			get_posts(
				array(
					'post_type'   => 'atlas_submission',
					'post_status' => 'any',
					'fields'      => 'ids',
				)
			)
		);
	}

	public function test_the_retention_purge_uses_a_real_date_query(): void {
		$old = $this->store( 'old@example.com' );
		$new = $this->store( 'new@example.com' );
		wp_update_post(
			array(
				'ID'            => $old,
				'post_date'     => gmdate( 'Y-m-d H:i:s', time() - 100 * DAY_IN_SECONDS ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - 100 * DAY_IN_SECONDS ),
			)
		);

		$this->assertSame( 1, Atlas\Submissions::purge_expired() );
		$this->assertNull( get_post( $old ) );
		$this->assertNotNull( get_post( $new ) );
	}

	public function test_the_daily_purge_is_scheduled(): void {
		Atlas\Submissions::schedule_purge();
		$this->assertNotFalse( wp_next_scheduled( 'atlas_purge_submissions' ) );
		$this->assertSame( 'daily', wp_get_schedule( 'atlas_purge_submissions' ) );
	}
}
