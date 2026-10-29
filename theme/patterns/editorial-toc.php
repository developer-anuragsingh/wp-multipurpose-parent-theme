<?php
/**
 * Title: Editorial Table of Contents
 * Slug: atlas/editorial-toc
 * Categories: atlas, text, featured
 * Keywords: editorial, blog, toc, table of contents, reading, article, guide
 * Description: Editorial reading companion featuring article metadata (reading time, verification status) and a structured Table of Contents with accessible anchor navigation.
 * Text Domain: atlas-theme
 *
 * Editorial niche reading pattern. Emits a semantic <aside> landmark containing
 * an accessible <nav> Table of Contents and E-E-A-T trust signals (reading time,
 * verification badge, publication timestamp). Supports clean navigation for human
 * readers while offering deterministic anchor headings for AI engine indexing (GEO).
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"tagName":"aside","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|30"},"border":{"radius":"0.5rem","width":"1px"}},"borderColor":"light","backgroundColor":"light","layout":{"type":"constrained"}} -->
<aside class="wp-block-group has-border-color has-light-border-color has-light-background-color has-background" role="complementary" aria-label="<?php echo esc_attr__( 'Article Overview and Table of Contents', 'atlas-theme' ); ?>" style="border-width:1px;border-radius:0.5rem;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
	<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"},"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"fontSize":"small","textColor":"dark"} -->
	<div class="wp-block-group has-dark-color has-text-color has-small-font-size">
		<!-- wp:paragraph -->
		<p><strong><?php esc_html_e( 'Reading time:', 'atlas-theme' ); ?></strong> <?php esc_html_e( '5 minutes', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><strong><?php esc_html_e( 'Status:', 'atlas-theme' ); ?></strong> <?php esc_html_e( 'Fact-Checked & Verified', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><strong><?php esc_html_e( 'Version:', 'atlas-theme' ); ?></strong> <?php esc_html_e( 'October 2026', 'atlas-theme' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:separator {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"backgroundColor":"light"} -->
	<hr class="wp-block-separator has-text-color has-light-color has-alpha-channel-opacity has-light-background-color has-background" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)"/>
	<!-- /wp:separator -->

	<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"level":3,"fontSize":"medium"} -->
		<h3 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Table of Contents', 'atlas-theme' ); ?></h3>
		<!-- /wp:heading -->

		<!-- wp:list {"className":"is-style-default"} -->
		<ul class="wp-block-list is-style-default">
			<!-- wp:list-item -->
			<li><a href="#executive-summary"><?php esc_html_e( '1. Executive Summary & Findings', 'atlas-theme' ); ?></a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="#core-architecture"><?php esc_html_e( '2. Core Architecture & Design Tokens', 'atlas-theme' ); ?></a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="#interactivity-contracts"><?php esc_html_e( '3. Interactivity API Contracts', 'atlas-theme' ); ?></a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="#performance-benchmarks"><?php esc_html_e( '4. Performance & Core Web Vitals', 'atlas-theme' ); ?></a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="#conclusion-and-resources"><?php esc_html_e( '5. Conclusion & Implementation Notes', 'atlas-theme' ); ?></a></li>
			<!-- /wp:list-item -->
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->
</aside>
<!-- /wp:group -->
