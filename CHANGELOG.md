# Changelog

All notable changes to the **Atlas Multipurpose WordPress Parent Theme** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Planned
- Dedicated visual regression baselines for key WooCommerce and landing page patterns.
- Optional `/llms-full.txt` full-content Markdown catalog expansion.
- Child theme boilerplate generator script.

---

## [1.0.0] - 2026-10-28

### Added

#### E-Commerce & WooCommerce Block Theming
- **WooCommerce FSE Block Templates:** Added 8 dedicated Full-Site Editing templates under `theme/templates/`:
  - `single-product.html` (single product canvas with breadcrumbs)
  - `archive-product.html` (main product catalog using `woocommerce/product-collection`)
  - `taxonomy-product_cat.html` (category archive)
  - `taxonomy-product_tag.html` (tag archive)
  - `product-search-results.html` (product search results with empty state)
  - `page-cart.html` (cart block experience)
  - `page-checkout.html` (checkout block experience)
  - `order-confirmation.html` (order received summary and totals)
- **High-Performance Order Storage (HPOS):** Declared explicit compatibility with `custom_order_tables` and `cart_checkout_blocks` in `Atlas\Setup`.
- **Product Gallery Supports:** Added `woocommerce`, `wc-product-gallery-zoom`, `wc-product-gallery-lightbox`, and `wc-product-gallery-slider` theme supports.

#### Reliability & 503 Maintenance Mode
- **503 Maintenance Mode Controller (`Atlas\Maintenance`):** Introduced a production-grade maintenance controller hooked into `template_redirect` (priority 0) with filter `atlas_maintenance_mode` or option `atlas_maintenance_mode`. Emits proper `HTTP/1.1 503 Service Unavailable`, `Retry-After: 3600`, and `noindex, nofollow, noarchive` headers. Bypasses requests for logged-in administrators with `manage_options` capability, and gracefully defers to WooCommerce native Coming Soon mode (`woocommerce_coming_soon`) when WooCommerce handles it.
- **Maintenance FSE Template (`maintenance.html`):** Added a dedicated Full-Site Editing template under `theme/templates/maintenance.html` and registered it under `customTemplates` in `theme.json` for site editors.

#### Performance & Core Web Vitals
- **Server-Side Transient Caching:** Implemented 12-hour fragment caching for heavy server-rendered computations (`Atlas\Schema` JSON-LD graphs and `Atlas\Llms_Txt` catalogs) using WordPress Transients API (`set_transient`, `get_transient`), with automatic cache invalidation on `save_post`, `deleted_post`, and `switch_theme`.
- **Speculation Rules API (`Atlas\Speculation_Rules`):** Tuned Core 6.8+ Speculative Loading to `prefetch` mode with `moderate` eagerness; excluded sensitive commerce and admin paths (`/cart/*`, `/checkout/*`, `/my-account/*`, `/*add-to-cart=*`); suppressed prefetching during 503 maintenance mode.
- **Critical Font Preloading (`Atlas\Assets`):** Injected `<link rel="preload">` in `<head>` for `inter-variable.woff2` to optimize FCP and eliminate font-swap layout shift (CLS).
- **LCP Hero Image Prioritization:** Automatically applies `fetchpriority="high"` and removes lazy-loading from the first above-the-fold content image via `wp_content_img_tag`.

#### Niche-Specific Patterns & Layouts
- **Quotes & Status Masonry Grid (`atlas/quotes-masonry`):** High-volume quotes layout using 0 KB JavaScript CSS columns and `@container` queries (`.atlas-masonry-grid`). Integrates with `is-style-atlas-copyable` and the `atlas/copy` Interactivity API store for accessible one-click clipboard copying.
- **NGO Impact Metrics Grid (`atlas/impact-metrics`):** High-contrast impact metrics grid for charitable and non-profit organizations, featuring key performance indicators, 4-column metric cards, and accessible `role="region"` ARIA landmarks.
- **Editorial Blog Table of Contents (`atlas/editorial-toc`):** Article reading companion featuring estimated reading time, verification badges, and a structured `<nav>` Table of Contents with deep anchor links for human navigation and GEO parsing.

