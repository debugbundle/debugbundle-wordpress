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
		unset($GLOBALS['debugbundle_wp_test_last_remote_request']);
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['debugbundle_wp_test_remote_request_response']);
		unset($GLOBALS['debugbundle_wp_test_last_remote_request']);
	}

	public function testRequestUrlMethodAndHeadersAreSanitizedBeforeRemoteFetch(): void
	{
		$GLOBALS['debugbundle_wp_test_remote_request_response'] = [
			'response' => ['code' => 200],
			'body' => '{}',
		];

		$fetcher = new ConfigFetcher();
		$fetcher(" https://api.debugbundle.com/v1/sdk/config\n", [
			'method' => " get<script>ignored</script>",
			'headers' => [
				'X-DebugBundle-Service' => ' wordpress <b>site</b> ',
				"Bad\nHeader" => 'ignored',
			],
		]);

		self::assertSame('https://api.debugbundle.com/v1/sdk/config', $GLOBALS['debugbundle_wp_test_last_remote_request']['url']);
		self::assertSame('GET', $GLOBALS['debugbundle_wp_test_last_remote_request']['args']['method']);
		self::assertSame(
			['X-DebugBundle-Service' => 'wordpress site'],
			$GLOBALS['debugbundle_wp_test_last_remote_request']['args']['headers']
		);
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
