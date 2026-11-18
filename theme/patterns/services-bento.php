<?php
/**
 * Title: Services Bento Grid
 * Slug: atlas/services-bento
 * Categories: atlas, featured, columns
 * Keywords: services, bento, grid, capabilities, features
 * Description: Modern 2026 Bento Grid services showcase with primary highlight card and modular capabilities.
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
		<p class="has-accent-text-color has-text-color has-small-font-size" style="letter-spacing:0.05em;text-transform:uppercase"><?php echo esc_html__( '✦ Core Capabilities', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"letterSpacing":"-0.025em"}}} -->
		<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Engineered for enterprise scale and speed', 'atlas-theme' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","textColor":"muted","style":{"spacing":{"margin":{"left":"auto","right":"auto"}}},"layout":{"selfStretch":"fit","flexSize":"680px"}} -->
		<p class="has-text-align-center has-muted-color has-text-color" style="max-width:680px;margin-left:auto;margin-right:auto"><?php echo esc_html__( 'Every architectural decision is built around sub-second rendering, rigorous security, and modular design tokens.', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"width":"60%","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"white"} -->
		<div class="wp-block-column has-white-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;flex-basis:60%;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|small","fontWeight":"700"}},"textColor":"accent-text"} -->
			<p class="has-accent-text-color has-text-color has-small-font-size"><?php echo esc_html__( '01. PLATFORM ARCHITECTURE', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"fontSize":"x-large"} -->
			<h3 class="wp-block-heading has-x-large-font-size"><?php echo esc_html__( 'Mission-Critical WordPress FSE Systems', 'atlas-theme' ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"muted"} -->
			<p class="has-muted-color has-text-color"><?php echo esc_html__( 'Native Full-Site Editing foundation with zero-PHP child theme capability. Engineered for 10M+ pageviews with sub-second Time-to-Interactive.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"40%","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"white"} -->
		<div class="wp-block-column has-white-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;flex-basis:40%;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|small","fontWeight":"700"}},"textColor":"accent-text"} -->
			<p class="has-accent-text-color has-text-color has-small-font-size"><?php echo esc_html__( '02. HARDENED SECURITY', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"fontSize":"large"} -->
			<h3 class="wp-block-heading has-large-font-size"><?php echo esc_html__( 'Zero-Trust Defense', 'atlas-theme' ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"muted"} -->
			<p class="has-muted-color has-text-color"><?php echo esc_html__( 'Cryptographic per-request nonces, zero unsafe-inline scripts, signed one-time form tokens, and automated rate limiting.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"white"} -->
		<div class="wp-block-column has-white-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|small","fontWeight":"700"}},"textColor":"accent-text"} -->
			<p class="has-accent-text-color has-text-color has-small-font-size"><?php echo esc_html__( '03. FLUID DESIGN', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"fontSize":"large"} -->
			<h3 class="wp-block-heading has-large-font-size"><?php echo esc_html__( 'Design Systems & Tokens', 'atlas-theme' ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"muted"} -->
			<p class="has-muted-color has-text-color"><?php echo esc_html__( 'Hierarchical design tokens, fluid typography scaling, and soft ambient shadow layers.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"white"} -->
		<div class="wp-block-column has-white-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|small","fontWeight":"700"}},"textColor":"accent-text"} -->
			<p class="has-accent-text-color has-text-color has-small-font-size"><?php echo esc_html__( '04. EDGE VELOCITY', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"fontSize":"large"} -->
			<h3 class="wp-block-heading has-large-font-size"><?php echo esc_html__( 'Speculation & Caching', 'atlas-theme' ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"muted"} -->
			<p class="has-muted-color has-text-color"><?php echo esc_html__( 'Instant prerendering via Speculation Rules API and generation-based transient schema caching.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"white"} -->
		<div class="wp-block-column has-white-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|small","fontWeight":"700"}},"textColor":"accent-text"} -->
			<p class="has-accent-text-color has-text-color has-small-font-size"><?php echo esc_html__( '05. RELIABILITY', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"fontSize":"large"} -->
			<h3 class="wp-block-heading has-large-font-size"><?php echo esc_html__( 'Enterprise SLA', 'atlas-theme' ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"muted"} -->
			<p class="has-muted-color has-text-color"><?php echo esc_html__( 'Guaranteed 99.99% uptime alignment with dedicated 4-hour executive inquiry turnaround.', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
