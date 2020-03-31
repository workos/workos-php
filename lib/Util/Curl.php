<?php

namespace WorkOS\Util;

class Curl
{
    const METHOD_GET = "get";
    const METHOD_POST = "post";

    const VERSION = "dev";

    public static function request($method, $path, $data = null)
    {
        $headers = [
            "User-Agent" => "WorkOS PHP/" . self::VERSION
        ];

        $opts = array();
        switch ($method) {
            case self::METHOD_GET:
                $url = self::generateUrl($path, $data);

                break;

            case self::METHOD_POST:
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                $url = self::generateUrl($path);

                $opts[\CURLOPT_POST] = 1;

                if ($data) {
                    $opts[\CURLOPT_POSTFIELDS] = \http_build_query($data);
                }

                break;
        }

        $opts[\CURLOPT_URL] = $url;
        $opts[\CURLOPT_HTTPHEADER] = $headers;
        $opts[\CURLOPT_RETURNTRANSFER] = 1;
        
        $response = self::execute($opts);

        return $response;
    }

    public static function generateUrl($path, $params = null)
    {
        $url = \WorkOS\WorkOS::getApiBaseUrl() . $path;

        if (is_array($params) && !empty($params)) {
            $queryParams = http_build_query($params);
            $url .= "?" . $queryParams;
        }

        return $url;
    }

    private static function execute($opts)
    {
        $curl = \curl_init();

        $headers = array();
        $headerCallback = function ($curl, $header_line) use (&$headers) {
            if (false === \strpos($header_line, ':')) {
                return \strlen($header_line);
            }

            list($key, $value) = \explode(':', \trim($header_line), 2);
            $headers[\trim($key)] = \trim($value);

            return \strlen($header_line);
        };
        $opts[\CURLOPT_HEADERFUNCTION] = $headerCallback;
        \curl_setopt_array($curl, $opts);
            
        $result = \curl_exec($curl);

        // I think this is for some sort of internal error
        // Any kind of response that returns a status code won't hit this block
        if ($result === false) {
            $errno = \curl_errno($curl);
            $msg = \curl_error($curl);
            \curl_close($curl);

            throw new \WorkOS\Exception\GenericException($msg, ["curlErrno" => $errno]);
        } else {
            // Unsure how versions of cURL and PHP correlate so using the legacy
            // reference for getting the last response code
            $statusCode = \curl_getinfo($curl, \CURLINFO_HTTP_CODE);
            \curl_close($curl);

            $response = new \WorkOS\Resource\Response($result, $headers, $statusCode);

            if ($statusCode >= 400) {
                if ($statusCode >= 500) {
                    throw new \WorkOS\Exception\ServerException($response);
                } elseif ($statusCode === 401) {
                    throw new \WorkOS\Exception\AuthenticationException($response);
                } elseif ($statusCode === 403) {
                    throw new \WorkOS\Exception\AuthorizationException($response);
                }

                throw new \WorkOS\Exception\BadRequestException($response);
            }
            return $response;
        }
    }
}
