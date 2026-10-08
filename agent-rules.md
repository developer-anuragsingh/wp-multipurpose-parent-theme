# AI Agent Instructions: Nexus WP Parent Theme

You are an expert WordPress developer. When writing or modifying code for this project, you MUST adhere to the following rules to guarantee production-ready code quality, top-tier security, and optimum performance[cite: 1].

## 1. Architecture & Code Standards
* **FSE Only:** Strictly use Full-Site Editing (FSE) patterns. UI structures reside in `/templates/` and `/parts/` (HTML markup), while global design tokens and layout configurations go in `theme.json` (version 3)[cite: 3].
* **No Legacy PHP:** Do NOT create legacy WordPress PHP templates (e.g., `header.php`, `footer.php`, `archive.php`). Do NOT use `add_theme_support()` for features natively handled by `theme.json`[cite: 3].
* **Decoupled Parts:** For specialized niche templates (like Landing pages), use decoupled template parts (e.g., `parts/footer-landing.html`) rather than modifying global site headers or footers[cite: 3].
* **Modern CSS:** Avoid heavy frameworks like Bootstrap. Rely on `theme.json` Appearance Tools (for margins, padding, typography, and native grid layouts) and modern CSS (Container Queries, `@layer`, `:has()`). Use Tailwind CSS only for isolated, highly complex custom blocks[cite: 1, 3].

## 2. Security & Privacy (Strict Enforcement)
* **Sanitization & Escaping:** Every piece of dynamic data MUST be escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`) before rendering to prevent XSS attacks and protect user data[cite: 1].
* **Nonce Verification:** All AJAX, REST APIs, and Interactivity API endpoints MUST validate requests using `wp_verify_nonce`[cite: 1].
* **Direct Database Queries:** Avoid direct `$wpdb` queries unless absolutely necessary; use WordPress core functions and the Block Bindings API to access data[cite: 1].

## 3. Performance & Frontend
* **Zero-KB JS Baseline:** Use the native WordPress Interactivity API (declarative HTML directives like `data-wp-interactive`) for frontend behavior. NEVER load jQuery or heavy JS libraries[cite: 1].
* **Speculation Rules:** Implement the Speculation Rules API for background prefetching of internal links to ensure instant page loads[cite: 1].
* **Block Bindings API:** Connect custom fields (post meta) directly to core blocks (`core/paragraph`, `core/image`) to eliminate the overhead of rendering custom dynamic PHP blocks[cite: 1].
* **Media Optimization:** Enforce AVIF/WebP formats and native lazy-loading for all media and iframes[cite: 1].

## 4. Generative Engine Optimization (GEO) & SEO
* **AI Machine Discoverability:** Generate `llms.txt` and `llms-full.txt` catalogs at the root, and support clean Markdown endpoints (`?format=md`) for AI crawlers[cite: 1].
* **Semantic Schema:** Inject rich JSON-LD schema (Organization, WebSite, FAQPage, Article) so AI engines can confidently map the brand into their knowledge graphs[cite: 1].
* **Answer-First Patterns:** Design all block patterns to present direct, factual answers and statistics at the top of the viewport for optimal AI citations[cite: 1].

## 5. Agent Workflow Rules
* When creating a child theme for a specific niche (e.g., E-Commerce), rely entirely on `theme.json` cascading and HTML template overrides. Do NOT use `functions.php` to enqueue parent stylesheets[cite: 3].
* When creating site navigation, strictly utilize the `wp_navigation` custom post type and the native Site Editor Navigation Block; do not use classic `Appearance -> Menus` logic[cite: 3].