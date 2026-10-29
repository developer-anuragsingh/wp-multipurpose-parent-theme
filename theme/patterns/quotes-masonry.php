<?php
/**
 * Title: Quotes Masonry Grid
 * Slug: atlas/quotes-masonry
 * Categories: atlas, text, featured
 * Keywords: quotes, masonry, status, copy, interactivity
 * Description: Responsive masonry grid of quotes and status updates using modern CSS columns and container queries. Integrated with the Interactivity API for one-click copy-to-clipboard.
 * Text Domain: atlas-theme
 *
 * Quotes and status updates niche pattern. Uses a 0 KB JavaScript CSS masonry
 * layout driven by CSS column-count and container queries (.atlas-masonry-grid).
 * Each quote carries the is-style-atlas-copyable style, triggering the
 * Atlas\Assets render filter and the atlas/copy Interactivity API store for
 * instant, accessible clipboard copying with live region announcements.
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|40"}},"backgroundColor":"light","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-light-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:heading {"level":2,"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Insights & Perspective', 'atlas-theme' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","fontSize":"medium","textColor":"dark"} -->
	<p class="has-text-align-center has-dark-color has-text-color has-medium-font-size"><?php esc_html_e( 'Curated wisdom from pioneers in software design, engineering, and continuous learning.', 'atlas-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"atlas-masonry-grid","layout":{"type":"default"}} -->
	<div class="wp-block-group atlas-masonry-grid">
		<!-- wp:quote {"className":"is-style-atlas-copyable","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"0.5rem","width":"1px"}},"borderColor":"light","backgroundColor":"white"} -->
		<blockquote class="wp-block-quote is-style-atlas-copyable has-border-color has-light-border-color has-white-background-color has-background" style="border-width:1px;border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<p><?php esc_html_e( 'Simplicity is prerequisite for reliability.', 'atlas-theme' ); ?></p>
			<cite><?php esc_html_e( 'Edsger W. Dijkstra', 'atlas-theme' ); ?></cite>
		</blockquote>
		<!-- /wp:quote -->

		<!-- wp:quote {"className":"is-style-atlas-copyable","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"0.5rem","width":"1px"}},"borderColor":"light","backgroundColor":"white"} -->
		<blockquote class="wp-block-quote is-style-atlas-copyable has-border-color has-light-border-color has-white-background-color has-background" style="border-width:1px;border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<p><?php esc_html_e( 'The most dangerous phrase in the language is, "We\'ve always done it this way."', 'atlas-theme' ); ?></p>
			<cite><?php esc_html_e( 'Grace Hopper', 'atlas-theme' ); ?></cite>
		</blockquote>
		<!-- /wp:quote -->

		<!-- wp:quote {"className":"is-style-atlas-copyable","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"0.5rem","width":"1px"}},"borderColor":"light","backgroundColor":"white"} -->
		<blockquote class="wp-block-quote is-style-atlas-copyable has-border-color has-light-border-color has-white-background-color has-background" style="border-width:1px;border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<p><?php esc_html_e( 'Perfection is achieved, not when there is nothing more to add, but when there is nothing left to take away.', 'atlas-theme' ); ?></p>
			<cite><?php esc_html_e( 'Antoine de Saint-Exupéry', 'atlas-theme' ); ?></cite>
		</blockquote>
		<!-- /wp:quote -->

		<!-- wp:quote {"className":"is-style-atlas-copyable","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"0.5rem","width":"1px"}},"borderColor":"light","backgroundColor":"white"} -->
		<blockquote class="wp-block-quote is-style-atlas-copyable has-border-color has-light-border-color has-white-background-color has-background" style="border-width:1px;border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<p><?php esc_html_e( 'Simple things should be simple, complex things should be possible.', 'atlas-theme' ); ?></p>
			<cite><?php esc_html_e( 'Alan Kay', 'atlas-theme' ); ?></cite>
		</blockquote>
		<!-- /wp:quote -->

		<!-- wp:quote {"className":"is-style-atlas-copyable","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"0.5rem","width":"1px"}},"borderColor":"light","backgroundColor":"white"} -->
		<blockquote class="wp-block-quote is-style-atlas-copyable has-border-color has-light-border-color has-white-background-color has-background" style="border-width:1px;border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<p><?php esc_html_e( 'Talk is cheap. Show me the code.', 'atlas-theme' ); ?></p>
			<cite><?php esc_html_e( 'Linus Torvalds', 'atlas-theme' ); ?></cite>
		</blockquote>
		<!-- /wp:quote -->

		<!-- wp:quote {"className":"is-style-atlas-copyable","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"0.5rem","width":"1px"}},"borderColor":"light","backgroundColor":"white"} -->
		<blockquote class="wp-block-quote is-style-atlas-copyable has-border-color has-light-border-color has-white-background-color has-background" style="border-width:1px;border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<p><?php esc_html_e( 'Premature optimization is the root of all evil (or at least most of it) in programming.', 'atlas-theme' ); ?></p>
			<cite><?php esc_html_e( 'Donald E. Knuth', 'atlas-theme' ); ?></cite>
		</blockquote>
		<!-- /wp:quote -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
