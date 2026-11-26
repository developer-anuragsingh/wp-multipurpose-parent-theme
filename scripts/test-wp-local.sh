#!/usr/bin/env bash
#
# test-wp-local.sh — run the real-WordPress integration suite locally WITHOUT
# Docker or a MySQL server, using the SQLite database integration plugin that
# WordPress Studio ships with every site.
#
# The suite gets its OWN throw-away SQLite file in the system temp directory;
# your Studio site's database is never opened or modified.
#
# USAGE:   composer test:wp:local [phpunit args...]
#          WP_TESTS_SQLITE_PLUGIN=/path/to/sqlite-database-integration composer test:wp:local
#
# CI runs the same suite against MySQL (.github/workflows/ci.yml, job
# wp-integration); that run is authoritative.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${SCRIPT_DIR}/.."

PLUGIN="${WP_TESTS_SQLITE_PLUGIN:-}"
if [[ -z "${PLUGIN}" ]]; then
	for base in "${HOME}/Studio" "${HOME}/Developer" "${HOME}/Sites"; do
		[[ -d "${base}" ]] || continue
		PLUGIN="$(find "${base}" -maxdepth 5 -type d -path '*wp-content/mu-plugins/sqlite-database-integration' 2>/dev/null | head -1)"
		[[ -n "${PLUGIN}" ]] && break
	done
fi

if [[ -z "${PLUGIN}" || ! -f "${PLUGIN}/db.copy" ]]; then
	echo "Could not find the SQLite integration plugin (sqlite-database-integration)." >&2
	echo "Create any site in WordPress Studio, or set WP_TESTS_SQLITE_PLUGIN to the plugin folder." >&2
	exit 1
fi

echo "Using SQLite integration from: ${PLUGIN}"
WP_TESTS_SQLITE_PLUGIN="${PLUGIN}" exec vendor/bin/phpunit -c phpunit-wp.xml.dist "$@"
