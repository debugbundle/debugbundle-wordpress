<?php

declare(strict_types=1);

namespace DebugBundleWp;

use DebugBundle\CanonicalEventSchema;
use DebugBundle\EvidenceWire;
use DebugBundle\RemoteConfig;

/** The plugin's canonical spool/forwarder is one hop in the proven delivery path. */
final class RelayEvidencePolicy
{
    public function __construct(private readonly Settings $settings)
    {
    }

    /** @return array<string, mixed>|null */
    private function payload(): ?array
    {
        return (new CachedConfigFetcher())->cachedPayload($this->settings->getEndpoint(), $this->settings->getProjectToken());
    }

    /** @return array{version:int,features:list<string>} */
    public function capabilities(): array
    {
        $payload = $this->payload();
        $snapshot = RemoteConfig::parseRemoteConfig($payload, 60000, (int) (microtime(true) * 1000));
        $descriptor = EvidenceWire::descriptor($snapshot !== null ? ($payload['evidence_capabilities'] ?? null) : null);
        if ($snapshot === null || $snapshot->capturePolicy->preset === 'minimal' || !$snapshot->capturePolicy->captureDiagnosticContext) {
            $descriptor['features'] = array_values(array_diff($descriptor['features'], ['recovery_v1', 'resource_observations_v1']));
        }
        $options = $this->settings->evidenceOptions();
        foreach (['exception_evidence_v1' => 'captureExceptionEvidence', 'recovery_v1' => 'captureRecoveryObservations', 'resource_observations_v1' => 'captureResourceObservations'] as $feature => $option) {
            if (!$options[$option]) $descriptor['features'] = array_values(array_diff($descriptor['features'], [$feature]));
        }
        return $descriptor;
    }

    /** @param array<string, mixed> $event
     * @return array<string, mixed>|null
     */
    public function prepare(array $event): ?array
    {
        $payload = $event['payload'] ?? null;
        if (!is_array($payload)) return null;
        $diagnostic = ($event['event_type'] ?? null) === 'diagnostic_context';
        $typed = $diagnostic || array_intersect(['exception_evidence', 'execution', 'resource_observations'], array_keys($payload)) !== [];
        if ($typed && (!CanonicalEventSchema::valid($event) || !EvidenceWire::optionalEvidence($event))) return null;
        if ($diagnostic) {
            $snapshot = RemoteConfig::parseRemoteConfig($this->payload(), 60000, (int) (microtime(true) * 1000));
            if ($snapshot === null || $snapshot->capturePolicy->preset === 'minimal' || !$snapshot->capturePolicy->captureDiagnosticContext) return null;
        }
        return EvidenceWire::supported($event, $this->capabilities()['features']);
    }

    public function observe(mixed $descriptor): void
    {
        (new CachedConfigFetcher())->observeEvidence($this->settings->getEndpoint(), $this->settings->getProjectToken(), $descriptor);
    }
}
