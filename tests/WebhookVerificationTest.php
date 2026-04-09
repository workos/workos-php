<?php

declare(strict_types=1);
// @oagen-ignore-file
// Hand-maintained tests for the WebhookVerification module (H01, H02).

namespace Tests;

use PHPUnit\Framework\TestCase;
use WorkOS\TestHelper;
use WorkOS\WebhookVerification;

class WebhookVerificationTest extends TestCase
{
    use TestHelper;

    private const SECRET = 'whsec_test_secret_key';

    public function testVerifyEventSuccess(): void
    {
        $payload = json_encode(['event' => 'user.created', 'data' => ['id' => '123']]);
        $timestamp = (string) (time() * 1000); // milliseconds
        $expectedSig = hash_hmac('sha256', "{$timestamp}.{$payload}", self::SECRET);
        $sigHeader = "t={$timestamp}, v1={$expectedSig}";

        $client = $this->createMockClient([]);
        $result = $client->webhookVerification()->verifyEvent(
            eventBody: $payload,
            eventSignature: $sigHeader,
            secret: self::SECRET,
        );
        $this->assertSame('user.created', $result['event']);
        $this->assertSame('123', $result['data']['id']);
    }

    public function testVerifyEventInvalidSignature(): void
    {
        $payload = '{"event":"test"}';
        $timestamp = (string) (time() * 1000);
        $sigHeader = "t={$timestamp}, v1=invalidsignature";

        $client = $this->createMockClient([]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Signature hash does not match');
        $client->webhookVerification()->verifyEvent(
            eventBody: $payload,
            eventSignature: $sigHeader,
            secret: self::SECRET,
        );
    }

    public function testVerifyEventExpiredTimestamp(): void
    {
        $payload = '{"event":"test"}';
        $timestamp = (string) ((time() - 300) * 1000); // 5 minutes ago
        $expectedSig = hash_hmac('sha256', "{$timestamp}.{$payload}", self::SECRET);
        $sigHeader = "t={$timestamp}, v1={$expectedSig}";

        $client = $this->createMockClient([]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Timestamp outside the tolerance zone');
        $client->webhookVerification()->verifyEvent(
            eventBody: $payload,
            eventSignature: $sigHeader,
            secret: self::SECRET,
        );
    }

    public function testGetTimestampAndSignatureHash(): void
    {
        $sigHeader = 't=1234567890, v1=abcdef1234567890';
        [$timestamp, $hash] = WebhookVerification::getTimestampAndSignatureHash($sigHeader);
        $this->assertSame('1234567890', $timestamp);
        $this->assertSame('abcdef1234567890', $hash);
    }

    public function testComputeSignature(): void
    {
        $expected = hash_hmac('sha256', '12345.payload', 'secret');
        $result = WebhookVerification::computeSignature('12345', 'payload', 'secret');
        $this->assertSame($expected, $result);
    }

    public function testWebhookVerificationAccessibleFromClient(): void
    {
        $client = $this->createMockClient([]);
        $this->assertInstanceOf(WebhookVerification::class, $client->webhookVerification());
    }
}
