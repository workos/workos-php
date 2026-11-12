<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace Tests;

use PHPUnit\Framework\TestCase;
use WorkOS\WorkloadIdentity\Gcp\GcpMetadata;
use WorkOS\WorkloadIdentity\IdentityTokenProvider;

/**
 * The GCP provider cases, WIF-GCP-01 to WIF-GCP-03, and the failure paths.
 * The metadata server is a mock server on the loopback interface; no test
 * reaches GCP.
 */
final class GcpMetadataTest extends TestCase
{
    /** @var list<MockServer> */
    private array $servers = [];

    protected function tearDown(): void
    {
        foreach ($this->servers as $server) {
            $server->stop();
        }
    }

    public function testWIF_GCP_01_GetsTheIdentityUrlWithTheEncodedAudienceAndTheMetadataHeader(): void
    {
        $server = $this->server(200, "gcp-jwt\n");

        $token = $this->provider($server, 'https://api.workos.com')->getIdentityToken();

        $this->assertSame('gcp-jwt', $token);
        $requests = $server->requests();
        $this->assertCount(1, $requests);
        $this->assertSame('GET', $requests[0]['method']);
        $this->assertSame(
            '/computeMetadata/v1/instance/service-accounts/default/identity?audience=https%3A%2F%2Fapi.workos.com&format=full',
            $requests[0]['uri'],
        );
        $this->assertSame('Google', $requests[0]['headers']['metadata-flavor']);
    }

    public function testWIF_GCP_02_ANon200OrAnEmptyBodyIsAnErrorAndNoRedirectIsFollowed(): void
    {
        // A 404.
        $error = $this->failureOf($this->provider($this->server(404, 'Not Found'), 'https://api.workos.com'));
        $this->assertStringContainsString('404', $error->getMessage());

        // A 200 with an empty body.
        $error = $this->failureOf($this->provider($this->server(200, ''), 'https://api.workos.com'));
        $this->assertStringContainsString('empty', $error->getMessage());

        // A 302 to a second mock.
        $second = $this->server(200, 'gcp-jwt');
        $first = $this->server(302, '', ['Location' => $second->url . '/identity']);
        $error = $this->failureOf($this->provider($first, 'https://api.workos.com'));
        $this->assertStringContainsString('302', $error->getMessage());
        $this->assertCount(1, $first->requests());
        $this->assertCount(0, $second->requests());
    }

    public function testAWhitespaceOnlyBodyIsAnError(): void
    {
        $error = $this->failureOf($this->provider($this->server(200, " \n"), 'https://api.workos.com'));

        $this->assertStringContainsString('empty', $error->getMessage());
    }

    public function testAnUnreachableMetadataServerIsAnError(): void
    {
        $server = $this->server();
        $provider = $this->provider($server, 'https://api.workos.com');
        $server->stop();
        $this->servers = [];

        $error = $this->failureOf($provider);

        $this->assertStringContainsString('could not be reached', $error->getMessage());
    }

    public function testWIF_GCP_03_ProxyEnvironmentVariablesAreIgnored(): void
    {
        $names = ['http_proxy', 'all_proxy', 'ALL_PROXY', 'no_proxy', 'NO_PROXY'];
        $saved = [];
        foreach ($names as $name) {
            $saved[$name] = getenv($name);
            putenv($name);
        }
        // A proxy nothing listens on: a request sent to it fails.
        putenv('http_proxy=http://127.0.0.1:9');
        putenv('all_proxy=http://127.0.0.1:9');

        try {
            $server = $this->server(200, 'gcp-jwt');

            $this->assertSame('gcp-jwt', $this->provider($server, 'https://api.workos.com')->getIdentityToken());
            $this->assertCount(1, $server->requests());
        } finally {
            foreach ($saved as $name => $value) {
                putenv($value === false ? $name : sprintf('%s=%s', $name, $value));
            }
        }
    }

    public function testTheAudienceIsPercentEncoded(): void
    {
        $server = $this->server(200, 'gcp-jwt');

        $this->provider($server, 'urn:example:api/v1 & more')->getIdentityToken();

        $this->assertSame(
            '/computeMetadata/v1/instance/service-accounts/default/identity?audience=urn%3Aexample%3Aapi%2Fv1%20%26%20more&format=full',
            $server->requests()[0]['uri'],
        );
    }

    public function testTheMetadataServerAddressIsNotAnOption(): void
    {
        $provider = GcpMetadata::create('https://api.workos.com');

        $this->assertSame('http://metadata.google.internal', self::baseUrl()->getValue($provider));
        $this->assertCount(1, (new \ReflectionMethod(GcpMetadata::class, 'create'))->getParameters());
    }

    public function testItIsAnIdentityTokenProviderAndRequiresAnAudience(): void
    {
        $this->assertInstanceOf(IdentityTokenProvider::class, GcpMetadata::create('https://api.workos.com'));

        $this->expectException(\InvalidArgumentException::class);
        GcpMetadata::create('');
    }

    /**
     * @param array<string, string> $headers
     */
    private function server(int $status = 200, string $body = '', array $headers = []): MockServer
    {
        return $this->servers[] = new MockServer($status, $body, $headers);
    }

    /**
     * A provider whose metadata server address is the mock's. The address is
     * a private property, not an option, so the test sets it by reflection.
     */
    private function provider(MockServer $server, string $audience): GcpMetadata
    {
        $provider = GcpMetadata::create($audience);
        self::baseUrl()->setValue($provider, $server->url);

        return $provider;
    }

    private static function baseUrl(): \ReflectionProperty
    {
        return new \ReflectionProperty(GcpMetadata::class, 'baseUrl');
    }

    private function failureOf(GcpMetadata $provider): \RuntimeException
    {
        try {
            $provider->getIdentityToken();
        } catch (\RuntimeException $e) {
            return $e;
        }

        $this->fail('Expected the provider to fail.');
    }
}
