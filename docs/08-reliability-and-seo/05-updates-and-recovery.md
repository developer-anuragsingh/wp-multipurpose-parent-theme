# 05. Versioning, Updates & Recovery

**File:** `docs/08-reliability-and-seo/05-updates-and-recovery.md`
**Module:** Reliability & SEO

## 1. Why This Matters
As a distributed parent theme with child themes layered on top, updates must not silently break downstream sites. A predictable versioning and recovery story is part of being production-ready.

## 2. Semantic Versioning
The theme follows SemVer (`MAJOR.MINOR.PATCH`), kept in sync across `style.css` (`Version:`) and `NEXUS_THEME_VERSION`:
* **PATCH** — bug/security fixes, no API or markup contract change.
* **MINOR** — new patterns/templates/features, backward compatible.
* **MAJOR** — breaking changes: renamed/removed template parts, changed Block Bindings source names, altered `theme.json` token slugs, or pattern slug changes that child themes may override.

Any change that can break a child theme override is a MAJOR change, by definition.

## 3. Backward Compatibility Contract
Child themes override by matching **slugs and paths** (template/part filenames, pattern slugs, `theme.json` token slugs — see `01-architecture/02-child-theme-strategy.md`). Therefore, within a major version:
* Do NOT rename or remove template parts, pattern slugs, or color/spacing/typography token slugs that child themes may target.
* Deprecate before removing: keep the old slug working for one MINOR cycle, document the replacement, then remove at the next MAJOR.
* New tokens/parts are additive and safe in MINOR releases.

## 4. Changelog & Migration Notes
* Maintain a `CHANGELOG.md` (Keep-a-Changelog style) listing every release.
* MAJOR releases ship a migration note: what changed, which child-theme overrides may be affected, and the upgrade steps.

## 5. Update Delivery
* Updates flow through the DevOps pipeline in `06-security-and-i18n/02-devops-and-i18n.md` (Git → GitHub Actions → rsync/SFTP, or Studio Sync).
* CI gates (`07-performance-and-qa/02-testing-and-ci.md`) must pass before an update is published.
* Prefer deploying to staging first; promote to production only after verification.

## 6. Backup & Rollback
* **Before any production deploy:** back up the current theme files and the database (operator responsibility; document it in the end-user guide).
* **Rollback:** keep the previous release artifact so a bad deploy can be reverted by redeploying the last-good tag (git tag per release makes this trivial). rsync/SFTP deploys should retain the prior version until the new one is verified.
* **Recovery mode:** rely on WordPress fatal-error protection / recovery mode so a broken update does not white-screen the live site; the operator receives the recovery email and can revert.

## 7. Verification Checklist
- [ ] `style.css` version and `NEXUS_THEME_VERSION` match.
- [ ] No child-overridable slug renamed/removed within a MAJOR version.
- [ ] Deprecations kept for one MINOR cycle with documented replacement.
- [ ] `CHANGELOG.md` updated; MAJOR releases include migration notes.
- [ ] Previous release artifact/tag retained for rollback.
- [ ] Staging-first deploy; CI green before publish.
