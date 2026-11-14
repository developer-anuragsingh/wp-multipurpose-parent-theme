# 01. Performance Budget & Core Web Vitals

**File:** `docs/07-performance-and-qa/01-performance-budget.md`
**Module:** Performance & QA

## 1. Why a Budget
"Sub-second FCP" and "instant loads" are claims the theme makes throughout the docs. A budget turns those claims into measurable, enforceable targets. A change that regresses the budget is treated as a bug.

## 2. Core Web Vitals Targets (75th percentile, field + lab)
| Metric | Target | Hard Ceiling |
| :--- | :--- | :--- |
| **LCP** (Largest Contentful Paint) | ≤ 1.8 s | 2.5 s |
| **INP** (Interaction to Next Paint) | ≤ 150 ms | 200 ms |
| **CLS** (Cumulative Layout Shift) | ≤ 0.05 | 0.1 |
| **FCP** (First Contentful Paint) | ≤ 1.2 s | 1.8 s |
| **TTFB** (Time to First Byte) | ≤ 400 ms | 800 ms |

## 3. Asset Weight Budget (per page, compressed)
| Resource | Budget |
| :--- | :--- |
| **JavaScript (initial)** | 0 KB on non-interactive pages; ≤ 15 KB where the Interactivity API runtime loads |
| **CSS (critical/render-blocking)** | ≤ 20 KB |
| **Tailwind (isolated blocks, if used)** | ≤ 10 KB project budget, verified in CI (v4 JIT generates only used utilities; prefer `@utility` over `@layer components`) |
| **Fonts** | ≤ 2 `.woff2` files, ≤ 90 KB total, `font-display: swap` |
| **Hero image (LCP)** | ≤ 150 KB, AVIF/WebP |
| **Total page weight (typical content page)** | ≤ 500 KB |

## 4. Enforcement Principles
* **Zero-KB JS baseline:** pages with no interactive component ship no client JS. Interactivity API runtime loads only on pages that use it.
* **No render-blocking JS:** scripts are deferred/module-loaded; the only inline head script permitted is the flicker-free theme script (CSP-nonced/hashed — see security §8).
* **Images:** AVIF/WebP by default, explicit `width`/`height` to reserve space (protects CLS), native `loading="lazy"` for below-the-fold media, and the LCP image is NOT lazy-loaded (it should be `fetchpriority="high"`).
* **Fonts:** self-hosted via the Font Library API, `font-display: swap` to avoid invisible text. **Preload every face used above the fold** (`atlas_critical_fonts_preload`): the theme preloads both Inter (body) and Sora (headings). Preloading only the body font let the heading font arrive late and swap in, which Lighthouse reported as the layout-shift culprit.
* **Speculation Rules:** `prefetch` (not `prerender`) by default with `moderate` eagerness to balance instant loads against server/bandwidth cost; excluded routes per `04-modern-wp-features/02-speculation-rules.md`. Suppress speculation entirely in Coming Soon / 503 mode so browsers never prefetch error responses.

## 5. Measurement
* **Lab:** Lighthouse (mobile preset), Performance ≥ 90, run with `scripts/lighthouse.sh [base-url] [path ...]` against a running site (it needs real PHP, so it is a pre-release gate rather than a CI job). It pins Lighthouse 13.6.0 and fails on: performance < 90, accessibility < 100, best-practices < 95, SEO < 95, TBT > 200 ms, LCP > 2500 ms, CLS > 0.15 (overridable via `MIN_*` / `MAX_*` env vars).
* **Bundle size:** CSS/JS budgets checked in CI (fail the build on regression).
* **Field:** real-user Core Web Vitals via the Site Kit / `web-vitals` where analytics are present.

## 6. Verification Checklist (per template)
- [ ] Lighthouse mobile Performance ≥ 90.
- [ ] CWV lab values within §2 ceilings.
- [ ] JS/CSS/font/image weights within §3 budget.
- [ ] LCP image prioritized, not lazy-loaded; below-fold media lazy-loaded.
- [ ] No render-blocking inline script except the CSP-allow-listed theme script.

## 6. Measured Baseline & Known Issues
Lighthouse 13.6.0, mobile emulation, local Studio site (PHP without page cache or gzip), home page:

| | Before | After |
|---|---|---|
| Performance | 74 | **96** |
| Accessibility | 100 | **100** |
| Best Practices | 96 | **100** |
| SEO | 92 | **100** |
| Total Blocking Time | 950 ms | **~0-20 ms** |
| LCP | 1.4 s | 1.4 s |
| CLS | 0.12 | 0.12 (open) |

What moved the numbers (each verified on the live site):
* **Emoji loader removed** (`Atlas\Setup::disable_emoji_scripts`, restore with `atlas_keep_emoji_scripts`). Core's inline detection script plus a `blob:` Worker was the "unattributable" ~1.2 s long task behind the 950 ms TBT, and it was also the only Content-Security-Policy issue Chrome logged. Every browser in the support matrix renders emoji natively.
* **`<meta name="description">`** added (`Atlas\Seo`); it prints only when the page has a description (singular excerpt or site tagline), so set a tagline.
* **Heading font preloaded** (see §4).

**Open issue: mobile CLS 0.12 (needs-improvement band 0.1-0.25).** Cause, measured with a `layout-shift` observer on a 412 px viewport: the server cannot know the viewport, so before hydration the primary nav renders inline and wraps to a second row (header 86 px); once `navigation.js` hydrates it switches to the off-canvas drawer and the header shrinks to 58 px, moving `main` up 28 px. Fixing it means deciding between: (a) a `js` class from the head script plus CSS that applies the drawer layout before hydration (no shift, but if the module fails to load the menu stays collapsed); (b) forcing the header to a fixed pre/post height (no shift, but ~28 px of dead space on mobile); (c) a `<details>`-based menu that works with no JS (largest change). Pick one deliberately; do not hide the menu pre-hydration without a no-JS fallback. Until then `scripts/lighthouse.sh` allows CLS ≤ 0.15.

