<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundle\DebugBundleSdk;
use DebugBundle\Transport\TransportInterface;
use DebugBundle\Transport\TransportResponse;
use DebugBundleWp\SdkBootstrap;
use DebugBundleWp\Settings;
use PHPUnit\Framework\TestCase;

final class SdkBootstrapRuntimeTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['debugbundle_wp_test_options'] = [
            Settings::OPTION_NAME => [
                'enabled' => true,
                'backend_capture_enabled' => true,
                'project_token' => 'dbundle_proj_test',
                'environment' => 'test',
                'service' => 'wordpress-test',
                'endpoint' => 'https://api.example.test/v1/events',
                'sample_rate' => 1.0,
                'log_level' => 'warning',
            ],
        ];
        $GLOBALS['debugbundle_wp_test_actions'] = [];
        $GLOBALS['debugbundle_wp_test_transients'] = [];
        $GLOBALS['debugbundle_wp_test_scheduled_hooks'] = [];
        $GLOBALS['debugbundle_wp_test_current_user_id'] = 42;
        $GLOBALS['debugbundle_wp_test_multisite'] = true;
        unset($GLOBALS['debugbundle_wp_test_last_remote_request']);
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        http_response_code(200);
        unset(
            $GLOBALS['debugbundle_wp_test_options'],
            $GLOBALS['debugbundle_wp_test_actions'],
            $GLOBALS['debugbundle_wp_test_transients'],
            $GLOBALS['debugbundle_wp_test_scheduled_hooks'],
            $GLOBALS['debugbundle_wp_test_current_user_id'],
            $GLOBALS['debugbundle_wp_test_multisite'],
            $GLOBALS['debugbundle_wp_test_remote_request_response'],
            $GLOBALS['debugbundle_wp_test_last_remote_request']
        );
    }

    public function testRegisterSkipsDisabledBackendCapture(): void
    {
        $GLOBALS['debugbundle_wp_test_options'][Settings::OPTION_NAME]['backend_capture_enabled'] = false;
        $bootstrap = new SdkBootstrap(new Settings());

        $bootstrap->register();

        self::assertNull((new \ReflectionProperty($bootstrap, 'sdk'))->getValue($bootstrap));
        self::assertSame([], $GLOBALS['debugbundle_wp_test_actions']);
        $bootstrap->onInit();
        $bootstrap->onShutdown();
        (new \ReflectionMethod($bootstrap, 'attachContext'))->invoke($bootstrap);
    }

    public function testOrdinaryRegistrationDoesNotFetchRemoteConfig(): void
    {
        $bootstrap = new SdkBootstrap(new Settings());
        $bootstrap->register();
        $sdk = (new \ReflectionProperty($bootstrap, 'sdk'))->getValue($bootstrap);
        self::assertInstanceOf(DebugBundleSdk::class, $sdk);
        self::assertArrayNotHasKey('debugbundle_wp_test_last_remote_request', $GLOBALS);
        $sdk->reset();
    }

    public function testScheduledRefreshPopulatesLocalPolicyCache(): void
    {
        $GLOBALS['debugbundle_wp_test_remote_request_response'] = [
            'response' => ['code' => 200],
            'body' => '{"probes_enabled":true,"remote_probes_enabled":false,"active_probes":[]}',
        ];
        $bootstrap = new SdkBootstrap(new Settings());
        $bootstrap->register();
        $sdk = (new \ReflectionProperty($bootstrap, 'sdk'))->getValue($bootstrap);
        self::assertInstanceOf(DebugBundleSdk::class, $sdk);

        self::assertArrayHasKey(SdkBootstrap::CONFIG_REFRESH_HOOK, $GLOBALS['debugbundle_wp_test_scheduled_hooks']);
        self::assertArrayNotHasKey('debugbundle_wp_test_last_remote_request', $GLOBALS);
        $bootstrap->refreshConfig();
        self::assertSame('https://api.example.test/v1/sdk/config',
            $GLOBALS['debugbundle_wp_test_last_remote_request']['url']);
        self::assertSame('Bearer dbundle_proj_test',
            $GLOBALS['debugbundle_wp_test_last_remote_request']['args']['headers']['authorization']);
        self::assertIsArray($GLOBALS['debugbundle_wp_test_transients']['debugbundle_sdk_capture_config'] ?? null);
        $sdk->reset();
    }

    public function testRequestLifecycleAddsWordPressContextAndFlushesFailureRequest(): void
    {
        $transport = new RecordingTransport();
        $sdk = new DebugBundleSdk($transport);
        $sdk->init([
            'projectToken' => 'dbundle_proj_test',
            'service' => 'wordpress-test',
            'environment' => 'test',
            'sampleRate' => 1.0,
        ]);
        $bootstrap = new SdkBootstrap(new Settings());
        (new \ReflectionProperty($bootstrap, 'sdk'))->setValue($bootstrap, $sdk);

        $bootstrap->onInit();
        http_response_code(500);
        $bootstrap->onShutdown();
        self::assertSame([], $transport->requests);
        (new \ReflectionMethod(DebugBundleSdk::class, 'handleShutdown'))->invoke($sdk, null);
        $bootstrap->recordBackendFlush();
        $sdk->reset();

        self::assertCount(1, $transport->requests);
        $events = $transport->requests[0]['events'];
        self::assertSame('request_event', $events[0]['event_type']);
        self::assertSame(500, $events[0]['payload']['response_status']);
        self::assertSame('DebugBundle Test Theme', $events[0]['context']['wordpress']['theme']['name']);
        self::assertTrue($events[0]['context']['wordpress']['multisite']);
        self::assertSame(
            hash_hmac('sha256', '42', 'debugbundle-test-auth'),
            $events[0]['correlation']['user_id_hash']
        );
        self::assertArrayHasKey('debugbundle_last_backend_flush_at', $GLOBALS['debugbundle_wp_test_transients']);
    }

    public function testSdkFailuresAreRecordedWithoutEscapingWordPressLifecycle(): void
    {
        $bootstrap = new SdkBootstrap(new Settings());
        $brokenSdk = (new \ReflectionClass(DebugBundleSdk::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty($bootstrap, 'sdk'))->setValue($bootstrap, $brokenSdk);

        $bootstrap->onInit();
        $bootstrap->onShutdown();

        self::assertNotEmpty($GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_backend_error'] ?? '');
    }

    public function testFatalAfterWordPressShutdownJoinsSinglePhpShutdownBatch(): void
    {
        $transport = new RecordingTransport();
        $sdk = new DebugBundleSdk($transport);
        $sdk->init(['projectToken' => 'dbundle_proj_test']);
        $bootstrap = new SdkBootstrap(new Settings());
        (new \ReflectionProperty($bootstrap, 'sdk'))->setValue($bootstrap, $sdk);

        $bootstrap->onInit();
        http_response_code(500);
        $bootstrap->onShutdown();
        self::assertSame([], $transport->requests);
        (new \ReflectionMethod(DebugBundleSdk::class, 'handleShutdown'))->invoke($sdk, [
            'type' => E_ERROR,
            'message' => 'fatal after WordPress shutdown',
            'file' => __FILE__,
            'line' => __LINE__,
        ]);
        $sdk->reset();

        self::assertCount(1, $transport->requests);
        self::assertSame(['backend_exception', 'request_event'],
            array_column($transport->requests[0]['events'], 'event_type'));
    }

    public function testPrivateRequestSanitizersHandleNestedAndUnsupportedValues(): void
    {
        $bootstrap = new SdkBootstrap(new Settings());
        $sanitizeKey = new \ReflectionMethod($bootstrap, 'sanitizeQueryKey');
        $sanitizeValue = new \ReflectionMethod($bootstrap, 'sanitizeQueryValue');
        $requestQuery = new \ReflectionMethod($bootstrap, 'requestQuery');
        $themeContext = new \ReflectionMethod($bootstrap, 'buildThemeContext');
        $userHash = new \ReflectionMethod($bootstrap, 'currentUserHash');

        self::assertSame('mixedkeyscript', $sanitizeKey->invoke($bootstrap, 'Mixed Key<script>'));
        self::assertSame([
            'name' => 'Alice',
            '0' => 'first',
            'nested' => ['value' => 'ok'],
        ], $sanitizeValue->invoke($bootstrap, [
            'name' => '<b>Alice</b>',
            0 => 'first',
            'nested' => ['value' => 'ok'],
            '' => 'ignored',
        ]));
        self::assertSame('', $sanitizeValue->invoke($bootstrap, new \stdClass()));
        self::assertSame([], $requestQuery->invoke($bootstrap));
        self::assertSame('debugbundle-test-theme', $themeContext->invoke($bootstrap)['stylesheet']);
        self::assertSame(hash_hmac('sha256', '42', 'debugbundle-test-auth'), $userHash->invoke($bootstrap));

        $GLOBALS['debugbundle_wp_test_current_user_id'] = 0;
        self::assertNull($userHash->invoke($bootstrap));
    }
}

final class RecordingTransport implements TransportInterface
{
    /** @var list<array<string, mixed>> */
    public array $requests = [];

    public function send(array $request): TransportResponse
    {
        $this->requests[] = $request;
        $eventCount = count($request['events'] ?? []);
        return new TransportResponse(202, null, [
            'accepted' => $eventCount,
            'rejected' => 0,
            'errors' => [],
        ]);
    }
}
