# 02. End-User & Operator Guide

**File:** `docs/09-monitoring-and-usage/02-end-user-guide.md`
**Module:** Monitoring & Usage

> Every other doc targets developers. This one targets the **site owner / administrator / content editor** who uses the theme — no code required. Keep it in plain, task-oriented language.

## 1. Who This Is For
* **Site administrators** installing and configuring the theme.
* **Content editors** building pages with the Site Editor and block patterns.
* **Operators** responsible for backups, updates, and privacy settings.

## 2. Installation & Activation
1. Requirements: WordPress 6.8+ (tested to 7.1.1), PHP 8.1+. The 6.8 floor is required because the theme relies on core Speculation Rules (6.8), theme.json v3 and Synced Pattern Overrides (6.6), and the Interactivity API / Block Bindings (6.5).
2. Upload the theme (or install a child theme — see §4) and activate under **Appearance → Themes**.
3. Open **Appearance → Editor** (the Site Editor) — this replaces the old Customizer and Menus screens.

## 3. Editing Your Site (Site Editor basics)
* **Global styles:** colors, typography, and spacing are set once in Styles and apply site-wide.
* **Templates & parts:** edit the header, footer, and page layouts visually under Templates/Patterns.
* **Navigation:** menus are managed with the Navigation block inside the Site Editor (not the old Appearance → Menus).
* **Patterns:** insert ready-made sections (hero, pricing, FAQ, testimonials) from the pattern inserter.
* **Dark/Light mode:** the theme ships a flicker-free toggle; visitors' choice is remembered.

## 4. Using a Child Theme (recommended for customization)
To customize safely without losing changes on update, use a child theme. You only need a folder, a `style.css` (with `Template: wp-multipurpose-parent-theme`), and optionally a `theme.json` for color/spacing overrides — no PHP. Full steps: `01-architecture/02-child-theme-strategy.md`.

## 5. Niche Setup Pointers
* **E-Commerce:** install WooCommerce; the theme uses native cart/checkout blocks — see `05-niches-and-usecases/01-woocommerce.md`.
* **Coming Soon / Landing:** use the blank template and landing footer part — `05-niches-and-usecases/05-coming-soon.md`.
* **Blog / Corporate / NGO / Quotes:** apply the matching patterns from the inserter.

## 6. Privacy & Compliance (operator duties)
The theme provides safe defaults and consent hooks, but these are YOUR responsibility to complete:
* Publish a privacy policy and link it from forms.
* Configure consent for any analytics/embeds you add (nothing non-essential loads before consent).
* Set data-retention expectations for form/donation submissions.
See `06-security-and-i18n/03-data-protection.md` for the full standard.

## 7. Performance & Languages
* The theme is built for speed (self-hosted fonts, zero-KB JS baseline, prefetching) — avoid adding heavy plugins/page builders that undo this.
* English and Hindi (`hi_IN`) ship built in; switch site language under **Settings → General**.

## 8. Maintenance: Backups, Updates, Recovery
* **Back up files + database before every update** (use your host's or a backup plugin's tooling).
* **Update on staging first** when possible, then production.
* **Harden login:** enable your host's rate-limiting/2FA for `wp-login.php`; the theme can't do this for you (`08-reliability-and-seo/04-rate-limiting-and-abuse.md`).
* **If an update breaks the site:** WordPress recovery mode emails you a safe-mode link; revert to the previous theme version (`08-reliability-and-seo/05-updates-and-recovery.md`).

## 9. Getting Help
* Developer/architecture reference: `docs/README.md` navigation matrix.
* Report issues via the theme repository.
