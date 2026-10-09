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
| **Tailwind (isolated blocks, if used)** | ≤ 10 KB (hard limit, tree-shaken) |
| **Fonts** | ≤ 2 `.woff2` files, ≤ 90 KB total, `font-display: swap` |
| **Hero image (LCP)** | ≤ 150 KB, AVIF/WebP |
| **Total page weight (typical content page)** | ≤ 500 KB |

## 4. Enforcement Principles
* **Zero-KB JS baseline:** pages with no interactive component ship no client JS. Interactivity API runtime loads only on pages that use it.
* **No render-blocking JS:** scripts are deferred/module-loaded; the only inline head script permitted is the flicker-free theme script (CSP-nonced/hashed — see security §8).
* **Images:** AVIF/WebP by default, explicit `width`/`height` to reserve space (protects CLS), native `loading="lazy"` for below-the-fold media, and the LCP image is NOT lazy-loaded (it should be `fetchpriority="high"`).
* **Fonts:** self-hosted via the Font Library API, preload the single critical font, `font-display: swap` to avoid invisible text.
* **Speculation Rules:** `prefetch` (not `prerender`) by default with `moderate` eagerness to balance instant loads against server/bandwidth cost; excluded routes per `04-modern-wp-features/02-speculation-rules.md`. Suppress speculation entirely in Coming Soon / 503 mode so browsers never prefetch error responses.

## 5. Measurement
* **Lab:** Lighthouse (mobile preset) in CI on representative templates — Performance ≥ 90.
* **Bundle size:** CSS/JS budgets checked in CI (fail the build on regression).
* **Field:** real-user Core Web Vitals via the Site Kit / `web-vitals` where analytics are present.

## 6. Verification Checklist (per template)
- [ ] Lighthouse mobile Performance ≥ 90.
- [ ] CWV lab values within §2 ceilings.
- [ ] JS/CSS/font/image weights within §3 budget.
- [ ] LCP image prioritized, not lazy-loaded; below-fold media lazy-loaded.
- [ ] No render-blocking inline script except the CSP-allow-listed theme script.
