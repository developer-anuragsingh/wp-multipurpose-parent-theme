# Multi-Purpose WordPress Parent Theme — Master Technical Plan

**Document Identifier:** `docs/README.md`  
**Current Version:** 1.0.0  
**Target Environment:** WordPress 6.5 – 7.1+  
**Architecture:** Full-Site Editing (FSE) & Native Block Engine  
**Repository Model:** Modular Multi-File Documentation with Code Isolation  

---

## 1. Executive Summary & Architectural Philosophy
This parent theme is engineered as an enterprise-grade base for four core niches: **Corporate**, **E-Commerce (WooCommerce)**, **NGO/Non-Profit**, and **High-Conversion Landing / Coming Soon pages**. 

The technical architecture abandons legacy WordPress themes (monolithic PHP template hooks, jQuery dependencies, and bulky CSS frameworks) in favor of:
* **Native FSE Engine:** Styling and layout delegation driven by `theme.json` v3 design tokens, semantic HTML template parts, and block patterns.
* **Zero-KB Initial JavaScript Footprint:** Frontend interactivity is implemented strictly via the WordPress Interactivity API, ensuring 0 KB client-side JS on pages without interactive components.
* **Generative Engine Optimization (GEO):** Native machine-readable endpoints (`llms.txt`, `?format=md`) and JSON-LD schema graphs tailored for AI agents (ChatGPT, Gemini, Perplexity, Claude).
* **System-Level Performance:** Native browser prefetching via the Speculation Rules API, modern image format enforcement (AVIF/WebP), and container-query-driven responsive layouts.
* **Bilingual & Localization Ready:** Native gettext domain scoping with built-in English (`en_US`) and Hindi (`hi_IN`) translation catalogs, alongside complete RTL adaptation.

---

## 2. Modular Documentation Navigation Matrix
Each technical domain is maintained in an isolated sub-document. Use the links below to access the deep-dive technical specifications and implementation guides:

| Module Directory | Topic & Scope | Detailed Specification File |
| :--- | :--- | :--- |
| `01-architecture/` | Directory hierarchy, `theme.json` cascading, template inheritance, child-theme rules. | [`01-fse-overview.md`](./01-architecture/01-fse-overview.md)<br>[`02-child-theme-strategy.md`](./01-architecture/02-child-theme-strategy.md) |
| `02-frontend-and-styles/` | WordPress Interactivity API stores, Tailwind CSS build setup, native Dark/Light mode engine. | [`01-interactivity-api.md`](./02-frontend-and-styles/01-interactivity-api.md)<br>[`02-tailwind-and-css.md`](./02-frontend-and-styles/02-tailwind-and-css.md)<br>[`03-dark-light-mode.md`](./02-frontend-and-styles/03-dark-light-mode.md) |
| `03-ai-and-seo/` | `llms.txt` rewrites, raw Markdown endpoints, entity-level JSON-LD schemas, Answer-First patterns. | [`01-geo-llms-txt.md`](./03-ai-and-seo/01-geo-llms-txt.md)<br>[`02-markdown-endpoints.md`](./03-ai-and-seo/02-markdown-endpoints.md)<br>[`03-json-ld-schema.md`](./03-ai-and-seo/03-json-ld-schema.md) |
| `04-modern-wp-features/` | Block Bindings API (custom meta to core blocks), Speculation Rules API, Synced Pattern Overrides. | [`01-block-bindings.md`](./04-modern-wp-features/01-block-bindings.md)<br>[`02-speculation-rules.md`](./04-modern-wp-features/02-speculation-rules.md)<br>[`03-synced-pattern-overrides.md`](./04-modern-wp-features/03-synced-pattern-overrides.md) |
| `05-niches-and-usecases/` | WooCommerce Cart/Checkout blocks, corporate layouts, NGO donation forms, coming-soon switches. | [`01-woocommerce.md`](./05-niches-and-usecases/01-woocommerce.md)<br>[`02-corporate-and-ngo.md`](./05-niches-and-usecases/02-corporate-and-ngo.md)<br>[`03-coming-soon.md`](./05-niches-and-usecases/03-coming-soon.md) |
| `06-security-and-i18n/` | Nonces, sanitization, user data security, POT/PO generation, English and Hindi (`hi_IN`) translation. | [`01-security-and-data.md`](./06-security-and-i18n/01-security-and-data.md)<br>[`02-internationalization.md`](./06-security-and-i18n/02-internationalization.md) |

