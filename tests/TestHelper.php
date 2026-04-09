<?php

declare(strict_types=1);
// @oagen-ignore-file

namespace WorkOS;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

trait TestHelper
{
    private ?MockHandler $mockHandler = null;
    private array $requestHistory = [];

    protected function loadFixture(string $name): array
    {
        $path = __DIR__ . '/Fixtures/' . $name . '.json';
        if (!file_exists($path)) {
            $this->markTestSkipped("Fixture not found: {$name}.json");
        }
        return json_decode(file_get_contents($path), true);
    }

    protected function createMockClient(
        array $responses,
        string $apiKey = 'test_api_key',
        ?string $clientId = 'test_client_id',
        string $baseUrl = 'https://api.workos.com',
        int $maxRetries = 3,
    ): WorkOS {
        $mockResponses = array_map(
            fn (array $response) => new Response(
                $response['status'] ?? 200,
                $response['headers'] ?? [],
                json_encode($response['body'] ?? [])
            ),
            $responses,
        );

        $this->mockHandler = new MockHandler($mockResponses);
        $handler = HandlerStack::create($this->mockHandler);
        $this->requestHistory = [];
        $handler->push(Middleware::history($this->requestHistory));

        return new WorkOS(
            apiKey: $apiKey,
            clientId: $clientId,
            baseUrl: $baseUrl,
            maxRetries: $maxRetries,
            handler: $handler,
        );
    }

    protected function getLastRequest(): \Psr\Http\Message\RequestInterface
    {
        return $this->requestHistory[array_key_last($this->requestHistory)]['request'];
    }

    protected function getLastRequestOptions(): array
    {
        return $this->requestHistory[array_key_last($this->requestHistory)]['options'];
    }
}
