<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class AdminPage
{
    public function __construct(private readonly Settings $settings)
    {
    }

    public function register(): void
    {
        if (!function_exists('add_action')) {
            return;
        }

        \add_action('admin_menu', [$this, 'addOptionsPage']);
        \add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addOptionsPage(): void
    {
        if (!function_exists('add_options_page')) {
            return;
        }

        \add_options_page(
            'DebugBundle',
            'DebugBundle',
            'manage_options',
            'debugbundle',
            [$this, 'render'],
        );
    }

    public function registerSettings(): void
    {
        if (!function_exists('register_setting')) {
            return;
        }

        \register_setting(Settings::OPTION_NAME, Settings::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => fn (mixed $input): array => $this->settings->sanitize(is_array($input) ? $input : []),
            'default' => $this->settings->all(),
        ]);
    }

    public function render(): void
    {
        if (!function_exists('current_user_can') || !\current_user_can('manage_options')) {
            return;
        }

        $values = $this->settings->all();
        $maskedToken = $this->settings->maskProjectToken();
        $projectTokenValue = defined(Settings::PROJECT_TOKEN_CONSTANT)
            ? ''
            : (string) ($values['project_token'] ?? '');

        echo '<div class="wrap">';
        echo '<h1>DebugBundle</h1>';
        echo '<p>Capture backend and frontend incidents from WordPress into DebugBundle.</p>';
        echo '<form method="post" action="options.php">';
        \settings_fields(Settings::OPTION_NAME);
        echo '<table class="form-table" role="presentation">';
        $this->renderTextRow('Project token', Settings::OPTION_NAME . '[project_token]', $projectTokenValue, defined(Settings::PROJECT_TOKEN_CONSTANT) ? 'Configured via wp-config.php.' : ($maskedToken !== '' ? 'Saved token is masked after save.' : 'Paste your DebugBundle project token.'));
        $this->renderTextRow('Environment', Settings::OPTION_NAME . '[environment]', (string) ($values['environment'] ?? ''), 'Defaults to the WordPress environment type.');
        $this->renderTextRow('Service', Settings::OPTION_NAME . '[service]', (string) ($values['service'] ?? ''), 'Default is the site host with a wordpress suffix.');
        $this->renderTextRow('Ingestion endpoint', Settings::OPTION_NAME . '[endpoint]', (string) ($values['endpoint'] ?? ''), 'Leave as the hosted API unless self-hosting.');
        $this->renderCheckboxRow('Enable plugin', Settings::OPTION_NAME . '[enabled]', (bool) ($values['enabled'] ?? true));
        $this->renderCheckboxRow('Enable backend capture', Settings::OPTION_NAME . '[backend_capture_enabled]', (bool) ($values['backend_capture_enabled'] ?? true));
        $this->renderCheckboxRow('Enable frontend capture', Settings::OPTION_NAME . '[frontend_capture_enabled]', (bool) ($values['frontend_capture_enabled'] ?? true));
        $this->renderCheckboxRow('Capture browser console warnings/errors', Settings::OPTION_NAME . '[browser_capture_console]', (bool) ($values['browser_capture_console'] ?? false));
        $this->renderTextRow('Sample rate', Settings::OPTION_NAME . '[sample_rate]', (string) ($values['sample_rate'] ?? '1'), '0.0 to 1.0.');
        $this->renderTextRow('Browser session sample rate', Settings::OPTION_NAME . '[browser_session_sample_rate]', (string) ($values['browser_session_sample_rate'] ?? '1'), '0.0 to 1.0.');
        $this->renderTextRow('Browser max events per session', Settings::OPTION_NAME . '[browser_max_events_per_session]', (string) ($values['browser_max_events_per_session'] ?? '100'), 'Exceptions still bypass the cap.');
        $this->renderTextRow('Log level', Settings::OPTION_NAME . '[log_level]', (string) ($values['log_level'] ?? 'warning'), 'debug, info, warning, error, or critical');
        $this->renderCheckboxRow('Delete settings on uninstall', Settings::OPTION_NAME . '[delete_on_uninstall]', (bool) ($values['delete_on_uninstall'] ?? false));
        echo '</table>';
        \submit_button('Save settings');
        echo '</form>';
        echo '<hr/>';
        echo '<h2>Status</h2>';
        echo '<ul>';
        echo '<li>Backend capture: ' . esc_html($this->settings->isBackendCaptureEnabled() ? 'enabled' : 'disabled') . '</li>';
        echo '<li>Frontend capture: ' . esc_html($this->settings->isFrontendCaptureEnabled() ? 'enabled' : 'disabled') . '</li>';
        echo '<li>Environment: ' . esc_html($this->settings->getEnvironment()) . '</li>';
        echo '<li>Service: ' . esc_html($this->settings->getService()) . '</li>';
        echo '<li>Relay route: ' . esc_html($this->settings->getRelayRoute()) . '</li>';
        echo '</ul>';
        echo '</div>';
    }

    private function renderTextRow(string $label, string $name, string $value, string $description = ''): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';
        echo '<input class="regular-text" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" type="text" value="' . esc_attr($value) . '" />';
        if ($description !== '') {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
        echo '</td>';
        echo '</tr>';
    }

    private function renderCheckboxRow(string $label, string $name, bool $checked): void
    {
        echo '<tr>';
        echo '<th scope="row">' . esc_html($label) . '</th>';
        echo '<td>';
        echo '<label>';
        echo '<input name="' . esc_attr($name) . '" type="checkbox" value="1" ' . checked($checked, true, false) . ' />';
        echo ' ' . esc_html($label);
        echo '</label>';
        echo '</td>';
        echo '</tr>';
    }
}
