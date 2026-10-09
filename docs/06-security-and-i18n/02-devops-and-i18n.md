# 02. DevOps, Local-to-Live & Internationalization (i18n)

**File:** `docs/06-security-and-i18n/02-devops-and-i18n.md`  
**Module:** Security & i18n  

## 1. Local Development (WordPress Studio)
Traditional environments (LocalWP, XAMPP, Docker) are heavy and require database container management. This theme is convenient to develop against **WordPress Studio**, which runs on WebAssembly (WASM) and SQLite[cite: 4].
* **Instant Spin-up:** No server configuration required[cite: 4].
* **Client Approvals:** Studio can generate temporary cloud-hosted preview URLs for client sign-offs without ngrok tunnels[cite: 4].

> **Caveats (verify before relying):**
> * **SQLite is not WordPress core's database.** It is provided through the SQLite database integration (a feature/canonical plugin that Studio bundles), not the official core engine. Production always runs MySQL/MariaDB — never ship code assuming SQLite behaviour (reinforces `agent-rules.md` §3).
> * **Studio features change.** Specific conveniences (preview URL duration, one-click sync) are product features of a fast-moving tool, not guarantees — treat exact numbers/behaviours as "subject to change" and confirm against current Studio docs.
> * Studio is a convenience, not a requirement: the theme must also develop and run on any standard MySQL-backed WordPress 6.8+ environment.

## 2. Deployment Pipeline (CI/CD)
The deployment workflow depends strictly on the hosting environment:
* **Studio Sync:** For live sites hosted on WordPress.com or Pressable, the theme utilizes one-click "Studio Sync" to push local files directly to production[cite: 4].
* **GitHub Actions:** For third-party hosts (VPS, AWS, SiteGround), the `/wp-content/themes/nexus-theme/` directory is version-controlled via Git. Merging code into the `main` branch automatically triggers a GitHub Action to deploy the updated theme files to the live server via rsync or SFTP[cite: 4].

## 3. Bilingual Support (English & Hindi)
The theme natively supports both English and Hindi languages to cater to a diverse user base[cite: 2].
* **Text Domains:** Every string in the theme (PHP or Block JSON) must be wrapped in native gettext functions (e.g., `__('Read More', 'nexus-theme')`).
* **Translation Catalogs:** The theme ships with a canonical `nexus-theme.pot` file in the `/languages/` directory, alongside pre-compiled Hindi (`hi_IN.po` / `hi_IN.mo`) localization bundles.
* **RTL & Typography:** The `theme.json` engine automatically adjusts CSS margins, padding, and font families when a Hindi locale or Right-to-Left (RTL) language is detected.