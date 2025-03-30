<?php

namespace WorkOS\Resource;

/**
 * Class UserIdentityProvider.
 */
class UserIdentityProvider extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "user_identity_provider";

    public const RESOURCE_ATTRIBUTES = [
        "idpId",
        "type",
        "provider",
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "idp_id" => "idpId",
        "type" => "type",
        "provider" => "provider",
    ];

    public static function constructFromResponse($response)
    {
        $instance = parent::constructFromResponse($response);

        $instance->values["type"] = (string) new UserIdentityProviderType($response["type"]);

        return $instance;
    }
}
