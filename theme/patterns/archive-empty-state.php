<?php
/**
 * Title: Archive Empty State
 * Slug: atlas/archive-empty-state
 * Categories: atlas
 * Description: Empty-state message with a call to action for archives that return no posts.
 * Inserter: false
 * Text Domain: atlas-theme
 *
 * Rendered inside the wp:query-no-results block of archive.html when an archive
 * query returns zero posts. All user-facing strings use the atlas-theme text
 * domain and dynamic output is escaped with esc_html__ / esc_url.
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"tagName":"section","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|40"}}} -->
<section class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading"><?php echo esc_html__( 'Nothing here yet', 'atlas-theme' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php echo esc_html__( 'There are no posts in this archive right now. Head back to the home page to explore the latest content.', 'atlas-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
	<div class="wp-block-buttons">
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html__( 'Back to home', 'atlas-theme' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</section>
<!-- /wp:group -->
