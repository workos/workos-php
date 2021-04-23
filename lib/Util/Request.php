<?php

namespace WorkOS\Util;

class Request
{
    public static function parsePaginationArgs($response)
    {
        return [
            $response["listMetadata"]["before"],
            $response["listMetadata"]["after"]
        ];
    }
}
