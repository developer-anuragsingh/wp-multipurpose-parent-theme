# 01. Generative Engine Optimization (GEO) & llms.txt

**File:** `docs/03-ai-and-seo/01-geo-llms-txt.md`  
**Module:** AI & SEO (GEO)

## 1. The Shift to Generative Engine Optimization (GEO)
Traditional SEO optimizes for click-throughs from search result lists. Generative Engine Optimization (GEO) optimizes content so Large Language Models (LLMs) and AI agents (ChatGPT, Perplexity, Gemini, Claude) can easily retrieve, synthesize, and cite it.

## 2. The `llms.txt` Standard — Optional, Low-ROI (2026 reality)
AI agents prefer clean, structured markdown over parsing complex HTML DOM trees, and `llms.txt` proposes a curated markdown "table of contents" for them.

> **Status check (2026):** Treat `llms.txt` as **optional and experimental, not a core discoverability mechanism**. It is not a ratified web standard (no IETF/W3C), and no major AI provider has confirmed it is consumed in production. 2026 server-log studies found the vast majority of `llms.txt` files received essentially zero requests from named AI crawlers (GPTBot, ClaudeBot, PerplexityBot, Googlebot), and the largest citation studies found no measurable lift. Sources: [Ahrefs/PPC.land](https://ppc.land/llms-txt-adoption-rises-8-8x-but-97-of-files-get-zero-ai-requests/), [signals.sh](https://signals.sh/blog/does-llms-txt-actually-work-adoption-reality), [loudface](https://www.loudface.co/blog/llms-txt).
>
> **Where it genuinely helps:** feeding clean docs to AI coding assistants (Cursor, Claude Code, etc.) at inference time — i.e. documentation-heavy sites. For general marketing/content sites the payoff today is low.
>
> **Guidance for this theme:** generating `llms.txt` is cheap and not harmful, so it MAY be offered as an opt-in feature — but the theme's AI/SEO strategy must NOT depend on it. Real discoverability comes from clean semantic HTML, entity/E-E-A-T signals (`03-json-ld-schema.md`), answer-first content, Core Web Vitals, and allowing AI crawlers (§ below). Content was rephrased for compliance with licensing restrictions.

### Implementation Details:
The theme will programmatically generate and maintain two files at the root directory:
1. **`/llms.txt`**: A concise, plain-text Markdown digest containing the site's purpose, brand identity, and a directory of the most important content.
2. **`/llms-full.txt`**: An expanded version that includes the full markdown text of core pages (e.g., About Us, Core Services, Main Documentation) to feed AI context windows instantly[cite: 2].

### Hook Architecture:
Instead of static files, the theme will use WordPress rewrite rules to dynamically generate this content based on published pages and global `theme.json` metadata, ensuring the AI crawler always receives up-to-date information.

## 3. AI Crawler Access Policy (the actual lever)
Unlike `llms.txt`, `robots.txt` directives for AI user agents are read and generally respected by the major providers, so this is where real control lives.
* **Decision per site:** the theme exposes a clear, documented way for the operator to **allow or block** named AI crawlers — e.g. `GPTBot`, `OAI-SearchBot` (OpenAI), `ClaudeBot` (Anthropic), `PerplexityBot` (Perplexity), and `Google-Extended` (Google AI training). This is a business/privacy decision, not a default to hardcode.
* **Default stance:** for a public marketing/content site aiming for AI citations, allow reputable AI crawlers; block only what the operator chooses. Never block CSS/JS (needed for rendering).
* **Consistency with privacy:** blocking choices here align with the data-protection rules — AI-readable surfaces expose only already-public content (`06-security-and-i18n/03-data-protection.md` §8).
* **Cross-reference:** base `robots.txt` rules live in `08-reliability-and-seo/02-traditional-seo.md`.

## 4. What Actually Drives AI Citations (priority order)
1. Clean, crawlable, semantic HTML with answer-first structure.
2. Strong, consistent entity + E-E-A-T signals (`03-json-ld-schema.md`).
3. Good Core Web Vitals and no crawler blocking.
4. Off-site authority: third-party mentions, reviews, consistent citations (earned, not markup).
5. `llms.txt` — optional, last, low-ROI (see §2).
