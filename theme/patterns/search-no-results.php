<?php
/**
 * Title: Search No Results
 * Slug: atlas/search-no-results
 * Categories: atlas
 * Text Domain: atlas-theme
 *
 * Empty-state content for the search template's wp:query-no-results block.
 * Pairs a reassuring heading and paragraph with a fresh search input and a set
 * of navigable suggestions built from the site's existing tags and categories.
 *
 * This is a PHP pattern (not static block markup) because the suggestions are
 * resolved at render time: only terms that actually exist are listed, and every
 * term URL and name is escaped at output so hostile term data cannot inject
 * live markup.
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Collect existing, non-empty tags and categories. hide_empty => true keeps the
 * suggestions useful (every link leads to real content) and, together with the
 * is_wp_error / is_array guards below, guarantees only existing terms render.
 */
$atlas_suggested_terms = get_terms(
	array(
		'taxonomy'   => array( 'post_tag', 'category' ),
		'hide_empty' => true,
		'number'     => 12,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);

if ( is_wp_error( $atlas_suggested_terms ) || ! is_array( $atlas_suggested_terms ) ) {
	$atlas_suggested_terms = array();
}
?>

<!-- wp:group {"tagName":"section","layout":{"type":"constrained"},"style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} -->
<section class="wp-block-group">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading"><?php echo esc_html__( 'No results found', 'atlas-theme' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php echo esc_html__( 'We could not find anything matching your search. Try different keywords, or explore one of the suggested topics below.', 'atlas-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:search {"label":"<?php echo esc_attr__( 'Search', 'atlas-theme' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr__( 'Search again…', 'atlas-theme' ); ?>","buttonText":"<?php echo esc_attr__( 'Search', 'atlas-theme' ); ?>"} /-->

	<?php if ( ! empty( $atlas_suggested_terms ) ) : ?>
		<!-- wp:heading {"level":3,"fontSize":"large"} -->
		<h3 class="wp-block-heading has-large-font-size"><?php echo esc_html__( 'Suggested topics', 'atlas-theme' ); ?></h3>
		<!-- /wp:heading -->

		<!-- wp:group {"tagName":"nav","layout":{"type":"flex","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
		<nav class="wp-block-group" aria-label="<?php echo esc_attr__( 'Suggested topics', 'atlas-theme' ); ?>">
			<?php
			foreach ( $atlas_suggested_terms as $atlas_term ) {
				if ( ! $atlas_term instanceof WP_Term ) {
					continue;
				}

				$atlas_term_link = get_term_link( $atlas_term );
				if ( is_wp_error( $atlas_term_link ) ) {
					continue;
				}
				?>
				<!-- wp:paragraph {"className":"atlas-suggested-term"} -->
				<p class="atlas-suggested-term"><a href="<?php echo esc_url( $atlas_term_link ); ?>"><?php echo esc_html( $atlas_term->name ); ?></a></p>
				<!-- /wp:paragraph -->
				<?php
			}
			?>
		</nav>
		<!-- /wp:group -->
	<?php endif; ?>
</section>
<!-- /wp:group -->
