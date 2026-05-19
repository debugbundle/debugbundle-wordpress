<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\ConfigFetcher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConfigFetcherTest extends TestCase
{
	protected function setUp(): void
	{
		unset($GLOBALS['debugbundle_wp_test_remote_request_response']);
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['debugbundle_wp_test_remote_request_response']);
	}

	public function testWpErrorThrowsStablePluginMessage(): void
	{
		$GLOBALS['debugbundle_wp_test_remote_request_response'] = new \WP_Error('Unsafe <strong>message</strong>');

		$fetcher = new ConfigFetcher();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('config_request_failed');

		$fetcher('https://example.com/v1/sdk/config', [
			'method' => 'GET',
			'headers' => [],
		]);
	}
}