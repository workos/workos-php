<?php

namespace WorkOS\Exception;

class BaseRequestException extends \Exception
{
    public $requestId;

    public function __construct($response, $message = null)
    {
        $this->filterResponseForException($response);

        if (null !== $message) {
            $this->message = $message;
        }
    }

    private function filterResponseForException($response)
    {
        try {
            $responseJson = $response->json();
            $this->message = $responseJson["message"];
        } catch (Exception $e) {
            $this->message = "";
        }

        $this->requestId = $response->headers["x-request-id"];
    }
}
