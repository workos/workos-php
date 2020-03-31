<?php

namespace WorkOS\Resource;

class Response
{
    public $body;
    public $headers;
    public $json;
    public $statusCode;

    public function __construct($body, $headers, $statusCode)
    {
        $this->body = $body;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
    }

    public function json()
    {
        if (!isset($json)) {
            $this->json = json_decode($this->body, true);
        }

        return $this->json;
    }
}
