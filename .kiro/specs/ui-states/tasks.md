# Implementation Plan: UI States

## Overview

This plan implements the accessible UI-state system for the Atlas Multipurpose FSE theme in three sequential phases, matching the design and requirements.

- **Phase 1 (content & nav)** — register the archive empty-state and search no-results block patterns, wire them into `archive.html` / `search.html` `wp:query-no-results` blocks, and enhance `404.html`. Server-rendered only; no JS dependency.
- **Phase 2 (async & network)** — add the `Atlas\UI_States` PHP concern (nonce-verified REST route, pattern registration, Interactivity state seeding, build asset enqueue), the `parts/notifications.html` template part, the shared `rest-client.js`, and the loading, offline, slow-request, and failure/maintenance store modules.
- **Phase 3 (micro-states & success)** — button, form, comment-feedback, and session-modal store modules, plus the full `src/css/ui-states.css` micro-state styling.

Each task builds on the previous one and ends by wiring new code into an integration point so no orphaned code is left behind. Property tests reference the design's Properties 1-18; test-only sub-tasks are marked `*`.

Convention anchor: the work mirrors the existing `Atlas\Assets` concern (`theme/inc/class-assets.php`), the `src/js/theme-mode.js` Interactivity store style, the `theme/build/*.asset.php` module convention, and `theme.json` preset variables only.

## Tasks

- [x] 1. Phase 1 — Archive empty-state block pattern
  - [x] 1.1 Create the archive empty-state pattern file
    - Create `theme/patterns/archive-empty-state.php` with pattern header metadata (`Title`, `Slug: atlas/archive-empty-state`, `Categories: atlas`, `Text Domain: atlas-theme`)
    - Render a heading + explanatory paragraph using `esc_html__` with the `atlas-theme` text domain
    - Render a call-to-action link to the site home: `esc_url( home_url( '/' ) )` with an `esc_html__` label
    - _Requirements: 1.1, 1.3, 1.4, 1.5, 10.10, 10.11_

  - [x] 1.2 Wire the archive empty-state pattern into `archive.html`
    - Replace the placeholder paragraph inside the `wp:query-no-results` block of `theme/templates/archive.html` with a reference to the `atlas/archive-empty-state` pattern
    - _Requirements: 1.2_

  - [ ]* 1.3 Write example test for the archive empty-state CTA
    - Assert the rendered pattern contains a home CTA link whose href equals `home_url()`
    - _Requirements: 1.2, 1.3_

- [x] 2. Phase 1 — Search no-results block pattern
  - [x] 2.1 Create the search no-results pattern file
    - Create `theme/patterns/search-no-results.php` with pattern header metadata (`Slug: atlas/search-no-results`, `Categories: atlas`, `Text Domain: atlas-theme`)
    - Render a heading + paragraph (gettext) and a `wp:search` input block
    - Render suggested tags and categories from `get_terms()` as navigable links, escaping each with `esc_url( get_term_link( $term ) )` and `esc_html( $term->name )`; render only existing terms
    - _Requirements: 2.1, 2.3, 2.4, 2.5, 2.6, 10.10, 10.11_

  - [x] 2.2 Wire the search no-results pattern into `search.html`
    - Replace the placeholder inside the `wp:query-no-results` block of `theme/templates/search.html` with a reference to the `atlas/search-no-results` pattern
    - _Requirements: 2.2_

  - [ ]* 2.3 Write property test for valid navigable term links
    - **Property 15: Search suggestions are valid navigable term links**
    - **Validates: Requirements 2.4**
    - Generate tag/category sets; assert every rendered suggestion is an anchor whose href equals an existing term's archive URL and no suggestion references a non-existent term (min 100 iterations)

  - [ ]* 2.4 Write property test for escaped dynamic pattern output
    - **Property 16: Dynamic pattern output is escaped**
    - **Validates: Requirements 2.6, 10.10**
    - Generate term names containing HTML/special characters; assert rendered output contains only the escaped form and produces no live markup (min 100 iterations)

  - [ ]* 2.5 Write example test for search no-results wiring
    - Assert the pattern renders a search input and is referenced from `search.html`'s `query-no-results` block
    - _Requirements: 2.2, 2.3_

