# Multi-Purpose WordPress Parent Theme — Master Technical Plan

**Document Identifier:** `docs/README.md`  
**Current Version:** 1.2.0 (Added Content-Rich/Blogging & Quotes Niche)  
**Target Environment:** WordPress 6.5 – 6.7+  
**Architecture:** Full-Site Editing (FSE) & Native Block Engine  
**Repository Model:** Modular Multi-File Documentation with Code Isolation  

---

## 1. Executive Summary & Architectural Philosophy
This parent theme is engineered as an enterprise-grade base for five core niches: **Corporate**, **E-Commerce (WooCommerce)**, **NGO/Non-Profit**, **Content/Blogs (Quotes & Status)**, and **High-Conversion Landing pages**. 

The technical architecture abandons legacy WordPress themes in favor of:
* **Native FSE Engine:** Styling and layout delegation driven by `theme.json` v3 design tokens, semantic HTML template parts, and block patterns.
* **Modern CSS & Grid:** Direct utilization of native Grid blocks, negative margins, container queries, cascade layers (`@layer`), and the `:has()` selector.
* **Zero-KB Initial JavaScript Footprint:** Frontend interactivity (e.g., 'Copy Quote', Infinite Scroll) is implemented strictly via the WordPress Interactivity API with full ARIA accessibility, ensuring 0 KB client-side JS on static pages.
* **Generative Engine Optimization (GEO):** Native machine-readable endpoints (`llms.txt`, `?format=md`) and JSON-LD schema graphs tailored for AI agents (ChatGPT, Gemini, Perplexity, Claude).
* **System-Level Performance:** Native browser prefetching via the Speculation Rules API, Font Library API local hosting, modern image format enforcement (AVIF/WebP), and native lazy loading.
* **Bilingual & Localization Ready:** Native gettext domain scoping with built-in English (`en_US`) and Hindi (`hi_IN`) translation catalogs, alongside complete RTL adaptation.

---

## 2. Modular Documentation Navigation Matrix
Each technical domain is maintained in an isolated sub-document. Use the links below to access the deep-dive technical specifications and implementation guides:

| Module Directory | Topic & Scope | Detailed Specification File |
| :--- | :--- | :--- |
| `01-architecture/` | Directory hierarchy, `theme.json` cascading, template inheritance, child-theme rules, and Font Library API integration. | [`01-fse-overview.md`](./01-architecture/01-fse-overview.md)<br>[`02-child-theme-strategy.md`](./01-architecture/02-child-theme-strategy.md) |
| `02-frontend-and-styles/` | WordPress Interactivity API stores, Container Queries, Cascade Layers, Tailwind CSS integration, and Dark/Light mode engine. | [`01-interactivity-api.md`](./02-frontend-and-styles/01-interactivity-api.md)<br>[`02-tailwind-and-modern-css.md`](./02-frontend-and-styles/02-tailwind-and-modern-css.md)<br>[`03-dark-light-mode.md`](./02-frontend-and-styles/03-dark-light-mode.md) |
| `03-ai-and-seo/` | `llms.txt` rewrites, raw Markdown endpoints, entity-level JSON-LD schemas (incl. `Quotation`, `Article`), Answer-First patterns. | [`01-geo-llms-txt.md`](./03-ai-and-seo/01-geo-llms-txt.md)<br>[`02-markdown-endpoints.md`](./03-ai-and-seo/02-markdown-endpoints.md)<br>[`03-json-ld-schema.md`](./03-ai-and-seo/03-json-ld-schema.md) |
| `04-modern-wp-features/` | Block Bindings API (custom meta to core blocks), Speculation Rules API, Synced Pattern Overrides, Section Styles. | [`01-block-bindings.md`](./04-modern-wp-features/01-block-bindings.md)<br>[`02-speculation-rules.md`](./04-modern-wp-features/02-speculation-rules.md)<br>[`03-synced-pattern-overrides.md`](./04-modern-wp-features/03-synced-pattern-overrides.md) |
| `05-niches-and-usecases/` | WooCommerce, corporate layouts, NGO forms, coming-soon switches, and Content/Blog (Quotes) templates. | [`01-woocommerce.md`](./05-niches-and-usecases/01-woocommerce.md)<br>[`02-corporate-and-ngo.md`](./05-niches-and-usecases/02-corporate-and-ngo.md)<br>[`03-content-and-blogs.md`](./05-niches-and-usecases/03-content-and-blogs.md)<br>[`04-coming-soon.md`](./05-niches-and-usecases/04-coming-soon.md) |
| `06-security-and-i18n/` | Nonces, sanitization, Content Security Policy (CSP), POT/PO generation, English and Hindi (`hi_IN`) translation. | [`01-security-and-data.md`](./06-security-and-i18n/01-security-and-data.md)<br>[`02-internationalization.md`](./06-security-and-i18n/02-internationalization.md) |

