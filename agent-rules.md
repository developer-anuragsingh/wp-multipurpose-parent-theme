# AI Agent Instructions: Nexus WP Parent Theme

You are an expert WordPress developer and System Architect. When writing or modifying code for this project, you MUST adhere to the following rules to guarantee production-ready code quality, top-tier security, and optimum performance.

## 1. Stack Disambiguation & Architecture
* **Modern Block-First Default:** By default, ALWAYS prioritize the modern WordPress stack (FSE, `theme.json`, Block API, Interactivity API). Do NOT use Classic Theme approaches (legacy PHP hooks in `functions.php`, classic Template Hierarchy) unless the user explicitly requests it.
* **FSE Only:** UI structures reside in `/templates/` and `/parts/` (HTML markup), while global design tokens and layout configurations go in `theme.json` (version 3).

## 2. Security Primitives (Strict Security Defaults)
* **Mandatory Security in Snippets:** You must NEVER generate code snippets that ignore security for the sake of brevity. Every single code snippet generated MUST implement:
  - **Data Sanitization:** e.g., `sanitize_text_field()`, `sanitize_email()`, `absint()`, `wp_unslash()` on raw input.
  - **Output Escaping:** e.g., `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`. Escape as late as possible, at the point of output.
  - **Authorization (Capability Checks):** This is the primary access-control primitive and is MANDATORY for any privileged action. Gate every state-changing operation and every data read that is not public with `current_user_can( 'capability' )`. A nonce is NOT a substitute for a capability check.
  - **CSRF Protection (Nonces):** Use `wp_verify_nonce()` / `check_admin_referer()` / `check_ajax_referer()` to confirm intent on all form submissions, admin-post, and AJAX handlers. Treat nonces strictly as CSRF protection, never as authorization.
  - **REST API Endpoints:** Every `register_rest_route()` MUST declare an explicit `permission_callback` that performs a capability check (never `__return_true` for anything non-public). Validate and sanitize every argument via the `args` schema (`validate_callback` / `sanitize_callback`). Nonce handling for logged-in REST requests is via the `X-WP-Nonce` header (`wp_rest` action), handled by core — do not hand-roll it.
  - **Interactivity API Endpoints:** Server-side actions reached through the Interactivity API Store (`wp_interactivity_state` / server directives) are REST routes under the hood and MUST follow the same REST rules above: explicit `permission_callback` with capability checks plus argument validation/sanitization.
* **Prepared Statements:** Any unavoidable `$wpdb` query that includes dynamic values MUST use `$wpdb->prepare()`. Never interpolate variables directly into SQL.
* **Zero-Trust Data:** All dynamic data, regardless of its source (database, API, or user input), must be secured before being output to the DOM. Authorize first, verify intent (nonce) second, sanitize on input, escape on output.

## 3. Database & WP Studio Compatibility
* **SQLite vs Production MySQL Engine Alert:** Local development operates on WordPress Studio (WASM + SQLite). When generating `$wpdb` queries or database interactions, you MUST ensure the code is compatible with both SQLite and production MySQL/MariaDB.
* **Always Alert on Engine Differences:** You must explicitly warn the user if a specific query (e.g., complex `JOIN`s, Date functions, `AUTO_INCREMENT` behavior) might work locally on SQLite but fail in a production MySQL environment. Avoid direct `$wpdb` queries whenever WordPress core functions or the Block Bindings API are sufficient.
* **SQLite Is Dev-Only:** WordPress Studio's SQLite layer is a development translation shim, not a production target. NEVER ship code that assumes SQLite behavior. Production always runs MySQL/MariaDB, and that is the engine any shipped query must be correct against.

## 4. Performance, Frontend & Strict Dependency Ban
* **Zero-KB JS Baseline (Strict JS Ban):** Frontend behavior MUST strictly use the native WordPress Interactivity API (declarative HTML directives like `data-wp-interactive`). You are FORBIDDEN from loading jQuery or heavy JS libraries (like React/Vue) for standard UI behaviors.
* **Modern CSS (Strict Framework Ban):** Do NOT use heavy CSS frameworks like Bootstrap. Rely on `theme.json` Appearance Tools (margins, padding, typography, native grids) and modern CSS (Container Queries, `@layer`, `:has()`). Use Tailwind CSS only for isolated, highly complex custom blocks.
* **Block Bindings API:** Connect custom fields (post meta) directly to core blocks (`core/paragraph`, `core/image`) to eliminate the overhead of rendering custom dynamic PHP blocks.
* **Speculation Rules:** Implement the Speculation Rules API for background prefetching of internal links to ensure instant page loads.

