# Multi-Purpose WordPress Parent Theme — Master Technical Plan

**Document Identifier:** `docs/README.md`  
**Current Version:** 1.4.0 (Separated Quotes and Editorial Blogs into distinct niches)  
**Target Environment:** WordPress 6.5 – 7.1+  
**Architecture:** Full-Site Editing (FSE) & Native Block Engine  
**Repository Model:** Modular Multi-File Documentation with Code Isolation  

---

## 1. Executive Summary & Architectural Philosophy
This parent theme is engineered as an enterprise-grade base for six core niches: **Corporate**, **E-Commerce (WooCommerce)**, **NGO/Non-Profit**, **Quotes & Status**, **Editorial Blogs**, and **High-Conversion Landing pages**. 

The technical architecture abandons legacy WordPress themes in favor of:
* **Native FSE Engine:** Styling and layout delegation driven by `theme.json` v3 design tokens, semantic HTML template parts, and block patterns.
* **Modern CSS & Grid:** Direct utilization of native Grid blocks, negative margins, container queries, cascade layers (`@layer`), and the `:has()` selector.
* **Zero-KB Initial JavaScript Footprint:** Frontend interactivity (e.g., 'Copy Quote', Infinite Scroll) is implemented strictly via the WordPress Interactivity API with full ARIA accessibility.
* **Generative Engine Optimization (GEO):** Native machine-readable endpoints (`llms.txt`, `?format=md`) and JSON-LD schema graphs tailored for AI agents (ChatGPT, Gemini, Perplexity, Claude).
* **Local-to-Live DevOps:** Optimized for local development via WordPress Studio (WASM + SQLite) with automated GitHub Actions CI/CD deployment pipelines.
* **Bilingual & Localization Ready:** Native gettext domain scoping with built-in English (`en_US`) and Hindi (`hi_IN`) translation catalogs, alongside complete RTL adaptation.

---

## 2. Modular Documentation Navigation Matrix
Each technical domain is maintained in an isolated sub-document. Use the links below to access the deep-dive technical specifications and implementation guides:

| Module Directory | Topic & Scope | Detailed Specification File |
| :--- | :--- | :--- |
| `01-architecture/` | Directory hierarchy, Zero-PHP Child Themes, template inheritance, and Font Library API integration. | [`01-fse-overview.md`](./01-architecture/01-fse-overview.md)<br>[`02-child-theme-strategy.md`](./01-architecture/02-child-theme-strategy.md) |
| `02-frontend-and-styles/` | WordPress Interactivity API stores, Container Queries, Cascade Layers, Tailwind CSS integration, Dark/Light mode engine, WCAG 2.2 AA accessibility, and two-layer form validation (no JS library). | [`01-interactivity-api.md`](./02-frontend-and-styles/01-interactivity-api.md)<br>[`02-tailwind-and-modern-css.md`](./02-frontend-and-styles/02-tailwind-and-modern-css.md)<br>[`03-dark-light-mode.md`](./02-frontend-and-styles/03-dark-light-mode.md)<br>[`04-accessibility.md`](./02-frontend-and-styles/04-accessibility.md)<br>[`05-forms-and-validation.md`](./02-frontend-and-styles/05-forms-and-validation.md) |
| `03-ai-and-seo/` | `llms.txt` rewrites, raw Markdown endpoints, entity-level JSON-LD schemas, Answer-First patterns. | [`01-geo-llms-txt.md`](./03-ai-and-seo/01-geo-llms-txt.md)<br>[`02-markdown-endpoints.md`](./03-ai-and-seo/02-markdown-endpoints.md)<br>[`03-json-ld-schema.md`](./03-ai-and-seo/03-json-ld-schema.md) |
| `04-modern-wp-features/` | Block Bindings API, Speculation Rules API, Synced Pattern Overrides, Section Styles. | [`01-block-bindings.md`](./04-modern-wp-features/01-block-bindings.md)<br>[`02-speculation-rules.md`](./04-modern-wp-features/02-speculation-rules.md)<br>[`03-synced-pattern-overrides.md`](./04-modern-wp-features/03-synced-pattern-overrides.md) |
| `05-niches-and-usecases/` | WooCommerce, Corporate, NGO, Quotes, Blogs, and Landing Page templates. | [`01-woocommerce.md`](./05-niches-and-usecases/01-woocommerce.md)<br>[`02-corporate-and-ngo.md`](./05-niches-and-usecases/02-corporate-and-ngo.md)<br>[`03-quotes-and-status.md`](./05-niches-and-usecases/03-quotes-and-status.md)<br>[`04-editorial-blogs.md`](./05-niches-and-usecases/04-editorial-blogs.md)<br>[`05-coming-soon.md`](./05-niches-and-usecases/05-coming-soon.md) |
| `06-security-and-i18n/` | Authorization (capability checks), input sanitization, prepared statements, nonces/CSRF, REST & Interactivity endpoint hardening, CSP, data-protection governance (PII, secrets, PCI, logging, consent, compliance), WordPress Studio Sync, English and Hindi (`hi_IN`) translation. | [`01-security-and-data.md`](./06-security-and-i18n/01-security-and-data.md)<br>[`02-devops-and-i18n.md`](./06-security-and-i18n/02-devops-and-i18n.md)<br>[`03-data-protection.md`](./06-security-and-i18n/03-data-protection.md) |
| `07-performance-and-qa/` | Core Web Vitals performance budget, asset weight limits, and the QA / testing / CI pipeline (PHPCS, lint, Theme Check, Lighthouse). | [`01-performance-budget.md`](./07-performance-and-qa/01-performance-budget.md)<br>[`02-testing-and-ci.md`](./07-performance-and-qa/02-testing-and-ci.md) |

