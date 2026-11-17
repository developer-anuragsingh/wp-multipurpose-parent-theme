# 06. Quality Automation Setup

**File:** `docs/07-performance-and-qa/06-automation-setup.md`
**Module:** Performance & QA

> `02-testing-and-ci.md` defines *which* gates a change must pass. This document covers the *tooling* that runs them — the config files at the repo root, the pre-commit hook, and the CI workflow — and how they fit together. The gate definitions in `02-testing-and-ci.md` remain authoritative; nothing here loosens them.

## 1. One-Time Install

> **New to this repo? Run this first.** Immediately after cloning, run `composer install && npm install` in the repo root. This installs the dev tooling AND activates the git pre-commit hook (via the `prepare` script) so every teammate is held to the same quality gates automatically. Until you run it, the pre-commit hook stays dormant on your machine — CI still enforces the gates on push, but local checks won't fire.

The tooling is dev-only; it adds no runtime dependency to the shipped theme.

```bash
composer install   # PHP_CodeSniffer + WordPress Coding Standards + PHPCompatibilityWP
npm install        # @wordpress/scripts, husky, lint-staged (also installs the git hook)
```

`npm install` runs the `prepare` script, which calls `husky` to register the git hooks in `.husky/`. After it finishes, `git commit` will automatically run the pre-commit gate.

## 2. What Each Gate Does
| Gate | Entry point | Config | Enforces |
| :--- | :--- | :--- | :--- |
| **PHP standards** | `composer lint` (`phpcs`) | `.phpcs.xml.dist` | `WordPress` + `WordPress-Extra` ruleset, text domain `atlas-theme`, `atlas`/`Atlas`/`ATLAS` prefixes, min WP 6.8 |
| **PHP compatibility** | `composer lint` (`phpcs`) | `.phpcs.xml.dist` | `PHPCompatibilityWP` against `testVersion` `8.1-` |
| **PHP auto-fix** | `composer lint:fix` (`phpcbf`) | `.phpcs.xml.dist` | Applies the auto-fixable subset of the above |
| **JS lint** | `npm run lint:js` | `@wordpress/scripts` defaults | `wp-scripts lint-js` over `theme/src/js` |
| **CSS lint** | `npm run lint:css` | `@wordpress/scripts` defaults | `wp-scripts lint-style` over `theme/src/css/**/*.css` |
| **Build** | `npm run build` | `@wordpress/scripts` | Interactivity API bundles / optional Tailwind compile |
| **Dependency audit** | `composer audit --locked`, `npm audit` | `composer.lock`, `package-lock.json` | Known-vulnerability advisories (see §4) |
| **Secret scan** | `gitleaks` | `.gitleaks.toml` | Default gitleaks rules, scoped away from `vendor/`, `node_modules/`, `.worktrees/` |

The PHP ruleset only scans `theme/`; `vendor/`, `node_modules/`, and `.worktrees/` are excluded. `browserslist` in `package.json` targets the Tier-1 matrix from `08-reliability-and-seo/03-browser-support.md` so autoprefixer and `@wordpress/scripts` build to the same support policy.

## 3. Pre-Commit (local, fast)
`.husky/pre-commit` runs on every `git commit`:
1. **`lint-staged`** — lints only the staged files: `phpcs` on staged `*.php`, `wp-scripts lint-js` on staged JS, `wp-scripts lint-style` on staged CSS. Scoping to staged files keeps the hook fast.
2. **`gitleaks protect --staged`** — scans the staged diff for secrets, guarded by `command -v gitleaks`. If gitleaks is not installed locally the step prints a notice and continues; it never blocks a commit on a missing local tool. CI runs the authoritative scan.

The hook catches style and obvious secret issues before they reach CI. Per `05-coding-standards.md` §9, never bypass it with `--no-verify` to force a commit.