---

## 3. Core Technical Pillars & Architecture

### A. Full-Site Editing (FSE) & `theme.json` Engine
* **Schema Standards:** Configured via `theme.json` Version 3.
* **Zero CSS Bloat:** Layouts, fluid typography, spacing scales, and core color tokens are strictly declared in `theme.json`, letting WordPress output optimized CSS custom properties (`--wp--preset--*`).
* **Appearance Tools:** Enabled (`"appearanceTools": true`) to expose advanced typography, margin, padding, and border controls natively in Gutenberg without custom CSS overrides.
* **Template Separation:** UI structures reside in `/templates/` (`index.html`, `single.html`, `page.html`, `archive.html`, `404.html`) and reusable components in `/parts/` (`header.html`, `footer.html`).

### B. Frontend Interactivity & Styling Strategy
* **WordPress Interactivity API:** Interactive UI components (e.g., mobile navigation drawer, dark mode toggle, live search modal, donation sliders) use declarative HTML directives (`data-wp-interactive`, `data-wp-context`, `data-wp-on--click`) backed by Preact Signals.
* **Tailwind CSS Utility Scoping:** 90% of styling relies entirely on `theme.json`. For custom niche components where native blocks are insufficient, a tree-shaken Tailwind CSS compiler generates a minimal utility footprint (target size: < 10 KB) without interfering with Gutenberg editor styles.
* **Flicker-Free Dark/Light Mode:** A head-injected runtime script evaluates `localStorage` and `prefers-color-scheme` to prevent Flash of Unstyled Content (FOUC), dynamically updating CSS token definitions under `[data-theme="dark"]`.

### C. Generative Engine Optimization (GEO) & Machine Discoverability
* **Automated `llms.txt`:** Serves a plain-text markdown catalog at `/llms.txt` and `/llms-full.txt` mapping site hierarchy, authoritative references, and core service offerings directly to AI crawlers.
* **Clean Markdown Endpoints (`?format=md`):** Template redirect filters strip HTML markup and document chrome on singular posts and pages, returning pure semantic markdown for scraping agents.
* **Structured Data Graph:** Injects rich JSON-LD graph entities (`Organization`, `WebSite`, `Product`, `FAQPage`, `TechArticle`) validated against Schema.org standards.
* **Answer-First Content Architecture:** Block patterns place concise factual summaries, definitions, and key data points above the fold to maximize direct AI citations.

### D. Advanced WordPress Core Feature Integrations
* **Block Bindings API:** Connects post metadata and custom fields directly to core blocks (`core/paragraph`, `core/heading`, `core/image`) using `register_block_bindings_source()`, eliminating custom dynamic block overhead.
* **Speculation Rules API:** Injects dynamic prefetch/prerender rules into the document `<head>`, enabling near-instant page transitions when a user hovers over internal links.
* **Synced Pattern Overrides:** Leverages block pattern overrides so teams can synchronize layout structures globally while permitting localized text and media changes per page.

---

## 4. Multi-Purpose Implementation Specifications

```text
                                  ┌──────────────────────────┐
                                  │   Nexus Parent Theme     │
                                  │ (FSE Engine & Core API)  │
                                  └─────────────┬────────────┘
         ┌──────────────────────┬───────────────┴──────────────┬──────────────────────┐
         ▼                      ▼                              ▼                      ▼
┌──────────────────┐   ┌──────────────────┐   ┌──────────────────┐   ┌──────────────────┐
│    Corporate     │   │    E-Commerce    │   │  NGO / Non-Profit│   │   Coming Soon    │
│ • Answer-First   │   │ • Product Grid   │   │ • Cause Showcase │   │ • Lead Capture   │
│ • Team & Stats   │   │ • Cart Block     │   │ • Fast Donation  │   │ • Minimal Header │
│ • Service Grids  │   │ • Checkout Block │   │ • Impact Numbers │   │ • 503 Maintenance│
└──────────────────┘   └──────────────────┘   └──────────────────┘   └──────────────────┘