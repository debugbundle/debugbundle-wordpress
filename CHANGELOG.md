# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning.

## [Unreleased]

## [1.2.5] - 2026-06-25

### Fixed

- Added the WordPress.org submitter to plugin contributors and documented DebugBundle external service usage with service, terms, and privacy links for WordPress.org review.
- Replaced raw WordPress input collection with typed sanitization for request headers, query metadata, admin notices, saved settings, and SDK remote configuration fetches.

## [1.2.4] - 2026-06-20

### Changed

- Rebuilt the shipped browser bundle on `@debugbundle/sdk-browser` `1.4.0` so WordPress inherits the latest capture-rule suggestion contract and bundle metadata updates from the stable JS SDK line.

## [1.2.3] - 2026-06-19

### Changed

- Rebuilt the shipped browser bundle on `@debugbundle/sdk-browser` `1.3.1` and raised the PHP SDK requirement to `debugbundle/sdk-php` `^1.1.2` so WordPress inherits the canonical event-envelope fixes across both runtime surfaces.

## [1.2.2] - 2026-06-17

### Changed

- Rebuilt the shipped browser bundle on `@debugbundle/sdk-browser` `1.3.0` so WordPress inherits the browser fetch-header preservation fix and the aligned trace-propagation documentation on the current stable JS SDK line.

## [1.2.1] - 2026-06-16

### Fixed

- Replaced the remaining raw `parse_url()` relay-origin parsing calls with `wp_parse_url()` so the WordPress REST browser relay satisfies current WordPress Plugin Check URL-parsing guidance consistently.

## [1.2.0] - 2026-06-09

### Changed

- Rebuilt the shipped browser bundle on `@debugbundle/sdk-browser` `1.2.0` so WordPress inherits the browser `beforeSend` hook, bounded rejection-reason capture, and bot-aware browser-noise controls on the stable JS SDK line.
- Synchronized plugin metadata, smoke fixtures, and release documentation with the `1.2.0` browser SDK line while keeping the PHP SDK requirement on `debugbundle/sdk-php` `^1.1.0`.

## [1.1.0] - 2026-06-08

### Changed

- Rebuilt the shipped browser bundle on `@debugbundle/sdk-browser` `1.1.0` and raised the PHP SDK requirement to `debugbundle/sdk-php` `^1.1.0` so WordPress inherits the path-scoped client-error capture policy updates on both runtime surfaces.
- Synchronized plugin metadata, smoke fixtures, and release documentation with the `1.1.0` SDK line.

## [1.0.1] - 2026-06-03

### Changed

- Rebuilt the shipped browser bundle on `@debugbundle/sdk-browser` `1.0.1` so WordPress release artifacts inherit the opaque browser-native error enrichment used for improved bundle diagnostics.
- Enabled document-head browser SDK loading by default for new installs while preserving footer loading on upgraded installs until an administrator changes the setting.

## [1.0.0] - 2026-05-31

### Changed

- Marked the first stable WordPress plugin release after the relay, spool, and diagnostics flow settled across live smoke coverage.
- Rebuilt the shipped browser bundle on `@debugbundle/sdk-browser` `1.0.0`, raised the PHP SDK requirement to `debugbundle/sdk-php` `^1.0.0`, and synchronized plugin release metadata for the stable SDK line.

## [0.1.11] - 2026-05-29

### Fixed

- Added browser relay preflight handling and matching CORS headers for the WordPress REST relay so split-host frontend deployments can post through the plugin safely.

### Changed

- Rebuilt the shipped browser bundle on `@debugbundle/sdk-browser` `0.1.11`, raised the PHP SDK requirement to `debugbundle/sdk-php` `^0.1.10`, and synchronized plugin release metadata for the new SDK release set.

## [0.1.10] - 2026-05-26

### Changed

- Rebuilt the shipped browser bundle on `@debugbundle/sdk-browser` `0.1.10`, raised the PHP SDK requirement to `debugbundle/sdk-php` `^0.1.9`, and synchronized release metadata files used by the automated plugin packaging checks.

## [0.1.7] - 2026-05-22

### Fixed

- Rebuilt the shipped browser bundle on `@debugbundle/sdk-browser` `0.1.8` so WordPress release artifacts inherit the browser trace-allowlist hardening that removes caller-provided regex execution.

## [0.1.6] - 2026-05-19

### Fixed

- Switched the remaining `ConfigFetcher` WP error path to a stable plugin-owned exception message so WordPress Plugin Check no longer flags raw exception output handling.

## [0.1.5] - 2026-05-19

### Fixed

- Addressed WordPress Plugin Check issues across plugin metadata, direct-file-access protection, WordPress-safe input handling, URL parsing, and filesystem operations used by relay spooling and uninstall cleanup.

## [0.1.4] - 2026-05-19

### Changed

- Broke long admin helper copy for sampling, browser session limits, and log levels into stacked description lines so the settings page reads more cleanly in the standard WordPress form layout.

## [0.1.3] - 2026-05-19

### Fixed

- Completed browser relay correlation fields after the bundled PHP relay sanitizer so forwarded frontend events satisfy the current DebugBundle ingestion schema.

## [0.1.2] - 2026-05-19

### Fixed

- Relay forwarding now parses DebugBundle ingestion response bodies and treats `accepted: 0`, partial acceptance, or rejected events as setup-test failures instead of false successful relay flushes.
- The admin Status block now shows the last relay ingestion result so frontend relay tests expose accepted/rejected counts and rejection reasons.

## [0.1.1] - 2026-05-19

### Fixed

- Hid the saved project token in a password field on the settings page so it no longer renders in plain text.
- Expanded the sample-rate, frontend session sampling, frontend per-session event cap, and log-level descriptions with concrete behavior guidance.
- Corrected the admin frontend relay test event payload to match the browser event contract and report relay forwarding failures instead of showing a false success.

### Changed

- Switched top-level plugin packaging metadata to GPLv2-or-later for WordPress distribution.
- Aligned the REST browser relay route with the shared PHP relay behavior and vendored relay compliance fixtures while keeping WordPress-specific persistent limiting and spool handling.

### Added

- Reusable release assembly script, local release packaging target, and WordPress.org deployment workflow scaffolding.

## [0.1.0] - 2026-05-19

### Added

- Initial standalone WordPress plugin scaffold with backend capture, browser asset build, relay route, spool handling, settings page, and Docker-friendly CI/release workflows.
- Admin backend/frontend test-event actions for setup verification.
- Mock-ingestion WordPress smoke coverage for backend and frontend delivery.
- Admin diagnostics for bundled SDK versions, PHP compatibility, flush status, relay errors, and spool size.
- Mock-ingestion outage coverage for relay spool creation and retry flush.
