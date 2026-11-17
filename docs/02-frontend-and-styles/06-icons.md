# 06. Icons & Icon-First UI

**File:** `docs/02-frontend-and-styles/06-icons.md`
**Module:** Frontend & Styles

## 1. Why Inline SVG (and not glyphs or an icon font)
The theme used Unicode characters as icons (hamburger, multiplication sign, sun, moon, a clipboard symbol). They looked different on every OS and font, were sometimes swapped for colour emoji, and could not be sized or aligned reliably. Icons are now **inline SVG** from one registry, `Atlas\Icons` (`theme/inc/class-icons.php`):
* **No request, no JS, no icon font.** The SVG ships in the HTML, so it is there on the first paint (no layout shift) and works with JavaScript off.
* **`stroke="currentColor"`.** An icon takes its control's text colour in light mode, dark mode, hover and focus with no extra CSS.
* **Sized before CSS loads.** Explicit `width`/`height` reserve the box; CSS then sizes it in `em` (`.atlas-icon`, 1.25em).
* **Decorative by construction:** `aria-hidden="true"` and `focusable="false"`. The icons are original, simple geometric paths on a 24px grid (no third-party icon set or licence to track).

Registered icons: `menu`, `close`, `copy`, `check`, `sun`, `moon`, `refresh`, `chevron-down`, `send`, `log-in`.

## 2. When to Use an Icon Alone, an Icon With a Label, or Text Only
| Pattern | Use for | In this theme |
|---|---|---|
| **Icon only** | Actions that are universally recognised and repeated | menu toggle, close menu, theme toggle, copy quote |
| **Icon + visible text** | Actions where the word carries meaning or the user might hesitate | Retry, Sign in again, Load more posts, Send message |
| **Text only** | Marketing calls to action, the language switcher, skip link | pattern buttons ("Get started"), `EN / HI`, "Skip to content" |

Reasons for the exceptions: a language is shown as a short code, not a flag (flags represent countries, not languages, and are ambiguous; the full native name is in its `aria-label` and tooltip, see `07-typography-and-layout.md` §4), the skip link is for keyboard users who need to see exactly what it does, and pattern CTAs are editorial copy that site owners rewrite.

## 3. Rules for Icon-Only Controls (all mandatory)
1. **An accessible name.** `aria-label` on the control. The icon never provides the name. (`tests/test-icons.php` fails the build for any unnamed icon-only `a`/`button` in the theme's markup, and axe flags it in the browser.)
2. **A tooltip** (`title`) that matches the name, for mouse users. The theme toggle binds `title` to the same state as its `aria-label`, so the tooltip always says what the next click does.
3. **A 44px target.** Header controls are at least 44x44px (WCAG 2.2 AA asks for 24px; 44px is the comfortable touch size). The copy button is 32px and grows to 44px on coarse pointers (`@media (pointer: coarse)`).
4. **A visible state change.** Hover background and a focus ring on every icon control. State changes are not colour-only: the menu icon becomes an X, the sun becomes a moon, and the copy icon becomes a check (plus a live-region announcement).
5. **A stable name.** The name does not flip on state change (the copy button stays "Copy quote"); success is announced once through a separate `role="status"` region.

## 4. Adding an Icon
1. Add the inner SVG markup to `Atlas\Icons::PATHS` (24px grid, stroke-based, no fills, no scripts, no external references).
2. PHP-rendered markup: `Icons::get( 'name' )`. Static `theme/parts/*.html` cannot call PHP, so paste the exact output of `Icons::get()`; `tests/test-icons.php` fails if a copy drifts.
3. Give the surrounding control a name (rule 3.1) and, if it is icon-only, a `title` and the 44px sizing classes.
4. If the E2E harness reproduces the control, paste the same markup there (`tests/e2e/fixtures/interactive-harness.html`); the drift test covers it.

## 5. Notes
* **Swapping icons** is done with CSS (`aria-expanded`, `.is-copied`, `[data-theme]`), not by rewriting text in JS, so it also works the instant the page paints.
* **Existing content:** the contact-form pattern is written into a page's content when it is inserted. A page that already contains the old pattern keeps its old submit button (text only) until the pattern is re-inserted; new insertions get the icon.
* **RTL:** the `send` and `log-in` arrows are directional. If a right-to-left language is added, mirror them with `:dir(rtl) .atlas-icon--send, :dir(rtl) .atlas-icon--log-in { transform: scaleX(-1); }`.
