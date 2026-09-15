<?php

declare(strict_types=1);

namespace {
    if (!function_exists('get_option')) {
        function get_option(string $option, mixed $default = false): mixed
        {
            return $GLOBALS['debugbundle_wp_test_options'][$option] ?? $default;
        }
    }

    if (!function_exists('add_action')) {
        function add_action(string $hook, callable $callback, int $priority = 10): void
        {
            $GLOBALS['debugbundle_wp_test_actions'][$hook][$priority][] = $callback;
        }
    }

    if (!function_exists('apply_filters')) {
        function apply_filters(string $hook, mixed $value): mixed
        {
            $filter = $GLOBALS['debugbundle_wp_test_filters'][$hook] ?? null;
            return is_callable($filter) ? $filter($value) : $value;
        }
    }
}

namespace DebugBundleWp\Tests {
    use DebugBundle\DebugBundleSdk;
    use DebugBundleWp\SdkBootstrap;
    use DebugBundleWp\Settings;
    use PHPUnit\Framework\Attributes\WithoutErrorHandler;
    use PHPUnit\Framework\TestCase;

    final class SdkBootstrapBeforeSendTest extends TestCase
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
                    'log_level' => 'warning',
                ],
            ];
            unset($GLOBALS['debugbundle_wp_test_actions'], $GLOBALS['debugbundle_wp_test_filters']);
        }

        protected function tearDown(): void
        {
            unset(
                $GLOBALS['debugbundle_wp_test_options'],
                $GLOBALS['debugbundle_wp_test_actions'],
                $GLOBALS['debugbundle_wp_test_filters']
            );
        }

        public function testBackendFilterIsWiredToTheVendoredSdkBeforeSendHook(): void
        {
            $GLOBALS['debugbundle_wp_test_filters']['debugbundle_before_send'] =
                static function (array $event): array {
                    $event['payload']['message'] = 'filtered';
                    return $event;
                };

            $bootstrap = new SdkBootstrap(new Settings());
            $bootstrap->register();

            $sdkProperty = new \ReflectionProperty($bootstrap, 'sdk');
            $sdk = $sdkProperty->getValue($bootstrap);
            self::assertInstanceOf(DebugBundleSdk::class, $sdk);

            try {
                $hookProperty = new \ReflectionProperty($sdk, 'beforeSend');
                $hook = $hookProperty->getValue($sdk);
                self::assertInstanceOf(\Closure::class, $hook);

                $result = $hook([
                    'schema_version' => '2026-03-01',
                    'event_id' => '11111111-1111-4111-8111-111111111111',
                    'event_type' => 'log_event',
                    'occurred_at' => '2026-07-27T08:00:00Z',
                    'sdk_name' => '@debugbundle/sdk-php',
                    'sdk_version' => '1.2.0',
                    'service' => ['name' => 'wordpress-test', 'environment' => 'test'],
                    'payload' => ['level' => 'error', 'message' => 'original', 'attributes' => []],
                ]);

                self::assertIsArray($result);
                self::assertSame('filtered', $result['payload']['message']);
            } finally {
                $sdk->reset();
            }
        }

        #[WithoutErrorHandler]
        public function testBundledSdkRespectsSuppressionAndKeepsOrdinaryAndExplicitCapture(): void
        {
            $captured = [];
            $GLOBALS['debugbundle_wp_test_filters']['debugbundle_before_send'] =
                static function (array $event) use (&$captured): ?array {
                    $captured[] = $event['payload']['message'];
                    return null;
                };
            $bootstrap = new SdkBootstrap(new Settings());
            $bootstrap->register();
            $sdk = (new \ReflectionProperty($bootstrap, 'sdk'))->getValue($bootstrap);
            self::assertInstanceOf(DebugBundleSdk::class, $sdk);
            $previous = error_reporting(E_ALL);
            try {
                @trigger_error('suppressed warning', E_USER_WARNING);
                error_reporting(E_ALL & ~E_USER_NOTICE);
                trigger_error('masked notice', E_USER_NOTICE);
                self::assertCount(0, $captured);
                error_reporting(E_ALL);
                $handler = set_error_handler(static fn () => false);
                restore_error_handler();
                self::assertIsCallable($handler);
                self::assertFalse($handler(E_USER_WARNING, 'ordinary warning', __FILE__, __LINE__));
                error_reporting(0);
                $sdk->captureException(new \RuntimeException('explicit exception'));
                self::assertSame(['ordinary warning', 'explicit exception'], $captured);
            } finally {
                error_reporting($previous);
                error_clear_last();
                $sdk->reset();
            }
        }
    }
}
