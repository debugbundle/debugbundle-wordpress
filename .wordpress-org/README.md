# WordPress.org Submission Notes

This directory contains the WordPress.org-ready marketing assets and submission notes for the DebugBundle plugin.

## Asset inventory

- `assets/icon.svg`
- `assets/icon-128x128.png`
- `assets/icon-256x256.png`
- `assets/banner-772x250.png`
- `assets/banner-1544x500.png`
- `assets/screenshot-1.png`
- `assets/screenshot-2.png`

## Regenerating assets

Run:

```bash
./scripts/generate-wordpress-org-assets.sh
```

The script rasterizes the SVG source files in `.wordpress-org/source/` with macOS Quick Look and writes the WordPress.org filenames into `.wordpress-org/assets/`.

## Suggested short description

Capture WordPress backend and browser incidents with a same-origin relay that keeps your DebugBundle token out of page JavaScript.

## Reviewer notes

- The plugin does not contact DebugBundle until a site administrator saves a project token.
- Browser JavaScript is bundled locally into the plugin package; no third-party CDN assets are loaded.
- Browser incidents are sent to a same-origin WordPress REST route and forwarded server-side.
- The project token stays server-side and is not exposed to browser JavaScript.
- The current v1 scope focuses on public-site capture rather than `wp-admin` capture.