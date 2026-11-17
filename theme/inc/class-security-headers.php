<?php
/**
 * HTTP security-headers concern.
 *
 * Emits a conservative, hosting-agnostic set of response headers on public
 * requests through the core `wp_headers` filter: X-Content-Type-Options,
 * X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS (HTTPS only) and a
 * Content-Security-Policy.
 *
 * The CSP ships in REPORT-ONLY mode by default so a third-party plugin's inline
 * script is never silently broken; operators switch to enforcement once the
 * browser console is clean (docs/06-security-and-i18n/01-security-and-data.md §8).
 * The theme's own inline dark-mode script is allowed by its SHA-256 hash (safe
 * behind full-page caches) and core/plugin inline scripts receive the
 * per-request nonce. Every header can be overridden or disabled via filters, and
 * a header already set by the server or a plugin is never overwritten.
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for the security-headers feature.
 */
class Security_Headers {

	/**
	 * Constant that may pin the CSP mode from wp-config.php.
	 *
	 * @var string
	 */
	const CONSTANT_CSP_MODE = 'ATLAS_CSP_MODE';

	/**
	 * Filter: CSP mode. One of MODE_OFF, MODE_REPORT_ONLY, MODE_ENFORCE.
	 *
	 * @var string
	 */
	const FILTER_CSP_MODE = 'atlas_csp_mode';

	/**
	 * Filter: the CSP directives array (name => array of sources).
	 *
	 * @var string
	 */
	const FILTER_CSP_DIRECTIVES = 'atlas_csp_directives';

	/**
	 * Filter: the full headers array this class adds (name => value).
	 *
	 * @var string
	 */
	const FILTER_HEADERS = 'atlas_security_headers';

	/**
	 * Filter: the Strict-Transport-Security value. Return '' to disable.
	 *
	 * @var string
	 */
	const FILTER_HSTS = 'atlas_hsts_header';

	/**
	 * CSP disabled.
	 *
	 * @var string
	 */
	const MODE_OFF = 'off';

	/**
	 * CSP reported but not enforced (default).
	 *
	 * @var string
	 */
	const MODE_REPORT_ONLY = 'report-only';

	/**
	 * CSP enforced.
	 *
	 * @var string
	 */
	const MODE_ENFORCE = 'enforce';

	/**
	 * Default HSTS value: one year, no includeSubDomains/preload (opt-in).
	 *
	 * To add includeSubDomains and/or preload, use the atlas_hsts_header filter:
	 *   add_filter( 'atlas_hsts_header', fn() => 'max-age=31536000; includeSubDomains; preload' );
	 *
	 * WARNING: preload is a permanent, browser-vendor-side commitment. Ensure ALL
	 * subdomains serve HTTPS before enabling it. See https://hstspreload.org/.
	 *
	 * @var string
	 */
	const HSTS_DEFAULT = 'max-age=31536000';

	/**
	 * Register the hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'wp_headers', array( self::class, 'filter_headers' ), 10, 1 );
		add_filter( 'wp_inline_script_attributes', array( self::class, 'add_nonce_attribute' ), 10, 1 );
		add_filter( 'wp_script_attributes', array( self::class, 'add_nonce_attribute' ), 10, 1 );
	}

	/**
	 * Add the security headers to the outgoing header array.
	 *
	 * Existing headers (case-insensitive) always win so a server, CDN or plugin
	 * that already sets one is never overwritten.
	 *
	 * @param array<string,string> $headers Headers WordPress is about to send.
	 * @return array<string,string> Headers with the security headers merged in.
	 */
	public static function filter_headers( $headers ) {
		$headers = is_array( $headers ) ? $headers : array();

		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return $headers;
		}

		$additions = array(
			'X-Content-Type-Options' => 'nosniff',
			'X-Frame-Options'        => 'SAMEORIGIN',
			'Referrer-Policy'        => 'strict-origin-when-cross-origin',
			'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=(), payment=(self)',
		);

		if ( function_exists( 'is_ssl' ) && is_ssl() ) {
			$hsts = (string) apply_filters( self::FILTER_HSTS, self::HSTS_DEFAULT );
			if ( '' !== $hsts ) {
				$additions['Strict-Transport-Security'] = $hsts;
			}
		}

		$mode = self::csp_mode();
		if ( self::MODE_OFF !== $mode ) {
			$name               = self::MODE_ENFORCE === $mode ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only';
			$additions[ $name ] = self::build_csp();
		}

		/**
		 * Filters the security headers the theme adds.
		 *
		 * @param array<string,string> $additions Header name => value.
		 */
		$additions = (array) apply_filters( self::FILTER_HEADERS, $additions );

