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
<div data-wp-interactive="nexus-forms">
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
* Error text is translatable (`esc_html__( ..., 'nexus-theme' )`).

## 4. Backend Layer (Security / Source of Truth)
Every submission — form POST, AJAX, or Interactivity API store request — is re-validated server-side per `06-security-and-i18n/01-security-and-data.md`:
1. **Authorize:** `current_user_can()` where the action is privileged.
2. **Verify intent:** nonce / `X-WP-Nonce` (CSRF).
3. **Validate:** reject values outside the expected shape/range/allow-list — do not silently coerce. For REST, use the route's `args` schema with `validate_callback`.
4. **Sanitize:** `wp_unslash()` then type-specific sanitizers (`sanitize_text_field()`, `sanitize_email()`, `absint()`, `sanitize_textarea_field()`, …). For REST, use `sanitize_callback`.
5. **Escape on output** when any submitted value is later rendered.

The frontend rules in §3 and the backend rules here must agree on the *contract* (same required fields, same formats, same max lengths) so the two layers give consistent results — but they are implemented and enforced independently.

## 5. Spam & Abuse (non-negotiable for public forms)
Public forms (contact, donation, newsletter) add anti-abuse measures that do not harm accessibility:
* Honeypot field (hidden from users, bots fill it) and/or a timing check.
* Rate limiting on the server endpoint.
* Avoid CAPTCHAs that break a11y; if required, use an accessible, privacy-respecting option and consent-gate any third-party loader (see `06-security-and-i18n/03-data-protection.md` §8).

## 6. Verification Checklist (per form)
- [ ] Native HTML5 constraints present; JS used only where native can't suffice.
- [ ] Real-time feedback validates on blur, re-validates on input after first error, debounced for async checks.
- [ ] No Zod / JS validation library added; interactivity uses the Interactivity API only.
- [ ] `:user-invalid` styling; errors never color-only.
- [ ] Labels associated; errors in `aria-live` region with `aria-describedby` + `aria-invalid`; strings translatable.
- [ ] Server re-validates AND sanitizes every field independently of the frontend.
- [ ] Authorization + nonce + arg-schema validation on the endpoint.
- [ ] Spam protection (honeypot/timing/rate-limit) on public forms.
