# 🏛️ Multi-Purpose WordPress Parent Theme
Production-ready Full-Site Editing (FSE) parent theme engineered with native WordPress Interactivity API, Generative Engine Optimization (GEO), Speculation Rules API, and theme.json v3.

**Requirements:** WordPress 6.8+ · PHP 8.1+ · Node LTS

👉 **New here? Start with [`CONTRIBUTING.md`](./CONTRIBUTING.md)** — step-by-step local setup for developers.

👉 **[View Master Plan (`docs/README.md`)](./docs/README.md)** — full technical documentation matrix.

👉 **[View Changelog (`CHANGELOG.md`)](./CHANGELOG.md)** — track completed features and release history.
👉 **Found a security problem? Read [`SECURITY.md`](./SECURITY.md)** and report it privately, not in a public issue.

## 📦 Building & Packaging Releases

> [!IMPORTANT]
> The compiled JavaScript and CSS assets in `theme/build/` are generated artifacts and intentionally gitignored.
> **Do not install the theme by creating a raw ZIP directly from GitHub source**, as it will lack compiled frontend script modules.
> Always generate the installable, production-ready theme package using:
> ```bash
> ./scripts/build-release.sh
> ```
> This compiles all Interactivity API modules with `@wordpress/scripts`, verifies version consistency across `style.css`, `functions.php`, and `package.json`, and packages an optimized `dist/atlas-theme-<version>.zip` alongside installable packages for child themes in `dist/`.

## 👶 Official Zero-PHP Child Themes (`child-themes/`)

Atlas includes official **Zero-PHP** child themes adhering to WordPress 6.8+ Full-Site Editing architecture. They contain no `functions.php`, achieving styling and layout customizations purely through `theme.json` v3 cascading and HTML template parts.

- **`child-themes/atlas-child` (Atlas Child Starter):**
  - **Purpose:** A clean, minimal boilerplate for developers and agencies building custom bespoke client themes from scratch.
  - **Included:** Blank template hierarchy, baseline color overrides in `theme.json`, and sample header template parts.
  - **Scaffolding:** New custom child themes can be instantly generated via `./scripts/create-child-theme.sh <slug> [Theme Name]`.

- **`child-themes/atlas-business` (Atlas Business):**
  - **Purpose:** A production-ready, turnkey corporate theme built for enterprise, B2B services, consulting, and SaaS platforms.
  - **Included:** Deep Navy & Emerald design tokens, high-impact services bento grid (`patterns/services-bento.php`), tailored typography scales, and custom corporate front-page template.

## Roadmap

- Dedicated visual regression baselines for remaining WooCommerce product archive and cart block states.
- Interactive NGO Single-Click Donation Modal pattern (`atlas/donation-modal`).
