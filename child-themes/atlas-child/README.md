# Atlas Child Starter (Starter Boilerplate)

A modern, minimal **Zero-PHP Child Theme Boilerplate** for the [Atlas Multipurpose Parent Theme](https://github.com/developer-anuragsingh/wp-multipurpose-parent-theme).

> **Role:** Blank starter template for developers and agencies creating bespoke client designs. For a production-ready corporate theme, see `child-themes/atlas-business`.

## Key Principles
- **0 KB PHP:** No `functions.php`. Stylesheet and asset cascading are handled natively by WordPress 6.8+ Full-Site Editing.
- **Design Tokens First:** All visual customisations (colors, fonts, spacing) live in `theme.json` and cascade into the parent theme.
- **Pure HTML Templates:** Layout customisations are done by dropping `.html` files into `templates/` or `parts/`.

## Installation
1. Ensure the parent theme (`atlas-theme`) is installed in `wp-content/themes/atlas-theme`.
2. Copy this folder into `wp-content/themes/atlas-child`.
3. In WordPress Admin, navigate to **Appearance → Themes** and activate **Atlas Child Starter**.
