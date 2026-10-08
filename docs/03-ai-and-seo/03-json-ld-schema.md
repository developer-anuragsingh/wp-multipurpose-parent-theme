# 03. JSON-LD Schema & Answer-First Patterns

**File:** `docs/03-ai-and-seo/03-json-ld-schema.md`  
**Module:** AI & SEO (GEO)

## 1. Advanced Entity Mapping via JSON-LD
AI engines rely heavily on structured data to build their Knowledge Graphs[cite: 2]. The theme must automatically output advanced JSON-LD schema markup without requiring heavy third-party SEO plugins[cite: 2].

### Core Schemas Injected:
* **Organization & WebSite:** Injected globally on the homepage for brand entity mapping[cite: 2].
* **FAQPage:** Automatically generated when the theme's "FAQ Accordion" block pattern is utilized[cite: 2].
* **Article & BlogPosting:** Applied to Editorial Blog layouts[cite: 2].
* **Quotation:** Specifically utilized for the "Quotes & Status" niche to ensure AI models attribute quotes correctly.

## 2. "Answer-First" Block Patterns
Generative AI models favor content that directly answers user queries with concrete facts at the beginning of a document[cite: 2]. 

### Pattern Design Rules:
* All hero and introductory block patterns must follow an **"Answer-First"** hierarchy[cite: 2].
* **Executive Summaries:** Blog and Corporate page patterns must include a highlighted "Key Takeaways" or "Summary" block directly below the `H1`.
* **Data Blocks:** Patterns utilizing statistics, pricing, or metrics must use semantic tables or native Grid layouts (`layout.type: "grid"`) so AI crawlers can easily parse the numeric data and highlight direct answers[cite: 2].