<?php
declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\Diagnostics;
use DebugBundleWp\SdkBootstrap;
use DebugBundleWp\Settings;
use PHPUnit\Framework\TestCase;

final class DiagnosticsSafetyTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['debugbundle_wp_test_transient_callback'], $GLOBALS['debugbundle_wp_test_transient_read_callback'], $GLOBALS['debugbundle_wp_test_transients']);
    }

    public function testSanitizesBeforeTruncatingAndBoundsRetainedDetails(): void
    {
        Diagnostics::recordBackendError('token=synthetic-private-value');
        self::assertStringNotContainsString('synthetic-private-value', $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_backend_error']);
        Diagnostics::recordRelayError(str_repeat('é', 4000));
        self::assertSame('diagnostic_details_withheld', $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_relay_error']);
        Diagnostics::recordBackendError(str_repeat('é', 300));
        $value = $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_backend_error'];
        self::assertLessThanOrEqual(500, strlen($value));
        self::assertSame(1, preg_match('//u', $value));
    }

    public function testWordPressNonceFieldsAreProtectedOnWriteAndLegacyRead(): void
    {
        $method = new \ReflectionMethod(Diagnostics::class, 'transientString');
        foreach (['wpnonce', '_wpnonce', 'woocommerce-login-nonce', 'woocommerce-register-nonce', 'woocommerce-reset-password-nonce'] as $field) {
            $message = $field . '=synthetic-private-nonce';
            Diagnostics::recordBackendError($message);
            self::assertStringNotContainsString('synthetic-private-nonce', $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_backend_error']);
            $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_backend_error'] = $message;
            self::assertStringNotContainsString('synthetic-private-nonce', $method->invoke(new Diagnostics(), 'debugbundle_last_backend_error'));
            self::assertSame($message, $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_backend_error']);
        }
    }

    public function testContainsRecursiveAndFailedStorageAndRestoresLaterDiagnostics(): void
    {
        $calls = 0;
        $GLOBALS['debugbundle_wp_test_transient_callback'] = static function () use (&$calls): void {
            $calls++;
            Diagnostics::recordRelayError('nested diagnostic');
            throw new \RuntimeException('storage failed');
        };
        $bootstrap = new SdkBootstrap(new Settings());
        (new \ReflectionMethod($bootstrap, 'recordBackendDiagnostic'))->invoke($bootstrap, new \RuntimeException('original failure'));
        self::assertSame(1, $calls);
        unset($GLOBALS['debugbundle_wp_test_transient_callback']);
        Diagnostics::recordBackendError('ordinary status');
        self::assertSame('ordinary status', $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_backend_error']);
        Diagnostics::recordRelayIngestionResult(1, 1, 'event[1]: invalid_event');
        self::assertSame('accepted=1 rejected=1 errors=event[1]: invalid_event', $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_relay_ingestion_result']);
    }

    public function testExistingDiagnosticsAreProtectedOnReadAndFailedReadsStayLocal(): void
    {
        $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_backend_error'] = 'token=legacy-private-value';
        $method = new \ReflectionMethod(Diagnostics::class, 'transientString');
        $diagnostics = new Diagnostics();
        $value = $method->invoke($diagnostics, 'debugbundle_last_backend_error');
        self::assertStringNotContainsString('legacy-private-value', $value);
        // Reading must not rewrite an installation's pre-existing state.
        self::assertSame('token=legacy-private-value', $GLOBALS['debugbundle_wp_test_transients']['debugbundle_last_backend_error']);
        $GLOBALS['debugbundle_wp_test_transient_read_callback'] = static function (): void { throw new \RuntimeException('storage failed'); };
        self::assertSame('', $method->invoke($diagnostics, 'debugbundle_last_backend_error'));
    }
}
