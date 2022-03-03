<?php

namespace WorkOS\Resource;

/**
 * Class ProfileAndToken.
 */
class ProfileAndToken extends BaseWorkOSResource
{
    public const RESOURCE_ATTRIBUTES = [
        "accessToken",
        "profile"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "access_token" => "accessToken"
    ];

    public static function constructFromResponse($response)
    {
        $instance = parent::constructFromResponse($response);

        $instance->values["profile"] = $response["profile"];

        return $instance;
    }
}