- [x] 3. Phase 1 — Enhanced 404 template
  - [x] 3.1 Enhance `404.html`
    - Keep the semantic `<main id="atlas-main">` / `<h1>` structure and the `wp:search` block
    - Add a home call-to-action link group using an escaped URL and a translatable `atlas-theme` label
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [ ]* 3.2 Write example test for the enhanced 404
    - Assert the rendered `404.html` includes a search input and a home CTA link
    - _Requirements: 3.1, 3.2, 3.3_

- [x] 4. Phase 1 — Pattern registration and smoke checks
  - [x] 4.1 Create `Atlas\UI_States` skeleton with pattern registration
    - Create `theme/inc/class-ui-states.php` (`namespace Atlas`) resolved by the existing autoloader, mirroring `Atlas\Assets`
    - Add `init()` hooking `init` to `register_block_patterns()`: register the `atlas` block pattern category and both patterns from `theme/patterns/` (slugs `atlas/archive-empty-state`, `atlas/search-no-results`)
    - Add `Atlas\UI_States::init();` to `theme/functions.php` after `Atlas\Assets::init();`
    - _Requirements: 1.1, 2.1_

  - [ ]* 4.2 Write smoke tests for pattern registration and i18n
    - Assert both patterns are registered after `init`; assert all user-facing strings in the patterns and 404 use the `atlas-theme` text domain; static-check that patterns/templates use `esc_html__`/`esc_url`/`wp_kses_post`
    - _Requirements: 1.1, 1.4, 1.5, 2.1, 2.5, 2.6, 3.4, 3.5, 10.11_

- [x] 5. Phase 1 checkpoint
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Phase 2 — REST endpoint (single async contract)
  - [x] 6.1 Register the nonce-verified REST route
    - In `Atlas\UI_States`, hook `rest_api_init` to `register_rest_route()` for namespace `atlas/v1`, path `/ui-state`, methods `GET`/`POST`
    - Implement `verify_nonce()` as the `permission_callback`: read `X-WP-Nonce`, call `wp_verify_nonce( $nonce, 'wp_rest' )`, return a `WP_Error` (401/403) on failure
    - Implement `handle_request()` returning the single contract shape `{ status: 'success'|'error'|'maintenance', data, message }`, with `message` pre-translated via `atlas-theme`; maintenance returns HTTP 503
    - _Requirements: 6.3, 6.5, 9.3, 10.9_

  - [ ]* 6.2 Write property test for nonce gating (PHP)
    - **Property 7: Nonce verification gates every request**
    - **Validates: Requirements 6.5, 10.9**
    - Generate valid/invalid/missing nonces against `permission_callback`; assert an auth error with no data is returned iff the nonce is missing or invalid (min 100 iterations)

- [x] 7. Phase 2 — Interactivity state seeding and asset enqueue
  - [x] 7.1 Create the build entry and seed store state
    - Create `theme/src/js/ui-states.js` as the single build entry that imports the per-concern modules from `theme/src/js/interactivity/` and imports `theme/src/css/ui-states.css`
    - In `Atlas\UI_States::register_assets()` (hooked to `wp_enqueue_scripts`), call `wp_interactivity_state( 'atlas/ui-states', [...] )` seeding `restUrl`, `nonce` (`wp_create_nonce('wp_rest')`), `slowThreshold => 8000`, and pre-translated `labels`
    - Register/enqueue `build/ui-states.js` as a script module (read `build/ui-states.asset.php` for deps + version, fail-closed if absent) and `wp_enqueue_style` the built `build/ui-states.css`, exactly like `Atlas\Assets`
    - _Requirements: 4.1, 10.6_

