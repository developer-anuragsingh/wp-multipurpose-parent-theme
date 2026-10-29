<?php
/**
 * Contact-form concern.
 *
 * Owns the Atlas theme's first custom write-capable REST route and the reference
 * implementation of the full form + server-side security model: the public
 * contact-form submission endpoint (atlas/v1/contact), the Interactivity store
 * seeding, the conditional enqueue of the built contact-form module/stylesheet
 * where the pattern renders, and the block-pattern registration. Mirrors
 * Atlas\UI_States / Atlas\Assets precisely — a static registrar wired from
 * functions.php after Atlas\Bindings::init(). Resolved via the hand-rolled
 * autoloader (Atlas\ -> inc/).
 *
 * Email delivery is wp_mail() only. Persisting submissions (a submissions store
 * + core privacy export/erasure hooks + a retention window per
 * docs/06-security-and-i18n/03-data-protection.md §7), file uploads, and CAPTCHA
 * are explicitly OUT OF SCOPE for this first cut and noted here as future work.
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for the contact-form feature.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object. All public surface is reachable from the
 * WordPress hooks wired in init().
 */
class Contact_Form {

	/**
	 * REST namespace the contact route registers under.
	 *
	 * Reuses the same versioned namespace UI_States registers under; this is a
	 * new route within atlas/v1, not a new namespace.
	 *
	 * @var string
	 */
	const REST_NAMESPACE = 'atlas/v1';

	/**
	 * REST route path (relative to self::REST_NAMESPACE). Full endpoint is
	 * atlas/v1/contact.
	 *
	 * @var string
	 */
	const REST_ROUTE = '/contact';

	/**
	 * Interactivity store namespace for the contact form.
	 *
	 * A new, independent store (distinct from atlas/ui-states).
	 *
	 * @var string
	 */
	const STORE = 'atlas/contact-form';

	/**
	 * Script module handle for the built contact-form module.
	 *
	 * @var string
	 */
	const MODULE_HANDLE = 'atlas-contact-form';

	/**
	 * Build slug shared by the module, asset manifest, and stylesheet.
	 *
	 * Keeps build/contact-form.js, build/contact-form.asset.php, and
	 * build/contact-form.css in lockstep from one source of truth.
	 *
	 * @var string
	 */
	const BUILD_SLUG = 'contact-form';

	/**
	 * Stylesheet handle for the built contact-form stylesheet.
	 *
	 * @var string
	 */
	const STYLE_HANDLE = 'atlas-contact-form';

	/**
	 * Nonce action for the contact form's intent check.
	 *
	 * The core REST nonce action: core verifies it automatically for logged-in
	 * REST via the X-WP-Nonce header, but this public form mints and re-verifies
	 * it itself as one of the compensating controls (see handle_request()).
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'wp_rest';

	/**
	 * Honeypot input name. Must be empty for a submission to pass.
	 *
	 * @var string
	 */
	const HONEYPOT_FIELD = 'atlas_contact_hp';

	/**
	 * Hidden timing field name. Carries the unix-seconds render timestamp.
	 *
	 * @var string
	 */
	const TIMING_FIELD = 'atlas_contact_ts';

	/**
	 * Minimum seconds between render and submit. Faster than this is treated as
	 * a bot and silently rejected.
	 *
	 * @var int
	 */
	const MIN_SUBMIT_SECONDS = 3;

	/**
	 * Filter hook name for resolving the client IP address.
	 *
	 * Allows operators behind trusted reverse proxies (e.g. AWS ALB, Cloudflare,
	 * Fastly, Nginx) to provide the verified client IP.
	 *
	 * @var string
	 */
	const FILTER_CLIENT_IP = 'atlas_client_ip';

	/**
	 * Filter hook name for overriding the contact form rate limit cap.
	 *
	 * @var string
	 */
	const FILTER_RATE_LIMIT = 'atlas_contact_form_rate_limit';

	/**
	 * Default maximum submissions per IP per window. Filterable via
	 * self::FILTER_RATE_LIMIT.
	 *
	 * @var int
	 */
	const RATE_LIMIT_DEFAULT = 5;

	/**
	 * Rate-limit window in seconds (10 minutes). Also the Retry-After hint and
	 * the counter transient TTL.
	 *
	 * @var int
	 */
	const RATE_LIMIT_WINDOW = 600;

