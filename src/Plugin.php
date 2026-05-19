<?php

declare(strict_types=1);

namespace DebugBundleWp;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    private const CRON_HOOK = 'debugbundle_flush_relay_spool';

    private static ?self $instance = null;

    private readonly Settings $settings;
    private readonly AdminPage $adminPage;
    private readonly BrowserAssets $browserAssets;
    private readonly BrowserRelayRoute $browserRelayRoute;
    private readonly SdkBootstrap $sdkBootstrap;

    private function __construct(private readonly string $pluginFile)
    {
        $this->settings = new Settings();
        $this->adminPage = new AdminPage($this->settings);
        $this->browserAssets = new BrowserAssets($this->settings, $pluginFile);
        $this->browserRelayRoute = new BrowserRelayRoute($this->settings, self::CRON_HOOK);
        $this->sdkBootstrap = new SdkBootstrap($this->settings);
    }

    public static function boot(string $pluginFile): void
    {
        if (self::$instance !== null) {
            return;
        }

        self::$instance = new self($pluginFile);
        self::$instance->register();
    }

    private function register(): void
    {
        \register_activation_hook($this->pluginFile, [$this, 'activate']);
        \register_deactivation_hook($this->pluginFile, [$this, 'deactivate']);
        \add_action('plugins_loaded', [$this, 'onPluginsLoaded']);
        \add_action(self::CRON_HOOK, [$this->browserRelayRoute, 'flushSpool']);
    }

    public function activate(): void
    {
        if (!Compatibility::isRuntimeSupported()) {
            \wp_die(\esc_html(Compatibility::failureMessage()));
        }

        $this->browserRelayRoute->ensureSpoolDirectory();
    }

    public function deactivate(): void
    {
        if (function_exists('wp_clear_scheduled_hook')) {
            \wp_clear_scheduled_hook(self::CRON_HOOK);
        }
    }

    public function onPluginsLoaded(): void
    {
        if (!Compatibility::isRuntimeSupported()) {
            return;
        }

        $this->adminPage->register();
        $this->sdkBootstrap->register();
        $this->browserAssets->register();
        $this->browserRelayRoute->register();
    }
}
