# 01. Security & Data Privacy Standards

**File:** `docs/06-security-and-i18n/01-security-and-data.md`  
**Module:** Security & i18n  

## 1. Zero-Trust Data Architecture
Enterprise and NGO websites handle sensitive user data. The theme enforces a strict "Zero-Trust" policy for any dynamic data rendering to guarantee user personal data security.

The order of operations for any dynamic data flow is fixed:
1. **Authorize** — confirm the current user is allowed to perform the action (`current_user_can()`).
2. **Verify intent** — confirm the request is deliberate, not forged (nonce / CSRF check).
3. **Sanitize on input** — clean raw data as it enters the system.
4. **Escape on output** — encode data at the exact point it is written to the DOM.

A failure at any one of these stages is a vulnerability, even when the other three are correct. In particular, a nonce is NOT authorization and escaping is NOT input validation.

## 2. Authorization (Capability Checks) — Primary Access Control
Capability checks are the primary access-control primitive and are **mandatory** for any privileged action. A valid nonce only proves a request was intentional; it says nothing about whether the user is *allowed* to make it.
* Gate every state-changing operation and every non-public data read with `current_user_can( 'capability' )` (e.g. `edit_posts`, `manage_options`, `manage_woocommerce`).
* Never rely on a nonce, a hidden field, or UI visibility as a substitute for a capability check — those are trivially bypassed.
* Check the most specific capability available for the resource (e.g. `edit_post` with a post ID), not a broad role check.

## 3. Input Sanitization & Prepared Statements
All incoming data is untrusted until proven otherwise. Sanitize at the boundary, as data enters the system.
* **Unslash first:** WordPress adds slashes to superglobals — call `wp_unslash()` before sanitizing values from `$_POST`, `$_GET`, `$_REQUEST`, or `$_SERVER`.
* **Sanitize by type:** `sanitize_text_field()`, `sanitize_email()`, `sanitize_textarea_field()`, `absint()` / `intval()` for integers, `sanitize_key()` for keys/slugs, `esc_url_raw()` for stored URLs.
* **Validate, don't just clean:** reject values outside an expected set (allow-lists for enums, bounds checks for numbers) rather than silently coercing them.
* **Prepared statements:** any unavoidable `$wpdb` query containing dynamic values MUST use `$wpdb->prepare()`. Never interpolate variables directly into SQL. Prefer core APIs (`WP_Query`, meta/option functions) and the Block Bindings API over raw queries wherever they suffice.

## 4. Output Escaping
All dynamic data, regardless of its source (database, API, or user input), must be escaped at the point of output to the DOM. Escape as late as possible.
* **HTML & Text:** `esc_html()` for standard text, `wp_kses_post()` for rich text blocks.
* **Attributes:** `esc_attr()` for IDs, classes, and ARIA labels.
* **URLs:** `esc_url()` for all links, image sources, and API endpoints.
* **Translations:** prefer the combined escaping+translation variants (`esc_html__()`, `esc_attr_e()`) so a single call both translates and escapes.

## 5. CSRF Protection (Nonces) — Intent, Not Authorization
Nonces confirm a request was deliberately initiated by the user; they are strictly CSRF protection and must never stand in for the capability check in §2.
* **Forms / admin-post:** `wp_nonce_field()` on output, `check_admin_referer()` on receipt.
* **AJAX (`admin-ajax.php`):** `check_ajax_referer()` on receipt.
* **Manual verification:** `wp_create_nonce()` / `wp_verify_nonce()` when the helpers above do not fit.

## 6. REST API Endpoints
Every state mutation or non-public read in the theme that is reachable over HTTP should be a properly guarded REST route.
* Every `register_rest_route()` MUST declare an explicit `permission_callback` that performs a capability check. Never use `__return_true` for anything that is not genuinely public.
* Validate and sanitize every argument through the `args` schema (`validate_callback` and `sanitize_callback`), so bad input is rejected before the handler runs.
* For logged-in requests, nonce handling uses the `X-WP-Nonce` header (the `wp_rest` action) and is handled by core — do not hand-roll it.

## 7. Interactivity API Security
Because the theme uses the WordPress Interactivity API for dynamic frontend features (Click-to-Copy, infinite scroll, donation forms, add-to-cart), its server-side actions are REST routes under the hood and MUST follow the same rules as §6:
* An explicit `permission_callback` with a capability check gates every server action that mutates data or returns non-public data.
* Every argument is validated and sanitized via the route's `args` schema.
* CSRF intent is confirmed via the standard `wp_rest` nonce (`X-WP-Nonce`) that the Interactivity API attaches to store requests — in addition to, never instead of, the capability check.

## 8. Content Security Policy (CSP)
The theme includes hooks to enforce strict CSP headers, preventing unauthorized third-party scripts from loading — critical for the E-Commerce (WooCommerce) checkout flows.
* **No blanket `unsafe-inline`:** a strict policy forbids inline scripts, which collides with the inline dark-mode head script (see `02-frontend-and-styles/03-dark-light-mode.md`). Reconcile this by emitting a per-request CSP nonce and attaching it to that single inline script (`<script nonce="...">` + `script-src 'nonce-...'`), or by hashing the script and allowing it via `'sha256-...'`. Never relax the whole policy to `unsafe-inline` to make one script work.
* **Report before enforce:** ship `Content-Security-Policy-Report-Only` first to catch violations, then switch to enforcing.

## 9. Privacy & Data Retention (GDPR)
NGO and commerce niches collect personal data, so privacy is a first-class security concern, not an afterthought.
* **Consent before collection:** gate non-essential cookies and third-party embeds behind explicit consent.
* **Local-first assets:** self-host fonts via the Font Library API (no third-party `<link>` beacons) — see `01-architecture/01-fse-overview.md` §6.
* **Core privacy hooks:** integrate with WordPress personal-data export/erasure hooks for any custom data the theme stores, and document retention periods for form submissions.