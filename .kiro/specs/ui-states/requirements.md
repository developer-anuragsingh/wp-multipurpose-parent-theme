# Requirements Document

## Introduction

This feature delivers a cohesive set of accessible UI states for the Atlas Multipurpose FSE block theme (WordPress 6.8+, PHP 8.1+). It covers content-presence states (empty archives, search no-results, enhanced 404), asynchronous feedback states (loading skeletons, button spinners, offline detection, request timeouts, failure recovery), and interaction states (form inputs, buttons, comment submission feedback, success toasts, and session-expired re-authentication).

Work is organized into three phases implemented sequentially within this single spec. Phase 1 covers static presence states delivered as registered block patterns and template updates. Phase 2 introduces asynchronous feedback driven by the WordPress Interactivity API and a nonce-verified REST route. Phase 3 covers interactive form, button, and session states.

Static presence states are delivered as registered block patterns in the `patterns/` directory, referenced into `archive.html`, `search.html`, and `404.html`. Global interactive UI (offline banner, failure and success toasts, session-expired modal) is delivered through a reusable template part `parts/notifications.html` referenced from the header and footer so that the regions mount on every template. A new PHP concern in `inc/` registers a nonce-verified REST route, registers the block patterns, passes the REST URL and nonce to the Interactivity API store, and enqueues built CSS and JavaScript via the extended `Setup::enqueue_assets`. Client behavior is implemented as modular Interactivity API store modules in `src/js`, and styling lives in `src/css` using only `theme.json` preset variables.

All requirements apply the cross-cutting standards defined in the Glossary and enumerated in Requirement 10 (Accessibility and Compliance Baseline), which applies to every other requirement in this document.

## Glossary

- **Atlas_Theme**: The Atlas Multipurpose FSE block theme, the system under design, targeting WordPress 6.8+ and PHP 8.1+.
- **Interactivity_Store**: The set of modular `@wordpress/interactivity` store modules in `src/js` (one module per concern: loading, offline, toast, session-modal, form and button states) that drive client-side UI state.
- **REST_Endpoint**: The custom WordPress REST API route registered via `register_rest_route` in `inc/`, protected by WordPress nonce verification, that serves as the single async contract for retry, timeout, and maintenance behavior.
- **Notifications_Part**: The reusable template part `parts/notifications.html`, referenced from the header and footer template parts, that hosts the centralized `aria-live` regions, offline banner, toasts, and session-expired modal on every template.
- **Pattern_Registry**: The block patterns registered from the `patterns/` directory by the new PHP concern in `inc/`.
- **Assets_Concern**: The extended `Setup::enqueue_assets` behavior that enqueues the built module view-script and stylesheet and localizes the REST URL and nonce into the Interactivity_Store.
- **Text_Domain**: The internationalization text domain `atlas-theme` used for all translatable strings.
- **Preset_Variable**: A `theme.json` CSS custom property (for example `var(--wp--preset--color--primary)` or `var(--wp--preset--spacing--50)`) used as the only source of color, spacing, and typography values in authored CSS.
- **Reduced_Motion**: The user preference expressed by the `prefers-reduced-motion: reduce` media query.
- **Focus_Ring**: A visible `:focus-visible` outline rendered using the theme primary color Preset_Variable.
- **Slow_Request_Threshold**: The elapsed time of 8 seconds after which an in-flight REST_Endpoint request is reported to the user as slow.
- **Live_Region**: An ARIA live region (`aria-live` with `role="status"` or `role="alert"`) in the Notifications_Part used to announce state changes to assistive technology.

## Requirements

### Requirement 1: Archive Empty State

**User Story:** As a visitor browsing an empty archive, I want a clear empty-state message with a call to action, so that I can navigate to available content instead of facing a blank page.

#### Acceptance Criteria

1. THE Pattern_Registry SHALL register an archive empty-state block pattern located in the `patterns/` directory.
2. WHEN an archive query returns zero posts, THE Atlas_Theme SHALL render the archive empty-state block pattern inside the `wp:query-no-results` block of `archive.html`.
3. THE archive empty-state block pattern SHALL include a call-to-action link that directs the visitor to the site home.
4. THE archive empty-state block pattern SHALL render all user-facing text using the Text_Domain.
5. THE archive empty-state block pattern SHALL escape dynamic output using `esc_html__`, `esc_url`, or `wp_kses_post`.

