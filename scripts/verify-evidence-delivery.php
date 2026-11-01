<?php

declare(strict_types=1);

// Executed by the real installed plugin's WP-CLI, using only its vendored SDK.
$check = static function (bool $valid, string $message): void {
    if (!$valid) throw new RuntimeException($message);
};
$wire = (string) file_get_contents('/debugbundle-smoke-state/ingestion-events.ndjson');
$check(!str_contains($wire, 'NATIVE_FIXTURE_SECRET'), 'Native private context escaped');
$events = [];
foreach (explode("\n", trim($wire)) as $line) {
    foreach (json_decode($line, true, 512, JSON_THROW_ON_ERROR)['events'] as $event) $events[] = $event;
}
$native = array_values(array_filter($events, static fn (array $event): bool =>
    ($event['payload']['message'] ?? null) === 'WordPress native evidence fixture'));
$check(count($native) === 1, 'Expected exactly one native exception');
$native = $native[0];
$check(\DebugBundle\CanonicalEventSchema::valid($native), 'Invalid native wire event');
$graph = $native['payload']['exception_evidence'] ?? [];
$check(\DebugBundle\EvidenceWire::graph($graph), 'Missing native graph');
$check(count($graph['nodes']) === 2 && $graph['nodes'][0]['relationships'][0]['kind'] === 'cause', 'Missing native cause');
$reference = $native['context']['evidence_reference'];
$recovery = array_values(array_filter($events, static fn (array $event): bool =>
    ($event['event_type'] ?? null) === 'diagnostic_context' && ($event['payload']['failure_ref'] ?? null) === $reference['failure_ref']));
$check(count($recovery) === 1, 'Expected independent native observation');
$check(\DebugBundle\CanonicalEventSchema::valid($recovery[0]) && \DebugBundle\EvidenceWire::optionalEvidence($recovery[0]), 'Invalid native observation');
$check($recovery[0]['payload']['scope'] === $reference['scope'] && $recovery[0]['payload']['state'] === 'attempt_completed'
    && $recovery[0]['payload']['status_code'] === 200, 'Native completion semantics changed');
foreach (['browser', 'spooled', 'revoked'] as $kind) {
    $input = json_decode((string) file_get_contents('/debugbundle-smoke-state/' . $kind . '-evidence.json'), true, 512, JSON_THROW_ON_ERROR)['batch'];
    foreach ($input as $expected) {
        $received = array_values(array_filter($events, static fn (array $event): bool => $event['event_id'] === $expected['event_id']));
        $diagnostic = $expected['event_type'] === 'diagnostic_context';
        $check(count($received) === ($kind === 'revoked' && $diagnostic ? 0 : 1), 'Missing or duplicated ' . $kind . ' event');
        if ($received === []) continue;
        $actual = $received[0];
        $check(\DebugBundle\CanonicalEventSchema::valid($actual) && \DebugBundle\EvidenceWire::optionalEvidence($actual), 'Invalid relayed wire event');
        if ($kind === 'revoked') {
            $check(!isset($actual['payload']['exception_evidence']) && !isset($actual['context']['evidence_reference']), 'Revoked evidence retained');
        } elseif ($diagnostic) {
            $check($actual['payload'] === $expected['payload'], 'Standalone observation changed');
        } else {
            $check($actual['payload']['exception_evidence'] === $expected['payload']['exception_evidence'], 'Relayed graph changed');
            $check($actual['context']['evidence_reference'] === $expected['context']['evidence_reference'], 'Relayed reference changed');
        }
    }
}
echo "Installed ZIP native, browser, spool and revocation evidence passed.\n";
