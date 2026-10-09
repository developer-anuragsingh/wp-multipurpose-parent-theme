# 01. WordPress Interactivity API

**File:** `docs/02-frontend-and-styles/01-interactivity-api.md`  
**Module:** Frontend & Styles  

## 1. The Zero-KB JavaScript Philosophy
This parent theme strictly prohibits the enqueueing of legacy JavaScript libraries like jQuery or heavy frontend frameworks (e.g., React or Vue) for standard UI behaviors[cite: 3]. 

Instead, it leverages the native **WordPress Interactivity API** as the standard for frontend behavior[cite: 3]. This ensures that pages without interactive components ship with **0 KB of client-side JavaScript**[cite: 3].

> **Version note:** the API shipped privately in WP 6.4 (Core-blocks only) and became [public and developer-usable in WP 6.5](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/). It already powers several Core blocks — Search, Query, Navigation, and File — so the theme builds on a battle-tested, Core-maintained foundation rather than a bespoke script layer. The theme's 6.8 floor comfortably exceeds the 6.5 requirement.

## 2. Core Architecture
The Interactivity API uses a lightweight declarative approach[cite: 3]. It relies on HTML directives connected to a centralized JavaScript store[cite: 3]. Under the hood, it is powered by **Preact and Preact Signals**, guaranteeing lightning-fast DOM updates without heavy virtual DOM diffing overhead[cite: 3].

### Key Directives Used in the Theme:
* `data-wp-interactive`: Defines the namespace for the interactive block[cite: 3].
* `data-wp-context`: Stores the local state (e.g., `{ "isOpen": false }`).
* `data-wp-on--click`: Binds event listeners directly in the HTML markup[cite: 3].
* `data-wp-bind--aria-expanded`: Dynamically updates accessibility attributes based on the state.

## 3. Implementation Use Cases
The theme utilizes the Interactivity API for the following core interactive features:
1. **Mobile Navigation Drawer:** Toggling the off-canvas menu with fluid animations and focus trapping.
2. **Dark/Light Mode Toggle:** Managing state switching seamlessly without page reloads.
3. **Quotes & Status (Copy to Clipboard):** Instant "click-to-copy" functionality for the Content/Blog niche.
4. **Infinite Scroll:** Loading subsequent blog or WooCommerce product pages dynamically.

## 4. Accessibility (a11y) Standards
All interactive components must be fully accessible. State changes (like opening a dropdown or off-canvas menu) must automatically update the corresponding ARIA labels.

Example implementation for a dropdown button:
```html
<button
    data-wp-on--click="actions.toggleDropdown"
    data-wp-bind--aria-expanded="context.isOpen"
    aria-controls="dropdown-menu">
    Menu
</button>
```

## 5. Security
Interactivity API server actions (submitting a form, adding a product to the cart, loading infinite-scroll data) are REST routes under the hood, so they MUST follow the full theme security model — not nonce verification alone. See `06-security-and-i18n/01-security-and-data.md` for the authoritative rules.

Every data-mutating or non-public Interactivity API endpoint requires, in order:
1. **Authorization:** an explicit `permission_callback` with a `current_user_can()` capability check. A nonce is NOT authorization.
2. **Intent (CSRF):** the standard `wp_rest` nonce the API attaches via the `X-WP-Nonce` header — in addition to, never instead of, the capability check.
3. **Input validation & sanitization:** validate and sanitize every argument through the route's `args` schema (`validate_callback` / `sanitize_callback`).
4. **Output escaping:** escape any returned data at the point it is rendered to the DOM.