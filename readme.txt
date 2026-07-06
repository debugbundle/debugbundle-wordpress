=== DebugBundle ===
Contributors: owenfar, debugbundle
Tags: debugging, error-tracking, monitoring, ai, observability
Requires at least: 6.5
Tested up to: 7.0
Stable tag: 1.2.5
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Turn WordPress PHP errors and browser exceptions into DebugBundle reports for developers and AI agents.

== Description ==

DebugBundle helps you understand what went wrong when a WordPress site breaks. Instead of piecing together PHP logs, browser errors, request details, and plugin or theme context by hand, the plugin sends the important debugging evidence to your DebugBundle project.

Use it when you want a clearer answer than "something broke." DebugBundle gives the developer or AI agent fixing the site the backend and visitor-side context around an incident, so they can see what happened, where it happened, and what evidence is available.

DebugBundle is built for agent-first debugging workflows. If you use an AI coding agent, DebugBundle reports give the agent structured incident evidence instead of a vague error message or copied log snippet. That helps the agent investigate plugin, theme, integration, and frontend failures with the same context a developer would need.

Setup stays simple: install the plugin, paste your DebugBundle project token, and run the built-in test buttons. You do not need Composer, npm, shell access, CDN scripts, or a custom relay setup.

Useful for:

- production PHP errors, fatal errors, and uncaught exceptions
- browser exceptions that visitors hit on public pages
- failed or slow first-party requests seen by the browser
- recent visitor actions and page changes that help explain a frontend error
- WordPress, PHP, service, environment, and SDK version context for developers or AI coding agents
- AI-assisted debugging workflows where an agent needs structured incident evidence instead of copied logs

What gets captured:

- PHP errors, uncaught exceptions, fatal shutdown errors, request metadata, and logs at or above the configured level
- frontend browser exceptions from public pages
- error-only browser breadcrumbs such as clicks, route changes, and first-party request failures
- service, environment, WordPress, PHP, and SDK version context useful for debugging

How delivery works:

- backend events are sent server-side through `debugbundle/sdk-php`
- browser events are posted to `/wp-json/debugbundle/v1/browser` and forwarded server-side
- transient browser relay delivery failures are retried through a bounded local spool
- the browser SDK is served from this plugin package, not from a third-party CDN

What it does not do:

- it does not contact DebugBundle until an administrator saves a project token or defines `DEBUGBUNDLE_PROJECT_TOKEN`
- it does not expose the project token to page JavaScript
- it does not capture `wp-admin` traffic by default
- it does not add incident browsing, billing, account management, or dashboard features inside WordPress
- it does not let an agent change WordPress settings, content, users, plugins, or themes

== Installation ==

1. In WordPress, go to Plugins -> Add New.
2. Search for DebugBundle, then install and activate the plugin.
3. Open Settings -> DebugBundle.
4. Paste your DebugBundle project token and save.
5. Use the backend and frontend test-event buttons to verify delivery.

If you cannot install from the WordPress.org plugin directory, download the ZIP from the [DebugBundle WordPress plugin GitHub releases page](https://github.com/debugbundle/debugbundle-wordpress/releases) and upload it under Plugins -> Add New -> Upload Plugin.

== External services ==

This plugin connects to the DebugBundle service at `https://api.debugbundle.com` to send production incident telemetry and to fetch SDK capture configuration for the connected DebugBundle project.

The plugin only sends data after a site administrator enters a DebugBundle project token in the plugin settings and saves it. Backend PHP/WordPress incidents may include sanitized exception, request, response, environment, service, log, and WordPress context needed for debugging. Browser incidents are posted to a same-origin WordPress REST route first and then forwarded server-side to DebugBundle, so the project token stays server-side and is never exposed to page JavaScript. Browser JavaScript is served from this plugin package, not from a third-party CDN.

The service is provided by DebugBundle:

- Service: https://debugbundle.com
- Terms of Service: https://debugbundle.com/terms
- Privacy Policy: https://debugbundle.com/privacy

== Screenshots ==

1. The standard WordPress settings form for connecting a site to a DebugBundle project and choosing capture options.
2. The status and test delivery section with SDK versions, relay details, spool size, and backend/frontend test buttons.

== Frequently Asked Questions ==

= Do I need a DebugBundle project? =

Yes. The plugin needs a DebugBundle project token before it can send backend or browser incidents.

= Does the browser SDK get my project token? =

No. The plugin keeps the project token on the server and receives browser events through a same-origin relay route.

= Does this capture wp-admin by default? =

No. The first release is focused on public-site capture.

= Does the plugin contact DebugBundle before I configure it? =

No. The plugin requires a saved project token before it can forward backend or browser incidents to DebugBundle.

= Can I use this instead of installing the PHP or browser SDK manually? =

Yes for normal WordPress sites. The plugin vendors the PHP SDK, bundles the browser SDK, and registers the WordPress REST browser relay for you.

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
