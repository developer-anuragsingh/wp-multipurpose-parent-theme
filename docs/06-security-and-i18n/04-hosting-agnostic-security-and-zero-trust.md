# 04. Hosting-Agnostic Security & Zero Trust Architecture

**File:** `docs/06-security-and-i18n/04-hosting-agnostic-security-and-zero-trust.md`  
**Module:** Security & i18n  

---

## 1. Hosting-Agnostic Design Philosophy

The Atlas parent theme is designed to run seamlessly across any hosting infrastructure — from commodity shared hosting (Apache/cPanel) and managed WordPress platforms (Kinsta, WP Engine) to cloud VPS environments (AWS, DigitalOcean) and edge networks (Cloudflare, Fastly).

To avoid vendor lock-in:
1. The theme **never hardcodes proprietary headers** or vendor-specific network assumptions.
2. The theme adheres strictly to **WordPress Core APIs and RFC HTTP standards**.
3. Extension points are exposed as standard **WordPress filter hooks**, allowing DevOps operators to configure custom proxy headers or infrastructure rules in child themes or `wp-config.php`.

---

## 2. Trusted Client IP Resolution (`atlas_client_ip`)

### The Challenge with Reverse Proxies & CDNs
When a WordPress site is placed behind an edge proxy or CDN (such as Cloudflare, AWS Application Load Balancer, Fastly, or Varnish), incoming requests arrive at the origin server with the proxy's IP address in `$_SERVER['REMOTE_ADDR']`.

If a rate-limiter keys strictly on `REMOTE_ADDR`:
* All visitors behind the same edge node share the rate-limit counter.
* Legitimate visitors will face unexpected `HTTP 429 Too Many Requests` blocks.

Conversely, naively trusting headers like `X-Forwarded-For` or `CF-Connecting-IP` without verification creates an IP spoofing vulnerability on non-proxy hosting environments.

### The Atlas Solution
Atlas defaults safely to `$_SERVER['REMOTE_ADDR']` and exposes the `atlas_client_ip` filter:

```php
// In theme/inc/class-contact-form.php
$ip = (string) apply_filters( Atlas\Contact_Form::FILTER_CLIENT_IP, $ip );
```

### Infrastructure Configuration Examples

#### Option A: Web Server Level (Recommended)
Configure your origin web server to restore the real client IP before PHP executes. This ensures `$_SERVER['REMOTE_ADDR']` is always accurate across WordPress core and all plugins.

* **Nginx (`ngx_http_realip_module`):**
  ```nginx
  # For Cloudflare:
  set_real_ip_from 173.245.48.0/20;
  set_real_ip_from 103.21.244.0/22;
  # ... (other Cloudflare IP ranges)
  real_ip_header CF-Connecting-IP;
  ```
* **Apache (`mod_remoteip`):**
  ```apache
  RemoteIPHeader CF-Connecting-IP
  RemoteIPTrustedProxy 173.245.48.0/20
  ```

#### Option B: Theme Filter Hook (Child Theme / `wp-config.php`)
If server-level configuration is inaccessible (e.g. shared hosting), hook `atlas_client_ip`:

* **Behind Cloudflare:**
  ```php
  add_filter( 'atlas_client_ip', function( $ip ) {
      if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
          return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
      }
      return $ip;
  } );
  ```

* **Behind AWS ALB / General Reverse Proxy:**
  ```php
  add_filter( 'atlas_client_ip', function( $ip ) {
      if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
          $forwarded = explode( ',', wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
          return sanitize_text_field( trim( $forwarded[0] ) );
      }
      return $ip;
  } );
  ```

---

## 3. Full-Page & Edge Caching Compatibility

Modern hosting environments (LiteSpeed Cache, Varnish, WP Rocket, Nginx FastCGI, Cloudflare APO) cache full HTML responses.

### Nonce Expiry Prevention
* WordPress nonces have a standard lifespan of 12–24 hours.
* If a page containing a dynamic form (like the contact form) is cached at the edge for >24 hours, the rendered nonce will become invalid, causing `403 Forbidden` errors on submission.
* **Best Practice:** Exclude dynamic form pages (`/contact/`, `/checkout/`, `/cart/`) from full-page edge caching, or configure cache TTL on form pages to under 10 hours.

### 503 Maintenance Mode Cache Hygiene
When maintenance mode is triggered via `Atlas\Maintenance`, the controller issues `nocache_headers()` alongside `status_header( 503 )`. This emits:
```http
Cache-Control: no-cache, must-revalidate, max-age=0
Expires: Wed, 11 Jan 1984 05:00:00 GMT
```
This guarantees that no intermediate CDN, proxy, or browser caches the 503 maintenance page, ensuring the site becomes immediately available the moment maintenance mode is deactivated.

---

## 4. Content Security Policy (CSP): Nonces vs. Hashes

When applying strict Content Security Policy (CSP) headers:
* `Atlas\Assets::print_theme_mode_script()` generates a per-request CSP nonce.
* However, if full HTML page caching is enabled, the nonce inside the cached HTML will become static and mismatch any dynamically generated CSP header.
* **Universal Solution:** For sites behind aggressive page caching, allow the inline script via its deterministic SHA-256 hash in your web server or CDN CSP headers:
  ```http
  script-src 'self' 'sha256-wQe28UaD2U0zZ08uM6v432b...'
  ```

---

## 5. Optional Zero Trust & WAF Hardening Guide

While Atlas runs on any hosting provider, enterprise deployments can layer Zero Trust Network Access (ZTNA) and Edge WAF rules for defense-in-depth:

### A. Zero Trust Network Access (ZTNA) for WordPress Admin
* **The Goal:** Remove public internet access to `/wp-admin` and `/wp-login.php`.
* **Implementation:**
  * **Cloudflare Access:** Create an Access Application for `example.com/wp-admin*` and `example.com/wp-login.php`. Require email OTP or Google Workspace SSO before the browser can even reach the WordPress login screen.
  * **Tailscale / WireGuard:** Restrict port 80/443 or `/wp-admin` access to internal VPN IP ranges.

### B. Cloudflare Tunnel (`cloudflared`) or Private Origin
* Eliminate open inbound ports (80/443) on the origin server.
* Use an encrypted tunnel daemon (`cloudflared` or AWS VPC Private Subnets) to connect the origin directly to the edge network.
* Origin IP address is completely hidden, preventing direct-to-IP DDoS and automated scanner bypass.

### C. Web Application Firewall (WAF)
* Enable WordPress Managed WAF rulesets to mitigate OWASP Top 10 vulnerabilities at the edge.
* Implement Rate Limiting on `/wp-login.php` (e.g. maximum 5 login requests per minute per IP) to neutralize brute-force attacks.
