# 01. Analytics & Monitoring

**File:** `docs/09-monitoring-and-usage/01-analytics-and-monitoring.md`
**Module:** Monitoring & Usage

## 1. Principle
You can't call something production-ready if you can't tell when it breaks or slows down. Monitoring is observation, not surveillance — and it must respect the privacy and consent rules in `06-security-and-i18n/03-data-protection.md`.

## 2. Real-User Core Web Vitals (RUM)
* Measure LCP, INP, CLS from real visitors against the budget in `07-performance-and-qa/01-performance-budget.md`.
* Use the lightweight `web-vitals` library (or Site Kit) — reporting beacons must respect the JS budget and load without blocking.
* Consent-gate any RUM that attaches identifiers; anonymous, aggregate CWV collection is preferable and privacy-friendly.

## 3. Error Tracking
* Surface both server (PHP) and client (JS) errors to an aggregator the operator chooses (e.g. a Sentry-style service), integrated via hooks — not hardcoded.
* **Never send PII or request bodies** to an error tracker (ties to data-protection §6). Scrub payloads; send route, error type, and stack only.
* Client error reporting is consent-gated like any third-party beacon.

## 4. Uptime & Availability
* Operator-side concern, but the theme supports it: ensure a cheap, cacheable health route exists and that maintenance mode returns a correct `503` (so uptime monitors read status honestly, not a false `200`).

## 5. Analytics (traffic)
* The theme does NOT bundle a specific analytics vendor. It provides a consent-gated hook/slot so the operator can add their chosen, privacy-respecting analytics.
* Load analytics only after consent; self-hostable options preferred. Document the data flow for the operator's privacy policy.

## 6. Privacy Guardrails (apply to everything above)
* Consent before any non-essential tracking (`06-security-and-i18n/03-data-protection.md` §8).
* No PII in metrics, logs, or error payloads.
* All third-party beacons are opt-in and documented.

## 7. Verification Checklist
- [ ] RUM measures CWV against budget; anonymous/consent-gated.
- [ ] Error tracking wired via hooks; PII scrubbed from payloads.
- [ ] Health/status honest (503 on maintenance, not 200).
- [ ] Analytics is operator-pluggable and consent-gated, not bundled.
- [ ] No non-essential tracking fires before consent.
