<?php

declare(strict_types=1);

// Integration fixture copied only to the disposable WordPress smoke volume.
add_filter('debugbundle_evidence_options', static fn (array $options): array =>
    array_replace($options, ['captureRecoveryObservations' => true]));
add_action('debugbundle_sdk_ready', static function (\DebugBundle\DebugBundleSdk $sdk): void {
    if (($_GET['debugbundle_evidence_smoke'] ?? null) !== '1') return;
    add_action('init', static function () use ($sdk): void {
        $sdk->runWithOperationContext(['kind' => 'request', 'name' => 'wordpress-evidence-fixture'], static function () use ($sdk): void {
            $failureRef = wp_generate_uuid4();
            $sdk->captureException(new RuntimeException('WordPress native evidence fixture', 0,
                new LogicException('WordPress native cause fixture')), ['failureRef' => $failureRef, 'password' => 'NATIVE_FIXTURE_SECRET']);
            $sdk->captureRecovery(['failureRef' => $failureRef, 'attemptId' => wp_generate_uuid4(),
                'state' => 'attempt_completed', 'statusCode' => 200]);
        });
        echo 'DebugBundle native evidence captured';
        exit;
    }, 10);
});
