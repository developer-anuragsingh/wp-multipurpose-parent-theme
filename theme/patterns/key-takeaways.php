<?php
/**
 * Title: Key Takeaways
 * Slug: atlas/key-takeaways
 * Categories: atlas, text
 * Keywords: summary, takeaways, tldr, highlights, key points
 * Description: Highlighted summary callout — a short, answer-first list of key points designed to sit directly under a page H1.
 * Text Domain: atlas-theme
 *
 * Answer-first summary callout. Leads with an H2 ("Key takeaways") and a short
 * bulleted list so the main points are machine-parseable and scannable at the
 * top of a page. Body text stays dark on the light surface to satisfy the
 * WCAG AA contrast rule. Styling is theme.json preset vars only (no custom CSS).
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"tagName":"section","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|30"},"border":{"radius":"0.5rem"}},"backgroundColor":"light","layout":{"type":"constrained"}} -->
<section class="wp-block-group has-light-background-color has-background" style="border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'Key takeaways', 'atlas-theme' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:list -->
	<ul class="wp-block-list">
		<!-- wp:list-item -->
		<li><?php esc_html_e( 'Every section is a reusable, inserter-visible block pattern.', 'atlas-theme' ); ?></li>
		<!-- /wp:list-item -->

		<!-- wp:list-item -->
		<li><?php esc_html_e( 'Styling comes entirely from theme.json presets — no inline hex or pixels.', 'atlas-theme' ); ?></li>
		<!-- /wp:list-item -->

		<!-- wp:list-item -->
		<li><?php esc_html_e( 'Markup is answer-first and accessible, with a single logical heading outline.', 'atlas-theme' ); ?></li>
		<!-- /wp:list-item -->
	</ul>
	<!-- /wp:list -->
</section>
<!-- /wp:group -->
