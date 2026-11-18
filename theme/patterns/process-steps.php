<?php
/**
 * Title: Process Steps Timeline
 * Slug: atlas/process-steps
 * Categories: atlas, featured, columns
 * Keywords: process, steps, timeline, workflow, how-it-works
 * Description: Four-stage structured methodology showing delivery from discovery to edge deployment.
 * Text Domain: atlas-theme
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|40"}},"backgroundColor":"light","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-light-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","alignItems":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|small","fontWeight":"700","letterSpacing":"0.05em"}},"textColor":"accent-text"} -->
		<p class="has-accent-text-color has-text-color has-small-font-size" style="letter-spacing:0.05em;text-transform:uppercase"><?php echo esc_html__( '✦ Proven Methodology', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"letterSpacing":"-0.025em"}}} -->
		<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Our Four-Stage Engineering Process', 'atlas-theme' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","textColor":"muted","style":{"spacing":{"margin":{"left":"auto","right":"auto"}}},"layout":{"selfStretch":"fit","flexSize":"600px"}} -->
		<p class="has-text-align-center has-muted-color has-text-color" style="max-width:600px;margin-left:auto;margin-right:auto"><?php echo esc_html__( 'A structured, transparent delivery pipeline ensuring zero surprises and verified production speed.', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"white"} -->
		<div class="wp-block-column has-white-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:heading {"level":3,"fontSize":"display","style":{"typography":{"fontWeight":"800"}},"textColor":"primary"} -->
			<h3 class="wp-block-heading has-primary-color has-text-color has-display-font-size">01</h3>
			<!-- /wp:heading -->
			<!-- wp:heading {"level":4,"fontSize":"large"} -->
			<h4 class="wp-block-heading has-large-font-size"><?php echo esc_html__( 'Discovery & Strategy', 'atlas-theme' ); ?></h4>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"muted"} -->
			<p class="has-muted-color has-text-color"><?php echo esc_html__( 'Comprehensive technical audit, compliance mapping, data model design, and performance budgeting.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"white"} -->
		<div class="wp-block-column has-white-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:heading {"level":3,"fontSize":"display","style":{"typography":{"fontWeight":"800"}},"textColor":"primary"} -->
			<h3 class="wp-block-heading has-primary-color has-text-color has-display-font-size">02</h3>
			<!-- /wp:heading -->
			<!-- wp:heading {"level":4,"fontSize":"large"} -->
			<h4 class="wp-block-heading has-large-font-size"><?php echo esc_html__( 'Design Systems', 'atlas-theme' ); ?></h4>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"muted"} -->
			<p class="has-muted-color has-text-color"><?php echo esc_html__( 'Fluid typography scaling, accessible contrast pairs, and theme.json token cascading for zero visual drift.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"white"} -->
		<div class="wp-block-column has-white-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:heading {"level":3,"fontSize":"display","style":{"typography":{"fontWeight":"800"}},"textColor":"primary"} -->
			<h3 class="wp-block-heading has-primary-color has-text-color has-display-font-size">03</h3>
			<!-- /wp:heading -->
			<!-- wp:heading {"level":4,"fontSize":"large"} -->
			<h4 class="wp-block-heading has-large-font-size"><?php echo esc_html__( 'Engineering & Testing', 'atlas-theme' ); ?></h4>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"muted"} -->
			<p class="has-muted-color has-text-color"><?php echo esc_html__( 'Native Interactivity API modules, strict CSP hardening, and automated Playwright E2E coverage.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"white"} -->
		<div class="wp-block-column has-white-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:heading {"level":3,"fontSize":"display","style":{"typography":{"fontWeight":"800"}},"textColor":"primary"} -->
			<h3 class="wp-block-heading has-primary-color has-text-color has-display-font-size">04</h3>
			<!-- /wp:heading -->
			<!-- wp:heading {"level":4,"fontSize":"large"} -->
			<h4 class="wp-block-heading has-large-font-size"><?php echo esc_html__( 'Deployment & Growth', 'atlas-theme' ); ?></h4>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"muted"} -->
			<p class="has-muted-color has-text-color"><?php echo esc_html__( 'Edge caching, Speculation Rules prerender activation, and verified 100/100 Core Web Vitals sign-off.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
