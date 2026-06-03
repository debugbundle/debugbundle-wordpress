<?php

declare(strict_types=1);

namespace {

    if (!function_exists('wp_json_encode')) {
        function wp_json_encode(mixed $value): string|false
        {
            return json_encode($value);
        }
    }

    if (!function_exists('home_url')) {
        function home_url(string $path = '/'): string
        {
            return 'https://app.example.com' . $path;
        }
    }

    if (!function_exists('site_url')) {
        function site_url(string $path = '/'): string
        {
            return 'https://app.example.com' . $path;
        }
    }

    if (!function_exists('get_option')) {
        function get_option(string $optionName, array $default = []): array
        {
            return $GLOBALS['debugbundle_wp_test_options'][$optionName] ?? $default;
        }
    }

    if (!function_exists('wp_get_environment_type')) {
        function wp_get_environment_type(): string
        {
            return 'production';
        }
    }

    if (!function_exists('wp_upload_dir')) {
        function wp_upload_dir(): array
        {
            return [
                'basedir' => $GLOBALS['debugbundle_wp_test_upload_basedir'] ?? sys_get_temp_dir(),
            ];
        }
    }

    if (!function_exists('current_user_can')) {
        function current_user_can(string $capability): bool
        {
            return $capability === 'manage_options';
        }
    }

    if (!function_exists('settings_fields')) {
        function settings_fields(string $optionGroup): void
        {
            echo '<input type="hidden" name="option_page" value="' . htmlspecialchars($optionGroup, ENT_QUOTES, 'UTF-8') . '" />';
        }
    }

    if (!function_exists('submit_button')) {
        function submit_button(string $text, string $type = 'primary', string $name = 'submit', bool $wrap = true): void
        {
            echo '<button class="button button-' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</button>';
        }
    }

    if (!function_exists('checked')) {
        function checked(mixed $checked, mixed $current = true, bool $display = true): string
        {
            return $checked === $current ? 'checked="checked"' : '';
        }
    }

    if (!function_exists('admin_url')) {
        function admin_url(string $path = ''): string
        {
            return 'https://example.test/wp-admin/' . ltrim($path, '/');
        }
    }

    if (!function_exists('wp_nonce_field')) {
        function wp_nonce_field(string $action): void
        {
            echo '<input type="hidden" name="_wpnonce" value="nonce-for-' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '" />';
        }
    }

