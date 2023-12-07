<?php

namespace WorkOS\Resource;

/**
 * Class UserWithOrganizationIdResponse.
 *
 * @property User $user
 * @property string $organizationId
 */
class UserWithOrganizationIdResponse extends BaseWorkOSResource
{
    public const RESOURCE_ATTRIBUTES = [
        "user",
        "organizationId",
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "organization_id" => "organizationId",
    ];

    public static function constructFromResponse($response)
    {
        $instance = parent::constructFromResponse($response);

        $instance->values["user"] = User::constructFromResponse($response["user"]);

        return $instance;
    }
}
