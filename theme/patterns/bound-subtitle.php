<?php
/**
 * Title: Bound Subtitle
 * Slug: atlas/bound-subtitle
 * Categories: atlas, text
 * Keywords: subtitle, deck, binding, meta, bound
 * Description: A subtitle paragraph bound to the post's Atlas subtitle meta via the atlas/post-meta binding source — edit it inline and it persists to the post.
 * Inserter: true
 * Text Domain: atlas-theme
 *
 * Demo of the Atlas\Bindings block binding source. The paragraph's content is
 * bound to the atlas_subtitle post meta through the atlas/post-meta source, so
 * editing it inline in the Editor writes the post's subtitle. When no subtitle
 * is set the binding fails closed to an empty value and the visible default text
 * below renders instead. The source name ("atlas/post-meta") and key
 * ("atlas_subtitle") in the markup below MUST match Atlas\Bindings::SOURCE and
 * Atlas\Bindings::META_SUBTITLE — block markup cannot reference a PHP constant,
 * so keep the two in lockstep. Styling is theme.json presets only (no custom
 * CSS).
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"atlas/post-meta","args":{"key":"atlas_subtitle"}}}}} -->
<p><?php esc_html_e( 'Add a subtitle for this post', 'atlas-theme' ); ?></p>
<!-- /wp:paragraph -->
