<?php

declare(strict_types=1);

namespace DebugBundleWp;

use RuntimeException;

final class ConfigFetcher
{
    public function __invoke(string $url, array $request): ConfigFetchResponse
    {
        if (!function_exists('wp_remote_request')) {
            throw new RuntimeException('wp_remote_request unavailable');
        }

        $headers = [];
        foreach (($request['headers'] ?? []) as $name => $value) {
            if (is_string($name) && is_scalar($value)) {
                $headers[$name] = (string) $value;
            }
        }

        $response = \wp_remote_request($url, [
            'method' => strtoupper((string) ($request['method'] ?? 'GET')),
            'headers' => $headers,
            'timeout' => 3,
        ]);

        if (function_exists('is_wp_error') && \is_wp_error($response)) {
            $message = method_exists($response, 'get_error_message') ? (string) $response->get_error_message() : 'config_request_failed';
            throw new RuntimeException($message);
        }

        $statusCode = function_exists('wp_remote_retrieve_response_code')
            ? (int) \wp_remote_retrieve_response_code($response)
            : 500;
        $body = function_exists('wp_remote_retrieve_body')
            ? (string) \wp_remote_retrieve_body($response)
            : '';
        $responseHeaders = function_exists('wp_remote_retrieve_headers')
            ? \wp_remote_retrieve_headers($response)
            : [];

        $normalizedHeaders = [];
        if (is_array($responseHeaders)) {
            foreach ($responseHeaders as $name => $value) {
                if (is_string($name) && is_scalar($value)) {
                    $normalizedHeaders[strtolower($name)] = (string) $value;
                }
            }
        } elseif (is_object($responseHeaders) && method_exists($responseHeaders, 'getAll')) {
            foreach ((array) $responseHeaders->getAll() as $name => $value) {
                if (is_string($name) && is_scalar($value)) {
                    $normalizedHeaders[strtolower($name)] = (string) $value;
                }
            }
        }

        $payload = null;
        if ($body !== '') {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        return new ConfigFetchResponse($statusCode, $normalizedHeaders, $payload);
    }
}
