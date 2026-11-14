# 05. Forms & Input Validation

**File:** `docs/02-frontend-and-styles/05-forms-and-validation.md`
**Module:** Frontend & Styles

## 1. The Core Principle: Two Layers, Two Purposes
Validation happens on **both** the frontend and the backend, but the two serve different jobs and are never substitutes for each other:

* **Frontend validation = UX.** It exists only to give the user fast, friendly feedback while filling a field. It can be bypassed trivially — disabled JavaScript, edited DOM, direct REST calls, `curl`. It carries **zero** security weight.
* **Backend validation = security.** The server is the source of truth. It MUST re-validate and sanitize every incoming value as hostile input, regardless of what the frontend already checked.

> **Hard rule:** The server never trusts the client. Any field the frontend validates, the backend validates again. Skipping server-side validation because "the form already checks it" is a vulnerability.

## 2. No JavaScript Validation Library (No Zod)
This theme does **not** use Zod, Yup, or any JS schema/validation library.
* **Why:** these are TypeScript/JS libraries that ship a client bundle, directly violating the Zero-KB JS baseline (`agent-rules.md` §4) and the performance budget (`07-performance-and-qa/01-performance-budget.md`). In an FSE/PHP theme there is no build-time typed contract between a block-markup form and a JS schema, so the library buys little and costs bundle weight.
* **Instead:** the frontend uses native HTML5 constraints → the Interactivity API for richer feedback → modern CSS for styling. The backend uses WordPress's own validation/sanitization APIs. No third-party validation dependency on either layer.

## 3. Frontend Layer (UX / Real-Time Feedback)
Build up in order of cost — reach for JS only when the native layer can't do the job.

### A. HTML5 Constraints First (0 KB JS)
Native attributes give instant browser feedback with no JavaScript:
`required`, `type="email"` / `type="url"` / `type="tel"`, `pattern`, `minlength` / `maxlength`, `min` / `max` / `step`, `inputmode` for mobile keyboards.

### B. Instant-Feedback Behavior (Interactivity API)
When you need live, inline feedback beyond native constraints, use the Interactivity API — not a JS framework.
* **Timing:** validate a field on `blur` (first interaction), then re-validate on `input` once it has already errored, so the message clears as the user fixes it.
* **Debounce async/expensive checks only:** server-dependent checks (e.g. "email already registered") are debounced by ~300 ms so a request fires only after the user pauses typing — this cuts needless requests and server load. Cheap *local* checks (required, format, length) run **instantly** with no debounce; delaying them just makes feedback feel laggy. Throttle (not debounce) continuous events like scroll/resize. See `07-performance-and-qa/05-coding-standards.md` §6.
* **Do not** show errors on untouched fields or on every keystroke before first blur — that is noisy and hostile.
* Directives: `data-wp-on--blur`, `data-wp-on--input`, and `data-wp-bind--aria-invalid` driving state.

```html
<div data-wp-interactive="atlas-forms">
  <label for="email">Email</label>
  <input
    id="email" name="email" type="email" required
    aria-describedby="email-error"
    data-wp-on--blur="actions.validateEmail"
    data-wp-on--input="actions.revalidateIfErrored"
    data-wp-bind--aria-invalid="state.emailInvalid" />
  <p id="email-error" role="alert" aria-live="polite"
     data-wp-text="state.emailError"></p>
</div>
```

### C. CSS-Only Styling (0 KB JS)
Style validity states with native selectors so visuals need no script:
* `:user-invalid` / `:user-valid` — styles a field only after the user has interacted (avoids yelling at empty forms).
* `:has()` — style the field wrapper based on the control's validity state.

### D. Accessibility (mandatory)
Error feedback MUST follow `02-frontend-and-styles/04-accessibility.md` §6:
* `<label>` programmatically associated with every input (not placeholder-only).
* Errors announced via an `aria-live` region and linked with `aria-describedby`; the field gets `aria-invalid="true"`.
* Never signal an error by color alone — pair with text and/or icon.
* Error text is translatable (`esc_html__( ..., 'atlas-theme' )`).

