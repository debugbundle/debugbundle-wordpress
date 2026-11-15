<?php
declare(strict_types=1);

/** Root readiness before and after the disposable WordPress installation.
 * @param list<string> $headers
 */
function debugbundleSmokeWordPressReady(array $headers): bool
{
    $status = $headers[0] ?? '';
    if (preg_match('/^HTTP\/\S+ 200\b/', $status) === 1) return true;
    if (preg_match('/^HTTP\/\S+ 302\b/', $status) !== 1) return false;
    foreach ($headers as $header) {
        if (stripos($header, 'Location:') === 0) {
            return parse_url(trim(substr($header, 9)), PHP_URL_PATH) === '/wp-admin/install.php';
        }
    }
    return false;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    // Use the public Host while connecting to the internal HTTP port. Before
    // installation, accept only WordPress's explicit installer redirect; never
    // follow an externally published port from inside the container.
    $context = stream_context_create(['http' => [
        'header' => "Host: 127.0.0.1:18080\r\n",
        'follow_location' => 0,
        'timeout' => 3,
        'ignore_errors' => true,
    ]]);
    $body = @file_get_contents('http://127.0.0.1/', false, $context, 0, 1);
    exit($body !== false && debugbundleSmokeWordPressReady($http_response_header ?? []) ? 0 : 1);
}
