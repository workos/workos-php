<?php

namespace WorkOS;

trait TestHelper
{
    protected $defaultRequestClient;
    protected $requestClientMock;

    protected function setUp()
    {
        $this->defaultRequestClient = Client::requestClient();
        $this->requestClientMock = $this->createMock("\WorkOS\RequestClient\RequestClientInterface");
    }

    // Configuration

    protected function withApiKey($apiKey = "pk_secretsauce")
    {
        WorkOS::setApiKey($apiKey);
    }

    protected function withProjectId($projectId = "project_pizza")
    {
        WorkOS::setProjectId($projectId);
    }

    protected function withApiKeyAndProjectId($apiKey = "pk_secretsauce", $projectId = "project_pizza")
    {
        WorkOS::setApiKey($apiKey);
        WorkOS::setProjectId($projectId);
    }

    // Requests

    protected function mockRequest(
        $method,
        $path,
        $headers = null,
        $params = null,
        $result = null,
        $responseHeaders = null,
        $responseCode = 200
    ) {
        Client::setRequestClient($this->requestClientMock);

        $url = Client::generateUrl($path);
        if (!$headers) {
            $headers = Client::generateBaseHeaders();
        }
        if (!$result) {
            $result = "{}";
        }
        if (!$responseHeaders) {
            $responseHeaders = [];
        }

        $this->prepareRequestMock($method, $url, $headers, $params)->willReturn([$result, $responseHeaders, $responseCode]);
    }

    private function prepareRequestMock($method, $url, $headers, $params)
    {
        return $this->requestClientMock
            ->expects(static::once())->method('request')
            ->with(
                static::identicalTo($method),
                static::identicalTo($url),
                static::identicalTo($headers),
                static::identicalTo($params)
            );
    }

    protected function tearDown()
    {
        WorkOS::setApiKey(null);
        WorkOS::setProjectId(null);

        Client::setRequestClient($this->defaultRequestClient);
    }
}
