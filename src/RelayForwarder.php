<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class RelayForwarder
{
    public function __construct(private readonly Settings $settings)
    {
    }

    /** @param list<array<string, mixed>> $events */
    public function forward(array $events): RelayForwardResult
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

        $response = \wp_remote_post($this->settings->getEndpoint(), [
            'timeout' => 5,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings->getProjectToken(),
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode(['events' => $events]),
        ]);

        if (function_exists('is_wp_error') && \is_wp_error($response)) {
            Diagnostics::recordRelayError('relay_forward_failed');
            return new RelayForwardResult(false, false, 'relay_forward_failed');
        }

        $statusCode = function_exists('wp_remote_retrieve_response_code')
            ? (int) \wp_remote_retrieve_response_code($response)
            : 500;

        if ($statusCode >= 200 && $statusCode < 300) {
            $ingestionResult = $this->ingestionResult($response, count($events));
            if ($ingestionResult === null) {
                Diagnostics::recordRelayError('acknowledgement_protocol_failure');
                return new RelayForwardResult(false, false, 'acknowledgement_protocol_failure');
            }

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
            return new RelayForwardResult(false, false, 'retryable_http_' . $statusCode);
        }

        Diagnostics::recordRelayError('non_retryable_http_' . $statusCode);
        return new RelayForwardResult(false, true, 'non_retryable_http_' . $statusCode);
    }

    /**
     * @return array{
     *   accepted:int,
     *   rejected:int,
     *   errors:list<array{index:int,reason:string}>
     * }|null
     */
    private function ingestionResult(mixed $response, int $eventCount): ?array
    {
        if (!function_exists('wp_remote_retrieve_body')) {
            return null;
        }

        $body = \wp_remote_retrieve_body($response);
        if (!is_string($body) || trim($body) === '') {
            return null;
        }

        $decoded = json_decode($body, true);
        if (
            !is_array($decoded)
            || !isset($decoded['accepted'], $decoded['rejected'])
            || !is_int($decoded['accepted'])
            || !is_int($decoded['rejected'])
            || $decoded['accepted'] < 0
            || $decoded['rejected'] < 0
            || $decoded['accepted'] + $decoded['rejected'] !== $eventCount
            || !isset($decoded['errors'])
            || !is_array($decoded['errors'])
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
