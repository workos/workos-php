<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace Tests\Service;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkOS\Resource\AuditLogEvent;
use WorkOS\Resource\AuditLogEventActor;
use WorkOS\Resource\AuditLogEventContext;
use WorkOS\TestHelper;

class AuditLogOccurredAtTest extends TestCase
{
    use TestHelper;

    public function testFactoryOmitsAbsentTimestampOnWire(): void
    {
        $event = AuditLogEvent::fromArray([
            'action' => 'user.signed_in',
            'actor' => ['id' => 'user_1', 'type' => 'user'],
            'targets' => [],
            'context' => ['location' => '127.0.0.1'],
        ]);
        $client = $this->createMockClient([['status' => 200, 'body' => ['success' => true]]]);

        $client->auditLogs()->createEvent('org_123', $event);

        $body = json_decode((string) $this->getLastRequest()->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('occurred_at', $body['event']);
    }

    #[DataProvider('explicitTimestamps')]
    public function testLegacyPositionalConstructorPreservesExplicitTimestamp(string $input, string $expected): void
    {
        $timestamp = new DateTimeImmutable($input);
        $event = new AuditLogEvent(
            'user.signed_in',
            $timestamp,
            new AuditLogEventActor('user_1', 'user'),
            [],
            new AuditLogEventContext('127.0.0.1'),
        );
        $client = $this->createMockClient([['status' => 200, 'body' => ['success' => true]]]);

        $client->auditLogs()->createEvent('org_123', $event);

        $body = json_decode((string) $this->getLastRequest()->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($expected, $body['event']['occurred_at']);
    }

    /** @return array<string, array{string, string}> */
    public static function explicitTimestamps(): array
    {
        return [
            'UTC' => ['2026-11-03T12:34:56.789Z', '2026-11-03T12:34:56.789+00:00'],
            'offset' => ['2026-11-03T14:34:56.789+02:00', '2026-11-03T14:34:56.789+02:00'],
        ];
    }
}
