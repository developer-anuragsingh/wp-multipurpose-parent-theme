<?php
/**
 * Robots / AI-crawler access-control concern.
 *
 * Owns the Atlas theme's virtual robots.txt policy. Hooks the core 'robots_txt'
 * filter so the policy is served from WordPress's virtual robots.txt (/robots.txt
 * when no physical file exists) — this concern NEVER writes a physical robots.txt
 * file. It keeps the incoming core output intact (so CSS/JS are never blocked and
 * core's wp-admin rules survive), guarantees an admin-ajax.php allow line and a
 * Sitemap: reference are present, then appends an operator-configurable
 * allow/block policy for the six named AI crawlers (default: allow all). Mirrors
 * Atlas\Bindings precisely — a static registrar wired from functions.php after
 * Atlas\Contact_Form::init(). Resolved via the hand-rolled autoloader
 * (Atlas\ -> inc/). robots.txt is machine output, so no string here is gettext.
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for the virtual robots.txt AI-crawler policy.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object. All public surface is reachable from the
 * WordPress hook wired in init().
 */
class Robots {

	/**
	 * Filter name an operator uses to override the per-crawler AI policy.
	 *
	 * Prefixed atlas_ per the global-prefix rule. The filter receives an
	 * associative map of crawler token => policy (allow|disallow); the operator
	 * flips individual values. Only the known AI_CRAWLERS tokens are ever read
	 * from the returned array, so an operator can change allow/disallow but can
	 * never inject arbitrary robots.txt lines.
	 *
	 * @var string
	 */
	const FILTER_AI_POLICY = 'atlas_ai_crawler_policy';

	/**
	 * The AI crawler user-agent tokens governed by this policy, in output order.
	 *
	 * A fixed, known set so the filter can only flip each token's allow/disallow
	 * value — never add unknown user-agents. Order is preserved in the emitted
	 * robots.txt blocks for stable, reviewable output.
	 *
	 * @var string[]
	 */
	const AI_CRAWLERS = array(
		'GPTBot',
		'OAI-SearchBot',
		'ChatGPT-User',
		'ClaudeBot',
		'PerplexityBot',
		'Google-Extended',
	);

	/**
	 * Policy value that permits a crawler (emits 'Allow: /').
	 *
	 * @var string
	 */
	const POLICY_ALLOW = 'allow';

	/**
	 * Policy value that blocks a crawler (emits 'Disallow: /').
	 *
	 * @var string
	 */
	const POLICY_DISALLOW = 'disallow';

	/**
	 * Register the robots.txt WordPress hooks.
	 *
	 * Called once from functions.php immediately after Atlas\Contact_Form::init().
	 * Wires the core 'robots_txt' filter (priority 10, two args) so the policy is
	 * appended to the virtual robots.txt. Keeps all robots wiring in one place so
	 * the boot order is explicit.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'robots_txt', array( self::class, 'filter_robots_txt' ), 10, 2 );
	}

	/**
	 * Append the Atlas robots.txt policy to WordPress's virtual robots.txt.
	 *
	 * Keeps the incoming core $output verbatim so core's rules — crucially never
	 * blocking CSS/JS — are preserved. When the site is not public ($public
	 * falsey) core already blocks everything; returning $output unchanged avoids
	 * appending allow lines that would contradict that. Otherwise it ensures the
	 * two operational lines SEO expects are present — an admin-ajax.php allow and
	 * a Sitemap: reference — appending only the ones core did not already emit
	 * (strpos checks prevent duplicates), and never adds any Disallow for .css or
	 * .js. Finally it appends the AI-crawler blocks built from the (filterable)
	 * policy and returns the combined string.
	 *
	 * @param string $output The robots.txt content assembled so far by core.
	 * @param mixed  $public The blog_public option (1 = indexable, 0 = discouraged).
	 * @return string The robots.txt content with the Atlas policy appended.
	 */
	public static function filter_robots_txt( string $output, $public ): string {
		if ( ! $public ) {
			return $output;
		}

		if ( false === strpos( $output, 'Allow: /wp-admin/admin-ajax.php' ) ) {
			$output .= "Allow: /wp-admin/admin-ajax.php\n";
		}

		if ( false === strpos( $output, 'Sitemap:' ) ) {
			$output .= 'Sitemap: ' . esc_url( home_url( '/wp-sitemap.xml' ) ) . "\n";
		}

		return $output . self::build_ai_policy();
	}

	/**
	 * Build the AI-crawler policy block appended to robots.txt.
	 *
	 * Seeds a default map that allows every crawler, then passes it through the
	 * FILTER_AI_POLICY filter so an operator can flip individual tokens. The
	 * returned array is consulted by key only for the known AI_CRAWLERS tokens —
	 * iterating the fixed constant (not the filter's keys) means an operator can
	 * change allow/disallow but can never inject arbitrary user-agent lines. Each
	 * crawler yields a 'User-agent:' line followed by 'Allow: /' (the default)
	 * unless its policy is explicitly POLICY_DISALLOW, in which case 'Disallow: /'.
	 *
	 * @return string The concatenated AI-crawler robots.txt blocks.
	 */
	private static function build_ai_policy(): string {
		$policy = apply_filters(
			self::FILTER_AI_POLICY,
			array_fill_keys( self::AI_CRAWLERS, self::POLICY_ALLOW )
		);

		$blocks = '';

		foreach ( self::AI_CRAWLERS as $agent ) {
			$value = isset( $policy[ $agent ] ) ? $policy[ $agent ] : self::POLICY_ALLOW;
			$rule  = self::POLICY_DISALLOW === $value ? 'Disallow: /' : 'Allow: /';

			$blocks .= "\nUser-agent: " . $agent . "\n" . $rule . "\n";
		}

		return $blocks;
	}
}