### Requirement 2: Search No-Results State

**User Story:** As a visitor whose search returned nothing, I want an alternate search box and suggested topics, so that I can refine my search and still find relevant content.

#### Acceptance Criteria

1. THE Pattern_Registry SHALL register a search no-results block pattern located in the `patterns/` directory.
2. WHEN a search query returns zero posts, THE Atlas_Theme SHALL render the search no-results block pattern inside the `wp:query-no-results` block of `search.html`.
3. THE search no-results block pattern SHALL include a search input box.
4. THE search no-results block pattern SHALL display suggested tags and categories as navigable links.
5. THE search no-results block pattern SHALL render all user-facing text using the Text_Domain.
6. THE search no-results block pattern SHALL escape dynamic output using `esc_html__`, `esc_url`, or `wp_kses_post`.

### Requirement 3: Enhanced 404 Page

**User Story:** As a visitor who reached a missing page, I want an informative 404 page with navigation options, so that I can recover and continue to useful content.

#### Acceptance Criteria

1. WHEN a request resolves to no matching content, THE Atlas_Theme SHALL render the enhanced `404.html` template.
2. THE enhanced `404.html` template SHALL include a search input box.
3. THE enhanced `404.html` template SHALL include a call-to-action link that directs the visitor to the site home.
4. THE enhanced `404.html` template SHALL render all user-facing text using the Text_Domain.
5. THE enhanced `404.html` template SHALL escape dynamic output using `esc_html__`, `esc_url`, or `wp_kses_post`.

### Requirement 4: Loading and Skeleton States

**User Story:** As a visitor waiting for content to load, I want skeleton placeholders and an inline button spinner, so that I understand that the system is working and the layout stays stable.

#### Acceptance Criteria

1. WHILE a REST_Endpoint content request is in flight, THE Interactivity_Store SHALL display shimmer skeleton cards in place of the pending content.
2. WHILE a REST_Endpoint content request is in flight, THE Interactivity_Store SHALL set `aria-busy="true"` on the region that contains the pending content.
3. WHEN a REST_Endpoint content request completes, THE Interactivity_Store SHALL set `aria-busy="false"` on the region that contained the pending content.
4. WHILE an action-triggering button has an associated request in flight, THE Interactivity_Store SHALL display an inline spinner within that button and set `aria-busy="true"` on that button.
5. WHEN skeleton placeholders are replaced by loaded content, THE Atlas_Theme SHALL preserve the rendered dimensions so that no layout shift occurs.
6. WHILE Reduced_Motion is active, THE Atlas_Theme SHALL render the shimmer and spinner as a static non-animated placeholder.

### Requirement 5: Offline and Slow-Request States

**User Story:** As a visitor on an unstable connection, I want to be told when I am offline or when a request is slow and be offered a retry, so that I know what is happening and can recover.

#### Acceptance Criteria

1. WHEN the browser reports a transition to offline through `navigator.onLine`, THE Interactivity_Store SHALL display an offline banner in the Notifications_Part.
2. WHEN the browser reports a transition to online through `navigator.onLine`, THE Interactivity_Store SHALL remove the offline banner from the Notifications_Part.
3. THE offline banner SHALL include a retry control that re-attempts the most recent failed REST_Endpoint request.
4. IF a REST_Endpoint request remains in flight for longer than the Slow_Request_Threshold, THEN THE Interactivity_Store SHALL display a slow-request notice to the visitor.
5. THE offline banner and slow-request notice SHALL be announced through a Live_Region.

### Requirement 6: Failure and Maintenance States

**User Story:** As a visitor whose request failed, I want a clear failure message with a retry option and a maintenance notice when appropriate, so that I can try again or understand that the site is temporarily unavailable.

#### Acceptance Criteria

1. IF a REST_Endpoint request returns an error response, THEN THE Interactivity_Store SHALL display a failure toast in the Notifications_Part.
2. THE failure toast SHALL include a retry control that re-attempts the failed REST_Endpoint request.
3. IF a REST_Endpoint request returns a maintenance status, THEN THE Interactivity_Store SHALL display a maintenance notice instead of the generic failure toast.
4. THE failure toast and maintenance notice SHALL be announced through a Live_Region using `role="alert"`.
5. IF a REST_Endpoint request fails nonce verification, THEN THE REST_Endpoint SHALL return an authentication error status.