- [x] 8. Phase 2 — Notifications template part
  - [x] 8.1 Create `parts/notifications.html`
    - Create `theme/parts/notifications.html` with `data-wp-interactive="atlas/ui-states"`: a polite `role="status"` live region, an assertive `role="alert"` live region, an offline banner (hidden by default, retry button bound to `actions.retry`), a toast stack container, and a `role="dialog" aria-modal="true"` session-expired modal with first/last focus sentinels
    - All strings use `atlas-theme`; controls progressively enhanced (present but inert without JS)
    - _Requirements: 5.1, 5.5, 6.4, 9.4, 10.2_

  - [x] 8.2 Wire the notifications part into header, footer, and theme.json
    - Add `<!-- wp:template-part {"slug":"notifications"} /-->` to `theme/parts/header.html` and `theme/parts/footer.html`
    - Add the `{ "name": "notifications", "title": "Notifications", "area": "uncategorized" }` entry to `theme.json` `templateParts`
    - _Requirements: 5.1, 10.2_

- [x] 9. Phase 2 — Shared REST client store module
  - [x] 9.1 Implement `rest-client.js`
    - Create `theme/src/js/interactivity/rest-client.js` registering on `atlas/ui-states`: a `fetch` wrapper injecting `X-WP-Nonce`, starting the 8s timer, recording `state.lastRequest` for retry, and classifying responses into success/error/maintenance/auth-error
    - Add a `retry` action that re-dispatches `state.lastRequest`
    - _Requirements: 5.3, 6.2, 6.1, 9.3_

  - [ ]* 9.2 Write property test for exclusive, total response classification
    - **Property 6: Response classification is exclusive and total**
    - **Validates: Requirements 6.1, 6.3, 9.3**
    - Generate responses; assert exactly one outcome (success/maintenance/failure) is surfaced per response (min 100 iterations)

  - [ ]* 9.3 Write property test for retry re-dispatch
    - **Property 5: Retry re-dispatches the most recent failed request**
    - **Validates: Requirements 5.3, 6.2**
    - Generate last-request descriptors; assert retry dispatches a request with the same endpoint and args (min 100 iterations)

- [x] 10. Phase 2 — Loading / skeleton store module
  - [x] 10.1 Implement `loading.js`
    - Create `theme/src/js/interactivity/loading.js` deriving skeleton visibility and `aria-busy` from a content region's request status
    - _Requirements: 4.1, 4.2, 4.3_

  - [ ]* 10.2 Write property test for skeleton visibility
    - **Property 1: Skeleton visible exactly while loading**
    - **Validates: Requirements 4.1**
    - Assert a skeleton renders iff the region status is `loading` (min 100 iterations)

  - [ ]* 10.3 Write property test for region aria-busy mirroring
    - **Property 2: aria-busy mirrors loading status (region)**
    - **Validates: Requirements 4.2, 4.3**
    - Generate start/complete event sequences; assert `aria-busy` equals `"true"` iff status is `loading` after each event (min 100 iterations)

- [x] 11. Phase 2 — Offline and slow-request store modules
  - [x] 11.1 Implement `offline.js`
    - Create `theme/src/js/interactivity/offline.js` listening to `online`/`offline`, toggling the offline banner, and short-circuiting requests dispatched while offline; retry re-dispatches the last failed request
    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 11.2 Implement `slow-request.js`
    - Create `theme/src/js/interactivity/slow-request.js` writing the slow notice to the polite live region when elapsed time crosses the 8s threshold, cleared on completion
    - _Requirements: 5.4, 5.5_

  - [ ]* 11.3 Write property test for offline banner visibility
    - **Property 4: Offline banner visibility mirrors connectivity**
    - **Validates: Requirements 5.1, 5.2**
    - Generate online/offline transition sequences; assert the banner is present iff the browser reports offline (min 100 iterations)

  - [ ]* 11.4 Write edge-case test for the 8s slow threshold
    - With fake timers, assert the slow notice is absent just before 8s and present just after
    - _Requirements: 5.4_