    if (!function_exists('esc_html')) {
        function esc_html(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }

    if (!function_exists('esc_attr')) {
        function esc_attr(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }

    if (!function_exists('get_transient')) {
        function get_transient(string $key): mixed
        {
            return $GLOBALS['debugbundle_wp_test_transients'][$key] ?? false;
        }
    }

    if (!function_exists('set_transient')) {
        function set_transient(string $key, mixed $value, int $expiration): bool
        {
            $GLOBALS['debugbundle_wp_test_transients'][$key] = $value;
            return true;
        }
    }

    if (!function_exists('wp_remote_post')) {
        function wp_remote_post(string $url, array $args = []): array
        {
            $GLOBALS['debugbundle_wp_test_last_remote_post'] = [
                'url' => $url,
                'args' => $args,
            ];

            return $GLOBALS['debugbundle_wp_test_remote_response'] ?? [
                'response' => ['code' => 202],
                'body' => json_encode(['accepted' => 1, 'rejected' => 0, 'errors' => []]),
            ];
        }
    }

    if (!function_exists('wp_remote_retrieve_response_code')) {
        function wp_remote_retrieve_response_code(array $response): int
        {
            return (int) ($response['response']['code'] ?? 500);
        }
    }

    if (!function_exists('wp_remote_retrieve_body')) {
        function wp_remote_retrieve_body(array $response): string
        {
            return is_string($response['body'] ?? null) ? $response['body'] : '';
        }
    }

    if (!function_exists('is_wp_error')) {
        function is_wp_error(mixed $thing): bool
        {
            return false;
        }
    }
}

namespace DebugBundleWp\Tests {

use DebugBundleWp\AdminPage;
use DebugBundleWp\AdminTestEvents;
use DebugBundleWp\BrowserRelayRoute;
use DebugBundleWp\Diagnostics;
use DebugBundleWp\RelayForwarder;
use DebugBundleWp\RelaySpool;
use DebugBundleWp\Settings;
use PHPUnit\Framework\TestCase;

final class AdminPageAndTestEventsTest extends TestCase
{
    private string $uploadBaseDir;

    protected function setUp(): void
    {
        $GLOBALS['debugbundle_wp_test_transients'] = [];
        $GLOBALS['debugbundle_wp_test_remote_response'] = [
            'response' => ['code' => 202],
            'body' => json_encode(['accepted' => 1, 'rejected' => 0, 'errors' => []]),
        ];
        $GLOBALS['debugbundle_wp_test_last_remote_post'] = null;
        $this->uploadBaseDir = sys_get_temp_dir() . '/debugbundle-wp-tests-' . bin2hex(random_bytes(6));
        $GLOBALS['debugbundle_wp_test_upload_basedir'] = $this->uploadBaseDir;
        $GLOBALS['debugbundle_wp_test_options'] = [
            Settings::OPTION_NAME => [
                'enabled' => true,
                'project_token' => 'dbundle_proj_test_1234567890',
                'environment' => 'production',
                'service' => 'demo-wordpress',
                'endpoint' => Settings::DEFAULT_ENDPOINT,
                'backend_capture_enabled' => true,
                'frontend_capture_enabled' => true,
                'browser_load_in_head' => false,
                'sample_rate' => 1.0,
                'browser_session_sample_rate' => 1.0,
                'browser_max_events_per_session' => 100,
                'browser_capture_console' => false,
                'log_level' => 'warning',
                'delete_on_uninstall' => false,
                'settings_version' => 1,
            ],
        ];
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->uploadBaseDir);
    }

    public function testRenderUsesPasswordFieldForStoredProjectTokenAndExpandedDescriptions(): void
    {
        $settings = new Settings();
        $spool = new RelaySpool();
        $page = new AdminPage(
            $settings,
            new AdminTestEvents($settings, new Diagnostics($spool), $spool, static fn (): object => new FakeRelayRoute(['status' => 202, 'body' => ['accepted' => 1, 'rejected' => 0, 'errors' => []]])),
            new Diagnostics($spool),
        );

        ob_start();
        $page->render();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('type="password"', $output);
        self::assertStringContainsString('Stored in a password field and hidden on screen by default.', $output);
        self::assertStringContainsString('Controls how much backend traffic is captured.', $output);
        self::assertStringContainsString('</p><p class="description">Use 1.0 to capture everything, 0.5 to keep about half of events, or 0.0 to stop sending backend events.', $output);
        self::assertStringContainsString('Controls how many visitor sessions enable frontend capture at all.', $output);
        self::assertStringContainsString('Load browser SDK in document head', $output);
        self::assertStringContainsString('Limits non-exception frontend events per browser session', $output);
        self::assertStringContainsString('Debug captures everything.', $output);
        self::assertStringContainsString('</p><p class="description">Choose the minimum backend log severity to capture.', $output);
    }

    public function testSendFrontendBuildsSchemaCompatibleBrowserExceptionPayload(): void
    {
        $settings = new Settings();
        $spool = new RelaySpool();
        $route = new FakeRelayRoute([
            'status' => 202,
            'body' => ['accepted' => 1, 'rejected' => 0, 'errors' => []],
        ]);
        $events = new AdminTestEvents($settings, new Diagnostics($spool), $spool, static fn () => $route);

        $result = $events->sendFrontend();

        self::assertTrue($result->success);
        self::assertStringContainsString('demo-wordpress-browser', $result->message);
        self::assertIsArray($route->capturedPayload);
        self::assertCount(1, $route->capturedPayload['batch']);

        $event = $route->capturedPayload['batch'][0];
        self::assertSame('frontend_exception', $event['event_type']);
        self::assertSame('browser', $event['service']['runtime']);
        self::assertSame('WordPress admin relay test', $event['payload']['browser']['name']);
        self::assertSame('/wp-admin/options-general.php?page=debugbundle', $event['payload']['route']);
        self::assertArrayNotHasKey('url', $event['payload']);
    }

    public function testSendFrontendReportsQueuedRetryWhenRelaySpoolsInsteadOfForwarding(): void
    {
        $settings = new Settings();
        $spool = new RelaySpool();
        $events = new AdminTestEvents(
            $settings,
            new Diagnostics($spool),
            $spool,
            static fn (): object => new FakeRelayRoute([
                'status' => 202,
                'body' => ['accepted' => 1, 'rejected' => 0, 'errors' => []],
            ], true),
        );

        $result = $events->sendFrontend();

        self::assertFalse($result->success);
        self::assertStringContainsString('relay delivery error', $result->message);
    }

    public function testRelayForwarderFailsWhenIngestionRejectsAcceptedHttpRequest(): void
    {
        $GLOBALS['debugbundle_wp_test_remote_response'] = [
            'response' => ['code' => 202],
            'body' => json_encode([
                'accepted' => 0,
                'rejected' => 1,
                'errors' => [
                    ['index' => 0, 'reason' => 'invalid_event'],
                ],
            ]),
        ];
        $settings = new Settings();
        $forwarder = new RelayForwarder($settings);

        $result = $forwarder->forward([$this->frontendEvent()]);

        self::assertFalse($result->success);
        self::assertTrue($result->drop);
        self::assertSame('ingestion_rejected accepted=0 expected=1 rejected=1 errors=event[0]: invalid_event', $result->error);
        self::assertSame($result->error, $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_relay_error'] ?? null);
        self::assertSame('accepted=0 rejected=1 errors=event[0]: invalid_event', $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_relay_ingestion_result'] ?? null);
    }

    public function testRelayForwarderSucceedsWhenIngestionAcceptsEveryEvent(): void
    {
        $settings = new Settings();
        $forwarder = new RelayForwarder($settings);

        $result = $forwarder->forward([$this->frontendEvent()]);

        self::assertTrue($result->success);
        self::assertFalse($result->drop);
        self::assertNull($result->error);
        self::assertArrayHasKey('debugbundle_last_relay_flush_at', $GLOBALS['debugbundle_wp_test_transients']);
        self::assertSame('accepted=1 rejected=0 errors=none', $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_relay_ingestion_result'] ?? null);
    }

    public function testBrowserRelayRouteCompletesCorrelationBeforeForwarding(): void
    {
        $settings = new Settings();
        $route = new BrowserRelayRoute($settings, 'debugbundle_test_cron');

        $response = $route->handleRequest(new FakeWordPressRelayRequest(json_encode([
            'batch' => [$this->frontendEvent()],
        ], JSON_THROW_ON_ERROR)));

        self::assertIsArray($response);
        self::assertSame(202, $response['status']);
        self::assertIsArray($GLOBALS['debugbundle_wp_test_last_remote_post']);
        $body = $GLOBALS['debugbundle_wp_test_last_remote_post']['args']['body'] ?? '';
        self::assertIsString($body);

        $forwarded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $forwarded['events'][0]['accepted'] ?? 1);
        $event = $forwarded['events'][0];
        self::assertSame('browser', $event['service']['runtime']);
        self::assertNull($event['service']['framework']);
        self::assertSame([
            'request_id' => null,
            'trace_id' => null,
            'session_id' => null,
            'user_id_hash' => null,
        ], $event['correlation']);
    }

    /** @return array<string, mixed> */
    private function frontendEvent(): array
    {
        return [
            'schema_version' => '2026-03-01',
            'event_id' => '00000000-0000-4000-8000-000000000001',
            'event_type' => 'frontend_exception',
            'occurred_at' => '2026-05-19T19:20:04Z',
            'sdk_name' => '@debugbundle/sdk-browser',
            'sdk_version' => '0.1.8',
            'service' => [
                'name' => 'demo-wordpress-browser',
                'runtime' => 'browser',
                'framework' => null,
                'environment' => 'production',
            ],
            'correlation' => [
                'request_id' => null,
                'trace_id' => null,
                'session_id' => null,
                'user_id_hash' => null,
            ],
            'payload' => [
                'name' => 'DebugBundleWordPressFrontendTestError',
                'message' => 'DebugBundle WordPress frontend test event',
                'stack' => "DebugBundleWordPressFrontendTestError: DebugBundle WordPress frontend test event\n    at wordpress-admin-test.js:1:1",
                'route' => '/wp-admin/options-general.php?page=debugbundle',
                'browser' => ['name' => 'WordPress admin relay test', 'version' => '0.1.8'],
                'breadcrumbs' => [],
                'device' => null,
            ],
        ];
    }

    private function removeDirectory(string $path): void
    {
        if ($path === '' || !is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $child = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($child)) {
                $this->removeDirectory($child);
                continue;
            }

            @unlink($child);
        }

        @rmdir($path);
    }
}

final class FakeRelayRoute
{
    /** @var array<string, mixed>|null */
    public ?array $capturedPayload = null;

    /** @param array{status:int,body:array<string, mixed>} $response */
    public function __construct(private readonly array $response, private readonly bool $recordRelayError = false)
    {
    }

    public function handleRequest(object $request): array
    {
        $decoded = json_decode($request->get_body(), true, 512, JSON_THROW_ON_ERROR);
        $this->capturedPayload = is_array($decoded) ? $decoded : null;
        if ($this->recordRelayError) {
            Diagnostics::recordRelayError('retryable_http_503');
        }

        return $this->response;
    }
}

final class FakeWordPressRelayRequest
{
    public function __construct(private readonly string $body)
    {
    }

    /** @return array<string, string> */
    public function get_headers(): array
    {
        return [
            'origin' => 'https://app.example.com',
            'content-type' => 'application/json',
        ];
    }

    public function get_body(): string
    {
        return $this->body;
    }

    public function get_method(): string
    {
        return 'POST';
    }
}

}
