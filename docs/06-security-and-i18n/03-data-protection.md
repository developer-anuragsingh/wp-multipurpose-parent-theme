# 03. Data Protection & Privacy Governance

**File:** `docs/06-security-and-i18n/03-data-protection.md`
**Module:** Security & i18n

> This document covers **data governance** — how user data is classified, stored, transported, retained, and shared. It complements `01-security-and-data.md`, which covers the **code-level primitives** (authorization, sanitization, escaping, CSRF, CSP). Both apply together: correct code that mishandles data governance is still a breach, and good governance with insecure code is still a breach.

## 1. PII Classification
Not all data carries equal risk. Classify every field the theme reads, stores, or transmits:
* **Sensitive PII:** payment data, government IDs, precise location, health, credentials. Never store in theme-owned storage; delegate to the appropriate processor.
* **Standard PII:** name, email, postal address, phone, IP address, order history, donation records.
* **Non-personal:** aggregate counts, anonymized analytics, published content.

Treat anything in the top two tiers as protected: it must be authorized to access, encrypted in transit, minimized, and retention-bound.

## 2. Data Minimization
* Collect only what a feature genuinely needs. If a field is not used, do not request or store it.
* Do not log PII (see §6). Do not persist request bodies "just in case."
* Prefer transient, request-scoped handling over durable storage. If you must store, store the minimum and set a retention period (§7).

## 3. Transport & At-Rest Security
* **HTTPS everywhere:** the theme assumes TLS. Never submit forms, load the Interactivity API store, or hit REST endpoints over plain HTTP. Recommend `HSTS` at the server/host layer.
* **No plaintext secrets at rest:** never store API keys, tokens, or credentials in `wp_options`, post meta, `theme.json`, or committed files. Sensitive stored values that must round-trip should be encrypted, not stored raw.
* **Cookies:** any theme-set cookie carrying session or preference data uses `Secure`, `HttpOnly` (unless it must be read by JS), and `SameSite=Lax` or `Strict`. The dark-mode preference in `localStorage` is non-personal and exempt.

## 4. Secrets Management
* **Never hardcode** API keys, payment credentials, SMTP passwords, or tokens in PHP, JS, templates, or `theme.json`.
* **Never commit secrets** to the repository. Use environment variables or `wp-config.php` constants, kept out of version control.
* **Never expose server secrets to the client:** no secret may appear in enqueued JS, inline scripts, data attributes, or the Interactivity API client state. Keep them server-side behind REST endpoints with capability checks.

## 5. Payment & PCI Boundary (WooCommerce)
* The theme MUST NOT touch, store, log, or transmit raw cardholder data. All payment capture is delegated to a PCI-DSS-compliant gateway/processor via the official WooCommerce payment blocks and gateway plugins.
* The theme's responsibility ends at rendering the gateway's secure fields/iframe; it never proxies card data through its own code or endpoints.
* Checkout routes are excluded from Speculation Rules prefetch (see `04-modern-wp-features/02-speculation-rules.md`) to avoid background mutations.

## 6. Logging & Error Hygiene
* **No PII in logs:** never write names, emails, addresses, tokens, or full request payloads to error logs or custom logs.
* **No debug output in production:** `WP_DEBUG` and `WP_DEBUG_DISPLAY` are off in production. Never echo stack traces, SQL, or `var_dump()`/`print_r()` to the response.
* **Fail closed:** on error, show a generic user-facing message; log only non-sensitive diagnostic context server-side.

## 7. Retention & Data-Subject Rights
* Define and document a retention period for every category of stored personal data (e.g. contact-form submissions, donation records). Purge beyond the window.
* Integrate WordPress core privacy tooling for any personal data the theme stores:
  - **Export:** register with the personal-data exporter so a user's data is included in `Tools → Export Personal Data`.
  - **Erasure:** register with the personal-data eraser so `Tools → Erase Personal Data` removes it.
* Provide a clear path to a published privacy policy; link it from forms that collect PII.

## 8. Consent & Third-Party Data Sharing
* **Consent before non-essential collection:** analytics, marketing cookies, and third-party embeds load only after explicit consent.
* **Local-first assets:** self-host fonts via the Font Library API — no third-party font CDN beacons (see `01-architecture/01-fse-overview.md` §6).
* **Inventory outbound data:** any feature that sends user data off-site (analytics, embeds, external APIs) must be documented and consent-gated. The GEO endpoints (`llms.txt`, `?format=md`) must expose only already-public published content, never personal data.

## 9. File Uploads (where a niche accepts them)
* Validate type by content, not just extension; restrict to an allow-list of MIME types.
* Enforce size limits; store outside web-executable paths where possible; never allow executable types (`.php`, `.phtml`, etc.).
* Run uploads through `wp_handle_upload()` / core media APIs rather than custom move logic, and apply capability checks (§ `01-security-and-data.md`).

## 10. Applicable Compliance Regimes
The theme is built for a bilingual English/Hindi audience and global clients, so design to the strictest applicable standard:
* **GDPR** (EU/EEA) — lawful basis, consent, export/erasure, breach notification.
* **India DPDP Act, 2023** — consent, purpose limitation, data-principal rights (relevant given the Hindi/`hi_IN` focus).
* **CCPA/CPRA** (California) — notice, opt-out of sale/share, deletion rights.

Compliance is a shared responsibility with the site operator; the theme's job is to provide the hooks, consent gating, and data-handling defaults that make compliance achievable, and to avoid patterns that make it impossible.

## 11. Verification Checklist
- [ ] No secrets in repo, `theme.json`, or client-side output.
- [ ] All PII-collecting flows are over HTTPS, authorized, and consent-gated.
- [ ] No PII in logs; `WP_DEBUG_DISPLAY` off in production.
- [ ] Theme stores no raw cardholder data; payments delegated to a PCI gateway.
- [ ] Retention periods defined; core export/erasure hooks wired for stored PII.
- [ ] File uploads (if any) type/size-validated and stored safely.
- [ ] Privacy policy linked from PII-collecting forms.
