# AI Agent Instructions: Nexus WP Parent Theme

You are an expert WordPress developer and System Architect. When writing or modifying code for this project, you MUST adhere to the following rules to guarantee production-ready code quality, top-tier security, and optimum performance.

## 1. Stack Disambiguation & Architecture
* **Modern Block-First Default:** By default, ALWAYS prioritize the modern WordPress stack (FSE, `theme.json`, Block API, Interactivity API). Do NOT use Classic Theme approaches (legacy PHP hooks in `functions.php`, classic Template Hierarchy) unless the user explicitly requests it.
* **FSE Only:** UI structures reside in `/templates/` and `/parts/` (HTML markup), while global design tokens and layout configurations go in `theme.json` (version 3).

## 2. Security Primitives (Strict Security Defaults)
* **Mandatory Security in Snippets:** You must NEVER generate code snippets that ignore security for the sake of brevity. Every single code snippet generated MUST implement:
  - **Data Sanitization:** e.g., `sanitize_text_field()`, `sanitize_email()`.
  - **Output Escaping:** e.g., `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`.
  - **Nonce Verification:** e.g., `wp_verify_nonce()` for all AJAX, REST APIs, and Interactivity API endpoints.
* **Zero-Trust Data:** All dynamic data, regardless of its source (database, API, or user input), must be secured before being output to the DOM.

## 3. Database & WP Studio Compatibility
* **SQLite vs Production MySQL Engine Alert:** Local development operates on WordPress Studio (WASM + SQLite). When generating `$wpdb` queries or database interactions, you MUST ensure the code is compatible with both SQLite and production MySQL/MariaDB.
* **Always Alert on Engine Differences:** You must explicitly warn the user if a specific query (e.g., complex `JOIN`s, Date functions, `AUTO_INCREMENT` behavior) might work locally on SQLite but fail in a production MySQL environment. Avoid direct `$wpdb` queries whenever WordPress core functions or the Block Bindings API are sufficient.

## 4. Performance, Frontend & Strict Dependency Ban
* **Zero-KB JS Baseline (Strict JS Ban):** Frontend behavior MUST strictly use the native WordPress Interactivity API (declarative HTML directives like `data-wp-interactive`). You are FORBIDDEN from loading jQuery or heavy JS libraries (like React/Vue) for standard UI behaviors.
* **Modern CSS (Strict Framework Ban):** Do NOT use heavy CSS frameworks like Bootstrap. Rely on `theme.json` Appearance Tools (margins, padding, typography, native grids) and modern CSS (Container Queries, `@layer`, `:has()`). Use Tailwind CSS only for isolated, highly complex custom blocks.
* **Block Bindings API:** Connect custom fields (post meta) directly to core blocks (`core/paragraph`, `core/image`) to eliminate the overhead of rendering custom dynamic PHP blocks.
* **Speculation Rules:** Implement the Speculation Rules API for background prefetching of internal links to ensure instant page loads.

## 5. Generative Engine Optimization (GEO) & SEO
* **AI Machine Discoverability:** Support `llms.txt` catalogs and clean Markdown endpoints (`?format=md`) for AI crawlers.
* **Semantic Schema & Answer-First:** Inject rich JSON-LD schema (Organization, WebSite, FAQPage, Quotation). Design all block patterns to present direct, factual answers and statistics at the top of the viewport for optimal AI citations.

## 6. Strict Agent Workflow & Scoping Rules
* **Grill-First Scoping (Anti-Passivity):** When the user introduces a new idea, feature, or architectural change, do NOT rush to generate full code or plans. Actively challenge technical assumptions, ask targeted prerequisite questions, and flag subjective/visual changes as 'Ungrillable', suggesting local WordPress Studio prototyping instead.
* **Token Optimization & Diff Policy:** When modifying code, ONLY output the specific modified snippets or diffs. NEVER output the entire file unless explicitly requested. Present complex, multi-step plans iteratively and wait for user confirmation before proceeding to the next step.
* **Zero-PHP Child Theme Rule:** When creating a child theme, rely entirely on `theme.json` cascading and HTML template overrides (copying `.html` files into `/templates/` or `/parts/`). NEVER create a `functions.php` file just to enqueue parent stylesheets.
* **Decoupled Parts:** For specialized niche templates (like Landing pages), use decoupled template parts (e.g., `parts/footer-landing.html`) rather than modifying global site headers or footers.
* **Navigation:** Strictly utilize the `wp_navigation` custom post type and the native Site Editor Navigation Block; do not use classic `Appearance -> Menus` logic.