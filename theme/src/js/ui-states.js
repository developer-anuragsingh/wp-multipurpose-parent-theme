/**
 * Atlas UI-states build entry.
 *
 * The single build entry (`wp-scripts` entry `ui-states`) that composes the
 * accessible UI-state system into one shipped script module. It imports the
 * per-concern Interactivity stores from `src/js/interactivity/` — every module
 * registers on the single `atlas/ui-states` namespace (one store, multiple
 * slices), matching the `src/js/theme-mode.js` style — and imports the authored
 * stylesheet so `wp-scripts` emits `build/ui-states.css` alongside the module.
 *
 * This file only wires the import graph; the store logic lives in the imported
 * modules, each authored by its own task. Import order is load order: the shared
 * REST client first (the other modules dispatch through it), then the state
 * modules in the design's listed order.
 *
 * Server config (REST URL, nonce, slow threshold, pre-translated labels) is
 * seeded into the store by `Atlas\UI_States::register_assets()` via
 * `wp_interactivity_state`, so the modules read config from the store rather
 * than any global.
 */

// Shared fetch wrapper + retry registry — the other stores dispatch through it.
import './interactivity/rest-client.js';

// Loading / skeleton + aria-busy for content regions.
import './interactivity/loading.js';

// Per-button loading state, inline spinner, disabled activation guard.
import './interactivity/button.js';

// Offline banner toggle + retry of the last failed request.
import './interactivity/offline.js';

// Slow-request notice when elapsed time crosses the slow threshold.
import './interactivity/slow-request.js';

// Error vs maintenance classification; failure/maintenance/success notices.
import './interactivity/failure.js';

// Form validation state: aria-invalid, inline error, aria-describedby.
import './interactivity/form.js';

// Comment-submission outcome -> submitted vs pending-moderation banner.
import './interactivity/comment-feedback.js';

// Session-expiry modal: focus move + trap, nonce refresh on re-auth.
import './interactivity/session-modal.js';

// Authored stylesheet; wp-scripts emits build/ui-states.css from this import.
import '../css/ui-states.css';
