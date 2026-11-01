<?php

declare(strict_types=1);

namespace DebugBundleWp;

use DebugBundle\IngestionWire;

final class RelayForwarder
{
    private readonly RelayEvidencePolicy $evidence;
    public function __construct(private readonly Settings $settings)
    {
        $this->evidence = new RelayEvidencePolicy($settings);
    }

    /** @param list<array<string, mixed>> $events */
    public function forward(array $events, ?int $deadline = null): RelayForwardResult
    {
        $events = Sanitization::protectRelayEvents($events);
        if ($events === null || $events === []) {
            Diagnostics::recordRelayError('relay_privacy_unavailable');
            return new RelayForwardResult(false, true, 'relay_privacy_unavailable');
        }
        if (!function_exists('wp_remote_post')) {
            Diagnostics::recordRelayError('wp_remote_post unavailable');
            return new RelayForwardResult(false, false, 'wp_remote_post unavailable');
        }

        $deadline = min($deadline ?? PHP_INT_MAX, hrtime(true) + 5_000_000_000);
        try {
            $prepared = [];
            foreach ($events as $event) {
                $allowed = $this->evidence->prepare($event);
                $fitted = $allowed === null ? null : IngestionWire::fit($allowed);
                if ($fitted !== null) $prepared[] = $fitted;
            }
            if ($prepared === []) return new RelayForwardResult(false, true, 'relay_event_too_large', []);
            $pending = [];
            $error = null;
            foreach (IngestionWire::chunks($prepared) as $batch) {
                $result = $this->forwardBatch($batch, $deadline);
                if (!$result->success) {
                    $error = $result->error;
                    if (!$result->drop) array_push($pending, ...($result->retryEvents ?? $batch));
                }
            }
            if ($error !== null) return new RelayForwardResult(false, $pending === [], $error, $pending === $events ? null : $pending);
            return new RelayForwardResult(true, false, null);
        } catch (\Throwable) {
            Diagnostics::recordRelayError('relay_forward_failed');
            return new RelayForwardResult(false, false, 'relay_forward_failed');
        }
    }

    /** @param list<array<string, mixed>> $events */
    private function forwardBatch(array $events, int $deadline): RelayForwardResult
    {
        // Previous chunks may have revoked proof. Recheck before every actual send.
        $events = array_values(array_filter(array_map($this->evidence->prepare(...), $events)));
        if ($events === []) return new RelayForwardResult(false, true, 'relay_evidence_withheld', []);
        $remaining = ($deadline - hrtime(true)) / 1_000_000_000;
        if ($remaining <= 0) return new RelayForwardResult(false, false, 'relay_forward_deadline', $events);

        $response = \wp_remote_post($this->settings->getEndpoint(), [
            'timeout' => $remaining,
            'redirection' => 0,
            'limit_response_size' => 262145,
            'decompress' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings->getProjectToken(),
                'Content-Type' => 'application/json',
                'Accept-Encoding' => 'identity',
                'X-DebugBundle-Evidence' => '1',
            ],
            'body' => IngestionWire::body($events),
        ]);

        if (function_exists('is_wp_error') && \is_wp_error($response)) {
            Diagnostics::recordRelayError('relay_forward_failed');
            return new RelayForwardResult(false, false, 'relay_forward_failed', $events);
        }

        $statusCode = function_exists('wp_remote_retrieve_response_code')
            ? (int) \wp_remote_retrieve_response_code($response)
            : 500;

        if ($statusCode >= 200 && $statusCode < 300) {
            $ingestionResult = $this->ingestionResult($response, count($events));
            if ($ingestionResult === null) {
                $this->evidence->observe(null);
                Diagnostics::recordRelayError('acknowledgement_protocol_failure');
                return new RelayForwardResult(false, false, 'acknowledgement_protocol_failure', $events);
            }

            $this->evidence->observe($ingestionResult['evidence_capabilities']);

            $formattedErrors = $this->formatIngestionErrors($ingestionResult['errors']);
            Diagnostics::recordRelayIngestionResult(
                $ingestionResult['accepted'],
                $ingestionResult['rejected'],
                $formattedErrors
            );
            if ($ingestionResult['rejected'] > 0) {
                $retryEvents = [];
                foreach ($ingestionResult['errors'] as $error) {
                    if ($this->isRetryableReason($error['reason'])) {
                        $retryEvents[] = $events[$error['index']];
                    }
                }

                $message = sprintf(
                    'ingestion_rejected accepted=%d expected=%d rejected=%d errors=%s',
                    $ingestionResult['accepted'],
                    count($events),
                    $ingestionResult['rejected'],
                    $formattedErrors
                );
                Diagnostics::recordRelayError($message);
                if ($retryEvents !== []) {
                    return new RelayForwardResult(false, false, $message, $retryEvents);
                }
                return new RelayForwardResult(false, true, $message, []);
            }

            Diagnostics::recordRelayFlush();
            return new RelayForwardResult(true, false, null);
        }

