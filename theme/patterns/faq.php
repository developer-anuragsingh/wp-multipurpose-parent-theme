<?php
/**
 * Title: FAQ Section
 * Slug: atlas/faq
 * Categories: atlas, text
 * Keywords: faq, questions, accordion, details, support
 * Description: Frequently asked questions as native, keyboard-operable disclosure widgets — no JavaScript required. The first Q/A carries the primary answer.
 * Text Domain: atlas-theme
 *
 * FAQ section built from native core/details blocks, which are keyboard
 * operable and need zero JavaScript. Leads with an H2 and keeps the most
 * important answer first. Styling is theme.json preset vars only (no custom CSS).
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"tagName":"section","style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'Frequently asked questions', 'atlas-theme' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:details {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
	<details class="wp-block-details" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><summary><?php esc_html_e( 'How do I use these patterns?', 'atlas-theme' ); ?></summary>
		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'Open the block inserter, search the Atlas category, and drop any section into your page. Each pattern is fully editable after insertion.', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->

	<!-- wp:details {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
	<details class="wp-block-details" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><summary><?php esc_html_e( 'Do the patterns require any JavaScript?', 'atlas-theme' ); ?></summary>
		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'No. This FAQ uses the native details element, and every pattern relies only on block supports and theme presets, so pages stay fast and work without scripts.', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->

	<!-- wp:details {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
	<details class="wp-block-details" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><summary><?php esc_html_e( 'Can I change the colours and spacing?', 'atlas-theme' ); ?></summary>
		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'Yes. Colours, spacing, and typography all come from your theme presets, so adjusting the theme updates every pattern consistently.', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->
</section>
<!-- /wp:group -->
