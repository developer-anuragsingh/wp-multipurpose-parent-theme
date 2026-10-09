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

## 3. Keyboard & Focus
* Every interactive control is reachable and operable by keyboard, in a logical tab order.
* A visible focus indicator is mandatory. Use `:focus-visible` and never remove outlines without an equivalent replacement.
* **Skip link:** a "Skip to content" link is the first focusable element in `parts/header.html`.
* Interactivity API widgets (nav drawer, dropdowns) must implement focus management: move focus into an opened dialog/drawer, trap it while open, restore it to the trigger on close, and close on `Escape`.

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
