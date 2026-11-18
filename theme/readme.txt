=== Atlas Multipurpose ===
Contributors: developer-anuragsingh
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: block-theme, full-site-editing, custom-colors, custom-spacing, custom-typography, wide-blocks, one-column, two-columns

A modern, high-performance Full-Site Editing (FSE) parent theme engineered with native WordPress Interactivity API, Speculation Rules, Block Bindings, Schema.org SEO, and bank-grade Content Security Policy.

== Description ==

Atlas Multipurpose is an ultra-fast, accessible, security-hardened WordPress Full-Site Editing parent theme.

Key Architectural Highlights:
* 100/100 Core Web Vitals performance target.
* Strict Content Security Policy (CSP) with dynamic per-request cryptographic nonces and zero unsafe inline scripts.
* Built-in timed form tokens and rate limiting for contact submissions.
* Native WordPress Interactivity API runtime for dark mode toggle, copy buttons, live form validation, and infinite scroll.
* Clean Zero-PHP Child Theme architecture compatibility.

== Installation ==

1. In your WordPress admin, navigate to Appearance > Themes > Add New.
2. Upload the `atlas-theme.zip` package (built via `scripts/build-release.sh`).
3. Click Install Now, and then Activate.

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

== Changelog ==

= 1.3.0 =
* Added real-time live typing data validation and focusout validation for contact form.
* Added phone number support with strict international validation and sanitization.
* Added modern interactive toggle switches for Customizer settings.
* Enhanced accessibility and WCAG AA contrast compliance.

= 1.2.0 =
* Added comprehensive Schema.org JSON-LD generation with fragment caching.
* Added Speculation Rules API support for instant prerendering.
* Added multi-language hreflang and language switcher.

= 1.0.0 =
* Initial release of Atlas Multipurpose Parent Theme.
