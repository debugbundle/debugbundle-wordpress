# WordPress 2.1 evidence

Version 2.1.0 bundles PHP SDK 2.1 and Browser SDK 3.2 in the
assembled plugin. Source tests using a mounted candidate do not qualify an
installed ZIP. Existing settings, project tokens, REST routes, legacy relay
responses and `debugbundle_before_send` filters retain their meaning.

Structured exception evidence and passive browser resource observations are on
by default when the complete delivery path proves support. Recovery is explicit
and off by default. A missing, malformed or unknown capability descriptor disables
new evidence; the primary error remains eligible for normal capture.

## Explicit application recovery

Register the filter before the plugin initializes (for example, in a must-use
plugin). Only these Boolean options are accepted; destination and credentials
cannot be changed through this filter.

```php
add_filter('debugbundle_evidence_options', static function (array $options): array {
    $options['captureRecoveryObservations'] = true;
    return $options;
});
```

`captureExceptionEvidence` and `captureResourceObservations` default to `true`.
Set either to `false` to disable that evidence. Project capture policy still has
authority: minimal policy or `capture_diagnostic_context=false` withholds
independent observations, including queued retries.

Use `debugbundle_sdk_ready` to receive the configured PHP SDK. An application
can retain this instance for its existing handlers and call
`runWithOperationContext`, `captureException` and `captureRecovery`. For example,
inside one operation callback, supply the same fresh UUID v4 `failureRef` to the
failure and its subsequent observations; use a distinct fresh UUID v4
`attemptId` for each attempt. Explicit asynchronous work must carry an explicit
`evidenceScope` and matching correlation. See the bundled PHP migration guide
for the full signatures and lifecycle limits.

Browser integrations can use `window.DebugBundleWordPress` after the
`debugbundle-browser` script loads; debug logging is not required. Use
`captureException(error, { failureRef })` and
`captureRecovery({ failureRef, attemptId, state })` within the same page scope.
Register the application script with `debugbundle-browser` as a dependency.
No project token is exposed to the page.

The states are `attempt_started`, `attempt_completed`, `attempt_failed`,
`recovery_observed` and `failure_persisted`. Report `recovery_observed` only when
the application has verified recovery. HTTP 200 and `attempt_completed` remain
literal observations; they do not resolve an incident or erase the failure.

## Capability and retry lifecycle

WP-Cron refreshes the endpoint/token-scoped policy and capability cache, including
frontend-only installs. Visitor requests read local state and never perform a
configuration request. Capability discovery uses the existing config/ingestion
requests. A valid ingestion acknowledgement may refresh capability proof but
does not extend the capture-policy lease.

New browser callers opt in with `debugbundle_evidence=1` on the existing relay
URL (or `X-DebugBundle-Evidence: 1`). Responses then include the proven
`evidence_capabilities` and indexed `evidence_errors`. Legacy callers retain
the original response shape. The relay rechecks proof, local options and project
policy before forwarding every chunk and replaying a spool entry.

Only privacy-protected, byte-bounded chunks are retained in the existing private
spool. A browser batch is acknowledged after all its chunks are retained.
Partial acknowledgements keep only retryable event indexes. Requests stay under
256 KiB and share the existing five-second forwarding deadline; response reads
are bounded, redirects disabled and encoded acknowledgement/config bodies
rejected. Existing legacy spool data is protected again before retry.

Deploy accepting DebugBundle readers/ingestion before publishing the PHP/browser
dependencies and this plugin. Installing or updating an existing WordPress site
is a separate operator action. The 2.1.0 public ZIP passed supported WordPress/PHP
runtime checks with final registry-resolved dependencies before distribution.
