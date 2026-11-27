# Security Policy

## Supported Versions

Security fixes are released for the latest minor version of each package. Update to the newest release before reporting.

| Package | Supported | Not supported |
|---|---|---|
| Atlas Multipurpose (`atlas-theme`, parent) | the latest minor line (the `x.y` of the newest release) | older minor lines |
| Atlas Business (`atlas-business`, child) | the latest release | older releases |
| Atlas Child (`atlas-child`, child starter) | the latest release | older releases |

Releases are published on the [GitHub Releases page](https://github.com/developer-anuragsingh/wp-multipurpose-parent-theme/releases) as zip files with a `.sha256` checksum next to each one.

## Reporting a Vulnerability

**Do not open a public issue, pull request or discussion for a security problem.**

Report it privately through GitHub:

1. Open the repository's **Security** tab.
2. Click **Report a vulnerability**.
3. Fill in the advisory form.

Please include:

* the affected package and version (from `style.css`, `Version:`);
* the WordPress and PHP versions;
* the steps to reproduce, or a proof of concept;
* the impact you see (for example, who can exploit it and what they gain);
* any suggested fix.

Test only on a site you own or are allowed to test. Do not access, change or delete other people's data, and do not run denial-of-service or automated volume tests against sites you do not own.

## What Happens Next

* We aim to acknowledge a report within 5 business days and to send an initial assessment within 10 business days.
* We keep you updated while we work on a fix, and agree a disclosure date with you.
* The fix ships as a normal release (the changelog is generated from the commit history) and is announced through a GitHub Security Advisory, with a CVE requested when it applies.
* We credit you in the advisory unless you ask us not to.

## Scope

In scope:

* PHP, JavaScript, CSS, templates, parts and patterns in `theme/` and `child-themes/`;
* the theme's REST routes (`atlas/v1/...`), the contact form, the Markdown (`?format=md`) and `llms.txt` endpoints, and the HTTP security headers and Content Security Policy the theme sends;
* the release zips and the build and release scripts in `scripts/` and `.github/workflows/`.

Out of scope (report these to their own maintainers):

* WordPress core, plugins, and other themes;
* server, CDN, hosting or `wp-config.php` configuration;
* issues that need an administrator, or a user with `unfiltered_html`, to exploit them;
* Content Security Policy violations reported while the policy runs in report-only mode (development and staging), unless they show a real bypass.

## Hardening Reference

How the theme handles headers, CSP, input validation, output escaping, the contact form token and rate limit, and personal data is documented in:

* [`docs/06-security-and-i18n/01-security-and-data.md`](./docs/06-security-and-i18n/01-security-and-data.md)
* [`docs/06-security-and-i18n/04-hosting-agnostic-security-and-zero-trust.md`](./docs/06-security-and-i18n/04-hosting-agnostic-security-and-zero-trust.md)
* [`docs/08-reliability-and-seo/04-rate-limiting-and-abuse.md`](./docs/08-reliability-and-seo/04-rate-limiting-and-abuse.md)
