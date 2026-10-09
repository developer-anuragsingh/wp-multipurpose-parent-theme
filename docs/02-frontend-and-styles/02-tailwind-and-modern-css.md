# 02. Modern CSS & Tailwind Integration

**File:** `docs/02-frontend-and-styles/02-tailwind-and-modern-css.md`  
**Module:** Frontend & Styles  

## 1. The "theme.json First" Principle
In the FSE era, massive CSS frameworks (like Bootstrap) are obsolete and actively discouraged. Bootstrap injects heavy opinionated defaults (~30KB CSS) that conflict with WordPress native blocks[cite: 3]. 
Our primary rule is: **90% of the site's styling must be handled natively via `theme.json` and Gutenberg Appearance Tools**[cite: 3]. 

## 2. Modern CSS Architecture
When writing custom CSS for specific niche components, developers must utilize modern CSS standards instead of relying on heavy utility classes or JavaScript fallbacks:
* **Cascade Layers (`@layer`):** Used to explicitly organize CSS specificity and prevent custom styles from unintentionally overriding WordPress core block styles.
* **Container Queries (`@container`):** Used for component-based responsive design instead of viewport-based media queries (`@media`), allowing block patterns to adapt gracefully regardless of where they are placed (e.g., inside a narrow sidebar or a full-width column)[cite: 3].
* **The `:has()` Selector:** Utilized for advanced parent-element state styling based on child elements, eliminating the need for JavaScript state toggling in many UI components[cite: 3].
* **Native Grid & Negative Margins:** WordPress natively supports CSS Grid (`layout.type: "grid"`) and negative margins. Always prefer native block grid settings over writing custom Flexbox/Grid CSS[cite: 3].

## 3. Tailwind CSS (Strictly Scoped — Tailwind v4)
Tailwind CSS is permitted **only** for highly complex, isolated custom blocks where native tools fall short.
* **Utility-First / JIT:** In Tailwind v4 the engine generates only the utility classes actually found in your source — there is no legacy `purge` step and nothing to "tree-shake" after the fact; unused utilities are simply never generated. Ensure the content/source paths are configured so the scanner sees your block markup.
* **Use `@utility`, NOT `@layer components`:** In v4, `@layer components` is emitted as a plain native CSS cascade layer and its contents are **always shipped whether used or not** — so custom classes defined there are never eliminated and will bloat the bundle ([Tailwind discussion](https://github.com/tailwindlabs/tailwindcss/discussions/20351)). Define reusable custom classes with the `@utility` API instead, so they participate in on-demand generation.
* **Bundle Size Budget:** Keep the compiled Tailwind CSS small — treat **≤ 10 KB** as a *project budget* (see `07-performance-and-qa/01-performance-budget.md`), not a guarantee from the tool. Verify the real size in CI rather than assuming.
* **Editor Isolation:** Tailwind classes must not leak into the global Gutenberg editor scope. Scope them to specific template parts or custom block wrappers to avoid editor conflicts.

> Note: the native CSS `@layer` guidance in §2 (for organizing your own hand-written CSS specificity) is unrelated to Tailwind's `@layer components` directive discussed here — do not conflate the two.

## 4. Media & Asset Optimization
* **Image Formats:** All CSS background images and theme-provided assets must use **AVIF** or **WebP** formats by default to ensure optimal performance[cite: 3].
* **Lazy Loading:** External media, videos, and iframes must utilize native browser lazy loading (`loading="lazy"`) to reduce server load and preserve sub-second First Contentful Paint (FCP)[cite: 3].