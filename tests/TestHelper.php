<?php

namespace WorkOS;

trait TestHelper
{
    protected $defaultRequestClient;
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
}
