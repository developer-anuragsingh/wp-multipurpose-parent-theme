# 01. E-Commerce (WooCommerce) Architecture

**File:** `docs/05-niches-and-usecases/01-woocommerce.md`  
**Module:** Niches & Usecases  

## 1. The FSE WooCommerce Paradigm
Legacy WooCommerce themes relied heavily on PHP hooks (`woocommerce_before_shop_loop`) and shortcodes (`[woocommerce_checkout]`). This parent theme prioritizes **WooCommerce Block Theming**.

* **Blocks are the default for new stores:** since [WooCommerce 8.3 (Nov 2023)](https://docs.woocommerce.com/document/cart-checkout-blocks-support-status/), the Cart and Checkout **blocks** are the default experience for new stores — no setup needed on a block theme.
* **Shortcodes still exist:** the legacy `[woocommerce_cart]` / `[woocommerce_checkout]` shortcodes were NOT removed. Existing stores were not auto-migrated, and some extensions still require them. So the blocks are the *default*, not a *replacement* — the theme styles the block experience but must not assume shortcodes are gone.

## 2. Core Block Utilization
Instead of PHP overrides, the theme styles native WooCommerce blocks via `theme.json` design tokens.
* **Cart & Checkout Blocks:** the default checkout experience, visually styled in the Site Editor. Backed by the WooCommerce **Store API** (not standard REST), fetched/updated as the shopper interacts.
* **Product Collection Block:** used for the main shop loop and archives — native filtering, grid layouts, and pagination without custom PHP loops.
* **Mini-Cart:** integrated into the navigation header. It is **dynamic, per-shopper state** driven by the Store API and reacts to add-to-cart events, so it MUST load via a cache-safe mechanism and never be baked into page-cached HTML (see `07-performance-and-qa/04-caching-and-scaling.md` §3).

## 3. WooCommerce Block Templates (override points)
WooCommerce ships block templates that a block theme overrides by placing a same-named file under `/templates/` ([WooCommerce theming docs](https://woocommerce.com/documentation/woocommerce-codex/theming/)):
* `single-product.html` — single product
* `archive-product.html` — product catalog / shop
* `taxonomy-product_cat.html`, `taxonomy-product_tag.html`, `taxonomy-product_attribute.html` — term archives
* `product-search-results.html` — product search
* `page-cart.html`, `page-checkout.html` — cart & checkout
* `order-confirmation.html` — order received
* `page-coming-soon.html` — WooCommerce's own Coming Soon page

> **Coordinate Coming Soon:** WooCommerce provides its own `page-coming-soon.html`. The theme's generic maintenance/503 approach (`05-niches-and-usecases/05-coming-soon.md`) must not conflict with it on a WooCommerce site — prefer WooCommerce's mechanism where WooCommerce is active.

## 4. Product Metadata via Block Bindings
Custom product fields (dimensions, SKU, features) are mapped to core blocks using the **Block Bindings API** (`04-modern-wp-features/01-block-bindings.md`), bypassing custom PHP blocks. Bound meta follows the security rules — `register_post_meta()` with an `auth_callback` capability check — and every binding declares a safe fallback.