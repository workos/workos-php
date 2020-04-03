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

    protected function mockResponse($body, $headers, $statusCode)
    {
        Client::setRequestClient($this->requestClientMock);

        $this->requestClientMock->method("request")->willReturn([$body, $headers, $statusCode]);
    }

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

    protected function tearDown()
    {
        WorkOS::setApiKey(null);
        WorkOS::setProjectId(null);

        Client::setRequestClient($this->defaultRequestClient);
    }
}