#### Automated Testing & CI/CD Pipeline
- **PHP Unit Test Suite:** Lightweight, database-free unit test environment (`tests/bootstrap.php`, `tests/run-tests.php`) covering Speculation Rules, Robots AI policies, Block Bindings, Contact Form security, Transient Caching, 503 Maintenance, and Niche Patterns (31 tests total).
- **PHPUnit Configuration:** Standard `phpunit.xml.dist` configuration file.
- **JavaScript Unit Tests:** Native Node.js test runner suite (`tests/js/ui-states.test.js`) validating REST client outcome classification and contact form client-side contracts.
- **CI Workflow Integration:** Updated `.github/workflows/ci.yml` to automatically execute `composer test` and `npm test` on every push and pull request.

#### Localization & Internationalization (i18n)
- **Translation Catalogs:** Extracted canonical `theme/languages/atlas-theme.pot` catalog using GNU gettext.
- **Hindi Translation Bundle:** Authored complete Hindi translation catalog `theme/languages/hi_IN.po` and compiled binary `theme/languages/hi_IN.mo`, including full translations for all new niche patterns.

#### Visual Assets & Branding
- **Default Social Sharing Card:** Designed and compiled high-resolution 1200x630 branded asset `theme/assets/images/og-default.png` matching `--wp--preset--color--dark`.

#### AI Discoverability (GEO) & Traditional SEO
- **Structured Data Engine (`Atlas\Schema`):** Emits server-side JSON-LD `@graph` (`Organization`, `WebSite`, `BlogPosting` with E-E-A-T signals, `BreadcrumbList`) with automatic back-off when third-party SEO plugins (Yoast, Rank Math, SEOPress) are active.
- **On-Page SEO Meta (`Atlas\Seo`):** Emits Open Graph, Twitter Cards, canonical links, and `noindex` directives on 404, search, and maintenance views.
- **AI Crawler Management (`Atlas\Robots`):** Filter on virtual `robots_txt` exposing operator policy controls for named AI crawlers (`GPTBot`, `ClaudeBot`, `PerplexityBot`, etc.).
- **Markdown & AI Endpoints:** Opt-in clean Markdown endpoint `?format=md` (`Atlas\Markdown_Endpoint`) and dynamic `/llms.txt` catalog (`Atlas\Llms_Txt`).

#### Modern Core Features & Form Security
- **Block Bindings API (`Atlas\Bindings`):** Registered `atlas/post-meta` source allowing inline editing of `atlas_subtitle` in WordPress 6.7+ Site Editor.
- **Secured Contact Form (`Atlas\Contact_Form`):** Public REST endpoint (`/wp-json/atlas/v1/contact`) featuring transient-backed IP rate-limiting (HTTP 429 + `Retry-After`), honeypot field, and submit timing validation.
- **Block Patterns Catalog:** Expanded to 14 production-ready patterns (Answer-First Hero, Key Takeaways, Stats Band, Feature Grid, Pricing Table, Testimonials, FAQ, Contact Form, Empty States, Quotes Masonry, NGO Impact Metrics, Editorial ToC).

#### Foundation & Frontend Interactivity
- **FSE Architecture:** Full-Site Editing foundation driven by `theme.json` v3 design tokens, fluid typography (Inter + Sora), and zero-bloat template parts.
- **Interactivity API:** Zero-KB initial JS baseline powering accessible mobile navigation drawer, flicker-free dark/light mode toggle with per-request CSP nonce, and click-to-copy quote blocks.
- **Accessible UI States:** Standardized async notification system (`parts/notifications.html`, `ui-states.css`) supporting offline banners, slow-request notices (8s), loading skeletons, and session expiration modals.

### Compliance & Quality Gates
- **PHPCS:** 100% compliant with `WordPress` and `WordPress-Extra` rulesets (0 errors, 0 warnings across all 27 PHP files).
- **Linters:** ESLint and Stylelint pass with 0 errors.
- **Accessibility:** Conforms to WCAG 2.2 Level AA guidelines.
