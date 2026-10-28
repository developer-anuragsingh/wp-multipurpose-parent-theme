<?php
/**
 * Speculation Rules API tuning concern.
 *
 * Tunes WordPress Core 6.8+ Speculative Loading via the official
 * 'wp_speculation_rules_configuration' filter. The theme never hand-injects
 * speculationrules script tags; it adjusts Core's configuration to ensure
 * safe, high-performance prefetching with moderate eagerness and strictly
 * excludes dynamic, non-idempotent, and commerce routes (/cart/*, /checkout/*,
 * /my-account/*, and *add-to-cart=*).
 *
 * It suppresses speculative loading entirely when maintenance mode is active
 * so browsers never prefetch 503 error responses (ties to docs/04-modern-wp-features/02-speculation-rules.md).
 *
 * Mirrors Atlas\Robots and Atlas\Seo precisely — a static registrar wired from
 * functions.php after Atlas\Llms_Txt::init(). Resolved via the hand-rolled
 * autoloader (Atlas\Speculation_Rules -> inc/class-speculation-rules.php).
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for Speculation Rules configuration.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object. All public surface is reachable from the
 * WordPress hook wired in init().
 */
class Speculation_Rules {

	/**
	 * Master gate filter: enable Atlas Speculation Rules tuning.
	 *
	 * Defaults true. A site may hard-disable the theme's tuning filter by
	 * returning false, reverting to untouched core defaults.
	 *
	 * @var string
	 */
	const FILTER_ENABLED = 'atlas_speculation_rules_enabled';

	/**
	 * Filter name an operator uses to augment or override excluded href paths.
	 *
	 * Receives the array of path patterns and returns the sanitized list.
	 *
	 * @var string
	 */
	const FILTER_EXCLUDED_PATHS = 'atlas_speculation_rules_excluded_paths';

	/**
	 * Filter name to force-suppress speculation rules.
	 *
	 * Defaults false. When truthy, speculation rules are emptied.
	 *
	 * @var string
	 */
	const FILTER_SUPPRESSED = 'atlas_speculation_rules_suppressed';

	/**
	 * Maintenance mode constant name checked for suppression.
	 *
	 * @var string
	 */
	const MAINTENANCE_CONSTANT = 'ATLAS_MAINTENANCE';

	/**
	 * Maintenance mode filter name checked for suppression.
	 *
	 * @var string
	 */
	const FILTER_MAINTENANCE = 'atlas_seo_maintenance';

	/**
	 * Default dynamic and commerce routes excluded from background prefetching.
	 *
	 * Non-idempotent or shopper-specific routes that must never be prefetched.
	 *
	 * @var string[]
	 */
	const DEFAULT_EXCLUDED_PATHS = array(
		'/cart/*',
		'/checkout/*',
		'/my-account/*',
		'/*add-to-cart=*',
	);

	/**
	 * Register the Speculation Rules WordPress hooks.
	 *
	 * Called once from functions.php after Atlas\Llms_Txt::init(). Wires the
	 * core 'wp_speculation_rules_configuration' filter.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'wp_speculation_rules_configuration', array( self::class, 'filter_configuration' ), 10, 1 );
	}

	/**
	 * Tune WordPress Core's speculation rules configuration.
	 *
	 * Sets prefetch mode and moderate eagerness, adds commerce and dynamic
	 * route exclusions, and suppresses rules completely during maintenance.
	 *
	 * @param array|false $config Core's speculation rules configuration array.
	 * @return array|false Filtered configuration array, or false to suppress.
	 */
	public static function filter_configuration( $config ) {
		if ( ! is_array( $config ) ) {
			return $config;
		}

		if ( ! (bool) apply_filters( self::FILTER_ENABLED, true ) ) {
			return $config;
		}

		if ( self::is_suppressed() ) {
			return false;
		}

		$config['mode']      = 'prefetch';
		$config['eagerness'] = 'moderate';

		$existing_exclusions = isset( $config['href_exclude_paths'] ) && is_array( $config['href_exclude_paths'] )
			? $config['href_exclude_paths']
			: array();

		/**
		 * Filter the paths excluded from speculative loading.
		 *
		 * @param string[] $excluded_paths Array of path wildcard strings to exclude.
		 */
		$filtered_exclusions = apply_filters( self::FILTER_EXCLUDED_PATHS, self::DEFAULT_EXCLUDED_PATHS );

		if ( ! is_array( $filtered_exclusions ) ) {
			$filtered_exclusions = self::DEFAULT_EXCLUDED_PATHS;
		}

		$merged_exclusions = array_unique( array_merge( $existing_exclusions, $filtered_exclusions ) );

		$config['href_exclude_paths'] = array_values( array_filter( array_map( 'sanitize_text_field', $merged_exclusions ) ) );

		return $config;
	}

	/**
	 * Determine whether speculative loading should be suppressed.
	 *
	 * Suppressed when maintenance mode is active (via constant or filter) or
	 * when explicitly suppressed via the filter.
	 *
	 * @return bool True when speculation rules must be suppressed.
	 */
	private static function is_suppressed(): bool {
		$by_constant    = defined( self::MAINTENANCE_CONSTANT ) && (bool) constant( self::MAINTENANCE_CONSTANT );
		$by_maintenance = $by_constant || (bool) apply_filters( self::FILTER_MAINTENANCE, false );

		return $by_maintenance || (bool) apply_filters( self::FILTER_SUPPRESSED, false );
	}
}
