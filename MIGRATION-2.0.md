# Upgrading DebugBundle for WordPress to 2.0

Install the 2.0 plugin ZIP or update through WordPress.org as usual. The plugin still bundles its PHP and browser SDKs; no extra service, Composer command, or browser configuration is needed. Existing project tokens, settings, browser relay route, and private retry spool remain in place.

Backend capture no longer sends when a batch fills. The PHP SDK prioritizes exceptions and makes one small best-effort attempt at request end. That attempt may briefly occupy a PHP worker; a transport outage can discard unsent events. If you use a custom PHP `beforeSend` callback, keep it prompt and bounded because application callbacks run in the PHP request.

Remote backend capture policy now comes from a local WordPress cache on visitor requests. WP-Cron refreshes it with the project token. Ensure WP-Cron runs if you rely on remote policy changes; when the cache is unavailable, the SDK uses its minimal fallback policy.

After upgrading, use the plugin's backend and frontend test buttons and check that the browser relay and retry spool still work. Existing 1.5 installations keep their previous behavior until upgraded.
