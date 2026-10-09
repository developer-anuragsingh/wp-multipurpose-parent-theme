---
inclusion: always
---

# Nexus WP Parent Theme — Core Agent Rules (always on)

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

## Non-negotiable quick reference
- **Security first:** authorize (`current_user_can`) → verify intent (nonce) → sanitize input → escape output; REST/Interactivity endpoints need an explicit `permission_callback`.
- **Zero-KB JS baseline:** native Interactivity API only; no jQuery/React/Vue; no JS validation library (Zod/Yup).
- **FSE-first:** markup in `templates/`+`parts/`, tokens in `theme.json` (v3), behaviour in `inc/`.
- **i18n:** every user-facing string via gettext with the literal `nexus-theme` text domain.
- **Coding principles:** KISS, DRY, YAGNI, SOLID (SOLID only for `inc/` OOP); centralize semantic/repeated strings as constants (never user-facing strings).
- **WordPress floor:** 6.8 (core Speculation Rules, theme.json v3, Block Bindings editor UI).
- **Verify before done:** lint/build/test; state honestly what was and wasn't verified.
