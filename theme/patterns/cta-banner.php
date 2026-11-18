<?php
/**
 * Title: Call To Action Banner
 * Slug: atlas/cta-banner
 * Categories: atlas, featured, call-to-action, banners
 * Keywords: cta, call to action, banner, contact, conversion
 * Description: High-contrast conversion section with executive headline, supporting value proposition, and primary action button.
 * Text Domain: atlas-theme
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|30"}},"backgroundColor":"primary","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-primary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|small","fontWeight":"700","letterSpacing":"0.05em","textTransform":"uppercase"},"color":{"text":"var:preset|color|white"}}} -->
		<p class="has-white-color has-text-color has-small-font-size" style="letter-spacing:0.05em;text-transform:uppercase"><?php echo esc_html__( '✦ Ready to transform your digital experience?', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:heading {"level":2,"textAlign":"center","textColor":"white","style":{"typography":{"fontSize":"var:preset|font-size|display","lineHeight":"1.15","letterSpacing":"-0.03em"}}} -->
	<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color has-display-font-size" style="letter-spacing:-0.03em;line-height:1.15"><?php echo esc_html__( 'Build high-performance digital platforms with verified scale.', 'atlas-theme' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","textColor":"light","style":{"spacing":{"margin":{"left":"auto","right":"auto"}}},"layout":{"selfStretch":"fit","flexSize":"640px"}} -->
	<p class="has-text-align-center has-light-color has-text-color" style="max-width:640px;margin-left:auto;margin-right:auto"><?php echo esc_html__( 'Partner with our enterprise engineering team to design, build, and deploy an uncompromising WordPress architecture.', 'atlas-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"backgroundColor":"accent","textColor":"white"} -->
		<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-accent-background-color has-text-color has-background wp-element-button" href="/contact-form/"><?php echo esc_html__( 'Schedule an Executive Consultation →', 'atlas-theme' ); ?></a></div>
		<!-- /wp:button -->

		<!-- wp:button {"backgroundColor":"primary","textColor":"white","className":"is-style-outline"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-primary-background-color has-text-color has-background wp-element-button" href="#capabilities"><?php echo esc_html__( 'Explore Our Capabilities', 'atlas-theme' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</section>
<!-- /wp:group -->
