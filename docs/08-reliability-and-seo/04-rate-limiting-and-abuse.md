# 04. Rate Limiting & Abuse Prevention

**File:** `docs/08-reliability-and-seo/04-rate-limiting-and-abuse.md`
**Module:** Reliability & SEO

## 1. Scope
Form spam protection is covered in `02-frontend-and-styles/05-forms-and-validation.md` §5. This doc covers the broader abuse surface: endpoint rate limiting, brute-force, scraping, and denial-of-service resilience. Much of DoS defense is an infrastructure concern — the theme's job is to not be the weak link and to provide hooks the operator can use.

## 2. Theme vs. Host Responsibility
* **Host / WAF / CDN (primary):** volumetric DoS, IP-level rate limiting, bot filtering. The theme cannot and should not try to replace these.
* **Theme (its share):** cap and guard the endpoints *it* introduces (REST routes, Interactivity actions, GEO endpoints), avoid expensive uncached work on public routes, and expose filters so operators can tune limits.

## 3. Endpoint Rate Limiting
* Every theme-authored write/compute endpoint (form submit, infinite-scroll loader, donation, llms.txt builder) should enforce a per-IP / per-user rate limit using a transient- or object-cache-backed counter, returning `429 Too Many Requests` with `Retry-After` when exceeded.
* Make limits filterable (e.g. `nexus_rate_limit_contact_form`) so operators adjust without editing theme code.
* Rate-limit by authenticated user ID when available, falling back to IP (respecting proxy headers only when the host is trusted).

## 4. Brute-Force & Credential Abuse
* The theme does not own login, but any custom auth-adjacent action (e.g. a members area) must throttle attempts and never reveal whether a username/email exists (uniform responses + timing).
* Recommend (in operator docs) limiting `wp-login.php` / XML-RPC at the host level; link this from the end-user guide (`09-monitoring-and-usage/02-end-user-guide.md`).

## 5. Scraping & GEO Endpoint Abuse
* `llms.txt`, `llms-full.txt`, and `?format=md` are intentionally machine-readable, so they invite heavy crawling. Serve them from cache (`07-performance-and-qa/04-caching-and-scaling.md`) so crawl volume hits the cache, not PHP.
* Expose only already-public content through these endpoints (reinforces `06-security-and-i18n/03-data-protection.md` §8) so scraping leaks nothing sensitive.

## 6. Resource-Exhaustion Hygiene
* No unbounded loops/queries on public routes; always paginate.
* Cap request-triggered work (max items per infinite-scroll page, max size of generated markdown).
* Timeouts on any outbound network call so a slow third party can't pile up PHP workers.

## 7. Verification Checklist
- [ ] Theme-authored write/compute endpoints rate-limited with 429 + Retry-After.
- [ ] Limits exposed via filters for operator tuning.
- [ ] Custom auth-adjacent actions throttled; no user-existence leak.
- [ ] GEO endpoints cached and expose only public content.
- [ ] No unbounded work on public routes; outbound calls have timeouts.
