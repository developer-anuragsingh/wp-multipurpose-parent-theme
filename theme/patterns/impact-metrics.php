<?php
/**
 * Title: Impact Metrics (NGO)
 * Slug: atlas/impact-metrics
 * Categories: atlas, featured, stats
 * Keywords: ngo, non-profit, impact, metrics, donate, charity, transparency
 * Description: Transparent impact metrics grid for NGO and non-profit organizations. Highlights community reach, donor efficiency, and accountability with accessible ARIA labeling.
 * Text Domain: atlas-theme
 *
 * NGO / non-profit niche impact metrics pattern. Employs a high-contrast native
 * block grid with explicit ARIA landmark labels, meeting WCAG 2.2 AA contrast
 * standards. Metric numbers use the accent preset on large display text for
 * maximum visual hierarchy without sacrificing readability or machine discoverability.
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|40"}},"backgroundColor":"white","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-white-background-color has-background" role="region" aria-label="<?php echo esc_attr__( 'Organizational Impact Metrics', 'atlas-theme' ); ?>" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"level":2,"textAlign":"center"} -->
		<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Real Impact, Driven by Community Trust', 'atlas-theme' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","textColor":"dark","fontSize":"medium"} -->
		<p class="has-text-align-center has-dark-color has-text-color has-medium-font-size"><?php esc_html_e( 'Every contribution powers transparent, verifiable change. Here is how our collective effort creates measurable progress.', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"layout":{"type":"grid","minimumColumnWidth":"15rem"},"style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} -->
	<div class="wp-block-group">
		<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"0.5rem","width":"1px"}},"borderColor":"light","backgroundColor":"light","layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} -->
		<div class="wp-block-group has-border-color has-light-border-color has-light-background-color has-background" style="border-width:1px;border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:heading {"level":3,"textColor":"accent","fontSize":"xx-large"} -->
			<h3 class="wp-block-heading has-accent-color has-text-color has-xx-large-font-size">12,800+</h3>
			<!-- /wp:heading -->

			<!-- wp:heading {"level":4,"fontSize":"medium"} -->
			<h4 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Families Supported', 'atlas-theme' ); ?></h4>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"fontSize":"small","textColor":"dark"} -->
			<p class="has-dark-color has-text-color has-small-font-size"><?php esc_html_e( 'Direct humanitarian aid and clean water infrastructure delivered across 18 operational communities.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"0.5rem","width":"1px"}},"borderColor":"light","backgroundColor":"light","layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} -->
		<div class="wp-block-group has-border-color has-light-border-color has-light-background-color has-background" style="border-width:1px;border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:heading {"level":3,"textColor":"accent","fontSize":"xx-large"} -->
			<h3 class="wp-block-heading has-accent-color has-text-color has-xx-large-font-size">94%</h3>
			<!-- /wp:heading -->

			<!-- wp:heading {"level":4,"fontSize":"medium"} -->
			<h4 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Direct Mission Funding', 'atlas-theme' ); ?></h4>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"fontSize":"small","textColor":"dark"} -->
			<p class="has-dark-color has-text-color has-small-font-size"><?php esc_html_e( '94 cents of every single dollar received is deployed directly into ground programs and community initiatives.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"0.5rem","width":"1px"}},"borderColor":"light","backgroundColor":"light","layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} -->
		<div class="wp-block-group has-border-color has-light-border-color has-light-background-color has-background" style="border-width:1px;border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:heading {"level":3,"textColor":"accent","fontSize":"xx-large"} -->
			<h3 class="wp-block-heading has-accent-color has-text-color has-xx-large-font-size">52,000+</h3>
			<!-- /wp:heading -->

			<!-- wp:heading {"level":4,"fontSize":"medium"} -->
			<h4 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Volunteer Hours', 'atlas-theme' ); ?></h4>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"fontSize":"small","textColor":"dark"} -->
			<p class="has-dark-color has-text-color has-small-font-size"><?php esc_html_e( 'Passionate volunteers providing grassroots educational guidance and regional disaster readiness.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"0.5rem","width":"1px"}},"borderColor":"light","backgroundColor":"light","layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} -->
		<div class="wp-block-group has-border-color has-light-border-color has-light-background-color has-background" style="border-width:1px;border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:heading {"level":3,"textColor":"accent","fontSize":"xx-large"} -->
			<h3 class="wp-block-heading has-accent-color has-text-color has-xx-large-font-size">100%</h3>
			<!-- /wp:heading -->

			<!-- wp:heading {"level":4,"fontSize":"medium"} -->
			<h4 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Public Auditing', 'atlas-theme' ); ?></h4>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"fontSize":"small","textColor":"dark"} -->
			<p class="has-dark-color has-text-color has-small-font-size"><?php esc_html_e( 'Independently audited yearly financials, available for open inspection with zero obscured allocations.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"backgroundColor":"primary","className":"is-style-fill"} -->
		<div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-primary-background-color has-background wp-element-button" href="#"><?php esc_html_e( 'Support Our Mission', 'atlas-theme' ); ?></a></div>
		<!-- /wp:button -->

		<!-- wp:button {"className":"is-style-outline"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#"><?php esc_html_e( 'Download 2026 Audit Report', 'atlas-theme' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</section>
<!-- /wp:group -->
