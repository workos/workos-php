<?php

namespace WorkOS\Resource;

/**
 * Class AuthenticateUserResponse.
 *
 * @property User $user
 */
class AuthenticateUserResponse extends BaseWorkOSResource
{
    public const RESOURCE_ATTRIBUTES = [
        "user"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [];

    public static function constructFromResponse($response)
    {
        $instance = parent::constructFromResponse($response);

        $instance->values["user"] = User::constructFromResponse($response["user"]);

        return $instance;
    }
}
