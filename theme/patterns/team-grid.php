<?php
/**
 * Title: Team Leadership Grid
 * Slug: atlas/team-grid
 * Categories: atlas, featured, columns
 * Keywords: team, leadership, members, people, staff
 * Description: Executive team showcase with circular avatars, professional titles, and bios.
 * Text Domain: atlas-theme
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|40"}},"backgroundColor":"white","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-white-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","alignItems":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|small","fontWeight":"700","letterSpacing":"0.05em"}},"textColor":"accent-text"} -->
		<p class="has-accent-text-color has-text-color has-small-font-size" style="letter-spacing:0.05em;text-transform:uppercase"><?php echo esc_html__( '✦ Leadership Team', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"letterSpacing":"-0.025em"}}} -->
		<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Guided by engineering and product veterans', 'atlas-theme' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","textColor":"muted","style":{"spacing":{"margin":{"left":"auto","right":"auto"}}},"layout":{"selfStretch":"fit","flexSize":"600px"}} -->
		<p class="has-text-align-center has-muted-color has-text-color" style="max-width:600px;margin-left:auto;margin-right:auto"><?php echo esc_html__( 'Our multidisciplinary leadership pairs decades of enterprise architecture with relentless product focus.', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"light"} -->
		<div class="wp-block-column has-light-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","alignItems":"center"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|x-large","fontWeight":"700"}}} -->
				<p class="has-x-large-font-size"><strong>Alex Morgan</strong></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|small","fontWeight":"600"}},"textColor":"accent-text"} -->
				<p class="has-accent-text-color has-text-color has-small-font-size"><?php echo esc_html__( 'Chief Executive Officer', 'atlas-theme' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","textColor":"muted","fontSize":"small"} -->
				<p class="has-text-align-center has-muted-color has-text-color has-small-font-size"><?php echo esc_html__( 'Former Principal Architect at Stripe. Oversees enterprise operations, platform reliability, and global strategy.', 'atlas-theme' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"light"} -->
		<div class="wp-block-column has-light-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","alignItems":"center"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|x-large","fontWeight":"700"}}} -->
				<p class="has-x-large-font-size"><strong>Sarah Jenkins</strong></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|small","fontWeight":"600"}},"textColor":"accent-text"} -->
				<p class="has-accent-text-color has-text-color has-small-font-size"><?php echo esc_html__( 'Head of Design Systems', 'atlas-theme' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","textColor":"muted","fontSize":"small"} -->
				<p class="has-text-align-center has-muted-color has-text-color has-small-font-size"><?php echo esc_html__( 'Design systems pioneer and accessibility advocate. Specializes in multi-tier enterprise token architectures.', 'atlas-theme' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"light"} -->
		<div class="wp-block-column has-light-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","alignItems":"center"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|x-large","fontWeight":"700"}}} -->
				<p class="has-x-large-font-size"><strong>David Chen</strong></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|small","fontWeight":"600"}},"textColor":"accent-text"} -->
				<p class="has-accent-text-color has-text-color has-small-font-size"><?php echo esc_html__( 'Principal Security Engineer', 'atlas-theme' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","textColor":"muted","fontSize":"small"} -->
				<p class="has-text-align-center has-muted-color has-text-color has-small-font-size"><?php echo esc_html__( 'Infosec researcher with expertise in Content Security Policies, cryptographic tokens, and zero-trust web hardening.', 'atlas-theme' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"},"border":{"radius":"16px"}},"backgroundColor":"light"} -->
		<div class="wp-block-column has-light-background-color has-background" style="border:1px solid var(--wp--preset--color--border);border-radius:16px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","alignItems":"center"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|x-large","fontWeight":"700"}}} -->
				<p class="has-x-large-font-size"><strong>Priya Sharma</strong></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|small","fontWeight":"600"}},"textColor":"accent-text"} -->
				<p class="has-accent-text-color has-text-color has-small-font-size"><?php echo esc_html__( 'VP of Web Performance', 'atlas-theme' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","textColor":"muted","fontSize":"small"} -->
				<p class="has-text-align-center has-muted-color has-text-color has-small-font-size"><?php echo esc_html__( 'Core Web Vitals specialist and spec reviewer for modern browser APIs. Champions 100/100 Lighthouse metrics.', 'atlas-theme' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
