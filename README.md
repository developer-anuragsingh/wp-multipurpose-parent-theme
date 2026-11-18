# 🏛️ Multi-Purpose WordPress Parent Theme
Production-ready Full-Site Editing (FSE) parent theme engineered with native WordPress Interactivity API, Generative Engine Optimization (GEO), Speculation Rules API, and theme.json v3.

**Requirements:** WordPress 6.8+ · PHP 8.1+ · Node LTS

👉 **New here? Start with [`CONTRIBUTING.md`](./CONTRIBUTING.md)** — step-by-step local setup for developers.

👉 **[View Master Plan (`docs/README.md`)](./docs/README.md)** — full technical documentation matrix.

👉 **[View Changelog (`CHANGELOG.md`)](./CHANGELOG.md)** — track completed features and release history.

## 📦 Building & Packaging Releases

> [!IMPORTANT]
> The compiled JavaScript and CSS assets in `theme/build/` are generated artifacts and intentionally gitignored.
> **Do not install the theme by creating a raw ZIP directly from GitHub source**, as it will lack compiled frontend script modules.
> Always generate the installable, production-ready theme package using:
> ```bash
> ./scripts/build-release.sh
> ```
> This compiles all Interactivity API modules with `@wordpress/scripts`, verifies version consistency across `style.css`, `functions.php`, and `package.json`, and packages an optimized `dist/atlas-theme-<version>.zip`.

## Roadmap

- Dedicated visual regression baselines for remaining WooCommerce product archive and cart block states.
- Interactive NGO Single-Click Donation Modal pattern (`atlas/donation-modal`).
