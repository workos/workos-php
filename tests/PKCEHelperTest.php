<?php

declare(strict_types=1);
// @oagen-ignore-file
// Hand-maintained tests for the PKCEHelper module (H08, H10, H11, H15, H16, H19).

namespace Tests;

use PHPUnit\Framework\TestCase;
use WorkOS\PKCEHelper;
use WorkOS\TestHelper;
use WorkOS\WorkOS;

class PKCEHelperTest extends TestCase
{
    use TestHelper;

    // -- H08: Core PKCE utilities --

    public function testGenerateCodeVerifier(): void
    {
        $verifier = PKCEHelper::generateCodeVerifier();
        $this->assertSame(43, strlen($verifier));
        // base64url: only [A-Za-z0-9_-]
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $verifier);
    }

    public function testGenerateCodeVerifierCustomLength(): void
    {
        $verifier = PKCEHelper::generateCodeVerifier(128);
        $this->assertSame(128, strlen($verifier));
    }

    public function testGenerateCodeVerifierTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PKCEHelper::generateCodeVerifier(42);
    }

    public function testGenerateCodeVerifierTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PKCEHelper::generateCodeVerifier(129);
    }

    public function testGenerateCodeChallenge(): void
    {
        $verifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
        $challenge = PKCEHelper::generateCodeChallenge($verifier);
        // S256: base64url(SHA256(verifier))
        $expected = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $this->assertSame($expected, $challenge);
    }

    public function testGenerate(): void
    {
        $pair = PKCEHelper::generate();
        $this->assertArrayHasKey('code_verifier', $pair);
        $this->assertArrayHasKey('code_challenge', $pair);
        $this->assertSame('S256', $pair['code_challenge_method']);

        // Verify the challenge matches the verifier
        $expectedChallenge = PKCEHelper::generateCodeChallenge($pair['code_verifier']);
        $this->assertSame($expectedChallenge, $pair['code_challenge']);
    }

    // -- H10: AuthKit PKCE authorization URL --

    public function testGetAuthKitAuthorizationUrl(): void
    {
        $client = $this->createMockClient([['status' => 200, 'body' => ['url' => 'https://auth.workos.com/...']]]);
        $result = $client->pkce()->getAuthKitAuthorizationUrl(
            redirectUri: 'https://example.com/callback',
            clientId: 'client_123',
        );
        $this->assertArrayHasKey('code_verifier', $result);
        $this->assertArrayHasKey('state', $result);
        $this->assertSame(43, strlen($result['code_verifier']));
        $this->assertSame(32, strlen($result['state'])); // hex-encoded 16 bytes

        $request = $this->getLastRequest();
        $this->assertSame('GET', $request->getMethod());
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('S256', $query['code_challenge_method']);
    }

    // -- H11: AuthKit PKCE code exchange --

    public function testAuthKitCodeExchange(): void
    {
        $fixture = ['access_token' => 'at_123', 'user' => ['id' => 'usr_1']];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $result = $client->pkce()->authKitCodeExchange(
            code: 'auth_code_123',
            codeVerifier: 'verifier_123',
            clientId: 'client_123',
        );
        $this->assertSame('at_123', $result['access_token']);
        $request = $this->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('authorization_code', $body['grant_type']);
        $this->assertSame('verifier_123', $body['code_verifier']);
    }

    // -- H15: SSO PKCE authorization URL --

    public function testGetSsoAuthorizationUrl(): void
    {
        $client = $this->createMockClient([['status' => 200, 'body' => ['url' => 'https://auth.workos.com/sso/...']]]);
        $result = $client->pkce()->getSsoAuthorizationUrl(
            redirectUri: 'https://example.com/callback',
            clientId: 'client_123',
            domain: 'example.com',
        );
        $this->assertArrayHasKey('code_verifier', $result);
        $this->assertArrayHasKey('state', $result);

        $request = $this->getLastRequest();
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame('example.com', $query['domain']);
    }

    // -- H16: SSO PKCE code exchange --

    public function testSsoCodeExchange(): void
    {
        $fixture = ['access_token' => 'at_sso', 'profile' => ['id' => 'prof_1']];
        $client = $this->createMockClient([['status' => 200, 'body' => $fixture]]);
        $result = $client->pkce()->ssoCodeExchange(
            code: 'sso_code_123',
            codeVerifier: 'verifier_sso',
            clientId: 'client_123',
        );
        $this->assertSame('at_sso', $result['access_token']);
        $request = $this->getLastRequest();
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('verifier_sso', $body['code_verifier']);
    }

    // -- H19: Public client factory --

    public function testCreatePublicClient(): void
    {
        $client = PKCEHelper::createPublicClient('client_123');
        $this->assertInstanceOf(WorkOS::class, $client);
        $httpClientProperty = new \ReflectionProperty($client, 'httpClient');
        $this->assertSame('client_123', $httpClientProperty->getValue($client)->getClientId());
    }

    public function testPkceAccessibleFromClient(): void
    {
        $client = $this->createMockClient([]);
        $this->assertInstanceOf(PKCEHelper::class, $client->pkce());
    }
}
