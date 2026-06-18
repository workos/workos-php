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
            $this->assertSame(
                ['message' => 'Organization not found', 'code' => 'entity_not_found', 'error' => 'not_found'],
                $e->rawBody,
            );
        }
    }

    public function testErrorResponseExposesAdditionalBodyFields(): void
    {
        // Headless AuthKit returns extra metadata (pending_authentication_token, email, etc.)
        // alongside an error. Customers need access to these fields to drive next-step flows.
        $body = json_encode([
            'message' => 'Email verification required.',
            'code' => 'email_verification_required',
            'error' => 'email_verification_required',
            'error_description' => 'The user must verify their email before signing in.',
            'pending_authentication_token' => 'pat_01HXYZ',
            'email' => 'user@example.com',
            'email_verification_id' => 'email_verification_01HXYZ',
        ]);

        $mock = new MockHandler([
            new Response(403, ['Content-Type' => 'application/json'], $body),
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
            $this->assertNotNull($e->rawBody);
            $this->assertSame('pat_01HXYZ', $e->rawBody['pending_authentication_token']);
            $this->assertSame('user@example.com', $e->rawBody['email']);
            $this->assertSame('email_verification_01HXYZ', $e->rawBody['email_verification_id']);
            $this->assertSame(
                'The user must verify their email before signing in.',
                $e->rawBody['error_description'],
            );
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
            $this->assertNull($e->rawBody);
        }
    }

    public function testResolveUrlPreservesEncodedIdAsSingleSegment(): void
    {
        // security-fix-plan.md finding #61: an untrusted ID like `om_xyz?/foo`
        // must remain a single percent-encoded path segment instead of
        // opening a new path/query when interpolated into a path template.
        // Generated services already call `rawurlencode($id)`; the centralized
        // fix must preserve that encoding (no double-encoding) and not re-split
        // the encoded slash.
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{}'),
        ]);
        $history = [];
        $handler = HandlerStack::create($mock);
        $handler->push(\GuzzleHttp\Middleware::history($history));

        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
            handler: $handler,
        );

        $id = 'om_xyz?/foo';
        $client->request('GET', 'organizations/' . rawurlencode($id));

        $request = $history[array_key_last($history)]['request'];
        $uri = $request->getUri();

        // No query string — `?` inside the ID stayed percent-encoded.
        $this->assertSame('', $uri->getQuery());
        // The whole ID (including its `/`) stayed inside a single segment.
        $this->assertSame('/organizations/om_xyz%3F%2Ffoo', $uri->getPath());

        // Defense-in-depth: a path with a raw `?` in an interpolated ID has
        // the `?` encoded so no stray query string opens. Note that a raw `/`
        // inside an unencoded ID remains a path separator — callers must
        // rawurlencode IDs that can contain `/` (covered by the first case
        // above). Here we use `om_xyz?foo` (no slash) to assert exactly that
        // boundary: `?` is encoded, segment count is preserved.
        $client2 = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
            handler: $handler,
        );
        $mock->append(new Response(200, ['Content-Type' => 'application/json'], '{}'));
        $client2->request('GET', 'organizations/om_xyz?foo');
        $rawRequest = $history[array_key_last($history)]['request'];
        $this->assertSame('', $rawRequest->getUri()->getQuery());
        $this->assertSame('/organizations/om_xyz%3Ffoo', $rawRequest->getUri()->getPath());

        // And confirm the documented caveat: a raw `/` in an unencoded ID is
        // NOT contained — it splits into a new segment. This pins the current
        // behaviour so a future refactor that silently changes it is caught.
        $client3 = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
            handler: $handler,
        );
        $mock->append(new Response(200, ['Content-Type' => 'application/json'], '{}'));
        $client3->request('GET', 'organizations/om_xyz/foo');
        $rawSlashRequest = $history[array_key_last($history)]['request'];
        $this->assertSame('/organizations/om_xyz/foo', $rawSlashRequest->getUri()->getPath());
    }

    public function testEmptyBodySerializesAsJsonObject(): void
    {
        // Issue #400: an all-optional body with every field omitted reduces to
        // an empty PHP array. Guzzle's `json` option encodes that to the JSON
        // array `[]`, which JSON-object endpoints (e.g. challengeFactor on a
        // TOTP factor) reject with a 422. The body must serialize to `{}`.
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{}'),
        ]);
        $history = [];
        $handler = HandlerStack::create($mock);
        $handler->push(\GuzzleHttp\Middleware::history($history));

        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
            handler: $handler,
        );

        $client->request('POST', 'auth/factors/auth_factor_123/challenge', body: []);

        $request = $history[array_key_last($history)]['request'];
        $this->assertSame('{}', (string) $request->getBody());
    }

    public function testNonEmptyBodyStillSerializesAsJsonObject(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{}'),
        ]);
        $history = [];
        $handler = HandlerStack::create($mock);
        $handler->push(\GuzzleHttp\Middleware::history($history));

        $client = new HttpClient(
            apiKey: 'test_key',
            clientId: null,
            baseUrl: 'https://api.workos.com',
            timeout: 10,
            maxRetries: 0,
            handler: $handler,
        );

        $client->request('POST', 'auth/factors/auth_factor_123/challenge', body: ['sms_template' => 'Your code is {{code}}']);

        $request = $history[array_key_last($history)]['request'];
        $this->assertSame('{"sms_template":"Your code is {{code}}"}', (string) $request->getBody());
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
