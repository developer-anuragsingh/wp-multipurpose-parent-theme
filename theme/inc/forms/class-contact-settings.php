<?php
/**
 * Contact page settings and communication channels manager.
 *
 * Provides centralized administration and configuration for the Atlas theme's
 * default Contact Us page: On/Off toggles for Google Maps (URL or coordinates),
 * recipient email forwarding, instant direct messaging (WhatsApp, Telegram,
 * Instagram, Messenger), social profiles and sharing, physical address / hours,
 * and built-in anti-abuse / Turnstile integration.
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar and manager for Contact Us page features.
 */
class Contact_Settings {

	/**
	 * Customizer section identifier.
	 *
	 * @var string
	 */
	const SECTION = 'atlas_contact_settings';

	/**
	 * Option keys.
	 */
	const OPT_SHOW_MAP         = 'atlas_contact_show_map';
	const OPT_MAP_TYPE         = 'atlas_contact_map_type';
	const OPT_MAP_URL          = 'atlas_contact_map_url';
	const OPT_MAP_COORDS       = 'atlas_contact_map_coords';
	const OPT_SHOW_FORM        = 'atlas_contact_show_form';
	const OPT_RECIPIENT_EMAIL  = 'atlas_contact_recipient_email';
	const OPT_TURNSTILE_SITE   = 'atlas_contact_turnstile_sitekey';
	const OPT_TURNSTILE_SECRET = 'atlas_contact_turnstile_secret';
	const OPT_SHOW_CHAT        = 'atlas_contact_show_chat';
	const OPT_WHATSAPP         = 'atlas_contact_whatsapp';
	const OPT_TELEGRAM         = 'atlas_contact_telegram';
	const OPT_INSTAGRAM        = 'atlas_contact_instagram';
	const OPT_MESSENGER        = 'atlas_contact_messenger';
	const OPT_SHOW_SOCIAL      = 'atlas_contact_show_social';
	const OPT_SOCIAL_X         = 'atlas_contact_social_x';
	const OPT_SOCIAL_LINKEDIN  = 'atlas_contact_social_linkedin';
	const OPT_SOCIAL_FACEBOOK  = 'atlas_contact_social_facebook';
	const OPT_SOCIAL_YOUTUBE   = 'atlas_contact_social_youtube';
	const OPT_SOCIAL_GITHUB    = 'atlas_contact_social_github';
	const OPT_SHOW_SHARE       = 'atlas_contact_show_share';
	const OPT_SHOW_INFO        = 'atlas_contact_show_info';
	const OPT_ADDRESS          = 'atlas_contact_address';
	const OPT_PHONE            = 'atlas_contact_phone';
	const OPT_HOURS            = 'atlas_contact_hours';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'customize_register', array( self::class, 'register_customizer' ) );
		add_action( 'customize_controls_print_styles', array( self::class, 'print_customizer_styles' ) );
		add_action( 'customize_controls_print_footer_scripts', array( self::class, 'print_customizer_scripts' ) );
		add_filter( 'atlas_contact_form_recipient', array( self::class, 'filter_recipient_email' ) );
		add_filter( 'atlas_contact_form_verify_challenge', array( self::class, 'verify_turnstile_challenge' ), 10, 2 );
	}

	/**
	 * Check whether a contact page feature toggle is enabled.
	 *
	 * @param string $feature     Option key (OPT_SHOW_*).
	 * @param bool   $default_val Default state if option is unset.
	 * @return bool
	 */
	public static function is_enabled( string $feature, bool $default_val = true ): bool {
		$value = get_option( $feature, $default_val ? '1' : '0' );
		$is_on = in_array( $value, array( true, '1', 1, 'yes', 'on' ), true );

		/**
		 * Filter contact feature enabled state.
		 *
		 * @param bool   $is_on   Active state.
		 * @param string $feature Feature key.
		 */
		return (bool) apply_filters( 'atlas_contact_feature_enabled', $is_on, $feature );
	}

	/**
	 * Resolve Google Maps embed source URL.
	 *
	 * Supports explicit embed URLs or latitude/longitude coordinates.
	 *
	 * @return string Clean embed URL, or empty if disabled/unconfigured.
	 */
	public static function get_map_embed_url(): string {
		if ( ! self::is_enabled( self::OPT_SHOW_MAP, true ) ) {
			return '';
		}

		$type = (string) get_option( self::OPT_MAP_TYPE, 'url' );

		if ( 'coords' === $type ) {
			$coords = trim( (string) get_option( self::OPT_MAP_COORDS, '' ) );
			if ( '' !== $coords && preg_match( '/^-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?$/', $coords ) ) {
				$clean = str_replace( ' ', '', $coords );
				return esc_url_raw( 'https://maps.google.com/maps?q=' . rawurlencode( $clean ) . '&z=15&output=embed' );
			}
		}

		$url = trim( (string) get_option( self::OPT_MAP_URL, '' ) );
		if ( '' !== $url ) {
			return esc_url_raw( $url );
		}

		/**
		 * Default fallback coordinates: Connaught Place, New Delhi (bilingual theme base).
		 */
		return esc_url_raw( 'https://maps.google.com/maps?q=28.6315,77.2167&z=15&output=embed' );
	}

	/**
	 * Filter the contact form recipient email.
	 *
	 * Overrides the recipient when an administrator configures a forward email.
	 *
	 * @param string $recipient Current recipient.
	 * @return string Filtered recipient email.
	 */
	public static function filter_recipient_email( string $recipient ): string {
		$custom = trim( (string) get_option( self::OPT_RECIPIENT_EMAIL, '' ) );

		if ( '' !== $custom && is_email( $custom ) ) {
			return $custom;
		}

		return $recipient;
	}

	/**
	 * Verify Cloudflare Turnstile anti-bot token when configured.
	 *
	 * Fails closed if a Turnstile secret key is configured and verification fails.
	 *
	 * @param bool             $passed  Current verification verdict.
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @return bool True if passed or Turnstile not configured, false if rejected.
	 */
	public static function verify_turnstile_challenge( $passed, $request ): bool {
		if ( false === $passed ) {
			return false;
		}

		$secret = defined( 'ATLAS_TURNSTILE_SECRET_KEY' )
			? (string) constant( 'ATLAS_TURNSTILE_SECRET_KEY' )
			: (string) get_option( self::OPT_TURNSTILE_SECRET, '' );

		if ( '' === trim( $secret ) ) {
			return true;
		}

		$token = $request instanceof \WP_REST_Request ? (string) $request->get_param( 'cf-turnstile-response' ) : '';
		if ( '' === trim( $token ) ) {
			return false;
		}

		if ( ! function_exists( 'wp_remote_post' ) ) {
			return true;
		}

		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'timeout' => 5,
				'body'    => array(
					'secret'   => $secret,
					'response' => $token,
					'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return ! empty( $body['success'] );
	}

	/**
	 * Retrieve active instant messaging channels.
	 *
	 * @return array<string, array{name:string, url:string, icon:string, label:string}>
	 */
	public static function get_chat_channels(): array {
		if ( ! self::is_enabled( self::OPT_SHOW_CHAT, true ) ) {
			return array();
		}

		$channels = array();

		$whatsapp = trim( (string) get_option( self::OPT_WHATSAPP, '' ) );
		if ( '' !== $whatsapp ) {
			$clean_phone          = preg_replace( '/[^0-9+]/', '', $whatsapp );
			$clean_phone          = ltrim( (string) $clean_phone, '+' );
			$channels['whatsapp'] = array(
				'name'  => 'WhatsApp',
				'url'   => esc_url_raw( 'https://wa.me/' . $clean_phone ),
				'icon'  => 'whatsapp',
				'label' => __( 'Chat on WhatsApp', 'atlas-theme' ),
			);
		}

		$telegram = trim( (string) get_option( self::OPT_TELEGRAM, '' ) );
		if ( '' !== $telegram ) {
			$clean_user           = ltrim( $telegram, '@' );
			$channels['telegram'] = array(
				'name'  => 'Telegram',
				'url'   => esc_url_raw( 'https://t.me/' . $clean_user ),
				'icon'  => 'telegram',
				'label' => __( 'Chat on Telegram', 'atlas-theme' ),
			);
		}

		$instagram = trim( (string) get_option( self::OPT_INSTAGRAM, '' ) );
		if ( '' !== $instagram ) {
			$clean_user            = ltrim( $instagram, '@' );
			$channels['instagram'] = array(
				'name'  => 'Instagram',
				'url'   => esc_url_raw( 'https://ig.me/m/' . $clean_user ),
				'icon'  => 'instagram',
				'label' => __( 'Message on Instagram', 'atlas-theme' ),
			);
		}

		$messenger = trim( (string) get_option( self::OPT_MESSENGER, '' ) );
		if ( '' !== $messenger ) {
			$clean_user            = ltrim( $messenger, '@' );
			$channels['messenger'] = array(
				'name'  => 'Messenger',
				'url'   => esc_url_raw( 'https://m.me/' . $clean_user ),
				'icon'  => 'facebook',
				'label' => __( 'Message on Facebook', 'atlas-theme' ),
			);
		}

		return apply_filters( 'atlas_contact_chat_channels', $channels );
	}

	/**
	 * Retrieve active social media profiles.
	 *
	 * @return array<string, array{name:string, url:string, icon:string}>
	 */
	public static function get_social_profiles(): array {
		if ( ! self::is_enabled( self::OPT_SHOW_SOCIAL, true ) ) {
			return array();
		}

		$profiles = array();
		$map      = array(
			'x'        => array(
				'name' => 'X (Twitter)',
				'opt'  => self::OPT_SOCIAL_X,
				'icon' => 'twitter',
			),
			'linkedin' => array(
				'name' => 'LinkedIn',
				'opt'  => self::OPT_SOCIAL_LINKEDIN,
				'icon' => 'linkedin',
			),
			'facebook' => array(
				'name' => 'Facebook',
				'opt'  => self::OPT_SOCIAL_FACEBOOK,
				'icon' => 'facebook',
			),
			'youtube'  => array(
				'name' => 'YouTube',
				'opt'  => self::OPT_SOCIAL_YOUTUBE,
				'icon' => 'share',
			),
			'github'   => array(
				'name' => 'GitHub',
				'opt'  => self::OPT_SOCIAL_GITHUB,
				'icon' => 'send',
			),
		);

		foreach ( $map as $slug => $data ) {
			$url = trim( (string) get_option( $data['opt'], '' ) );
			if ( '' !== $url ) {
				$profiles[ $slug ] = array(
					'name' => $data['name'],
					'url'  => esc_url_raw( $url ),
					'icon' => $data['icon'],
				);
			}
		}

		return apply_filters( 'atlas_contact_social_profiles', $profiles );
	}

	/**
	 * Retrieve share links for the current contact page.
	 *
	 * @param string $page_url   Target page URL.
	 * @param string $page_title Target title.
	 * @return array<string, array{name:string, url:string, icon:string}>
	 */
	public static function get_share_links( string $page_url = '', string $page_title = '' ): array {
		if ( ! self::is_enabled( self::OPT_SHOW_SHARE, true ) ) {
			return array();
		}

		$url   = '' !== $page_url ? $page_url : home_url( '/contact/' );
		$title = '' !== $page_title ? $page_title : get_bloginfo( 'name' );

		$enc_url   = rawurlencode( $url );
		$enc_title = rawurlencode( $title );

		return array(
			'whatsapp' => array(
				'name' => 'WhatsApp',
				'url'  => 'https://api.whatsapp.com/send?text=' . $enc_title . '%20' . $enc_url,
				'icon' => 'whatsapp',
			),
			'x'        => array(
				'name' => 'X',
				'url'  => 'https://twitter.com/intent/tweet?url=' . $enc_url . '&text=' . $enc_title,
				'icon' => 'twitter',
			),
			'linkedin' => array(
				'name' => 'LinkedIn',
				'url'  => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $enc_url,
				'icon' => 'linkedin',
			),
			'facebook' => array(
				'name' => 'Facebook',
				'url'  => 'https://www.facebook.com/sharer/sharer.php?u=' . $enc_url,
				'icon' => 'facebook',
			),
		);
	}

	/**
	 * Retrieve physical office and contact information.
	 *
	 * @return array{address:string, phone:string, hours:string}
	 */
	public static function get_contact_info(): array {
		if ( ! self::is_enabled( self::OPT_SHOW_INFO, true ) ) {
			return array(
				'address' => '',
				'phone'   => '',
				'hours'   => '',
			);
		}

		return array(
			'address' => trim( (string) get_option( self::OPT_ADDRESS, __( 'Connaught Place, New Delhi, India', 'atlas-theme' ) ) ),
			'phone'   => trim( (string) get_option( self::OPT_PHONE, '+91 11 2345 6789' ) ),
			'hours'   => trim( (string) get_option( self::OPT_HOURS, __( 'Monday - Friday: 9:00 AM - 6:00 PM IST', 'atlas-theme' ) ) ),
		);
	}

	/**
	 * Register WordPress Customizer section and controls.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer instance.
	 * @return void
	 */
	public static function register_customizer( $wp_customize ): void {
		if ( ! is_object( $wp_customize ) ) {
			return;
		}

		$wp_customize->add_section(
			self::SECTION,
			array(
				'title'       => __( 'Atlas: Contact Page & Channels', 'atlas-theme' ),
				'priority'    => 130,
				'description' => __( 'Configure inbuilt features, direct messaging, and Google Maps on the Contact Us page.', 'atlas-theme' ),
			)
		);

		$settings = array(
			self::OPT_SHOW_MAP         => array(
				'label'   => __( 'Show Google Maps Section', 'atlas-theme' ),
				'type'    => 'switch',
				'default' => '1',
			),
			self::OPT_MAP_TYPE         => array(
				'label'   => __( 'Map Type (url / coords)', 'atlas-theme' ),
				'type'    => 'text',
				'default' => 'url',
			),
			self::OPT_MAP_URL          => array(
				'label'   => __( 'Google Maps Embed URL', 'atlas-theme' ),
				'type'    => 'url',
				'default' => '',
			),
			self::OPT_MAP_COORDS       => array(
				'label'   => __( 'Map Coordinates (lat,lng)', 'atlas-theme' ),
				'type'    => 'text',
				'default' => '28.6315,77.2167',
			),
			self::OPT_SHOW_FORM        => array(
				'label'   => __( 'Show Interactive Contact Form', 'atlas-theme' ),
				'type'    => 'switch',
				'default' => '1',
			),
			self::OPT_RECIPIENT_EMAIL  => array(
				'label'   => __( 'Forward Inquiries To Email', 'atlas-theme' ),
				'type'    => 'email',
				'default' => '',
			),
			self::OPT_SHOW_CHAT        => array(
				'label'   => __( 'Show Direct Chat Channels', 'atlas-theme' ),
				'type'    => 'switch',
				'default' => '1',
			),
			self::OPT_WHATSAPP         => array(
				'label'   => __( 'WhatsApp Number (with country code)', 'atlas-theme' ),
				'type'    => 'text',
				'default' => '',
			),
			self::OPT_TELEGRAM         => array(
				'label'   => __( 'Telegram Username (@username)', 'atlas-theme' ),
				'type'    => 'text',
				'default' => '',
			),
			self::OPT_INSTAGRAM        => array(
				'label'   => __( 'Instagram Username (@username)', 'atlas-theme' ),
				'type'    => 'text',
				'default' => '',
			),
			self::OPT_MESSENGER        => array(
				'label'   => __( 'Facebook Messenger Username', 'atlas-theme' ),
				'type'    => 'text',
				'default' => '',
			),
			self::OPT_SHOW_SOCIAL      => array(
				'label'   => __( 'Show Social Media Profile Links', 'atlas-theme' ),
				'type'    => 'switch',
				'default' => '1',
			),
			self::OPT_SOCIAL_X         => array(
				'label'   => __( 'X (Twitter) Profile URL', 'atlas-theme' ),
				'type'    => 'url',
				'default' => '',
			),
			self::OPT_SOCIAL_LINKEDIN  => array(
				'label'   => __( 'LinkedIn Profile URL', 'atlas-theme' ),
				'type'    => 'url',
				'default' => '',
			),
			self::OPT_SOCIAL_FACEBOOK  => array(
				'label'   => __( 'Facebook Profile URL', 'atlas-theme' ),
				'type'    => 'url',
				'default' => '',
			),
			self::OPT_SHOW_SHARE       => array(
				'label'   => __( 'Show 1-Click Page Share Bar', 'atlas-theme' ),
				'type'    => 'switch',
				'default' => '1',
			),
			self::OPT_SHOW_INFO        => array(
				'label'   => __( 'Show Address & Office Hours', 'atlas-theme' ),
				'type'    => 'switch',
				'default' => '1',
			),
			self::OPT_ADDRESS          => array(
				'label'   => __( 'Office Physical Address', 'atlas-theme' ),
				'type'    => 'text',
				'default' => '',
			),
			self::OPT_PHONE            => array(
				'label'   => __( 'Contact Phone Number', 'atlas-theme' ),
				'type'    => 'text',
				'default' => '',
			),
			self::OPT_HOURS            => array(
				'label'   => __( 'Business Operating Hours', 'atlas-theme' ),
				'type'    => 'text',
				'default' => '',
			),
			self::OPT_TURNSTILE_SITE   => array(
				'label'   => __( 'Cloudflare Turnstile Site Key (Optional)', 'atlas-theme' ),
				'type'    => 'text',
				'default' => '',
			),
			self::OPT_TURNSTILE_SECRET => array(
				'label'   => __( 'Cloudflare Turnstile Secret Key (Optional)', 'atlas-theme' ),
				'type'    => 'text',
				'default' => '',
			),
		);

		foreach ( $settings as $key => $meta ) {
			$sanitize = 'sanitize_text_field';
			if ( 'switch' === $meta['type'] || 'checkbox' === $meta['type'] ) {
				$sanitize = static fn( $val ) => $val ? '1' : '0';
			} elseif ( 'email' === $meta['type'] ) {
				$sanitize = 'sanitize_email';
			} elseif ( 'url' === $meta['type'] ) {
				$sanitize = 'esc_url_raw';
			}

			$wp_customize->add_setting(
				$key,
				array(
					'default'           => $meta['default'],
					'sanitize_callback' => $sanitize,
				)
			);

			if ( 'switch' === $meta['type'] && class_exists( 'Atlas\Customize_Switch_Control' ) ) {
				$wp_customize->add_control(
					new Customize_Switch_Control(
						$wp_customize,
						$key,
						array(
							'label'    => $meta['label'],
							'section'  => self::SECTION,
							'settings' => $key,
						)
					)
				);
			} else {
				$wp_customize->add_control(
					$key,
					array(
						'label'    => $meta['label'],
						'section'  => self::SECTION,
						'type'     => $meta['type'],
						'settings' => $key,
					)
				);
			}
		}
	}

	/**
	 * Print inline CSS styles for Customizer toggle switches.
	 *
	 * @return void
	 */
	public static function print_customizer_styles(): void {
		?>
		<style id="atlas-customizer-switch-styles">
			.atlas-switch-control {
				display: flex;
				align-items: center;
				justify-content: space-between;
				gap: 12px;
				margin-bottom: 12px;
				padding: 10px 0;
				border-bottom: 1px solid #f0f0f1;
			}
			.atlas-switch-info {
				flex: 1;
			}
			.atlas-switch-control .customize-control-title {
				margin-bottom: 0;
				font-weight: 600;
				font-size: 13px;
				cursor: pointer;
			}
			.atlas-switch {
				position: relative;
				display: inline-flex;
				align-items: center;
				gap: 8px;
				cursor: pointer;
				user-select: none;
			}
			.atlas-switch-input {
				position: absolute;
				opacity: 0;
				width: 0;
				height: 0;
				margin: 0;
				pointer-events: none;
			}
			.atlas-switch-track {
				position: relative;
				display: inline-block;
				width: 44px;
				height: 24px;
				background-color: #cbd5e1;
				border-radius: 24px;
				transition: background-color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
			}
			.atlas-switch-thumb {
				position: absolute;
				top: 2px;
				left: 2px;
				width: 20px;
				height: 20px;
				background-color: #ffffff;
				border-radius: 50%;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
				transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
			}
			.atlas-switch-input:checked + .atlas-switch-track {
				background-color: #2271b1;
			}
			.atlas-switch-input:checked + .atlas-switch-track .atlas-switch-thumb {
				transform: translateX(20px);
			}
			.atlas-switch-input:focus-visible + .atlas-switch-track {
				box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #2271b1;
			}
			.atlas-switch-status {
				font-size: 11px;
				font-weight: 700;
				letter-spacing: 0.05em;
				color: #64748b;
				min-width: 26px;
				text-transform: uppercase;
			}
			.atlas-switch-input:checked ~ .atlas-switch-status {
				color: #2271b1;
			}
		</style>
		<?php
	}

	/**
	 * Print inline JavaScript for Customizer toggle switches.
	 *
	 * Dynamically toggles the visual ON / OFF text when an administrator flips the switch.
	 *
	 * @return void
	 */
	public static function print_customizer_scripts(): void {
		?>
		<script id="atlas-customizer-switch-scripts">
			(function() {
				if (typeof wp === 'undefined' || !wp.customize) {
					return;
				}
				wp.customize.bind('ready', function() {
					document.addEventListener('change', function(e) {
						if (e.target && e.target.classList.contains('atlas-switch-input')) {
							var container = e.target.closest('.atlas-switch');
							if (!container) return;
							var status = container.querySelector('.atlas-switch-status');
							if (status) {
								status.textContent = e.target.checked ? 'ON' : 'OFF';
							}
						}
					});
				});
			})();
		</script>
		<?php
	}
}