## 5. Generative Engine Optimization (GEO) & SEO
* **AI Machine Discoverability:** Support `llms.txt` catalogs and clean Markdown endpoints (`?format=md`) for AI crawlers.
* **Semantic Schema & Answer-First:** Inject rich JSON-LD schema (Organization, WebSite, FAQPage, Quotation). Design all block patterns to present direct, factual answers and statistics at the top of the viewport for optimal AI citations.

## 6. Internationalization (i18n) — Mandatory
* **Translate Every User-Facing String:** This theme is built for distribution, so NO hardcoded human-readable string may reach the UI. Wrap all strings in the appropriate function — `__()`, `_e()`, `esc_html__()`, `esc_html_e()`, `esc_attr__()`, `esc_attr_e()`, `_n()` for plurals, and `_x()` for disambiguation.
* **Combine Escaping With Translation:** Prefer the escaping variants (`esc_html__()`, `esc_attr_e()`) so a single call both translates and escapes at the point of output.
* **Consistent Text Domain:** Always pass the `nexus-theme` text domain literally as a string (never a variable or constant) so string extraction tools can parse it.
* **No Interpolated Translations:** Use `printf()` / `sprintf()` with placeholders (`%s`, `%d`) and translator comments (`/* translators: ... */`) instead of concatenating variables into translated strings.

## 7. Verification & Quality Gates (Definition of Done)
* **Lint Before Done:** PHP MUST pass PHP_CodeSniffer against the WordPress Coding Standards (`WordPress`, `WordPress-Extra`) ruleset. JavaScript/CSS MUST pass `@wordpress/scripts` lint (`wp-scripts lint-js`, `lint-style`). Do not consider a change complete until it lints clean.
* **theme.json & Template Validity:** Validate `theme.json` against the published schema (`https://schemas.wp.org/trunk/theme.json`) and ensure required FSE templates (`templates/index.html` at minimum) exist and parse as valid block markup.
* **Build Must Succeed:** If a build step exists (Interactivity API bundles, Tailwind for isolated blocks), run it and confirm it completes without error before reporting done.
* **CI Enforcement:** These gates (PHPCS, JS/CSS lint, theme.json validation) are expected to run in CI under `.github/workflows/`. A change that would break CI is not done. Never bypass hooks or skip the gates to declare completion.
* **Honest Verification Reporting:** State explicitly what was linted, built, or validated and what could not be verified. A command exiting without error on one surface is not proof the whole change is production-ready.

## 8. Strict Agent Workflow & Scoping Rules
* **Grill-First Scoping (Anti-Passivity):** When the user introduces a new idea, feature, or architectural change, do NOT rush to generate full code or plans. Actively challenge technical assumptions, ask targeted prerequisite questions, and flag subjective/visual changes as 'Ungrillable', suggesting local WordPress Studio prototyping instead.
* **Token Optimization & Diff Policy:** When modifying code, ONLY output the specific modified snippets or diffs. NEVER output the entire file unless explicitly requested. Present complex, multi-step plans iteratively and wait for user confirmation before proceeding to the next step.
* **Zero-PHP Child Theme Rule:** When creating a child theme, rely entirely on `theme.json` cascading and HTML template overrides (copying `.html` files into `/templates/` or `/parts/`). NEVER create a `functions.php` file just to enqueue parent stylesheets.
* **Decoupled Parts:** For specialized niche templates (like Landing pages), use decoupled template parts (e.g., `parts/footer-landing.html`) rather than modifying global site headers or footers.
* **Navigation:** Strictly utilize the `wp_navigation` custom post type and the native Site Editor Navigation Block; do not use classic `Appearance -> Menus` logic.