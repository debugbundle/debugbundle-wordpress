<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\CachedConfigFetcher;
use DebugBundleWp\RelayEvidencePolicy;
use DebugBundleWp\Settings;
use PHPUnit\Framework\TestCase;

final class RelayEvidencePolicyTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['debugbundle_wp_test_transients'] = [];
        $GLOBALS['debugbundle_wp_test_options'][Settings::OPTION_NAME] = ['project_token' => 'fictional'];
        unset($GLOBALS['debugbundle_wp_test_filters']['debugbundle_evidence_options']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['debugbundle_wp_test_transients'], $GLOBALS['debugbundle_wp_test_options'],
            $GLOBALS['debugbundle_wp_test_remote_request_response'], $GLOBALS['debugbundle_wp_test_filters']['debugbundle_evidence_options']);
    }

    private function refresh(bool $diagnostics = true): void
    {
        $GLOBALS['debugbundle_wp_test_remote_request_response'] = [
            'response' => ['code' => 200], 'body' => json_encode([
                'evidence_capabilities' => ['version' => 1, 'features' => \DebugBundle\EvidenceWire::FEATURES],
                'capture_policy' => ['preset' => 'balanced', 'capture_logs' => 'warning', 'capture_request_events' => 'failures_only',
                    'capture_breadcrumbs' => 'exception_only', 'capture_probe_events' => 'buffer_only', 'capture_diagnostic_context' => $diagnostics],
            ]),
        ];
        $settings = new Settings();
        (new CachedConfigFetcher())->refresh($settings->getEndpoint(), $settings->getProjectToken());
    }

    /** @return array<string,mixed> */
    private function recovery(): array
    {
        return ['schema_version' => '2026-03-01', 'event_id' => '11111111-1111-4111-8111-111111111111',
            'event_type' => 'diagnostic_context', 'sdk_name' => '@debugbundle/sdk-browser', 'sdk_version' => '3.2.0',
            'service' => ['name' => 'wordpress.browser', 'environment' => 'test'], 'occurred_at' => '2026-10-29T00:00:00Z',
            'correlation' => ['session_id' => '0', 'trace_id' => null], 'payload' => ['version' => 1, 'kind' => 'recovery',
                'failure_ref' => '22222222-2222-4222-8222-222222222222', 'attempt_id' => '33333333-3333-4333-8333-333333333333',
                'scope' => ['kind' => 'page', 'page_id' => '44444444-4444-4444-8444-444444444444'],
                'state' => 'attempt_completed', 'status_code' => 200]];
    }

    public function testRecoveryRequiresProofPolicyAndExplicitLocalOptInIncludingReplay(): void
    {
        $policy = new RelayEvidencePolicy(new Settings());
        $event = $this->recovery();
        self::assertNull($policy->prepare($event));
        $this->refresh();
        self::assertNull($policy->prepare($event));
        $GLOBALS['debugbundle_wp_test_filters']['debugbundle_evidence_options'] = static fn (): array => ['captureRecoveryObservations' => true];
        self::assertSame($event, $policy->prepare($event));
        self::assertSame('attempt_completed', $policy->prepare($event)['payload']['state']);
        $this->refresh(false);
        self::assertNull($policy->prepare($event));
        self::assertNotContains('recovery_v1', $policy->capabilities()['features']);
        self::assertNotContains('resource_observations_v1', $policy->capabilities()['features']);
        $this->refresh();
        $GLOBALS['debugbundle_wp_test_transients']['debugbundle_sdk_capture_config']['policy_expires_at'] = time() - 1;
        self::assertNull($policy->prepare($event));
        $policy->observe(['version' => 1, 'features' => \DebugBundle\EvidenceWire::FEATURES]);
        self::assertNull($policy->prepare($event));
    }

    public function testMalformedEvidenceAndDisabledLocalFeaturesNeverEscape(): void
    {
        $policy = new RelayEvidencePolicy(new Settings());
        $this->refresh();
        $GLOBALS['debugbundle_wp_test_filters']['debugbundle_evidence_options'] = static fn (): array => [
            'captureRecoveryObservations' => true, 'captureExceptionEvidence' => false, 'captureResourceObservations' => false,
        ];
        self::assertSame(['recovery_v1', 'evidence_provenance_v1'], $policy->capabilities()['features']);
        $event = $this->recovery(); $event['payload']['state'] = 'recovered';
        self::assertNull($policy->prepare($event));
        self::assertNull($policy->prepare(['payload' => null]));
        $GLOBALS['debugbundle_wp_test_transients']['debugbundle_sdk_capture_config']['payload']['capture_policy']['capture_logs'] = 'invalid';
        self::assertSame([], $policy->capabilities()['features']);
    }
}
