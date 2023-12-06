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
            $this->assertEquals($e->getMessage(), $result);
            $this->assertEquals($e->response->json(), json_decode($result, true));
        }
    }

    /**
     * @dataProvider requestExceptionTestProvider
     */
    public function testClientThrowsRequestExceptionsWithMessageAndCode($statusCode, $exceptionClass)
    {
        $this->withApiKeyAndClientId();

        $path = "some/place";
        $result = $this->messageAndCodeFixture();

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
            // var_dump($e);
            $this->assertEquals($e->responseMessage, "Start date cannot be before 2022-06-22T00:00:00.000Z.");
            $this->assertEquals($e->responseCode, "invalid_date_range_exception");
            $this->assertEquals($e->response->json(), json_decode($result, true));
        }
    }

    /**
     * @dataProvider requestExceptionTestProvider
     */
    public function testClientThrowsRequestExceptionsWithErrorAndErrorDescription($statusCode, $exceptionClass)
    {
        $this->withApiKeyAndClientId();

        $path = "some/place";
        $result = $this->errorAndErrorDescriptionFixture();

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
            // var_dump($e);
            $this->assertEquals($e->responseError, "invalid_grant");
            $this->assertEquals($e->responseErrorDescription, "The code '01GDK892VGKGVF2QNWVTABG8MX' has expired or is invalid.");
            $this->assertEquals($e->response->json(), json_decode($result, true));
        }
    }

    /**
     * @dataProvider requestExceptionTestProvider
     */
    public function testClientThrowsRequestExceptionsWithErrors($statusCode, $exceptionClass)
    {
        $this->withApiKeyAndClientId();

        $path = "some/place";
        $result = $this->errorsArrayFixture();

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
            // var_dump($e);
            $this->assertEquals($e->responseErrors, ["invalid_grant", "ambiguous_connection_selector"]);
            $this->assertEquals($e->response->json(), json_decode($result, true));
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

    private function messageAndCodeFixture()
    {
        return json_encode([
            "message" => "Start date cannot be before 2022-06-22T00:00:00.000Z.",
            "code" => "invalid_date_range_exception"
        ]);
    }

    private function errorAndErrorDescriptionFixture()
    {
        return json_encode([
            "error" => "invalid_grant",
            "error_description" => "The code '01GDK892VGKGVF2QNWVTABG8MX' has expired or is invalid."
        ]);
    }

    private function errorsArrayFixture()
    {
        return json_encode([
            "errors" => ["invalid_grant", "ambiguous_connection_selector"]
        ]);
    }
}