        if ($statusCode === 429 || $statusCode >= 500) {
            Diagnostics::recordRelayError('retryable_http_' . $statusCode);
            return new RelayForwardResult(false, false, 'retryable_http_' . $statusCode, $events);
        }

        Diagnostics::recordRelayError('non_retryable_http_' . $statusCode);
        return new RelayForwardResult(false, true, 'non_retryable_http_' . $statusCode);
    }

    /**
     * @return array{
     *   accepted:int,
     *   rejected:int,
     *   errors:list<array{index:int,reason:string}>
     *   ,evidence_capabilities:mixed
     * }|null
     */
    private function ingestionResult(mixed $response, int $eventCount): ?array
    {
        if (!function_exists('wp_remote_retrieve_body')) {
            return null;
        }
        $headers = function_exists('wp_remote_retrieve_headers') ? \wp_remote_retrieve_headers($response) : [];
        if (is_object($headers) && method_exists($headers, 'getAll')) $headers = $headers->getAll();
        if (is_array($headers)) foreach ($headers as $key => $value) {
            if (is_string($key) && strtolower($key) === 'content-encoding'
                && (!is_string($value) || !in_array(strtolower(trim($value)), ['', 'identity'], true))) return null;
        }

        $body = \wp_remote_retrieve_body($response);
        if (!is_string($body) || strlen($body) > 262144 || trim($body) === '') {
            return null;
        }

        $decoded = json_decode($body, true);
        $wire = json_decode($body);
        if (
            !$wire instanceof \stdClass || !is_array($wire->errors ?? null)
            || !is_array($decoded)
            || !isset($decoded['accepted'], $decoded['rejected'])
            || !is_int($decoded['accepted'])
            || !is_int($decoded['rejected'])
            || $decoded['accepted'] < 0
            || $decoded['rejected'] < 0
            || $decoded['accepted'] + $decoded['rejected'] !== $eventCount
            || !isset($decoded['errors'])
            || !is_array($decoded['errors'])
            || !array_is_list($decoded['errors'])
        ) {
            return null;
        }

        $errors = $this->normalizeIngestionErrors($decoded['errors'], $eventCount);
        if ($errors === null || count($errors) !== $decoded['rejected']) {
            return null;
        }

        return [
            'accepted' => $decoded['accepted'],
            'rejected' => $decoded['rejected'],
            'errors' => $errors,
            'evidence_capabilities' => ($wire->evidence_capabilities ?? null) instanceof \stdClass
                && is_array($wire->evidence_capabilities->features ?? null) ? ($decoded['evidence_capabilities'] ?? null) : null,
        ];
    }

    /** @return list<array{index:int,reason:string}>|null */
    private function normalizeIngestionErrors(array $errors, int $eventCount): ?array
    {
        $normalized = [];
        $indexes = [];
        foreach ($errors as $error) {
            if (
                !is_array($error)
                || !isset($error['index'], $error['reason'])
                || !is_int($error['index'])
                || $error['index'] < 0
                || $error['index'] >= $eventCount
                || isset($indexes[$error['index']])
                || !is_string($error['reason'])
                || $error['reason'] === ''
            ) {
                return null;
            }

            $indexes[$error['index']] = true;
            $normalized[] = [
                'index' => $error['index'],
                'reason' => preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $error['reason']) === 1
                    ? $error['reason'] : 'unknown_rejection',
            ];
        }

        return $normalized;
    }

    /** @param list<array{index:int,reason:string}> $errors */
    private function formatIngestionErrors(array $errors): string
    {
        if ($errors === []) {
            return 'none';
        }

        return implode('; ', array_map(
            static fn (array $error): string => sprintf('event[%d]: %s', $error['index'], $error['reason']),
            array_slice($errors, 0, 5)
        ));
    }

    private function isRetryableReason(string $reason): bool
    {
        return in_array($reason, [
            'rate_limited',
            'monthly_quota_exceeded',
            'analytics_quota_exceeded',
        ], true);
    }
}
