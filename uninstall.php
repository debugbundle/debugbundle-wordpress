<?php

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$settings = get_option('debugbundle_settings', []);
if (!is_array($settings) || !($settings['delete_on_uninstall'] ?? false)) {
    return;
}

delete_option('debugbundle_settings');

$uploadDir = function_exists('wp_upload_dir') ? wp_upload_dir() : null;
if (is_array($uploadDir) && isset($uploadDir['basedir']) && is_string($uploadDir['basedir'])) {
    $spoolPath = rtrim($uploadDir['basedir'], '/\\') . '/debugbundle-spool';
    if (is_dir($spoolPath)) {
        $files = glob($spoolPath . '/*');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_string($file) && is_file($file)) {
                    @unlink($file);
                }
            }
        }
        @rmdir($spoolPath);
    }
}