## 4. Backend Layer (Security / Source of Truth)
Every submission — form POST, AJAX, or Interactivity API store request — is re-validated server-side per `06-security-and-i18n/01-security-and-data.md`:
1. **Authorize:** `current_user_can()` where the action is privileged.
2. **Verify intent:** nonce / `X-WP-Nonce` (CSRF) for logged-in actions. Public anonymous forms (contact) use a stateless signed form token instead, because a nonce proves nothing for a visitor with no session (see §5).
3. **Validate:** reject values outside the expected shape/range/allow-list — do not silently coerce. For REST, use the route's `args` schema with `validate_callback`.
4. **Sanitize:** `wp_unslash()` then type-specific sanitizers (`sanitize_text_field()`, `sanitize_email()`, `absint()`, `sanitize_textarea_field()`, …). For REST, use `sanitize_callback`.
5. **Escape on output** when any submitted value is later rendered.

The frontend rules in §3 and the backend rules here must agree on the *contract* (same required fields, same formats, same max lengths) so the two layers give consistent results — but they are implemented and enforced independently.

## 5. Spam & Abuse (non-negotiable for public forms)
Public forms (contact, donation, newsletter) add anti-abuse measures that do not harm accessibility. The contact form (`Atlas\Contact_Form`) is the reference implementation, enforced in this order and failing early:
1. **Signed form token** (`atlas_contact_ts` field) — a stateless `<issued-at>.<HMAC-SHA256>` token. A missing, forged, back-dated or expired token returns `403`. Lifetime 12 h, tune with `atlas_contact_token_ttl`. The JS refreshes it from `GET atlas/v1/contact/token` on load so page caches cannot make it stale.
2. **Honeypot** (`atlas_contact_hp`) — must stay empty; bots get a generic `200` with no mail.
3. **Timing** — the *signed* token age must be at least 3 s; a faster submission gets a generic `200` with no mail. Because the age is inside the HMAC, a bot cannot fake it.
4. **Rate limit** — per client IP (default 5 per 10 min, `atlas_contact_form_rate_limit`), `429` + `Retry-After`. The counter is atomic on a persistent object cache (Redis/Memcached) and best-effort on transients. The IP is keyed-hashed (never stored or reversible). An empty or invalid IP falls into one shared bucket (it is **not** a free pass). Behind a proxy/CDN, supply the real IP via `atlas_client_ip`.
5. **Validate + sanitize** every field server-side.
6. **Mail hardening** — the submitter name is stripped of CR/LF/NUL, `<>",;:@` before it reaches the subject or `Reply-To` (header-injection and display-name spoofing), and the email must pass `is_email()` with no whitespace.

Other rules:
* Avoid CAPTCHAs that break a11y; if required, use an accessible, privacy-respecting option and consent-gate any third-party loader (see `06-security-and-i18n/03-data-protection.md` §8).
* The enhanced request is sent with `credentials: 'omit'` and no `X-WP-Nonce`, so a stale core REST nonce can never cause a spurious `403`.

## 6. Verification Checklist (per form)
- [ ] Native HTML5 constraints present; JS used only where native can't suffice.
- [ ] Real-time feedback validates on blur, re-validates on input after first error, debounced for async checks.
- [ ] No Zod / JS validation library added; interactivity uses the Interactivity API only.
- [ ] `:user-invalid` styling; errors never color-only.
- [ ] Labels associated; errors in `aria-live` region with `aria-describedby` + `aria-invalid`; strings translatable.
- [ ] Server re-validates AND sanitizes every field independently of the frontend.
- [ ] Authorization + nonce + arg-schema validation on the endpoint.
- [ ] Spam protection (signed token/honeypot/timing/rate-limit) on public forms; mail headers built only from header-safe values.
