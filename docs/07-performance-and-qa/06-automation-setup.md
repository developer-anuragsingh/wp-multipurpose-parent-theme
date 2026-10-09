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
| **PHP standards** | `composer lint` (`phpcs`) | `.phpcs.xml.dist` | `WordPress` + `WordPress-Extra` ruleset, text domain `nexus-theme`, `nexus`/`Nexus`/`NEXUS` prefixes, min WP 6.8 |
| **PHP compatibility** | `composer lint` (`phpcs`) | `.phpcs.xml.dist` | `PHPCompatibilityWP` against `testVersion` `8.1-` |
| **PHP auto-fix** | `composer lint:fix` (`phpcbf`) | `.phpcs.xml.dist` | Applies the auto-fixable subset of the above |
| **JS lint** | `npm run lint:js` | `@wordpress/scripts` defaults | `wp-scripts lint-js` over `theme/src/js` |
| **CSS lint** | `npm run lint:css` | `@wordpress/scripts` defaults | `wp-scripts lint-style` over `theme/src/css/**/*.css` |
| **Build** | `npm run build` | `@wordpress/scripts` | Interactivity API bundles / optional Tailwind compile |
| **Secret scan** | `gitleaks` | `.gitleaks.toml` | Default gitleaks rules, scoped away from `vendor/`, `node_modules/`, `.worktrees/` |

The PHP ruleset only scans `theme/`; `vendor/`, `node_modules/`, and `.worktrees/` are excluded. `browserslist` in `package.json` targets the Tier-1 matrix from `08-reliability-and-seo/03-browser-support.md` so autoprefixer and `@wordpress/scripts` build to the same support policy.

## 3. Pre-Commit (local, fast)
`.husky/pre-commit` runs on every `git commit`:
1. **`lint-staged`** — lints only the staged files: `phpcs` on staged `*.php`, `wp-scripts lint-js` on staged JS, `wp-scripts lint-style` on staged CSS. Scoping to staged files keeps the hook fast.
2. **`gitleaks protect --staged`** — scans the staged diff for secrets, guarded by `command -v gitleaks`. If gitleaks is not installed locally the step prints a notice and continues; it never blocks a commit on a missing local tool. CI runs the authoritative scan.

The hook catches style and obvious secret issues before they reach CI. Per `05-coding-standards.md` §9, never bypass it with `--no-verify` to force a commit.

## 4. CI (authoritative, every push and PR)
`.github/workflows/ci.yml` runs on `push` and `pull_request` with three independent jobs:
* **`php`** — `shivammathur/setup-php@v2` (PHP 8.1) → `composer install` → `composer lint`.
* **`node`** — `actions/setup-node@v4` (Node LTS) → `npm ci` → `npm run lint:js` + `npm run lint:css`.
* **`secrets`** — full-history checkout → `gitleaks/gitleaks-action@v2` using `.gitleaks.toml`.

The jobs mirror the local gates but run clean-room, so a commit that slipped past (or bypassed) the local hook is still caught. CI is the Definition-of-Done gate from `02-testing-and-ci.md` §1 — a change that would break CI is not done.

## 5. How Pre-Commit and CI Relate
Pre-commit is a fast, local, best-effort filter over staged files; CI is the slower, authoritative, whole-repo gate. Pre-commit reduces round-trips (style/secret problems caught before push); CI guarantees the gates actually ran regardless of local setup. Neither replaces the manual QA passes (accessibility, cross-browser, RTL/i18n) in `02-testing-and-ci.md` §5.
