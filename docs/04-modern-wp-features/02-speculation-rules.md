# 02. Speculation Rules API (Prefetching)

**File:** `docs/04-modern-wp-features/02-speculation-rules.md`  
**Module:** Modern WP Features  

## 1. Instant Page Loads
To provide an app-like, ultra-fast user experience, this theme integrates the **Speculation Rules API**[cite: 2]. 

Instead of waiting for a user to click a link and then requesting the page from the server, this technology tells the browser to anticipate the user's action and fetch the page in the background[cite: 2]. When the user actually clicks, the page renders instantly with zero loading time[cite: 2].

## 2. Implementation Strategy
The theme injects a JSON configuration into the `<head>` of the document.

### Rule Configuration:
* **Hover Trigger:** The primary rule is set to trigger when a user hovers over an internal link (e.g., a blog post card or a WooCommerce product).
* **Prerender vs. Prefetch:** To save bandwidth, the theme defaults to `prefetch` (downloading the HTML and critical assets) rather than full `prerender` (which executes JavaScript invisibly).

```html
<script type="speculationrules">
{
  "prefetch": [
    {
      "source": "document",
      "where": {
        "and": [
          { "href_matches": "/*" },
          { "not": { "href_matches": "/wp-admin/*" } },
          { "not": { "href_matches": "*?add-to-cart=*" } }
        ]
      },
      "eagerness": "moderate"
    }
  ]
}
</script>
```

## 3. Security & E-Commerce Exclusions
Crucially, dynamic routes (like `?add-to-cart=`, `/checkout/`, or `wp-admin`) are strictly excluded from speculation rules to prevent accidental background database mutations or cart anomalies.