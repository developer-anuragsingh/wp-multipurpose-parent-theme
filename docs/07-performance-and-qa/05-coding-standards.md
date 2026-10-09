# 05. Coding Standards & Principles

**File:** `docs/07-performance-and-qa/05-coding-standards.md`
**Module:** Performance & QA

> `02-testing-and-ci.md` enforces code *style* (PHPCS, lint). This doc governs code *design* — the principles a linter cannot check: simplicity, reuse, structure, and naming. Both apply to all theme-authored PHP, JS, and CSS.

## 1. Core Principles

### KISS — Keep It Simple
Prefer the simplest solution that fully solves the problem. This theme's architecture already embodies KISS (Zero-KB JS, `theme.json`-first styling, no heavy frameworks). Do not add abstraction, configurability, or cleverness the task does not require.

### DRY — Don't Repeat Yourself
Repeated logic, markup, or values are centralized: reusable sections become Block Patterns / Synced Pattern Overrides, repeated field reads use Block Bindings, and repeated/semantic strings become constants (§3). DRY targets *knowledge* duplication — do not over-abstract two lines that merely look alike.

### SOLID — scoped to OOP PHP only
SOLID is an object-oriented principle set. It applies to the class-based PHP in `inc/` (Block Bindings sources, REST controllers, security/SEO services) — **not** to HTML templates, `theme.json`, or patterns, which have no classes. Where classes exist:
* **S**ingle Responsibility — one class/function, one reason to change.
* **O**pen/Closed — extend via hooks/filters, don't modify core behaviour in place.
* **L**iskov, **I**nterface Segregation, **D**ependency Inversion — depend on small interfaces/abstractions, inject dependencies rather than hardcoding them.

### YAGNI — You Aren't Gonna Need It
Build only what the current requirement needs. This reinforces the "grill-first" anti-passivity rule in `agent-rules.md` — challenge speculative features before coding them.

### Separation of Concerns
Markup lives in `templates/` + `parts/`, design tokens in `theme.json`, behaviour in `inc/` (PHP) and the Interactivity API store (JS). Do not blur these — e.g. no structural CSS in `style.css`, no business logic in templates.

## 2. Function & File Structure
* **Single responsibility:** a function does one thing; if it needs "and" to describe it, split it.
* **Size limits (guidance, not dogma):** functions stay small and readable; break up long files. High cyclomatic complexity (deeply nested conditionals) is refactored with guard clauses / early returns.
* **Guard clauses over deep nesting:** return early on invalid input instead of wrapping the body in `if`.
* **No dead or commented-out code** in the repo — delete it; git history preserves it.
* **Pure where possible:** prefer functions that transform input to output without side effects, especially in the JS store.

## 3. Strings & Constants
Centralize **repeated or semantic** strings; do not blindly constant-ify every literal.

* **MUST be constants / class constants:** meta keys, option names, hook/action/filter names, nonce actions, custom post type & taxonomy slugs, REST namespaces (e.g. `nexus/v1`), transient/cache keys, capability strings. A typo in a repeated magic string is a silent bug; a constant makes it a hard error.
* **MUST stay literal (never constants):**
  - **User-facing strings** — these go through gettext (`esc_html__( 'Read More', 'nexus-theme' )`). Putting them in a constant breaks translation extraction (see `06-security-and-i18n/02-devops-and-i18n.md`).
  - **The text domain** `'nexus-theme'` — always a literal string argument so extraction tools can parse it.
* **Leave alone (KISS):** a trivial, single-use local string with no shared meaning. Forcing it into a constant hurts readability without benefit.