- [x] 12. Phase 2 — Failure / maintenance store module
  - [x] 12.1 Implement `failure.js`
    - Create `theme/src/js/interactivity/failure.js` showing a failure toast (assertive, with retry) for error responses, a maintenance notice for `status: maintenance`, and a success toast (polite) on success — mutually exclusive
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 9.3_

  - [ ]* 12.2 Write property test for urgency-appropriate live-region roles
    - **Property 17: Dynamic state changes are announced with urgency-appropriate roles**
    - **Validates: Requirements 5.5, 6.4, 10.2**
    - Generate notice-producing state changes; assert non-urgent notices use `role="status"` and urgent notices use `role="alert"` (min 100 iterations)

  - [ ]* 12.3 Write example tests for live-region announcements
    - Assert the live regions receive text for offline/slow/success/failure/maintenance outcomes
    - _Requirements: 5.5, 6.4_

- [x] 13. Phase 2 checkpoint
  - Ensure all tests pass, ask the user if questions arise.

- [x] 14. Phase 3 — Button store module and activation guard
  - [x] 14.1 Implement `button.js`
    - Create `theme/src/js/interactivity/button.js` deriving per-button loading state, inline spinner, and `aria-busy` from the button's own request, and blocking activation while disabled
    - _Requirements: 4.4, 8.5, 8.6_

  - [ ]* 14.2 Write property test for button busy/spinner mirroring
    - **Property 3: Button busy and spinner mirror the button's own request**
    - **Validates: Requirements 4.4**
    - Assert the spinner shows and `aria-busy` is `"true"` iff the button's request is in flight (min 100 iterations)

  - [ ]* 14.3 Write property test for disabled-button inertness
    - **Property 13: Disabled buttons do not activate**
    - **Validates: Requirements 8.5**
    - Generate activation attempts on disabled buttons; assert no action and no REST request is dispatched (min 100 iterations)

- [x] 15. Phase 3 — Form store module
  - [x] 15.1 Implement `form.js`
    - Create `theme/src/js/interactivity/form.js` tracking `aria-invalid`, rendering/removing the inline error message, and associating it via `aria-describedby`; track disabled state
    - _Requirements: 7.2, 7.3, 7.4_

  - [ ]* 15.2 Write property test for aria-invalid tracking
    - **Property 11: aria-invalid tracks field validity**
    - **Validates: Requirements 7.2, 7.4**
    - Generate validity-transition sequences; assert `aria-invalid` and inline-error presence track field validity (min 100 iterations)

  - [ ]* 15.3 Write property test for aria-describedby association
    - **Property 12: Invalid fields reference their error via aria-describedby**
    - **Validates: Requirements 7.3**
    - Assert each invalid field's `aria-describedby` references the id of its rendered inline error element (min 100 iterations)

- [x] 16. Phase 3 — Comment feedback store module
  - [x] 16.1 Implement `comment-feedback.js`
    - Create `theme/src/js/interactivity/comment-feedback.js` mapping comment-submission outcome to the submitted-confirmation banner vs the pending-moderation banner
    - _Requirements: 9.1, 9.2_

  - [ ]* 16.2 Write property test for comment banner selection
    - **Property 14: Comment outcome selects exactly one banner**
    - **Validates: Requirements 9.1, 9.2**
    - Generate submission outcomes; assert exactly one of submitted/pending banners is shown per outcome (min 100 iterations)

- [x] 17. Phase 3 — Session-expired modal store module
  - [x] 17.1 Implement `session-modal.js`
    - Create `theme/src/js/interactivity/session-modal.js`: open the modal on a logged-in session-expiry auth error, move focus into the modal and trap it (wrapping at first/last focusable), and refresh `state.nonce` on re-authentication (wrap storage/refresh in `try/catch`)
    - _Requirements: 9.4, 9.5, 9.6_

  - [ ]* 17.2 Write property test for session-expiry modal open
    - **Property 8: Session-expiry auth error opens the modal**
    - **Validates: Requirements 9.4**
    - Generate auth-error responses for logged-in session expiry; assert the modal opens (min 100 iterations)

  - [ ]* 17.3 Write property test for focus trap
    - **Property 9: Focus stays trapped within the open modal**
    - **Validates: Requirements 9.5**
    - Generate Tab / Shift+Tab sequences while the modal is open; assert focus stays within the modal, wraps at the ends, and is inside the modal immediately after open (min 100 iterations)

  - [ ]* 17.4 Write property test for nonce refresh
    - **Property 10: Re-authentication refreshes the nonce used next**
    - **Validates: Requirements 9.6**
    - Generate re-authentications yielding new nonces; assert every subsequent request carries the new nonce (min 100 iterations)

