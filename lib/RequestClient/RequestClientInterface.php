<?php

namespace WorkOS\RequestClient;

/**
 * Interface RequestClientInterface.
 */
interface RequestClientInterface
{
    /**
     * @param string $method Client method
     * @param string $url URL to resource
     * @param null|array $headers Headers for request
     * @param null|array $params Associative array that'll be passed as query parameters or form data
     *
     * @throws \WorkOS\Exception\GenericException if a client level exception is encountered
     *
     * @return array An array composed of the result string, response headers and status code
     */
    public function request($method, $url, $headers, $params);
}
