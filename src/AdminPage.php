<?php

declare(strict_types=1);

namespace DebugBundleWp;

if (!defined('ABSPATH')) {
    exit;
}

final class AdminPage
{
    private const NOTICE_NONCE_ACTION = 'debugbundle_notice';

    private readonly AdminTestEvents $testEvents;
    private readonly Diagnostics $diagnostics;

    public function __construct(private readonly Settings $settings, ?AdminTestEvents $testEvents = null, ?Diagnostics $diagnostics = null)
    {
        $this->testEvents = $testEvents ?? new AdminTestEvents($settings);
        $this->diagnostics = $diagnostics ?? new Diagnostics();
    }

    public function register(): void
    {
        if (!function_exists('add_action')) {
            return;
        }

        \add_action('admin_menu', [$this, 'addOptionsPage']);
        \add_action('admin_init', [$this, 'registerSettings']);
        \add_action('admin_notices', [$this, 'renderAdminNotice']);
        \add_action('admin_post_debugbundle_backend_test', [$this, 'handleBackendTest']);
        \add_action('admin_post_debugbundle_frontend_test', [$this, 'handleFrontendTest']);
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
            'sanitize_callback' => [$this, 'sanitizeSettings'],
            'default' => $this->settings->all(),
        ]);
    }

    /** @return array<string, mixed> */
    public function sanitizeSettings(mixed $input): array
    {
        return $this->settings->sanitize(is_array($input) ? $input : []);
    }

    public function render(): void
    {
        if (!function_exists('current_user_can') || !\current_user_can('manage_options')) {
            return;
        }

        $values = $this->settings->all();
        $diagnostics = $this->diagnostics->status();
        $projectTokenValue = defined(Settings::PROJECT_TOKEN_CONSTANT)
            ? ''
            : (string) ($values['project_token'] ?? '');

        echo '<div class="wrap">';
        echo '<h1>DebugBundle</h1>';
        echo '<p>Capture backend and frontend incidents from WordPress into DebugBundle.</p>';
        echo '<form method="post" action="options.php">';
        \settings_fields(Settings::OPTION_NAME);
        echo '<table class="form-table" role="presentation">';
        $this->renderTextRow(
            'Project token',
            Settings::OPTION_NAME . '[project_token]',
            $projectTokenValue,
            defined(Settings::PROJECT_TOKEN_CONSTANT)
                ? 'Configured via wp-config.php.'
                : ($projectTokenValue !== ''
                    ? 'Stored in a password field and hidden on screen by default.'
                    : 'Paste your DebugBundle project token.'),
            defined(Settings::PROJECT_TOKEN_CONSTANT) ? 'text' : 'password',
            defined(Settings::PROJECT_TOKEN_CONSTANT) ? '' : 'new-password'
        );
        $this->renderTextRow('Environment', Settings::OPTION_NAME . '[environment]', (string) ($values['environment'] ?? ''), 'Defaults to the WordPress environment type.');
        $this->renderTextRow('Service', Settings::OPTION_NAME . '[service]', (string) ($values['service'] ?? ''), 'Default is the site host with a wordpress suffix.');
        $this->renderTextRow('Ingestion endpoint', Settings::OPTION_NAME . '[endpoint]', (string) ($values['endpoint'] ?? ''), 'Leave as the hosted API unless self-hosting.');
        $this->renderCheckboxRow('Enable plugin', Settings::OPTION_NAME . '[enabled]', (bool) ($values['enabled'] ?? true));
        $this->renderCheckboxRow('Enable backend capture', Settings::OPTION_NAME . '[backend_capture_enabled]', (bool) ($values['backend_capture_enabled'] ?? true));
        $this->renderCheckboxRow('Enable frontend capture', Settings::OPTION_NAME . '[frontend_capture_enabled]', (bool) ($values['frontend_capture_enabled'] ?? true));
        $this->renderCheckboxRow('Load browser SDK in document head', Settings::OPTION_NAME . '[browser_load_in_head]', (bool) ($values['browser_load_in_head'] ?? false));
        $this->renderCheckboxRow('Capture browser console warnings/errors', Settings::OPTION_NAME . '[browser_capture_console]', (bool) ($values['browser_capture_console'] ?? false));
        $this->renderTextRow('Sample rate', Settings::OPTION_NAME . '[sample_rate]', (string) ($values['sample_rate'] ?? '1'), [
            'Controls how much backend traffic is captured.',
            'Use 1.0 to capture everything, 0.5 to keep about half of events, or 0.0 to stop sending backend events.',
        ]);
        $this->renderTextRow('Browser session sample rate', Settings::OPTION_NAME . '[browser_session_sample_rate]', (string) ($values['browser_session_sample_rate'] ?? '1'), [
            'Controls how many visitor sessions enable frontend capture at all.',
            'Use 1.0 for every session, 0.25 for roughly one in four sessions, or 0.0 to disable frontend capture.',
        ]);
        $this->renderTextRow('Browser max events per session', Settings::OPTION_NAME . '[browser_max_events_per_session]', (string) ($values['browser_max_events_per_session'] ?? '100'), [
            'Limits non-exception frontend events per browser session so noisy tabs do not flood ingestion.',
            'Frontend exceptions still bypass this cap and are always sent.',
        ]);
        $this->renderTextRow('Log level', Settings::OPTION_NAME . '[log_level]', (string) ($values['log_level'] ?? 'warning'), [
            'Debug captures everything.',
            'Warning keeps warnings and errors.',
            'Error keeps only errors and critical logs.',
            'Choose the minimum backend log severity to capture.',
        ]);
        $this->renderCheckboxRow('Delete settings on uninstall', Settings::OPTION_NAME . '[delete_on_uninstall]', (bool) ($values['delete_on_uninstall'] ?? false));
        echo '</table>';
        \submit_button('Save settings');
        echo '</form>';
        echo '<hr/>';
        echo '<h2>Status</h2>';
        echo '<ul>';
        echo '<li>Plugin version: ' . esc_html((string) $diagnostics['plugin_version']) . '</li>';
        echo '<li>PHP SDK version: ' . esc_html((string) $diagnostics['php_sdk_version']) . '</li>';
        echo '<li>Browser SDK version: ' . esc_html((string) $diagnostics['browser_sdk_version']) . '</li>';
        echo '<li>PHP compatibility: ' . esc_html((string) $diagnostics['php_compatibility']) . '</li>';
        echo '<li>Backend capture: ' . esc_html($this->settings->isBackendCaptureEnabled() ? 'enabled' : 'disabled') . '</li>';
        echo '<li>Frontend capture: ' . esc_html($this->settings->isFrontendCaptureEnabled() ? 'enabled' : 'disabled') . '</li>';
        echo '<li>Environment: ' . esc_html($this->settings->getEnvironment()) . '</li>';
        echo '<li>Service: ' . esc_html($this->settings->getService()) . '</li>';
        echo '<li>Relay route: ' . esc_html($this->settings->getRelayRoute()) . '</li>';
        echo '<li>Relay spool: ' . esc_html((string) $diagnostics['spool_file_count']) . ' file(s), ' . esc_html((string) $diagnostics['spool_size_bytes']) . ' bytes</li>';
        if ($diagnostics['last_backend_flush'] !== '') {
            echo '<li>Last successful backend flush: ' . esc_html((string) $diagnostics['last_backend_flush']) . '</li>';
        }
        if ($diagnostics['last_relay_flush'] !== '') {
            echo '<li>Last successful relay flush: ' . esc_html((string) $diagnostics['last_relay_flush']) . '</li>';
        }
        if ($diagnostics['last_relay_ingestion_result'] !== '') {
            echo '<li>Last relay ingestion result: ' . esc_html((string) $diagnostics['last_relay_ingestion_result']) . '</li>';
        }
        if ($diagnostics['last_backend_error'] !== '') {
            echo '<li>Last backend SDK error: ' . esc_html((string) $diagnostics['last_backend_error']) . '</li>';
        }
        if ($diagnostics['last_relay_error'] !== '') {
            echo '<li>Last relay error: ' . esc_html((string) $diagnostics['last_relay_error']) . '</li>';
        }
        echo '</ul>';
        echo '<h2>Test delivery</h2>';
        echo '<p>Send one backend test event or one frontend relay test event using the current settings.</p>';
        echo '<p>';
        $this->renderTestButton('debugbundle_backend_test', 'Send backend test event');
        echo ' ';
        $this->renderTestButton('debugbundle_frontend_test', 'Send frontend relay test event');
        echo '</p>';
        echo '</div>';
    }

    public function handleBackendTest(): void
    {
        $this->handleTestResult('debugbundle_backend_test', $this->testEvents->sendBackend());
    }

    public function handleFrontendTest(): void
    {
        $this->handleTestResult('debugbundle_frontend_test', $this->testEvents->sendFrontend());
    }

    public function renderAdminNotice(): void
    {
        $page = $this->requestQueryText('page');
        $message = $this->requestQueryText('debugbundle_notice');
        $nonce = $this->requestQueryText('debugbundle_notice_nonce');

        if ($page !== 'debugbundle' || $message === null || !$this->verifyNoticeNonce($nonce)) {
            return;
        }

        $status = $this->requestQueryText('debugbundle_notice_status') ?? 'success';
        $class = $status === 'error' ? 'notice notice-error is-dismissible' : 'notice notice-success is-dismissible';
        echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($message) . '</p></div>';
    }

    private function renderTextRow(string $label, string $name, string $value, string|array $description = '', string $type = 'text', string $autocomplete = ''): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';
        echo '<input class="regular-text" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" type="' . esc_attr($type) . '" value="' . esc_attr($value) . '"';
        if ($autocomplete !== '') {
            echo ' autocomplete="' . esc_attr($autocomplete) . '"';
        }
        echo ' />';
        $descriptionLines = is_array($description) ? $description : [$description];
        foreach ($descriptionLines as $descriptionLine) {
            if (!is_string($descriptionLine) || $descriptionLine === '') {
                continue;
            }

            echo '<p class="description">' . esc_html($descriptionLine) . '</p>';
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

    private function renderTestButton(string $action, string $label): void
    {
        echo '<form method="post" action="' . esc_attr(admin_url('admin-post.php')) . '" style="display:inline-block;margin-right:8px;">';
        echo '<input type="hidden" name="action" value="' . esc_attr($action) . '" />';
        \wp_nonce_field($action);
        \submit_button($label, 'secondary', 'submit', false);
        echo '</form>';
    }

    private function handleTestResult(string $action, AdminTestResult $result): void
    {
        if (!function_exists('current_user_can') || !\current_user_can('manage_options')) {
            \wp_die('You do not have permission to run DebugBundle test events.');
        }

        \check_admin_referer($action);

        $location = \add_query_arg([
            'page' => 'debugbundle',
            'debugbundle_notice_status' => $result->success ? 'success' : 'error',
            'debugbundle_notice' => $result->message,
            'debugbundle_notice_nonce' => function_exists('wp_create_nonce') ? \wp_create_nonce(self::NOTICE_NONCE_ACTION) : self::NOTICE_NONCE_ACTION,
        ], \admin_url('options-general.php'));

        \wp_safe_redirect($location);
        exit;
    }

    private function requestQueryText(string $key): ?string
    {
        $value = filter_input(INPUT_GET, $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!is_string($value) || $value === '') {
            return null;
        }

        if (function_exists('wp_unslash')) {
            $value = (string) \wp_unslash($value);
        }

        return function_exists('sanitize_text_field') ? (string) \sanitize_text_field($value) : trim($value);
    }

    private function verifyNoticeNonce(?string $nonce): bool
    {
        if ($nonce === null || $nonce === '') {
            return false;
        }

        if (!function_exists('wp_verify_nonce')) {
            return true;
        }

        return \wp_verify_nonce($nonce, self::NOTICE_NONCE_ACTION) !== false;
    }
}
