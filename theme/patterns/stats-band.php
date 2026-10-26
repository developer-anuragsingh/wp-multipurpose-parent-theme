<?php
/**
 * Title: Stats Band
 * Slug: atlas/stats-band
 * Categories: atlas, text, featured
 * Keywords: stats, metrics, numbers, kpi, figures
 * Description: Full-width band of key metrics in a responsive native grid. Numbers are plain text so they stay machine-parseable and answer-first.
 * Text Domain: atlas-theme
 *
 * Stats / metrics band on a dark surface with light text for AA contrast. The
 * large metric numbers use the accent colour (large display text only, which
 * meets the large-text contrast ratio); the small labels stay light, never
 * accent. Layout is a native block grid — no custom CSS. Numbers are plain
 * text so they remain machine-parseable.
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|50"}},"backgroundColor":"dark","textColor":"light","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-light-color has-dark-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:heading {"level":2,"textAlign":"center","textColor":"white"} -->
	<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color"><?php esc_html_e( 'Trusted by teams that ship', 'atlas-theme' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"layout":{"type":"grid","minimumColumnWidth":"12rem"},"style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} -->
	<div class="wp-block-group">
		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"level":3,"textAlign":"center","textColor":"accent","fontSize":"xx-large"} -->
			<h3 class="wp-block-heading has-text-align-center has-accent-color has-text-color has-xx-large-font-size">99.9%</h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","textColor":"light","fontSize":"small"} -->
			<p class="has-text-align-center has-light-color has-text-color has-small-font-size"><?php esc_html_e( 'Uptime across deployments', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"level":3,"textAlign":"center","textColor":"accent","fontSize":"xx-large"} -->
			<h3 class="wp-block-heading has-text-align-center has-accent-color has-text-color has-xx-large-font-size">0&nbsp;KB</h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","textColor":"light","fontSize":"small"} -->
			<p class="has-text-align-center has-light-color has-text-color has-small-font-size"><?php esc_html_e( 'JavaScript on the front end', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"level":3,"textAlign":"center","textColor":"accent","fontSize":"xx-large"} -->
			<h3 class="wp-block-heading has-text-align-center has-accent-color has-text-color has-xx-large-font-size">7</h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","textColor":"light","fontSize":"small"} -->
			<p class="has-text-align-center has-light-color has-text-color has-small-font-size"><?php esc_html_e( 'Composable section patterns', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"level":3,"textAlign":"center","textColor":"accent","fontSize":"xx-large"} -->
			<h3 class="wp-block-heading has-text-align-center has-accent-color has-text-color has-xx-large-font-size">AA</h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","textColor":"light","fontSize":"small"} -->
			<p class="has-text-align-center has-light-color has-text-color has-small-font-size"><?php esc_html_e( 'WCAG contrast target', 'atlas-theme' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
