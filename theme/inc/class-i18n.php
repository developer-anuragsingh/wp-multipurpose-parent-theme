<?php
/**
 * Internationalization and language switcher concern.
 *
 * Owns the Atlas theme's front-end localization, visitor language switching,
 * locale cookie persistence, hreflang SEO integration, and accessible
 * language selector UI in the header.
 *
 * Defaults to en_US and hi_IN, extensible via the atlas_supported_locales
 * filter. Resolved via the hand-rolled autoloader (Atlas\I18n -> inc/class-i18n.php).
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for the i18n and language switching feature.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object.
 */
class I18n {

	/**
	 * Cookie name used to persist the visitor's chosen locale.
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'atlas_locale';

	/**
	 * Query parameter key used to switch language via URL.
	 *
	 * @var string
	 */
	const QUERY_VAR = 'lang';

	/**
	 * Filter hook to override or extend the supported locales map.
	 *
	 * @var string
	 */
	const FILTER_SUPPORTED_LOCALES = 'atlas_supported_locales';

	/**
	 * Default fallback locale.
	 *
	 * @var string
	 */
	const DEFAULT_LOCALE = 'en_US';

	/**
	 * Register the i18n WordPress hooks.
	 *
	 * Called once from functions.php. Hooks determine_locale, hreflang alternates,
	 * header template part filtering, and the language switcher shortcode.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'determine_locale', array( self::class, 'filter_locale' ) );
		add_filter( 'atlas_seo_hreflang_locales', array( self::class, 'filter_hreflang' ) );
		add_filter( 'render_block_core/template-part', array( self::class, 'filter_header_template_part' ), 10, 2 );
		add_shortcode( 'atlas_language_switcher', array( self::class, 'render_switcher' ) );
	}

	/**
	 * Retrieve the map of supported locales and their metadata.
	 *
	 * Includes code, short name (e.g. EN, HI), label, hreflang code, and native label.
	 *
	 * @return array<string, array<string, string>> Supported locales map.
	 */
	public static function get_supported_locales(): array {
		$default = array(
			'en_US' => array(
				'code'     => 'en_US',
				'short'    => 'EN',
				'label'    => 'English',
				'hreflang' => 'en',
				'native'   => 'English',
			),
			'hi_IN' => array(
				'code'     => 'hi_IN',
				'short'    => 'HI',
				'label'    => 'Hindi',
				'hreflang' => 'hi',
				'native'   => 'हिन्दी',
			),
		);

		return (array) apply_filters( self::FILTER_SUPPORTED_LOCALES, $default );
	}

	/**
	 * Normalize and match a candidate string against the supported locales.
	 *
	 * Accepts full locale code ('hi_IN'), short code ('hi', 'HI'), or hreflang ('hi').
	 *
	 * @param string                               $input     Candidate locale string.
	 * @param array<string, array<string, string>> $supported Supported locales map.
	 * @return string|null Matched full locale code, or null when invalid.
	 */
	public static function match_locale( string $input, array $supported ): ?string {
		if ( isset( $supported[ $input ] ) ) {
			return $input;
		}

		$input_lower = strtolower( trim( $input ) );

		foreach ( $supported as $code => $info ) {
			if ( strtolower( $code ) === $input_lower ) {
				return $code;
			}
			if ( isset( $info['short'] ) && strtolower( $info['short'] ) === $input_lower ) {
				return $code;
			}
			if ( isset( $info['hreflang'] ) && strtolower( $info['hreflang'] ) === $input_lower ) {
				return $code;
			}
		}

		return null;
	}

