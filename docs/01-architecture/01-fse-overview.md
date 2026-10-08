# 01. FSE Architecture & Directory Hierarchy

**File:** `docs/01-architecture/01-fse-overview.md`  
**Module:** Architecture  

## 1. The FSE Paradigm Shift & Core Pillars
This parent theme completely abandons classic WordPress hook-driven architecture (where layouts rely on `header.php`, `footer.php`, and `functions.php`). Instead, it utilizes the **Modern Block (FSE) Theme architecture**, driven entirely by Gutenberg blocks and HTML[cite: 2].

The architecture is built on four core pillars:
* **`theme.json` (Global Styles):** The central configuration file for global design tokens, typography, and layout widths, replacing massive traditional CSS files[cite: 2].
* **HTML Templates & Parts:** Pure HTML files containing block markup (e.g., `<!-- wp:group -->`), eliminating the need for PHP template rendering[cite: 2].
* **The Site Editor:** Replaces the legacy Customizer and Widgets screens, allowing global visual editing of the entire site canvas via `Appearance → Editor`[cite: 2].
* **Block Patterns:** Reusable sections (e.g., Hero Banners, Pricing Tables) registered via PHP to allow one-click insertions anywhere on the site[cite: 2].

## 2. Core Directory Structure
The theme follows a strict, zero-bloat FSE directory structure:

```text
wp-multipurpose-parent-theme/
├── theme.json           # Global design tokens, layout width, and appearance tools
├── style.css            # Theme metadata (No structural CSS)
├── functions.php        # Theme bootstrap, text domain, and block bindings init (Zero frontend markup)
├── templates/           # HTML templates defining page structures
│   ├── index.html       # Fallback template
│   ├── single.html      # Single post/blog layout
│   ├── page.html        # Standard page layout
│   ├── 404.html         # Error page
│   └── blank.html       # Empty canvas for Coming Soon / Landing pages
├── parts/               # Reusable HTML template parts
│   ├── header.html      # Global header
│   ├── footer.html      # Global footer
│   └── footer-landing.html # Decoupled isolated footer for landing pages
└── patterns/            # PHP files registering block patterns
```

## 3. The `theme.json` (v3) Engine
The `theme.json` file is the central command for all styling. 
* **Appearance Tools:** Configured with `"appearanceTools": true`, exposing advanced typography, padding, margins, borders, and native grid layouts (`layout.type: "grid"`) directly in the Gutenberg editor[cite: 2].
* **CSS Variable Generation:** Registers the color palette and typography scales, forcing WordPress to automatically generate optimized CSS Custom Properties (e.g., `--wp--preset--color--primary`)[cite: 2].

## 4. Navigation Architecture (`wp_navigation`)
The theme abandons the classic `Appearance → Menus` screen. Instead, navigation data is natively stored in the database as the `wp_navigation` custom post type[cite: 2]. Administrators manage all links, sub-menus, and mobile dropdowns directly within the Site Editor using the core Navigation Block[cite: 2].

## 5. Decoupled Template Parts
To serve multiple niches efficiently (especially Landing Pages and Coming Soon modes), the architecture utilizes **Decoupled Template Parts**. Rather than injecting conditional logic (`if (is_front_page())`) into a global PHP file, dedicated template files (e.g., `blank.html`) are constructed using distinct HTML parts like `parts/footer-landing.html`. This ensures niche layouts remain completely independent of the global site design.

## 6. Local Font Library API
The theme utilizes the native WordPress Font Library API. External Google Fonts are not enqueued via `<link>` tags. Instead, font assets (`.woff2`) are declared directly in `theme.json` (under `settings.typography.fontFamilies`), ensuring local hosting, faster First Contentful Paint (FCP), and strict GDPR compliance.

## 7. Local Development & WordPress Studio Compatibility
The file architecture is strictly optimized for **WordPress Studio** (WASM + SQLite environment)[cite: 4]. Because Studio runs SQLite instead of MySQL, all core architectural features (like patterns, layout widths, and template structures) are defined entirely in code (`.html` and `.json`) rather than the database[cite: 4]. This ensures seamless 1:1 GitHub deployments and reliable 7-day cloud preview generations for clients[cite: 4].