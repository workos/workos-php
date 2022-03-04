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
        $this->withApiKeyAndClientId();

        $path = "some/place";

        $this->expectException($exceptionClass);
        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            false,
            null,
            null,
            $statusCode
        );

        Client::request(Client::METHOD_GET, $path);
    }

    /**
     * @dataProvider requestExceptionTestProvider
     */
    public function testClientThrowsRequestExceptionsIncludeRequestId($statusCode, $exceptionClass)
    {
        $this->withApiKeyAndClientId();

        $path = "some/place";
        $responseHeaders = ["x-request-id" => "123yocheckme"];

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            false,
            null,
            $responseHeaders,
            $statusCode
        );

        try {
            Client::request(Client::METHOD_GET, $path);
        } catch (Exception\BaseRequestException $e) {
            $this->assertEquals($e->requestId, $responseHeaders["x-request-id"]);
            return;
        }

        $this->fail("Expected exception of type " . $exceptionClass . " not thrown.");
    }

    /**
     * @dataProvider requestExceptionTestProvider
     */
    public function testClientThrowsRequestExceptionsWithBadMessage($statusCode, $exceptionClass)
    {
        $this->withApiKeyAndClientId();

        $path = "some/place";
        $result = "thisaintjson";

        $this->mockRequest(
            Client::METHOD_GET,
            $path,
            null,
            null,
            false,
            $result,
            null,
            $statusCode
        );

        try {
            Client::request(Client::METHOD_GET, $path);
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
