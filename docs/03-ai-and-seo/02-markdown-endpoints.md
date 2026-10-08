# 02. Clean Markdown Endpoints (`?format=md`)

**File:** `docs/03-ai-and-seo/02-markdown-endpoints.md`  
**Module:** AI & SEO (GEO)

## 1. The HTML/DOM Parsing Problem
AI web scrapers and summarizers struggle with heavy HTML elements, nested `div`s, and inline scripts. Serving raw semantic text drastically improves the chances of an AI correctly understanding and citing your content[cite: 2].

## 2. The `?format=md` Endpoint Strategy
The theme implements a custom endpoint parameter. By appending `?format=md` to any post or page URL, the theme intercepts the standard HTML rendering pipeline and outputs a pure Markdown representation of the content[cite: 2].

### Technical Execution:
1. **Template Redirect Hook:** A custom hook on `template_redirect` listens for the `format=md` query variable.
2. **Content Parsing:** If detected, the theme parses the `post_content` (Gutenberg blocks).
3. **HTML to Markdown Conversion:** It strips all structural theme clutter (headers, footers, sidebars, interactive directives) and converts standard blocks (paragraphs, headings, lists, quotes) into pure Markdown[cite: 2].
4. **Header Modification:** The response is sent with a `Content-Type: text/markdown` or `text/plain` HTTP header, signaling to the AI agent that this is a machine-readable document.

## 3. SEO Protection
To prevent traditional search engines (like Googlebot) from penalizing the site for duplicate content, the Markdown endpoints must automatically include a canonical link header pointing to the standard HTML URL.