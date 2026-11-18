<?php
/**
 * Title: 404 Error Content
 * Slug: atlas/404
 * Categories: atlas
 * Text Domain: atlas-theme
 * Description: Fully localized 404 empty state with translatable headings, helpful search, and home link.
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"tagName":"section","layout":{"type":"constrained"},"style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} -->
<section class="wp-block-group">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading"><?php echo esc_html__( 'Page not found', 'atlas-theme' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php echo esc_html__( 'The page you were looking for could not be found. Try searching for it or returning to the homepage.', 'atlas-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:search {"label":"<?php echo esc_attr__( 'Search', 'atlas-theme' ); ?>","buttonText":"<?php echo esc_attr__( 'Search', 'atlas-theme' ); ?>"} /-->

	<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","justifyContent":"left"}} -->
		<!-- wp:home-link {"label":"<?php echo esc_attr__( 'Back to home', 'atlas-theme' ); ?>"} /-->
	<!-- /wp:navigation -->
</section>
<!-- /wp:group -->
