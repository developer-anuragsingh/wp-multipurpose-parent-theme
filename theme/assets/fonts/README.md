# Self-Hosted Fonts

These fonts are bundled with the theme and loaded via the WordPress Font Library
API (declared in `theme/theme.json` under `settings.typography.fontFamilies`).
Self-hosting keeps First Contentful Paint fast and avoids third-party font-CDN
beacons (GDPR-friendly). No Google Fonts `<link>` is used.

| File | Family | Role | Axes | Source | License |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `inter-variable.woff2` | Inter | Body | weight (variable) | [Inter](https://github.com/rsms/inter) | SIL Open Font License 1.1 |
| `sora-variable.woff2` | Sora | Heading | weight (variable) | [Sora](https://github.com/wght/Sora) | SIL Open Font License 1.1 |

Both fonts are licensed under the [SIL Open Font License, Version 1.1](https://openfontlicense.org/),
which permits bundling and redistribution with this theme.

## Updating a font
1. Replace the `.woff2` file here, keeping the exact filename referenced in `theme.json`.
2. If the family, axes, or weights change, update the matching `fontFace` entry in `theme.json`.
3. Verify the font loads in the Site Editor and that `font-display: swap` behaviour is intact.
