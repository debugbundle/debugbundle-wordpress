=== DebugBundle ===
Contributors: owenfar, debugbundle
Tags: debugging, monitoring, error-tracking, observability, javascript
Requires at least: 6.5
Tested up to: 7.0
Stable tag: 1.2.5
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Capture backend and frontend WordPress incidents into DebugBundle with a server-side relay for browser events.

== Description ==

DebugBundle captures backend PHP/WordPress errors and user-facing browser incidents, then forwards them to DebugBundle without exposing your project token to page JavaScript.

Features include:

- backend PHP and request capture
- frontend browser exception capture
- same-origin WordPress REST relay for browser events
- bounded spool and retry behavior for transient delivery failures
- simple settings page under Settings -> DebugBundle
- compact diagnostics for SDK versions, flush status, and spool size
- backend and frontend test-event buttons for setup verification
- document-head loading for the bundled browser SDK on new installs, with upgraded installs preserving footer loading until explicitly changed

== Installation ==

1. Upload the plugin ZIP to WordPress and activate it.
2. Go to Settings -> DebugBundle.
3. Paste your DebugBundle project token.
4. Save settings.
5. Use the test-event buttons on the settings page to verify backend and frontend delivery.

For development validation, the repository includes a Docker-based WordPress smoke test that installs WordPress, activates the plugin, verifies backend and frontend delivery against a mock ingestion service, and proves relay spool recovery after a simulated ingestion outage.

== External services ==

This plugin connects to the DebugBundle service at `https://api.debugbundle.com` to send production incident telemetry and to fetch SDK capture configuration for the connected DebugBundle project.

The plugin only sends data after a site administrator enters a DebugBundle project token in the plugin settings and saves it. Backend PHP/WordPress incidents may include sanitized exception, request, response, environment, service, log, and WordPress context needed for debugging. Browser incidents are posted to a same-origin WordPress REST route first and then forwarded server-side to DebugBundle, so the project token stays server-side and is never exposed to page JavaScript. Browser JavaScript is served from this plugin package, not from a third-party CDN.

The service is provided by DebugBundle:

- Service: https://debugbundle.com
- Terms of Service: https://debugbundle.com/terms
- Privacy Policy: https://debugbundle.com/privacy

== Screenshots ==

1. The DebugBundle settings screen for connecting a WordPress site to a DebugBundle project.
2. Frontend capture, relay, and privacy controls on the DebugBundle options page.

== Frequently Asked Questions ==

= Does the browser SDK get my project token? =

No. The plugin keeps the project token on the server and receives browser events through a same-origin relay route.

= Does this capture wp-admin by default? =

No. The first release is focused on public-site capture.

= Does the plugin contact DebugBundle before I configure it? =

No. The plugin requires a saved project token before it can forward backend or browser incidents to DebugBundle.

== Changelog ==


= 1.2.5 =

* Add the WordPress.org submitter to plugin contributors, document the DebugBundle external service with terms and privacy links, and tighten WordPress-native sanitization around request metadata, settings, and remote configuration fetches.

= 1.2.4 =

* Rebuild the bundled browser SDK on `@debugbundle/sdk-browser` `1.4.0` so the plugin ships the latest capture-rule suggestion contract and bundle metadata updates across the stable browser SDK line.

= 1.2.2 =

* Rebuild the bundled browser SDK on `@debugbundle/sdk-browser` `1.3.0` so the plugin ships the browser fetch-header preservation fix on the current stable JS SDK line.

= 1.2.1 =

* Replace the remaining `parse_url()` relay-origin parsing calls with `wp_parse_url()` so the plugin passes current WordPress Plugin Check URL-parsing guidance consistently.

= 1.2.0 =

* Rebuild the bundled browser SDK on `@debugbundle/sdk-browser` `1.2.0` so the plugin ships the browser `beforeSend` hook, bounded rejection-reason capture, and bot-aware browser noise controls on the stable SDK line.

= 1.1.0 =

* Rebuild the bundled browser SDK on `@debugbundle/sdk-browser` `1.1.0` and require `debugbundle/sdk-php` `^1.1.0` so the plugin ships the path-scoped client-error capture updates across the stable PHP and browser SDK line.

= 1.0.1 =

* Rebuild the bundled browser SDK on `@debugbundle/sdk-browser` `1.0.1` so the plugin ships the opaque browser-error enrichment and head-loading defaults together on the stable SDK line.

= 1.0.0 =

* Mark the first stable WordPress plugin release after the browser relay, spool, and diagnostics model settled across live smoke coverage.
* Rebuild the bundled browser SDK on `@debugbundle/sdk-browser` `1.0.0` and require `debugbundle/sdk-php` `^1.0.0` so the plugin ships on the stable SDK line.


= 0.1.7 =

* Rebuild the bundled browser SDK on `@debugbundle/sdk-browser` `0.1.8` so the shipped WordPress asset includes the trace-allowlist hardening fix.

= 0.1.6 =

* Replace the remaining raw config-fetch error propagation with a stable plugin-owned failure message so WordPress Plugin Check no longer flags the exception path.

= 0.1.5 =

* Address WordPress Plugin Check compliance issues around metadata, direct-access guards, WordPress-safe request handling, and filesystem APIs.

= 0.1.4 =

* Break long sampling and log-level helper text into stacked description lines so the settings page reads more cleanly.

= 0.1.3 =

* Complete browser relay correlation fields before forwarding so frontend events satisfy the current ingestion schema.

= 0.1.2 =

* Report the DebugBundle ingestion response for frontend relay deliveries, including accepted and rejected counts.
* Treat ingestion-level rejected events as relay test failures even when the HTTP request itself returned 202.

= 0.1.1 =

* Hide the saved project token in a password field on the settings page.
* Clarify sampling and log-level settings with concrete explanations.
* Send a schema-valid frontend relay test event and report relay forwarding errors instead of showing a false success.

= 0.1.0 =

* Initial pre-release plugin scaffold.
