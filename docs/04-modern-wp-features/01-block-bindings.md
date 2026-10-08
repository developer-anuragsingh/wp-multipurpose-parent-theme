# 01. Block Bindings API

**File:** `docs/04-modern-wp-features/01-block-bindings.md`  
**Module:** Modern WP Features  

## 1. The End of Simple Custom Dynamic Blocks
Historically, if a developer wanted to display a custom field (like a product price or custom author metadata) inside the post content, they had to build a custom dynamic PHP block or rely on shortcodes. 

This parent theme strictly utilizes the **Block Bindings API**, introduced in WordPress 6.5, to eliminate this bloat.

## 2. Core Architecture
The Block Bindings API allows you to connect custom fields (or any arbitrary database data) directly to standard WordPress core blocks (like `core/paragraph`, `core/heading`, and `core/image`).

### Implementation Workflow:
1. **Register the Binding:** Use PHP (`register_block_bindings_source()`) to define where the data comes from (e.g., post meta).
2. **Bind in HTML:** In your template parts or block patterns, add the `metadata` attribute to the core block markup.

```html
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"nexus/custom-meta","args":{"key":"product_price"}}}}} -->
<p>Default Price Fallback</p>
<!-- /wp:paragraph -->
```

## 3. Niche Use Cases
* **E-Commerce:** Binding custom product dimensions or SKU metadata directly to paragraph blocks on the single product template.
* **Quotes & Status:** Binding the `quote_author` taxonomy to a core heading block in the masonry grid.