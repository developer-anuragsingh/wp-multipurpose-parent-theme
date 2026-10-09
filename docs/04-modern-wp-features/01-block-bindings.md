# 01. Block Bindings API

**File:** `docs/04-modern-wp-features/01-block-bindings.md`  
**Module:** Modern WP Features  

## 1. The End of Simple Custom Dynamic Blocks
Historically, if a developer wanted to display a custom field (like a product price or custom author metadata) inside the post content, they had to build a custom dynamic PHP block or rely on shortcodes. 

This parent theme strictly utilizes the **Block Bindings API**, introduced in WordPress 6.5 and significantly expanded in 6.7, to eliminate this bloat. Supported core blocks include `paragraph`, `heading`, `image`, and `button`, with bindable attributes such as `content`, `url`, and `alt`.

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

## 4. Editor-Side Editing (WordPress 6.7+)
WordPress 6.7 added the ability to **read and edit bound custom-field values directly in the Editor UI**, not just render them on the front end ([editor experience in 6.7](https://make.wordpress.org/core/2024/10/21/block-bindings-improvements-to-the-editor-experience-in-6-7/), [getting/setting values](https://developer.wordpress.org/news/2024/10/getting-and-setting-block-binding-values-in-the-editor/)). The theme should register sources that support this so editors manage meta inline rather than through separate fields.

To be editable in the Editor, a registered source provides:
* `label` — a human-readable name shown in the Editor UI.
* `get_value_callback` — returns the value for front-end render AND editor display.
* `uses_context` — declares the context (e.g. `postId`, `postType`) the callback needs.
* For editing, the bound value must map to writable post meta registered with `register_post_meta()` (with `show_in_rest` and a `single` string type) so the Editor can persist changes.

```php
register_block_bindings_source(
	'nexus/custom-meta',
	array(
		'label'              => __( 'Nexus Custom Meta', 'nexus-theme' ),
		'get_value_callback' => 'nexus_bindings_get_meta_value',
		'uses_context'       => array( 'postId' ),
	)
);
```

> This is the modern, recommended pattern as of the theme's 6.8 floor: pair a registered binding source with properly registered, REST-exposed post meta so values render on the front end and remain editable in the Site/Post Editor. Follow the security rules (`06-security-and-i18n/01-security-and-data.md`) — `register_post_meta()` needs an `auth_callback` with a capability check, and every binding provides a safe fallback (`08-reliability-and-seo/01-error-handling.md`).