## 4. CI (authoritative, every push and PR)
`.github/workflows/ci.yml` runs on `push` and `pull_request` with independent jobs:
* **`php`** — `shivammathur/setup-php` (PHP 8.1) → `composer install` → `composer lint` → `composer test`.
* **`node`** — `actions/setup-node` (Node LTS) → `npm ci` → `npm run lint:js` + `npm run lint:css` → `npm test`.
* **`e2e`** — build + Playwright E2E and visual regression.
* **`secrets`** — full-history checkout → `gitleaks/gitleaks-action` using `.gitleaks.toml`. On pull requests the action needs `GITHUB_TOKEN` in its `env` and the job needs `pull-requests: read` (it reads the PR's commits through the API); without them it stops with "GITHUB_TOKEN is now required to scan pull requests". Push events do not need the token, which is why this only shows up on PRs (for example Dependabot's).
* **`Update visual baselines (Linux)`** (separate, manual `workflow_dispatch`, read-only) renders the visual-regression baselines on the CI image and uploads them as an artifact to commit (`03-testing-strategy.md` §7).
* **`commitlint`** — Conventional Commits on the PR range or pushed commit.
* **`package`** — runs `scripts/build-release.sh` so a broken build or a leaky zip fails the PR, not the release.
* **`audit`** — `composer audit --locked` (blocking); `npm audit --omit=dev` (blocking: the theme ships no npm runtime dependencies, so this must stay at 0); `npm audit --audit-level=critical` (blocking); `npm audit --audit-level=high` (informational, `continue-on-error`) because build-tool (`@wordpress/scripts`) advisories can only be fixed upstream and never ship to visitors.

**Supply-chain hardening**
* Every third-party action is **pinned to a full commit SHA** (the readable tag is kept as a trailing comment). A moved or hijacked tag can no longer change what CI runs. To update, change the SHA and comment together, or let Dependabot do it.
* The workflow sets top-level `permissions: contents: read` (least privilege). `release-please.yml` is the only workflow with write scopes (`contents`, `pull-requests`), scoped to `main`.
* `concurrency` cancels superseded runs on branches (never on `main`).
* `.github/dependabot.yml` opens weekly grouped PRs for GitHub Actions, npm and Composer; security advisories open PRs immediately.
* Recommended repository settings (not enforceable from code): branch protection on `main` requiring the CI jobs above, "Require approval for first-time contributors", and GitHub secret scanning + push protection.

The jobs mirror the local gates but run clean-room, so a commit that slipped past (or bypassed) the local hook is still caught. CI is the Definition-of-Done gate from `02-testing-and-ci.md` §1 — a change that would break CI is not done.

## 5. How Pre-Commit and CI Relate
Pre-commit is a fast, local, best-effort filter over staged files; CI is the slower, authoritative, whole-repo gate. Pre-commit reduces round-trips (style/secret problems caught before push); CI guarantees the gates actually ran regardless of local setup. Neither replaces the manual QA passes (accessibility, cross-browser, RTL/i18n) in `02-testing-and-ci.md` §5.

## 6. Release Packaging
`theme/build/` (compiled JS/CSS) is generated and gitignored, so zipping the repo or `theme/` by hand ships a theme **without its scripts**. Always package with:
```bash
npm ci
scripts/build-release.sh          # builds, verifies, writes dist/atlas-theme-<version>.zip (+ .sha256)
scripts/build-release.sh --skip-build   # package the existing build/ output
```
The script (1) refuses to run if `style.css`, `ATLAS_THEME_VERSION` in `functions.php` and `package.json` disagree on the version, (2) builds and checks every expected bundle exists, (3) stages a clean copy (no `.DS_Store`, source maps, `.gitignore`, or `src/js/`; `src/css/interactivity.css` is kept because the theme enqueues it), (4) zips deterministically with top-level folder `atlas-theme/` (the required slug), and (5) verifies the archive contents.
**Versioning:** release-please bumps `package.json` and, via `extra-files` plus `x-release-please-start-version` / `-end` markers, `theme/style.css` and `theme/functions.php`. Do not delete those marker lines. **How a release happens:** every push to `main` runs release-please, which keeps one open release PR (`chore(main): release ...`) with the next version (from `feat:` = minor, `fix:` = patch, `!`/`BREAKING CHANGE` = major) and the generated changelog. Merging that PR (squash) creates the tag `atlas-wp-multipurpose-parent-theme-vX.Y.Z` and the GitHub Release. Prerequisite: repository **Settings -> Actions -> General -> Workflow permissions -> "Allow GitHub Actions to create and approve pull requests"** must be on, or release-please fails with "GitHub Actions is not permitted to create or approve pull requests". Note: CI does not run on the release PR, because GitHub does not trigger workflows for PRs opened with the built-in `GITHUB_TOKEN`; it only changes versions and the changelog, and the full CI runs on `main` after the merge (use a PAT or GitHub App token in the workflow if PR checks are required). On a release, `.github/workflows/release-please.yml` builds the zip and attaches it and its SHA-256 to the GitHub Release; keep the previous release asset for rollback (`08-reliability-and-seo/05-updates-and-recovery.md`).
