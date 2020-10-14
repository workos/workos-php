<?php

namespace WorkOS\Resource;

/**
 * Class Profile.
 */
class Profile extends BaseWorkOSResource
{
    const RESOURCE_TYPE = "profile";

    const RESOURCE_ATTRIBUTES = [
        "id",
        "email",
        "firstName",
        "lastName",
        "connectionId",
        "connectionType",
        "idpId",
        "rawAttributes"
    ];

    const RESPONSE_TO_RESOURCE_KEY = [
        "id" => "id",
        "email" => "email",
        "first_name" => "firstName",
        "last_name" => "lastName",
        "connection_id" => "connectionId",
        "connection_type" => "connectionType",
        "idp_id" => "idpId",
        "raw_attributes" => "rawAttributes"
    ];
}
