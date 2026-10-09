# 03. Automated Testing Strategy

**File:** `docs/07-performance-and-qa/03-testing-strategy.md`
**Module:** Performance & QA

> This doc covers **test authoring** (unit, integration, e2e, visual). It sits above `02-testing-and-ci.md`, which covers the lint/CI gates. Linting proves code *style*; tests prove code *behaviour*.

## 1. The Testing Pyramid
Favour many fast, cheap tests at the base and few slow, broad tests at the top:
* **Unit (most):** isolated PHP functions and JS store logic.
* **Integration (some):** PHP against a real WordPress test instance (hooks, REST routes, Block Bindings sources).
* **End-to-end (few):** full user flows in a real browser.
* **Visual regression (targeted):** key templates/patterns, to catch unintended layout shifts.

## 2. PHP — PHPUnit + WP test suite
* **Framework:** PHPUnit via the WordPress core test library (`WP_UnitTestCase`), or the Yoast/WP-Browser toolchain.
* **What to cover:**
  - Block Bindings source callbacks (correct value, safe fallback on missing meta).
  - REST / Interactivity endpoints: `permission_callback` denies the unauthorized, args validation rejects bad input, handler returns expected shape.
  - `?format=md` conversion and `llms.txt` generation output.
  - Any sanitization/validation helper the theme defines.
* **Security-focused assertions:** every endpoint test MUST include a case asserting an unauthorized or unauthenticated request is rejected — not just the happy path.

## 3. JavaScript — Jest (via `@wordpress/scripts`)
* **Framework:** Jest with `@wordpress/scripts test-unit-js`.
* **What to cover:** Interactivity API store `actions`/`state` logic — form validation rules, dark-mode toggle state, copy-to-clipboard, infinite-scroll pagination state. Test the store logic in isolation, not the DOM.

## 4. End-to-End — Playwright
* **Framework:** Playwright (`@wordpress/e2e-test-utils-playwright` where helpful).
* **Critical flows to cover:**
  - Mobile nav drawer: open, focus trap, `Escape` closes, focus restored.
  - Dark/light toggle: switches, persists across reload, no FOUC.
  - A representative form: inline validation feedback appears, invalid submit blocked, valid submit succeeds.
  - WooCommerce (if active): add-to-cart → cart → checkout renders.
* **Accessibility in e2e:** run `axe` within Playwright on key pages; fail on critical violations (ties to `02-frontend-and-styles/04-accessibility.md`).

## 5. Visual Regression
* Snapshot key templates and patterns (home, single, product, landing) at mobile + desktop widths; diff against a baseline to catch unintended CSS shifts. Run on PRs that touch `theme.json`, CSS, or patterns.

## 6. Coverage & Discipline
* **New feature or bug fix ⇒ a test.** A bug fix includes a regression test that fails before the fix and passes after.
* Target meaningful coverage of theme-authored PHP/JS logic (not a vanity percentage) — prioritize security callbacks, data transforms, and validation.
* Tests run in CI (`02-testing-and-ci.md`) and must pass before merge.

## 7. Verification Checklist
- [ ] PHPUnit covers Block Bindings, REST/Interactivity endpoints (incl. auth-denied cases), and GEO output.
- [ ] Jest covers Interactivity store logic.
- [ ] Playwright covers nav, dark mode, a form, and commerce flow; axe runs on key pages.
- [ ] Visual regression baselines exist for key templates.
- [ ] Every bug fix ships with a failing-then-passing regression test.
