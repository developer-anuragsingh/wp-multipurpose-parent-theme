#!/usr/bin/env bash
#
# lighthouse.sh — measure a RUNNING site with Lighthouse and fail on regressions.
#
# Lighthouse needs a real server (it measures PHP + HTML + assets together), so
# it is a manual / pre-release gate rather than a CI job.
#
# USAGE:  scripts/lighthouse.sh [base-url] [path ...]
#   default: http://localhost:8885  and path "/"
#   e.g.   scripts/lighthouse.sh https://staging.example.com / /contact/
#
# Thresholds (override via env): MIN_PERFORMANCE=90 MIN_ACCESSIBILITY=100
#   MIN_BEST_PRACTICES=95 MIN_SEO=95 MAX_TBT=200 MAX_LCP=2500 MAX_CLS=0.15
# MAX_CLS is 0.15, not the 0.10 "good" line, because of a documented, measured
# mobile shift (inline nav -> drawer on hydration); see
# docs/07-performance-and-qa/01-performance-budget.md §6. Tighten it once fixed.
#
# Lighthouse is run through npx at a pinned version (not a project dependency).
set -uo pipefail

BASE="${1:-http://localhost:8885}"; BASE="${BASE%/}"
shift || true
PATHS=("$@"); [[ ${#PATHS[@]} -eq 0 ]] && PATHS=("/")

LH_VERSION="13.6.0"
export CHROME_PATH="${CHROME_PATH:-/Applications/Google Chrome.app/Contents/MacOS/Google Chrome}"
OUT="$(mktemp -d)"; trap 'rm -rf "${OUT}"' EXIT
FAIL=0

for p in "${PATHS[@]}"; do
	URL="${BASE}${p}"
	echo "Lighthouse ${LH_VERSION}: ${URL}  (mobile emulation, simulated 4x CPU throttle)"
	if ! npx --yes "lighthouse@${LH_VERSION}" "${URL}" --quiet \
		--chrome-flags="--headless=new --no-sandbox" \
		--only-categories=performance,accessibility,best-practices,seo \
		--output=json --output-path="${OUT}/r.json" >/dev/null 2>&1; then
		echo "  [FAIL] Lighthouse could not run (is Chrome installed and the site reachable?)"; FAIL=$((FAIL + 1)); continue
	fi
	python3 - "${OUT}/r.json" <<'PY' || FAIL=$((FAIL + 1))
import json, os, sys
d = json.load(open(sys.argv[1])); a = d["audits"]; c = d["categories"]
score = lambda k: round(c[k]["score"] * 100)
env = os.environ.get
checks = [
    ("performance",    score("performance"),    float(env("MIN_PERFORMANCE", 90)), ">="),
    ("accessibility",  score("accessibility"),  float(env("MIN_ACCESSIBILITY", 100)), ">="),
    ("best-practices", score("best-practices"), float(env("MIN_BEST_PRACTICES", 95)), ">="),
    ("seo",            score("seo"),            float(env("MIN_SEO", 95)), ">="),
    ("TBT ms",         a["total-blocking-time"]["numericValue"],      float(env("MAX_TBT", 200)), "<="),
    ("LCP ms",         a["largest-contentful-paint"]["numericValue"], float(env("MAX_LCP", 2500)), "<="),
    ("CLS",            a["cumulative-layout-shift"]["numericValue"],  float(env("MAX_CLS", 0.15)), "<="),
]
bad = 0
for name, val, limit, op in checks:
    ok = val >= limit if op == ">=" else val <= limit
    bad += 0 if ok else 1
    print(f"  [{'PASS' if ok else 'FAIL'}] {name:<15} {val:>9.3f}  (need {op} {limit:g})")
sys.exit(1 if bad else 0)
PY
done

echo
[[ "${FAIL}" -gt 0 ]] && { echo "LIGHTHOUSE GATE FAILED (${FAIL})"; exit 1; }
echo "LIGHTHOUSE GATE PASSED"
