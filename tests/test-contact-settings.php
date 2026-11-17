<?php
/**
 * Unit tests for Atlas\Contact_Settings and Contact Us page suite.
 *
 * @package AtlasTheme
 */

class Test_Contact_Settings {

	public static function run(): array {
		global $atlas_test_options, $atlas_test_filters;
		$results = array();

		// Reset options.
		$atlas_test_options = array();

		// 1. Default toggle enabled state.
		$results[] = array(
			'name'   => 'Contact_Settings: features default to enabled when unset',
			'passed' => \Atlas\Contact_Settings::is_enabled( \Atlas\Contact_Settings::OPT_SHOW_MAP, true )
				&& \Atlas\Contact_Settings::is_enabled( \Atlas\Contact_Settings::OPT_SHOW_FORM, true )
				&& \Atlas\Contact_Settings::is_enabled( \Atlas\Contact_Settings::OPT_SHOW_CHAT, true ),
		);

		// 2. Disabling via option.
		$atlas_test_options[ \Atlas\Contact_Settings::OPT_SHOW_MAP ] = '0';
		$results[] = array(
			'name'   => 'Contact_Settings: feature can be disabled via option switch',
			'passed' => ! \Atlas\Contact_Settings::is_enabled( \Atlas\Contact_Settings::OPT_SHOW_MAP, true )
				&& '' === \Atlas\Contact_Settings::get_map_embed_url(),
		);

		// 3. Map embed URL with coordinates.
		$atlas_test_options[ \Atlas\Contact_Settings::OPT_SHOW_MAP ]   = '1';
		$atlas_test_options[ \Atlas\Contact_Settings::OPT_MAP_TYPE ]   = 'coords';
		$atlas_test_options[ \Atlas\Contact_Settings::OPT_MAP_COORDS ] = '28.6139, 77.2090';
		$map_url = \Atlas\Contact_Settings::get_map_embed_url();

		$results[] = array(
			'name'   => 'Contact_Settings: coordinates convert to valid Google Maps embed URL',
			'passed' => str_contains( $map_url, 'https://maps.google.com/maps?q=28.6139%2C77.2090' )
				&& str_contains( $map_url, 'output=embed' ),
		);

		// 4. Map embed URL with explicit URL.
		$atlas_test_options[ \Atlas\Contact_Settings::OPT_MAP_TYPE ] = 'url';
		$atlas_test_options[ \Atlas\Contact_Settings::OPT_MAP_URL ]  = 'https://maps.google.com/maps?q=MyOffice&output=embed';
		$results[] = array(
			'name'   => 'Contact_Settings: explicit map embed URL is sanitized and returned',
			'passed' => 'https://maps.google.com/maps?q=MyOffice&output=embed' === \Atlas\Contact_Settings::get_map_embed_url(),
		);

		// 5. Recipient email forwarding.
		$default_email = 'admin@example.com';
		$results[] = array(
			'name'   => 'Contact_Settings: recipient email falls back to admin_email when unset',
			'passed' => $default_email === \Atlas\Contact_Settings::filter_recipient_email( $default_email ),
		);

		$atlas_test_options[ \Atlas\Contact_Settings::OPT_RECIPIENT_EMAIL ] = 'inquiries@example.com';
		$results[] = array(
			'name'   => 'Contact_Settings: recipient email routes to forward address when set',
			'passed' => 'inquiries@example.com' === \Atlas\Contact_Settings::filter_recipient_email( $default_email ),
		);

		// 6. Direct chat channel links.
		$atlas_test_options[ \Atlas\Contact_Settings::OPT_SHOW_CHAT ] = '1';
		$atlas_test_options[ \Atlas\Contact_Settings::OPT_WHATSAPP ]  = '+91 98765 43210';
		$atlas_test_options[ \Atlas\Contact_Settings::OPT_TELEGRAM ]  = '@mybusiness';
		$atlas_test_options[ \Atlas\Contact_Settings::OPT_INSTAGRAM ] = 'mybrand';
		$atlas_test_options[ \Atlas\Contact_Settings::OPT_MESSENGER ] = 'mybrandpage';

		$channels = \Atlas\Contact_Settings::get_chat_channels();

		$results[] = array(
			'name'   => 'Contact_Settings: direct chat channels generate proper instant messaging links',
			'passed' => isset( $channels['whatsapp'], $channels['telegram'], $channels['instagram'], $channels['messenger'] )
				&& 'https://wa.me/919876543210' === $channels['whatsapp']['url']
				&& 'https://t.me/mybusiness' === $channels['telegram']['url']
				&& 'https://ig.me/m/mybrand' === $channels['instagram']['url']
				&& 'https://m.me/mybrandpage' === $channels['messenger']['url'],
		);

		// 7. Social share links.
		$shares = \Atlas\Contact_Settings::get_share_links( 'https://example.com/contact/', 'Contact Us' );
		$results[] = array(
			'name'   => 'Contact_Settings: 1-click share links are built for WhatsApp, X, LinkedIn, and Facebook',
			'passed' => isset( $shares['whatsapp'], $shares['x'], $shares['linkedin'], $shares['facebook'] )
				&& str_contains( $shares['whatsapp']['url'], 'api.whatsapp.com/send' )
				&& str_contains( $shares['x']['url'], 'twitter.com/intent/tweet' )
				&& str_contains( $shares['linkedin']['url'], 'linkedin.com/sharing' ),
		);

		// 8. Turnstile anti-bot verification fails closed when configured and token missing.
		$atlas_test_options[ \Atlas\Contact_Settings::OPT_TURNSTILE_SECRET ] = '0x4AAAAAAABBBBBB';
		$req = new WP_REST_Request();
		$results[] = array(
			'name'   => 'Contact_Settings: Turnstile verification fails closed when secret set but token missing',
			'passed' => false === \Atlas\Contact_Settings::verify_turnstile_challenge( true, $req ),
		);

		// 9. Pattern and template files exist.
		$pattern_file  = ATLAS_THEME_DIR . '/patterns/contact-page.php';
		$template_file = ATLAS_THEME_DIR . '/templates/page-contact.html';

		$results[] = array(
			'name'   => 'Contact Page: pattern file contact-page.php exists',
			'passed' => file_exists( $pattern_file ),
		);

		$results[] = array(
			'name'   => 'Contact Page: template file page-contact.html exists',
			'passed' => file_exists( $template_file ),
		);

		// Reset options.
		$atlas_test_options = array();

		return $results;
	}
}
