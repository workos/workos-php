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

    // -- security-fix-plan.md finding #60: JWS verification --

    /**
     * Build a JWKS dict + signed JWT for verification tests.
     *
     * @param array<string, mixed> $claims
     * @param string $alg The `alg` value to advertise in the JWT header.
     * @return array{0: array<string, mixed>, 1: string} JWKS, signed JWT.
     */
    private function buildSignedJwt(array $claims, string $alg = 'RS256', string $kid = 'kid_test'): array
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $details = openssl_pkey_get_details($key);

        $b64u = static fn (string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

        $header = $b64u(json_encode(['alg' => $alg, 'typ' => 'JWT', 'kid' => $kid]));
        $payload = $b64u(json_encode($claims));
        $signingInput = $header . '.' . $payload;

        openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256);
        $jwt = $signingInput . '.' . $b64u($signature);

        $jwk = [
            'kty' => 'RSA',
            'kid' => $kid,
            'alg' => 'RS256',
            'use' => 'sig',
            'n' => $b64u($details['rsa']['n']),
            'e' => $b64u($details['rsa']['e']),
        ];

        return [['keys' => [$jwk]], $jwt];
    }

    public function testAuthenticateValidatesSignedJwt(): void
    {
        [$jwks, $jwt] = $this->buildSignedJwt([
            'sid' => 'session_test',
            'org_id' => 'org_test',
            'exp' => time() + 3600,
        ]);

        $sealed = SessionManager::sealSessionFromAuthResponse(
            accessToken: $jwt,
            refreshToken: 'ref_test',
            cookiePassword: $this->cookiePassword,
            user: ['id' => 'usr_test'],
        );

        $client = $this->createMockClient([['status' => 200, 'body' => $jwks]]);
        $result = $client->sessionManager()->authenticate(
            sessionData: $sealed,
            cookiePassword: $this->cookiePassword,
            clientId: 'client_123',
        );

        $this->assertTrue($result['authenticated']);
        $this->assertSame('session_test', $result['session_id']);
        $this->assertSame('org_test', $result['organization_id']);
    }

    public function testAuthenticateRejectsTamperedSignature(): void
    {
        [$jwks, $jwt] = $this->buildSignedJwt([
            'sid' => 'session_test',
            'exp' => time() + 3600,
        ]);

        // Flip a byte in the middle of the signature segment so the base64
        // decoder produces a clearly different signature. Avoids the trailing
        // padding bits that base64url can canonicalise away.
        $parts = explode('.', $jwt);
        $sig = $parts[2];
        $mid = intdiv(strlen($sig), 2);
        $parts[2] = substr($sig, 0, $mid) . ($sig[$mid] === 'A' ? 'B' : 'A') . substr($sig, $mid + 1);
        $tampered = implode('.', $parts);

        $sealed = SessionManager::sealSessionFromAuthResponse(
            accessToken: $tampered,
            refreshToken: 'ref_test',
            cookiePassword: $this->cookiePassword,
        );

        $client = $this->createMockClient([['status' => 200, 'body' => $jwks]]);
        $result = $client->sessionManager()->authenticate(
            sessionData: $sealed,
            cookiePassword: $this->cookiePassword,
            clientId: 'client_123',
        );

        $this->assertFalse($result['authenticated']);
        $this->assertSame('invalid_jwt', $result['reason']);
    }

    public function testAuthenticateRejectsAlgNone(): void
    {
        // Forge a `none`-algorithm token; signature verification must refuse
        // even before fetching JWKS.
        $b64u = static fn (string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
        $header = $b64u(json_encode(['alg' => 'none', 'typ' => 'JWT', 'kid' => 'kid_test']));
        $payload = $b64u(json_encode(['sid' => 'session_test', 'exp' => time() + 3600]));
        $jwt = $header . '.' . $payload . '.';

        $sealed = SessionManager::sealSessionFromAuthResponse(
            accessToken: $jwt,
            refreshToken: 'ref_test',
            cookiePassword: $this->cookiePassword,
        );

        $client = $this->createMockClient([]);
        $result = $client->sessionManager()->authenticate(
            sessionData: $sealed,
            cookiePassword: $this->cookiePassword,
            clientId: 'client_123',
        );

        $this->assertFalse($result['authenticated']);
        $this->assertSame('invalid_jwt', $result['reason']);
    }

    public function testAuthenticateRejectsUnknownKid(): void
    {
        [, $jwt] = $this->buildSignedJwt(
            ['sid' => 'session_test', 'exp' => time() + 3600],
            'RS256',
            'kid_signed_with',
        );

        // JWKS advertises a different kid than the token claims.
        $otherJwks = ['keys' => [['kty' => 'RSA', 'kid' => 'kid_other', 'n' => 'AQ', 'e' => 'AQAB']]];

        $sealed = SessionManager::sealSessionFromAuthResponse(
            accessToken: $jwt,
            refreshToken: 'ref_test',
            cookiePassword: $this->cookiePassword,
        );

        $client = $this->createMockClient([['status' => 200, 'body' => $otherJwks]]);
        $result = $client->sessionManager()->authenticate(
            sessionData: $sealed,
            cookiePassword: $this->cookiePassword,
            clientId: 'client_123',
        );

        $this->assertFalse($result['authenticated']);
        $this->assertSame('invalid_jwt', $result['reason']);
    }
}
