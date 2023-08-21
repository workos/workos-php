<?php

namespace WorkOS\Resource;

/**
 * Class MagicAuthChallenge.
 */
class MagicAuthChallenge extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "magic_auth_challenge";

    public const RESOURCE_ATTRIBUTES = [
        "object",
        "id"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "object" => "object",
        "id" => "id"
    ];
}
