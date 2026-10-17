#!/usr/bin/env bash
#
# link-to-studio.sh — symlink this theme into a WordPress Studio (or any local
# WordPress) install so you can edit in the git repo and preview live.
#
# WHY: the shippable theme lives in this repo's `theme/` subdirectory, but
# WordPress loads themes from <wp-install>/wp-content/themes/. A symlink makes
# the Studio site read the repo's theme/ directly — no copying, no sync. Edit
# in git, refresh the site, changes appear instantly.
#
# USAGE:
#   scripts/link-to-studio.sh /absolute/path/to/<studio-site>
#
# where <studio-site> is the "Local path" shown in the Studio app for your site
# (the folder that contains wp-load.php, wp-admin/, wp-content/). The script
# links theme/ as wp-content/themes/atlas-theme (slug must match the theme's
# text domain 'atlas-theme').
#
# Re-running is safe: it refuses to clobber a real directory and only replaces
# an existing symlink of the same name.

set -euo pipefail

# --- Resolve the theme source (this repo's theme/ dir), independent of CWD ---
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
THEME_SRC="${REPO_ROOT}/theme"

# The slug MUST equal the theme text domain so WordPress resolves assets/i18n.
THEME_SLUG="atlas-theme"

# --- Validate arguments -------------------------------------------------------
if [[ $# -ne 1 ]]; then
	echo "Usage: $0 /absolute/path/to/<studio-site>" >&2
	echo "       (<studio-site> is the 'Local path' from the Studio app)" >&2
	exit 2
fi

SITE_PATH="$1"

# --- Sanity checks ------------------------------------------------------------
if [[ ! -f "${THEME_SRC}/style.css" ]]; then
	echo "ERROR: theme source not found or missing style.css at: ${THEME_SRC}" >&2
	exit 1
fi

if [[ ! -f "${SITE_PATH}/wp-load.php" || ! -d "${SITE_PATH}/wp-content/themes" ]]; then
	echo "ERROR: '${SITE_PATH}' does not look like a WordPress install." >&2
	echo "       Expected wp-load.php and wp-content/themes/ inside it." >&2
	echo "       Use the exact 'Local path' shown in the Studio app." >&2
	exit 1
fi

THEMES_DIR="${SITE_PATH}/wp-content/themes"
LINK="${THEMES_DIR}/${THEME_SLUG}"

# --- Create / refresh the symlink safely -------------------------------------
if [[ -L "${LINK}" ]]; then
	echo "Existing symlink found; replacing it: ${LINK}"
	rm "${LINK}"
elif [[ -e "${LINK}" ]]; then
	echo "ERROR: '${LINK}' exists and is NOT a symlink (a real file/dir)." >&2
	echo "       Refusing to delete it. Move it aside and re-run." >&2
	exit 1
fi

ln -s "${THEME_SRC}" "${LINK}"

echo "Linked: ${LINK} -> ${THEME_SRC}"
echo "Verifying WordPress can read the theme header..."
grep -m1 "Theme Name" "${LINK}/style.css" || {
	echo "WARNING: could not read Theme Name through the link." >&2
	exit 1
}

echo
echo "Done. Next: open the Studio site -> WP Admin -> Appearance -> Themes"
echo "      -> activate 'Atlas Multipurpose'. Edit in the git repo; the site"
echo "      reflects changes live (refresh the page)."