### Requirement 7: Form Input States

**User Story:** As a visitor filling out a form, I want clear input states and inline error messages, so that I understand which fields need attention and how to correct them.

#### Acceptance Criteria

1. WHILE a form input holds keyboard focus, THE Atlas_Theme SHALL render the input with a Focus_Ring.
2. IF a form input fails validation, THEN THE Interactivity_Store SHALL set `aria-invalid="true"` on that input.
3. IF a form input fails validation, THEN THE Interactivity_Store SHALL display an inline error message associated with that input through `aria-describedby`.
4. WHEN a previously invalid form input passes validation, THE Interactivity_Store SHALL set `aria-invalid="false"` on that input and remove the associated inline error message.
5. WHILE a form input is disabled, THE Atlas_Theme SHALL render the input in a visually distinct disabled state.

### Requirement 8: Button States

**User Story:** As a visitor interacting with buttons, I want distinct visual states for every interaction, so that I always know whether a button is available, active, or working.

#### Acceptance Criteria

1. WHILE a button is in its default state, THE Atlas_Theme SHALL render the button using Preset_Variable values.
2. WHILE a pointer hovers a button, THE Atlas_Theme SHALL render a distinct hover state.
3. WHILE a button is activated, THE Atlas_Theme SHALL render a distinct active state.
4. WHILE a button holds keyboard focus, THE Atlas_Theme SHALL render the button with a Focus_Ring.
5. WHILE a button is disabled, THE Atlas_Theme SHALL render a distinct disabled state and prevent activation.
6. WHILE a button has an associated request in flight, THE Atlas_Theme SHALL render a distinct loading state.

### Requirement 9: Submission Feedback and Session Re-Authentication

**User Story:** As a logged-in visitor submitting a comment or form, I want confirmation of submission and a way to re-authenticate when my session expires, so that my action is acknowledged and I do not lose my work.

#### Acceptance Criteria

1. WHEN a comment is submitted successfully, THE Atlas_Theme SHALL display a comment-submitted confirmation banner.
2. WHEN a submitted comment is held for moderation, THE Atlas_Theme SHALL display a pending-moderation banner.
3. WHEN a form submission to the REST_Endpoint succeeds, THE Interactivity_Store SHALL display a success toast in the Notifications_Part.
4. IF a REST_Endpoint request from a logged-in visitor fails nonce verification due to an expired session, THEN THE Interactivity_Store SHALL display a session-expired re-authentication modal.
5. WHEN the session-expired re-authentication modal is displayed, THE Interactivity_Store SHALL move keyboard focus into the modal and restrict focus to the modal while it is open.
6. WHEN re-authentication through the session-expired modal completes, THE Interactivity_Store SHALL refresh the nonce used for subsequent REST_Endpoint requests.

### Requirement 10: Accessibility and Compliance Baseline

**User Story:** As any user of the theme, I want every UI state to meet accessibility and security standards, so that the experience is usable, safe, and compliant across devices and assistive technologies.

#### Acceptance Criteria

1. THE Atlas_Theme SHALL render every UI state defined in Requirements 1 through 9 using semantic HTML markup that conforms to WCAG 2.1 AA.
2. WHEN a UI state changes dynamically, THE Interactivity_Store SHALL announce the change through a Live_Region using `role="status"` or `role="alert"` as appropriate to the urgency.
3. THE Atlas_Theme SHALL render a visible Focus_Ring on every interactive element when that element holds keyboard focus.
4. THE Atlas_Theme SHALL make every interactive UI state operable by keyboard.
5. WHILE Reduced_Motion is active, THE Atlas_Theme SHALL render all animated states without motion.
6. THE Atlas_Theme SHALL author all color, spacing, and typography values in `src/css` using Preset_Variable values only.
7. THE Atlas_Theme SHALL render every UI state without horizontal overflow at a viewport width of 320 pixels.
8. WHEN a UI state swaps rendered content, THE Atlas_Theme SHALL preserve layout dimensions so that no layout shift occurs.
9. THE REST_Endpoint SHALL verify a WordPress nonce on every request before returning data.
10. THE Atlas_Theme SHALL escape all server-rendered dynamic output using `esc_html__`, `esc_url`, or `wp_kses_post`.
11. THE Atlas_Theme SHALL render all user-facing strings as translatable strings using the Text_Domain.
