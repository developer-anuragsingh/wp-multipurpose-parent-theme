<?php
/**
 * 503 Maintenance and Coming-Soon mode controller.
 *
 * Intercepts public requests when maintenance or coming-soon mode is active,
 * emitting an HTTP 503 Service Unavailable status code and a Retry-After header
 * while serving the decoupled maintenance template (templates/maintenance.html).
 *
 * Suppresses Speculation Rules so search engines and browsers never prefetch 503s.
 * Authorised operators with 'manage_options' capability bypass maintenance mode
 * so they can preview and configure the site safely.
 *
 * Defers to WooCommerce's native Coming Soon feature when active to prevent
 * conflicting 503 interventions (per docs/05-niches-and-usecases/01-woocommerce.md).
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for the maintenance and 503 controller.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object. All public surface is reachable from the
 * WordPress hook wired in init().
 */
class Maintenance {

	/**
	 * Constant checked for enabling maintenance mode.
	 *
	 * @var string
	 */
	const CONSTANT_ENABLED = 'ATLAS_MAINTENANCE';

	/**
	 * Master filter to enable or disable maintenance mode.
	 *
	 * @var string
	 */
	const FILTER_ENABLED = 'atlas_maintenance_enabled';

	/**
	 * Filter to customize the Retry-After header duration in seconds.
	 *
	 * @var string
	 */
	const FILTER_RETRY_AFTER = 'atlas_maintenance_retry_after';

	/**
	 * Default Retry-After delay in seconds (1 hour).
	 *
	 * @var int
	 */
	const DEFAULT_RETRY_AFTER = 3600;

	/**
	 * Option key storing the admin maintenance toggle.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'atlas_maintenance_mode';

	/**
	 * Register WordPress hooks.
	 *
	 * Hooks template_redirect at priority 0 so the maintenance interception
	 * occurs before normal template rendering or expensive queries.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( self::class, 'maybe_intercept' ), 0 );
	}

	/**
	 * Determine if maintenance mode is active.
	 *
	 * Evaluates constant, filters, or database option.
	 *
	 * @return bool True if maintenance mode is active.
	 */
	public static function is_active(): bool {
		$by_constant = defined( self::CONSTANT_ENABLED ) && (bool) constant( self::CONSTANT_ENABLED );
		if ( $by_constant ) {
			return true;
		}

		if ( (bool) apply_filters( self::FILTER_ENABLED, false ) ) {
			return true;
		}

		if ( (bool) apply_filters( 'atlas_seo_maintenance', false ) ) {
			return true;
		}

		return (bool) get_option( self::OPTION_KEY, false );
	}

	/**
	 * Intercept public requests and serve the 503 maintenance response.
	 *
	 * @return void
	 */
	public static function maybe_intercept(): void {
		if ( ! self::is_active() ) {
			return;
		}

		// Never intercept WordPress admin, cron, or AJAX requests.
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// Allow administrators and authorized managers to preview the site.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// Coordinate with WooCommerce native coming soon feature.
		if ( class_exists( 'WooCommerce' ) && 'yes' === get_option( 'woocommerce_coming_soon', 'no' ) ) {
			return;
		}

		$retry_after = (int) apply_filters( self::FILTER_RETRY_AFTER, self::DEFAULT_RETRY_AFTER );

		nocache_headers();
		status_header( 503 );

		if ( ! headers_sent() ) {
			header( 'Retry-After: ' . $retry_after );
			header( 'X-Robots-Tag: noindex, nofollow' );
		}

		add_filter( 'template_include', array( self::class, 'override_template' ), 99 );
	}

	/**
	 * Override the template with templates/maintenance.html if available.
	 *
	 * @param string $template Incoming template path.
	 * @return string Maintenance template path or original fallback.
	 */
	public static function override_template( string $template ): string {
		foreach ( array( 'maintenance', 'blank' ) as $slug ) {
			$block_template = self::resolve_block_template( $slug );

			if ( null === $block_template ) {
				continue;
			}

			// Block templates are NOT PHP files: handing core a path to a .html
			// file would include it raw (unrendered block comments, no <html>,
			// no head, no CSS). Instead publish the template to core's
			// template-canvas.php, exactly as locate_block_template() does.
			global $_wp_current_template_id, $_wp_current_template_content;

			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Core's block-template handoff globals.
			$_wp_current_template_id = $block_template->id;
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Core's block-template handoff globals.
			$_wp_current_template_content = $block_template->content;

			return ABSPATH . WPINC . '/template-canvas.php';
		}

		// No usable block template (classic theme / not resolvable): keep the
		// original template. The 503 status and headers still apply.
		return $template;
	}

	/**
	 * Resolve a block template by slug through the active theme (child first).
	 *
	 * @param string $slug Template slug, e.g. 'maintenance'.
	 * @return \WP_Block_Template|null The template, or null when unavailable.
	 */
	private static function resolve_block_template( string $slug ): ?object {
		if ( ! function_exists( 'get_block_template' ) || ! function_exists( 'get_stylesheet' ) ) {
			return null;
		}

		$block_template = get_block_template( get_stylesheet() . '//' . $slug, 'wp_template' );

		if ( ! is_object( $block_template ) || empty( $block_template->content ) ) {
			return null;
		}

		return $block_template;
	}
}
