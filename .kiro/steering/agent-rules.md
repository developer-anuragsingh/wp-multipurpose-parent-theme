---
inclusion: always
---

# Atlas WP Parent Theme — Core Agent Rules (always on)

These are the project-wide rules every agent working in this workspace MUST follow.
The authoritative, full rule set lives in `agent-rules.md` at the theme root and is
referenced below as the single source of truth — do not duplicate or fork it here.

#[[file:agent-rules.md]]

## How the detailed docs are organized
The full technical specification lives under `docs/` (navigation matrix in
`docs/README.md`). Those deep-dive docs are loaded on demand by the conditional
steering file `docs-context.md` when you work on theme code, to keep everyday
context light. When a task touches a specific area (security, forms, WooCommerce,
SEO/GEO, performance, coding standards), consult the matching doc in `docs/` rather
than guessing.

## Local environment (WordPress Studio)
The shippable theme is the repo's `theme/` subdirectory. For local preview it is
**symlinked** into a WordPress Studio site at `wp-content/themes/atlas-theme` (slug must
equal the `atlas-theme` text domain) — edits in the repo appear live in the site. This
setup is machine-local and NOT committed (paths differ per machine). To (re)create it on
any machine: `scripts/link-to-studio.sh <studio-site-local-path>`. Full guide: `CONTRIBUTING.md` §5.

## Non-negotiable quick reference
- **Security first:** authorize (`current_user_can`) → verify intent (nonce) → sanitize input → escape output; REST/Interactivity endpoints need an explicit `permission_callback`.
- **Zero-KB JS baseline:** native Interactivity API only; no jQuery/React/Vue; no JS validation library (Zod/Yup).
- **FSE-first:** markup in `templates/`+`parts/`, tokens in `theme.json` (v3), behaviour in `inc/`.
- **i18n:** every user-facing string via gettext with the literal `atlas-theme` text domain.
- **Coding principles:** KISS, DRY, YAGNI, SOLID (SOLID only for `inc/` OOP); centralize semantic/repeated strings as constants (never user-facing strings).
- **WordPress floor:** 6.8 (core Speculation Rules, theme.json v3, Block Bindings editor UI).
- **Verify before done:** lint/build/test; state honestly what was and wasn't verified.
