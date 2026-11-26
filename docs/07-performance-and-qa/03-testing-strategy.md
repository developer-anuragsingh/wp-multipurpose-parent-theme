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

## 7. What Exists Today (and how to run it)
* **PHP unit suite — `composer test` (`php tests/run-tests.php`, 259 tests).** A fast, dependency-free runner over lightweight WordPress doubles in `tests/bootstrap.php`. Suites include Security Headers, Contact Form Security (signed token, rate limit incl. the object-cache path, header injection), Markdown Endpoint (conversion, hostile `javascript:`/`<script>` input, cache, size cap), Assets & CSP nonce, Submissions & Privacy (private storage, retention, export/erase, hook wiring), Colours & Contrast (preset existence and AA pairs in both modes), Fixture Drift & Nav (harness markup equals the real header, no-JS drawer rules), Typography & Layout (font scale applies, body 14-16px, root side padding, header/footer alignment, 16px form fields), Icons (valid and safe SVG, every icon-only control named, no glyph stand-ins, static copies equal the registry), SEO & Schema output (escaping, noindex, canonical, JSON-LD hardening, plugin back-off), Schema Nodes & Cache (BlogPosting, BreadcrumbList for posts/terms/archives, generation-based invalidation and its hooks), 503 Maintenance, i18n, llms.txt and patterns. Doubles can drift from core; keep them faithful (e.g. `esc_url_raw` rejects non-allow-listed protocols like core does) and prefer adding a smoke check for anything the doubles cannot prove.
* **Live smoke test — `scripts/smoke-test.sh [base-url]`.** Runs against a real site (default `http://localhost:8885`, the Studio site) and proves what the doubles cannot: security headers are actually sent, the token endpoint is registered and `no-store`, and a missing or forged contact token is rejected with `403`, and the home page has one canonical link and a valid, `<`-free JSON-LD block. Run it after any change to headers, REST routes or the contact form, and before every release.
* **JS unit tests — `npm test`** and **Playwright E2E/visual/accessibility — `npm run test:e2e`** (fixture server, not WordPress; includes the axe-core scans in `tests/e2e/accessibility.spec.js`, see `02-frontend-and-styles/04-accessibility.md` §9, and the layout-shift and no-JS navigation guards in `tests/e2e/navigation-layout.spec.js`). Visual baselines are **per operating system** (`<name>-<project>-darwin.png` for local macOS runs, `-linux.png` for CI), because macOS and Linux rasterise text differently and a macOS baseline can never match a Linux render. The fixtures load the theme's real Inter/Sora webfonts from `theme.json` (never an OS font stack) and each visual test waits for `document.fonts.ready`, so both sets show identical layout. After an intentional design change: (1) locally run `npx playwright test tests/e2e/visual-regression.spec.js --update-snapshots=all` for the `-darwin` set; (2) on GitHub run the **Update visual baselines (Linux)** workflow (Actions tab, or `gh workflow run "Update visual baselines (Linux)"`), download its `visual-baselines-linux` artifact into `tests/e2e/visual-regression.spec.js-snapshots/` and commit the `-linux` files. Use `--update-snapshots=all` (a plain `--update-snapshots` skips images that still pass within the 5% tolerance and lets stale baselines accumulate).
* **Lighthouse gate — `scripts/lighthouse.sh [base-url] [path ...]`.** Mobile-emulated performance, accessibility, best-practices, SEO, TBT, LCP and CLS thresholds against a running site (`01-performance-budget.md` §5-6).
* **Live-site E2E — `npm run test:e2e:live`** (`tests/e2e/live-site.spec.js`). Runs against a real, running site and only when `LIVE_BASE_URL` is set (the npm script sets it to the Studio site, `http://localhost:8885`); without it, for example in CI, the file is skipped instead of failing with `ERR_CONNECTION_REFUSED` (it had kept `main` red since it was added). Its form test fills the form in under 3 s, so the anti-spam timing check answers with the deliberately generic "sent" message and nothing is stored or mailed: it verifies the UI flow, not delivery. To test delivery, wait longer than `Atlas\Contact_Form::MIN_SUBMIT_SECONDS` before submitting (each run then stores one real submission).
* **Ordering rule:** the `SEO & Schema` suite must stay **last** in `tests/run-tests.php` (and `Schema Nodes & Cache` must come before it): its final tests define an SEO-plugin constant (constants cannot be undefined) that disables Seo/Schema for the rest of the process.
* **Integration suite in real WordPress — `composer test:wp` (CI) / `composer test:wp:local` (laptop).** See §8. **Not covered anywhere yet:** the contact form's mail delivery through a real MTA (the suite uses core's mock mailer), multisite, and WooCommerce templates with WooCommerce active.
* **Verify tests can fail.** A suite that has only ever passed proves little. When adding a security control, break it on purpose (e.g. make the post type public, widen the eraser, delete the `add_action`) and confirm a test goes red, then revert. This caught a real gap: tests that registered hooks by hand never noticed `init()` forgetting one, so `init()` wiring now has its own test.
* **Stress before trusting a green run.** `npx playwright test --repeat-each=4` (and `--repeat-each=120` on a single spec) exposed a test that passed 96% of the time because it clicked a control the open drawer covers. A test that depends on animation timing is a bug in the test; fix it to do what a user can do.

