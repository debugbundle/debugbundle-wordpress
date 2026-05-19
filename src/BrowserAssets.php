<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class BrowserAssets
{
    public function __construct(
        private readonly Settings $settings,
        private readonly string $pluginFile,
    ) {
    }

    public function register(): void
    {
        if (!function_exists('add_action')) {
            return;
        }

        \add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        if (!$this->settings->isFrontendCaptureEnabled()) {
            return;
        }

        if (!function_exists('wp_enqueue_script') || !function_exists('plugins_url')) {
            return;
        }

        $handle = 'debugbundle-browser';
        $relativeAssetPath = 'assets/dist/debugbundle-browser.js';
        $filePath = dirname($this->pluginFile) . '/' . $relativeAssetPath;
        if (!is_readable($filePath)) {
            return;
        }

        $assetUrl = \plugins_url($relativeAssetPath, $this->pluginFile);
        $version = is_file($filePath) ? (string) filemtime($filePath) : '0.1.0';

        \wp_register_script($handle, $assetUrl, [], $version, true);
        \wp_add_inline_script(
            $handle,
            'window.DebugBundleWordPressConfig = ' . wp_json_encode($this->settings->frontendScriptConfig()) . ';',
            'before'
        );
        \wp_enqueue_script($handle);
    }
}
