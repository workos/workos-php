<?php

declare(strict_types=1);
// @oagen-ignore-file
// Hand-maintained tests for the Passwordless module.

namespace Tests;

use PHPUnit\Framework\TestCase;
use WorkOS\TestHelper;

class PasswordlessTest extends TestCase
{
    use TestHelper;

    public function testCreateSession(): void
    {
        $fixture = [
            'object' => 'passwordless_session',
            'id' => 'passwordless_session_01EZCZ',
            'email' => 'user@example.com',
            'expires_at' => '2024-01-01T00:00:00Z',
            'link' => 'https://auth.workos.com/passwordless/01EZCZ/confirm',
        ];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $result = $client->passwordless()->createSession(email: 'user@example.com');
        $this->assertIsArray($result);
        $this->assertSame('passwordless_session', $result['object']);
        $request = $this->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('passwordless/sessions', $request->getUri()->getPath());
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('user@example.com', $body['email']);
        $this->assertSame('MagicLink', $body['type']);
    }

    public function testSendSession(): void
    {
        $client = $this->createMockClient([['status' => 204]]);
        $client->passwordless()->sendSession('passwordless_session_01EZCZ');
        $request = $this->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith(
            'passwordless/sessions/passwordless_session_01EZCZ/send',
            $request->getUri()->getPath()
        );
    }

    public function testPasswordlessAccessibleFromClient(): void
    {
        $client = $this->createMockClient([]);
        $this->assertInstanceOf(\WorkOS\Passwordless::class, $client->passwordless());
    }
}
