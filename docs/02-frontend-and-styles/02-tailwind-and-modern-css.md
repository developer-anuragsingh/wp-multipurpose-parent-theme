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

## 3. Tailwind CSS (Strictly Scoped)
Tailwind CSS is permitted **only** for highly complex, isolated custom blocks where native tools fall short. 
* **Utility-First Approach:** Tailwind provides rapid utility classes without shipping unused CSS, making it the best alternative to heavy frameworks[cite: 3].
* **Bundle Size Limit:** The Tailwind compiler must be configured to aggressively tree-shake the output. The final compiled CSS bundle must remain strictly under **10 KB** for production[cite: 3].
* **Editor Isolation:** Tailwind classes must not leak into the global Gutenberg editor scope. They should be strictly scoped to specific template parts or custom block wrappers to avoid editor conflicts.

## 4. Media & Asset Optimization
* **Image Formats:** All CSS background images and theme-provided assets must use **AVIF** or **WebP** formats by default to ensure optimal performance[cite: 3].
* **Lazy Loading:** External media, videos, and iframes must utilize native browser lazy loading (`loading="lazy"`) to reduce server load and preserve sub-second First Contentful Paint (FCP)[cite: 3].