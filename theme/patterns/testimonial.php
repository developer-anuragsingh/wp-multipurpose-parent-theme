<?php
/**
 * Title: Testimonial Card
 * Slug: atlas/testimonial
 * Categories: atlas, text, featured
 * Keywords: testimonial, review, quote, overrides, synced
 * Description: A locked testimonial card whose review text and avatar image are overridable per instance via synced pattern overrides (core/pattern-overrides).
 * Text Domain: atlas-theme
 *
 * Testimonial card with synced pattern overrides. The card structure (border,
 * radius, padding) is locked via block supports and theme presets; only the
 * review paragraph and the avatar image are marked overridable with the
 * core/pattern-overrides bindings source, each carrying a unique metadata.name.
 * The author name is an H3 (card-level, not a page heading).
 *
 * Avatar: the src is intentionally empty so each instance supplies its own
 * image via the override. Explicit width/height protect against layout shift.
 * Ship avatars as AVIF with a WebP fallback where your media pipeline allows;
 * no binary asset is bundled with this pattern.
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"tagName":"section","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|30"},"border":{"radius":"0.5rem","width":"1px"}},"borderColor":"light","backgroundColor":"white","layout":{"type":"constrained"}} -->
<section class="wp-block-group has-border-color has-light-border-color has-white-background-color has-background" style="border-width:1px;border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
	<!-- wp:image {"metadata":{"name":"Avatar","bindings":{"__default":{"source":"core/pattern-overrides"}}},"width":"64px","height":"64px","sizeSlug":"thumbnail","className":"is-style-rounded"} -->
	<figure class="wp-block-image size-thumbnail is-resized is-style-rounded"><img src="" alt="<?php echo esc_attr__( 'Reviewer avatar', 'atlas-theme' ); ?>" width="64" height="64"/></figure>
	<!-- /wp:image -->

	<!-- wp:paragraph {"metadata":{"name":"Review Text","bindings":{"__default":{"source":"core/pattern-overrides"}}},"fontSize":"large"} -->
	<p class="has-large-font-size"><?php esc_html_e( 'This theme let us launch an accessible, answer-first site in days instead of weeks. The section patterns are a joy to work with.', 'atlas-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":3,"fontSize":"medium"} -->
	<h3 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Jordan Rivera, Head of Web', 'atlas-theme' ); ?></h3>
	<!-- /wp:heading -->
</section>
<!-- /wp:group -->
