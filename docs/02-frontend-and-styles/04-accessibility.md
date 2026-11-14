# 04. Accessibility (a11y) Standards

**File:** `docs/02-frontend-and-styles/04-accessibility.md`
**Module:** Frontend & Styles

## 1. Target & Scope
The theme targets **WCAG 2.2 Level AA** across all shipped templates, parts, and block patterns. Accessibility is a release gate, not a polish step: a template that fails AA is not production-ready.

> Full WCAG conformance cannot be proven by automated tooling alone. Automated checks (axe, Lighthouse) catch roughly a third of issues; the remainder requires manual testing with assistive technology (screen readers, keyboard-only navigation) and expert review. Claims of "WCAG AA compliant" must be backed by that manual testing, not just a passing scanner.

## 2. Color & Contrast
The `theme.json` palette must satisfy AA contrast ratios for every intended foreground/background pairing.
* **Body text:** ≥ 4.5:1 against its background.
* **Large text (≥ 24px, or ≥ 19px bold) and UI components / focus indicators:** ≥ 3:1.
* **Validate every pairing**, including dark-mode overrides (`data-theme="dark"`). Verify `primary` (`#2563eb`), `accent` (`#f97316`), `dark`, and `light` against each surface they can land on. Orange accent on white in particular fails for normal text and must only be used for large text or non-text UI.
* Never convey information by color alone — pair with text, icon, or shape.
* **Role-based presets (not raw brand colours) where contrast differs by surface:**
  - `accent-text` (`#c2410c`, 4.95:1 on the light surface) is the accent colour **for text**. The brand `accent` (`#f97316`) is only 2.68:1 on `light`, so never use `has-accent-color` for text on light surfaces. In dark mode `accent-text` re-points to `accent` (6.37:1 on navy).
  - `surface` is the card/section background: white in light mode, `#1e293b` in dark mode. Never use `white` as a surface behind `dark` text: `white` is not re-pointed in dark mode (it is the label colour on primary buttons), so a white card with `dark` text became near-white on white (1.04:1).
  - Links: `primary` (`#2563eb`) is 3.45:1 on the dark surface, so in dark mode links use `#60a5fa` (7.02:1). Buttons keep primary background with white text (5.17:1).
* `tests/test-colors.php` (fast PHP suite) enforces that every colour preset referenced by patterns/parts/templates exists in `theme.json` and that the pairs above meet AA in **both** modes, reading dark overrides straight from `interactivity.css`.

## 3. Keyboard & Focus
* Every interactive control is reachable and operable by keyboard, in a logical tab order.
* A visible focus indicator is mandatory. Use `:focus-visible` and never remove outlines without an equivalent replacement.
* **Skip link:** a "Skip to content" link is the first focusable element in `parts/header.html`.
* Interactivity API widgets (nav drawer, dropdowns) must implement focus management: move focus into an opened dialog/drawer, trap it while open, restore it to the trigger on close, and close on `Escape`.
* **Nav drawer toggle semantics:** it is a link with `role="button"`, `aria-controls`, and `aria-expanded` (bound to state once hydrated). Enter activates a link natively; Space does not, so `navigation.js` adds it (and prevents page scroll) to match button behaviour. Before hydration (or if the module fails) only Enter works, which is acceptable for the fallback. The closed panel is `visibility:hidden`, so it is out of the tab order and the accessibility tree; the opened panel gets a visible "Close menu" link.

## 4. ARIA & Semantics
* Prefer native semantic HTML over ARIA. Use ARIA only to fill gaps.
* State changes driven by the Interactivity API must update the matching ARIA attribute (`aria-expanded`, `aria-pressed`, `aria-selected`, `aria-hidden`) via `data-wp-bind--*`.
* One `<main>` landmark per template; use `<nav>`, `<header>`, `<footer>` landmarks; headings form a single logical outline (one `h1`, no skipped levels).
* Every image conveys meaning through `alt` text; decorative images use empty `alt=""`.

## 5. Motion & Animation
* All animation (nav drawer transitions, theme switching, scroll effects) MUST honour `prefers-reduced-motion: reduce` and drop to a non-animated state.

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

## 6. Forms
* Every input has a programmatically associated `<label>` (not placeholder-only).
* Errors are announced (`aria-live`), tied to the field (`aria-describedby`), and not signalled by color alone.
* Required fields use `required` / `aria-required`, not just an asterisk.

## 7. RTL & Internationalization
* Layouts must not break under RTL (Hindi/`hi_IN` and future RTL locales). Prefer logical CSS properties (`margin-inline-start`, `padding-block`) over physical ones.
* Do not hardcode text direction; let WordPress set `dir` on `<html>`.

## 8. Verification Checklist (per template / pattern)
- [ ] Automated scan passes (axe / Lighthouse a11y ≥ 95, zero critical violations).
- [ ] Keyboard-only walkthrough: all controls reachable, visible focus, no traps (except intentional dialog traps), `Escape` closes overlays.
- [ ] Screen-reader pass (VoiceOver / NVDA) on key templates: landmarks, headings, and state changes announced correctly.
- [ ] Contrast validated for light AND dark mode.
- [ ] `prefers-reduced-motion` respected.
- [ ] RTL layout verified.

## 9. Automated Checks (axe-core)
`tests/e2e/accessibility.spec.js` runs axe-core (WCAG 2.0/2.1/2.2 A and AA rules) on the fixture pages `/interactive` and `/patterns`, in **light and dark** mode, on desktop and mobile (8 scans), as part of `npm run test:e2e` (and so in CI). To scan a real site instead:
```bash
A11Y_BASE_URL=http://localhost:8885 A11Y_PATHS=/,/contact-form/ npx playwright test accessibility
```
Automated scanning finds only a fraction of WCAG problems and does **not** replace the manual keyboard and screen-reader pass (`07-performance-and-qa/02-testing-and-ci.md` §5). Two things this tooling taught us:
* **Fixtures must not hand-copy design tokens.** The harness palettes drifted from `theme.json` (different primary and accent), producing wrong contrast results in both directions. The fixture server now generates `/theme-palette.css` from the real `theme.json`, `interactivity.css` supplies the dark overrides, and server-seeded Interactivity state is read from the same JSON block WordPress prints.
* **A passing scan can be a false pass.** A pattern referencing a missing preset renders with no colour and "passes". Break the fix on purpose and confirm the scan goes red (see `07-performance-and-qa/03-testing-strategy.md` §7).

**Core navigation fallback:** a header `core/navigation` block with no menu assigned makes WordPress render its Page List fallback as `<ul><ul>...</ul></ul>`, which axe flags (`list`, serious). It disappears as soon as a real menu exists. Assign a navigation menu in the Site Editor on every site.
