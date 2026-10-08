# 01. Generative Engine Optimization (GEO) & llms.txt

**File:** `docs/03-ai-and-seo/01-geo-llms-txt.md`  
**Module:** AI & SEO (GEO)

## 1. The Shift to Generative Engine Optimization (GEO)
Traditional SEO optimizes for click-throughs from search result lists. Generative Engine Optimization (GEO) optimizes content so Large Language Models (LLMs) and AI agents (ChatGPT, Perplexity, Gemini, Claude) can easily retrieve, synthesize, and cite it.

## 2. The `llms.txt` Standard
AI agents prefer clean, structured markdown over parsing complex HTML DOM trees. This theme implements the emerging `llms.txt` standard to act as an "AI Sitemap".

### Implementation Details:
The theme will programmatically generate and maintain two files at the root directory:
1. **`/llms.txt`**: A concise, plain-text Markdown digest containing the site's purpose, brand identity, and a directory of the most important content.
2. **`/llms-full.txt`**: An expanded version that includes the full markdown text of core pages (e.g., About Us, Core Services, Main Documentation) to feed AI context windows instantly[cite: 2].

### Hook Architecture:
Instead of static files, the theme will use WordPress rewrite rules to dynamically generate this content based on published pages and global `theme.json` metadata, ensuring the AI crawler always receives up-to-date information.