# 05. Coming Soon & Landing Pages

**File:** `docs/05-niches-and-usecases/05-coming-soon.md`  
**Module:** Niches & Usecases  

## 1. The Blank Canvas Requirement
Landing pages and "Coming Soon" modes require high conversion focus, meaning global site headers and footers (which contain distracting navigation links) must be removed[cite: 2].

## 2. Custom Template Architecture
* **`blank.html` Template:** The theme includes a dedicated FSE template (`templates/blank.html`) that entirely omits the standard `<!-- wp:template-part {"slug":"header"} /-->` and footer blocks[cite: 2].
* **Decoupled Parts:** If a landing page needs a simplified footer (e.g., just a copyright line and privacy link), it utilizes a decoupled template part (`parts/footer-landing.html`) to ensure edits do not affect the global site footer[cite: 2].

## 3. 503 Maintenance Mode
When the "Coming Soon" mode is active, the theme intercepts requests and returns a `503 Service Unavailable` HTTP status code to prevent search engines from indexing the incomplete site.

### How the maintenance page is rendered
`Atlas\Maintenance` serves `templates/maintenance.html` as a **block template**: it publishes the resolved template (child theme first, then parent; falling back to `blank`) to core's `template-canvas.php`, so the page is a full HTML document with `<head>`, global styles, header/footer parts and the Security_Headers. A `.html` template must never be returned straight from `template_include`: PHP would include it raw and print unrendered block comments with no `<html>`, head or CSS. If no block template resolves (e.g. a classic theme), the original template is kept; the `503`, `Retry-After`, `noindex` and `nocache` headers are still sent.
Enable with `define( 'ATLAS_MAINTENANCE', true );`, the `atlas_maintenance_enabled` filter, or the `atlas_maintenance_mode` option (e.g. `wp option update atlas_maintenance_mode 1`). Users with `manage_options` bypass it.
