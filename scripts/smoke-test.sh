#!/usr/bin/env bash
#
# smoke-test.sh — verify the security-critical behaviour of a RUNNING site.
#
# The PHP unit tests use WordPress doubles; this script checks the real thing
# end to end (headers actually sent, REST routes actually registered).
#
# USAGE:  scripts/smoke-test.sh [base-url]      (default http://localhost:8885)
# Read-only except one deliberately-invalid POST that must be rejected (no mail).
set -uo pipefail

BASE="${1:-http://localhost:8885}"
BASE="${BASE%/}"
FAIL=0
pass() { echo "  [PASS] $1"; }
fail() { echo "  [FAIL] $1"; FAIL=$((FAIL + 1)); }
check() { # check "<name>" <command...>
	local name="$1"; shift
	if "$@" >/dev/null 2>&1; then pass "${name}"; else fail "${name}"; fi
}

echo "Smoke test: ${BASE}"

HEADERS="$(curl -sIL -m 30 "${BASE}/" | tr -d '\r')"
has_header() { grep -qi "^$1:" <<<"${HEADERS}"; }

check "Home page responds 200"                         grep -q "^HTTP/.* 200" <<<"${HEADERS}"
check "X-Content-Type-Options: nosniff"                grep -qi "^x-content-type-options: nosniff" <<<"${HEADERS}"
check "X-Frame-Options present"                        has_header x-frame-options
check "Referrer-Policy present"                        has_header referrer-policy
check "Permissions-Policy present"                     has_header permissions-policy
check "Content-Security-Policy (or Report-Only) sent"  grep -qiE "^content-security-policy(-report-only)?:" <<<"${HEADERS}"
check "CSP forbids plugins (object-src 'none')"        grep -qi "object-src 'none'" <<<"${HEADERS}"
check "CSP never allows unsafe-inline scripts"         bash -c "! grep -i '^content-security-policy' <<<\"\$1\" | grep -Eio \"script-src[^;]*\" | grep -q unsafe-inline" _ "${HEADERS}"

PAGE="$(curl -sL -m 30 "${BASE}/")"
check "Page has exactly one canonical link"            bash -c '[[ "$(grep -o "rel=\"canonical\"" <<<"$1" | wc -l | tr -d " ")" == "1" ]]' _ "${PAGE}"
check "JSON-LD block is present and valid JSON"        bash -c 'python3 -c "import json,re,sys; m=re.search(r\"<script type=.application/ld\\+json.>(.*?)</script>\", sys.stdin.read(), re.S); json.loads(m.group(1))" <<<"$1"' _ "${PAGE}"
check "No unescaped '<' inside the JSON-LD block"      bash -c 'python3 -c "import re,sys; m=re.search(r\"<script type=.application/ld\\+json.>(.*?)</script>\", sys.stdin.read(), re.S); sys.exit(1 if \"<\" in m.group(1) else 0)" <<<"$1"' _ "${PAGE}"

TOKEN_JSON="$(curl -s -m 30 -D /tmp/atlas-smoke-h "${BASE}/wp-json/atlas/v1/contact/token")"
check "Contact token endpoint returns a token"         grep -Eq '"token":"[0-9]+\.[a-f0-9]{64}"' <<<"${TOKEN_JSON}"
check "Contact token endpoint is no-store"             grep -qi "cache-control:.*no-store" /tmp/atlas-smoke-h
rm -f /tmp/atlas-smoke-h

CODE="$(curl -s -m 30 -o /dev/null -w '%{http_code}' -X POST "${BASE}/wp-json/atlas/v1/contact" \
	-H 'Content-Type: application/json' -H 'X-Atlas-Enhanced: 1' \
	-d '{"name":"Smoke","email":"smoke@example.com","message":"Smoke test, missing token."}')"
[[ "${CODE}" == "403" ]] && pass "Submission without a form token is rejected (403)" || fail "Submission without a form token returned ${CODE}, expected 403"

CODE="$(curl -s -m 30 -o /dev/null -w '%{http_code}' -X POST "${BASE}/wp-json/atlas/v1/contact" \
	-H 'Content-Type: application/json' -H 'X-Atlas-Enhanced: 1' \
	-d '{"name":"Smoke","email":"smoke@example.com","message":"Forged token.","atlas_contact_ts":"1700000000.0000000000000000000000000000000000000000000000000000000000000000"}')"
[[ "${CODE}" == "403" ]] && pass "Forged form token is rejected (403)" || fail "Forged form token returned ${CODE}, expected 403"

echo
if [[ "${FAIL}" -gt 0 ]]; then echo "SMOKE TEST FAILED (${FAIL})"; exit 1; fi
echo "SMOKE TEST PASSED"
