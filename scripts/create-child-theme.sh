#!/usr/bin/env bash
#
# create-child-theme.sh — Scaffold a modern Zero-PHP Child Theme for Atlas.
#
# WHY: In modern Full-Site Editing (FSE), child themes require NO PHP code.
# Global styles, color palettes, fonts, and template overrides are achieved
# exclusively via style.css, theme.json cascading, and HTML template parts.
#
# USAGE:
#   scripts/create-child-theme.sh <slug> [Theme Name] [Target Directory]
#
# EXAMPLES:
#   scripts/create-child-theme.sh atlas-corporate "Atlas Corporate"
#   scripts/create-child-theme.sh atlas-ngo "Atlas NGO" /path/to/wp-content/themes/
#

set -euo pipefail

# --- Argument Validation ---
if [[ $# -lt 1 ]]; then
	echo "Usage: $0 <slug> [Theme Name] [Target Directory]" >&2
	echo "Example: $0 atlas-corporate \"Atlas Corporate\"" >&2
	exit 1
fi

CHILD_SLUG="$1"
CHILD_NAME="${2:-${CHILD_SLUG}}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
TARGET_BASE="${3:-${REPO_ROOT}}"

# Target directory
CHILD_DIR="${TARGET_BASE}/${CHILD_SLUG}"

if [[ -d "${CHILD_DIR}" ]]; then
	echo "ERROR: Target directory already exists: ${CHILD_DIR}" >&2
	exit 1
fi

echo "Scaffolding Zero-PHP child theme '${CHILD_NAME}' at: ${CHILD_DIR}..."

# Create directory structure
mkdir -p "${CHILD_DIR}/templates"
mkdir -p "${CHILD_DIR}/parts"

# 1. Create style.css (Theme metadata and parent linkage only)
cat <<EOF > "${CHILD_DIR}/style.css"
/*
Theme Name:   ${CHILD_NAME}
Theme URI:    https://github.com/developer-anuragsingh/wp-multipurpose-parent-theme
Description:  Zero-PHP Child Theme for Atlas Multipurpose Parent Theme.
Author:       Anurag Singh
Template:     atlas-theme
Version:      1.0.0
Text Domain:  ${CHILD_SLUG}
License:      GNU General Public License v2 or later
*/

/*
 * NOTE: Modern FSE child themes do NOT need custom CSS or @import rules here.
 * Design tokens and styling overrides cascade automatically via theme.json.
 */
EOF

# 2. Create theme.json (Design token overrides that cascade into parent)
cat <<EOF > "${CHILD_DIR}/theme.json"
{
  "\$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "settings": {
    "color": {
      "palette": [
        {
          "slug": "primary",
          "color": "#0f172a",
          "name": "Primary Brand"
        },
        {
          "slug": "accent",
          "color": "#059669",
          "name": "Accent Highlight"
        }
      ]
    }
  }
}
EOF

# 3. Create sample template part and front-page override
cat <<EOF > "${CHILD_DIR}/parts/header-compact.html"
<!-- wp:group {"tagName":"header","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"backgroundColor":"light","layout":{"type":"flex","justifyContent":"space-between"}} -->
<header class="wp-block-group alignfull has-light-background-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">
	<!-- wp:site-title {"level":1} /-->
	<!-- wp:navigation {"layout":{"type":"flex","setCascadingProperties":true,"justifyContent":"right"}} /-->
</header>
<!-- /wp:group -->
EOF

cat <<EOF > "${CHILD_DIR}/templates/front-page.html"
<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","anchor":"atlas-main","layout":{"type":"default"}} -->
<main id="atlas-main" class="wp-block-group">
	<!-- wp:pattern {"slug":"atlas/hero-answer-first"} /-->
	<!-- wp:pattern {"slug":"atlas/feature-grid"} /-->
	<!-- wp:pattern {"slug":"atlas/pricing-table"} /-->
	<!-- wp:pattern {"slug":"atlas/faq"} /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->
EOF

# 4. Create README.md
cat <<EOF > "${CHILD_DIR}/README.md"
# ${CHILD_NAME}

A modern **Zero-PHP Child Theme** for the [Atlas Multipurpose Parent Theme](https://github.com/developer-anuragsingh/wp-multipurpose-parent-theme).

## Key Principles
- **0 KB PHP:** No \`functions.php\`. Stylesheet and asset cascading are handled natively by WordPress 6.8+ Full-Site Editing.
- **Design Tokens First:** All visual customisations (colors, fonts, spacing) live in \`theme.json\` and cascade into the parent theme.
- **Pure HTML Templates:** Layout customisations are done by dropping \`.html\` files into \`templates/\` or \`parts/\`.

## Installation
1. Ensure the parent theme (\`atlas-theme\`) is installed in \`wp-content/themes/atlas-theme\`.
2. Copy this folder into \`wp-content/themes/${CHILD_SLUG}\`.
3. In WordPress Admin, navigate to **Appearance → Themes** and activate **${CHILD_NAME}**.
EOF

# Ensure zero PHP files were created
PHP_COUNT=$(find "${CHILD_DIR}" -name "*.php" | wc -l | tr -d ' ')
if [[ "${PHP_COUNT}" -ne 0 ]]; then
	echo "FATAL: PHP file detected in Zero-PHP child theme!" >&2
	exit 1
fi

echo "SUCCESS: Zero-PHP child theme '${CHILD_NAME}' successfully created!"
echo "Location: ${CHILD_DIR}"
