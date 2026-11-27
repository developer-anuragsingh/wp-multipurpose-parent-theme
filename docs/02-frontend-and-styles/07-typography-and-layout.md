# 07. Typography, Side Padding, the Language Switcher & Hindi Text

**File:** `docs/02-frontend-and-styles/07-typography-and-layout.md`
**Module:** Frontend & Styles

## 1. The Font Scale and the Trap That Made It Ignored
The scale lives in `theme.json` -> `settings.typography.fontSizes` and is the **only** place sizes are set (the body uses the `medium` preset; headings use the larger ones; CSS and patterns reference `var(--wp--preset--font-size--*)`).

| Preset | Fluid range | Used for |
|---|---|---|
| `small` | 12 - 13 px | labels, captions, footer |
| `medium` | **14 - 15 px** | **body text** |
| `large` | 16 - 18 px | h3 |
| `x-large` | 20 - 24 px | h2 |
| `xx-large` | 26 - 34 px | h1 |

**The trap.** WordPress ignores a theme preset that reuses a core slug (`small`, `medium`, `large`, `x-large`) unless `settings.typography.defaultFontSizes` is `false`; it keeps its own 13 / 20 / 36 / 42 px instead. For a long time only `xx-large` (a slug core does not define) applied, so body text was **20 px on desktop** and the theme's scale was dead configuration. `defaultFontSizes: false` is now set and `tests/test-typography.php` fails the build if it is removed. Symptom to recognise: `wp_get_global_settings( array( 'typography', 'fontSizes' ) )` shows the theme origin containing only the slugs core does not define.

**Reading this on a live site:** `wp eval 'print_r( wp_get_global_settings( array( "typography", "fontSizes" ) ) );'` lists sizes per origin (`default`, `theme`, `custom`). If your site still shows large text after an update, also check Site Editor -> Styles -> Typography for user overrides stored in the database (the `wp_global_styles` post), which win over `theme.json`.

**Readability guardrails (enforced by tests).** Body never below 14 px or above 16 px; nothing below 12 px; h1 tops out at 36 px; the scale strictly increases; each base size sits inside its fluid min/max. Compact type is a trade-off: **Devanagari (Hindi) has a smaller x-height than Latin**, so 14 px Hindi reads smaller than 14 px English. If Hindi readability matters more than density, raise `medium` to 15 - 16 px (one value in `theme.json`) and every size follows.

**Form fields are the exception.** `.atlas-contact-form__input/__textarea` use `1rem` (16 px), not the body preset: iOS Safari zooms the whole page when a focused field is smaller than 16 px.

## 2. Side Padding
`useRootPaddingAwareAlignments` was on but **no root padding was defined**, so constrained content touched the screen edge on phones. `styles.spacing.padding` now sets `clamp(1rem, 4vw, 2rem)` on both sides: 16 px on a 360 px phone, growing to 32 px from about 800 px.
* `main` and other constrained groups inherit it (`has-global-padding`); `alignfull` sections still reach the edges because core cancels the root padding for them.
* **Header and footers read the same value** (`var(--wp--style--root--padding-left/right)` in `parts/header.html`, `footer.html`, `footer-landing.html`), so the site title and copyright line up with the page content and the left and right gaps are equal at every width (verified at 360 / 412 / 768 / 1280 / 1920 px, no horizontal scroll). Change the edge gap in one place: `styles.spacing.padding` in `theme.json`.
* On wide screens the header and footer span the full width with the 32 px gap while the content sits in the 840 px column; that is intentional.

## 3. The Notifications Part Exists Once, Outside Layout Groups
`parts/notifications.html` (live regions, offline banner, toasts, session modal) was included in **both** the header and the footer, and as the first child of each flex group. Consequences: duplicate `id`s (`atlas-session-modal-title/-desc` twice, invalid HTML that breaks `aria-labelledby`), two sets of live regions, and a zero-size flex child whose gap pushed the site title to about 50 px and the copyright text to the middle of the footer. It is now included once, in the header, **before** the flex group. `tests/test-fixtures.php` enforces both rules.

## 4. Language Switcher: Short Labels
The switcher shows **EN / HI** (the `short` value in `Atlas\I18n::get_supported_locales()`). Each link keeps `lang`/`hreflang` and carries the full name: `aria-label="HI (हिन्दी)"` and `title="हिन्दी"`.
* The accessible name **contains the visible text** (WCAG 2.5.3 Label in Name), so a voice-control user can say "click HI", and screen readers announce the language by name.
* The tooltip shows the native name for sighted users.
* To show the native script instead, change one `esc_html( $short )` back to `$native` in `render_switcher()` (and the static copy in `header.html`); `HI` in Latin script is the more compact choice but is less recognisable to a reader who knows only Devanagari.

