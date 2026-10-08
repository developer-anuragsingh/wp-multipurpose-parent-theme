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