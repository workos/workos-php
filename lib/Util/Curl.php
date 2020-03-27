<?php

namespace WorkOS\Util;

class Curl
{
    const METHOD_TYPE_GET = "get";
    const METHOD_TYPE_POST = "post";

    const VERSION = "dev";

    public static function request($method, $path, $data = null)
    {
        $curl = curl_init();

        $headers = [
            "User-Agent" => "WorkOS PHP/" . self::VERSION
        ];

        switch ($method) {
            case self::METHOD_TYPE_GET:
                $url = self::generateUrl($path, $data);

                break;

            case self::METHOD_TYPE_POST:
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                $url = self::generateUrl($path);

                curl_setopt($curl, CURLOPT_POST, 1);
                
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                }

                break;
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        
        $result = curl_exec($curl);
        
        curl_close($curl);
        return $result;
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
}
