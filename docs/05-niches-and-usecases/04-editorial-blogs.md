# 04. Editorial Blogs & Magazines

**File:** `docs/05-niches-and-usecases/04-editorial-blogs.md`  
**Module:** Niches & Usecases  

## 1. Content Consumption First
Editorial layouts are optimized for readability, minimal distraction, and deep AI indexing.

## 2. GEO & Schema Integration
* **Article / BlogPosting JSON-LD:** Automatically generated to establish entity relationships for AI engines like Perplexity and ChatGPT[cite: 3].
* **Markdown Endpoint Targeting:** Blog posts are heavily optimized for the `?format=md` endpoint, ensuring AI scrapers can parse the content without theme layout clutter[cite: 3].

## 3. Dynamic Components
* **Infinite Scroll:** Implemented natively via the WordPress Interactivity API to load the next set of posts at the bottom of the archive without reloading the page.
* **Dynamic Table of Contents (ToC):** Generates anchor links automatically based on H2 and H3 blocks within the post content.