---

## 3. Core Technical Pillars & Architecture

### A. Full-Site Editing (FSE) & `theme.json` Engine
* **Schema Standards:** Configured via `theme.json` Version 3.
* **Zero CSS Bloat:** Layouts, fluid typography, spacing scales, and core color tokens are strictly declared in `theme.json`, letting WordPress output optimized CSS custom properties (`--wp--preset--*`).
* **Appearance Tools & Grid:** Enabled (`"appearanceTools": true`) to expose advanced typography, padding, border controls, native grid layouts (`layout.type: "grid"`), and negative margins directly in Gutenberg.

### B. Frontend Interactivity & Modern CSS Strategy
* **WordPress Interactivity API:** Interactive UI components use declarative HTML directives backed by Preact Signals. Used for dynamic ARIA states, instant "Copy to Clipboard" functionality for quotes, and infinite scrolling on blog archives.
* **Modern CSS Architecture:** Bypasses heavy frameworks in favor of Container Queries, Cascade Layers (`@layer`), and the `:has()` pseudo-class for complex layout state management.

### C. Generative Engine Optimization (GEO) & AI Discoverability
* **Automated `llms.txt`:** Serves a plain-text markdown catalog mapping site hierarchy to AI crawlers.
* **Structured Data Graph & Answer-First:** Injects rich JSON-LD graph entities (`Organization`, `WebSite`, `FAQPage`, `Quotation`, `BlogPosting`) and utilizes "Answer-First" block patterns placing concise factual summaries at the top.

### D. Advanced WordPress Core Feature Integrations
* **Block Bindings API:** Connects post metadata and custom fields directly to core blocks (`core/paragraph`, `core/heading`, `core/image`) eliminating custom dynamic block overhead.
* **Speculation Rules API:** Injects dynamic prefetch/prerender rules into the document `<head>`, enabling near-instant page transitions when hovering over quote categories or blog links.

---

## 4. Multi-Purpose Implementation Specifications

```text
                                         ┌──────────────────────────┐
                                         │   Nexus Parent Theme     │
                                         │ (FSE Engine & Core API)  │
                                         └─────────────┬────────────┘
     ┌──────────────────────┬──────────────────────────┼──────────────────────────┬──────────────────────┐
     ▼                      ▼                          ▼                          ▼                      ▼
┌──────────────┐   ┌────────────────┐   ┌──────────────────────────┐   ┌──────────────────┐   ┌──────────────┐
│  Corporate   │   │   E-Commerce   │   │ Content & Blogs (Quotes) │   │ NGO / Non-Profit │   │ Coming Soon  │
│• Answer-First│   │• Product Grid  │   │• Masonry Grid Layouts    │   │• Cause Showcase  │   │• Lead Capture│
│• Stats Grid  │   │• Cart Block    │   │• "Copy Quote" API Block  │   │• Fast Donation   │   │• 503 Mode    │
│• Pricing     │   │• Checkout Block│   │• Infinite Scroll         │   │• Impact Numbers  │   │• Blank Canvas│
└──────────────┘   └────────────────┘   └──────────────────────────┘   └──────────────────┘   └──────────────┘