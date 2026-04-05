<?php

declare(strict_types=1);
// @oagen-ignore-file
// Hand-maintained tests for the Actions module (H03).

namespace Tests;

use PHPUnit\Framework\TestCase;
use WorkOS\Actions;
use WorkOS\TestHelper;

class ActionsTest extends TestCase
{
    use TestHelper;

    private const SECRET = 'actions_test_secret';

    public function testVerifyHeaderSuccess(): void
    {
        $payload = '{"action":"authentication","data":{}}';
        $timestamp = (string) (time() * 1000);
        $expectedSig = hash_hmac('sha256', "{$timestamp}.{$payload}", self::SECRET);
        $sigHeader = "t={$timestamp}, v1={$expectedSig}";

        $client = $this->createMockClient([]);
        // Should not throw
        $client->actions()->verifyHeader($payload, $sigHeader, self::SECRET);
        $this->assertTrue(true);
    }

    public function testVerifyHeaderInvalidSignature(): void
    {
        $payload = '{"action":"authentication"}';
        $timestamp = (string) (time() * 1000);
        $sigHeader = "t={$timestamp}, v1=invalidsig";

        $client = $this->createMockClient([]);
        $this->expectException(\InvalidArgumentException::class);
        $client->actions()->verifyHeader($payload, $sigHeader, self::SECRET);
    }

    public function testConstructAction(): void
    {
        $payload = '{"action":"authentication","data":{"user_id":"usr_123"}}';
        $timestamp = (string) (time() * 1000);
        $expectedSig = hash_hmac('sha256', "{$timestamp}.{$payload}", self::SECRET);
        $sigHeader = "t={$timestamp}, v1={$expectedSig}";

        $client = $this->createMockClient([]);
        $result = $client->actions()->constructAction($payload, $sigHeader, self::SECRET);
        $this->assertSame('authentication', $result['action']);
        $this->assertSame('usr_123', $result['data']['user_id']);
    }

    public function testSignResponse(): void
    {
        $client = $this->createMockClient([]);
        $result = $client->actions()->signResponse(
            actionType: 'authentication',
            verdict: 'Allow',
            secret: self::SECRET,
        );

        $this->assertSame('authentication_action_response', $result['object']);
        $this->assertSame('Allow', $result['payload']['verdict']);
        $this->assertArrayHasKey('signature', $result);
        $this->assertArrayHasKey('timestamp', $result['payload']);
    }

    public function testSignResponseWithDeny(): void
    {
        $client = $this->createMockClient([]);
        $result = $client->actions()->signResponse(
            actionType: 'user_registration',
            verdict: 'Deny',
            secret: self::SECRET,
            errorMessage: 'Registration blocked',
        );

        $this->assertSame('user_registration_action_response', $result['object']);
        $this->assertSame('Deny', $result['payload']['verdict']);
        $this->assertSame('Registration blocked', $result['payload']['error_message']);
    }

    public function testActionsAccessibleFromClient(): void
    {
        $client = $this->createMockClient([]);
        $this->assertInstanceOf(Actions::class, $client->actions());
    }
}
