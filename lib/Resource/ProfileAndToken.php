<?php

namespace WorkOS\Resource;

/**
 * Class ProfileAndToken.
 */
class ProfileAndToken extends BaseWorkOSResource
{
    const RESOURCE_ATTRIBUTES = [
        "accessToken"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "access_token" => "accessToken"
    ];

    public static function constructFromResponse($response)
    {
        $instance = parent::constructFromResponse($response);

        $instance->values["profile"] = Profile::constructFromResponse($response["profile"]);

        return $instance;
    }
}
