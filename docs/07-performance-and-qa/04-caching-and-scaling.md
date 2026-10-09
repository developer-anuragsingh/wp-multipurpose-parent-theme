# 04. Caching & Scaling

**File:** `docs/07-performance-and-qa/04-caching-and-scaling.md`
**Module:** Performance & QA

## 1. Why This Matters
The theme claims "instant" loads and sub-second FCP. Those claims only hold under real traffic if the theme is cache-friendly and does not fight the host's caching layers. The rule: the theme must be **cache-correct by default** — never emit per-user content on a cacheable page without marking it.

## 2. Caching Layers (what the theme must respect)
* **Page cache / full-page cache** (host, Varnish, or a plugin): most front-end pages are anonymous and fully cacheable. The theme must NOT output user-specific data (names, cart totals, nonces) into markup that a page cache will serve to everyone.
* **Object cache** (Redis/Memcached): expensive lookups (Block Bindings meta reads, `llms.txt` directory building) should use the object cache via the Transients API or `wp_cache_*`.
* **Browser cache / CDN:** static assets (CSS, JS, fonts, images) are versioned (`NEXUS_THEME_VERSION`) so they can be cached long-term and busted on release.

## 3. Theme Responsibilities
* **Fragment caching:** cache the output of expensive, reusable fragments (e.g. the generated `llms.txt`, a computed JSON-LD graph) in a transient with a sensible TTL and a clear invalidation hook (on `save_post`, `switch_theme`, etc.).
* **Dynamic vs. cached:** anything genuinely per-user (mini-cart count, logged-in greeting) must load via a cache-safe mechanism — a client-side Interactivity API fetch to a non-cached REST endpoint — not baked into the cached page HTML.
* **Nonce caching trap:** nonces are user- and time-specific. Never print a nonce into page-cached HTML; fetch it client-side or exclude the fragment from cache. (This directly affects forms — see `02-frontend-and-styles/05-forms-and-validation.md`.)
* **Avoid cache-busting patterns:** no `nocache_headers()` on normal front-end templates, no cookies set on anonymous pages (they defeat page caches).

## 4. Speculation Rules at Scale
Prefetching multiplies requests. Under heavy traffic:
* Keep `eagerness: "moderate"` (not `eager`) to avoid prefetch storms.
* Ensure prefetched pages are cacheable (per §2) so prefetch hits the cache, not PHP.
* Exclude non-idempotent and per-user routes (cart, checkout, `wp-admin`) — already required in `04-modern-wp-features/02-speculation-rules.md`.

## 5. Database & Query Efficiency
* Prefer core APIs (`WP_Query`, meta/option functions) that are object-cache-aware over raw `$wpdb`.
* Avoid unbounded queries; always paginate (infinite scroll loads fixed page sizes).
* Watch for N+1 patterns in Block Bindings loops; batch meta reads where possible.
* Honour the SQLite/MySQL caveat (`01-architecture/...` + `agent-rules.md` §3): shipped queries are correct against MySQL/MariaDB.

## 6. CDN & Assets
* Self-hosted fonts and images served with long `Cache-Control` + immutable where versioned.
* Images in AVIF/WebP with explicit dimensions (ties to the perf budget and CLS, `01-performance-budget.md`).

## 7. Verification Checklist
- [ ] No per-user data (names, cart, nonces) baked into page-cacheable HTML.
- [ ] Expensive fragments (llms.txt, JSON-LD) cached in transients with invalidation hooks.
- [ ] Per-user bits load via a cache-safe REST/Interactivity fetch.
- [ ] Assets versioned for long-term browser/CDN caching.
- [ ] Speculation prefetch targets are cacheable; dynamic routes excluded.
- [ ] No unbounded queries; no N+1 in bindings loops.