	/**
	 * Per-IP rate-limit counter transient key prefix (the IP is hashed into the
	 * key, never stored in the clear).
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'atlas_cf_rl_';

	/**
	 * Block pattern category slug (registered by UI_States, reused here — this
	 * class never re-registers it).
	 *
	 * @var string
	 */
	const CATEGORY = 'atlas';

	/**
	 * Pattern slug for the contact-form pattern.
	 *
	 * @var string
	 */
	const PATTERN_SLUG = 'atlas/contact-form';

	/**
	 * Pattern file (relative to the theme's patterns/ directory).
	 *
	 * @var string
	 */
	const PATTERN_FILE = 'contact-form.php';

	/**
	 * Stable wrapper class the pattern renders; the single key
	 * page_has_contact_form() detects to enqueue the module only where the form
	 * actually appears.
	 *
	 * @var string
	 */
	const WRAPPER_CLASS = 'atlas-contact-form';

	/**
	 * Maximum accepted length of the name field, in characters. Shared by the
	 * args schema (validate) and the pattern markup (maxlength).
	 *
	 * @var int
	 */
	const NAME_MAX = 100;

	/**
	 * Maximum accepted length of the email field, in characters.
	 *
	 * @var int
	 */
	const EMAIL_MAX = 254;

	/**
	 * Minimum accepted length of the message field, in characters.
	 *
	 * @var int
	 */
	const MESSAGE_MIN = 10;

	/**
	 * Maximum accepted length of the message field, in characters.
	 *
	 * @var int
	 */
	const MESSAGE_MAX = 5000;

	/**
	 * Register the contact-form WordPress hooks.
	 *
	 * Called once from functions.php immediately after Atlas\Bindings::init().
	 * Keeps all contact-form hook wiring in one place so the boot order is
	 * explicit.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register_block_pattern' ) );
		add_action( 'rest_api_init', array( self::class, 'register_rest_route' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ) );
		add_filter( 'rest_post_dispatch', array( self::class, 'add_retry_after_header' ), 10, 3 );
	}

	/**
	 * Attach a Retry-After header to the rate-limited (429) response.
	 *
	 * A WP_Error carrying a 429 status does not emit Retry-After on its own, so
	 * this late filter adds it when our rate-limit error is the result for the
	 * contact route. Scoped to this route so it never touches other endpoints.
	 *
	 * @param \WP_HTTP_Response $result  The response about to be served.
	 * @param \WP_REST_Server   $server  The REST server instance (unused).
	 * @param \WP_REST_Request  $request The request being answered.
	 * @return \WP_HTTP_Response The response, with Retry-After when rate-limited.
	 */
	public static function add_retry_after_header( $result, $server, $request ) {
		unset( $server );

		if ( ! $result instanceof \WP_HTTP_Response || ! $request instanceof \WP_REST_Request ) {
			return $result;
		}

		// WP_REST_Request::get_route() returns the matched route WITH a leading
		// slash (e.g. '/atlas/v1/contact'); normalize both sides before compare.
		if ( self::REST_NAMESPACE . self::REST_ROUTE !== ltrim( (string) $request->get_route(), '/' ) ) {
			return $result;
		}

		if ( 429 === $result->get_status() ) {
			$result->header( 'Retry-After', (string) self::RATE_LIMIT_WINDOW );
		}

		return $result;
	}

	/**
	 * Register the contact-form block pattern.
	 *
	 * Runs on init. Registers only the pattern — the 'atlas' category is owned
	 * and registered by Atlas\UI_States, so re-registering it here would emit a
	 * notice. A missing pattern file is skipped so a partial theme never fatals.
	 *
	 * @return void
	 */
	public static function register_block_pattern(): void {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		$path = ATLAS_THEME_DIR . '/patterns/' . self::PATTERN_FILE;
		if ( ! file_exists( $path ) ) {
			return;
		}

		$data = get_file_data(
			$path,
			array(
				'title'       => 'Title',
				'description' => 'Description',
				'inserter'    => 'Inserter',
			)
		);

		ob_start();
		require $path;
		$content = (string) ob_get_clean();

		register_block_pattern(
			self::PATTERN_SLUG,
			array(
				'title'       => '' !== $data['title'] ? $data['title'] : __( 'Contact Form', 'atlas-theme' ),
				'description' => $data['description'],
				'categories'  => array( self::CATEGORY ),
				'inserter'    => ! in_array( strtolower( $data['inserter'] ), array( 'no', 'false', '0' ), true ),
				'content'     => $content,
			)
		);
	}

