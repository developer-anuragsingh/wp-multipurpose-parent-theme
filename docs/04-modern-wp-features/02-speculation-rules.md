# 02. Speculation Rules API (Prefetching)

**File:** `docs/04-modern-wp-features/02-speculation-rules.md`  
**Module:** Modern WP Features  

## 1. Instant Page Loads
To provide an app-like, ultra-fast user experience, this theme uses the **Speculation Rules API** — the browser anticipates the user's next navigation and fetches the page in the background, so a click renders near-instantly.

## 2. Use WordPress Core — Do NOT Hand-Inject
**Important (WP 6.8+):** Speculative Loading is built into WordPress Core as of [6.8](https://make.wordpress.org/core/2025/03/06/speculative-loading-in-6-8/). Core already emits the `<script type="speculationrules">` tag automatically on the front end. The theme MUST NOT hand-inject its own `<script type="speculationrules">` into `<head>` — doing so collides with core's output and produces duplicate/conflicting rules.

* **Core defaults:** on sites with pretty permalinks, for anonymous users, core adds a conservative `prefetch` rule by default ([core source](https://core.trac.wordpress.org/browser/trunk/src/wp-includes/speculative-loading.php)). This already covers the "instant load" goal with zero theme code.
* **This theme's floor is WP 6.8** precisely so this core behaviour is guaranteed (see `docs/README.md` target environment).

## 3. Tuning via the Core Filter
To adjust mode (`prefetch` vs `prerender`) or eagerness, use the official filter — not a hardcoded script tag:

```php
add_filter(
	'wp_speculation_rules_configuration',
	static function ( $config ) {
		// Example: upgrade to prerender at moderate eagerness.
		// Keep conservative/moderate on commerce sites to avoid prefetch storms.
		$config['mode']      = 'prefetch';   // or 'prerender'
		$config['eagerness'] = 'moderate';   // conservative | moderate | eager
		return $config;
	}
);
```

See [`wp_speculation_rules_configuration`](https://developer.wordpress.org/reference/hooks/wp_speculation_rules_configuration/) and [`wp_get_speculation_rules_configuration()`](https://developer.wordpress.org/reference/functions/wp_get_speculation_rules_configuration/). Default to `prefetch` + `moderate` to balance speed against server/bandwidth cost; reserve `prerender` for mostly-static content.

## 4. Excluding Sensitive Routes
Dynamic, non-idempotent, and per-user routes (`?add-to-cart=`, `/cart/`, `/checkout/`, `/my-account/`, `wp-admin`) must be excluded so no background request mutates state or warms a per-user page. Core already excludes logged-in sessions and admin; add theme/commerce exclusions through the same configuration filter (append `href_exclude_paths`) rather than a custom script:

```php
add_filter(
	'wp_speculation_rules_configuration',
	static function ( $config ) {
		$config['href_exclude_paths'] = array_merge(
			$config['href_exclude_paths'] ?? array(),
			array( '/cart/*', '/checkout/*', '/my-account/*', '/*add-to-cart=*' )
		);
		return $config;
	}
);
```

## 5. Maintenance / Coming Soon
When maintenance (503) mode is active, speculative loading must be suppressed so browsers never prefetch error responses (ties to `08-reliability-and-seo/01-error-handling.md` §2 and `05-niches-and-usecases/05-coming-soon.md`).

## 6. Verification Checklist
- [ ] No hand-injected `<script type="speculationrules">` anywhere in the theme.
- [ ] Mode/eagerness tuned only via `wp_speculation_rules_configuration`.
- [ ] Commerce/account/admin routes excluded via `href_exclude_paths`.
- [ ] Speculation suppressed in 503/maintenance mode.
- [ ] Verified on WP 6.8+ (the theme's minimum).