## 4. WordPress Naming & Organization
* **Prefix everything global** with `nexus_` / `NEXUS_` / `Nexus\` (functions, constants, hooks, option keys, class namespace) to avoid collisions with core and plugins.
* **Casing:** `snake_case` for functions/variables/hooks, `PascalCase` for classes, `UPPER_SNAKE_CASE` for constants — matching WordPress Coding Standards.
* **File organization in `inc/`:** one concern per file/subfolder — e.g. `inc/bindings/`, `inc/rest/`, `inc/security/`, `inc/seo/`. Autoload the `Nexus\` namespace from `inc/` via the theme's own hand-rolled autoloader (declared in `functions.php`) using **WordPress class-file naming** — a class maps to `class-{lowercased-hyphenated}.php` (e.g. `Nexus\Setup` → `inc/class-setup.php`; sub-namespaces → subdirectories). This is NOT Composer PSR-4: Composer is dev-only tooling here and no `vendor/autoload.php` ships with the theme (agent-rules §4 bans runtime deps). The naming satisfies the enforced `WordPress-Extra` ruleset. Prefer this over a wall of manual `require`.
* **Enqueue standards:** register/enqueue assets on the correct hook, versioned with `NEXUS_THEME_VERSION`, loaded conditionally (only where used) to protect the JS/CSS budget.

## 5. CSS Standards
* Organize specificity with Cascade Layers (`@layer`) in a documented order; keep critical base styles outside layers for progressive enhancement (`08-reliability-and-seo/03-browser-support.md`).
* Avoid `!important` except as a last, documented resort.
* Use **logical properties** (`margin-inline-start`, `padding-block`) for RTL safety (`02-frontend-and-styles/04-accessibility.md`).
* No hardcoded colors/spacing where a `theme.json` CSS custom property exists.

## 6. JavaScript (Interactivity API) Standards
* Structure the store with clear separation of `state`, `actions`, and `callbacks`; keep actions small and testable (`07-performance-and-qa/03-testing-strategy.md`).
* No jQuery / no JS framework / no validation library (Zero-KB baseline).
* **Debounce vs. throttle** (ties to `02-frontend-and-styles/05-forms-and-validation.md`):
  - **Debounce** expensive/async work that should run only after the user pauses (async field validation, search-as-you-type) — not cheap local checks, which stay instant.
  - **Throttle** high-frequency continuous events (scroll for infinite scroll, resize) so a handler runs at a bounded rate.

## 7. Error Handling & Return Values
* Use `WP_Error` consistently for recoverable failures so callers can branch; reserve exceptions for truly exceptional paths.
* Never fail silently — return a meaningful value or error, and provide safe fallbacks (`08-reliability-and-seo/01-error-handling.md`).

## 8. Documentation (code comments)
* Every function/class/method has a PHPDoc/JSDoc block (`@param`, `@return`, `@since`).
* Comments explain **why**, not **what** — the code already says what it does. Document non-obvious decisions, edge cases, and security/compat reasoning.

## 9. Git & Collaboration
* **Commit messages:** Conventional Commits (`feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`).
* **Branching:** feature branches off the integration branch; no direct commits to the release branch.
* **PRs:** small, focused, with a description of what/why and how it was verified; must pass CI (`02-testing-and-ci.md`) and a review before merge.
* **Pre-commit hooks:** run lint (and fast tests) locally so style/obvious errors never reach CI; never bypass hooks to force a commit.

## 10. Dependency Hygiene
* Keep dependencies minimal and justified; pin exact/locked versions.
* Vet new packages (maintenance, popularity, typosquatting) before adding.
* Follow the deprecation policy in `08-reliability-and-seo/05-updates-and-recovery.md` before removing anything downstream may rely on.

## 11. Verification Checklist
- [ ] Simplest solution that works (KISS); no speculative features (YAGNI).
- [ ] No duplicated knowledge; reusable logic/markup/strings centralized (DRY).
- [ ] SOLID applied to `inc/` OOP; concerns separated across markup/tokens/logic.
- [ ] Semantic/repeated strings are constants; user-facing strings stay in gettext.
- [ ] `nexus_`-prefixed, correctly-cased, organized under `inc/` with autoloading.
- [ ] Debounce async-only; throttle scroll/resize; local checks instant.
- [ ] PHPDoc/JSDoc present; comments say why.
- [ ] Conventional commit; PR passes CI + review; hooks not bypassed.
