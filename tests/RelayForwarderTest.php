<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\RelayForwarder;
use DebugBundleWp\CachedConfigFetcher;
use DebugBundleWp\Settings;
use PHPUnit\Framework\TestCase;

final class RelayForwarderTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['debugbundle_wp_test_remote_post_callback'], $GLOBALS['debugbundle_wp_test_options'], $GLOBALS['debugbundle_wp_test_transients']);
    }

    /** @return array<string,mixed> */
    private function event(): array
    {
        $fixtures = json_decode((string) file_get_contents(__DIR__ . '/fixtures/relay-compliance.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($fixtures['cases'] as $fixture) {
            if ($fixture['id'] === 'valid-browser-batch') return $fixture['request']['bodyJson']['batch'][0];
        }
        self::fail('Missing canonical browser fixture');
    }

    public function testPartitionsActualProtectedJsonAndKeepsOneWholeBatchDeadline(): void
    {
        $events = [];
        for ($index = 0; $index < 3; $index++) {
            $event = $this->event(); $event['event_id'] = sprintf('00000000-0000-4000-8000-%012d', $index);
            $event['context'] = array_fill_keys(array_map(static fn (int $i): string => 'field' . $i, range(1, 12)), str_repeat('x', 8000));
            $events[] = $event;
        }
        $requests = [];
        $GLOBALS['debugbundle_wp_test_remote_post_callback'] = static function (string $url, array $args) use (&$requests): array {
            $requests[] = $args;
            $count = count(json_decode($args['body'], true, 512, JSON_THROW_ON_ERROR)['events']);
            return ['response' => ['code' => 202], 'body' => json_encode(['accepted' => $count, 'rejected' => 0, 'errors' => []])];
        };
        $result = (new RelayForwarder(new Settings()))->forward($events);
        self::assertTrue($result->success);
        self::assertCount(2, $requests);
        foreach ($requests as $request) {
            self::assertLessThanOrEqual(262144, strlen($request['body']));
            self::assertLessThanOrEqual(5, $request['timeout']);
            self::assertSame(0, $request['redirection']);
            self::assertSame(262145, $request['limit_response_size']);
        }
        self::assertLessThanOrEqual($requests[0]['timeout'], $requests[1]['timeout']);
    }

    public function testOversizedAcknowledgementRetainsTheUnconfirmedBatch(): void
    {
        $GLOBALS['debugbundle_wp_test_remote_post_callback'] = static fn (): array => [
            'response' => ['code' => 202], 'body' => json_encode(['accepted' => 1, 'rejected' => 0, 'errors' => [], 'padding' => str_repeat('x', 262144)]),
        ];
        $result = (new RelayForwarder(new Settings()))->forward([$this->event()]);
        self::assertFalse($result->success); self::assertFalse($result->drop);
        self::assertSame('acknowledgement_protocol_failure', $result->error);
    }

    public function testForwardingLearnsAndRevokesProofWithoutChangingTheLegacyPrimary(): void
    {
        $GLOBALS['debugbundle_wp_test_transients'] = [];
        $GLOBALS['debugbundle_wp_test_options'][Settings::OPTION_NAME] = ['project_token' => 'fictional'];
        $event = $this->event();
        $event['payload']['exception_evidence'] = [
            'version' => 1, 'runtime' => 'browser', 'root_id' => 0, 'truncated' => false, 'unavailable_reason' => null,
            'nodes' => [['id' => 0, 'name' => 'Error', 'message' => 'fictional', 'stack' => null,
                'trace_origin' => 'unavailable', 'parsing_status' => 'unavailable', 'frames' => [], 'relationships' => []]],
        ];
        $requests = [];
        $proof = ['version' => 1, 'features' => ['exception_evidence_v1']];
        $GLOBALS['debugbundle_wp_test_remote_post_callback'] = static function (string $url, array $args) use (&$requests, &$proof): array {
            $requests[] = $args;
            return ['response' => ['code' => 202], 'body' => json_encode(['accepted' => 1, 'rejected' => 0, 'errors' => [], 'evidence_capabilities' => $proof])];
        };
        $forwarder = new RelayForwarder(new Settings());
        self::assertTrue($forwarder->forward([$event])->success);
        $first = json_decode($requests[0]['body'], true)['events'][0];
        self::assertArrayNotHasKey('exception_evidence', $first['payload']);
        self::assertSame($event['payload']['message'], $first['payload']['message']);
        self::assertSame('1', $requests[0]['headers']['X-DebugBundle-Evidence']);
        self::assertTrue($forwarder->forward([$event])->success);
        self::assertArrayHasKey('exception_evidence', json_decode($requests[1]['body'], true)['events'][0]['payload']);
        $proof = ['version' => 2, 'features' => ['exception_evidence_v1']];
        self::assertTrue($forwarder->forward([$event])->success);
        self::assertTrue($forwarder->forward([$event])->success);
        self::assertArrayNotHasKey('exception_evidence', json_decode($requests[3]['body'], true)['events'][0]['payload']);
        $settings = new Settings();
        self::assertSame([], (new CachedConfigFetcher())->cachedPayload($settings->getEndpoint(), $settings->getProjectToken())['evidence_capabilities']['features']);
    }

    public function testEncodedAcknowledgementCannotConfirmDeliveryOrProveCapability(): void
    {
        $GLOBALS['debugbundle_wp_test_remote_post_callback'] = static fn (): array => [
            'response' => ['code' => 202], 'headers' => ['Content-Encoding' => 'gzip'],
            'body' => '{"accepted":1,"rejected":0,"errors":[],"evidence_capabilities":{"version":1,"features":["recovery_v1"]}}',
        ];
        $result = (new RelayForwarder(new Settings()))->forward([$this->event()]);
        self::assertFalse($result->success); self::assertFalse($result->drop);
        self::assertSame('acknowledgement_protocol_failure', $result->error);
    }

    public function testObjectShapedErrorsCannotConfirmDelivery(): void
    {
        $GLOBALS['debugbundle_wp_test_remote_post_callback'] = static fn (): array => [
            'response' => ['code' => 202], 'body' => '{"accepted":1,"rejected":0,"errors":{}}',
        ];
        $result = (new RelayForwarder(new Settings()))->forward([$this->event()]);
        self::assertFalse($result->success);
        self::assertFalse($result->drop);
        self::assertSame('acknowledgement_protocol_failure', $result->error);
    }

    public function testProofRevocationBetweenChunksKeepsOnlyThePreparedRetry(): void
    {
        $settings = new Settings();
        $GLOBALS['debugbundle_wp_test_options'][Settings::OPTION_NAME] = ['project_token' => 'fictional'];
        (new CachedConfigFetcher())->observeEvidence($settings->getEndpoint(), $settings->getProjectToken(),
            ['version' => 1, 'features' => ['exception_evidence_v1']]);
        $event = $this->event();
        $event['payload']['exception_evidence'] = [
            'version' => 1, 'runtime' => 'browser', 'root_id' => 0, 'truncated' => false, 'unavailable_reason' => null,
            'nodes' => [['id' => 0, 'name' => 'Error', 'message' => 'fictional', 'stack' => null,
                'trace_origin' => 'unavailable', 'parsing_status' => 'unavailable', 'frames' => [], 'relationships' => []]],
        ];
        $event['context'] = array_fill_keys(array_map(static fn (int $i): string => 'field' . $i, range(1, 12)), str_repeat('x', 8000));
        $events = [$event, $event, $event]; $requests = [];
        $GLOBALS['debugbundle_wp_test_remote_post_callback'] = static function (string $url, array $args) use (&$requests): array {
            $requests[] = json_decode($args['body'], true)['events'];
            return count($requests) === 1
                ? ['response' => ['code' => 202], 'body' => json_encode(['accepted' => count($requests[0]), 'rejected' => 0, 'errors' => []])]
                : ['response' => ['code' => 503], 'body' => 'unavailable'];
        };
        $result = (new RelayForwarder($settings))->forward($events);
        self::assertCount(2, $requests);
        self::assertArrayHasKey('exception_evidence', $requests[0][0]['payload']);
        self::assertArrayNotHasKey('exception_evidence', $requests[1][0]['payload']);
        self::assertSame($requests[1], $result->retryEvents);
    }
}
