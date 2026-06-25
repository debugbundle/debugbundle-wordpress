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

        $safeUrl = $this->sanitizeUrl($url);
        if ($safeUrl === '') {
            throw new RuntimeException('config_request_failed');
        }

        $headers = [];
        foreach (($request['headers'] ?? []) as $name => $value) {
            if (is_string($name) && is_scalar($value)) {
                $headerName = $this->sanitizeHeaderName($name);
                if ($headerName !== '') {
                    $headers[$headerName] = $this->sanitizeText((string) $value);
                }
            }
        }

        $response = \wp_remote_request($safeUrl, [
            'method' => $this->sanitizeMethod((string) ($request['method'] ?? 'GET')),
            'headers' => $headers,
            'timeout' => 3,
        ]);

        if (function_exists('is_wp_error') && \is_wp_error($response)) {
            throw new RuntimeException('config_request_failed');
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
                    $headerName = $this->sanitizeHeaderName($name);
                    if ($headerName !== '') {
                        $normalizedHeaders[strtolower($headerName)] = $this->sanitizeText((string) $value);
                    }
                }
            }
        } elseif (is_object($responseHeaders) && method_exists($responseHeaders, 'getAll')) {
            foreach ((array) $responseHeaders->getAll() as $name => $value) {
                if (is_string($name) && is_scalar($value)) {
                    $headerName = $this->sanitizeHeaderName($name);
                    if ($headerName !== '') {
                        $normalizedHeaders[strtolower($headerName)] = $this->sanitizeText((string) $value);
                    }
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

    private function sanitizeUrl(string $url): string
    {
        if (function_exists('wp_unslash')) {
            $url = (string) \wp_unslash($url);
        }

        $sanitized = function_exists('esc_url_raw')
            ? \esc_url_raw($url)
            : filter_var(trim($url), FILTER_SANITIZE_URL);

        if (!is_string($sanitized) || $sanitized === '') {
            return '';
        }

        $scheme = \wp_parse_url($sanitized, PHP_URL_SCHEME);
        return in_array($scheme, ['http', 'https'], true) ? $sanitized : '';
    }

    private function sanitizeMethod(string $method): string
    {
        $key = function_exists('sanitize_key')
            ? \sanitize_key($method)
            : (preg_replace('/[^a-z0-9_\-]/', '', strtolower($method)) ?? '');
        $method = strtoupper($key);

        return in_array($method, ['GET', 'POST', 'HEAD'], true) ? $method : 'GET';
    }

    private function sanitizeHeaderName(string $name): string
    {
        $name = trim($name);
        return preg_match('/^[A-Za-z0-9-]+$/', $name) === 1 ? $name : '';
    }

    private function sanitizeText(string $value): string
    {
        if (function_exists('wp_unslash')) {
            $value = (string) \wp_unslash($value);
        }

        if (function_exists('sanitize_text_field')) {
            return (string) \sanitize_text_field($value);
        }

        $withoutHtml = preg_replace('/<[^>]*>/', '', $value);
        return trim(is_string($withoutHtml) ? $withoutHtml : $value);
    }
}
