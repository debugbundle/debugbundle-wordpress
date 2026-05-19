=== DebugBundle ===
Contributors: debugbundle
Tags: debugging, monitoring, error-tracking, observability, javascript
Requires at least: 6.5
Tested up to: 6.8
Stable tag: 0.1.0
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

External service notice:

- The plugin only sends incident data to the DebugBundle service after a site administrator enters a DebugBundle project token in the plugin settings and saves it.
- Browser JavaScript is served from this plugin package, not from a third-party CDN.
- Browser incidents are posted to a same-origin WordPress REST route first and then forwarded server-side to DebugBundle.

Privacy note:

- The plugin is intended to send operational incident and request telemetry needed for debugging.
- The project token stays server-side and is never exposed to page JavaScript.
- Site owners are responsible for reviewing their DebugBundle configuration and privacy disclosures before enabling collection on production traffic.

== Installation ==

1. Upload the plugin ZIP to WordPress and activate it.
2. Go to Settings -> DebugBundle.
3. Paste your DebugBundle project token.
4. Save settings.
5. Use the test-event buttons on the settings page to verify backend and frontend delivery.

For development validation, the repository includes a Docker-based WordPress smoke test that installs WordPress, activates the plugin, verifies backend and frontend delivery against a mock ingestion service, and proves relay spool recovery after a simulated ingestion outage.

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

= 0.1.0 =

* Initial pre-release plugin scaffold.
