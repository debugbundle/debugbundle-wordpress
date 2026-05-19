# DebugBundle WordPress Plugin

DebugBundle plugin for WordPress.

This plugin captures backend PHP/WordPress incidents and user-facing browser incidents, then forwards them to DebugBundle without exposing the project token to browser JavaScript.

## Status

Pre-release plugin under active development. Public setup documentation lives at https://debugbundle.com/docs/integrations/wordpress.

## Features

- Backend PHP error, exception, shutdown, and request capture through `debugbundle/sdk-php`
- Frontend browser capture through `@debugbundle/sdk-browser`
- Same-origin WordPress REST relay for browser events
- Server-side project-token handling only
- Bounded relay spool with retry via WP-Cron
- Simple settings page under `Settings -> DebugBundle`
- Compact diagnostics for SDK versions, PHP compatibility, flush status, and spool size
- Backend and frontend test-event buttons for setup verification

## External Service Behavior

- The plugin remains inactive until a site administrator saves a DebugBundle project token.
- Browser JavaScript is bundled into the plugin and served locally from WordPress.
- Browser incidents are sent to a same-origin WordPress REST route and then forwarded server-side to DebugBundle.

## Privacy Notes

- The project token is stored server-side and is not exposed to browser JavaScript.
- The plugin is intended to send operational incident and request telemetry needed for debugging.
- Site owners should review their privacy disclosures before enabling incident collection on production traffic.

## Requirements

- WordPress 6.5 or newer
- PHP 8.2 or newer

## Local Development

### PHP dependencies

```bash
composer install
```

### Browser asset build

```bash
corepack enable
pnpm install
pnpm build
```

### Tests

```bash
composer test
composer typecheck
```

### WordPress smoke test

```bash
make smoke-wordpress
```

This boots WordPress, MariaDB, and a mock DebugBundle ingestion service in Docker. It installs WordPress, activates the plugin, verifies the disabled relay response, configures the plugin against the mock endpoint, sends backend and frontend test events, asserts the mock ingestion service received them with the server-side project token, then simulates an ingestion outage and verifies relay spool recovery.

### WordPress.org assets

```bash
./scripts/generate-wordpress-org-assets.sh
```

This regenerates the icon, banner, and screenshot PNG assets in `.wordpress-org/assets/` from the SVG source files in `.wordpress-org/source/`.

## Packaging

Build a local release artifact with:

```bash
make release-artifact VERSION=0.1.0
```

This creates `.dist/debugbundle-wordpress-<version>.zip` and a matching SHA-256 checksum. The packaged plugin directory inside the archive remains `debugbundle/` for WordPress compatibility.

The release artifact contains:

- `vendor/`
- `assets/dist/`
- plugin PHP source
- WordPress.org-compatible metadata files

## Release Automation

- `.github/workflows/release.yml` runs tests, builds the browser asset, assembles the ZIP, and attaches it to a GitHub release on `v*` tags or manual dispatch.
- `.github/workflows/deploy-wordpress-org.yml` publishes the built plugin to WordPress.org SVN after a GitHub release is published, or via manual dispatch.

Repository setup required before the WordPress.org workflow can run:

- GitHub Actions secret `WORDPRESS_ORG_USERNAME`
- GitHub Actions secret `WORDPRESS_ORG_PASSWORD`
- GitHub Actions variable `WORDPRESS_PLUGIN_SLUG`

The `WORDPRESS_PLUGIN_SLUG` value should match the slug assigned by the WordPress.org plugin review team.

## Security

Report vulnerabilities through GitHub private vulnerability reporting for the plugin repository once it is published.

If the repository is not yet public, use the main DebugBundle security reporting process.

## License

GPL-2.0-or-later