## 5. The Spacing Scale

`settings.spacing.spacingSizes` had the same trap as the font scale: with `defaultSpacingSizes` left on, core's `20..80` presets (0.44 / 0.67 / 1 / 1.5 / 2.25 / 3.38 / 5.06 rem) replaced the theme's colliding slugs and the theme origin was empty. The theme now sets `"defaultSpacingSizes": false` and owns the full scale:

| Slug | Name | Size | Core value it replaces |
|---|---|---|---|
| `20` | X-Small | 0.5rem (8px) | 0.44rem |
| `30` | Small | 0.75rem (12px) | 0.67rem |
| `40` | Medium | 1rem (16px) | 1rem |
| `50` | Large | 1.5rem (24px) | 1.5rem |
| `60` | X-Large | clamp(1.5rem, 4vw, 2.25rem) (24-36px) | 2.25rem |
| `70` | 2X-Large | clamp(2rem, 5vw, 3rem) (32-48px) | 3.38rem |
| `80` | 3X-Large | clamp(2.5rem, 7vw, 4rem) (40-64px) | 5.06rem |

Why these values: the old theme values (`60` = 4rem) were never rendered, so adopting them would have made every section much larger, against the compact-UI direction of §1. The scale keeps the spacing users already see on desktop (`40`, `50`, `60` are unchanged at 1280px), rounds the small steps to whole pixels, and makes the large steps fluid so section padding tightens on phones (`60` is 24px at 412px instead of 36px). `70` and `80` are not used by the theme itself but are defined so content saved with core's presets keeps a value.

Guards: `tests/test-typography.php` (the switch is off, every slug 20-80 exists, the scale is increasing, 40 = 16px, 60 <= 36px, 80 <= 64px, nothing below 8px) and `tests/integration/test-theme-json.php` (real WordPress emits `--wp--preset--spacing--40: 1rem` and the clamp for `60`, and none of core's values). The E2E harnesses read the spacing presets from `theme.json` through `/theme-palette.css` (`tests/e2e/fixtures/server.js`) instead of a hand-copied scale.

## 6. Hindi (Devanagari) Text Size
Devanagari stacks vowel signs above and below the letters, so the Latin body size (`medium`, 14-15px) is too small for Hindi. When `Atlas\I18n` renders a Hindi page (`<html lang="hi-IN">`), `theme/assets/css/interactivity.css` re-points the presets under `:root:lang(hi)`, the same way dark mode re-points colours:

| Preset | Latin (theme.json) | Hindi page |
|---|---|---|
| `medium` (body) | 14-15px | `clamp(0.9375rem, 0.875rem + 0.25vw, 1rem)`: 15px at 412px, 16px from about 1000px |
| `small` (captions, labels, switcher) | 12-13px | `clamp(0.8125rem, 0.75rem + 0.2vw, 0.875rem)`: 13-14px |
| body `line-height` | 1.6 | 1.75 |

* **Root only.** The selector is `:root:lang(hi)`, never a bare `:lang(hi)`: an inline `lang="hi"` element on an English page (the "HI" switcher link, a Hindi quotation) keeps the Latin size, so words do not jump in size inside English text.
* **Whole page.** On a Hindi page every element that uses those presets grows, including any English content the site has not translated yet. That is deliberate: the UI chrome is Hindi, and a mixed-size page reads worse.
* **Fonts.** Inter and Sora have no Devanagari glyphs; Hindi text uses the `system-ui` fallback in the font stack (Kohinoor/ITF Devanagari on Apple, Nirmala UI on Windows, Noto Sans Devanagari on Android).
* **Child themes** that change the scale can override the Hindi values with the same `:root:lang(hi)` selector in their own CSS.
* **Guards:** `tests/test-typography.php` (Hindi body 15-16px, captions 13-14px, line-height >= 1.7, no unscoped `:lang(hi)`) and `tests/e2e/language-switcher.spec.js` (computed body size and leading on a Hindi root; an inline Hindi paragraph on an English page keeps the Latin size). Verified on the Studio site: `?lang=hi` body 15.03px at 412px and 16px at 1280px, English unchanged (14.1px / 15px).
