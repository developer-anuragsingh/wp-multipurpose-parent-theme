# 03. Browser Support & Progressive Enhancement

**File:** `docs/08-reliability-and-seo/03-browser-support.md`
**Module:** Reliability & SEO

## 1. Why This Needs a Policy
The theme leans on modern platform features — `:has()`, Container Queries, Cascade Layers, the Interactivity API, and Speculation Rules. These are well supported in current browsers but have real fallbacks to define, so a page never *breaks* on an older or non-supporting browser.

## 2. Support Matrix
* **Tier 1 (full fidelity):** latest 2 versions of Chrome, Edge, Firefox, Safari, plus iOS Safari and Chrome Android (current). Everything works as designed.
* **Tier 2 (graceful degradation):** one version older, and browsers lacking a specific modern feature. Core content and navigation remain fully usable; enhancements may be absent.
* **Out of scope:** Internet Explorer and discontinued engines. No effort spent; content must still be readable (plain HTML degrades naturally).

## 3. Progressive Enhancement — The Rule
Content and core navigation MUST work with **HTML + CSS alone**. JavaScript (the Interactivity API) only *enhances*. This is also the reliability fallback from `08-reliability-and-seo/01-error-handling.md` §3.
* Nav drawer: a reachable list/links without JS; JS adds the off-canvas toggle.
* Dark mode: site is fully usable in its default theme without the toggle script.
* Infinite scroll: real paginated links exist and work without JS (also required for SEO, `02-traditional-seo.md`).
* Forms: submit and server-validate without JS; JS adds inline feedback (`02-frontend-and-styles/05-forms-and-validation.md`).

## 4. Feature-Specific Fallbacks
* **`:has()` / Container Queries:** use as progressive enhancement; the base layout (via `theme.json` and standard CSS) is already usable without them. Prefer `@supports` guards where a missing feature would otherwise break layout.
* **Cascade Layers (`@layer`):** supported broadly; keep critical base styles outside layers so an unsupporting engine still applies them.
* **Interactivity API:** requires JS by definition — hence the non-JS fallback requirement above.
* **Speculation Rules:** Chromium-only (Chrome/Edge/Opera 121+). Safari and Firefox silently ignore it ([WordPress plugin notes](https://wordpress.org/plugins/speculation-rules/), [MDN](https://developer.mozilla.org/en-US/docs/Web/API/Speculation_Rules_API)) — those users navigate normally with no breakage, but get no prefetch speed-up. Set expectations accordingly: "near-instant" applies to Chromium users, not Safari/iOS. Progressive by nature, so no fallback code needed.
* **AVIF/WebP:** serve via `<picture>`/`srcset` with a widely-supported fallback (WebP→JPEG/PNG) so no image fails to load.

## 5. Verification
* Define the matrix in `browserslist` (in `package.json`) so build tooling (autoprefixer, `@wordpress/scripts`) targets it consistently.
* Playwright e2e (`07-performance-and-qa/03-testing-strategy.md`) runs across Chromium, Firefox, and WebKit to cover the Tier-1 engines.

## 6. Verification Checklist
- [ ] Core content + navigation usable with JS disabled.
- [ ] `@supports` guards where a missing modern feature would break layout.
- [ ] Images have a widely-supported fallback format.
- [ ] `browserslist` defined and matches this matrix.
- [ ] e2e runs on Chromium + Firefox + WebKit.
