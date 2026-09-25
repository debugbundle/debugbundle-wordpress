<?php

declare(strict_types=1);

// Exercise the classes from the extracted ZIP; only WordPress host functions
// are stubbed. Never substitute the plugin's checked-out Settings or asset code.
$plugin = $argv[1] ?? '';
define('ABSPATH', '/wordpress/');
function get_option(string $name, mixed $default = false): mixed
{
    return $name === 'debugbundle_settings' ? [
        'enabled' => true, 'frontend_capture_enabled' => true,
        'project_token' => 'dbundle_proj_ZIP_SERVER_SECRET',
        'service' => 'zip-smoke', 'environment' => 'production',
    ] : $default;
}
function home_url(string $path): string { return 'https://wordpress.example' . $path; }
function wp_parse_url(string $url, int $component): mixed { return parse_url($url, $component); }
function plugins_url(string $path, string $plugin): string { return '/wp-content/plugins/debugbundle/' . $path; }
function wp_register_script(string $handle, string $url, array $deps, string $version, bool $footer): void {}
function wp_enqueue_script(string $handle): void {}
function wp_json_encode(mixed $value): string { return json_encode($value, JSON_THROW_ON_ERROR); }
function wp_add_inline_script(string $handle, string $script, string $position): void
{
    if ($handle !== 'debugbundle-browser' || $position !== 'before') {
        throw new RuntimeException('Unexpected browser configuration placement');
    }
    echo $script;
}
require $plugin . '/src/Settings.php';
require $plugin . '/src/BrowserAssets.php';
(new \DebugBundleWp\BrowserAssets(new \DebugBundleWp\Settings(), $plugin . '/debugbundle.php'))->enqueue();
