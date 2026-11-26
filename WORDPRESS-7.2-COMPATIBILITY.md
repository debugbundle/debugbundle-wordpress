# WordPress 7.2 compatibility qualification

Date: 26 November 2026. Plugin: **2.2.0**. Result: the published plugin passes
local qualification on **WordPress 7.2-RC3 / PHP 8.2.33 and PHP 8.6.0**.
The final WordPress 7.2 release has not been tested yet.

## Artifact and scope

Tested the [WordPress.org 2.2.0 ZIP](https://downloads.wordpress.org/plugin/debugbundle.2.2.0.zip),
SHA-256 `0e3b387d93df13252266e36792f3bf51b0b46e6a7edcdfc00e8c9b71dda879ab`.
All 21 plugin PHP source/entrypoint files match checkout
`26c76981530b6dbe65f3a4dda2384fb4e089f176`. The ZIP bundles PHP SDK 2.2.0
and Browser SDK 3.3.0. Neither dependency nor the published artifact was rebuilt.

The disposable Docker installation used MariaDB and a mock ingestion server,
synthetic credentials/events, and no source overlays. Core was downloaded with
WP-CLI, its upstream MD5 verified, and its installed version checked before
activating the plugin. The installer needs 512 MiB for extraction; the first
attempt failed at WP-CLI's default 128 MiB before plugin activation.

Initial qualification changed no existing WordPress installation, consumer
product, production configuration, production telemetry or public distribution.
The subsequent approved publication below changes only two directory readmes.

## Checks and evidence

| Check | Result |
| --- | --- |
| Published ZIP on WordPress 7.2-RC3 / PHP 8.2.33 | Passed |
| Same ZIP on WordPress 7.2-RC3 / PHP 8.6.0 | Passed |
| Existing Docker PHPUnit/PHPStan suite | 100 tests, 508 assertions; no PHPStan errors |
| PHP 8.6 PHPUnit/PHPStan suite, including final metadata | 100 tests, 508 assertions; no PHPStan errors |
| Metadata regression | Failed with `Tested up to: 7.1`, passed after changing it to `7.2` |
| Plugin Check 2.2.0, default static checks on both installed runtimes | Zero errors; two warnings |

The installed smoke covers activation, disabled relay behaviour, authenticated
cached policy, diagnostic/nonce privacy, PHP suppression and empty-message
exceptions, hook replacement/reduction, backend and frontend delivery, server-side
credentials, the exact packaged browser asset, native/relayed evidence, outage
spooling, retry and policy revocation. Browser asset execution uses the existing
Node harness; this is not a browser UI or device acceptance claim.

Plugin Check reports `unexpected_markdown_file` for `MIGRATION-2.0.md` and
`MIGRATION-2.1.md`. These are intentionally bundled migration instructions for
installed users. They remain in this metadata-only change; there are no code
errors in the report. Plugin Check's separate runtime checks and AI review were
not enabled.

Ignored local receipts live under `.tmp/wordpress-7.2/`: `php82.log`, `php86.log`,
`unit-and-types.log`, `php86-unit-and-types-final.log`, `metadata-red.log`,
`php82-evidence/`, and `wordpress/.smoke/`. The latter two include Plugin Check's
version and report. `relay-post-headers.txt` records the 7.2 CORS header check.

## Release-note applicability

Reviewed the official [7.2 field guide](https://make.wordpress.org/core/2026/11/20/wordpress-7-2-field-guide/),
[REST API changes](https://make.wordpress.org/core/2026/11/17/rest-api-changes-in-wordpress-7-2/)
and [PHP 8.6 notes](https://make.wordpress.org/core/2026/11/23/wordpress-and-php-8-6/).

- The plugin uses a WordPress REST relay. Its explicit
  `Access-Control-Allow-Methods: POST, OPTIONS` survives the real 7.2 HTTP POST
  response. WordPress handles HTTP OPTIONS through its own route-discovery
  response (200); this check does not claim the SDK handler's direct 204 contract
  is the HTTP server's behaviour.
- No custom media-upload processing, editor components, block registration,
  Abilities API, Interactivity API directives, jQuery or core-icon styling were
  found in the plugin's production integrations.
- Neither the plugin nor the PHP SDK source uses the PHP 8.6 changes called out
  for custom stream wrappers, `spl_object_hash()`, font MIME handling or image
  flipping. The actual PHP 8.6 installed-plugin and unit suites pass.

This maps to FR-SDK-03, FR-REL-01, NFR-REL-01 and NFR-PERF-01, and the installed
distribution/relay checks in AC-SDK-SAFETY-INSTALL and AC-REL-11. Existing privacy,
credential isolation and the documented PHP request-end delivery exception
remain unchanged. This is compatibility qualification, not a new full-family
SDK parity certification.

## Maintained-surface applicability

| Surface | Disposition and evidence |
| --- | --- |
| WordPress backend, relay and bundled distribution | Applicable; unchanged released code qualified through the exact installed ZIP on both runtimes. |
| PHP SDK | Applicable through its bundled WordPress path; unit/wrapper and installed-path evidence above. No standalone PHP release or all-framework PHP 8.6 certification is claimed. |
| Browser SDK | Applicable through the packaged asset and WordPress relay; exact asset execution and installed relay passed. No SDK source change. |
| Node.js and its Express/Fastify/Next.js adapters | Not applicable to WordPress core metadata; no code or contract change. |
| Python and its Django/Flask/FastAPI adapters | Not applicable; no code or contract change. |
| Java and its Spring/servlet/JAX-RS/agent adapters | Not applicable; no code or contract change. |
| Go and its net/http/Gin/Echo adapters | Not applicable; no code or contract change. |
| Ruby and its Rack/Rails/Sidekiq adapters | Not applicable; no code or contract change. |
| .NET and its ASP.NET Core adapters | Not applicable; no code or contract change. |
| Rust and its Tower/Axum/Actix adapters | Not applicable; no code or contract change. |
| Android | Not applicable; no code or contract change. |
| Swift/iOS | Not applicable; no code or contract change. |
| React Native, including both native bridges | Not applicable; no code or contract change. |

## Local changes and publication boundary

Local changes update `readme.txt` to `Tested up to: 7.2`, its metadata regression,
and README qualification instructions. The smoke harness can download an exact
RC without a Docker core tag, records the actual runtime and optionally runs
Plugin Check. CI retains the previous WordPress 7.1.1 smoke and adds 7.2-RC3 on
PHP 8.2/8.6 plus PHP 8.6 unit/static analysis. These changes passed local review
and the remote CI run recorded below.

**Retain** WordPress 2.2.0, PHP 2.2.0, Browser 3.3.0 and every other distribution
version: no shipped runtime behaviour or dependency changed. The approved
publication updated only the `Tested up to` line in WordPress.org SVN
`trunk/readme.txt` and `tags/2.2.0/readme.txt`, verified the stable tag and read the
directory metadata back. The full release workflow was not used to rebuild or
replace the retained plugin version. Recheck against the final 7.2 release when
available; consumer installation remains separately authorised.

The manual `update-wordpress-compatibility.yml` workflow prepares that exact
two-readme diff using the existing GitHub WordPress.org credentials. Its target
is fixed to plugin 2.2.0 and WordPress 7.2; unexpected stable tags or previous
compatibility values fail before writing. A subsequent run is idempotent. The
workflow was committed, pushed and dispatched with explicit owner approval.

The preparation step was validated against both current SVN readmes: each diff
contains only `Tested up to: 7.1` to `7.2`. Local checks passed for idempotence,
changed stable tags, unexpected/duplicate compatibility fields, refusal of any
third path or property change, and no partial writes when either readme fails
validation. Receipts are under `.tmp/wordpress-7.2/publication/`.

## Publication receipts

- Source commit: [`d97823d5902a20ff6e3a6cbe2b97942a2472df75`](https://github.com/debugbundle/debugbundle-wordpress/commit/d97823d5902a20ff6e3a6cbe2b97942a2472df75).
- [CI run 45824702322](https://github.com/debugbundle/debugbundle-wordpress/actions/runs/45824702322)
  passed all ten jobs: PHP 8.2–8.6, browser build, coverage, and three installed
  WordPress smoke lanes (7.1.1/PHP 8.2 and 7.2-RC3/PHP 8.2/8.6).
- [Readme-only publication run 45825069549](https://github.com/debugbundle/debugbundle-wordpress/actions/runs/45825069549)
  succeeded at 13:01 UTC on 26 November 2026. SVN revision **3827011** changed
  only `trunk/readme.txt` and `tags/2.2.0/readme.txt`: `Tested up to: 7.1` → `7.2`.
- Independent public readback verified both readmes byte-for-byte against their
  previous contents with that one-line replacement. The WordPress.org plugin
  information API reports `version: "2.2.0"` and `tested: "7.2"`.
- The plugin version and dependency versions were retained. No consumer site
  was installed, updated or otherwise changed by this publication.

Ignored receipts include `ci-result.json`, `published-run.log`,
`trunk-readme-published.txt`, `stable-readme-published.txt`, and
`directory-before.json` / `directory-after.json` under the publication directory.
