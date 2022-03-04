<?php

namespace WorkOS\Resource;

/**
 * Class Profile.
 */
class Profile extends BaseWorkOSResource
{
    public const RESOURCE_TYPE = "profile";

    public const RESOURCE_ATTRIBUTES = [
        "id",
        "email",
        "firstName",
        "lastName",
        "organizationId",
        "connectionId",
        "connectionType",
        "idpId",
        "rawAttributes"
    ];

    public const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "email" => "email",
        "first_name" => "firstName",
        "last_name" => "lastName",
        "organization_id" => "organizationId",
        "connection_id" => "connectionId",
        "connection_type" => "connectionType",
        "idp_id" => "idpId",
        "raw_attributes" => "rawAttributes"
    ];
}
