<?php

declare(strict_types=1);
// @oagen-ignore-file

namespace Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use WorkOS\Exception\ApiException;
use WorkOS\Exception\BadRequestException;
use WorkOS\Exception\RateLimitExceededException;
use WorkOS\HttpClient;

class HttpClientTest extends TestCase
{
    public function testDecodeResponseThrowsOnNonJsonBody(): void
    {
        $html = '<html><body>Redirect</body></html>';
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'text/html'], $html),
        ]);
        $handler = HandlerStack::create($mock);

        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
            handler: $handler,
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Expected JSON response but received non-JSON body');

        $client->request('GET', '/test');
    }

    public function testBuildUrlOmitsQuestionMarkForEmptyQuery(): void
    {
        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
        );

        $url = $client->buildUrl('sso/authorize', []);
        $this->assertSame('https://api.workos.com/sso/authorize', $url);
    }

    public function testBuildUrlOmitsQuestionMarkForEmptyArrayValues(): void
    {
        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
        );

        // http_build_query returns '' for arrays containing only empty arrays
        $url = $client->buildUrl('sso/authorize', ['scopes' => []]);
        $this->assertStringNotContainsString('?', $url);
    }

    public function testBuildUrlAppendsQueryString(): void
    {
        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
        );

        $url = $client->buildUrl('sso/authorize', ['client_id' => 'abc', 'response_type' => 'code']);
        $this->assertStringContainsString('?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
        $this->assertSame('abc', $query['client_id']);
        $this->assertSame('code', $query['response_type']);
    }

    public function testErrorResponseIncludesCodeAndError(): void
    {
        $body = json_encode([
            'message' => 'Organization not found',
            'code' => 'entity_not_found',
            'error' => 'not_found',
        ]);

        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], $body),
        ]);

        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
            handler: HandlerStack::create($mock),
        );

        try {
            $client->request('GET', '/test');
            $this->fail('Expected BadRequestException');
        } catch (BadRequestException $e) {
            $this->assertSame('Organization not found', $e->getMessage());
            $this->assertSame(400, $e->statusCode);
            $this->assertSame('entity_not_found', $e->errorCode);
            $this->assertSame('not_found', $e->error);
        }
    }

    public function testErrorResponseOmittingCodeAndErrorSetsNull(): void
    {
        $body = json_encode(['message' => 'Something went wrong']);

        $mock = new MockHandler([
            new Response(422, ['Content-Type' => 'application/json'], $body),
        ]);

        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
            handler: HandlerStack::create($mock),
        );

        try {
            $client->request('GET', '/test');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame('Something went wrong', $e->getMessage());
            $this->assertNull($e->errorCode);
            $this->assertNull($e->error);
        }
    }

    public function testRateLimitExceptionIncludesRetryAfterAndErrorFields(): void
    {
        $body = json_encode([
            'message' => 'Rate limit exceeded',
            'code' => 'rate_limit',
            'error' => 'too_many_requests',
        ]);

        $mock = new MockHandler([
            new Response(429, [
                'Content-Type' => 'application/json',
                'Retry-After' => '30',
            ], $body),
        ]);

        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
            handler: HandlerStack::create($mock),
        );

        try {
            $client->request('GET', '/test');
            $this->fail('Expected RateLimitExceededException');
        } catch (RateLimitExceededException $e) {
            $this->assertSame('Rate limit exceeded', $e->getMessage());
            $this->assertSame(429, $e->statusCode);
            $this->assertSame('rate_limit', $e->errorCode);
            $this->assertSame('too_many_requests', $e->error);
            $this->assertSame(30, $e->retryAfter);
        }
    }

    public function testEmptyErrorBodySetsNullCodeAndError(): void
    {
        $mock = new MockHandler([
            new Response(500, ['Content-Type' => 'application/json'], ''),
        ]);

        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
            handler: HandlerStack::create($mock),
        );

        try {
            $client->request('GET', '/test');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertStringContainsString('WorkOS request failed with status 500', $e->getMessage());
            $this->assertNull($e->errorCode);
            $this->assertNull($e->error);
        }
    }

    public function testNonStringCodeFieldIsIgnored(): void
    {
        $body = json_encode([
            'message' => 'Validation failed',
            'code' => 42,
            'error' => ['nested' => 'object'],
        ]);

        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], $body),
        ]);

        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
            handler: HandlerStack::create($mock),
        );

        try {
            $client->request('GET', '/test');
            $this->fail('Expected BadRequestException');
        } catch (BadRequestException $e) {
            $this->assertSame('Validation failed', $e->getMessage());
            $this->assertNull($e->errorCode);
            $this->assertNull($e->error);
        }
    }
}
