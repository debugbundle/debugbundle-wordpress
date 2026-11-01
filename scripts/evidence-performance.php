<?php

declare(strict_types=1);

// Test harness only: run through WP-CLI against the checksum-verified staged ZIP.
if (!defined('WP_CLI') || !WP_CLI || getenv('DEBUGBUNDLE_EVIDENCE_PERFORMANCE') !== '1'
    || (new \DebugBundleWp\Settings())->getEndpoint() !== 'http://mock-ingestion:18081/v1/events') {
    fwrite(STDERR, "Disposable WordPress evidence fixture required\n");
    exit(1);
}

function wpEvidenceRequire(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function wpEvidenceProperty(object $owner, string $name): mixed
{
    return (new ReflectionProperty($owner, $name))->getValue($owner);
}

function wpEvidenceCompare(bool $enabled, bool $pressure): array
{
    $options = static fn (array $value): array => array_replace($value,
        ['captureExceptionEvidence' => $enabled, 'captureRecoveryObservations' => $enabled]);
    add_filter('debugbundle_evidence_options', $options);
    $bootstrap = new \DebugBundleWp\SdkBootstrap(new \DebugBundleWp\Settings());
    $bootstrap->register();
    $sdk = wpEvidenceProperty($bootstrap, 'sdk');
    wpEvidenceRequire($sdk instanceof \DebugBundle\DebugBundleSdk, 'Packaged bootstrap did not initialise');
    $transport = new class implements \DebugBundle\Transport\TransportInterface {
        public int $failures = 0;
        public function send(array $request): \DebugBundle\Transport\TransportResponse
        {
            foreach ($request['events'] as $event) {
                if ($event['event_type'] === 'backend_exception') $this->failures++;
            }
            return new \DebugBundle\Transport\TransportResponse(202, null,
                ['accepted' => count($request['events']), 'rejected' => 0, 'errors' => []]);
        }
    };
    // Keep the real bounded queue and WordPress hook path, but hold transport out
    // of caller timing. Configuration proof still comes from the local cached ACK.
    (new ReflectionProperty($sdk, 'transport'))->setValue($sdk, $transport);
    (new ReflectionProperty($sdk, 'batchSize'))->setValue($sdk, 1001);
    (new ReflectionProperty($sdk, 'capturePolicy'))->setValue($sdk, \DebugBundle\RemoteConfig::balancedCapturePolicy());
    try {
        $bootstrap->onInit();
        if ($pressure) {
            for ($index = 0; $index < 1000; $index++) $sdk->captureLog("warning $index", 'warning');
            wpEvidenceRequire(count(wpEvidenceProperty($sdk, 'buffer')) === 1000, 'Queue not filled');
        }
        $calls = [];
        for ($index = 0; $index < 20; $index++) {
            $failure = wp_generate_uuid4();
            $calls[] = [new RuntimeException("outer $index", 0, new LogicException("inner $index")),
                ['failureRef' => $failure], ['failureRef' => $failure, 'attemptId' => wp_generate_uuid4(),
                    'state' => 'attempt_completed', 'statusCode' => 200]];
        }
        gc_collect_cycles();
        memory_reset_peak_usage();
        $baseline = memory_get_usage(false);
        $started = hrtime(true);
        foreach ($calls as [$error, $context, $observation]) {
            $sdk->captureException($error, $context);
            $sdk->captureRecovery($observation);
        }
        $elapsed = hrtime(true) - $started;
        $peak = memory_get_peak_usage(false) - $baseline;
        $live = memory_get_usage(false) - $baseline;
        $events = wpEvidenceProperty($sdk, 'buffer');
        $failures = array_filter($events, static fn (array $event): bool => $event['event_type'] === 'backend_exception');
        wpEvidenceRequire(count($failures) === count($calls), 'Original failures displaced');
        foreach ($failures as $event) {
            wpEvidenceRequire(isset($event['payload']['exception_evidence']) === $enabled, 'Evidence mode not exercised');
            if ($enabled) wpEvidenceRequire(count($event['payload']['exception_evidence']['nodes']) === 2, 'Native cause lost');
        }
        $diagnostics = count(array_filter($events, static fn (array $event): bool => $event['event_type'] === 'diagnostic_context'));
        wpEvidenceRequire($diagnostics === ($enabled ? count($calls) : 0), 'Recovery admission changed');
        $bytes = wpEvidenceProperty($sdk, 'bufferBytes');
        $diagnosticBytes = wpEvidenceProperty($sdk, 'diagnosticBudget')->accountedBytes();
        wpEvidenceRequire(count($events) <= 1000 && $bytes <= 8 * 1024 * 1024 && $diagnosticBytes <= 65536, 'Retention ceiling exceeded');
        unset($events, $failures);
        $flushStarted = hrtime(true);
        $bootstrap->onShutdown();
        $bootstrap->recordBackendFlush();
        // The PHP registered shutdown callback follows the WordPress callbacks.
        $sdk->flushAtRequestEnd();
        $flushElapsed = hrtime(true) - $flushStarted;
        wpEvidenceRequire($transport->failures === count($calls), 'WordPress request-end flush lost failures');
        $budget = wpEvidenceProperty($sdk, 'diagnosticBudget');
        // Failure-window metadata remains until its five-minute expiry, even
        // after all queued observations have been acknowledged or dropped.
        wpEvidenceRequire(wpEvidenceProperty($budget, 'pending') === [], 'Pending diagnostic ownership not released');
        wpEvidenceRequire(wpEvidenceProperty($sdk, 'buffer') === [], 'Request-end queue not released');
        return ['wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION, 'evidence' => $enabled,
            'pressure' => $pressure, 'pairs' => count($calls), 'capture_ns_per_pair' => $elapsed / count($calls),
            'peak_allocated_bytes' => $peak, 'live_allocated_bytes' => $live, 'retained_wire_bytes' => $bytes,
            'diagnostic_bytes' => $diagnosticBytes, 'retained_window_bytes_after_flush' => $budget->accountedBytes(),
            'request_end_ns' => $flushElapsed];
    } finally {
        $sdk->reset();
        remove_filter('debugbundle_evidence_options', $options);
        remove_action('init', [$bootstrap, 'onInit'], 0);
        remove_action('shutdown', [$bootstrap, 'onShutdown'], 0);
    }
}

try {
    wpEvidenceCompare(true, false);
    for ($repeat = 1; $repeat <= 3; $repeat++) {
        foreach ([false, true] as $pressure) foreach ([false, true] as $enabled) {
            echo json_encode(['repeat' => $repeat] + wpEvidenceCompare($enabled, $pressure), JSON_THROW_ON_ERROR) . "\n";
        }
    }
    echo "WordPress evidence performance comparison passed\n";
} catch (Throwable $error) {
    // The installed SDK owns PHP's uncaught handler. Fixture assertions must
    // fail WP-CLI explicitly instead of being captured as ordinary telemetry.
    fwrite(STDERR, 'WordPress performance assertion: ' . $error->getMessage() . "\n");
    exit(1);
}
