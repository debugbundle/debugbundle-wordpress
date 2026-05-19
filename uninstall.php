<?php

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (!function_exists('debugbundle_wordpress_uninstall_filesystem')) {
    function debugbundle_wordpress_uninstall_filesystem(): ?object
    {
        if (!function_exists('WP_Filesystem') && defined('ABSPATH')) {
            $debugbundleFilesystemBootstrap = ABSPATH . 'wp-admin/includes/file.php';
            if (is_readable($debugbundleFilesystemBootstrap)) {
                require_once $debugbundleFilesystemBootstrap;
            }
        }

        if (function_exists('WP_Filesystem')) {
            \WP_Filesystem();
        }

        global $wp_filesystem;

        return is_object($wp_filesystem) ? $wp_filesystem : null;
    }
}

$debugbundleSettings = get_option('debugbundle_settings', []);
if (!is_array($debugbundleSettings) || !($debugbundleSettings['delete_on_uninstall'] ?? false)) {
    return;
}

delete_option('debugbundle_settings');

$debugbundleUploadDir = function_exists('wp_upload_dir') ? wp_upload_dir() : null;
if (is_array($debugbundleUploadDir) && isset($debugbundleUploadDir['basedir']) && is_string($debugbundleUploadDir['basedir'])) {
    $debugbundleSpoolPath = rtrim($debugbundleUploadDir['basedir'], '/\\') . '/debugbundle-spool';
    if (is_dir($debugbundleSpoolPath)) {
        $debugbundleFiles = glob($debugbundleSpoolPath . '/*');
        if (is_array($debugbundleFiles)) {
            foreach ($debugbundleFiles as $debugbundleFile) {
                if (is_string($debugbundleFile) && is_file($debugbundleFile) && function_exists('wp_delete_file')) {
                    \wp_delete_file($debugbundleFile);
                }
            }
        }

        $debugbundleFilesystem = debugbundle_wordpress_uninstall_filesystem();
        if ($debugbundleFilesystem !== null && method_exists($debugbundleFilesystem, 'rmdir')) {
            $debugbundleFilesystem->rmdir($debugbundleSpoolPath, false);
        }
    }
}
