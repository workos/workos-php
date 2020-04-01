<?php

namespace WorkOS\RequestClient;

/**
 * Class CurlRequestClient.
 */
class CurlRequestClient implements RequestClientInterface
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
    public static function request($method, $url, $headers = null, $params = null)
    {
        $opts = [
         \CURLOPT_URL => $url,
         \CURLOPT_HTTPHEADER => $headers,
         \CURLOPT_RETURNTRANSFER => 1
        ];

        switch ($method) {
            case \WorkOS\Client::METHOD_GET:
                if ($params) {
                    $url .= "?" . http_build_query($params);
                }

                break;

            case \WorkOS\CLIENT::METHOD_POST:
                $headers["Content-Type"] = "application/x-www-form-urlencoded";
                
                $opts[\CURLOPT_POST] = 1;
                $opts[\CURLOPT_POSTFIELDS] = \http_build_query($params);
                
                break;
        }

        return self::execute($opts);
    }

    private static function execute($opts)
    {
        $curl = \curl_init();

        $headers = array();
        $headerCallback = function ($curl, $header_line) use (&$headers) {
            if (false === \strpos($header_line, ":")) {
                return \strlen($header_line);
            }

            list($key, $value) = \explode(":", \trim($header_line), 2);
            $headers[\trim($key)] = \trim($value);

            return \strlen($header_line);
        };
        $opts[\CURLOPT_HEADERFUNCTION] = $headerCallback;
        \curl_setopt_array($curl, $opts);
            
        $result = \curl_exec($curl);

        // I think this is for some sort of internal error
        // Any kind of response that returns a status code won"t hit this block
        if ($result === false) {
            $errno = \curl_errno($curl);
            $msg = \curl_error($curl);
            \curl_close($curl);

            throw new \WorkOS\Exception\GenericException($msg, ["curlErrno" => $errno]);
        } else {
            // Unsure how versions of cURL and PHP correlate so using the legacy
            // reference for getting the last response code
            $statusCode = \curl_getinfo($curl, \CURLINFO_RESPONSE_CODE);
            \curl_close($curl);

            return [$result, $headers, $statusCode];
        }
    }
}
