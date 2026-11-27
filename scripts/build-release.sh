#!/usr/bin/env bash
#
# build-release.sh — produce an installable, production-ready theme zip.
#
# WHY: the shippable theme lives in theme/, but its JS/CSS bundles in
# theme/build/ are generated and gitignored. Zipping the folder by hand risks
# shipping a theme with no scripts (stale or missing build) or with dev files.
# This script always builds, verifies, and packages deterministically.
#
# USAGE:
#   scripts/build-release.sh              # build assets, then package
#   scripts/build-release.sh --skip-build # package the existing build/
#
# OUTPUT: dist/atlas-theme-<version>.zip (+ .sha256). The zip's top-level
# folder is atlas-theme/ (must equal the text domain / theme slug).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
THEME_SRC="${REPO_ROOT}/theme"
SLUG="atlas-theme"
SKIP_BUILD=0
[[ "${1:-}" == "--skip-build" ]] && SKIP_BUILD=1

cd "${REPO_ROOT}"

# --- 1. Version sources must agree (release-please keeps them in sync). -------
STYLE_VER="$(sed -n 's/^Version:[[:space:]]*//p' "${THEME_SRC}/style.css" | head -1 | tr -d '[:space:]')"
CONST_VER="$(sed -n "s/.*define( 'ATLAS_THEME_VERSION', '\([^']*\)'.*/\1/p" "${THEME_SRC}/functions.php" | head -1)"
PKG_VER="$(sed -n 's/^[[:space:]]*"version":[[:space:]]*"\([^"]*\)".*/\1/p' package.json | head -1)"
if [[ -z "${STYLE_VER}" || "${STYLE_VER}" != "${CONST_VER}" || "${STYLE_VER}" != "${PKG_VER}" ]]; then
	echo "ERROR: version mismatch  style.css='${STYLE_VER}'  functions.php='${CONST_VER}'  package.json='${PKG_VER}'" >&2
	exit 1
fi
VERSION="${STYLE_VER}"
# stamp_stable_tag <readme.txt> <version>: set the WordPress.org "Stable tag"
# of a STAGED readme to the release version. release-please bumps style.css but
# cannot annotate readme.txt (an annotation would show up in the parsed
# header), so the zip is stamped here and never ships a stale Stable tag.
stamp_stable_tag() {
	local readme="$1" ver="$2"
	grep -q '^Stable tag:' "${readme}" || { echo "ERROR: ${readme} has no 'Stable tag:' line" >&2; exit 1; }
	sed -i.bak "s/^Stable tag:.*/Stable tag: ${ver}/" "${readme}" && rm -f "${readme}.bak"
	grep -qx "Stable tag: ${ver}" "${readme}" || { echo "ERROR: could not stamp Stable tag in ${readme}" >&2; exit 1; }
}

# --- 2. Build the generated assets. ------------------------------------------
if [[ "${SKIP_BUILD}" -eq 0 ]]; then
	npm run build
fi
for f in theme-mode navigation copy contact-form ui-states infinite-scroll; do
	if [[ ! -f "${THEME_SRC}/build/${f}.js" || ! -f "${THEME_SRC}/build/${f}.asset.php" ]]; then
		echo "ERROR: missing build output theme/build/${f}.js(.asset.php). Run without --skip-build." >&2
		exit 1
	fi
done

# --- 3. Stage a clean copy (no dev/OS junk, no source files). -----------------
STAGE="$(mktemp -d)"
trap 'rm -rf "${STAGE}"' EXIT
mkdir -p "${STAGE}/${SLUG}"
rsync -a \
	--exclude='.DS_Store' --exclude='Thumbs.db' --exclude='*.map' \
	--exclude='.gitignore' --exclude='src/' --exclude='node_modules/' \
	"${THEME_SRC}/" "${STAGE}/${SLUG}/"
stamp_stable_tag "${STAGE}/${SLUG}/readme.txt" "${VERSION}"

# --- 4. Zip deterministically and checksum. -----------------------------------
mkdir -p dist
OUT="${REPO_ROOT}/dist/${SLUG}-${VERSION}.zip"
rm -f "${OUT}" "${OUT}.sha256"
(cd "${STAGE}" && find "${SLUG}" -type f | LC_ALL=C sort | zip -X -q "${OUT}" -@)
(cd dist && shasum -a 256 "${SLUG}-${VERSION}.zip" > "${SLUG}-${VERSION}.zip.sha256")

# --- 5. Verify the archive contents. ------------------------------------------
LIST="$(unzip -Z1 "${OUT}")"
for required in "${SLUG}/style.css" "${SLUG}/screenshot.png" "${SLUG}/readme.txt" "${SLUG}/theme.json" "${SLUG}/functions.php" "${SLUG}/build/contact-form.js" "${SLUG}/assets/css/interactivity.css"; do
	grep -qx "${required}" <<<"${LIST}" || { echo "ERROR: ${required} missing from zip" >&2; exit 1; }
done
if grep -E '(^|/)(\.DS_Store|node_modules|src)(/|$)|\.map$' <<<"${LIST}" >/dev/null; then
	echo "ERROR: dev/OS/source files leaked into the zip" >&2
	exit 1
fi

echo "Built ${OUT} ($(du -h "${OUT}" | cut -f1), $(wc -l <<<"${LIST}" | tr -d ' ') files)"
cat "${OUT}.sha256"

# --- 6. Package Zero-PHP Child Themes in child-themes/ if present. ------------
if [[ -d "${REPO_ROOT}/child-themes" ]]; then
	for CHILD_DIR in "${REPO_ROOT}/child-themes"/*; do
		if [[ -d "${CHILD_DIR}" && -f "${CHILD_DIR}/style.css" ]]; then
			CHILD_SLUG="$(basename "${CHILD_DIR}")"
			CHILD_VER="$(sed -n 's/^Version:[[:space:]]*//p' "${CHILD_DIR}/style.css" | head -1 | tr -d '[:space:]')"
			if [[ -z "${CHILD_VER}" ]]; then
				CHILD_VER="1.0.0"
			fi
			CHILD_OUT="${REPO_ROOT}/dist/${CHILD_SLUG}-${CHILD_VER}.zip"
			rm -f "${CHILD_OUT}" "${CHILD_OUT}.sha256"

			CHILD_STAGE="$(mktemp -d)"
			mkdir -p "${CHILD_STAGE}/${CHILD_SLUG}"
			rsync -a \
				--exclude='.DS_Store' --exclude='Thumbs.db' --exclude='*.map' \
				--exclude='.gitignore' --exclude='node_modules/' \
				"${CHILD_DIR}/" "${CHILD_STAGE}/${CHILD_SLUG}/"
			stamp_stable_tag "${CHILD_STAGE}/${CHILD_SLUG}/readme.txt" "${CHILD_VER}"

			(cd "${CHILD_STAGE}" && find "${CHILD_SLUG}" -type f | LC_ALL=C sort | zip -X -q "${CHILD_OUT}" -@)
			(cd dist && shasum -a 256 "${CHILD_SLUG}-${CHILD_VER}.zip" > "${CHILD_SLUG}-${CHILD_VER}.zip.sha256")
			rm -rf "${CHILD_STAGE}"

			# Verify child archive
			CHILD_LIST="$(unzip -Z1 "${CHILD_OUT}")"
			for req_child in "${CHILD_SLUG}/style.css" "${CHILD_SLUG}/theme.json" "${CHILD_SLUG}/readme.txt" "${CHILD_SLUG}/screenshot.png"; do
				grep -qx "${req_child}" <<<"${CHILD_LIST}" || { echo "ERROR: ${req_child} missing from child zip" >&2; exit 1; }
			done

			echo "Built Child Theme ${CHILD_OUT} ($(du -h "${CHILD_OUT}" | cut -f1), $(wc -l <<<"${CHILD_LIST}" | tr -d ' ') files)"
			cat "${CHILD_OUT}.sha256"
		fi
	done
fi
