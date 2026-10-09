# 02. Traditional SEO Fundamentals

**File:** `docs/08-reliability-and-seo/02-traditional-seo.md`
**Module:** Reliability & SEO

> The GEO docs (`03-ai-and-seo/`) optimize for AI engines. This doc covers the **classic SEO** foundation for Google/Bing. Both run together; GEO does not replace fundamentals.

## 1. Discoverability Baseline
* **XML Sitemap:** rely on WordPress core sitemaps (`wp-sitemap.xml`) or integrate cleanly with an SEO plugin if present. Do not hardcode a competing sitemap. Keep the GEO `llms.txt` separate from the XML sitemap (different audiences).
* **robots.txt:** sane defaults — allow crawling of public content, disallow `wp-admin` (except `admin-ajax.php`), reference the sitemap. Must NOT block CSS/JS (Google needs them to render).
* **AI crawler directives:** `robots.txt` is also where AI crawler access is controlled (unlike `llms.txt`, these directives are honoured by major providers). Expose an operator-configurable allow/block policy for `GPTBot`, `OAI-SearchBot`, `ClaudeBot`, `PerplexityBot`, and `Google-Extended`. Default: allow reputable AI crawlers for a citation-seeking public site; let the operator block per their business/privacy choice. Full rationale in `03-ai-and-seo/01-geo-llms-txt.md` §3.
* **Canonical URLs:** every page emits a self-referential canonical. The `?format=md` endpoint sets its canonical to the HTML URL AND marks itself `noindex` to avoid duplicate-content issues (reconciles the note in `03-ai-and-seo/02-markdown-endpoints.md`).

## 2. On-Page Metadata
* **Title & description:** unique, template-aware `<title>` (title-tag support is enabled) and meta description per page type. Defer to an SEO plugin's values when one is active rather than double-outputting.
* **Open Graph & Twitter Cards:** emit `og:title`, `og:description`, `og:image`, `og:type`, `og:url` and `twitter:card` so shared links render rich previews. Provide a theme default `og:image` fallback.
* **Structured heading hierarchy:** one `h1` per page, logical order — shared with the a11y contract (`04-accessibility.md`).

## 3. Crawlability & Indexing Control
* **Noindex where appropriate:** search results pages, thin tag archives (configurable), the `?format=md` endpoint, and maintenance mode.
* **Pagination:** paginated archives/infinite scroll expose crawlable paginated URLs (not JS-only) so content remains discoverable.
* **Clean URLs:** rely on pretty permalinks; avoid query-string-only content routes for indexable pages.

## 4. Performance = SEO
Core Web Vitals are ranking signals. The perf budget (`07-performance-and-qa/01-performance-budget.md`) and this doc reinforce each other — LCP/INP/CLS targets are SEO requirements, not just UX.

## 5. International SEO
* **hreflang:** for the bilingual English/Hindi setup, emit `hreflang` alternates so search engines serve the right locale.
* **Lang attribute:** `<html lang>` reflects the active locale.

## 6. Media SEO
* Descriptive `alt` text (also a11y), descriptive filenames, AVIF/WebP, and images included where relevant in the sitemap.

## 7. Verification Checklist
- [ ] XML sitemap present and valid; not duplicated against a plugin.
- [ ] robots.txt allows CSS/JS, disallows admin, references sitemap.
- [ ] Self-referential canonicals; `?format=md` is canonical→HTML + noindex.
- [ ] Unique title/description; OG + Twitter tags with default image.
- [ ] Noindex on search/maintenance/md endpoints.
- [ ] Paginated content crawlable (not JS-only).
- [ ] hreflang + `<html lang>` correct for en/hi.
