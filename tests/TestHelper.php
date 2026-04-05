<?php

declare(strict_types=1);

namespace WorkOS;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

trait TestHelper
{
    private ?MockHandler $mockHandler = null;

    protected function loadFixture(string $name): array
    {
        $path = __DIR__ . '/Fixtures/' . $name . '.json';
        if (!file_exists($path)) {
            $this->markTestSkipped("Fixture not found: {$name}.json");
        }
        return json_decode(file_get_contents($path), true);
    }

    protected function createMockClient(array $responses): WorkOS
    {
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

        return new WorkOS(
            apiKey: 'test_api_key',
            handler: $handler,
        );
    }

    protected function getLastRequest(): \Psr\Http\Message\RequestInterface
    {
        return $this->mockHandler->getLastRequest();
    }
}
