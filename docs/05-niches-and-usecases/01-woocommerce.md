# 01. E-Commerce (WooCommerce) Architecture

**File:** `docs/05-niches-and-usecases/01-woocommerce.md`  
**Module:** Niches & Usecases  

## 1. The FSE WooCommerce Paradigm
Legacy WooCommerce themes relied heavily on PHP hooks (`woocommerce_before_shop_loop`) and shortcodes (`[woocommerce_checkout]`). This parent theme strictly enforces **WooCommerce Block Theming**. 

## 2. Core Block Utilization
Instead of PHP overrides, the theme utilizes native WooCommerce blocks tailored via `theme.json` design tokens.
* **Cart & Checkout Blocks:** Replaces legacy shortcodes. These blocks are visually styled within the Site Editor, allowing friction-free checkout flows[cite: 2].
* **Product Collection Block:** Used for the main shop loop and category archives. It natively supports filtering, grid layouts, and pagination without writing custom PHP loops[cite: 2].
* **Mini-Cart:** Integrated seamlessly into the navigation header via template parts.

## 3. Product Page Template (`single-product.html`)
The single product layout is managed entirely via the FSE template hierarchy. 
Custom fields (e.g., product dimensions, specific features) are mapped to paragraph blocks using the **Block Bindings API**, bypassing the need to create custom PHP blocks for niche product data.