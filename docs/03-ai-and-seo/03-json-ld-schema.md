# 03. JSON-LD Schema & Answer-First Patterns

**File:** `docs/03-ai-and-seo/03-json-ld-schema.md`  
**Module:** AI & SEO (GEO)

## 1. Advanced Entity Mapping via JSON-LD
AI engines rely heavily on structured data to build their Knowledge Graphs[cite: 2]. The theme must automatically output advanced JSON-LD schema markup without requiring heavy third-party SEO plugins[cite: 2].

### Core Schemas Injected:
* **Organization & WebSite:** Injected globally on the homepage for brand entity mapping — the foundation of brand identity for both search and AI engines[cite: 2].
* **Article & BlogPosting:** Applied to Editorial Blog layouts, including `author`, `datePublished`/`dateModified`, and `publisher` for E-E-A-T signals.
* **Breadcrumb:** Site hierarchy for navigation context (still a supported Google rich result).
* **Product & Review/AggregateRating:** For the WooCommerce niche (still supported rich results; see also the I/O 2026 feed-layer changes for commerce eligibility).
* **Quotation:** Utilized for the "Quotes & Status" niche to help AI models attribute quotes correctly.

### FAQPage & HowTo — Deprecated as Rich Results (important)
As of **May 7, 2026, Google no longer displays FAQ rich results**, and **HowTo is likewise unsupported** — the rich-result appearance, Search Console reporting, and Rich Results Test support were removed through mid-2026 ([Google FAQ docs](https://developers.google.com/search/docs/appearance/structured-data/faqpage)).
* **Do NOT present FAQPage/HowTo as a Google search feature.** It no longer produces one.
* The `FAQPage` type remains *valid* schema.org markup and may still carry value for Answer Engine Optimization (AI citation), so the theme MAY emit it where an FAQ pattern is used — but strictly as optional AEO support, never as a promised rich result.
* Prioritize the schemas that still earn Google features: Article, Product, Breadcrumb, Review, Organization.

Content was rephrased for compliance with licensing restrictions.

## 2. "Answer-First" Block Patterns
Generative AI models favor content that directly answers user queries with concrete facts at the beginning of a document[cite: 2]. 

### Pattern Design Rules:
* All hero and introductory block patterns must follow an **"Answer-First"** hierarchy[cite: 2].
* **Executive Summaries:** Blog and Corporate page patterns must include a highlighted "Key Takeaways" or "Summary" block directly below the `H1`.
* **Data Blocks:** Patterns utilizing statistics, pricing, or metrics must use semantic tables or native Grid layouts (`layout.type: "grid"`) so AI crawlers can easily parse the numeric data and highlight direct answers[cite: 2].

## 3. Entity, Authorship & E-E-A-T Signals
Modern GEO is driven less by any single file and more by how strongly an engine can identify and trust your brand entity. The theme emits the structured signals that support this:
* **Brand entity graph:** `Organization` with `name`, `logo`, `url`, and `sameAs` (official social/profile URLs) so engines resolve the brand to one entity.
* **Authorship:** `Article`/`BlogPosting` carry a `Person` `author` with `sameAs`, plus `publisher` — supporting Experience, Expertise, Authoritativeness, Trust (E-E-A-T), which influences whether AI engines cite the source.
* **Freshness:** accurate `datePublished` / `dateModified` — AI engines favour current, maintained content.
* **Consistency:** the same entity names/URLs across schema, visible content, and metadata; contradictory signals weaken entity resolution.

> Off-site validation (third-party mentions, reviews, consistent citations elsewhere) is a major factor in which sources AI engines choose, but it is earned off-site and cannot be produced by theme markup. The theme's job is to make the on-site entity signals clean and unambiguous.

## 4. AI Crawler Access
Structured data only helps if crawlers may read the page. Access control is defined in `03-ai-and-seo/01-geo-llms-txt.md` and `08-reliability-and-seo/02-traditional-seo.md` (robots.txt for AI user agents such as GPTBot, OAI-SearchBot, ClaudeBot, PerplexityBot, and Google-Extended). Blocking a crawler there makes any schema on the page moot for that engine.
