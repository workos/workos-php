<?php

namespace WorkOS;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    use TestHelper;

    /**
     * @dataProvider requestExceptionTestProvider
     */
    public function testClientThrowsRequestExceptions($statusCode, $exceptionClass)
    {
        $body = "{\"message\":\"uh-oh\"}";
        $headers = [
            "x-request-id" => "123yocheckme"
        ];
        
        $this->expectException($exceptionClass);

        $this->withApiKeyAndProjectId();
        $this->mockResponse($body, $headers, $statusCode);

        Client::request(Client::METHOD_GET, "\some\place");
    }

    /**
     * @dataProvider requestExceptionTestProvider
     */
    public function testClientThrowsRequestExceptionsWithRequestId($statusCode, $exceptionClass)
    {
        $body = "{\"message\":\"uh-oh\"}";
        $headers = [
            "x-request-id" => "123yocheckme"
        ];
        
        $this->withApiKeyAndProjectId();
        $this->mockResponse($body, $headers, $statusCode);

        try {
            Client::request(Client::METHOD_GET, "\some\place");
        } catch (Exception\BaseRequestException $e) {
            $this->assertEquals($e->requestId, $headers["x-request-id"]);
        }
    }

    /**
     * @dataProvider requestExceptionTestProvider
     */
    public function testClientThrowsRequestExceptionsWithBadMessageAndNoRequestId($statusCode, $exceptionClass)
    {
        $body = "thisaintjson";
        $headers = [];

        $this->withApiKeyAndProjectId();
        $this->mockResponse($body, $headers, $statusCode);

        try {
            Client::request(Client::METHOD_GET, "\some\place");
        } catch (Exception\BaseRequestException $e) {
            $this->assertEquals($e->getMessage(), "");
        }
    }

    // Providers
    public function requestExceptionTestProvider()
    {
        return [
            [400, Exception\BadRequestException::class],
            [401, Exception\AuthenticationException::class],
            [403, Exception\AuthorizationException::class],
            [404, Exception\NotFoundException::class],
            [500, Exception\ServerException::class],
            [503, Exception\ServerException::class],
            [504, Exception\ServerException::class]
        ];
    }
}
