# 03. Synced Pattern Overrides

**File:** `docs/04-modern-wp-features/03-synced-pattern-overrides.md`  
**Module:** Modern WP Features  

## 1. The Challenge with Reusable Blocks
Previously, "Reusable Blocks" (now Synced Patterns) locked both the layout and the content. If you changed the text on one page, it changed globally. If you unsynced the pattern, you lost the ability to update the design globally later.

## 2. Synced Pattern Overrides Architecture
Introduced in WordPress 6.6, **Synced Pattern Overrides** allow you to create a global layout pattern while unlocking specific content parts (like text or images) to be overridden on individual pages[cite: 2].

### How the Theme Uses It:
This is heavily utilized in the **Corporate** and **Landing Page** niches. 
For example, the theme includes a standard "Testimonial Card" pattern. The structural design (border radius, drop shadows, font sizes) is locked and synced globally. However, the paragraph (the review text) and the image (the avatar) are marked as "overridable."

## 3. Developer Implementation
When registering patterns via PHP in the `/patterns/` directory, specific blocks inside the pattern must be explicitly named and allowed to be overridden using the `__metadata` attribute.

```html
<!-- wp:paragraph {"metadata":{"name":"Review Text","bindings":{"__default":{"source":"core/pattern-overrides"}}}} -->
<p>This is the default testimonial text that users will overwrite.</p>
<!-- /wp:paragraph -->
```
By utilizing this feature, enterprise clients can maintain strict brand consistency across hundreds of landing pages while safely editing the localized content.