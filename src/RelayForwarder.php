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
            $message = method_exists($response, 'get_error_message') ? (string) $response->get_error_message() : 'relay_forward_failed';
            Diagnostics::recordRelayError($message);
            return new RelayForwardResult(false, false, $message);
        }

        $statusCode = function_exists('wp_remote_retrieve_response_code')
            ? (int) \wp_remote_retrieve_response_code($response)
            : 500;

        if ($statusCode >= 200 && $statusCode < 300) {
            $ingestionResult = $this->ingestionResult($response);
            if ($ingestionResult !== null) {
                $formattedErrors = $this->formatIngestionErrors($ingestionResult['errors']);
                Diagnostics::recordRelayIngestionResult($ingestionResult['accepted'], $ingestionResult['rejected'], $formattedErrors);
                $expected = count($events);
                if ($ingestionResult['accepted'] < $expected || $ingestionResult['rejected'] > 0 || $ingestionResult['errors'] !== []) {
                    $message = sprintf(
                        'ingestion_rejected accepted=%d expected=%d rejected=%d errors=%s',
                        $ingestionResult['accepted'],
                        $expected,
                        $ingestionResult['rejected'],
                        $formattedErrors
                    );
                    Diagnostics::recordRelayError($message);
                    return new RelayForwardResult(false, true, $message);
                }
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

    /** @return array{accepted:int,rejected:int,errors:list<string>}|null */
    private function ingestionResult(mixed $response): ?array
    {
        if (!function_exists('wp_remote_retrieve_body')) {
            return null;
        }

        $body = \wp_remote_retrieve_body($response);
        if (!is_string($body) || trim($body) === '') {
            return null;
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || !array_key_exists('accepted', $decoded) || !array_key_exists('rejected', $decoded)) {
            return null;
        }

        return [
            'accepted' => max(0, (int) $decoded['accepted']),
            'rejected' => max(0, (int) $decoded['rejected']),
            'errors' => $this->normalizeIngestionErrors($decoded['errors'] ?? []),
        ];
    }

    /** @return list<string> */
    private function normalizeIngestionErrors(mixed $errors): array
    {
        if (!is_array($errors)) {
            return [];
        }

        $normalized = [];
        foreach ($errors as $error) {
            if (is_string($error)) {
                $normalized[] = $error;
                continue;
            }

            if (is_array($error)) {
                $index = $error['index'] ?? null;
                $reason = $error['reason'] ?? null;
                if (is_string($reason) && $reason !== '') {
                    $normalized[] = is_int($index) ? sprintf('event[%d]: %s', $index, $reason) : $reason;
                }
            }
        }

        return $normalized;
    }

    /** @param list<string> $errors */
    private function formatIngestionErrors(array $errors): string
    {
        return $errors === [] ? 'none' : implode('; ', array_slice($errors, 0, 5));
    }
}
