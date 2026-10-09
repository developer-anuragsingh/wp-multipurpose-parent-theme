# 01. Error Handling & Graceful Fallbacks

**File:** `docs/08-reliability-and-seo/01-error-handling.md`
**Module:** Reliability & SEO

## 1. Principle: Fail Closed, Fail Quietly, Never Leak
When something goes wrong, the theme must degrade gracefully: the user sees a sensible fallback, search engines get correct status codes, and no technical detail or PII leaks to the response. This complements the logging hygiene rules in `06-security-and-i18n/03-data-protection.md` §6.

## 2. HTTP Status Correctness
* **404:** unknown routes render `templates/404.html` and return a real `404` status (never a soft-200 "not found" page).
* **500:** on a fatal theme error, let WordPress serve its recovery path; never render a half-broken page with a `200`.
* **503:** Coming Soon / maintenance mode returns `503 Service Unavailable` with a `Retry-After` header (ties to `05-niches-and-usecases/05-coming-soon.md`), and suppresses Speculation Rules so browsers don't prefetch 503s.
* **GEO endpoints:** `?format=md` and `llms.txt` return correct `Content-Type` and, on failure to generate, a clean error status — not a broken partial document.

## 3. Dynamic Feature Fallbacks
Every dynamic feature must define what happens when its data is missing or the mechanism is unavailable:
* **Block Bindings:** every bound block declares a sensible default/fallback value in markup, shown when the meta key is empty or missing (never render an empty or raw `null`).
* **Interactivity API:** interactive components degrade to a usable non-JS state (progressive enhancement — see `08-reliability-and-seo/03-browser-support.md`). A nav drawer, for example, remains a reachable list without JS.
* **llms.txt / Markdown endpoints:** if content parsing fails, log server-side (no PII) and return a minimal valid document or a clean error, not a stack trace.
* **Third-party / network calls:** wrap in timeouts and try/catch; on failure show cached or placeholder content, never a hard crash.

## 4. User-Facing Error Messaging
* Generic, friendly, translatable (`esc_html__( ..., 'nexus-theme' )`) messages — never raw exception text, SQL, or file paths.
* Forms surface validation errors inline per the a11y contract (`02-frontend-and-styles/05-forms-and-validation.md`), and a submission failure shows a retry-able message, not a blank page.

## 5. Developer-Facing Diagnostics
* `WP_DEBUG` / `WP_DEBUG_DISPLAY` off in production; `WP_DEBUG_LOG` may be on to a protected file.
* Log actionable, non-sensitive context (route, error code, timing) — never request bodies or PII.
* Prefer `WP_Error` for recoverable failures so callers can branch, rather than throwing to a white screen.

## 6. Verification Checklist
- [ ] 404 renders the template AND returns a 404 status.
- [ ] Fatal errors don't render a 200; recovery path used.
- [ ] 503 maintenance mode sets `Retry-After` and disables speculation.
- [ ] Every Block Binding has a visible fallback value.
- [ ] Interactive components are usable without JS.
- [ ] No stack traces, SQL, or PII ever reach the response.
