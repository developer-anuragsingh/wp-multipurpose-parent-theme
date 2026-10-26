<?php
/**
 * Title: Hero (Answer-First)
 * Slug: atlas/hero-answer-first
 * Categories: atlas, featured, call-to-action, banner
 * Keywords: hero, banner, landing, headline, cta
 * Description: Answer-first landing hero with a page H1, a concise factual subheading, and call-to-action buttons. Intended as a page's main title block — use only on a front page or landing template where no other H1 exists.
 * Text Domain: atlas-theme
 *
 * Front-page / landing hero. This is the ONE section pattern that intentionally
 * emits an <h1>: it is meant to be a page's main title. Do not place it on a
 * page that already has an H1. All user-facing strings use the atlas-theme text
 * domain. Styling is theme.json preset vars only (no custom CSS); the accent
 * colour appears only on button/large display text per the contrast rules.
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|40"}},"backgroundColor":"light","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-light-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:heading {"level":1,"textAlign":"center"} -->
	<h1 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Build faster with a theme that answers first', 'atlas-theme' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","fontSize":"large","textColor":"dark"} -->
	<p class="has-text-align-center has-dark-color has-text-color has-large-font-size"><?php esc_html_e( 'Ship accessible, fast, answer-first pages. Every section is a composable block pattern styled entirely from your theme presets.', 'atlas-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
	<div class="wp-block-buttons">
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php esc_html_e( 'Get started', 'atlas-theme' ); ?></a></div>
		<!-- /wp:button -->

		<!-- wp:button {"backgroundColor":"accent","className":"is-style-fill"} -->
		<div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-accent-background-color has-background wp-element-button" href="#"><?php esc_html_e( 'See how it works', 'atlas-theme' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</section>
<!-- /wp:group -->
