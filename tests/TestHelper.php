<?php

namespace WorkOS;

use WorkOS\Client;

trait TestHelper
{
    /**
     * @var \WorkOS\RequestClient\RequestClientInterface
     */
    protected $defaultRequestClient;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestClientMock;

    protected function setUp(): void
    {
        $this->defaultRequestClient = Client::requestClient();
        $this->requestClientMock = $this->createMock("\WorkOS\RequestClient\RequestClientInterface");
    }

    protected function tearDown(): void
    {
        WorkOS::setApiKey(null);
        WorkOS::setClientId(null);

        Client::setRequestClient($this->defaultRequestClient);
    }

    // Configuration

    protected function withApiKey($apiKey = "pk_secretsauce")
    {
        WorkOS::setApiKey($apiKey);
    }

    protected function withApiKeyAndClientId($apiKey = "pk_secretsauce", $clientId = "client_pizza")
    {
        WorkOS::setApiKey($apiKey);
        WorkOS::setClientId($clientId);
    }

    // Requests

    protected function mockRequest(
        $method,
        $path,
        $headers = null,
        $params = null,
        $withAuth = false,
        $result = null,
        $responseHeaders = null,
        $responseCode = 200
    ) {
        Client::setRequestClient($this->requestClientMock);

        $url = Client::generateUrl($path);
        if (!$headers) {
            $requestHeaders = Client::generateBaseHeaders($withAuth);
        } else {
            $requestHeaders = \array_merge(Client::generateBaseHeaders($withAuth), $headers);
        }

        if (!$result) {
            $result = "{}";
        }
        if (!$responseHeaders) {
            $responseHeaders = [];
        }

        $this->prepareRequestMock($method, $url, $requestHeaders, $params)
            ->willReturn([$result, $responseHeaders, $responseCode]);
    }

    protected function secondMockRequest(
        $method,
        $path,
        $headers = null,
        $params = null,
        $withAuth = false,
        $result = null,
        $responseHeaders = null,
        $responseCode = 200
    ) {
        Client::setRequestClient($this->requestClientMock);
        $url = Client::generateUrl($path);
        if (!$headers) {
            $requestHeaders = Client::generateBaseHeaders($withAuth);
        } else {
            $requestHeaders = \array_merge(Client::generateBaseHeaders(), $headers);
        }

        if (!$result) {
            $result = "{}";
        }
        if (!$responseHeaders) {
            $responseHeaders = [];
        }

        $this->prepareRequestMock($method, $url, $requestHeaders, $params)
            ->willReturn([$result, $responseHeaders, $responseCode]);
    }

    private function prepareRequestMock($method, $url, $headers, $params)
    {
        return $this->requestClientMock
            ->expects(static::atLeastOnce())->method('request')
            ->with(
                static::identicalTo($method),
                static::identicalTo($url),
                static::identicalTo($headers),
                static::identicalTo($params)
            );
    }

    /**
     * Asserts that a specific deprecation warning is triggered when callable is executed
     *
     * @param string $expected_warning The expected deprecation message
     * @param callable $callable The function or method that should trigger the deprecation
     * @return mixed The return value from the callable
     */
    protected function assertDeprecationTriggered(string $expected_warning, callable $callable)
    {
        $caught = false;

        set_error_handler(function ($errno, $errstr) use ($expected_warning, &$caught) {
            if ($errno === E_USER_DEPRECATED && $errstr === $expected_warning) {
                $caught = true;
                return true;
            }
            return false;
        });

        $result = $callable();

        restore_error_handler();

        if (!$caught) {
            $this->fail('Expected deprecation warning was not triggered: ' . $expected_warning);
        }

        return $result;
    }
}
