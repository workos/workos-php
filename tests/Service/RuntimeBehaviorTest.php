<?php

declare(strict_types=1);

namespace Tests\Service;

use PHPUnit\Framework\TestCase;
use WorkOS\Exception\AuthenticationException;
use WorkOS\Exception\RateLimitExceededException;
use WorkOS\RequestOptions;
use WorkOS\TestHelper;
use WorkOS\Version;

class RuntimeBehaviorTest extends TestCase
{
    use TestHelper;

    public function testAutoPaginationFetchesEveryPage(): void
    {
        $first = $this->loadFixture('organization');
        $second = $this->loadFixture('organization');
        $third = $this->loadFixture('organization');

        $first['id'] = 'org_first';
        $second['id'] = 'org_second';
        $third['id'] = 'org_third';

        $client = $this->createMockClient([
            [
                'status' => 200,
                'body' => [
                    'data' => [$first, $second],
                    'list_metadata' => ['after' => 'cursor_next'],
                ],
            ],
            [
                'status' => 200,
                'body' => [
                    'data' => [$third],
                    'list_metadata' => ['after' => null],
                ],
            ],
        ]);

        $page = $client->organizations()->listOrganizations();
        $ids = array_map(
            static fn ($organization) => $organization->id,
            iterator_to_array($page->autoPagingIterator(), false),
        );

        $this->assertSame(['org_first', 'org_second', 'org_third'], $ids);
        $this->assertSame('order=desc&after=cursor_next', $this->getLastRequest()->getUri()->getQuery());
    }

    public function testPerRequestOverridesAffectTransport(): void
    {
        $client = $this->createMockClient(
            [['status' => 200, 'body' => $this->loadFixture('organization')]],
            baseUrl: 'https://api.primary.example',
        );

        $client->organizations()->getOrganization(
            'org_123',
            new RequestOptions(
                extraHeaders: ['X-Test-Header' => 'override'],
                timeout: 12,
                baseUrl: 'https://api.override.example/v1',
            ),
        );

        $request = $this->getLastRequest();
        $requestOptions = $this->getLastRequestOptions();

        $this->assertSame('api.override.example', $request->getUri()->getHost());
        $this->assertSame('/v1/organizations/org_123', $request->getUri()->getPath());
        $this->assertSame('override', $request->getHeaderLine('X-Test-Header'));
        $this->assertSame(12, $requestOptions['timeout']);
    }

    public function testWrapperMethodsUseInstanceScopedCredentials(): void
    {
        $fixture = $this->loadFixture('authenticate_response');

        $clientA = $this->createMockClient(
            [['status' => 200, 'body' => $fixture]],
            apiKey: 'api_key_a',
            clientId: 'client_a',
        );
        $clientA->userManagement()->authenticateWithPassword(email: 'a@example.com', password: 'secret');
        $requestBodyA = json_decode((string) $this->getLastRequest()->getBody(), true);

        $clientB = $this->createMockClient(
            [['status' => 200, 'body' => $fixture]],
            apiKey: 'api_key_b',
            clientId: 'client_b',
        );
        $clientB->userManagement()->authenticateWithPassword(email: 'b@example.com', password: 'secret');
        $requestBodyB = json_decode((string) $this->getLastRequest()->getBody(), true);

        $this->assertSame('client_a', $requestBodyA['client_id']);
        $this->assertSame('api_key_a', $requestBodyA['client_secret']);
        $this->assertSame('client_b', $requestBodyB['client_id']);
        $this->assertSame('api_key_b', $requestBodyB['client_secret']);
    }

    public function testAuthenticationErrorsAreMapped(): void
    {
        $client = $this->createMockClient([
            [
                'status' => 401,
                'headers' => ['X-Request-ID' => 'req_auth'],
                'body' => ['message' => 'Nope'],
            ],
        ]);

        try {
            $client->organizations()->getOrganization(
                'org_123',
                new RequestOptions(maxRetries: 0),
            );
            $this->fail('Expected AuthenticationException');
        } catch (AuthenticationException $exception) {
            $this->assertSame(401, $exception->statusCode);
            $this->assertSame('req_auth', $exception->requestId);
            $this->assertSame('Nope', $exception->getMessage());
        }
    }

    public function testDefaultUserAgentIdentifiesTheSdk(): void
    {
        $client = $this->createMockClient([
            ['status' => 200, 'body' => $this->loadFixture('organization')],
        ]);

        $client->organizations()->getOrganization('org_123');

        $this->assertSame(
            sprintf('%s/%s', Version::SDK_IDENTIFIER, Version::SDK_VERSION),
            $this->getLastRequest()->getHeaderLine('User-Agent'),
        );
    }

    public function testConstructorUserAgentOverridesDefault(): void
    {
        $client = $this->createMockClient(
            [['status' => 200, 'body' => $this->loadFixture('organization')]],
            userAgent: 'WorkOS PHP Laravel/5.1.0',
        );

        $client->organizations()->getOrganization('org_123');

        $this->assertSame(
            'WorkOS PHP Laravel/5.1.0',
            $this->getLastRequest()->getHeaderLine('User-Agent'),
        );
    }

    public function testPerRequestExtraHeadersCannotOverrideUserAgent(): void
    {
        $client = $this->createMockClient(
            [['status' => 200, 'body' => $this->loadFixture('organization')]],
            userAgent: 'WorkOS PHP Laravel/5.1.0',
        );

        $client->organizations()->getOrganization(
            'org_123',
            new RequestOptions(extraHeaders: ['User-Agent' => 'Custom/9.9.9']),
        );

        $this->assertSame(
            'WorkOS PHP Laravel/5.1.0',
            $this->getLastRequest()->getHeaderLine('User-Agent'),
        );
    }

    public function testRateLimitErrorsExposeRetryAfter(): void
    {
        $client = $this->createMockClient([
            [
                'status' => 429,
                'headers' => [
                    'X-Request-ID' => 'req_rate',
                    'Retry-After' => '7',
                ],
                'body' => ['message' => 'Slow down'],
            ],
        ]);

        try {
            $client->organizations()->listOrganizations(options: new RequestOptions(maxRetries: 0));
            $this->fail('Expected RateLimitExceededException');
        } catch (RateLimitExceededException $exception) {
            $this->assertSame(429, $exception->statusCode);
            $this->assertSame('req_rate', $exception->requestId);
            $this->assertSame('Slow down', $exception->getMessage());
            $this->assertSame(7, $exception->retryAfter);
        }
    }
}