- [x] 18. Phase 3 — UI-states stylesheet
  - [x] 18.1 Author `src/css/ui-states.css`
    - Create `theme/src/css/ui-states.css` using `theme.json` preset variables only: shimmer + spinner keyframes, reserved skeleton dimensions (no layout shift), `:focus-visible` ring from the `primary` token, button states (default/hover/active/focus/disabled with `pointer-events: none` + loading modifier), form input states (focus/`aria-invalid`/inline error/disabled), notifications styling (banner/toast/modal), a `prefers-reduced-motion: reduce` static-placeholder block, and 320px no-overflow rules
    - _Requirements: 4.1, 4.4, 4.5, 4.6, 7.1, 7.5, 8.1, 8.2, 8.3, 8.4, 8.5, 10.3, 10.5, 10.6, 10.7, 10.8_

  - [ ]* 18.2 Write property test for preset-variable-only CSS
    - **Property 18: Authored CSS uses preset variables only**
    - **Validates: Requirements 10.6**
    - Parse `src/css/ui-states.css`; assert every color/spacing/font-size declaration resolves to a `var(--wp--preset--...)` variable and never a hard-coded literal (min 100 iterations over declarations)

  - [ ]* 18.3 Write integration and accessibility tests
    - No layout shift between skeleton and loaded content (R4.5, R10.8); reduced-motion disables shimmer/spinner (R4.6, R10.5); axe scan + keyboard walkthrough with a visible focus ring on each interactive element (R7.1, R8.1-R8.4, R8.6, R10.1, R10.3, R10.4); disabled visual states (R7.5, R8.5); 320px `scrollWidth <= clientWidth` (R10.7)
    - _Requirements: 4.5, 4.6, 7.1, 7.5, 8.1, 8.2, 8.3, 8.4, 8.6, 10.1, 10.3, 10.4, 10.5, 10.7, 10.8_

- [x] 19. Final checkpoint
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional (tests and polish) and can be skipped for a faster MVP, but the property tests back the design's correctness guarantees and are recommended.
- Each task references the specific requirement clauses and, for test tasks, the design Property it validates.
- Phase checkpoints provide incremental validation; the three phases follow the requirements' sequential order (content/nav → async/network → micro-states/success).
- All PHP mirrors the existing `Atlas\Assets` concern; all JS follows the `src/js/theme-mode.js` Interactivity store style; all CSS uses `theme.json` preset variables only.
- This workflow produces planning artifacts only. Implementation happens in a separate step.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "2.1", "3.1"] },
    { "id": 1, "tasks": ["1.2", "1.3", "2.2", "2.3", "2.4", "2.5", "3.2"] },
    { "id": 2, "tasks": ["4.1"] },
    { "id": 3, "tasks": ["4.2", "6.1", "7.1", "8.1", "18.1"] },
    { "id": 4, "tasks": ["6.2", "8.2", "9.1", "18.2", "18.3"] },
    { "id": 5, "tasks": ["9.2", "9.3", "10.1", "11.1", "11.2", "12.1", "14.1", "15.1", "16.1", "17.1"] },
    { "id": 6, "tasks": ["10.2", "10.3", "11.3", "11.4", "12.2", "12.3", "14.2", "14.3", "15.2", "15.3", "16.2", "17.2", "17.3", "17.4"] }
  ]
}
```
