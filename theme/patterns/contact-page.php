<?php
/**
 * Title: Contact Us Page (Full Suite)
 * Slug: atlas/contact-page
 * Categories: atlas
 * Inserter: true
 * Text Domain: atlas-theme
 *
 * Full-featured Contact Us page pattern containing:
 *  - Contact Hero with fast response expectations
 *  - Instant Direct Chat buttons (WhatsApp, Telegram, Instagram, Messenger)
 *  - Contact Information cards (Phone, Email, Hours, Physical Address)
 *  - Progressive-enhancement AJAX Contact Form with signed anti-bot token
 *  - Google Maps embed (URL or coordinates), click-to-load: nothing is
 *    requested from Google until the visitor clicks "Show map"
 *  - Social Media Profile links and 1-Click Page Share bar
 *
 * Every section respects its On/Off switch in Atlas\Contact_Settings.
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$atlas_show_chat   = \Atlas\Contact_Settings::is_enabled( \Atlas\Contact_Settings::OPT_SHOW_CHAT, true );
$atlas_show_form   = \Atlas\Contact_Settings::is_enabled( \Atlas\Contact_Settings::OPT_SHOW_FORM, true );
$atlas_show_info   = \Atlas\Contact_Settings::is_enabled( \Atlas\Contact_Settings::OPT_SHOW_INFO, true );
$atlas_show_map    = \Atlas\Contact_Settings::is_enabled( \Atlas\Contact_Settings::OPT_SHOW_MAP, true );
$atlas_show_social = \Atlas\Contact_Settings::is_enabled( \Atlas\Contact_Settings::OPT_SHOW_SOCIAL, true );
$atlas_show_share  = \Atlas\Contact_Settings::is_enabled( \Atlas\Contact_Settings::OPT_SHOW_SHARE, true );

$atlas_chat_channels = \Atlas\Contact_Settings::get_chat_channels();
$atlas_info          = \Atlas\Contact_Settings::get_contact_info();
$atlas_map_url       = \Atlas\Contact_Settings::get_map_embed_url();
$atlas_socials       = \Atlas\Contact_Settings::get_social_profiles();
$atlas_shares        = \Atlas\Contact_Settings::get_share_links();
$atlas_recipient     = \Atlas\Contact_Settings::filter_recipient_email( get_option( 'admin_email' ) );
?>

<!-- wp:group {"tagName":"main","className":"atlas-contact-page","layout":{"type":"constrained"}} -->
<main class="wp-block-group atlas-contact-page">

	<!-- wp:group {"tagName":"header","align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
	<header class="wp-block-group alignwide">
		<!-- wp:heading {"level":1} -->
		<h1 class="wp-block-heading"><?php echo esc_html__( 'Get in touch with us', 'atlas-theme' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|medium"}}} -->
		<p class="has-medium-font-size">
			<?php echo esc_html__( 'Have a question, feedback, or a partnership inquiry? Reach out through any of our direct channels or send us a message below.', 'atlas-theme' ); ?>
		</p>
		<!-- /wp:paragraph -->
	</header>
	<!-- /wp:group -->

	<?php if ( $atlas_show_chat && ! empty( $atlas_chat_channels ) ) : ?>
		<!-- wp:group {"tagName":"section","align":"wide","className":"atlas-direct-chat"} -->
		<section class="wp-block-group alignwide atlas-direct-chat" aria-label="<?php echo esc_attr__( 'Instant messaging channels', 'atlas-theme' ); ?>">
			<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"var:preset|font-size|small"}}} -->
			<h2 class="wp-block-heading has-small-font-size" style="text-transform:uppercase;letter-spacing:0.05em;color:var(--wp--preset--color--primary);">
				<?php echo esc_html__( 'Instant Direct Messaging', 'atlas-theme' ); ?>
			</h2>
			<!-- /wp:heading -->

			<!-- wp:html -->
			<div class="atlas-direct-chat__grid">
				<?php foreach ( $atlas_chat_channels as $atlas_channel_key => $atlas_channel ) : ?>
					<a href="<?php echo esc_url( $atlas_channel['url'] ); ?>" class="atlas-chat-card atlas-chat-card--<?php echo esc_attr( $atlas_channel_key ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $atlas_channel['label'] ); ?>">
						<?php echo \Atlas\Icons::get( $atlas_channel['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static trusted SVG. ?>
						<span><?php echo esc_html( $atlas_channel['name'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
			<!-- /wp:html -->
		</section>
		<!-- /wp:group -->
	<?php endif; ?>

	<!-- wp:group {"tagName":"section","align":"wide","className":"atlas-contact-layout"} -->
	<section class="wp-block-group alignwide atlas-contact-layout">

		<?php if ( $atlas_show_form ) : ?>
			<!-- wp:group {"tagName":"div","className":"atlas-contact-column"} -->
			<div class="wp-block-group atlas-contact-column">
				<!-- wp:pattern {"slug":"atlas/contact-form"} /-->
			</div>
			<!-- /wp:group -->
		<?php endif; ?>

		<?php if ( $atlas_show_info ) : ?>
			<!-- wp:group {"tagName":"div","className":"atlas-contact-column","style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
			<div class="wp-block-group atlas-contact-column">
				<!-- wp:heading {"level":2} -->
				<h2 class="wp-block-heading"><?php echo esc_html__( 'Contact details', 'atlas-theme' ); ?></h2>
				<!-- /wp:heading -->

				<!-- wp:html -->
				<div class="atlas-contact-info-grid">
					<?php if ( '' !== $atlas_info['phone'] ) : ?>
						<div class="atlas-info-card">
							<div class="atlas-info-card__header">
								<?php echo \Atlas\Icons::get( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php echo esc_html__( 'Phone', 'atlas-theme' ); ?></span>
							</div>
							<div class="atlas-info-card__body">
								<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $atlas_info['phone'] ) ); ?>">
									<?php echo esc_html( $atlas_info['phone'] ); ?>
								</a>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( '' !== $atlas_recipient ) : ?>
						<div class="atlas-info-card">
							<div class="atlas-info-card__header">
								<?php echo \Atlas\Icons::get( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php echo esc_html__( 'Email', 'atlas-theme' ); ?></span>
							</div>
							<div class="atlas-info-card__body">
								<a href="mailto:<?php echo esc_attr( $atlas_recipient ); ?>">
									<?php echo esc_html( $atlas_recipient ); ?>
								</a>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( '' !== $atlas_info['address'] ) : ?>
						<div class="atlas-info-card">
							<div class="atlas-info-card__header">
								<?php echo \Atlas\Icons::get( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php echo esc_html__( 'Location', 'atlas-theme' ); ?></span>
							</div>
							<div class="atlas-info-card__body">
								<?php echo nl2br( esc_html( $atlas_info['address'] ) ); ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( '' !== $atlas_info['hours'] ) : ?>
						<div class="atlas-info-card">
							<div class="atlas-info-card__header">
								<?php echo \Atlas\Icons::get( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php echo esc_html__( 'Business hours', 'atlas-theme' ); ?></span>
							</div>
							<div class="atlas-info-card__body">
								<?php echo esc_html( $atlas_info['hours'] ); ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
				<!-- /wp:html -->
			</div>
			<!-- /wp:group -->
		<?php endif; ?>

	</section>
	<!-- /wp:group -->

	<?php if ( $atlas_show_map && '' !== $atlas_map_url ) : ?>
		<!-- wp:group {"tagName":"section","align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
		<section class="wp-block-group alignwide">
			<!-- wp:heading {"level":2} -->
			<h2 class="wp-block-heading"><?php echo esc_html__( 'Our location', 'atlas-theme' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:html -->
			<?php echo \Atlas\Contact_Settings::render_map( $atlas_map_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside render_map(). ?>
			<!-- /wp:html -->
		</section>
		<!-- /wp:group -->
	<?php endif; ?>

	<?php if ( ( $atlas_show_social && ! empty( $atlas_socials ) ) || ( $atlas_show_share && ! empty( $atlas_shares ) ) ) : ?>
		<!-- wp:group {"tagName":"section","align":"wide","className":"atlas-social-share-row"} -->
		<section class="wp-block-group alignwide atlas-social-share-row">

			<?php if ( $atlas_show_social && ! empty( $atlas_socials ) ) : ?>
				<div class="atlas-icon-pills-group">
					<p style="font-weight:600;font-size:var(--wp--preset--font-size--small);margin-bottom:0.5rem;">
						<?php echo esc_html__( 'Connect with us:', 'atlas-theme' ); ?>
					</p>
					<div class="atlas-icon-pills">
						<?php foreach ( $atlas_socials as $atlas_soc_key => $atlas_soc ) : ?>
							<a
								href="<?php echo esc_url( $atlas_soc['url'] ); ?>"
								class="atlas-icon-pill"
								target="_blank"
								rel="noopener noreferrer"
								title="<?php echo esc_attr( $atlas_soc['name'] ); ?>"
								aria-label="<?php echo esc_attr( $atlas_soc['name'] ); ?>"
							>
								<?php echo \Atlas\Icons::get( $atlas_soc['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $atlas_show_share && ! empty( $atlas_shares ) ) : ?>
				<div class="atlas-icon-pills-group">
					<p style="font-weight:600;font-size:var(--wp--preset--font-size--small);margin-bottom:0.5rem;">
						<?php echo esc_html__( 'Share this page:', 'atlas-theme' ); ?>
					</p>
					<div class="atlas-icon-pills">
						<?php foreach ( $atlas_shares as $atlas_share_key => $atlas_sh ) : ?>
							<?php
							/* translators: %s: social network name. */
							$atlas_share_label = sprintf( __( 'Share on %s', 'atlas-theme' ), $atlas_sh['name'] );
							?>
							<a
								href="<?php echo esc_url( $atlas_sh['url'] ); ?>"
								class="atlas-icon-pill"
								target="_blank"
								rel="noopener noreferrer"
								title="<?php echo esc_attr( $atlas_share_label ); ?>"
								aria-label="<?php echo esc_attr( $atlas_share_label ); ?>"
							>
								<?php echo \Atlas\Icons::get( $atlas_sh['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

		</section>
		<!-- /wp:group -->
	<?php endif; ?>

</main>
<!-- /wp:group -->
