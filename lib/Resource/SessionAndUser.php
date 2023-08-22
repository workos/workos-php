<?php

namespace WorkOS\Resource;

/**
 * Class SessionAndUser.
 *
 * @property Session  $session
 * @property User $user
 */
class SessionAndUser extends BaseWorkOSResource
{
    public const RESOURCE_ATTRIBUTES = [
        "session",
        "user"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [];

    public static function constructFromResponse($response)
    {
        $instance = parent::constructFromResponse($response);

        $instance->values["session"] = Session::constructFromResponse($response["session"]);
        $instance->values["user"] = User::constructFromResponse($response["user"]);

        return $instance;
    }
}
