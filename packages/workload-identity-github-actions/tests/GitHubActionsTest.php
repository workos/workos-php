<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace Tests;

use PHPUnit\Framework\TestCase;
use WorkOS\WorkloadIdentity\GitHubActions\GitHubActions;
use WorkOS\WorkloadIdentity\IdentityTokenProvider;

/**
 * The GitHub Actions provider cases, WIF-GHA-01 and WIF-GHA-02, and the
 * failure paths. The token endpoint is a mock server on the loopback
 * interface; no test reaches GitHub.
 */
final class GitHubActionsTest extends TestCase
{
    private const ENV_VARS = ['ACTIONS_ID_TOKEN_REQUEST_URL', 'ACTIONS_ID_TOKEN_REQUEST_TOKEN'];

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    /** @var list<MockServer> */
    private array $servers = [];

    protected function setUp(): void
    {
        foreach (self::ENV_VARS as $name) {
            $this->savedEnv[$name] = getenv($name);
            putenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $name => $value) {
            putenv($value === false ? $name : sprintf('%s=%s', $name, $value));
        }
        foreach ($this->servers as $server) {
            $server->stop();
        }
    }

    public function testWIF_GHA_01_GetsTheTokenWithTheEncodedAudienceAndTheRequestTokenAsBearer(): void
    {
        $server = $this->server(200, '{"value":"gha-jwt"}', ['Content-Type' => 'application/json']);
        putenv('ACTIONS_ID_TOKEN_REQUEST_URL=' . $server->url . '/token?api-version=2.0');
        putenv('ACTIONS_ID_TOKEN_REQUEST_TOKEN=gha-request-token');

        $token = GitHubActions::create('https://api.workos.com')->getIdentityToken();

        $this->assertSame('gha-jwt', $token);
        $requests = $server->requests();
        $this->assertCount(1, $requests);
        $this->assertSame('GET', $requests[0]['method']);
        $this->assertSame('/token?api-version=2.0&audience=https%3A%2F%2Fapi.workos.com', $requests[0]['uri']);
        $this->assertSame('Bearer gha-request-token', $requests[0]['headers']['authorization']);
        $this->assertSame('application/json', $requests[0]['headers']['accept']);
    }

    public function testWIF_GHA_02_MissingEnvironmentVariablesFailWithThePermissionHint(): void
    {
        $server = $this->server(200, '{"value":"gha-jwt"}');
        $provider = GitHubActions::create('https://api.workos.com');

        // No request URL.
        putenv('ACTIONS_ID_TOKEN_REQUEST_TOKEN=gha-request-token');
        $this->assertStringContainsString('permissions: id-token: write', $this->failureOf($provider)->getMessage());

        // No request token.
        putenv('ACTIONS_ID_TOKEN_REQUEST_URL=' . $server->url . '/token?api-version=2.0');
        putenv('ACTIONS_ID_TOKEN_REQUEST_TOKEN');
        $this->assertStringContainsString('permissions: id-token: write', $this->failureOf($provider)->getMessage());

        $this->assertCount(0, $server->requests());
    }

    public function testANon200StatusIsAnErrorThatNamesTheStatus(): void
    {
        $server = $this->server(404, 'Not Found');
        $this->pointAt($server);

        $error = $this->failureOf(GitHubActions::create('https://api.workos.com'));

        $this->assertStringContainsString('404', $error->getMessage());
    }

    public function testAResponseWithoutATokenIsAnError(): void
    {
        foreach (['{}', '{"value":""}', 'not json', ''] as $body) {
            $this->pointAt($this->server(200, $body));

            $error = $this->failureOf(GitHubActions::create('https://api.workos.com'));

            $this->assertStringContainsString('no token', $error->getMessage());
        }
    }

    public function testARedirectIsNotFollowed(): void
    {
        $second = $this->server(200, '{"value":"gha-jwt"}');
        $first = $this->server(302, '', ['Location' => $second->url . '/token']);
        $this->pointAt($first);

        $error = $this->failureOf(GitHubActions::create('https://api.workos.com'));

        $this->assertStringContainsString('302', $error->getMessage());
        $this->assertCount(1, $first->requests());
        $this->assertCount(0, $second->requests());
    }

    public function testAnUnreachableEndpointIsAnErrorWithoutTheRequestToken(): void
    {
        $server = $this->server();
        $this->pointAt($server);
        $server->stop();
        $this->servers = [];

        $error = $this->failureOf(GitHubActions::create('https://api.workos.com'));

        $this->assertStringContainsString('could not be reached', $error->getMessage());
        $this->assertStringNotContainsString('gha-request-token', (string) $error);
    }

    public function testTheAudienceIsPercentEncoded(): void
    {
        $server = $this->server(200, '{"value":"gha-jwt"}');
        putenv('ACTIONS_ID_TOKEN_REQUEST_URL=' . $server->url . '/token');
        putenv('ACTIONS_ID_TOKEN_REQUEST_TOKEN=gha-request-token');

        GitHubActions::create('urn:example:api/v1 & more')->getIdentityToken();

        $this->assertSame('/token?audience=urn%3Aexample%3Aapi%2Fv1%20%26%20more', $server->requests()[0]['uri']);
    }

    public function testTheEnvironmentIsReadOnEveryCall(): void
    {
        $provider = GitHubActions::create('https://api.workos.com');
        $this->failureOf($provider);

        $this->pointAt($this->server(200, '{"value":"gha-jwt"}'));

        $this->assertSame('gha-jwt', $provider->getIdentityToken());
    }

    public function testItIsAnIdentityTokenProviderAndRequiresAnAudience(): void
    {
        $this->assertInstanceOf(IdentityTokenProvider::class, GitHubActions::create('https://api.workos.com'));

        $this->expectException(\InvalidArgumentException::class);
        GitHubActions::create('');
    }

    /**
     * @param array<string, string> $headers
     */
    private function server(int $status = 200, string $body = '', array $headers = []): MockServer
    {
        return $this->servers[] = new MockServer($status, $body, $headers);
    }

    private function pointAt(MockServer $server): void
    {
        putenv('ACTIONS_ID_TOKEN_REQUEST_URL=' . $server->url . '/token?api-version=2.0');
        putenv('ACTIONS_ID_TOKEN_REQUEST_TOKEN=gha-request-token');
    }

    private function failureOf(GitHubActions $provider): \RuntimeException
    {
        try {
            $provider->getIdentityToken();
        } catch (\RuntimeException $e) {
            return $e;
        }

        $this->fail('Expected the provider to fail.');
    }
}
