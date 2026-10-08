# 02. DevOps, Local-to-Live & Internationalization (i18n)

**File:** `docs/06-security-and-i18n/02-devops-and-i18n.md`  
**Module:** Security & i18n  

## 1. Local Development (WordPress Studio)
Traditional environments (LocalWP, XAMPP, Docker) are heavy and require database container management. This theme is optimized for **WordPress Studio**, which runs entirely on WebAssembly (WASM) and SQLite[cite: 4].
* **Instant Spin-up:** No server configuration required[cite: 4].
* **Client Approvals:** Developers will use Studio's built-in feature to generate 7-day cloud-hosted preview URLs for instant client sign-offs without setting up ngrok tunnels[cite: 4].

## 2. Deployment Pipeline (CI/CD)
The deployment workflow depends strictly on the hosting environment:
* **Studio Sync:** For live sites hosted on WordPress.com or Pressable, the theme utilizes one-click "Studio Sync" to push local files directly to production[cite: 4].
* **GitHub Actions:** For third-party hosts (VPS, AWS, SiteGround), the `/wp-content/themes/nexus-theme/` directory is version-controlled via Git. Merging code into the `main` branch automatically triggers a GitHub Action to deploy the updated theme files to the live server via rsync or SFTP[cite: 4].

## 3. Bilingual Support (English & Hindi)
The theme natively supports both English and Hindi languages to cater to a diverse user base[cite: 2].
* **Text Domains:** Every string in the theme (PHP or Block JSON) must be wrapped in native gettext functions (e.g., `__('Read More', 'nexus-theme')`).
* **Translation Catalogs:** The theme ships with a canonical `nexus-theme.pot` file in the `/languages/` directory, alongside pre-compiled Hindi (`hi_IN.po` / `hi_IN.mo`) localization bundles.
* **RTL & Typography:** The `theme.json` engine automatically adjusts CSS margins, padding, and font families when a Hindi locale or Right-to-Left (RTL) language is detected.