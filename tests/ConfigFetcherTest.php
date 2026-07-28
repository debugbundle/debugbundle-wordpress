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

	public function testResponseHeadersAndJsonPayloadAreNormalized(): void
	{
		$GLOBALS['debugbundle_wp_test_remote_request_response'] = [
			'response' => ['code' => 200],
			'body' => '{"probes_enabled":true}',
			'headers' => [
				'ETag' => ' "cfg-v2" ',
				"Bad\nHeader" => 'ignored',
			],
		];

		$response = (new ConfigFetcher())('https://api.example.test/v1/sdk/config', [
			'method' => 'PUT',
			'headers' => ['X-Test' => ['ignored'], 1 => 'ignored'],
		]);

		self::assertSame(200, $response->statusCode);
		self::assertSame(['etag' => '"cfg-v2"'], $response->headers);
		self::assertSame(['probes_enabled' => true], $response->json());
		self::assertSame('GET', $GLOBALS['debugbundle_wp_test_last_remote_request']['args']['method']);
	}

	public function testWordPressHeaderObjectsAreNormalized(): void
	{
		$GLOBALS['debugbundle_wp_test_remote_request_response'] = [
			'response' => ['code' => 200],
			'body' => '{}',
			'headers' => new class {
				/** @return array<string, mixed> */
				public function getAll(): array
				{
					return [
						'ETag' => ' "object-v1" ',
						'X-Count' => 2,
						"Bad\nHeader" => 'ignored',
						'X-Array' => ['ignored'],
					];
				}
			},
		];

		$response = (new ConfigFetcher())('https://api.example.test/v1/sdk/config', []);

		self::assertSame([
			'etag' => '"object-v1"',
			'x-count' => '2',
		], $response->headers);
	}

	public function testInvalidUrlFailsClosedAndInvalidJsonReturnsNullPayload(): void
	{
		$fetcher = new ConfigFetcher();

		try {
			$fetcher('javascript:alert(1)', []);
			self::fail('Expected invalid configuration URL to fail.');
		} catch (RuntimeException $exception) {
			self::assertSame('config_request_failed', $exception->getMessage());
		}

		$GLOBALS['debugbundle_wp_test_remote_request_response'] = [
			'response' => ['code' => 304],
			'body' => 'not-json',
		];
		$response = $fetcher('https://api.example.test/v1/sdk/config', []);
		self::assertSame(304, $response->statusCode);
		self::assertNull($response->json());
	}
}
