<?php
/**
 * Contact-form submission storage, retention and privacy tooling.
 *
 * Stores every accepted contact submission in a PRIVATE custom post type so a
 * failed wp_mail() no longer loses the message, then governs that personal data
 * as docs/06-security-and-i18n/03-data-protection.md §7 requires:
 *
 *  - Private by construction: not public, not queryable, not searchable, not in
 *    REST, never in sitemaps/feeds; every capability maps to manage_options.
 *  - Retention: a daily cron purges entries older than the retention window
 *    (default 90 days, filterable, never below 1 day).
 *  - Data-subject rights: core Export / Erase Personal Data tools find and
 *    remove a person's submissions by email address.
 *  - Transparency: suggested privacy-policy text is registered.
 *
 * It is decoupled from Atlas\Contact_Form: it only listens to the
 * atlas_contact_form_submitted action, so the form works the same without it.
 * No field value is ever logged.
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for submission storage and privacy hooks.
 */
class Submissions {

	/**
	 * Private post type that holds submissions.
	 *
	 * @var string
	 */
	const POST_TYPE = 'atlas_submission';

	/**
	 * Action fired by Contact_Form after an accepted submission.
	 *
	 * @var string
	 */
	const ACTION_SUBMITTED = 'atlas_contact_form_submitted';

	/**
	 * Filter: store submissions at all (default true).
	 *
	 * @var string
	 */
	const FILTER_ENABLED = 'atlas_contact_store_submissions';

	/**
	 * Filter: retention window in days (default 90, minimum 1).
	 *
	 * @var string
	 */
	const FILTER_RETENTION_DAYS = 'atlas_submission_retention_days';

	/**
	 * Default retention window in days.
	 *
	 * @var int
	 */
	const RETENTION_DAYS_DEFAULT = 90;

	/**
	 * Cron hook that purges expired submissions.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'atlas_purge_submissions';

	/**
	 * Meta key: the submitter's email address (lower-cased).
	 *
	 * @var string
	 */
	const META_EMAIL = 'atlas_email';

	/**
	 * Meta key: the submitter's phone number.
	 *
	 * @var string
	 */
	const META_PHONE = 'atlas_phone';

	/**
	 * Meta key: mail delivery status, 'sent' or 'mail_failed'.
	 *
	 * @var string
	 */
	const META_STATUS = 'atlas_mail_status';

	/**
	 * Items processed per exporter/eraser/purge batch.
	 *
	 * @var int
	 */
	const BATCH = 50;

