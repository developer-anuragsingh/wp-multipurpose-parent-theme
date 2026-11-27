=== Atlas Multipurpose ===
Contributors: developer-anuragsingh
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.6.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: block-theme, full-site-editing, custom-colors, custom-spacing, custom-typography, wide-blocks, one-column, two-columns

A fast, accessible Full-Site Editing parent theme with the Interactivity API, Speculation Rules, Block Bindings, Schema.org structured data and a Content Security Policy.

== Description ==

Atlas Multipurpose is a block (Full-Site Editing) parent theme built for speed, accessibility and security. Build your site with child themes (two are included in the project: Atlas Business and the Atlas Child starter).

Highlights:

* Performance: no jQuery, self-hosted fonts, Speculation Rules prerendering and no layout shift from the header or interactive controls. Target: Lighthouse mobile Performance 90 or higher.
* Security headers: X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy and HSTS (HTTPS only). Headers already set by your server or a plugin are never overwritten.
* Content Security Policy: enforced in production, report-only in development and staging. Inline scripts are allowed only by per-request nonce or hash; there is no 'unsafe-inline' for scripts.
* Contact form: signed, cache-safe form token, rate limiting, honeypot, header-injection protection and private storage of submissions.
* Interactivity API: dark mode toggle, off-canvas navigation that also works without JavaScript, copy-quote button, live form validation and archive infinite scroll with a paginated fallback.
* SEO: canonical, Open Graph, Twitter cards, hreflang, meta description and Schema.org JSON-LD. The theme steps back when an SEO plugin is active.
* AI discovery (opt-in): /llms.txt catalogue and a Markdown view of posts (?format=md).
* English and Hindi translations, with a front-end EN/HI language switcher.
* WooCommerce block templates and HPOS compatibility.

== Installation ==

1. Download atlas-theme-<version>.zip from the GitHub Releases page (https://github.com/developer-anuragsingh/wp-multipurpose-parent-theme/releases). You can check the file against the .sha256 checksum published next to it.
2. In your WordPress admin, go to Appearance > Themes > Add New > Upload Theme.
3. Choose the zip, click Install Now, then Activate.
4. Optional: install and activate a child theme (Atlas Business or your own) so your changes survive theme updates.

== Frequently Asked Questions ==

= A plugin's script is blocked by the Content Security Policy. What do I do? =

In production the policy is enforced. Allow the plugin's origin with the atlas_csp_directives filter, or set the mode in wp-config.php: define( 'ATLAS_CSP_MODE', 'report-only' ); while you test. Accepted values are off, report-only and enforce.

= I use a full-page cache. Does the contact form still work? =

Yes. The form does not rely on a WordPress nonce. It fetches a fresh signed token from a no-store REST endpoint, so cached pages keep working.

= How do I report a security problem? =

Please report it privately as described in SECURITY.md in the project repository (https://github.com/developer-anuragsingh/wp-multipurpose-parent-theme/security). Do not open a public issue.

== Privacy ==

* Contact form submissions are stored as private posts, visible only to administrators, and deleted automatically after 90 days (filter: atlas_submission_retention_days). They are included in WordPress's personal data export and erase tools.
* The language switcher stores the chosen language in the atlas_locale cookie (one year, HttpOnly, SameSite=Lax, Secure on HTTPS). It holds only the language code.
* The dark mode toggle stores the chosen mode in the browser's localStorage (atlas-theme-mode). It is never sent to the server.
* Fonts are self-hosted, and the theme loads no third-party scripts, fonts or trackers. The Contact Us page's Google map is click-to-load: nothing is requested from Google until the visitor clicks "Show map" (Google then receives the visitor's IP address and may set cookies). Turn the map off in Appearance > Customize > Atlas: Contact Page & Channels > Show Google Maps Section.

== Changelog ==

The full changelog is in CHANGELOG.md in the project repository.

= 1.6.2 =
* Fixed: the theme's own spacing scale is now applied (WordPress was replacing it with its defaults).
* Fixed: the copy-quote button no longer shifts the page when it appears.

= 1.6.1 =
* Fixed: the live-site end-to-end test only runs when a site URL is configured.

= 1.6.0 =
* Added: business bento, team, logos, process and call-to-action patterns.
* Added: translatable 404 template and font licence files.
* Security: style nonces in the Content Security Policy and optional enforcement on staging.

= 1.5.0 =
* Added: new templates, palette tokens and a dark style variation.

= 1.4.0 =
* Added: Atlas Business child theme.
* Added: Contact Us page with on/off switches and direct contact channels.
* Added: toggle switches for Customizer settings.
* Added: live contact form validation and a phone field.
* Fixed: "Tested up to", screenshot, licence and comments issues found in review.

= 1.3.0 =
* Added: WooCommerce Product schema (only when WooCommerce is active) and Markdown tables.
* Security: Content Security Policy enforced in production.

= 1.2.0 =
* Added: SEO, AI crawler discovery and schema metadata improvements.

= 1.1.0 =
* Added: security headers, signed contact form token, rate limiting, private submission storage, JSON-LD hardening, accessibility and performance gates, and the EN/HI language switcher.

= 1.0.0 =
* Initial release of Atlas Multipurpose Parent Theme.

== Copyright & Licenses ==

Atlas Multipurpose WordPress Theme, (C) 2026 Anurag Singh.
Atlas Multipurpose is distributed under the terms of the GNU General Public License v2 or later.

This theme bundles the following third-party fonts:

* Inter Font (Rasmus Andersson)
  Source: https://github.com/rsms/inter
  License: SIL Open Font License, 1.1 (OFL)
  License URI: https://openfontlicense.org/

* Sora Font (Jonathan Barnbrook, Julian Moncada)
  Source: https://github.com/googlefonts/sora
  License: SIL Open Font License, 1.1 (OFL)
  License URI: https://openfontlicense.org/
