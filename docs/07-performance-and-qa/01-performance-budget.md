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
* **Lab:** Lighthouse (mobile preset), Performance ≥ 90 (currently 100), run with `scripts/lighthouse.sh [base-url] [path ...]` against a running site (it needs real PHP, so it is a pre-release gate rather than a CI job). It pins Lighthouse 13.6.0 and fails on: performance < 90, accessibility < 100, best-practices < 95, SEO < 95, TBT > 200 ms, LCP > 2500 ms, CLS > 0.1 (overridable via `MIN_*` / `MAX_*` env vars).
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
| Performance | 74 | **100** |
| Accessibility | 100 | **100** |
| Best Practices | 96 | **100** |
| SEO | 92 | **100** |
| Total Blocking Time | 950 ms | **~0-20 ms** |
| LCP | 1.4 s | 1.4 s |
| CLS | 0.12 | **0.000** |

What moved the numbers (each verified on the live site):
* **Emoji loader removed** (`Atlas\Setup::disable_emoji_scripts`, restore with `atlas_keep_emoji_scripts`). Core's inline detection script plus a `blob:` Worker was the "unattributable" ~1.2 s long task behind the 950 ms TBT, and it was also the only Content-Security-Policy issue Chrome logged. Every browser in the support matrix renders emoji natively.
* **`<meta name="description">`** added (`Atlas\Seo`); it prints only when the page has a description (singular excerpt or site tagline), so set a tagline.
* **Heading font preloaded** (see §4).

* **Mobile layout shift fixed (CLS 0.12 -> 0.000).** Cause, measured with a `layout-shift` observer on a 412 px viewport: the server cannot know the viewport, so before hydration the primary nav rendered inline and wrapped to a second row (header 86 px); once `navigation.js` hydrated it switched to the off-canvas drawer and the header shrank to 58 px, moving `main` up 28 px. Fix: the closed drawer layout (collapsed `visibility:hidden` panel, visible toggle link) is now pure CSS from the first paint, so hydration changes no geometry, and the menu works without JS via `:target` (see `02-frontend-and-styles/01-interactivity-api.md`). A second, smaller shift came from the JS-only theme toggle being revealed (header action row 26 px -> 37 px); `.atlas-theme-toggle[hidden]` now reserves its box with `visibility:hidden`.
* **Guards:** `tests/e2e/navigation-layout.spec.js` asserts (mobile viewport) the header height is identical with `navigation.js` blocked vs hydrated, no layout shift of the header or `<main>` during hydration, the hidden theme toggle keeps a non-zero box but is invisible, and the menu opens/closes with JavaScript disabled and with the module blocked. `tests/test-fixtures.php` fails if the E2E harness markup drifts from `theme/parts/header.html`.

**Known, separate item:** on pages with a Copyable quote, the copy button (`hidden` until the Clipboard API is confirmed usable) is revealed on hydration and grows its quote by ~30 px (CLS about 0.02 on that page; not on the home page). It is not fixed because reserving its space would leave a blank band wherever the Clipboard API is unavailable (insecure contexts), which contradicts that control's "never show a dead button" design. Decide deliberately before changing it.

