# 02. QA, Testing & CI Pipeline

**File:** `docs/07-performance-and-qa/02-testing-and-ci.md`
**Module:** Performance & QA

## 1. Definition of Done
A change is "done" only when it passes every gate below. A command exiting without error on one surface is not proof the whole change is production-ready. Never bypass hooks or skip gates to declare completion.

## 2. Quality Gates
| Gate | Tool | Pass Criteria |
| :--- | :--- | :--- |
| **PHP standards** | PHP_CodeSniffer + WordPress Coding Standards (`WordPress`, `WordPress-Extra`) | Zero errors |
| **PHP compatibility** | `PHPCompatibilityWP` against `Requires PHP: 8.1` | Zero errors |
| **JS lint** | `wp-scripts lint-js` | Zero errors |
| **CSS lint** | `wp-scripts lint-style` | Zero errors |
| **theme.json** | JSON Schema validation against `https://schemas.wp.org/trunk/theme.json` | Valid |
| **Template validity** | Block markup parses; required templates present (`index.html` minimum) | Valid |
| **Theme Check** | Theme Check plugin (distribution readiness) | Zero errors |
| **Accessibility** | axe / Lighthouse a11y + manual pass (see `02-frontend-and-styles/04-accessibility.md`) | ≥ 95, zero critical |
| **Performance** | Lighthouse + bundle-size budget (see `01-performance-budget.md`) | Within budget |

## 3. Tooling Setup
* **`composer.json`** declares dev dependencies: `squizlabs/php_codesniffer`, `wp-coding-standards/wpcs`, `phpcompatibility/phpcompatibility-wp`, and wires a `composer lint` script.
* **`package.json`** uses `@wordpress/scripts` for `lint-js` / `lint-style` and build steps (Interactivity API bundles, optional Tailwind).
* **`.phpcs.xml.dist`** pins the ruleset (`WordPress-Extra`), the text domain (`nexus-theme`), the minimum WP/PHP versions, and the paths to scan.

## 4. CI (`.github/workflows/`)
A `ci.yml` workflow runs on every push and pull request:

```yaml
# .github/workflows/ci.yml (shape, not final)
name: CI
on: [push, pull_request]
jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.1' }
      - run: composer install --no-progress
      - run: composer lint          # PHPCS + PHPCompatibility
      - uses: actions/setup-node@v4
        with: { node-version: 'lts/*' }
      - run: npm ci
      - run: npm run lint:js
      - run: npm run lint:css
      - run: npm run build
      # theme.json schema validation + Theme Check run here
```

Deployment (rsync/SFTP for third-party hosts, Studio Sync for WordPress.com/Pressable per `06-security-and-i18n/02-devops-and-i18n.md`) runs only after all gates pass on `main`.

## 5. Manual QA (not automatable)
Automated gates are necessary but not sufficient. Before a release, run manual passes for:
* **Accessibility:** keyboard-only and screen-reader walkthrough of key templates.
* **Cross-browser:** latest Chrome, Firefox, Safari + mobile Safari / Chrome Android.
* **RTL / i18n:** Hindi (`hi_IN`) locale renders correctly; strings are translated, not hardcoded.
* **Child-theme inheritance:** a sample child theme (`theme.json` + template override) cascades and overrides as documented.
* **Security spot-check:** every REST / Interactivity endpoint has a `permission_callback` with a capability check and validates its arguments (see `06-security-and-i18n/01-security-and-data.md`).

## 6. Local Verification Commands
```bash
composer lint            # PHPCS (WordPress-Extra) + PHP 8.1 compatibility
npm run lint:js          # wp-scripts lint-js
npm run lint:css         # wp-scripts lint-style
npm run build            # Interactivity API bundles / Tailwind
```
Run these before every commit; CI enforces the same set.
