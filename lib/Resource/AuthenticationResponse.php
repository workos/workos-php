<?php

namespace WorkOS\Resource;

/**
 * Class AuthenticationResponse.
 *
 * @property User $user
 * @property ?string $organizationId
 * @property string $accessToken
 * @property string $refreshToken
 * @property ?Impersonator $impersonator
 */
class AuthenticationResponse extends BaseWorkOSResource
{
    public const RESOURCE_ATTRIBUTES = [
        "user",
        "organizationId",
        "impersonator",
        "accessToken",
        "refreshToken",
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "organization_id" => "organizationId",
        "access_token" => "accessToken",
        "refresh_token" => "refreshToken",
    ];

    public static function constructFromResponse($response)
    {
        $instance = parent::constructFromResponse($response);

        $instance->values["user"] = User::constructFromResponse($response["user"]);

        if (isset($response["impersonator"])) {
            $instance->values["impersonator"] = Impersonator::constructFromResponse(
                $response["impersonator"]
            );
        }

        return $instance;
    }
}