		$existing = array_change_key_case( $headers, CASE_LOWER );

		foreach ( $additions as $name => $value ) {
			$name = (string) $name;
			if ( '' === $name || '' === (string) $value || isset( $existing[ strtolower( $name ) ] ) ) {
				continue;
			}
			// Reject CR/LF so a filtered value can never split the response.
			if ( preg_match( '/[\r\n]/', $name . $value ) ) {
				continue;
			}
			$headers[ $name ] = (string) $value;
		}

		return $headers;
	}

	/**
	 * Resolve the active CSP mode (constant, then filter), defaulting safely.
	 *
	 * Default selection order:
	 *  1. If ATLAS_CSP_MODE constant is defined in wp-config.php, use it.
	 *  2. Otherwise use MODE_ENFORCE for production (WP_DEBUG off, WP_ENVIRONMENT_TYPE
	 *     is 'production' or unset) and MODE_REPORT_ONLY for development/staging, so
	 *     local development never hard-blocks a violated rule while production is
	 *     always protected. Operators may override via the atlas_csp_mode filter.
	 *
	 * To pin a specific mode regardless of environment:
	 *   define( 'ATLAS_CSP_MODE', 'enforce' );   // in wp-config.php
	 *   define( 'ATLAS_CSP_MODE', 'report-only' );
	 *   define( 'ATLAS_CSP_MODE', 'off' );
	 *
	 * @return string One of the MODE_* constants.
	 */
	public static function csp_mode(): string {
		if ( defined( self::CONSTANT_CSP_MODE ) ) {
			$mode = (string) constant( self::CONSTANT_CSP_MODE );
		} else {
			// Auto-detect: enforce on production, report-only elsewhere.
			$env  = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
			$mode = ( 'production' === $env && ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) )
				? self::MODE_ENFORCE
				: self::MODE_REPORT_ONLY;
		}

		/**
		 * Filters the active CSP mode.
		 *
		 * @param string $mode One of 'off', 'report-only', 'enforce'.
		 */
		$mode = (string) apply_filters( self::FILTER_CSP_MODE, $mode );

		return in_array( $mode, array( self::MODE_OFF, self::MODE_REPORT_ONLY, self::MODE_ENFORCE ), true )
			? $mode
			: self::MODE_REPORT_ONLY;
	}

	/**
	 * Build the Content-Security-Policy header value.
	 *
	 * @return string The serialized policy.
	 */
	public static function build_csp(): string {
		$hash  = "'sha256-" . base64_encode( hash( 'sha256', Assets::theme_mode_script(), true ) ) . "'";
		$nonce = "'nonce-" . Assets::get_csp_nonce() . "'";

		$directives = array(
			'default-src'     => array( "'self'" ),
			'script-src'      => array( "'self'", $nonce, $hash ),
			// Core emits inline block-support styles; styles cannot run code.
			'style-src'       => array( "'self'", "'unsafe-inline'" ),
			'img-src'         => array( "'self'", 'data:', 'https:' ),
			'font-src'        => array( "'self'", 'data:' ),
			'connect-src'     => array( "'self'" ),
			'object-src'      => array( "'none'" ),
			'base-uri'        => array( "'self'" ),
			'form-action'     => array( "'self'" ),
			'frame-ancestors' => array( "'self'" ),
			'frame-src'       => array( "'self'", 'https://www.google.com', 'https://maps.google.com' ),
		);

		/**
		 * Filters the CSP directives (directive => list of sources).
		 *
		 * @param array<string,string[]> $directives The default directives.
		 */
		$directives = (array) apply_filters( self::FILTER_CSP_DIRECTIVES, $directives );

		$parts = array();
		foreach ( $directives as $directive => $sources ) {
			$sources = array_filter( array_map( 'strval', (array) $sources ), 'strlen' );
			$parts[] = trim( $directive . ' ' . implode( ' ', $sources ) );
		}

		return implode( '; ', $parts );
	}

	/**
	 * Add the per-request CSP nonce to a core-emitted script tag.
	 *
	 * Only applies when a CSP is active, and never overrides an existing nonce.
	 *
	 * @param array<string,mixed> $attributes Script tag attributes.
	 * @return array<string,mixed> Attributes with a nonce when applicable.
	 */
	public static function add_nonce_attribute( $attributes ) {
		if ( ! is_array( $attributes ) || isset( $attributes['nonce'] ) ) {
			return $attributes;
		}

		if ( ( function_exists( 'is_admin' ) && is_admin() ) || self::MODE_OFF === self::csp_mode() ) {
			return $attributes;
		}

		$attributes['nonce'] = Assets::get_csp_nonce();

		return $attributes;
	}
}