	/**
	 * Register the hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register_post_type' ) );
		add_action( 'init', array( self::class, 'schedule_purge' ) );
		add_action( self::ACTION_SUBMITTED, array( self::class, 'store' ), 10, 2 );
		add_action( self::CRON_HOOK, array( self::class, 'purge_expired' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'register_eraser' ) );
		add_action( 'admin_init', array( self::class, 'add_privacy_policy_content' ) );
	}

	/**
	 * Register the private submission post type.
	 *
	 * @return void
	 */
	public static function register_post_type(): void {
		$cap = 'manage_options';

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Contact Submissions', 'atlas-theme' ),
					'singular_name' => __( 'Contact Submission', 'atlas-theme' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_in_rest'        => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'menu_icon'           => 'dashicons-email-alt',
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => false,
				'supports'            => array( 'title', 'editor' ),
				'map_meta_cap'        => false,
				// Every operation, including reading, needs manage_options.
				'capabilities'        => array(
					'edit_post'              => $cap,
					'read_post'              => $cap,
					'delete_post'            => $cap,
					'edit_posts'             => $cap,
					'edit_others_posts'      => $cap,
					'delete_posts'           => $cap,
					'publish_posts'          => $cap,
					'read_private_posts'     => $cap,
					'delete_private_posts'   => $cap,
					'delete_published_posts' => $cap,
					'delete_others_posts'    => $cap,
					'edit_private_posts'     => $cap,
					'edit_published_posts'   => $cap,
					'create_posts'           => 'do_not_allow',
				),
			)
		);
	}

	/**
	 * Schedule the daily purge if it is not already scheduled.
	 *
	 * @return void
	 */
	public static function schedule_purge(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Resolve the retention window in days (never below 1).
	 *
	 * @return int Days.
	 */
	public static function retention_days(): int {
		return max( 1, (int) apply_filters( self::FILTER_RETENTION_DAYS, self::RETENTION_DAYS_DEFAULT ) );
	}

	/**
	 * Persist an accepted submission.
	 *
	 * @param array $data { name, email, message } already validated and sanitized.
	 * @param bool  $sent Whether wp_mail() accepted the message.
	 * @return int The new post ID, or 0 when storage is disabled or failed.
	 */
	public static function store( $data, $sent = true ): int {
		if ( ! apply_filters( self::FILTER_ENABLED, true ) || ! is_array( $data ) ) {
			return 0;
		}

		$email = strtolower( trim( (string) ( $data['email'] ?? '' ) ) );

		if ( '' === $email || ! is_email( $email ) ) {
			return 0;
		}

		$post_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => self::POST_TYPE,
					'post_status'  => 'private',
					'post_title'   => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
					'post_content' => sanitize_textarea_field( (string) ( $data['message'] ?? '' ) ),
					'meta_input'   => array(
						self::META_EMAIL  => $email,
						self::META_PHONE  => sanitize_text_field( (string) ( $data['phone'] ?? '' ) ),
						self::META_STATUS => $sent ? 'sent' : 'mail_failed',
					),
				)
			)
		);

		return is_int( $post_id ) && $post_id > 0 ? $post_id : 0;
	}

	/**
	 * Delete submissions older than the retention window.
	 *
	 * @return int Number of submissions removed in this run.
	 */
	public static function purge_expired(): int {
		$cutoff  = gmdate( 'Y-m-d H:i:s', time() - ( self::retention_days() * DAY_IN_SECONDS ) );
		$removed = 0;
		$fetched = 0;

		do {
			$ids = get_posts(
				array(
					'post_type'      => self::POST_TYPE,
					'post_status'    => 'any',
					'fields'         => 'ids',
					'posts_per_page' => self::BATCH,
					'no_found_rows'  => true,
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'date_query'     => array(
						array(
							'column' => 'post_date_gmt',
							'before' => $cutoff,
						),
					),
				)
			);

			$fetched = count( $ids );
			$pass    = 0;

			foreach ( $ids as $id ) {
				if ( wp_delete_post( (int) $id, true ) ) {
					++$pass;
				}
			}

			$removed += $pass;

			// A pass that deleted nothing would refetch the same rows forever.
			if ( 0 === $pass ) {
				break;
			}
		} while ( self::BATCH === $fetched );

		return $removed;
	}

	/**
	 * Add this theme's exporter to core's personal-data export tool.
	 *
	 * @param array $exporters Registered exporters.
	 * @return array Exporters including this one.
	 */
	public static function register_exporter( $exporters ) {
		$exporters = is_array( $exporters ) ? $exporters : array();

		$exporters['atlas-contact-submissions'] = array(
			'exporter_friendly_name' => __( 'Contact form submissions', 'atlas-theme' ),
			'callback'               => array( self::class, 'export' ),
		);

		return $exporters;
	}

	/**
	 * Add this theme's eraser to core's personal-data erasure tool.
	 *
	 * @param array $erasers Registered erasers.
	 * @return array Erasers including this one.
	 */
	public static function register_eraser( $erasers ) {
		$erasers = is_array( $erasers ) ? $erasers : array();

		$erasers['atlas-contact-submissions'] = array(
			'eraser_friendly_name' => __( 'Contact form submissions', 'atlas-theme' ),
			'callback'             => array( self::class, 'erase' ),
		);

		return $erasers;
	}

	/**
	 * Find submission IDs belonging to an email address.
	 *
	 * @param string $email Email address.
	 * @param int    $page  1-based page.
	 * @return int[] IDs.
	 */
	private static function ids_for_email( string $email, int $page ): array {
		$email = strtolower( trim( $email ) );

		if ( '' === $email ) {
			return array();
		}

		return array_map(
			'intval',
			get_posts(
				array(
					'post_type'      => self::POST_TYPE,
					'post_status'    => 'any',
					'fields'         => 'ids',
					'posts_per_page' => self::BATCH,
					'paged'          => max( 1, $page ),
					'no_found_rows'  => true,
					'orderby'        => 'ID',
					'order'          => 'ASC',
					// Bounded: scoped to one private post type, retention keeps it small, batched.
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'   => self::META_EMAIL,
							'value' => $email,
						),
					),
				)
			)
		);
	}

	/**
	 * Personal-data exporter callback.
	 *
	 * @param string $email Email address being exported.
	 * @param int    $page  1-based page.
	 * @return array{data: array, done: bool} Export items and completion flag.
	 */
	public static function export( $email, $page = 1 ): array {
		$ids   = self::ids_for_email( (string) $email, (int) $page );
		$items = array();

		foreach ( $ids as $id ) {
			$post = get_post( $id );

			if ( ! $post ) {
				continue;
			}

			$items[] = array(
				'group_id'    => 'atlas-contact-submissions',
				'group_label' => __( 'Contact form submissions', 'atlas-theme' ),
				'item_id'     => 'atlas-submission-' . $id,
				'data'        => array(
					array(
						'name'  => __( 'Name', 'atlas-theme' ),
						'value' => (string) $post->post_title,
					),
					array(
						'name'  => __( 'Email', 'atlas-theme' ),
						'value' => (string) get_post_meta( $id, self::META_EMAIL, true ),
					),
					array(
						'name'  => __( 'Message', 'atlas-theme' ),
						'value' => (string) $post->post_content,
					),
					array(
						'name'  => __( 'Submitted (GMT)', 'atlas-theme' ),
						'value' => (string) $post->post_date_gmt,
					),
				),
			);
		}

		return array(
			'data' => $items,
			'done' => count( $ids ) < self::BATCH,
		);
	}

	/**
	 * Personal-data eraser callback.
	 *
	 * Always reads page 1: each pass deletes what it finds, so the next pass sees
	 * the remaining items at the start of the result set.
	 *
	 * @param string $email Email address being erased.
	 * @param int    $page  1-based page (unused by design).
	 * @return array{items_removed: int, items_retained: bool, messages: array, done: bool} Eraser result.
	 */
	public static function erase( $email, $page = 1 ): array {
		unset( $page );

		$ids     = self::ids_for_email( (string) $email, 1 );
		$removed = 0;

		foreach ( $ids as $id ) {
			if ( wp_delete_post( $id, true ) ) {
				++$removed;
			}
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => count( $ids ) < self::BATCH,
		);
	}

	/**
	 * Register suggested privacy-policy text with core's policy guide.
	 *
	 * @return void
	 */
	public static function add_privacy_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$text = sprintf(
			/* translators: %d: retention period in days. */
			__( 'When you submit the contact form we collect your name, email address and message so we can reply. The message is emailed to the site owner and kept in the site database for %d days, then deleted automatically. You can ask for a copy of this data or for it to be erased at any time. The form does not set cookies and does not send your data to third parties.', 'atlas-theme' ),
			self::retention_days()
		);

		wp_add_privacy_policy_content(
			__( 'Atlas theme: contact form', 'atlas-theme' ),
			wp_kses_post( wpautop( $text ) )
		);
	}
}