	/**
	 * Resolve the current active locale for the request.
	 *
	 * @return string The resolved locale code.
	 */
	public static function get_current_locale(): string {
		$supported = self::get_supported_locales();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only public GET parameter for locale selection.
		if ( isset( $_GET[ self::QUERY_VAR ] ) && is_string( $_GET[ self::QUERY_VAR ] ) ) {
			$matched = self::match_locale( sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VAR ] ) ), $supported );
			if ( $matched ) {
				return $matched;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) && is_string( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			$matched = self::match_locale( sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ), $supported );
			if ( $matched ) {
				return $matched;
			}
		}

		if ( function_exists( 'get_locale' ) ) {
			$current = (string) get_locale();
			if ( isset( $supported[ $current ] ) ) {
				return $current;
			}
		}

		return self::DEFAULT_LOCALE;
	}

	/**
	 * Filter WordPress determine_locale based on URL param or cookie.
	 *
	 * Validates input against supported locales to prevent unauthorized injection.
	 * Sets the persistence cookie when changed via query param.
	 *
	 * @param string $locale Default determined locale.
	 * @return string The validated locale.
	 */
	public static function filter_locale( string $locale ): string {
		$supported = self::get_supported_locales();

		// 1. URL parameter takes precedence and updates the cookie.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only public GET parameter for locale selection.
		if ( isset( $_GET[ self::QUERY_VAR ] ) && is_string( $_GET[ self::QUERY_VAR ] ) ) {
			$candidate = sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VAR ] ) );
			$matched   = self::match_locale( $candidate, $supported );

			if ( $matched ) {
				self::set_locale_cookie( $matched );
				return $matched;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// 2. Cookie persistence.
		if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) && is_string( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			$candidate = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
			$matched   = self::match_locale( $candidate, $supported );

			if ( $matched ) {
				return $matched;
			}
		}

		return $locale;
	}

	/**
	 * Supply hreflang alternate URLs for SEO meta integration.
	 *
	 * @param array<string, string> $alternates Existing hreflang alternates map.
	 * @return array<string, string> Enriched alternates map.
	 */
	public static function filter_hreflang( array $alternates ): array {
		$supported   = self::get_supported_locales();
		$current_url = self::current_request_url();

		foreach ( $supported as $code => $info ) {
			$hreflang                = isset( $info['hreflang'] ) ? $info['hreflang'] : $code;
			$url                     = add_query_arg( self::QUERY_VAR, $code, $current_url );
			$alternates[ $hreflang ] = esc_url_raw( $url );
		}

		// Self-referential x-default points to clean URL without query var.
		$alternates['x-default'] = esc_url_raw( remove_query_arg( self::QUERY_VAR, $current_url ) );

		return $alternates;
	}

	/**
	 * Render the language selector HTML markup.
	 *
	 * Accessible navigation landmark with semantic links, current language indicator,
	 * and fail-open progressive enhancement.
	 *
	 * @return string HTML markup for the language switcher.
	 */
	public static function render_switcher(): string {
		$supported      = self::get_supported_locales();
		$current_locale = self::get_current_locale();
		$current_url    = self::current_request_url();

		$output = '<nav class="atlas-lang-switcher" aria-label="' . esc_attr__( 'Language selector', 'atlas-theme' ) . '">';
		$index  = 0;
		$total  = count( $supported );

		foreach ( $supported as $code => $info ) {
			$is_active = ( $code === $current_locale );
			$url       = esc_url( add_query_arg( self::QUERY_VAR, $code, $current_url ) );
			$short     = isset( $info['short'] ) ? $info['short'] : $code;
			$hreflang  = isset( $info['hreflang'] ) ? $info['hreflang'] : $code;
			$native    = isset( $info['native'] ) ? $info['native'] : $short;

			$classes = 'atlas-lang-switcher__item';
			if ( $is_active ) {
				$classes .= ' is-active';
			}

			$current_attr = $is_active ? ' aria-current="true"' : '';

			$output .= sprintf(
				'<a href="%1$s" class="%2$s" data-lang="%3$s" hreflang="%4$s" lang="%4$s"%5$s>%6$s</a>',
				$url,
				esc_attr( $classes ),
				esc_attr( $code ),
				esc_attr( $hreflang ),
				$current_attr,
				esc_html( $native )
			);

			++$index;
			if ( $index < $total ) {
				$output .= '<span class="atlas-lang-switcher__separator" aria-hidden="true">/</span>';
			}
		}

		$output .= '</nav>';

		return $output;
	}

	/**
	 * Inject dynamic active state and current URLs into the header template part.
	 *
	 * Keeps static FSE header parts in sync with the active visitor locale.
	 *
	 * @param string $content Rendered block content.
	 * @param array  $block   Parsed block information.
	 * @return string Updated block content.
	 */
	public static function filter_header_template_part( string $content, array $block ): string {
		if ( ! isset( $block['attrs']['slug'] ) || 'header' !== $block['attrs']['slug'] ) {
			return $content;
		}

		if ( ! str_contains( $content, 'atlas-lang-switcher' ) ) {
			return $content;
		}

		// Replace static switcher placeholder with dynamically resolved switcher.
		$pattern = '/<nav\b[^>]*class=["\'][^"\']*atlas-lang-switcher[^"\']*["\'][^>]*>.*?<\/nav>/is';

		if ( preg_match( $pattern, $content ) ) {
			$replacement = self::render_switcher();
			return (string) preg_replace( $pattern, $replacement, $content, 1 );
		}

		return $content;
	}

	/**
	 * Set the locale cookie on public GET responses.
	 *
	 * @param string $locale Validated locale code.
	 * @return void
	 */
	private static function set_locale_cookie( string $locale ): void {
		if ( headers_sent() ) {
			return;
		}

		$lifetime = defined( 'YEAR_IN_SECONDS' ) ? (int) YEAR_IN_SECONDS : 31536000;
		$path     = defined( 'COOKIEPATH' ) && is_string( COOKIEPATH ) && '' !== COOKIEPATH ? COOKIEPATH : '/';
		$domain   = defined( 'COOKIE_DOMAIN' ) && is_string( COOKIE_DOMAIN ) ? COOKIE_DOMAIN : '';
		$secure   = function_exists( 'is_ssl' ) ? is_ssl() : false;

		setcookie(
			self::COOKIE_NAME,
			$locale,
			array(
				'expires'  => time() + $lifetime,
				'path'     => $path,
				'domain'   => $domain,
				'secure'   => $secure,
				'httponly' => false,
				'samesite' => 'Lax',
			)
		);
	}

	/**
	 * Resolve the current request URL for canonical hreflang and query building.
	 *
	 * @return string Absolute URL for current request.
	 */
	private static function current_request_url(): string {
		if ( function_exists( 'wp_get_canonical_url' ) ) {
			$canonical = wp_get_canonical_url();
			if ( is_string( $canonical ) && '' !== $canonical ) {
				return $canonical;
			}
		}

		if ( function_exists( 'is_singular' ) && is_singular() && function_exists( 'get_permalink' ) ) {
			$permalink = get_permalink();
			if ( is_string( $permalink ) && '' !== $permalink ) {
				return $permalink;
			}
		}

		return function_exists( 'home_url' ) ? home_url( '/' ) : 'https://example.com/';
	}
}