## 8. Integration Suite in Real WordPress (`tests/integration/`)
The unit suite proves the theme's own logic against hand-written doubles; it cannot prove what **WordPress itself** does with the theme. This suite runs the theme inside the real WordPress core test library (`WP_UnitTestCase`, `wp-phpunit/wp-phpunit`) on a real database, with `roots/wordpress-no-content` as the core (dev-only; nothing here ships in the theme zip).

**What it covers (44 tests):** the theme activates and every concern is hooked; theme.json as core applies it (the theme font scale wins over core's defaults and core's sizes are not emitted, root side padding is emitted, the `surface` / `accent-text` / `link` presets exist); the contact REST routes through the real REST server (routes exist, token is `no-store`, missing or forged token -> 403, core's args schema -> 400, valid -> mail via core's mock mailer + private storage, honeypot and too-fast -> silent, header injection neutralised, sixth request -> 429 + `Retry-After`, no-JS -> 303 back to the referer, mail failure still stores); privacy (post type not public or in REST, editors cannot read a submission, exporter/eraser scoped to one email, real `date_query` purge, daily cron); front-end output (security headers, the CSP hash equals the inline script WordPress actually printed, core inline scripts get the nonce, BlogPosting + breadcrumb for a real categorised post, renaming the site refreshes an already-cached post graph, hostile titles cannot break the JSON-LD script, meta description escaping, search noindex, absolute `og:image`, EN/HI switcher); every template and part resolves as a block template, patterns register and render, the contact pattern ships a valid token and no nonce, maintenance uses the block-template canvas, Markdown uses core's URL sanitising.

**How it runs.**
* **CI** (`.github/workflows/ci.yml`, job `wp-integration`): MySQL 8 service, two legs: WordPress **6.8** (the minimum, `Requires at least`) on PHP **8.1**, and the version in `composer.lock` (currently 7.1) on PHP **8.4**. This run is authoritative.
* **Locally, without Docker or MySQL:** `composer test:wp:local`. It uses the SQLite integration plugin that WordPress Studio installs in every site (found automatically under `~/Studio`, `~/Developer` or `~/Sites`, or set `WP_TESTS_SQLITE_PLUGIN`). The suite gets its own throw-away SQLite file in the system temp directory; no Studio site database is opened. SQLite translates MySQL queries, so treat a local pass as a fast check and CI as the proof.
* **Against your own MySQL:** `composer test:wp` with `WP_TESTS_DB_HOST`, `WP_TESTS_DB_NAME`, `WP_TESTS_DB_USER`, `WP_TESTS_DB_PASSWORD`. The database is **emptied on every run**; never point it at a real site.

**How the theme is activated.** WordPress resolves themes by folder name and this theme's slug is `atlas-theme`, while the source lives in `./theme`. `tests/integration/bootstrap.php` creates a per-checkout temporary themes root with an `atlas-theme` symlink to `./theme`, registers it, and activates the theme through option filters (no database writes during bootstrap). A test asserts the suite really is testing this checkout's theme.

**Dependency note.** `composer.json` sets `config.platform.php` to `8.1` so the lock file only contains packages that install on the oldest supported PHP. Without it, Composer resolved `doctrine/instantiator` 2.2 (PHP 8.4+) from a PHP 8.5 machine, which would have broken `composer install` on the 8.1-8.3 CI legs.

**What writing it taught us.** Two unit-suite assumptions were wrong in real WordPress: core keeps its default font sizes in the settings tree even with `defaultFontSizes: false` (only the emitted CSS proves the fix), and core escapes the site tagline when it is saved, so markup in it is literal text. Both tests now assert what WordPress really does. Every security control in this suite was also broken on purpose (font switch, root padding, token check, submission capability) to confirm a test goes red.

