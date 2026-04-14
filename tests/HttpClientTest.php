<?php

declare(strict_types=1);
// @oagen-ignore-file

namespace Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use WorkOS\Exception\ApiException;
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
}
