<?php

namespace WorkOS\Exception;

/**
 * Class BaseRequestException.
 *
 * Base Exception for use in filtering a response for information.
 */
class BaseRequestException extends \Exception
{
    public $requestId = "";

    /**
     * BaseRequestException constructor.
     *
     * @param \WorkOS\Resource\Response $response
     * @param null|string $message Exception message
     */
    public function __construct($response, $message = null)
    {
        $this->filterResponseForException($response);

        if (isset($message)) {
            $this->message = $message;
        }
    }

    private function filterResponseForException($response)
    {
        try {
            $responseJson = $response->json();
            $this->message = $responseJson["message"];
        } catch (\Exception $e) {
            $this->message = "";
        }

        if (\array_key_exists("x-request-id", $response->headers)) {
            $this->requestId = $response->headers["x-request-id"];
        }
    }
}
