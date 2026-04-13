<?php

declare(strict_types=1);
// @oagen-ignore-file
// Hand-maintained tests for the SessionManager module (H04-H07, H13).

namespace Tests;

use PHPUnit\Framework\TestCase;
use WorkOS\SessionManager;
use WorkOS\TestHelper;

class SessionManagerTest extends TestCase
{
    use TestHelper;

    private string $cookiePassword;

    protected function setUp(): void
    {
        parent::setUp();
        // Generate a valid 32-byte key for sodium_crypto_secretbox
        $this->cookiePassword = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }

    // -- H06: seal/unseal --

    public function testSealAndUnsealData(): void
    {
        $data = ['access_token' => 'tok_123', 'refresh_token' => 'ref_456'];
        $sealed = SessionManager::sealData($data, $this->cookiePassword);
        $this->assertIsString($sealed);
        $this->assertNotSame(json_encode($data), $sealed);

        $unsealed = SessionManager::unsealData($sealed, $this->cookiePassword);
        $this->assertSame($data, $unsealed);
    }

    public function testUnsealWithWrongKey(): void
    {
        $data = ['access_token' => 'tok_123'];
        $sealed = SessionManager::sealData($data, $this->cookiePassword);

        $wrongKey = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
        $this->expectException(\InvalidArgumentException::class);
        SessionManager::unsealData($sealed, $wrongKey);
    }

    // -- H07: Auth response sealing --

    public function testSealSessionFromAuthResponse(): void
    {
        $sealed = SessionManager::sealSessionFromAuthResponse(
            accessToken: 'access_tok_123',
            refreshToken: 'refresh_tok_456',
            cookiePassword: $this->cookiePassword,
            user: ['id' => 'usr_123', 'email' => 'test@example.com'],
        );

        $this->assertIsString($sealed);

        $unsealed = SessionManager::unsealData($sealed, $this->cookiePassword);
        $this->assertSame('access_tok_123', $unsealed['access_token']);
        $this->assertSame('refresh_tok_456', $unsealed['refresh_token']);
        $this->assertSame('usr_123', $unsealed['user']['id']);
    }

    // -- H04: Session cookie authenticate --

    public function testAuthenticateNoSession(): void
    {
        $client = $this->createMockClient([]);
        $result = $client->sessionManager()->authenticate(
            sessionData: '',
            cookiePassword: $this->cookiePassword,
            clientId: 'client_123',
        );
        $this->assertFalse($result['authenticated']);
        $this->assertSame('no_session_cookie_provided', $result['reason']);
    }

    public function testAuthenticateInvalidCookie(): void
    {
        $client = $this->createMockClient([]);
        $result = $client->sessionManager()->authenticate(
            sessionData: 'not-a-valid-sealed-string',
            cookiePassword: $this->cookiePassword,
            clientId: 'client_123',
        );
        $this->assertFalse($result['authenticated']);
        $this->assertSame('invalid_session_cookie', $result['reason']);
    }

    public function testAuthenticateMissingAccessToken(): void
    {
        $sealed = SessionManager::sealData(
            ['refresh_token' => 'ref_123'],
            $this->cookiePassword,
        );

        $client = $this->createMockClient([]);
        $result = $client->sessionManager()->authenticate(
            sessionData: $sealed,
            cookiePassword: $this->cookiePassword,
            clientId: 'client_123',
        );
        $this->assertFalse($result['authenticated']);
        $this->assertSame('invalid_session_cookie', $result['reason']);
    }

    // -- H13: JWKS helper --

    public function testGetJwksUrl(): void
    {
        $url = SessionManager::getJwksUrl('client_123');
        $this->assertSame('https://api.workos.com/sso/jwks/client_123', $url);
    }

    public function testGetJwksUrlCustomBase(): void
    {
        $url = SessionManager::getJwksUrl('client_123', 'https://custom.workos.com/');
        $this->assertSame('https://custom.workos.com/sso/jwks/client_123', $url);
    }

    public function testFetchJwks(): void
    {
        $fixture = ['keys' => [['kty' => 'RSA', 'kid' => 'key_1']]];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $result = $client->sessionManager()->fetchJwks('client_123');
        $this->assertArrayHasKey('keys', $result);
        $request = $this->getLastRequest();
        $this->assertStringEndsWith('sso/jwks/client_123', $request->getUri()->getPath());
    }

    public function testSessionManagerAccessibleFromClient(): void
    {
        $client = $this->createMockClient([]);
        $this->assertInstanceOf(SessionManager::class, $client->sessionManager());
    }
}
