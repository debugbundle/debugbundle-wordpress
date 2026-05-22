# DebugBundle WordPress Plugin

WordPress plugin for DebugBundle.

![CI](https://img.shields.io/github/actions/workflow/status/debugbundle/debugbundle-wordpress/ci.yml?branch=main&label=ci)
![Version](https://img.shields.io/badge/version-0.1.7-blue)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)

Use this plugin to capture backend PHP/WordPress incidents and user-facing browser incidents from a WordPress site. Browser events are sent through a same-origin WordPress REST relay so the DebugBundle project token stays server-side.

## Requirements

- WordPress 6.5 or newer
- PHP 8.2 or newer

## Installation

1. Download the plugin ZIP from the `debugbundle-wordpress` GitHub Release.
2. In WordPress, open **Plugins -> Add New -> Upload Plugin**.
3. Upload and activate the ZIP.
4. Open **Settings -> DebugBundle**.
5. Paste your DebugBundle project token and save.
6. Use the backend and frontend test-event buttons to verify delivery.

The plugin can also read the project token from `wp-config.php`:

```php
define('DEBUGBUNDLE_PROJECT_TOKEN', 'dbundle_proj_xxxxxxxxxxxx');
```

## What It Captures

- Backend PHP errors, exceptions, shutdown failures, request context, logs, runtime facts, and probes through `debugbundle/sdk-php`
- Frontend exceptions, breadcrumbs, first-party request failures, device context, and probes through `@debugbundle/sdk-browser`
- Browser events through the WordPress REST relay at `/wp-json/debugbundle/v1/browser`
- Delivery diagnostics, last flush status, and relay spool size in the settings screen

The first release focuses on public-site capture. It does not capture `wp-admin` traffic by default.

## Settings

| Setting | Default | Purpose |
| --- | --- | --- |
| Project token | none | Server-side write-only DebugBundle project token. |
| Environment | WordPress environment type or `production` | Runtime environment shown on incidents and bundles. |
| Service | site host with `-wordpress` suffix | Service name for backend capture. Browser service uses the same value with `-browser`. |
| Ingestion endpoint | `https://api.debugbundle.com/v1/events` | Hosted or self-hosted DebugBundle ingestion endpoint. |
| Enable plugin | enabled | Master capture toggle. |
| Enable backend capture | enabled | Capture PHP/WordPress backend events. |
| Enable frontend capture | enabled | Capture browser events. |
| Capture browser console warnings/errors | disabled | Include frontend console breadcrumbs. |
| Sample rate | `1.0` | Backend event sampling rate. |
| Browser session sample rate | `1.0` | Fraction of browser sessions with frontend capture enabled. |
| Browser max events per session | `100` | Limit non-exception browser events per session. |
| Log level | `warning` | Minimum backend log severity. |
| Delete settings on uninstall | disabled | Remove saved settings when the plugin is uninstalled. |

## External Service Behavior

- The plugin does not send incident data until an administrator saves a project token or defines `DEBUGBUNDLE_PROJECT_TOKEN`.
- Browser JavaScript is bundled into the plugin and served locally from WordPress.
- Browser incidents are posted to the same-origin REST route first, then forwarded server-side to DebugBundle.
- A bounded relay spool retries delivery after transient ingestion failures.

## Privacy Notes

- The project token is stored server-side and is never exposed to browser JavaScript.
- Captured data is operational incident and request telemetry used for debugging.
- Site owners should review their DebugBundle configuration and privacy disclosures before enabling production capture.

## Local Development

Install PHP dependencies:

```bash
composer install
```

Build browser assets:

```bash
corepack enable
pnpm install
pnpm build
```

Run validation:

```bash
composer test
composer typecheck
make smoke-wordpress
```

`make smoke-wordpress` boots WordPress, MariaDB, and a mock DebugBundle ingestion service in Docker. It activates the plugin, verifies disabled relay behavior, sends backend and frontend test events, proves server-side token forwarding, simulates an ingestion outage, and validates relay spool recovery.

## Packaging

Build a local release ZIP:

```bash
make release-artifact VERSION=0.1.7
```

This writes `.dist/debugbundle-wordpress-<version>.zip` and a matching SHA-256 checksum. The packaged plugin directory inside the archive remains `debugbundle/` for WordPress compatibility.

Regenerate WordPress.org assets with:

```bash
make wordpress-org-assets
```

## Release Automation

- `.github/workflows/release.yml` runs tests, builds the browser asset, assembles the ZIP, and attaches it to a GitHub release.
- `.github/workflows/deploy-wordpress-org.yml` publishes the built plugin to WordPress.org SVN after a GitHub release is published or via manual dispatch.

Required WordPress.org workflow setup:

- GitHub Actions secret `WORDPRESS_ORG_USERNAME`
- GitHub Actions secret `WORDPRESS_ORG_PASSWORD`
- GitHub Actions variable `WORDPRESS_PLUGIN_SLUG`

## Documentation

- WordPress plugin docs: <https://debugbundle.com/docs/integrations/wordpress>
- SDK overview: <https://debugbundle.com/docs/sdks>
- Browser relay: <https://debugbundle.com/docs/sdks/browser-relay>
- Repository: <https://github.com/debugbundle/debugbundle-wordpress>

## Security

Report vulnerabilities through GitHub private vulnerability reporting for the plugin repository once it is public. If the repository is not yet public, use the main DebugBundle security reporting process.

## License

GPL-2.0-or-later. See `LICENSE`.