	/**
	 * Register the public contact-form REST route.
	 *
	 * Runs on rest_api_init. The route accepts POST (CREATABLE) only and is
	 * PUBLIC by design — a contact form must accept anonymous submissions, so
	 * permission_callback is '__return_true'. Per agent-rules §2, a public
	 * write route is permitted ONLY with a full set of documented compensating
	 * controls, all enforced inside handle_request() before any mail is sent:
	 *
	 *   1. Intent — the seeded wp_rest nonce is verified (X-WP-Nonce header or
	 *      the _wpnonce body param for the no-JS POST).
	 *   2. Honeypot — a hidden field that must stay empty; a filled one is a bot.
	 *   3. Timing — a submission faster than MIN_SUBMIT_SECONDS is a bot.
	 *   4. Rate limit — per-IP throttling returning HTTP 429 + Retry-After once
	 *      the filterable limit is exceeded.
	 *   5. Validation + sanitization — every field is independently validated
	 *      (validate_callback -> 400 on bad input) and sanitized (wp_unslash +
	 *      the matching WordPress sanitizer) before use.
	 *
	 * The args schema below declares validate_callback AND sanitize_callback for
	 * each visible field, so core returns 400 automatically on invalid input and
	 * the handler only ever sees sanitized values.
	 *
	 * @return void
	 */
	public static function register_rest_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_request' ),
				// Public submission endpoint — see the method PHPDoc for the
				// compensating controls that justify __return_true here.
				'permission_callback' => '__return_true',
				'args'                => self::args_schema(),
			)
		);
	}

	/**
	 * Build the REST args schema (validate + sanitize per field).
	 *
	 * Each visible field (name, email, message) is independently validated and
	 * sanitized; the two anti-abuse fields are declared so they are read from
	 * the request (their checks run in handle_request()). Backend validation is
	 * the source of truth and treats all input as hostile regardless of any
	 * HTML5 / JS checks (agent-rules §4).
	 *
	 * @return array<string, array<string, mixed>> The register_rest_route args.
	 */
	private static function args_schema(): array {
		return array(
			'name'               => array(
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					$value = is_string( $value ) ? trim( $value ) : '';
					return '' !== $value && mb_strlen( $value ) <= self::NAME_MAX;
				},
				'sanitize_callback' => static function ( $value ) {
					return sanitize_text_field( wp_unslash( (string) $value ) );
				},
			),
			'email'              => array(
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					$value = is_string( $value ) ? trim( $value ) : '';
					return mb_strlen( $value ) <= self::EMAIL_MAX && (bool) is_email( $value );
				},
				'sanitize_callback' => static function ( $value ) {
					return sanitize_email( wp_unslash( (string) $value ) );
				},
			),
			'message'            => array(
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					$value  = is_string( $value ) ? trim( $value ) : '';
					$length = mb_strlen( $value );
					return $length >= self::MESSAGE_MIN && $length <= self::MESSAGE_MAX;
				},
				'sanitize_callback' => static function ( $value ) {
					return sanitize_textarea_field( wp_unslash( (string) $value ) );
				},
			),
			self::HONEYPOT_FIELD => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => static function ( $value ) {
					return sanitize_text_field( wp_unslash( (string) $value ) );
				},
			),
			self::TIMING_FIELD   => array(
				'required'          => false,
				'type'              => 'integer',
				'sanitize_callback' => static function ( $value ) {
					return absint( $value );
				},
			),
		);
	}

	/**
	 * Route callback: run the full 5-step security pipeline, then send mail.
	 *
	 * Order (each step fails early): intent (nonce) -> honeypot -> timing ->
	 * rate limit -> re-validate/sanitize -> wp_mail. Returns a GENERIC success
	 * or GENERIC error — never a message that leaks which control tripped (a
	 * caught bot sees a 200 "thanks", not "you were blocked"). No field value is
	 * ever logged (data-protection §6); a wp_mail() failure yields a generic
	 * error with no payload.
	 *
	 * Response mode (D6): the enhanced JS path sends X-Atlas-Enhanced and gets
	 * the JSON contract; a plain no-JS browser POST is processed identically and
	 * answered with a redirect back to the form page carrying ?atlas-contact=
	 * sent|error|invalid so the pattern can render a server-side status message.
	 *
	 * @param \WP_REST_Request $request The incoming request.
	 * @return \WP_REST_Response|\WP_Error The response (or WP_Error for 429).
	 */
	public static function handle_request( \WP_REST_Request $request ) {
		$enhanced = '1' === (string) $request->get_header( 'X-Atlas-Enhanced' );

		// 1. Verify intent: accept the nonce from the header (enhanced) OR the
		// _wpnonce body param (no-JS POST). Not the sole gate — a compensating
		// control per the route PHPDoc.
		$nonce = (string) $request->get_header( 'X-WP-Nonce' );
		if ( '' === $nonce ) {
			$nonce = (string) $request->get_param( '_wpnonce' );
		}
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return self::respond( $enhanced, 'invalid', 403 );
		}

		// 2. Honeypot: a filled hidden field is a bot. Report a generic success
		// so the bot is not told it was caught; send no mail.
		$honeypot = (string) $request->get_param( self::HONEYPOT_FIELD );
		if ( '' !== trim( $honeypot ) ) {
			return self::respond( $enhanced, 'sent', 200 );
		}

		// 3. Timing: a submission faster than MIN_SUBMIT_SECONDS (or with a
		// missing/future timestamp) is a bot. Generic success, no mail.
		$ts  = absint( $request->get_param( self::TIMING_FIELD ) );
		$now = time();
		if ( 0 === $ts || $ts > $now || ( $now - $ts ) < self::MIN_SUBMIT_SECONDS ) {
			return self::respond( $enhanced, 'sent', 200 );
		}

		// 4. Rate limit: per-IP throttle. 429 + Retry-After once the filterable
		// limit is exceeded. REMOTE_ADDR only — proxy headers are untrusted.
		$rate_limited = self::check_rate_limit();
		if ( is_wp_error( $rate_limited ) ) {
			return $rate_limited;
		}

		// 5. Re-read the already-validated-and-sanitized fields. The args schema
		// guarantees these are present, sanitized, and within bounds; re-check
		// defensively so a bypassed schema still fails closed.
		$name    = (string) $request->get_param( 'name' );
		$email   = (string) $request->get_param( 'email' );
		$message = (string) $request->get_param( 'message' );

		if ( '' === trim( $name ) || ! is_email( $email ) || mb_strlen( trim( $message ) ) < self::MESSAGE_MIN ) {
			return self::respond( $enhanced, 'invalid', 400 );
		}

		$sent = self::send_mail( $name, $email, $message );
		if ( ! $sent ) {
			// Generic error only. No field values, no payload, no stack trace.
			return self::respond( $enhanced, 'error', 500 );
		}

		return self::respond( $enhanced, 'sent', 200 );
	}

	/**
	 * Enforce the per-IP rate limit via a transient counter.
	 *
	 * Keys on REMOTE_ADDR (hashed into the transient key) — proxy headers such
	 * as X-Forwarded-For are NOT trusted by default (agent-rules §2); an
	 * operator on a trusted proxy can layer that in via their own filter. The
	 * limit is filterable through atlas_contact_form_rate_limit. Returns a
	 * WP_Error carrying HTTP 429 and a Retry-After header hint when exceeded,
	 * otherwise increments the counter and returns true.
	 *
	 * @return true|\WP_Error True when under the limit, WP_Error (429) when over.
	 */
	private static function check_rate_limit() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';

		/**
		 * Filters the resolved client IP address for rate limiting.
		 *
		 * Defaults to REMOTE_ADDR. Operators behind a trusted reverse proxy (e.g.
		 * AWS ALB, Cloudflare, Fastly, or Nginx) may supply the verified client IP.
		 *
		 * @param string $ip The sanitized client IP address.
		 */
		$ip = (string) apply_filters( self::FILTER_CLIENT_IP, $ip );

		if ( '' === $ip ) {
			// No resolvable IP: do not throttle (fail open on the limiter only;
			// the other four controls still apply).
			return true;
		}

		/**
		 * Filters the maximum contact-form submissions per IP per window.
		 *
		 * @param int $limit The default per-IP submission cap.
		 */
		$limit = (int) apply_filters( self::FILTER_RATE_LIMIT, self::RATE_LIMIT_DEFAULT );

		$key   = self::TRANSIENT_PREFIX . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return new \WP_Error(
				'atlas_contact_rate_limited',
				__( 'Too many requests. Please try again later.', 'atlas-theme' ),
				array(
					'status'            => 429,
					'additional_errors' => array(),
					'retry_after'       => self::RATE_LIMIT_WINDOW,
				)
			);
		}

		set_transient( $key, $count + 1, self::RATE_LIMIT_WINDOW );

		return true;
	}

	/**
	 * Send the contact-form email via wp_mail().
	 *
	 * Recipient defaults to the site admin_email and is filterable via
	 * atlas_contact_form_recipient. The reply-to is the submitter's (sanitized)
	 * email so the recipient can reply directly; the From header is left to the
	 * site default. Subject and body are built from the already-sanitized
	 * fields. Returns the wp_mail() boolean so the caller can emit a generic
	 * error without logging any field value.
	 *
	 * @param string $name    Sanitized submitter name.
	 * @param string $email   Sanitized submitter email.
	 * @param string $message Sanitized submitter message.
	 * @return bool True when wp_mail() accepted the message for delivery.
	 */
	private static function send_mail( string $name, string $email, string $message ): bool {
		/**
		 * Filters the contact-form recipient address.
		 *
		 * @param string $recipient The default recipient (site admin email).
		 */
		$recipient = (string) apply_filters( 'atlas_contact_form_recipient', get_option( 'admin_email' ) );

		if ( ! is_email( $recipient ) ) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s: submitter name. */
			__( 'New contact message from %s', 'atlas-theme' ),
			$name
		);

		$body = sprintf(
			/* translators: 1: submitter name, 2: submitter email, 3: message body. */
			__( "Name: %1\$s\nEmail: %2\$s\n\nMessage:\n%3\$s", 'atlas-theme' ),
			$name,
			$email,
			$message
		);

		$headers = array();
		if ( is_email( $email ) ) {
			$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
		}

		return (bool) wp_mail( $recipient, $subject, $body, $headers );
	}

	/**
	 * Build the mode-appropriate response for a pipeline outcome.
	 *
	 * Enhanced (JS fetch) requests get the stable JSON contract
	 * { status, message } with the given HTTP code so the store can branch on
	 * status. A plain no-JS browser POST is answered with a redirect back to the
	 * referring form page carrying ?atlas-contact=<outcome>, which the pattern
	 * renders as an escaped server-side status message. All messages are generic
	 * and pre-translated; none reveals which control tripped.
	 *
	 * @param bool   $enhanced Whether the request came from the enhanced JS path.
	 * @param string $outcome  One of 'sent', 'invalid', 'error'.
	 * @param int    $status   The HTTP status code to return.
	 * @return \WP_REST_Response The response.
	 */
	private static function respond( bool $enhanced, string $outcome, int $status ): \WP_REST_Response {
		$messages = array(
			'sent'    => __( 'Thanks — your message has been sent.', 'atlas-theme' ),
			'invalid' => __( 'Please check the form and try again.', 'atlas-theme' ),
			'error'   => __( 'Something went wrong. Please try again later.', 'atlas-theme' ),
		);

		$message = isset( $messages[ $outcome ] ) ? $messages[ $outcome ] : $messages['error'];
		$state   = 'sent' === $outcome ? 'success' : 'error';

		if ( $enhanced ) {
			return new \WP_REST_Response(
				array(
					'status'  => $state,
					'message' => $message,
				),
				$status
			);
		}

		// No-JS path: redirect back to the submitting page with an outcome flag.
		$referer  = wp_get_referer();
		$redirect = $referer ? $referer : home_url( '/' );
		$redirect = add_query_arg( 'atlas-contact', $outcome, $redirect );

		$response = new \WP_REST_Response( null, 303 );
		$response->header( 'Location', $redirect );

		return $response;
	}

	/**
	 * Seed the Interactivity store and conditionally enqueue the built assets.
	 *
	 * Runs on wp_enqueue_scripts (front end only, guarded by is_admin()). Seeds
	 * the atlas/contact-form store with the REST URL, a fresh wp_rest nonce, and
	 * the pre-translated gettext labels (so the module performs no runtime i18n),
	 * then registers the built module from its .asset.php and enqueues it + the
	 * built stylesheet ONLY when page_has_contact_form() is true. A missing
	 * .asset.php (build not run) is skipped so the page fails closed to the
	 * working no-JS form rather than fataling.
	 *
	 * @return void
	 */
	public static function register_assets(): void {
		if ( is_admin() || ! self::page_has_contact_form() ) {
			return;
		}

		wp_interactivity_state(
			self::STORE,
			array(
				'restUrl' => rest_url( self::REST_NAMESPACE . self::REST_ROUTE ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'labels'  => array(
					'submitting'   => __( 'Sending your message…', 'atlas-theme' ),
					'success'      => __( 'Thanks — your message has been sent.', 'atlas-theme' ),
					'error'        => __( 'Something went wrong. Please try again later.', 'atlas-theme' ),
					'rateLimited'  => __( 'Too many requests. Please try again later.', 'atlas-theme' ),
					'invalid'      => __( 'Please check the form and try again.', 'atlas-theme' ),
					'nameError'    => __( 'Please enter your name.', 'atlas-theme' ),
					'emailError'   => __( 'Please enter a valid email address.', 'atlas-theme' ),
					'messageError' => __( 'Please enter a message of at least 10 characters.', 'atlas-theme' ),
				),
			)
		);

		$asset_file = ATLAS_THEME_DIR . '/build/' . self::BUILD_SLUG . '.asset.php';
		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;

			wp_register_script_module(
				self::MODULE_HANDLE,
				ATLAS_THEME_URI . '/build/' . self::BUILD_SLUG . '.js',
				isset( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
				isset( $asset['version'] ) ? $asset['version'] : ATLAS_THEME_VERSION
			);

			wp_enqueue_script_module( self::MODULE_HANDLE );
		}

		$style_file = ATLAS_THEME_DIR . '/build/' . self::BUILD_SLUG . '.css';
		if ( file_exists( $style_file ) ) {
			wp_enqueue_style(
				self::STYLE_HANDLE,
				ATLAS_THEME_URI . '/build/' . self::BUILD_SLUG . '.css',
				array( 'atlas-theme' ),
				ATLAS_THEME_VERSION
			);
		}
	}

	/**
	 * Whether the current request renders the contact-form pattern.
	 *
	 * Front-end singular only, fail-closed. Mirrors Assets::page_has_quotes():
	 * a cheap str_contains() pre-check on the stable wrapper class, then a
	 * recursive parse_blocks() walk to confirm the form wrapper actually exists
	 * (so a stray class string elsewhere can never enqueue the module). A synced
	 * pattern (core/block reference) is out of scope for this first cut.
	 *
	 * @return bool True only when the contact-form wrapper is present.
	 */
	public static function page_has_contact_form(): bool {
		if ( is_admin() || ! is_singular() ) {
			return false;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		if ( ! str_contains( $post->post_content, self::WRAPPER_CLASS ) ) {
			return false;
		}

		return self::blocks_have_contact_form( parse_blocks( $post->post_content ) );
	}

	/**
	 * Recursively test parsed blocks for the contact-form wrapper class.
	 *
	 * @param array $blocks Parsed block array from parse_blocks().
	 * @return bool True when a block carries the contact-form wrapper class.
	 */
	private static function blocks_have_contact_form( array $blocks ): bool {
		foreach ( $blocks as $block ) {
			if ( isset( $block['attrs']['className'] )
				&& str_contains( (string) $block['attrs']['className'], self::WRAPPER_CLASS ) ) {
				return true;
			}

			if ( ! empty( $block['innerHTML'] )
				&& str_contains( (string) $block['innerHTML'], self::WRAPPER_CLASS ) ) {
				return true;
			}

			if ( ! empty( $block['innerBlocks'] )
				&& self::blocks_have_contact_form( $block['innerBlocks'] ) ) {
				return true;
			}
		}

		return false;
	}
}