---

## 3. Core Technical Pillars & Architecture

### A. Full-Site Editing (FSE) & `theme.json` Engine
* **Appearance Tools Enforced:** Enabled (`"appearanceTools": true`) in `theme.json` to expose advanced typography, padding, borders, native grid layouts (`layout.type: "grid"`), and negative margins directly in Gutenberg, eliminating the need for heavy CSS frameworks.
* **Zero-PHP Child Theme Architecture:** Strict inheritance model where child themes require only a folder, a `style.css` (with `Template: parent-theme-slug`), and a `theme.json` file. Template overrides rely purely on `theme.json` cascading and HTML template overrides, bypassing legacy `functions.php` and `wp_enqueue_scripts` overhead.

### B. Frontend Interactivity & Modern CSS Strategy
* **WordPress Interactivity API:** Interactive UI components use declarative HTML directives backed by Preact Signals. Used for dynamic ARIA states, instant "Copy to Clipboard" functionality, and infinite scrolling.
* **Block-Based Navigation:** Abandons classic `Appearance → Menus` in favor of the native `wp_navigation` custom post type. All menu items and sub-menus are managed directly within the Site Editor using the Navigation Block.
* **Modern CSS Architecture:** Bypasses legacy frameworks in favor of Container Queries, Cascade Layers (`@layer`), and the `:has()` pseudo-class for complex layout state management.

### C. Generative Engine Optimization (GEO) & AI Discoverability
* **Automated `llms.txt`:** Serves a plain-text markdown catalog mapping site hierarchy to AI crawlers.
* **Structured Data Graph & Answer-First:** Injects rich JSON-LD graph entities (`Organization`, `WebSite`, `FAQPage`, `Quotation`, `Article`, `BlogPosting`) and utilizes "Answer-First" block patterns placing concise factual summaries at the top.

### D. Advanced WordPress Core Feature Integrations
* **Block Bindings API:** Connects post metadata and custom fields directly to core blocks (`core/paragraph`, `core/heading`, `core/image`).
* **Speculation Rules API:** Injects dynamic prefetch/prerender rules into the document `<head>`, enabling near-instant page transitions.

---

## 4. Multi-Purpose Implementation Specifications

```text
                                         ┌──────────────────────────┐
                                         │   Nexus Parent Theme     │
                                         │ (FSE Engine & Core API)  │
                                         └─────────────┬────────────┘
     ┌───────────────────┬───────────────────┬─────────┴─────────┬───────────────────┬───────────────────┐
     ▼                   ▼                   ▼                   ▼                   ▼                   ▼
┌─────────┐         ┌──────────┐        ┌─────────┐         ┌─────────┐         ┌─────────┐         ┌─────────┐
│Corporate│         │E-Commerce│        │ Quotes  │         │  Blogs  │         │   NGO   │         │ Landing │
└─────────┘         └──────────┘        └─────────┘         └─────────┘         └─────────┘         └─────────┘