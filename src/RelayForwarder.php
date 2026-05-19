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
}
