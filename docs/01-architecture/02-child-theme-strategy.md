# 02. Zero-PHP Child Theme Architecture

**File:** `docs/01-architecture/02-child-theme-strategy.md`  
**Module:** Architecture  

## 1. The Zero-PHP Concept
In traditional WordPress development, child themes required a `functions.php` file utilizing `wp_enqueue_scripts` to load the parent theme's stylesheet[cite: 2]. 

This parent theme strictly enforces a **Zero-PHP Child Theme Architecture**. In modern block (FSE) themes, creating a child theme is simpler—you only need a folder and a `style.css` file[cite: 2]. Because we rely entirely on Full-Site Editing (FSE) and `theme.json` for global styling, WordPress natively handles the stylesheet cascading, meaning you do not need to enqueue the parent stylesheet via `functions.php`[cite: 2].

## 2. Child Theme Minimal Directory Structure
To create a niche-specific child theme (e.g., E-Commerce or Corporate), the directory should look like this:

```text
wp-content/themes/nexus-corporate/
├── style.css            # Mandatory: Theme metadata and Template linkage
├── theme.json           # Optional: Overrides for colors, typography, and spacing
├── templates/           # Optional: Niche-specific HTML layout overrides
│   └── front-page.html
└── parts/               # Optional: Niche-specific structural parts
```

## 3. Mandatory Initialization Files

### A. `style.css` (The Linker)
The child theme does not use `style.css` for writing CSS rules. It is exclusively used to define the theme metadata and link it to the parent theme. 

**Critical Rule:** The `Template:` value must match the exact directory name (slug) of your parent theme, case-sensitive[cite: 2].

```css
/*
 Theme Name:   Nexus Corporate
 Theme URI:    [https://github.com/developer-anuragsingh/wp-multipurpose-parent-theme](https://github.com/developer-anuragsingh/wp-multipurpose-parent-theme)
 Description:  Corporate & Enterprise child theme for Nexus Parent.
 Author:       Anurag Singh
 Template:     wp-multipurpose-parent-theme
 Version:      1.0.0
 Text Domain:  nexus-corporate
*/
```

### B. `theme.json` (The Cascade)
When a child theme includes a `theme.json` file, it does **not** replace the parent's `theme.json`. Instead, WordPress automatically cascades (merges) the two files[cite: 2]. 

The child theme only needs to declare the properties it wants to change. For example, to override the primary brand color for the Corporate niche, the child `theme.json` only needs:

```json
{
  "$schema": "[https://schemas.wp.org/trunk/theme.json](https://schemas.wp.org/trunk/theme.json)",
  "version": 3,
  "settings": {
    "color": {
      "palette": [
        {
          "slug": "primary",
          "color": "#0f172a",
          "name": "Corporate Navy"
        }
      ]
    }
  }
}
```
All other settings (layout widths, typography scales, spacing presets) are automatically inherited from the parent theme.

## 4. Template & Part Overrides
If a specific niche requires a different layout for a product or a blog post, do not use PHP hooks. To modify parent block templates safely, you only need to copy the relevant `.html` file into your child theme's `/templates/` or `/parts/` folder[cite: 2]. WordPress will automatically prioritize the child theme's HTML file during rendering.

## 5. Developer & AI Agent Guidelines
* **No `functions.php` Initialization:** Never create a `functions.php` in the child theme simply to enqueue stylesheets[cite: 2].
* **No Custom CSS:** Avoid writing CSS in the child theme's `style.css`. Rely on `theme.json` to generate the necessary CSS Custom Properties.
* **Independent Parts:** If a child theme requires a unique header, create it inside the child theme's `/parts/header.html` rather than trying to conditionally filter the parent's header.